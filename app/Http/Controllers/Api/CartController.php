<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart($request->user(), $request->session()->getId());

        return response()->json([
            'success' => true,
            'message' => null,
            'data' => new CartResource($this->cartService->withDetails($cart)),
        ]);
    }

    public function store(AddCartItemRequest $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart($request->user(), $request->session()->getId());
        $product = Product::findOrFail($request->validated('product_id'));
        $variant = $request->filled('product_variant_id')
            ? ProductVariant::findOrFail($request->validated('product_variant_id'))
            : null;

        try {
            $cart = $this->cartService->addItem($cart, $product, $variant, $request->validated('quantity'));
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => []], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'محصول به سبد خرید اضافه شد.',
            'data' => new CartResource($cart),
        ], 201);
    }

    public function update(UpdateCartItemRequest $request, int $item): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart($request->user(), $request->session()->getId());
        $cart = $this->cartService->updateQuantity($cart, $item, $request->validated('quantity'));

        return response()->json([
            'success' => true,
            'message' => 'سبد خرید به‌روزرسانی شد.',
            'data' => new CartResource($cart),
        ]);
    }

    public function destroy(Request $request, int $item): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart($request->user(), $request->session()->getId());
        $cart = $this->cartService->removeItem($cart, $item);

        return response()->json([
            'success' => true,
            'message' => 'آیتم از سبد خرید حذف شد.',
            'data' => new CartResource($cart),
        ]);
    }
}
