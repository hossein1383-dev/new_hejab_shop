<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BlogPost\StoreBlogPostRequest;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class BlogPostController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);

        $posts = BlogPost::with('category')->latest()->paginate(20);

        return view('admin.blog.index', compact('posts'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);

        return view('admin.blog.form', ['post' => new BlogPost(), 'categories' => BlogCategory::orderBy('name')->get()]);
    }

    public function store(StoreBlogPostRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['author_id'] = $request->user()->id;

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $request->file('featured_image')->store('blog', 'public');
        }
        if ($data['status'] === 'published') {
            $data['published_at'] = now();
        }

        BlogPost::create($data);

        return redirect()->route('admin.blog.index')->with('order_success', 'مطلب ایجاد شد.');
    }

    public function edit(BlogPost $blog_post): View
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);

        return view('admin.blog.form', ['post' => $blog_post, 'categories' => BlogCategory::orderBy('name')->get()]);
    }

    public function update(StoreBlogPostRequest $request, BlogPost $blog_post): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('featured_image')) {
            if ($blog_post->featured_image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($blog_post->featured_image);
            }
            $data['featured_image'] = $request->file('featured_image')->store('blog', 'public');
        } else {
            unset($data['featured_image']);
        }

        if ($data['status'] === 'published' && $blog_post->status !== 'published') {
            $data['published_at'] = now();
        }

        $blog_post->update($data);

        return redirect()->route('admin.blog.index')->with('order_success', 'مطلب ویرایش شد.');
    }

    public function destroy(BlogPost $blog_post): RedirectResponse
    {
        abort_unless(auth()->user()->hasPermission('settings.manage'), 403);

        if ($blog_post->featured_image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($blog_post->featured_image);
        }

        $blog_post->delete();

        return redirect()->route('admin.blog.index')->with('order_success', 'مطلب حذف شد.');
    }
}
