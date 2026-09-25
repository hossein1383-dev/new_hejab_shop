<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductPageController extends Controller
{
    public function __construct(private readonly ProductService $productService)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::query()
            ->with(['category', 'brand', 'images'])
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->input('search') . '%')
                ->orWhere('sku', 'like', '%' . $request->input('search') . '%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('admin.products.form', [
            'product' => new Product(),
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['tags'] = $this->parseTags($request->input('tags_text'));

        $this->productService->create($data);

        return redirect()->route('admin.products.index')->with('order_success', 'محصول ایجاد شد.');
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        $product->load('images', 'tags');

        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        if ($request->filled('tags_text')) {
            $data['tags'] = $this->parseTags($request->input('tags_text'));
        }

        $this->productService->update($product, $data);

        return redirect()->route('admin.products.index')->with('order_success', 'محصول ویرایش شد.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $this->productService->delete($product);

        return redirect()->route('admin.products.index')->with('order_success', 'محصول حذف شد.');
    }

    /**
     * حذف یک تصویر تکی از گالری محصول — بخش ۶۵. قبلاً اصلاً راهی برای این
     * نبود، پس تصاویر فقط اضافه می‌شدند و هیچ‌وقت کم نمی‌شدند.
     */
    public function destroyImage(\App\Models\ProductImage $image): RedirectResponse
    {
        $this->authorize('update', $image->product);

        $wasThumbnail = $image->is_thumbnail;
        $product = $image->product;

        \Illuminate\Support\Facades\Storage::disk('public')->delete($image->path);
        $image->delete();

        // اگر عکس شاخص حذف شد، اولین عکس باقی‌مانده (اگر بود) شاخص جدید شود
        if ($wasThumbnail) {
            $product->images()->orderBy('sort_order')->first()?->update(['is_thumbnail' => true]);
        }

        return back()->with('order_success', 'تصویر حذف شد.');
    }

    private function parseTags(?string $tagsText): array
    {
        if (! $tagsText) {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $tagsText)));
    }
}
