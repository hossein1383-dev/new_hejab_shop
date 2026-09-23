<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayContract;
use App\Models\Order;
use Illuminate\Support\Str;

/**
 * Gateway ساختگی برای Development/Test — بخش ۱۴.
 *
 * ⚠️ هرگز در Production استفاده نشود. این کلاس صرفاً برای این ساخته شده که
 * کل Flow پرداخت (بخش ۱۴) قابل تست باشد بدون نیاز به اتصال واقعی به
 * درگاه بانکی. برای Production، یک کلاس دیگر (مثلاً ZarinpalGateway) باید
 * همین Interface را پیاده‌سازی کند و در AppServiceProvider جایگزین این شود.
 */
class FakePaymentGateway implements PaymentGatewayContract
{
    public function initiate(Order $order): array
    {
        return $this->initiateGeneric($order->total, $order->order_number, 'پرداخت سفارش', route('payment.callback'));
    }

    public function initiateGeneric(int $amount, string $referenceId, string $description, string $callbackUrl, ?string $mobile = null): array
    {
        $reference = 'FAKE-' . Str::upper(Str::random(12));

        return [
            'redirect_url' => route('payment.fake.show', ['reference' => $reference]),
            'reference' => $reference,
        ];
    }

    public function verify(array $callbackData): array
    {
        return [
            'success' => ($callbackData['outcome'] ?? null) === 'success',
            'reference' => $callbackData['reference'] ?? '',
            'amount' => (int) ($callbackData['amount'] ?? 0),
        ];
    }

    public function refund(string $trackId, int $amountToman): bool
    {
        return true; // در محیط توسعه/تست همیشه «موفق» فرض می‌شود
    }
}
