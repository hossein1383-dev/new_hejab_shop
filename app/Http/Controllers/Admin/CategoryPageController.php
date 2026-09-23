<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * صفحات Admin برای دسته‌بندی (جدا از Admin\CategoryController که API/JSON
 * است). هر دو از همان CategoryService و Form Request ها استفاده می‌کنند تا
 * منطق تجاری تکراری نشود (بخش ۱).
 */
class CategoryPageController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Category::class);

        $categories = Category::query()
            ->with('parent')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->input('search') . '%'))
            ->orderBy('sort_order')
            ->paginate(20)
            ->withQueryString();

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);

        $parents = Category::orderBy('name')->get();

        return view('admin.categories.form', ['category' => new Category(), 'parents' => $parents]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $this->categoryService->create($request->validated());

        return redirect()->route('admin.categories.index')->with('order_success', 'دسته‌بندی ایجاد شد.');
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        $parents = Category::where('id', '!=', $category->id)->orderBy('name')->get();

        return view('admin.categories.form', compact('category', 'parents'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categoryService->update($category, $request->validated());

        return redirect()->route('admin.categories.index')->with('order_success', 'دسته‌بندی ویرایش شد.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        try {
            $this->categoryService->delete($category);
        } catch (\DomainException $e) {
            return back()->with('order_error', $e->getMessage());
        }

        return redirect()->route('admin.categories.index')->with('order_success', 'دسته‌بندی حذف شد.');
    }
}
