<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Question\AnswerQuestionRequest;
use App\Http\Requests\Question\AskQuestionRequest;
use App\Models\Product;
use App\Models\Question;
use App\Services\QuestionService;
use Illuminate\Http\JsonResponse;

class QuestionController extends Controller
{
    public function __construct(private readonly QuestionService $questionService)
    {
    }

    public function index(Product $product): JsonResponse
    {
        $questions = $product->questions()->approved()->with('answers.user')->latest()->paginate(10);

        return response()->json(['success' => true, 'message' => null, 'data' => $questions]);
    }

    public function store(AskQuestionRequest $request, Product $product): JsonResponse
    {
        $question = $this->questionService->ask($request->user(), $product, $request->validated('body'));

        return response()->json([
            'success' => true,
            'message' => 'سوال شما ثبت شد و پس از بررسی نمایش داده می‌شود.',
            'data' => $question,
        ], 201);
    }

    public function answer(AnswerQuestionRequest $request, Question $question): JsonResponse
    {
        $this->questionService->answer($question, $request->user(), $request->validated('body'));

        return response()->json(['success' => true, 'message' => 'پاسخ ثبت شد.', 'data' => $question->fresh('answers')]);
    }
}
