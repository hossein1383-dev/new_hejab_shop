<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Services\CartService;
use App\Services\CategoryService;
use App\Services\ProductService;
use App\Services\WishlistService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly CategoryService $categoryService,
        private readonly CartService $cartService,
        private readonly WishlistService $wishlistService,
        private readonly \App\Services\InventoryService $inventoryService,
    ) {
    }

    /**
     * لیست محصولات با فیلتر/سورت (بخش ۱۰). هم برای /products عمومی و هم
     * برای /category/{category} استفاده می‌شود.
     */
    public function index(Request $request, ?Category $category = null): View
    {
        $filters = [
            'category_id' => $category?->id,
            'brand_ids' => $request->input('brands', []),
            'min_price' => $request->input('min_price'),
            'max_price' => $request->input('max_price'),
            'q' => $request->input('q'),
            'sort' => $request->input('sort', 'newest'),
        ];

        $products = $this->productService->paginateForStorefront($filters);
        $facets = $this->productService->filterFacets($category?->id);
        $categories = $this->categoryService->activeTree();

        $cartItemsCount = $this->cartService->currentItemsCount(
            $request->user(),
            $request->session()->getId()
        );

        return view('products.index', [
            'products' => $products,
            'facets' => $facets,
            'categories' => $categories,
            'currentCategory' => $category,
            'appliedFilters' => $filters,
            'cartItemsCount' => $cartItemsCount,
            'wishlistProductIds' => $this->wishlistService->productIdsFor($request->user()),
            'ratingsMap' => Review::ratingsMapFor($products->pluck('id')->all()),
        ]);
    }

    /** صفحه محصول (بخش ۱۱). */
    public function show(Request $request, Product $product): View
    {
        abort_unless($product->status === 'active', 404);

        $product->load(['images', 'videos', 'brand', 'category', 'tags', 'variants.attributeValues.attribute']);

        $relatedProducts = Product::query()
            ->active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with('images')
            ->take(8)
            ->get();

        $categories = $this->categoryService->activeTree();

        $cartItemsCount = $this->cartService->currentItemsCount(
            $request->user(),
            $request->session()->getId()
        );

        $reviews = $product->approvedReviews()->with('user')->latest()->paginate(5);
        $questions = $product->questions()->approved()->with('answers.user')->latest()->paginate(5);
        $userHasReviewed = $request->user()
            ? $product->reviews()->where('user_id', $request->user()->id)->exists()
            : false;

        $wishlistProductIds = $this->wishlistService->productIdsFor($request->user());
        $ratingsMap = Review::ratingsMapFor($relatedProducts->pluck('id')->all());

        return view('products.show', [
            'product' => $product,
            'totalAvailableQuantity' => $this->inventoryService->totalAvailableQuantity($product),
            'relatedProducts' => $relatedProducts,
            'categories' => $categories,
            'cartItemsCount' => $cartItemsCount,
            'reviews' => $reviews,
            'questions' => $questions,
            'userHasReviewed' => $userHasReviewed,
            'wishlistProductIds' => $wishlistProductIds,
            'isInWishlist' => in_array($product->id, $wishlistProductIds),
            'ratingsMap' => $ratingsMap,
        ]);
    }
}
