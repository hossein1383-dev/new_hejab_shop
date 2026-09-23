<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * تخمین زنده هزینه ارسال در صفحه Checkout — بخش ۱۵. بدون این، مشتری تا
 * لحظه نهایی نمی‌دانست هزینه ارسال (که حالا بسته به شهر واقعی فرق می‌کند)
 * چقدر می‌شود.
 */
class ShippingEstimateController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
        private readonly CartService $cartService,
    ) {
    }

    public function estimate(Request $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart($request->user(), $request->session()->getId());
        $cart = $this->cartService->withDetails($cart);

        if ($cart->items->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'سبد خرید خالی است.'], 422);
        }

        if ($request->filled('address_id')) {
            $address = Address::find($request->input('address_id'));
            if (! $address || ($request->user() && $address->user_id !== $request->user()->id)) {
                return response()->json(['success' => false, 'message' => 'آدرس معتبر نیست.'], 422);
            }
        } else {
            // آدرس تازه‌ای که هنوز ذخیره نشده — فقط شهر برای تخمین لازم است
            $address = new Address([
                'province' => $request->input('province'),
                'city' => $request->input('city'),
                'heropost_city_id' => $request->input('heropost_city_id'),
            ]);
        }

       $serviceTypeId = (int) $request->input('service_type', 1);
\Illuminate\Support\Facades\Log::info('DEBUG estimate-shipping', ['raw_input' => $request->input('service_type'), 'parsed' => $serviceTypeId, 'heropost_city_id' => $address->heropost_city_id]);
$shippingCost = $this->checkoutService->estimateShippingCost($cart, $address, $serviceTypeId);
\Illuminate\Support\Facades\Log::info('DEBUG estimate-shipping result', ['service_type' => $serviceTypeId, 'shipping_cost' => $shippingCost]);

        return response()->json([
            'success' => true,
            'data' => [
                'shipping_cost' => $shippingCost,
                'subtotal' => $cart->total(),
                'total' => $cart->total() + $shippingCost,
            ],
        ]);
    }
}
