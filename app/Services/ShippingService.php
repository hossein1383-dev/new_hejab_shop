<?php

namespace App\Services;

class ShippingService
{
    /**
     * محاسبه هزینه ارسال. فعلاً یک قانون ساده (Flat + رایگان بالای سقف معین)
     * طبق بخش ۱۵. این Assumption صریحاً مستند می‌شود (بخش ۴۶): تا وقتی قوانین
     * واقعی کسب‌وکار (بر اساس استان/وزن) مشخص شود، همین مقدار پیش‌فرض کاربرد دارد.
     *
     * معماری طوری است که اتصال به API واقعی پست/تیپاکس بعداً فقط با تغییر
     * همین متد ممکن است، بدون تاثیر روی بقیه سیستم (بخش ۱۵).
     */
    private const FLAT_RATE = 50000; // تومان
    private const FREE_SHIPPING_THRESHOLD = 2000000; // تومان

    public function calculateCost(string $province, int $orderSubtotal): int
    {
        if ($orderSubtotal >= self::FREE_SHIPPING_THRESHOLD) {
            return 0;
        }

        return self::FLAT_RATE;
    }
}
