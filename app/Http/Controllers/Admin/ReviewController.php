<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviewService)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Review::class);

        $reviews = Review::query()
            ->with('user', 'product')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')), fn ($q) => $q->where('status', 'pending'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }

    public function moderate(Request $request, Review $review): RedirectResponse
    {
        $this->authorize('moderate', $review);

        $request->validate(['status' => ['required', 'in:approved,rejected']]);

        $this->reviewService->moderate($review, $request->input('status'), $request->user());

        return back()->with('order_success', 'وضعیت نظر به‌روزرسانی شد.');
    }
}
