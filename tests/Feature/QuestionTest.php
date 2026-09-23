<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_ask_question_and_it_starts_pending(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user)->postJson("/products/{$product->id}/questions", [
            'body' => 'آیا این محصول گارانتی دارد؟',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('questions', ['product_id' => $product->id, 'status' => 'pending']);
    }

    public function test_answering_a_question_makes_it_approved_and_visible(): void
    {
        $asker = User::factory()->create();
        $answerer = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($asker)->postJson("/products/{$product->id}/questions", ['body' => 'سوال تست؟']);
        $question = $product->questions()->first();

        $this->actingAs($answerer)->postJson("/questions/{$question->id}/answer", ['body' => 'بله، دارد.'])
            ->assertOk();

        $this->assertDatabaseHas('questions', ['id' => $question->id, 'status' => 'approved']);

        $response = $this->getJson("/products/{$product->id}/questions");
        $this->assertCount(1, $response->json('data.data'));
    }

    public function test_guest_cannot_ask_question(): void
    {
        $product = Product::factory()->create();

        $this->postJson("/products/{$product->id}/questions", ['body' => 'سوال؟'])
            ->assertStatus(401);
    }
}
