<?php

namespace App\Services\SmsGateways;

use App\Contracts\SmsGatewayContract;
use Ipe\Sdk\Facades\SmsIr;

/**
 * اتصال واقعی به SMS.ir برای ارسال کد OTP — بخش ۱۴/۱۵. از متد Verify Send
 * استفاده می‌شود (نه ارسال آزاد) چون کدهای تاییدیه در SMS.ir از یک الگوی
 * از پیش تاییدشده در پنل استفاده می‌کنند.
 *
 * پیش‌نیاز: در پنل SMS.ir یک الگوی تاییدیه با یک پارامتر به نام Code
 * بسازید و شناسه‌اش را در .env به‌عنوان SMSIR_OTP_TEMPLATE_ID بگذارید.
 */
class SmsIrGateway implements SmsGatewayContract
{
    public function __construct(private readonly int $otpTemplateId)
    {
    }

    public function sendOtp(string $phone, string $code): void
    {
        SmsIr::verifySend($phone, $this->otpTemplateId, [
            ['name' => 'Code', 'value' => $code],
        ]);
    }
}
