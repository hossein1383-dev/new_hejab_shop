<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Product;
use App\Models\Review;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReviewPageTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(): User
    {
        $role = Role::create(['name' => 'Support', 'slug' => 'support']);
        $permission = Permission::firstOrCreate(['slug' => 'reviews.moderate'], ['name' => 'Moderate Reviews', 'group' => 'Reviews']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    public function test_staff_sees_pending_reviews_by_default(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser();
        $product = Product::factory()->create(['name' => 'محصول نظر تست']);
        $reviewer = User::factory()->create();

        Review::create(['product_id' => $product->id, 'user_id' => $reviewer->id, 'rating' => 4, 'status' => 'pending']);

        $this->actingAs($staff)->get('/admin/reviews')
            ->assertOk()->assertSee('محصول نظر تست');
    }

    public function test_staff_can_approve_review_from_panel(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser();
        $product = Product::factory()->create();
        $reviewer = User::factory()->create();
        $review = Review::create(['product_id' => $product->id, 'user_id' => $reviewer->id, 'rating' => 5, 'status' => 'pending']);

        $this->actingAs($staff)
            ->put("/admin/reviews/{$review->id}/moderate", ['status' => 'approved'])
            ->assertRedirect();

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'status' => 'approved']);
        $this->assertDatabaseHas('activity_logs', ['model_type' => Review::class, 'model_id' => $review->id]);
    }

    public function test_customer_cannot_access_review_moderation_page(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get('/admin/reviews')->assertStatus(403);
    }
}
