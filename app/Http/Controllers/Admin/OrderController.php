<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CheckoutService;
use App\Services\OrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly CheckoutService $checkoutService,
        private readonly \App\Services\HeropostParcelService $heropostParcelService,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        $orders = Order::query()
            ->with('user')
            ->when($request->filled('search'), fn ($q) => $q->where('order_number', 'like', '%' . $request->input('search') . '%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);
        $order->load('items.product', 'statusHistories.user', 'payments', 'user');

        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        $request->validate([
            'status' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $order = $this->orderService->updateStatus(
                $order,
                $request->input('status'),
                $request->user(),
                $request->input('note')
            );
        } catch (\DomainException $e) {
            return back()->with('order_error', $e->getMessage());
        }

        $this->orderService->notifyIfShipped($order);

        return back()->with('order_success', 'وضعیت سفارش به‌روزرسانی شد.');
    }

    /**
     * لغو سفارش + بازگشت خودکار وجه (بخش ۴۹) — چه پرداخت‌نشده باشد
     * (فقط لغو) چه پرداخت‌شده (لغو + بازگشت به کارت یا کیف پول).
     */
    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        try {
            $this->checkoutService->cancelOrder($order, $request->input('note'));
        } catch (\DomainException $e) {
            return back()->with('order_error', $e->getMessage());
        }

        return back()->with('order_success', 'سفارش لغو شد و بازگشت وجه (در صورت نیاز) انجام گردید.');
    }

    /** ثبت مرسوله واقعی در هیروپست و دریافت کد رهگیری — بخش ۵۲. */
    public function createParcel(Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        try {
            $this->heropostParcelService->createParcelForOrder($order);
        } catch (\DomainException $e) {
            return back()->with('order_error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('order_error', 'ثبت مرسوله ناموفق بود: ' . $e->getMessage());
        }

        return back()->with('order_success', 'مرسوله با موفقیت در هیروپست ثبت شد.');
    }
}
