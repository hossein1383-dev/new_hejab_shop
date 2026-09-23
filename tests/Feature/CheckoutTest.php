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

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_checkout_with_saved_address(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['price' => 100000]);
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 10);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user, 'session-checkout-1');
        $cartService->addItem($cart, $product, null, 2);

        $order = app(CheckoutService::class)->createOrderFromCart($cart, $user, [], $address);

        $this->assertEquals('pending_payment', $order->status);
        $this->assertEquals(200000, $order->subtotal);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'user_id' => $user->id]);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'quantity' => 2, 'unit_price' => 100000]);

        // موجودی باید رزرو شده باشد، نه هنوز کسر قطعی
        $inventoryService = app(InventoryService::class);
        $this->assertEquals(8, $inventoryService->availableQuantity($product, null));

        // سبد باید خالی شده باشد
        $this->assertEquals(0, $cart->fresh('items')->itemsCount());
    }

    public function test_guest_cannot_checkout_even_with_inline_address(): void
    {
        // بخش ۵۰: خرید مهمان دیگر مجاز نیست — باید حتماً وارد شوند.
        $product = Product::factory()->create(['price' => 50000]);
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 5);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart(null, 'session-guest-checkout');
        $cartService->addItem($cart, $product, null, 1);

        $this->expectException(\DomainException::class);
        app(CheckoutService::class)->createOrderFromCart(
            $cart,
            null,
            ['name' => 'مهمان تست', 'email' => 'guest@example.com', 'phone' => '09120000000'],
            [
                'receiver_name' => 'مهمان تست',
                'phone' => '09120000000',
                'province' => 'تهران',
                'city' => 'تهران',
                'address_line' => 'خیابان تست',
            ]
        );
    }

    public function test_checkout_fails_when_cart_is_empty(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $cart = app(CartService::class)->getOrCreateCart($user, 'session-empty');

        $this->expectException(\DomainException::class);
        app(CheckoutService::class)->createOrderFromCart($cart, $user, [], $address);
    }

    public function test_checkout_fails_when_stock_insufficient_and_creates_no_order(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['price' => 100000]);
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 1);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user, 'session-insufficient');
        $cartService->addItem($cart, $product, null, 1); // Cap شده روی موجودی ۱

        // موجودی را بعد از افزودن به سبد، به صفر می‌رسانیم تا در لحظه Checkout ناکافی باشد
        app(InventoryService::class)->recordMovement($product, null, 'adjustment', -1);

        $this->expectException(\DomainException::class);

        try {
            app(CheckoutService::class)->createOrderFromCart($cart, $user, [], $address);
        } finally {
            $this->assertDatabaseCount('orders', 0);
        }
    }

    public function test_checkout_recalculates_price_from_current_product_price_not_stale_cart_price(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['price' => 100000]);
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 10);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user, 'session-price-change');
        $cartService->addItem($cart, $product, null, 1); // Snapshot قیمت در سبد: 100000

        // قیمت محصول بعد از افزودن به سبد تغییر می‌کند
        $product->update(['price' => 150000]);

        $order = app(CheckoutService::class)->createOrderFromCart($cart, $user, [], $address);

        // Order باید قیمت جدید (150000) را منعکس کند، نه قیمت قدیمی سبد (بخش ۱۲)
        $this->assertEquals(150000, $order->subtotal);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'unit_price' => 150000]);
    }
}
