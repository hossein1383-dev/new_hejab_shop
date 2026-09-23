<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Services\CartService;
use App\Services\CategoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly CartService $cartService,
    ) {
    }

    public function index(Request $request): View
    {
        $posts = BlogPost::published()->with('category')->latest('published_at')->paginate(9);

        return view('blog.index', [
            'posts' => $posts,
            'categories' => $this->categoryService->activeTree(),
            'cartItemsCount' => $this->cartService->currentItemsCount($request->user(), $request->session()->getId()),
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $post = BlogPost::published()->where('slug', $slug)->with('category')->firstOrFail();

        $relatedPosts = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('blog.show', [
            'post' => $post,
            'relatedPosts' => $relatedPosts,
            'categories' => $this->categoryService->activeTree(),
            'cartItemsCount' => $this->cartService->currentItemsCount($request->user(), $request->session()->getId()),
        ]);
    }
}
