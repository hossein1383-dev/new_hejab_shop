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

    /**
     * پیامک به سوپر ادمین وقتی سفارش جدید ثبت می‌شود — بخش ۵۴.
     * $adminName = نام سوپر ادمین (گیرنده پیامک)
     * $customerPhone = شماره تلفن مشتری‌ای که سفارش را ثبت کرده
     */
    public function sendNewOrderAdminAlert(string $adminPhone, string $adminName, string $customerPhone): void;

    /** بخش ۵۵: پیامک تایید ثبت سفارش به خودِ مشتری. */
    public function sendOrderConfirmation(string $phone, string $customerName): void;

    /** بخش ۵۵: پیامک تحویل مرسوله به پست + کد رهگیری، به خودِ مشتری. */
    public function sendParcelShipped(string $phone, string $customerName, string $trackingCode): void;

    /** بخش ۵۶: پیامک هدیه کد تخفیف — برای ارسال گروهی به همه مشتریان. */
    public function sendCouponGift(string $phone, string $customerName, string $couponCode): void;
}
