<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Models\Product;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviewService)
    {
    }

    public function index(Product $product): JsonResponse
    {
        $reviews = $product->approvedReviews()->with('user', 'images')->latest()->paginate(10);

        return response()->json(['success' => true, 'message' => null, 'data' => $reviews]);
    }

    public function store(StoreReviewRequest $request, Product $product): JsonResponse
    {
        try {
            $review = $this->reviewService->submit(
                $request->user(),
                $product,
                $request->validated('rating'),
                $request->validated('comment'),
                $request->file('images', [])
            );
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => []], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'نظر شما ثبت شد و پس از تایید نمایش داده می‌شود.',
            'data' => $review,
        ], 201);
    }

    /** تایید/رد Review — فقط Admin دارای Permission (بخش ۱۷). */
    public function moderate(Request $request, Review $review): JsonResponse
    {
        $this->authorize('moderate', $review);

        $request->validate(['status' => ['required', 'in:approved,rejected']]);

        $review = $this->reviewService->moderate($review, $request->input('status'), $request->user());

        return response()->json(['success' => true, 'message' => 'وضعیت نظر به‌روزرسانی شد.', 'data' => $review]);
    }
}
