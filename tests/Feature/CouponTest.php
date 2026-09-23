<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    private function checkoutWithCoupon(Coupon $coupon, int $price = 100000, int $qty = 2, ?User $forUser = null)
    {
        $user = $forUser ?? User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['price' => $price]);
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 10);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user, 'session-coupon-' . uniqid());
        $cartService->addItem($cart, $product, null, $qty);

        return [
            app(CheckoutService::class)->createOrderFromCart($cart, $user, [], $address, 'standard', $coupon->code),
            $user,
            $product,
        ];
    }

    public function test_percentage_coupon_reduces_order_total(): void
    {
        $coupon = Coupon::factory()->create(['type' => 'percentage', 'value' => 10]);

        [$order] = $this->checkoutWithCoupon($coupon, price: 100000, qty: 2);

        $this->assertEquals(200000, $order->subtotal);
        $this->assertEquals(20000, $order->discount_total);
        // Total = Subtotal − Discount + Tax + Shipping = 200000 − 20000 + 0 + 50000 (بخش ۳۵)
        $this->assertEquals(230000, $order->total);
        $this->assertDatabaseHas('coupon_usages', ['coupon_id' => $coupon->id, 'order_id' => $order->id, 'discount_amount' => 20000]);
    }

    public function test_fixed_coupon_never_exceeds_subtotal(): void
    {
        $coupon = Coupon::factory()->create(['type' => 'fixed', 'value' => 500000]);

        [$order] = $this->checkoutWithCoupon($coupon, price: 50000, qty: 1);

        $this->assertEquals(50000, $order->discount_total); // نه ۵۰۰,۰۰۰
    }

    public function test_percentage_coupon_respects_max_discount_cap(): void
    {
        $coupon = Coupon::factory()->create(['type' => 'percentage', 'value' => 50, 'max_discount_amount' => 30000]);

        [$order] = $this->checkoutWithCoupon($coupon, price: 200000, qty: 1); // ۵۰٪ می‌شد ۱۰۰,۰۰۰

        $this->assertEquals(30000, $order->discount_total);
    }

    public function test_coupon_rejected_when_below_minimum_order(): void
    {
        $coupon = Coupon::factory()->create(['min_order_amount' => 500000]);

        $this->expectException(\DomainException::class);
        $this->checkoutWithCoupon($coupon, price: 10000, qty: 1);
    }

    public function test_coupon_still_valid_seconds_before_expiry(): void
    {
        $coupon = Coupon::factory()->create(['ends_at' => now()->addSeconds(30)]);

        [$order] = $this->checkoutWithCoupon($coupon, price: 100000, qty: 1);

        $this->assertGreaterThan(0, $order->discount_total);
    }

    public function test_expired_coupon_is_rejected(): void
    {
        $coupon = Coupon::factory()->create(['ends_at' => now()->subDay()]);

        $this->expectException(\DomainException::class);
        $this->checkoutWithCoupon($coupon);
    }

    public function test_coupon_usage_limit_is_enforced(): void
    {
        $coupon = Coupon::factory()->create(['usage_limit' => 1]);
        $this->checkoutWithCoupon($coupon);

        $this->expectException(\DomainException::class);
        $this->checkoutWithCoupon($coupon);
    }

    public function test_coupon_per_user_limit_is_enforced(): void
    {
        $coupon = Coupon::factory()->create(['per_user_limit' => 1]);
        $user = User::factory()->create();

        $this->checkoutWithCoupon($coupon, forUser: $user);

        $this->expectException(\DomainException::class);
        $this->checkoutWithCoupon($coupon, forUser: $user);
    }

    public function test_coupon_restricted_to_specific_category_only_discounts_matching_items(): void
    {
        $coupon = Coupon::factory()->create(['type' => 'percentage', 'value' => 10]);
        $eligibleCategory = \App\Models\Category::factory()->create();
        $coupon->categories()->attach($eligibleCategory->id);

        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);

        $eligibleProduct = Product::factory()->create(['price' => 100000, 'category_id' => $eligibleCategory->id]);
        $otherProduct = Product::factory()->create(['price' => 100000]);
        app(InventoryService::class)->recordMovement($eligibleProduct, null, 'purchase', 10);
        app(InventoryService::class)->recordMovement($otherProduct, null, 'purchase', 10);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user, 'session-cat-coupon');
        $cartService->addItem($cart, $eligibleProduct, null, 1);
        $cartService->addItem($cart, $otherProduct, null, 1);

        $order = app(CheckoutService::class)->createOrderFromCart($cart, $user, [], $address, 'standard', $coupon->code);

        // فقط ۱۰٪ از ۱۰۰,۰۰۰ (محصول واجد شرایط)، نه از کل ۲۰۰,۰۰۰
        $this->assertEquals(10000, $order->discount_total);
    }

    public function test_coupon_validate_endpoint_returns_discount_preview(): void
    {
        $user = User::factory()->create();
        $coupon = Coupon::factory()->create(['code' => 'SAVE10', 'type' => 'percentage', 'value' => 10]);
        $product = Product::factory()->create(['price' => 100000]);
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 10);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user, 'irrelevant');
        $cartService->addItem($cart, $product, null, 2);

        $response = $this->actingAs($user)->postJson('/coupons/validate', ['code' => 'SAVE10']);

        $response->assertOk()
            ->assertJsonPath('data.discount_amount', 20000)
            ->assertJsonPath('data.new_total', 180000);
    }

    public function test_coupon_validate_endpoint_rejects_invalid_code(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 10);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user, 'irrelevant');
        $cartService->addItem($cart, $product, null, 1);

        $response = $this->actingAs($user)->postJson('/coupons/validate', ['code' => 'NOT-REAL']);

        $response->assertStatus(422)->assertJson(['success' => false]);
    }
}
