<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\PlaceOrderRequest;
use App\Models\Address;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
        private readonly CartService $cartService,
        private readonly PaymentService $paymentService,
    ) {
    }

    /**
     * ثبت سفارش از سبد فعلی و شروع پرداخت — بخش ۱۲ و ۱۴.
     * خروجی شامل آدرس Redirect به Gateway است.
     */
    public function store(PlaceOrderRequest $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart($request->user(), $request->session()->getId());

        $shippingAddress = $request->filled('address_id')
            ? Address::findOrFail($request->validated('address_id'))
            : $request->validated('address');

        if ($shippingAddress instanceof Address && $request->user() && $shippingAddress->user_id !== $request->user()->id) {
            abort(403);
        }

        try {
            $order = $this->checkoutService->createOrderFromCart(
                $cart,
                $request->user(),
                [
                    'name' => $request->validated('guest_name'),
                    'email' => $request->validated('guest_email'),
                    'phone' => $request->validated('guest_phone'),
                ],
                $shippingAddress,
                $request->validated('shipping_method', 'standard'),
                $request->validated('coupon_code'),
                (int) $request->input('service_type', 1)
            );

            $this->checkoutService->notifyOrderCreated($order);

            // بخش ۱۸: پرداخت با کیف پول فقط برای کاربر لاگین‌کرده معنا دارد
            if ($request->input('payment_method') === 'wallet' && $request->user()) {
                $this->paymentService->payWithWallet($order, $request->user());

                return response()->json([
                    'success' => true,
                    'message' => 'سفارش با موفقیت از کیف پول پرداخت شد.',
                    'data' => [
                        'order_number' => $order->order_number,
                        'redirect_url' => route('orders.confirmation', ['order' => $order->order_number]),
                    ],
                ], 201);
            }

            $payment = $this->paymentService->initiate($order);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => []], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'سفارش ثبت شد. در حال انتقال به درگاه پرداخت...',
            'data' => [
                'order_number' => $order->order_number,
                'redirect_url' => $payment['redirect_url'],
            ],
        ], 201);
    }
}
