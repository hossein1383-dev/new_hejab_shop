<?php

namespace App\Services;

use App\Contracts\PaymentGatewayContract;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentSuccessfulNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayContract $gateway,
        private readonly InventoryService $inventoryService,
        private readonly WalletService $walletService,
    ) {
    }

    /** ایجاد یک Payment جدید و شروع آن نزد Gateway (بخش ۱۴). */
    public function initiate(Order $order): array
    {
        if ($order->status !== 'pending_payment') {
            throw new \DomainException('این سفارش قابل پرداخت نیست.');
        }

        $result = $this->gateway->initiate($order);

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => config('services.zibal.driver') === 'zibal' ? 'zibal' : 'fake',
            'reference' => $result['reference'],
            'amount' => $order->total,
            'status' => 'pending',
        ]);

        $payment->transactions()->create([
            'type' => 'initiate',
            'status' => 'success',
            'raw_response' => json_encode(['reference' => $result['reference']]),
        ]);

        return $result;
    }

    /**
     * پردازش Callback بازگشتی از Gateway. در برابر موارد زیر محافظت می‌شود
     * (بخش ۱۴):
     * - Duplicate Callback: اگر Payment قبلاً paid شده، دوباره پردازش نمی‌شود (Idempotent)
     * - Amount Mismatch: مبلغ تاییدشده باید دقیقاً با مبلغ سفارش برابر باشد
     * - Race Condition: با lockForUpdate روی ردیف Payment
     */
    public function handleCallback(array $callbackData): Payment
    {
        $verification = $this->gateway->verify($callbackData);
        $shouldNotifySuccess = false;

        $payment = DB::transaction(function () use ($verification, &$shouldNotifySuccess) {
            $payment = Payment::where('reference', $verification['reference'])
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                throw new \DomainException('تراکنش پرداخت یافت نشد.');
            }

            // Duplicate Callback: اگر قبلاً پردازش شده، همان نتیجه قبلی برگردانده می‌شود
            if ($payment->status !== 'pending') {
                return $payment;
            }

            $payment->transactions()->create([
                'type' => 'callback',
                'status' => $verification['success'] ? 'success' : 'failed',
                'raw_response' => json_encode($verification),
            ]);

            if (! $verification['success']) {
                $payment->update(['status' => 'failed']);
                $this->markOrderFailed($payment->order);

                return $payment->fresh();
            }

            // Amount Mismatch: مبلغ تاییدشده باید دقیقاً برابر مبلغ سفارش باشد
            if ($verification['amount'] !== $payment->amount) {
                Log::warning('Payment amount mismatch', [
                    'payment_id' => $payment->id,
                    'expected' => $payment->amount,
                    'received' => $verification['amount'],
                ]);

                $payment->update(['status' => 'failed']);
                $this->markOrderFailed($payment->order);

                return $payment->fresh();
            }

            $payment->update(['status' => 'paid', 'paid_at' => now()]);
            $this->markOrderPaid($payment->order);
            $shouldNotifySuccess = true;

            return $payment->fresh();
        });

        // اعلان بعد از Commit شدن Transaction ارسال می‌شود (بخش ۳۳/۳۶) تا Job
        // صف‌شده با یک Transaction هنوز commit‌نشده مسابقه ندهد.
        if ($shouldNotifySuccess) {
            $payment->order?->user?->notify(new PaymentSuccessfulNotification($payment->order));
        }

        return $payment;
    }

    /**
     * پرداخت سفارش مستقیم از موجودی کیف پول — بخش ۱۸. برخلاف initiate()،
     * نیازی به Gateway/Callback نیست چون همه‌چیز همین لحظه و داخلی مشخص
     * می‌شود؛ ولی همان مسیر markOrderPaid() فاز ۳ (تایید کسر موجودی انبار،
     * تاریخچه وضعیت) عیناً استفاده می‌شود تا هیچ منطقی تکرار نشود.
     */
    public function payWithWallet(Order $order, User $user): Payment
    {
        if ($order->status !== 'pending_payment') {
            throw new \DomainException('این سفارش قابل پرداخت نیست.');
        }

        if ($order->user_id !== $user->id) {
            throw new \DomainException('این سفارش متعلق به شما نیست.');
        }

        $shouldNotifySuccess = false;

        $payment = DB::transaction(function () use ($order, $user, &$shouldNotifySuccess) {
            // اگر موجودی کافی نباشد، WalletService خودش DomainException می‌دهد
            // و کل Transaction (ازجمله هر تغییری که تا اینجا رخ داده) Rollback می‌شود.
            $this->walletService->debit($user, $order->total, $order->order_number, 'پرداخت سفارش ' . $order->order_number);

            $payment = Payment::create([
                'order_id' => $order->id,
                'gateway' => 'wallet',
                'reference' => 'WALLET-' . Str::upper(Str::random(12)),
                'amount' => $order->total,
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $payment->transactions()->create([
                'type' => 'callback',
                'status' => 'success',
                'raw_response' => json_encode(['method' => 'wallet']),
            ]);

            $this->markOrderPaid($order);
            $shouldNotifySuccess = true;

            return $payment;
        });

        if ($shouldNotifySuccess) {
            $order->user?->notify(new PaymentSuccessfulNotification($order));
        }

        return $payment;
    }

    private function markOrderPaid(Order $order): void
    {
        $order->load('items.product', 'items.variant');

        foreach ($order->items as $item) {
            $this->inventoryService->confirmReservedSale(
                $item->product,
                $item->variant,
                $item->quantity,
                $order->order_number
            );
        }

        $order->update(['status' => 'paid', 'paid_at' => now()]);
        $order->statusHistories()->create(['from_status' => 'pending_payment', 'to_status' => 'paid']);
    }

    private function markOrderFailed(Order $order): void
    {
        if ($order->status !== 'pending_payment') {
            return; // یک سفارش که قبلاً paid شده هرگز با Callback بعدی failed نمی‌شود
        }

        $order->load('items.product', 'items.variant');

        foreach ($order->items as $item) {
            $this->inventoryService->release($item->product, $item->variant, $item->quantity);
        }

        $order->update(['status' => 'cancelled']);
        $order->statusHistories()->create(['from_status' => 'pending_payment', 'to_status' => 'cancelled', 'note' => 'پرداخت ناموفق']);
    }
}
