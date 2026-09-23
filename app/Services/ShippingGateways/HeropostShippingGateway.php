<?php

namespace App\Services\ShippingGateways;

use App\Contracts\ShippingGatewayContract;
use App\Models\Address;
use App\Services\HeropostClient;
use Illuminate\Support\Facades\Log;

/**
 * استعلام قیمت واقعی از هیروپست — بخش ۱۵.
 *
 * ⚠️ فرض مستند (بخش ۴۶): چون این فروشگاه محصولات متنوعی از نظر
 * نوع/سایز/نوع سرویس پستی ندارد (طبق تصمیم صریح، برای سادگی)، این ۴ مقدار
 * برای همه سفارش‌ها ثابت هستند. اگر بعداً نیاز به تفکیک شد (مثلاً بسته
 * سنگین جدا از سبک)، باید این‌ها را بر اساس وزن/دسته محصول پویا کرد.
 *
 * ⚠️ فرض دیگر: کلیدهای دقیق پاسخ getCities() در مستندات ارائه‌شده مشخص
 * نبود (فقط URL آورده بود، نمونه پاسخ نداشت) — بعد از اولین اجرای واقعی
 * php artisan heropost:sync-cities با اطلاعات ورود واقعی، باید این
 * نگاشت کلیدها تایید/اصلاح شود.
 */
class HeropostShippingGateway implements ShippingGatewayContract
{
    private const PARCEL_CATEGORY_ID = 3; // پوشاک (طبق نمونه مستندات)
    private const BOX_SIZE_ID = 8; // 450×400×300mm — تایید شده
    // ⚠️ مقدار ۱ برای این حساب هیروپست مجاز نبود («شما مجاز به استفاده از
    // سرویس پاکت نمی‌باشید») — ۲ همان مقداری است که در نمونه مستندات هم بود.
    private const PACKET_TYPE_ID = 2;
    private const MIN_WEIGHT_GRAMS = 200; // حداقل وزن قابل قبول هیروپست برای مرسولات سبک

    public function __construct(
        private readonly HeropostClient $client,
        private readonly ShippingGatewayContract $fallback,
    ) {
    }

    public function calculateCost(Address $address, int $totalWeightGrams, int $orderSubtotal, int $serviceTypeId = 1): int
    {
        // آدرس‌های قدیمی‌تر (قبل از این ویژگی) شناسه شهر هیروپست ندارند
        if (! $address->heropost_city_id) {
            return $this->fallback->calculateCost($address, $totalWeightGrams, $orderSubtotal, $serviceTypeId);
        }

        try {
            $result = $this->client->calculatePrice([
                'ToCityID' => $address->heropost_city_id,
                'ParcelCategoryID' => self::PARCEL_CATEGORY_ID,
                'BoxSizeID' => self::BOX_SIZE_ID,
                'ServiceTypeID' => $serviceTypeId,
                'PacketTypeID' => self::PACKET_TYPE_ID,
                'Weight' => max($totalWeightGrams, self::MIN_WEIGHT_GRAMS),
                'ParcelValue' => $orderSubtotal * 10, // تومان → ریال
                // ⚠️ مستندات این را «اختیاری» نوشته بود، ولی API واقعی هیروپست
                // بدون این فیلد خطای ۴۲۲ می‌دهد — پس همیشه صریح ارسال می‌شود.
                'NonStandardPackage' => 0,
            ]);

            return (int) round(($result['TotalPrice'] ?? 0) / 10); // ریال → تومان
        } catch (\Throwable $e) {
            // بخش ۳۴: قطعی سرویس هیروپست نباید کل Checkout را بخواباند —
            // به نرخ ثابت برمی‌گردیم و خطا را Log می‌کنیم برای بررسی بعدی.
            Log::error('Heropost price lookup failed, falling back to flat rate', [
                'address_id' => $address->id,
                'exception' => $e->getMessage(),
            ]);

            return $this->fallback->calculateCost($address, $totalWeightGrams, $orderSubtotal, $serviceTypeId);
        }
    }
}
