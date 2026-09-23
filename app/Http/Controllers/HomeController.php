<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Services\CartService;
use App\Services\CategoryService;
use App\Services\WishlistService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly CartService $cartService,
        private readonly WishlistService $wishlistService,
    ) {
    }

    /**
     * صفحه اصلی — ترتیب طبق بخش ۲۳: Header, Hero, Popular Categories,
     * Featured Products, New Arrivals, Best Sellers, و ردیف هر دسته‌بندی.
     * Server-Rendered چون محتوای SEO-حساس است (بخش ۴۵).
     */
    public function index(Request $request): View
    {
        $categories = $this->categoryService->activeTree();
        $banners = Banner::where('position', 'home_hero')->activeNow()->orderBy('sort_order')->get();

        // بخش ۳۲/۳۳: کوئری‌های صفحه اصلی روی هر Request سنگین هستند و به‌ندرت
        // تغییر می‌کنند، پس با TTL کوتاه Cache می‌شوند (Eventual Consistency
        // قابل قبول برای این لیست‌ها — بخش ۴۶: Assumption مستند).
        $homeProducts = Cache::remember('home.products', now()->addMinutes(10), function () {
            return [
                'featured' => Product::query()->active()->where('is_featured', true)
                    ->with(['images', 'brand', 'variants'])->latest()->take(8)->get(),
                'new' => Product::query()->active()->where('is_new', true)
                    ->with(['images', 'brand', 'variants'])->latest()->take(8)->get(),
                'bestsellers' => Product::query()->active()->where('is_bestseller', true)
                    ->with(['images', 'brand', 'variants'])->latest()->take(8)->get(),
            ];
        });

        $featuredProducts = $homeProducts['featured'];
        $newProducts = $homeProducts['new'];
        $bestSellers = $homeProducts['bestsellers'];

        // ردیف «۶ محصول از هر دسته‌بندی + مشاهده بیشتر» — بخش ۲۳
        $categoryRows = Cache::remember('home.category_rows', now()->addMinutes(10), function () {
            $rootCategories = Category::active()
                ->roots()
                ->whereHas('products', fn ($q) => $q->active())
                ->orderBy('sort_order')
                ->get();

            // بخش ۴۶: فرض مستند — حداکثر ۳۰۰ محصول جدید در کل دسته‌بندی‌ها
            // خوانده می‌شود (نه کل کاتالوگ) تا حافظه برای فروشگاه‌های خیلی
            // بزرگ منفجر نشود؛ برای مقیاس خیلی بزرگ‌تر باید با Window
            // Function سطح دیتابیس جایگزین شود.
            $productsByCategory = Product::active()
                ->whereIn('category_id', $rootCategories->pluck('id'))
                ->with(['images', 'brand', 'variants'])
                ->latest()
                ->take(300)
                ->get()
                ->groupBy('category_id');

            return $rootCategories->map(fn ($category) => [
                'category' => $category,
                'products' => ($productsByCategory->get($category->id) ?? collect())->take(6),
            ])->filter(fn ($row) => $row['products']->isNotEmpty())->values();
        });

        $cartItemsCount = $this->cartService->currentItemsCount(
            $request->user(),
            $request->session()->getId()
        );

        $wishlistProductIds = $this->wishlistService->productIdsFor($request->user());

        // بخش ۲۵: امتیاز همه محصولات صفحه اصلی در یک کوئری واحد (نه Relation
        // روی هر مدل) چون پشت Cache::remember بالا، Eager Load رابطه نظرات
        // به‌درستی منتقل نمی‌شد و باعث N+1 واقعی می‌شد.
        // بخش ۲۵: «بهترین کالاها» بر اساس میانگین امتیاز واقعی مشتری‌ها —
        // نه سلیقه ادمین. حداقل ۱ نظر تاییدشده لازم است تا محصولی که هنوز
        // هیچ نظری ندارد به‌اشتباه در صدر قرار نگیرد.
        $topRatedProductIds = Cache::remember('home.top_rated_ids', now()->addMinutes(30), function () {
            return Review::query()
                ->where('status', 'approved')
                ->select('product_id')
                ->groupBy('product_id')
                ->orderByRaw('AVG(rating) desc')
                ->take(8)
                ->pluck('product_id');
        });
        $topRatedProducts = Product::active()
            ->whereIn('id', $topRatedProductIds)
            ->with(['images', 'brand', 'variants'])
            ->get()
            ->sortBy(fn ($p) => $topRatedProductIds->search($p->id))
            ->values();

        $allHomeProductIds = $featuredProducts->pluck('id')
            ->merge($newProducts->pluck('id'))
            ->merge($bestSellers->pluck('id'))
            ->merge($topRatedProducts->pluck('id'))
            ->merge($categoryRows->flatMap(fn ($row) => $row['products']->pluck('id')))
            ->unique()
            ->values()
            ->all();
        $ratingsMap = Review::ratingsMapFor($allHomeProductIds);

        // ردیف اسکرول‌شونده وبلاگ (بین محصولات جدید و ردیف‌های دسته‌بندی) —
        // بخش ۳۷. حداکثر ۸ پست آخر.
        $latestBlogPosts = Cache::remember('home.latest_blog_posts', now()->addMinutes(30), function () {
            return BlogPost::published()->latest('published_at')->take(8)->get();
        });

        return view('home', compact(
            'categories',
            'banners',
            'featuredProducts',
            'newProducts',
            'bestSellers',
            'topRatedProducts',
            'categoryRows',
            'cartItemsCount',
            'wishlistProductIds',
            'latestBlogPosts',
            'ratingsMap',
        ));
    }
}
