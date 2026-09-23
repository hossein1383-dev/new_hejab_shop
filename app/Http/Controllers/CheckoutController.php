<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\CategoryService;
use App\Services\WalletService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CategoryService $categoryService,
        private readonly WalletService $walletService,
    ) {
    }

    /**
     * صفحه Checkout چندمرحله‌ای (بخش ۱۲): آدرس → ارسال → پرداخت.
     * اگر سبد خالی باشد، کاربر به سبد برمی‌گردد (بخش ۲۹.۱: پیشگیری از خطا).
     */
    public function index(Request $request): View|RedirectResponse
    {
        $cart = $this->cartService->getOrCreateCart($request->user(), $request->session()->getId());
        $cart = $this->cartService->withDetails($cart);

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.show')->with('checkout_error', 'سبد خرید شما خالی است.');
        }

        // بخش ۵۰: خرید مهمان دیگر مجاز نیست — باید قبل از پرداخت وارد شوند.
        if (! $request->user()) {
            session(['url.intended' => route('checkout.show')]);

            return redirect()->route('login')->with('checkout_notice', 'برای ادامه خرید، ابتدا وارد حساب کاربری خود شوید.');
        }

        $addresses = $request->user()?->addresses ?? collect();
        $categories = $this->categoryService->activeTree();
        $cartItemsCount = $cart->itemsCount();
        $walletBalance = $request->user() ? $this->walletService->getOrCreate($request->user())->balance : 0;
        $heropostProvinces = \App\Models\HeropostCity::query()->distinct()->orderBy('province_name')->pluck('province_name');

        return view('checkout.index', compact('cart', 'addresses', 'categories', 'cartItemsCount', 'walletBalance', 'heropostProvinces'));
    }
}
