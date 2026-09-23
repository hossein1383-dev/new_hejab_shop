<?php

namespace App\Services\ShippingGateways;

use App\Contracts\ShippingGatewayContract;
use App\Models\Address;

/**
 * قانون ساده Flat + رایگان بالای سقف معین — بخش ۱۵. همان منطق قبلی
 * ShippingService، فقط پشت Interface مشترک تا با HeropostShippingGateway
 * قابل تعویض باشد. همچنین Fallback امن هنگام خطای هیروپست (بخش ۳۴).
 */
class FlatRateShippingGateway implements ShippingGatewayContract
{
    private const FLAT_RATE = 50000; // تومان
    private const FREE_SHIPPING_THRESHOLD = 2000000; // تومان

    public function calculateCost(Address $address, int $totalWeightGrams, int $orderSubtotal, int $serviceTypeId = 1): int
    {
        if ($orderSubtotal >= self::FREE_SHIPPING_THRESHOLD) {
            return 0;
        }

        return self::FLAT_RATE;
    }
}
