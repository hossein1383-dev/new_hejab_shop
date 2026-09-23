<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogCategoryController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);

        $categories = BlogCategory::withCount('posts')->orderBy('name')->get();

        return view('admin.blog.categories', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('settings.manage'), 403);

        $data = $request->validate(['name' => ['required', 'string', 'max:255', 'unique:blog_categories,name']]);
        $data['slug'] = Str::slug($data['name']);

        BlogCategory::create($data);

        return back()->with('order_success', 'دسته وبلاگ ایجاد شد.');
    }

    public function destroy(BlogCategory $blog_category): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);

        if ($blog_category->posts()->exists()) {
            return back()->with('order_error', 'این دسته دارای مطلب است و قابل حذف نیست.');
        }

        $blog_category->delete();

        return back()->with('order_success', 'دسته وبلاگ حذف شد.');
    }
}
