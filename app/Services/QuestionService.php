<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Question;
use App\Models\User;

class QuestionService
{
    public function ask(User $user, Product $product, string $body): Question
    {
        return Question::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'body' => $body,
            'status' => 'pending',
        ]);
    }

    public function answer(Question $question, User $user, string $body): void
    {
        $question->answers()->create(['user_id' => $user->id, 'body' => $body]);

        if ($question->status === 'pending') {
            $question->update(['status' => 'approved']);
        }
    }
}
