<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    /**
     * تعداد آیتم‌های سبد فعلی، بدون ساختن یک Cart جدید در دیتابیس اگر وجود ندارد
     * (برای نمایش عدد کنار آیکون سبد در Header، در هر بازدید صفحه — بخش ۲۴).
     */
    public function currentItemsCount(?User $user, string $sessionId): int
    {
        $cart = $user
            ? Cart::where('user_id', $user->id)->where('status', 'active')->first()
            : Cart::where('session_id', $sessionId)->whereNull('user_id')->where('status', 'active')->first();

        return $cart?->itemsCount() ?? 0;
    }

    /** پیدا کردن یا ساختن Cart فعال برای کاربر لاگین‌کرده یا مهمان (بر اساس Session). */
    public function getOrCreateCart(?User $user, string $sessionId): Cart
    {
        if ($user) {
            return Cart::firstOrCreate(
                ['user_id' => $user->id, 'status' => 'active'],
                ['session_id' => $sessionId]
            );
        }

        return Cart::firstOrCreate(
            ['session_id' => $sessionId, 'user_id' => null, 'status' => 'active'],
        );
    }

    /**
     * بارگذاری کامل روابط لازم برای نمایش سبد (تصویر محصول + برچسب Variant)
     * در یک‌جا، تا هم صفحه سبد و هم پاسخ‌های AJAX ساختار یکسان و بدون N+1 داشته باشند.
     */
    /**
     * بارگذاری کامل روابط لازم برای نمایش سبد. آیتم‌هایی که محصولشان از قبل
     * حذف شده (Soft Delete توسط Admin وقتی هنوز در سبد کسی بود) به‌طور
     * خودکار از سبد پاک می‌شوند تا صفحه سبد کرش نکند و مشتری روی چیزی که
     * دیگر برای فروش نیست گیر نکند (بخش ۳: Data Integrity).
     */
    public function withDetails(Cart $cart): Cart
    {
        $cart->load(['items.product.images', 'items.variant.attributeValues.attribute']);

        $orphanedItemIds = $cart->items->whereNull('product')->pluck('id');
        if ($orphanedItemIds->isNotEmpty()) {
            $cart->items()->whereIn('id', $orphanedItemIds)->delete();
            $cart->load('items.product.images', 'items.variant.attributeValues.attribute');
        }

        return $cart;
    }

    /**
     * افزودن محصول به سبد. مقدار نهایی هرگز از موجودی قابل‌فروش بیشتر نمی‌شود
     * (جلوگیری از Overselling در همین لایه، جدا از بررسی نهایی در Checkout).
     */
    public function addItem(Cart $cart, Product $product, ?ProductVariant $variant, int $quantity): Cart
    {
        return DB::transaction(function () use ($cart, $product, $variant, $quantity) {
            $available = $this->inventoryService->availableQuantity($product, $variant);

            $existing = $cart->items()
                ->where('product_id', $product->id)
                ->where('product_variant_id', $variant?->id)
                ->first();

            $currentQty = $existing?->quantity ?? 0;
            $newQty = min($currentQty + $quantity, $available);

            if ($newQty <= 0) {
                throw new \DomainException('موجودی این محصول کافی نیست.');
            }

            $unitPrice = $variant?->effectivePrice() ?? $product->price;

            if ($existing) {
                $existing->update(['quantity' => $newQty, 'unit_price' => $unitPrice]);
            } else {
                $cart->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'quantity' => $newQty,
                    'unit_price' => $unitPrice,
                ]);
            }

            return $this->withDetails($cart->fresh());
        });
    }

    public function updateQuantity(Cart $cart, int $itemId, int $quantity): Cart
    {
        $item = $cart->items()->findOrFail($itemId);

        if ($quantity <= 0) {
            $item->delete();

            return $this->withDetails($cart->fresh());
        }

        $available = $this->inventoryService->availableQuantity($item->product, $item->variant);
        $item->update(['quantity' => min($quantity, $available)]);

        return $this->withDetails($cart->fresh());
    }

    public function removeItem(Cart $cart, int $itemId): Cart
    {
        $cart->items()->where('id', $itemId)->delete();

        return $this->withDetails($cart->fresh());
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
    }

    /**
     * هنگام Login، سبد مهمان با سبد کاربر Merge می‌شود.
     * منطق امن: اگر آیتم مشابه (Product+Variant) هم‌زمان در هر دو سبد بود،
     * مقدار جمع می‌شود ولی هرگز از موجودی قابل فروش عبور نمی‌کند (بخش ۹ و ۴۴).
     */
    public function mergeGuestCartIntoUser(User $user, string $guestSessionId): void
    {
        $guestCart = Cart::where('session_id', $guestSessionId)
            ->whereNull('user_id')
            ->where('status', 'active')
            ->first();

        if (! $guestCart || $guestCart->items->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($user, $guestCart) {
            $userCart = Cart::firstOrCreate(
                ['user_id' => $user->id, 'status' => 'active'],
                ['session_id' => $guestCart->session_id]
            );

            foreach ($guestCart->items as $guestItem) {
                try {
                    $this->addItem(
                        $userCart,
                        $guestItem->product,
                        $guestItem->variant,
                        $guestItem->quantity
                    );
                } catch (\DomainException) {
                    // آیتمی که دیگر موجودی ندارد نادیده گرفته می‌شود؛
                    // کل Merge نباید به‌خاطر یک آیتم ناموجود شکست بخورد.
                    continue;
                }
            }

            $guestCart->items()->delete();
            $guestCart->update(['status' => 'converted']);
        });
    }
}
