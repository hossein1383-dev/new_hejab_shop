<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_page_shows_empty_state_when_no_items(): void
    {
        $this->withoutVite();

        $response = $this->get('/cart');

        $response->assertOk()->assertSee('سبد خرید شما خالی است');
    }

    public function test_cart_page_shows_items_and_total(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'محصول سبد تست', 'price' => 120000]);
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 10);

        // برای کاربر لاگین‌شده، سبد بر اساس user_id پیدا می‌شود نه Session،
        // پس نیازی به تداوم Cookie بین دو Request جدا نیست.
        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user, 'irrelevant-session');
        $cartService->addItem($cart, $product, null, 2);

        $response = $this->actingAs($user)->get('/cart');

        $response->assertOk()
            ->assertSee('محصول سبد تست')
            ->assertSee(number_format(240000));
    }

    public function test_cart_page_shows_pending_orders_with_pay_button(): void
    {
        $this->withoutVite();
        [$order, $user] = $this->createPendingOrderForCartTest();

        $response = $this->actingAs($user)->get('/cart');

        $response->assertOk()
            ->assertSee('سفارش‌های در انتظار پرداخت')
            ->assertSee('data-retry-payment', false);
    }

    public function test_customer_can_retry_payment_for_pending_order(): void
    {
        [$order, $user] = $this->createPendingOrderForCartTest();

        $response = $this->actingAs($user)->postJson("/orders/{$order->id}/retry-payment");

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertArrayHasKey('redirect_url', $response->json('data'));
    }

    public function test_customer_cannot_retry_payment_for_another_users_order(): void
    {
        [$order] = $this->createPendingOrderForCartTest();
        $otherUser = \App\Models\User::factory()->create();

        $response = $this->actingAs($otherUser)->postJson("/orders/{$order->id}/retry-payment");

        $response->assertForbidden();
    }

    public function test_cannot_retry_payment_for_already_paid_order(): void
    {
        [$order, $user] = $this->createPendingOrderForCartTest();
        $order->update(['status' => 'paid']);

        $response = $this->actingAs($user)->postJson("/orders/{$order->id}/retry-payment");

        $response->assertStatus(422);
    }

    private function createPendingOrderForCartTest(): array
    {
        $user = \App\Models\User::factory()->create();
        $address = \App\Models\Address::factory()->create(['user_id' => $user->id]);
        $product = \App\Models\Product::factory()->create(['price' => 100000]);
        app(\App\Services\InventoryService::class)->recordMovement($product, null, 'purchase', 5);

        $cartService = app(\App\Services\CartService::class);
        $cart = $cartService->getOrCreateCart($user, 'session-pending-' . uniqid());
        $cartService->addItem($cart, $product, null, 1);
        $order = app(\App\Services\CheckoutService::class)->createOrderFromCart($cart, $user, [], $address);

        return [$order, $user];
    }
}
