<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\CategoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CategoryService $categoryService,
    ) {
    }

    /**
     * صفحه سبد خرید (بخش ۹). Server-Rendered؛ تعامل‌های بعدی (تغییر تعداد،
     * حذف آیتم) از طریق همان API موجود (POST/PUT/DELETE /cart) با AJAX انجام
     * می‌شوند تا کد تکراری نداشته باشیم.
     */
    public function index(Request $request): View
    {
        $cart = $this->cartService->getOrCreateCart($request->user(), $request->session()->getId());
        $cart = $this->cartService->withDetails($cart);

        $categories = $this->categoryService->activeTree();
        $cartItemsCount = $cart->itemsCount();

        // بخش ۵۳: سفارش‌های در انتظار پرداخت مانده — قبلاً هیچ راهی برای
        // تکمیل پرداختشان وجود نداشت.
        $pendingOrders = $request->user()
            ? $request->user()->orders()->where('status', 'pending_payment')->latest()->get()
            : collect();

        return view('cart.index', compact('cart', 'categories', 'cartItemsCount', 'pendingOrders'));
    }
}
