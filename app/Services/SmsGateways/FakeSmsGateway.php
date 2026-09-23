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
}
