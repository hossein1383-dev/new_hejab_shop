<?php

namespace App\Contracts;

use App\Models\Address;

interface ShippingGatewayContract
{
    /**
     * هزینه ارسال یک سفارش به یک آدرس مشخص.
     * @param  int  $totalWeightGrams  مجموع وزن اقلام سفارش (گرم)
     * @param  int  $orderSubtotal  جمع مبلغ سفارش (تومان) — برای قوانینی مثل ارسال رایگان بالای سقف
     * @param  int  $serviceTypeId  بخش ۵۲: پیشتاز=۱ (پیش‌فرض)، ویژه=۳ — فقط توسط HeropostShippingGateway استفاده می‌شود
     */
    public function calculateCost(Address $address, int $totalWeightGrams, int $orderSubtotal, int $serviceTypeId = 1): int;
}
