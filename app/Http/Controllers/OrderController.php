<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly \App\Services\CheckoutService $checkoutService,
    ) {
    }

    /**
     * صفحه ساده تایید سفارش. نسخه کامل (پیگیری سفارش، Timeline وضعیت) در
     * فاز Account/Order History طراحی خواهد شد؛ این فقط تایید فوری بعد از
     * پرداخت است تا Flow کامل بخش ۱۴ قابل تست باشد.
     */
    public function confirmation(string $order): View
    {
        $order = Order::with('items')->where('order_number', $order)->firstOrFail();

        return view('orders.confirmation', compact('order'));
    }

    /**
     * پرداخت مجدد سفارشی که در وضعیت «در انتظار پرداخت» مانده — بخش ۵۳.
     * قبلاً هیچ راهی برای این وجود نداشت (مثلاً اگر مشتری وسط پرداخت
     * مرورگر را می‌بست، سفارش برای همیشه معلق می‌ماند).
     */
    public function retryPayment(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()?->id, 403);

        try {
            $result = $this->paymentService->initiate($order);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * لغو سفارش «در انتظار پرداخت» توسط خودِ مشتری از صفحه سبد خرید —
     * بخش ۶۷. چون سفارش هنوز پرداخت نشده، هیچ بازگشت وجهی لازم نیست؛
     * فقط موجودی رزروشده آزاد می‌شود (همان منطق cancelOrder ادمین).
     */
    public function cancelOwn(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()?->id, 403);

        try {
            $this->checkoutService->cancelOrder($order);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }
}
