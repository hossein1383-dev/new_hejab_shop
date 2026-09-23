<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function __construct(private readonly WishlistService $wishlistService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $wishlist = $this->wishlistService->getOrCreate($request->user())->load('items.product');

        return response()->json([
            'success' => true,
            'message' => null,
            'data' => [
                'products' => $wishlist->items->pluck('product'),
            ],
        ]);
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $this->wishlistService->add($request->user(), $product);

        return response()->json([
            'success' => true,
            'message' => 'به علاقه‌مندی‌ها اضافه شد.',
            'data' => [],
        ], 201);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->wishlistService->remove($request->user(), $product);

        return response()->json([
            'success' => true,
            'message' => 'از علاقه‌مندی‌ها حذف شد.',
            'data' => [],
        ]);
    }
}
