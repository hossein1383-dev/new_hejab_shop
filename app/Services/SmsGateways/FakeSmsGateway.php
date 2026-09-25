<?php

namespace App\Services\SmsGateways;

use App\Contracts\SmsGatewayContract;
use Illuminate\Support\Facades\Log;

/**
 * Gateway ساختگی پیامک برای Development/Test — بخش ۱۴/۱۵ (همان الگوی
 * FakePaymentGateway). ⚠️ هرگز در Production استفاده نشود؛ پیام واقعاً به
 * هیچ‌کجا ارسال نمی‌شود، فقط در Log ثبت می‌گردد تا در توسعه کد OTP قابل دیدن باشد.
 */
class FakeSmsGateway implements SmsGatewayContract
{
    public function sendOtp(string $phone, string $code): void
    {
        Log::info("[FakeSmsGateway] کد OTP به {$phone}: {$code}");
    }

    public function sendNewOrderAdminAlert(string $adminPhone, string $adminName, string $customerPhone): void
    {
        Log::info("[FakeSmsGateway] هشدار سفارش جدید به {$adminPhone} ({$adminName}): مشتری {$customerPhone}");
    }

    public function sendOrderConfirmation(string $phone, string $customerName): void
    {
        Log::info("[FakeSmsGateway] تایید سفارش به {$phone} ({$customerName})");
    }

    public function sendParcelShipped(string $phone, string $customerName, string $trackingCode): void
    {
        Log::info("[FakeSmsGateway] اطلاع ارسال مرسوله به {$phone} ({$customerName}): کد {$trackingCode}");
    }

    public function sendCouponGift(string $phone, string $customerName, string $couponCode): void
    {
        Log::info("[FakeSmsGateway] پیامک هدیه تخفیف به {$phone} ({$customerName}): کد {$couponCode}");
    }
}
