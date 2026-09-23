<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private function moderatorUser(): User
    {
        $permission = Permission::create(['slug' => 'reviews.moderate', 'name' => 'Moderate Reviews', 'group' => 'Reviews']);
        $role = Role::create(['name' => 'Support', 'slug' => 'support-role']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    public function test_user_can_submit_review_and_it_starts_pending(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user)->postJson("/products/{$product->id}/reviews", [
            'rating' => 5,
            'comment' => 'محصول خیلی خوبی بود.',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('reviews', ['product_id' => $product->id, 'user_id' => $user->id, 'status' => 'pending']);
    }

    public function test_pending_review_is_not_visible_in_public_list(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)->postJson("/products/{$product->id}/reviews", ['rating' => 4]);

        $response = $this->getJson("/products/{$product->id}/reviews");

        $this->assertCount(0, $response->json('data.data'));
    }

    public function test_user_cannot_submit_two_reviews_for_same_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)->postJson("/products/{$product->id}/reviews", ['rating' => 5])->assertStatus(201);
        $response = $this->actingAs($user)->postJson("/products/{$product->id}/reviews", ['rating' => 3]);

        $response->assertStatus(422);
    }

    public function test_moderator_can_approve_review_and_it_becomes_visible(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $this->actingAs($user)->postJson("/products/{$product->id}/reviews", ['rating' => 5]);
        $review = $product->reviews()->first();

        $moderator = $this->moderatorUser();
        $this->actingAs($moderator)->postJson("/reviews/{$review->id}/moderate", ['status' => 'approved'])
            ->assertOk();

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'status' => 'approved']);
        $this->assertDatabaseHas('activity_logs', ['model_type' => \App\Models\Review::class, 'model_id' => $review->id]);

        $response = $this->getJson("/products/{$product->id}/reviews");
        $this->assertCount(1, $response->json('data.data'));
    }

    public function test_regular_user_cannot_moderate_review(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $this->actingAs($user)->postJson("/products/{$product->id}/reviews", ['rating' => 5]);
        $review = $product->reviews()->first();

        $other = User::factory()->create();
        $this->actingAs($other)->postJson("/reviews/{$review->id}/moderate", ['status' => 'approved'])
            ->assertStatus(403);
    }
}
