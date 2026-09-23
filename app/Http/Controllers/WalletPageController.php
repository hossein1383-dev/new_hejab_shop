<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\CategoryService;
use App\Services\WalletService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class WalletPageController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly CategoryService $categoryService,
        private readonly CartService $cartService,
    ) {
    }

    public function show(Request $request): View
    {
        $wallet = $this->walletService->getOrCreate($request->user());
        $transactions = $wallet->transactions()->latest()->take(30)->get();

        return view('wallet.show', [
            'wallet' => $wallet,
            'transactions' => $transactions,
            'categories' => $this->categoryService->activeTree(),
            'cartItemsCount' => $this->cartService->currentItemsCount($request->user(), $request->session()->getId()),
        ]);
    }
}
