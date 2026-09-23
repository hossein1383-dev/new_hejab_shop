<?php

namespace App\Contracts;

/**
 * قرارداد Gateway پیامک — مستقل از هر سرویس خاص (همان الگوی
 * PaymentGatewayContract در بخش ۱۴/۱۵) تا اتصال یک سرویس واقعی
 * (مثلاً SMS.ir) فقط با پیاده‌سازی این Interface ممکن شود.
 *
 * فقط sendOtp دارد (نه یک send عمومی متن آزاد) چون سرویس‌های پیامک ایرانی
 * (ازجمله SMS.ir) برای کدهای تاییدیه از یک «الگوی» از پیش تاییدشده استفاده
 * می‌کنند، نه متن دلخواه — پس این Interface دقیقاً همان چیزی را می‌خواهد
 * که واقعاً لازم است.
 */
interface SmsGatewayContract
{
    public function sendOtp(string $phone, string $code): void;
}
