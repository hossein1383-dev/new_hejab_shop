<?php

namespace App\Services;

use App\Contracts\SmsGatewayContract;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * ثبت و لغو مرسوله واقعی در هیروپست برای یک سفارش — بخش ۵۲.
 *
 * ⚠️ اصلاح مهم بعد از دریافت مستندات کامل: آدرس فروشگاه (مبدا ارسال) در
 * این API اصلاً وجود ندارد — یک‌بار در خودِ پنل هیروپست تنظیم می‌شود، نه
 * با هر تماس API. فیلدهای Customer* اطلاعات «گیرنده» (مشتری شما) هستند،
 * نه فرستنده — نسخه قبلی این فایل این را اشتباه فرض کرده بود.
 */
class HeropostParcelService
{
    /** @var int گرم — همان پیش‌فرض مستند CheckoutService برای محصولات بدون وزن */
    private const DEFAULT_ITEM_WEIGHT_GRAMS = 300;
    private const MIN_WEIGHT_GRAMS = 200;

    // ⚠️ همان مقادیر ثابتی که برای استعلام قیمت هم استفاده شده (بخش ۱۵).
    private const PARCEL_CATEGORY_ID = 3; // پوشاک
    private const BOX_SIZE_ID = 8; // 450×400×300mm — تایید شده توسط شما
    private const SERVICE_TYPE_ID = 1; // پیشتاز — پیش‌فرض؛ «ویژه» (۳) هم قابل استفاده
    private const PACKET_TYPE_ID = 2; // بسته

    public function __construct(
        private readonly HeropostClient $client,
        private readonly SmsGatewayContract $smsGateway,
    ) {
    }

    /**
     * ثبت مرسوله برای یک سفارش پرداخت‌شده — کد رهگیری (ParcelCode) روی
     * خودِ سفارش ذخیره می‌شود.
     */
    public function createParcelForOrder(Order $order): Order
    {
        if ($order->heropost_tracking_code) {
            throw new \DomainException('برای این سفارش قبلاً مرسوله ثبت شده است.');
        }

        if (! $order->shipping_heropost_city_id) {
            throw new \DomainException('این سفارش شناسه شهر هیروپست ندارد (احتمالاً آدرس قدیمی است) — باید دستی در هیروپست ثبت شود.');
        }

        $order->load('items.product', 'user');
        $totalWeightGrams = $order->items->sum(function ($item) {
            $weightKg = $item->product?->weight ?? null;
            $itemWeightGrams = $weightKg ? (int) round($weightKg * 1000) : self::DEFAULT_ITEM_WEIGHT_GRAMS;

            return $itemWeightGrams * $item->quantity;
        });

        // ⚠️ فرض مستند (بخش ۵۲): مستندات این فیلدها را «اختیاری» می‌داند، ولی
        // برای احتیاط همه را دقیقاً مثل نمونه کاری خودِ هیروپست با مقدار
        // پیش‌فرض امن می‌فرستیم — چون نبودن بعضی از آن‌ها ممکن است باعث
        // خطای داخلی (نه پیام واضح رد شدن) در سمت آن‌ها شود.
        $result = $this->client->createParcel([
            'ClientOrderID' => (string) $order->id,
            'CustomerNID' => '',
            'CustomerName' => $order->shipping_name,
            'CustomerFamily' => '',
            'CustomerMobile' => $order->shipping_phone,
            'CustomerEmail' => $order->user?->email ?? '',
            'CustomerPostalCode' => $order->shipping_postal_code,
            'CustomerAddress' => $order->shipping_address_line,
            'ParcelContent' => 'پوشاک',
            'IsReadyToAccept' => 0,
            'PrintRequest' => 0,
            'PrePrintBarcode' => '',
            'ToCityID' => $order->shipping_heropost_city_id,
            'ParcelCategoryID' => self::PARCEL_CATEGORY_ID,
            'BoxSizeID' => self::BOX_SIZE_ID,
            'ServiceTypeID' => $order->shipping_service_type_id ?: self::SERVICE_TYPE_ID,
            'PacketTypeID' => self::PACKET_TYPE_ID,
            'Weight' => max($totalWeightGrams, self::MIN_WEIGHT_GRAMS),
            'ParcelValue' => $order->total * 10, // تومان → ریال
            'PrePaidAmount' => 0,
            'CollectNeed' => 0,
            'NonStandardPackage' => 0,
            'SMSService' => 0,
            'TwoReceiptant' => 0,
        ]);

        $trackingCode = $result['ParcelCode'] ?? null;

        $order->update([
            'heropost_parcel_id' => $trackingCode,
            'heropost_tracking_code' => $trackingCode,
        ]);

        // بخش ۵۵: پیامک اطلاع ارسال مرسوله + کد رهگیری به مشتری
        if ($trackingCode && $order->user) {
            try {
                $this->smsGateway->sendParcelShipped($order->user->phone, $order->user->smsDisplayName(), $trackingCode);
            } catch (\Throwable $e) {
                Log::warning('ارسال پیامک اطلاع مرسوله به مشتری ناموفق بود', [
                    'order_id' => $order->id,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        return $order->fresh();
    }

    /**
     * لغو مرسوله (اگر ثبت شده بود) — هنگام لغو سفارش صدا زده می‌شود.
     * هیچ‌وقت Exception پرتاب نمی‌کند: اگر هیروپست رد کرد یا خطای شبکه
     * داد، فقط در Log ثبت می‌شود تا لغو سفارش (که مهم‌تر است) متوقف نشود.
     */
    public function cancelParcelForOrder(Order $order): void
    {
        if (! $order->heropost_parcel_id) {
            return;
        }

        try {
            $this->client->cancelParcel($order->heropost_parcel_id);
        } catch (\Throwable $e) {
            Log::warning('لغو مرسوله هیروپست ناموفق بود — نیاز به بررسی دستی', [
                'order_id' => $order->id,
                'parcel_id' => $order->heropost_parcel_id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
