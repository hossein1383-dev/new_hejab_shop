<?php

namespace App\Services\SmsGateways;

use App\Contracts\SmsGatewayContract;
use Ipe\Sdk\Facades\SmsIr;

/**
 * اتصال واقعی به SMS.ir — بخش ۱۴/۱۵/۵۴/۵۵. از متد Verify Send استفاده
 * می‌شود (نه ارسال آزاد) چون SMS.ir از الگوهای از پیش تاییدشده در پنل
 * استفاده می‌کند.
 *
 * پیش‌نیازها در پنل SMS.ir:
 * - الگوی OTP با پارامتر Code → SMSIR_OTP_TEMPLATE_ID
 * - الگوی «سفارش جدید» (برای سوپر ادمین) با پارامترهای Name و Username →
 *   SMSIR_NEW_ORDER_TEMPLATE_ID
 * - الگوی «تایید سفارش» (برای مشتری) با پارامتر Username →
 *   SMSIR_ORDER_CONFIRMATION_TEMPLATE_ID
 * - الگوی «ارسال مرسوله» (برای مشتری) با پارامترهای Username و Code →
 *   SMSIR_PARCEL_SHIPPED_TEMPLATE_ID
 * - الگوی «هدیه تخفیف» (برای ارسال گروهی) با پارامترهای Username و Code →
 *   SMSIR_COUPON_GIFT_TEMPLATE_ID
 */
class SmsIrGateway implements SmsGatewayContract
{
    public function __construct(
        private readonly int $otpTemplateId,
        private readonly ?int $newOrderTemplateId = null,
        private readonly ?int $orderConfirmationTemplateId = null,
        private readonly ?int $parcelShippedTemplateId = null,
        private readonly ?int $couponGiftTemplateId = null,
    ) {
    }

    public function sendOtp(string $phone, string $code): void
    {
        SmsIr::verifySend($phone, $this->otpTemplateId, [
            ['name' => 'Code', 'value' => $code],
        ]);
    }

    public function sendNewOrderAdminAlert(string $adminPhone, string $adminName, string $customerPhone): void
    {
        if (! $this->newOrderTemplateId) {
            return; // بخش ۵۴: الگو هنوز در .env تنظیم نشده — بی‌صدا رد می‌شود
        }

        SmsIr::verifySend($adminPhone, $this->newOrderTemplateId, [
            ['name' => 'Name', 'value' => $adminName],
            ['name' => 'Username', 'value' => $customerPhone],
        ]);
    }

    public function sendOrderConfirmation(string $phone, string $customerName): void
    {
        if (! $this->orderConfirmationTemplateId) {
            return;
        }

        SmsIr::verifySend($phone, $this->orderConfirmationTemplateId, [
            ['name' => 'Username', 'value' => $customerName],
        ]);
    }

    public function sendParcelShipped(string $phone, string $customerName, string $trackingCode): void
    {
        if (! $this->parcelShippedTemplateId) {
            return;
        }

        SmsIr::verifySend($phone, $this->parcelShippedTemplateId, [
            ['name' => 'Username', 'value' => $customerName],
            ['name' => 'Code', 'value' => $trackingCode],
        ]);
    }

    public function sendCouponGift(string $phone, string $customerName, string $couponCode): void
    {
        if (! $this->couponGiftTemplateId) {
            return;
        }

        SmsIr::verifySend($phone, $this->couponGiftTemplateId, [
            ['name' => 'Username', 'value' => $customerName],
            ['name' => 'Code', 'value' => $couponCode],
        ]);
    }
}
