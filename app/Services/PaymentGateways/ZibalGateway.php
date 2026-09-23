<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayContract;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * اتصال واقعی به درگاه زیبال — بخش ۱۴/۱۵.
 * مستندات: https://help.zibal.ir/ipg/
 *
 * ⚠️ نکته حیاتی: زیبال مبلغ را به **ریال** می‌گیرد و برمی‌گرداند، ولی در
 * این پروژه همه‌جا مبلغ به **تومان** ذخیره و نمایش داده می‌شود (طبق تمام
 * View های ساخته‌شده که "تومان" نشان می‌دهند). پس هنگام ارسال ×۱۰ و هنگام
 * دریافت ÷۱۰ می‌شود — اگر این فرض اشتباه است (یعنی سیستم واقعاً ریالی
 * است)، این ضرب/تقسیم باید حذف شود.
 */
class ZibalGateway implements PaymentGatewayContract
{
    private const REQUEST_URL = 'https://gateway.zibal.ir/v1/request';
    private const VERIFY_URL = 'https://gateway.zibal.ir/v1/verify';
    private const START_URL = 'https://gateway.zibal.ir/start/';

    /** کدهای «موفق» طبق مستندات زیبال — بخش ۱۴. */
    private const RESULT_SUCCESS = 100;
    private const RESULT_ALREADY_VERIFIED = 201;

    public function __construct(private readonly string $merchant)
    {
    }

    public function initiate(Order $order): array
    {
        return $this->initiateGeneric(
            $order->total,
            $order->order_number,
            'پرداخت سفارش ' . $order->order_number,
            route('payment.callback'),
            $order->shipping_phone
        );
    }

    public function initiateGeneric(int $amount, string $referenceId, string $description, string $callbackUrl, ?string $mobile = null): array
    {
        $response = Http::asJson()->post(self::REQUEST_URL, [
            'merchant' => $this->merchant,
            'amount' => $amount * 10, // تومان → ریال
            'callbackUrl' => $callbackUrl,
            'description' => $description,
            'orderId' => $referenceId,
            'mobile' => $mobile,
        ]);

        $data = $response->json() ?? [];

        if (($data['result'] ?? null) !== self::RESULT_SUCCESS) {
            Log::error('Zibal payment request failed', ['reference_id' => $referenceId, 'response' => $data]);

            throw new \RuntimeException('خطا در اتصال به درگاه پرداخت. لطفاً دوباره تلاش کنید.');
        }

        return [
            'redirect_url' => self::START_URL . $data['trackId'],
            'reference' => (string) $data['trackId'],
        ];
    }

    public function verify(array $callbackData): array
    {
        $trackId = $callbackData['trackId'] ?? null;

        // بخش ۳۴: اگر کاربر قبل از تکمیل پرداخت از درگاه برگردد، زیبال
        // success=0 می‌فرستد؛ در این حالت هم باز باید trackId را با Verify
        // واقعی چک کنیم، نه فقط به همین پارامتر اعتماد کنیم (که جعل آن
        // توسط کاربر در URL ساده است).
        if (! $trackId) {
            return ['success' => false, 'reference' => '', 'amount' => 0];
        }

        $response = Http::asJson()->post(self::VERIFY_URL, [
            'merchant' => $this->merchant,
            'trackId' => $trackId,
        ]);

        $data = $response->json() ?? [];
        $result = $data['result'] ?? null;

        $success = in_array($result, [self::RESULT_SUCCESS, self::RESULT_ALREADY_VERIFIED], true);

        if (! $success) {
            Log::warning('Zibal payment verify failed', ['track_id' => $trackId, 'response' => $data]);
        }

        return [
            'success' => $success,
            'reference' => (string) $trackId,
            'amount' => $success ? (int) round(($data['amount'] ?? 0) / 10) : 0, // ریال → تومان
        ];
    }

    /**
     * ⚠️ فرض مستند (بخش ۴۹): آدرس دقیق API استرداد وجه زیبال پشت پنل
     * کاربری‌شان است و در مستندات عمومی پیدا نشد؛ این آدرس حدسی است (بر
     * اساس الگوی request/verify فعلی‌شان) و **تایید نشده**. این سرویس هم
     * فقط بعد از فعال‌سازی «بانکداری شرکتی» در حساب زیبال کار می‌کند.
     * به همین دلیل کاملاً محافظه‌کارانه نوشته شده: هر پاسخ غیرمنتظره یا
     * خطایی (حتی خطای شبکه) باعث false می‌شود، نه Exception — تا فراخوان
     * (RefundService) بتواند بی‌خطر به بازگشت به کیف پول برگردد.
     */
    public function refund(string $trackId, int $amountToman): bool
    {
        try {
            $response = Http::asJson()->timeout(15)->post('https://api.zibal.ir/v1/refund', [
                'merchant' => $this->merchant,
                'trackId' => (int) $trackId,
                'amount' => $amountToman * 10, // تومان → ریال
            ]);

            $data = $response->json() ?? [];
            $success = ($data['result'] ?? null) === self::RESULT_SUCCESS;

            if (! $success) {
                Log::warning('Zibal refund not available or failed — falling back to wallet', ['track_id' => $trackId, 'response' => $data]);
            }

            return $success;
        } catch (\Throwable $e) {
            Log::warning('Zibal refund request threw an exception — falling back to wallet', ['track_id' => $trackId, 'exception' => $e->getMessage()]);

            return false;
        }
    }
}
