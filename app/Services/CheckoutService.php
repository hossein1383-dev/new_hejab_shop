<?php

namespace App\Services;

use App\Contracts\PaymentGatewayContract;
use App\Contracts\ShippingGatewayContract;
use App\Contracts\SmsGatewayContract;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderCreatedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CheckoutService
{
    /** @var int گرم — فرض مستند (بخش ۴۶) برای محصولاتی که وزن ثبت‌شده ندارند */
    private const DEFAULT_ITEM_WEIGHT_GRAMS = 300;

    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly ShippingGatewayContract $shippingGateway,
        private readonly CartService $cartService,
        private readonly CouponService $couponService,
        private readonly WalletService $walletService,
        private readonly PaymentGatewayContract $paymentGateway,
        private readonly HeropostParcelService $heropostParcelService,
        private readonly SmsGatewayContract $smsGateway,
    ) {
    }

    /**
     * ایجاد سفارش از سبد خرید فعلی. قیمت و موجودی هرگز از Cart یا Client
     * پذیرفته نمی‌شوند؛ همه‌چیز از داده فعلی Product/Variant در Backend
     * دوباره محاسبه می‌شود (بخش ۱۲ و ۳۴: هرگز به قیمت/موجودی از Frontend
     * اعتماد نکن). کل عملیات (بررسی موجودی + رزرو + ساخت سفارش) Atomic است.
     *
     * @throws \DomainException وقتی سبد خالی است یا موجودی کافی نیست
     */
    public function createOrderFromCart(
        Cart $cart,
        ?User $user,
        array $guestInfo,
        Address|array $shippingAddress,
        string $shippingMethod = 'standard',
        ?string $couponCode = null,
        int $serviceTypeId = 1,
    ): Order {
        $cart->load('items.product', 'items.variant');

        if ($cart->items->isEmpty()) {
            throw new \DomainException('سبد خرید شما خالی است.');
        }

        // بخش ۵۰: خرید مهمان دیگر مجاز نیست — این محافظت هم‌سطح سرویس است
        // (علاوه بر Redirect سطح Controller) تا هیچ مسیر دیگری نتواند این
        // قانون را دور بزند.
        if (! $user) {
            throw new \DomainException('برای ثبت سفارش باید وارد حساب کاربری خود شوید.');
        }

        return DB::transaction(function () use ($cart, $user, $guestInfo, $shippingAddress, $shippingMethod, $couponCode, $serviceTypeId) {
            $subtotal = 0;
            $taxTotal = 0;
            $lineData = [];

            foreach ($cart->items as $item) {
                $product = $item->product;
                $variant = $item->variant;

                // بررسی مجدد موجودی درست قبل از رزرو (بخش ۸: جلوگیری از Overselling)
                $available = $this->inventoryService->availableQuantity($product, $variant);
                if ($available < $item->quantity) {
                    throw new \DomainException("موجودی «{$product->name}» کافی نیست.");
                }

                // قیمت همیشه از داده فعلی محصول خوانده می‌شود، نه از Cart (بخش ۱۲)
                $unitPrice = $variant?->effectivePrice() ?? $product->price;
                $lineTotal = $unitPrice * $item->quantity;
                $subtotal += $lineTotal;
                $taxTotal += (int) round($lineTotal * ((float) $product->tax_percent / 100));

                $lineData[] = [
                    'product' => $product,
                    'variant' => $variant,
                    'quantity' => $item->quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }

            // بخش ۱۵: مجموع وزن سفارش (گرم) برای استعلام قیمت پستی واقعی —
            // اگر وزن محصولی ثبت نشده باشد، از یک وزن پیش‌فرض مستند استفاده می‌شود.
            $totalWeightGrams = $this->calculateTotalWeightGrams(collect($lineData));

            $addressForShipping = $shippingAddress instanceof Address ? $shippingAddress : new Address($shippingAddress);
            $shippingCost = $this->shippingGateway->calculateCost($addressForShipping, $totalWeightGrams, $subtotal, $serviceTypeId);

            $discountTotal = 0;
            $coupon = null;

            if ($couponCode) {
                $coupon = $this->couponService->findValidCoupon($couponCode, $user, $subtotal);
                $discountTotal = $this->couponService->calculateDiscount(
                    $coupon,
                    collect($lineData)->map(fn ($line) => ['product' => $line['product'], 'line_total' => $line['line_total']])
                );
            }

            $total = $subtotal - $discountTotal + $taxTotal + $shippingCost;

            $addressFields = is_array($shippingAddress) ? $shippingAddress : [
                'address_id' => $shippingAddress->id,
                'receiver_name' => $shippingAddress->receiver_name,
                'phone' => $shippingAddress->phone,
                'province' => $shippingAddress->province,
                'city' => $shippingAddress->city,
                'address_line' => $shippingAddress->address_line,
                'postal_code' => $shippingAddress->postal_code,
                'heropost_city_id' => $shippingAddress->heropost_city_id,
            ];

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $user?->id,
                'guest_name' => $user ? null : ($guestInfo['name'] ?? null),
                'guest_email' => $user ? null : ($guestInfo['email'] ?? null),
                'guest_phone' => $user ? null : ($guestInfo['phone'] ?? null),
                'address_id' => $addressFields['address_id'] ?? null,
                'shipping_name' => $addressFields['receiver_name'],
                'shipping_phone' => $addressFields['phone'],
                'shipping_province' => $addressFields['province'],
                'shipping_city' => $addressFields['city'],
                'shipping_heropost_city_id' => $addressFields['heropost_city_id'] ?? null,
                'shipping_service_type_id' => $serviceTypeId,
                'shipping_address_line' => $addressFields['address_line'],
                'shipping_postal_code' => $addressFields['postal_code'] ?? null,
                'shipping_method' => $shippingMethod,
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'shipping_cost' => $shippingCost,
                'total' => $total,
                'status' => 'pending_payment',
            ]);

            foreach ($lineData as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'product_variant_id' => $line['variant']?->id,
                    'product_name' => $line['product']->name,
                    'sku' => $line['variant']?->sku ?? $line['product']->sku,
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ]);

                // رزرو موجودی تا زمان تایید یا لغو پرداخت (بخش ۸ و ۱۴)
                $this->inventoryService->reserve($line['product'], $line['variant'], $line['quantity']);
            }

            $order->statusHistories()->create([
                'from_status' => null,
                'to_status' => 'pending_payment',
                'user_id' => $user?->id,
            ]);

            if ($coupon) {
                $this->couponService->recordUsage($coupon, $user, $order, $discountTotal);
            }

            // سبد خالی می‌شود ولی خودِ Cart باقی می‌ماند تا کاربر بتواند دوباره خرید کند
            $this->cartService->clear($cart);

            return $order->fresh('items');
        });
    }

    /**
     * ارسال Notification ثبت سفارش، جدا و بعد از Commit شدن Transaction اصلی
     * (بخش ۳۳/۳۶) — تا Job صف‌شده با یک Transaction هنوز commit‌نشده مسابقه ندهد.
     * این متد بعد از createOrderFromCart در Controller صدا زده می‌شود.
     */
    public function notifyOrderCreated(Order $order): void
    {
        $order->user?->notify(new OrderCreatedNotification($order));

        // بخش ۵۵: پیامک تایید سفارش به خودِ مشتری
        if ($order->user) {
            try {
                $this->smsGateway->sendOrderConfirmation($order->user->phone, $order->user->smsDisplayName());
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('ارسال پیامک تایید سفارش به مشتری ناموفق بود', [
                    'order_id' => $order->id,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        // بخش ۵۴: به همه سوپر ادمین‌ها پیامک هشدار سفارش جدید فرستاده می‌شود.
        $superAdmins = \App\Models\User::whereHas('roles', fn ($q) => $q->where('slug', 'super-admin'))
            ->whereNotNull('phone')
            ->get();

        foreach ($superAdmins as $admin) {
            try {
                $this->smsGateway->sendNewOrderAdminAlert($admin->phone, $admin->name, $order->shipping_phone);
            } catch (\Throwable $e) {
                // هرگز نباید ثبت سفارش را به‌خاطر خطای پیامک متوقف کند
                \Illuminate\Support\Facades\Log::warning('ارسال پیامک هشدار سفارش جدید به سوپر ادمین ناموفق بود', [
                    'order_id' => $order->id,
                    'admin_id' => $admin->id,
                    'exception' => $e->getMessage(),
                ]);
            }
        }
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }

    /**
     * لغو سفارشی که پرداخت نشده (مثلاً Timeout یا انصراف کاربر) — موجودی
     * رزروشده آزاد می‌شود (بخش ۱۴).
     */
    /**
     * لغو سفارش — بخش ۴۹. برخلاف نسخه قبلی که فقط سفارش پرداخت‌نشده را
     * می‌پذیرفت، این نسخه سفارش پرداخت‌شده را هم لغو می‌کند و پول را
     * خودکار برمی‌گرداند: کیف پول → همان‌جا برمی‌گردد؛ درگاه → ابتدا تلاش
     * می‌شود مستقیم به کارت برگردد، اگر نشد (سرویس فعال نیست یا خطا داد)
     * به کیف پول مشتری اعتبار داده می‌شود — پول هرگز گم نمی‌شود مگر
     * سفارش مهمان باشد که در آن صورت فقط در Log هشدار بحرانی ثبت و باید
     * دستی رسیدگی شود (چون کاربر مهمان کیف پولی برای اعتباردهی ندارد).
     */
    public function cancelOrder(Order $order, ?string $note = null): void
    {
        if (! in_array($order->status, ['pending_payment', 'paid', 'preparing'], true)) {
            throw new \DomainException('این سفارش دیگر قابل لغو نیست.');
        }

        $fromStatus = $order->status;
        $wasPaid = $fromStatus !== 'pending_payment';

        DB::transaction(function () use ($order, $fromStatus, $wasPaid, $note) {
            foreach ($order->items as $item) {
                $this->inventoryService->release($item->product, $item->variant, $item->quantity);
            }

            // بخش ۵۲: اگر مرسوله‌ای در هیروپست ثبت شده بود، لغوش هم می‌شود —
            // این هیچ‌وقت لغو سفارش را متوقف نمی‌کند (فقط در Log هشدار می‌دهد)
            $this->heropostParcelService->cancelParcelForOrder($order);

            $newStatus = $wasPaid ? 'refunded' : 'cancelled';
            $order->update(['status' => $newStatus]);
            $order->statusHistories()->create([
                'from_status' => $fromStatus,
                'to_status' => $newStatus,
                'note' => $note,
            ]);

            if ($wasPaid) {
                $this->refundPaidOrder($order);
            }
        });
    }

    private function refundPaidOrder(Order $order): void
    {
        $payment = $order->payments()->where('status', 'paid')->latest()->first();

        if (! $payment) {
            Log::critical('لغو سفارش پرداخت‌شده بدون رکورد پرداخت موفق — نیاز به بررسی دستی', ['order_id' => $order->id]);

            return;
        }

        if ($payment->gateway === 'wallet') {
            // پرداخت کیف پول همیشه یعنی کاربر عضو بوده (مهمان کیف پول ندارد)
            $this->walletService->credit($order->user, $order->total, "refund-order-{$order->id}", "بازگشت وجه لغو سفارش {$order->order_number}");

            return;
        }

        $refundedToCard = $this->paymentGateway->refund($payment->reference, $order->total);

        if (! $refundedToCard) {
            if ($order->user) {
                $this->walletService->credit($order->user, $order->total, "refund-order-{$order->id}", "بازگشت وجه (به کیف پول، چون بازگشت مستقیم به کارت ممکن نشد) برای سفارش {$order->order_number}");
            } else {
                Log::critical('استرداد به کارت ناموفق بود و سفارش مهمان است (کیف پول ندارد) — نیاز فوری به بازگشت وجه دستی', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'amount' => $order->total,
                    'guest_phone' => $order->guest_phone,
                ]);
            }
        }
    }

    /**
     * تخمین زنده هزینه ارسال برای نمایش در صفحه Checkout — بدون ساخت
     * سفارش. همان منطق واقعی createOrderFromCart استفاده می‌شود تا هیچ‌وقت
     * عددی که به مشتری نشان داده می‌شود با مبلغ نهایی فرق نکند.
     */
    public function estimateShippingCost(Cart $cart, Address $address, int $serviceTypeId = 1): int
    {
        $lineData = $cart->items->map(fn ($item) => ['product' => $item->product, 'quantity' => $item->quantity]);
        $totalWeightGrams = $this->calculateTotalWeightGrams($lineData);
        $subtotal = $cart->total();

        return $this->shippingGateway->calculateCost($address, $totalWeightGrams, $subtotal, $serviceTypeId);
    }

    /** @param  \Illuminate\Support\Collection<int, array{product: \App\Models\Product, quantity: int}>  $lineData */
    private function calculateTotalWeightGrams($lineData): int
    {
        return $lineData->sum(function ($line) {
            $weightKg = $line['product']->weight ?? null;
            $itemWeightGrams = $weightKg ? (int) round($weightKg * 1000) : self::DEFAULT_ITEM_WEIGHT_GRAMS;

            return $itemWeightGrams * $line['quantity'];
        });
    }
}
