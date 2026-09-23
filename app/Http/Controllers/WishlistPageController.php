<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Services\CartService;
use App\Services\CategoryService;
use App\Services\WishlistService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class WishlistPageController extends Controller
{
    public function __construct(
        private readonly WishlistService $wishlistService,
        private readonly CategoryService $categoryService,
        private readonly CartService $cartService,
    ) {
    }

    public function show(Request $request): View
    {
        $wishlist = $this->wishlistService->getOrCreate($request->user())
            ->load('items.product.images', 'items.product.brand', 'items.product.variants');

        // آیتم‌هایی که محصولشان از قبل حذف شده (Soft Delete) پاک می‌شوند تا
        // صفحه کرش نکند (همان مشکل CartService::withDetails)
        $orphanedItemIds = $wishlist->items->whereNull('product')->pluck('id');
        if ($orphanedItemIds->isNotEmpty()) {
            $wishlist->items()->whereIn('id', $orphanedItemIds)->delete();
            $wishlist->load('items.product.images', 'items.product.brand', 'items.product.variants');
        }

        $products = $wishlist->items->pluck('product');

        return view('wishlist.show', [
            'products' => $products,
            'categories' => $this->categoryService->activeTree(),
            'cartItemsCount' => $this->cartService->currentItemsCount($request->user(), $request->session()->getId()),
            'ratingsMap' => Review::ratingsMapFor($products->pluck('id')->all()),
        ]);
    }
}
