<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(
        private readonly CouponService $couponService,
        private readonly CartService $cartService,
    ) {
    }

    /**
     * پیش‌نمایش زنده تخفیف کوپن روی سبد فعلی، بدون ثبت سفارش (بخش ۲۹.۱:
     * Feedback فوری). اعتبارسنجی نهایی و قطعی باز هم در لحظه Checkout انجام
     * می‌شود (بخش ۱۶: Validation کوپن همیشه در Backend).
     */
    public function validateCode(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $cart = $this->cartService->getOrCreateCart($request->user(), $request->session()->getId());
        $cart = $this->cartService->withDetails($cart);

        if ($cart->items->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'سبد خرید شما خالی است.', 'errors' => []], 422);
        }

        $lines = $cart->items->map(fn ($item) => [
            'product' => $item->product,
            'line_total' => $item->lineTotal(),
        ]);

        $subtotal = $lines->sum('line_total');

        try {
            $coupon = $this->couponService->findValidCoupon($request->input('code'), $request->user(), $subtotal);
            $discount = $this->couponService->calculateDiscount($coupon, $lines);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => []], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'کد تخفیف اعمال شد.',
            'data' => [
                'discount_amount' => $discount,
                'new_total' => max(0, $subtotal - $discount),
            ],
        ]);
    }
}
