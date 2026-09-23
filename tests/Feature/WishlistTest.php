<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_add_and_remove_wishlist_item(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)->postJson("/wishlist/{$product->id}")->assertStatus(201);
        $this->assertDatabaseHas('wishlist_items', ['product_id' => $product->id]);

        $this->actingAs($user)->deleteJson("/wishlist/{$product->id}")->assertOk();
        $this->assertDatabaseMissing('wishlist_items', ['product_id' => $product->id]);
    }

    public function test_guest_cannot_access_wishlist(): void
    {
        $product = Product::factory()->create();

        $response = $this->postJson("/wishlist/{$product->id}");

        $response->assertStatus(401);
    }

    public function test_adding_same_product_twice_does_not_duplicate(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)->postJson("/wishlist/{$product->id}")->assertStatus(201);
        $this->actingAs($user)->postJson("/wishlist/{$product->id}")->assertStatus(201);

        $this->assertEquals(1, \App\Models\WishlistItem::where('product_id', $product->id)->count());
    }

    public function test_orphaned_wishlist_item_from_deleted_product_is_removed_automatically(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $product = Product::factory()->create();
        app(\App\Services\WishlistService::class)->add($user, $product);

        $product->delete(); // Soft Delete — شبیه‌سازی حذف محصول از پنل درحالی‌که در علاقه‌مندی کسی است

        $this->actingAs($user)->get('/wishlist')
            ->assertOk()
            ->assertSee('هنوز محصولی به علاقه‌مندی‌ها اضافه نکرده‌اید');

        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_wishlist_page_shows_added_products(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'محصول علاقه‌مندی تست']);
        app(\App\Services\WishlistService::class)->add($user, $product);

        $this->actingAs($user)->get('/wishlist')
            ->assertOk()->assertSee('محصول علاقه‌مندی تست');
    }

    public function test_wishlist_page_shows_empty_state_when_no_items(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();

        $this->actingAs($user)->get('/wishlist')
            ->assertOk()->assertSee('هنوز محصولی به علاقه‌مندی‌ها اضافه نکرده‌اید');
    }

    public function test_guest_is_redirected_to_login_from_wishlist_page(): void
    {
        $this->get('/wishlist')->assertRedirect('/login');
    }

    public function test_wishlisted_product_shows_filled_heart_on_listing_page(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $product = Product::factory()->create(['status' => 'active']);
        app(\App\Services\WishlistService::class)->add($user, $product);

        $response = $this->actingAs($user)->get('/products');

        $response->assertOk();
        // دکمه قلب این محصول باید کلاس is-active داشته باشد (HTML واقعی بین
        // کلاس‌ها ممکن است بیش از یک فاصله بگذارد، پس \s+ استفاده شده)
        $this->assertMatchesRegularExpression(
            '/product-card__wishlist-btn\s+is-active[^>]*data-product-id="' . $product->id . '"/',
            $response->getContent()
        );
    }

    public function test_non_wishlisted_product_does_not_show_filled_heart(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $product = Product::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->get('/products');

        $response->assertOk();
        $this->assertDoesNotMatchRegularExpression(
            '/product-card__wishlist-btn is-active[^>]*data-product-id="' . $product->id . '"/',
            $response->getContent()
        );
    }
}
