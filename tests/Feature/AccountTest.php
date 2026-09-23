<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_account_page(): void
    {
        $this->get('/account')->assertRedirect('/login');
    }

    public function test_user_can_view_profile_page(): void
    {
        $this->withoutVite();
        $user = User::factory()->create(['name' => 'نام تست']);

        $this->actingAs($user)->get('/account')->assertOk()->assertSee('نام تست');
    }

    public function test_user_can_update_name_and_email(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/account/profile', [
            'name' => 'نام جدید',
            'email' => 'new@example.com',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'نام جدید', 'email' => 'new@example.com']);
    }

    public function test_user_cannot_use_email_already_taken_by_another_user(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/account/profile', [
            'name' => 'نام',
            'email' => 'taken@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_user_sees_own_orders_list(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['name' => 'محصول تست لیست سفارش', 'price' => 100000]);
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 10);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user, 'session-account-orders');
        $cartService->addItem($cart, $product, null, 1);
        $order = app(CheckoutService::class)->createOrderFromCart($cart, $user, [], $address);

        $response = $this->actingAs($user)->get('/account/orders');

        // بخش ۵۱: به‌جای شماره سفارش، نام محصولات نمایش داده می‌شود
        $response->assertOk()->assertSee('محصول تست لیست سفارش');
    }

    public function test_user_cannot_view_another_users_order(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $owner->id]);
        $product = Product::factory()->create();
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 10);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($owner, 'session-account-intruder');
        $cartService->addItem($cart, $product, null, 1);
        $order = app(CheckoutService::class)->createOrderFromCart($cart, $owner, [], $address);

        $this->actingAs($intruder)->get("/account/orders/{$order->id}")->assertStatus(403);
    }

    public function test_user_can_view_own_order_detail(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create();
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 10);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user, 'session-account-own-order');
        $cartService->addItem($cart, $product, null, 1);
        $order = app(CheckoutService::class)->createOrderFromCart($cart, $user, [], $address);

        $this->actingAs($user)->get("/account/orders/{$order->id}")
            ->assertOk()->assertSee($order->order_number);
    }

    public function test_user_sees_their_addresses_on_addresses_page(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        Address::factory()->create(['user_id' => $user->id, 'title' => 'محل کار']);

        $this->actingAs($user)->get('/account/addresses')
            ->assertOk()->assertSee('محل کار');
    }
}
