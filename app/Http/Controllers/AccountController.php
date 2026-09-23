<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CartService;
use App\Services\CategoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly CartService $cartService,
    ) {
    }

    /**
     * داده‌های مشترک همه صفحات حساب کاربری — Header (چه موبایل چه دسکتاپ)
     * به‌طور مستقیم به این دو متغیر نیاز دارد.
     */
    private function sharedViewData(Request $request): array
    {
        return [
            'categories' => $this->categoryService->activeTree(),
            'cartItemsCount' => $this->cartService->currentItemsCount(
                $request->user(),
                $request->session()->getId()
            ),
        ];
    }

    public function profile(Request $request): View
    {
        return view('account.profile', array_merge(
            $this->sharedViewData($request),
            ['user' => $request->user()]
        ));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,' . $request->user()->id],
        ]);

        $request->user()->update($data);

        return back()->with('order_success', 'اطلاعات حساب شما به‌روزرسانی شد.');
    }

    public function orders(Request $request): View
    {
        $orders = $request->user()->orders()->with('items')->latest()->paginate(10);

        return view('account.orders', array_merge(
            $this->sharedViewData($request),
            compact('orders')
        ));
    }

    /** جزئیات یک سفارش — فقط اگر متعلق به همین کاربر باشد (بخش ۳۴: Authorization). */
    public function orderShow(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        $order->load('items', 'statusHistories', 'shipment');

        return view('account.order-show', array_merge(
            $this->sharedViewData($request),
            compact('order')
        ));
    }

    public function addresses(Request $request): View
    {
        $addresses = $request->user()->addresses;
        $heropostProvinces = \App\Models\HeropostCity::query()
            ->distinct()
            ->orderBy('province_name')
            ->pluck('province_name');

        return view('account.addresses', array_merge(
            $this->sharedViewData($request),
            compact('addresses', 'heropostProvinces')
        ));
    }
}
