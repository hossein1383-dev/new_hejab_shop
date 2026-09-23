<?php

namespace App\Contracts;

use App\Models\Order;

/**
 * قرارداد Gateway پرداخت — بخش ۱۴ و ۱۵: معماری نباید به یک Gateway خاص وابسته
 * باشد. برای افزودن یک Gateway واقعی (مثلاً ZarinPal)، فقط کافی است این
 * Interface را پیاده‌سازی کنید و در PaymentService جایگزین کنید؛ هیچ کد
 * دیگری در سیستم نیازی به تغییر ندارد.
 */
interface PaymentGatewayContract
{
    /**
     * شروع پرداخت نزد Gateway برای یک سفارش مشخص.
     * @return array{redirect_url: string, reference: string}
     */
    public function initiate(Order $order): array;

    /**
     * شروع پرداخت عمومی که به یک Order وابسته نیست (مثلاً شارژ کیف پول —
     * بخش ۱۸). امضای جدا از initiate() چون آنجا کل شیء Order لازم است
     * (برای شماره سفارش/آدرس)، ولی اینجا فقط مبلغ و توضیح کافی است.
     * @return array{redirect_url: string, reference: string}
     */
    public function initiateGeneric(int $amount, string $referenceId, string $description, string $callbackUrl, ?string $mobile = null): array;

    /**
     * راستی‌آزمایی نتیجه پرداخت پس از بازگشت از Gateway.
     * @return array{success: bool, reference: string, amount: int}
     */
    public function verify(array $callbackData): array;

    /**
     * استرداد وجه مستقیم به کارت مبدا — بخش ۴۹. اگر Gateway از این
     * پشتیبانی نکند (یا سرویس فعال نباشد)، false برمی‌گرداند تا فراخوان
     * بتواند به‌جایش به کیف پول برگرداند — پول هرگز گم نمی‌شود.
     */
    public function refund(string $trackId, int $amountToman): bool;
}
