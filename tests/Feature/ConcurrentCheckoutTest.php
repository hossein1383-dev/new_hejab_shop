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

/**
 * شبیه‌سازی دو کاربر که هم‌زمان می‌خواهند آخرین واحد موجودی را بخرند
 * (بخش ۳۵: Concurrent Checkout, Overselling). PHPUnit واقعاً Multi-Thread
 * نیست، پس این تست به‌جای دو Thread واقعی، دنباله‌ای از عملیات را شبیه‌سازی
 * می‌کند که همان مسیر کد (lockForUpdate در InventoryService) را تمرین
 * می‌کند؛ محافظت واقعی در برابر Race Condition واقعی توسط دیتابیس (Row Lock)
 * تضمین می‌شود، نه توسط این تست — این تست فقط منطق «رد دومی» را تایید می‌کند.
 */
class ConcurrentCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_second_buyer_cannot_reserve_the_same_last_unit_of_stock(): void
    {
        $product = Product::factory()->create(['price' => 100000]);
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 1); // فقط ۱ عدد موجودی

        $buyer1 = User::factory()->create();
        $buyer2 = User::factory()->create();
        $address1 = Address::factory()->create(['user_id' => $buyer1->id]);
        $address2 = Address::factory()->create(['user_id' => $buyer2->id]);

        $cartService = app(CartService::class);
        $checkoutService = app(CheckoutService::class);

        // هر دو خریدار همان تک واحد موجود را به سبد خود اضافه می‌کنند
        $cart1 = $cartService->getOrCreateCart($buyer1, 'session-buyer-1');
        $cartService->addItem($cart1, $product, null, 1);

        $cart2 = $cartService->getOrCreateCart($buyer2, 'session-buyer-2');
        $cartService->addItem($cart2, $product, null, 1);

        // خریدار اول زودتر Checkout می‌کند — باید موفق شود و موجودی را رزرو کند
        $order1 = $checkoutService->createOrderFromCart($cart1, $buyer1, [], $address1);
        $this->assertEquals('pending_payment', $order1->status);

        // خریدار دوم بعد از او تلاش می‌کند — باید رد شود چون دیگر موجودی نیست
        $this->expectException(\DomainException::class);
        $checkoutService->createOrderFromCart($cart2, $buyer2, [], $address2);
    }

    public function test_only_one_order_is_created_when_stock_is_exhausted_by_first_buyer(): void
    {
        $product = Product::factory()->create(['price' => 100000]);
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 1);

        $buyer1 = User::factory()->create();
        $buyer2 = User::factory()->create();
        $address1 = Address::factory()->create(['user_id' => $buyer1->id]);
        $address2 = Address::factory()->create(['user_id' => $buyer2->id]);

        $cartService = app(CartService::class);
        $checkoutService = app(CheckoutService::class);

        $cart1 = $cartService->getOrCreateCart($buyer1, 'session-only-1');
        $cartService->addItem($cart1, $product, null, 1);
        $cart2 = $cartService->getOrCreateCart($buyer2, 'session-only-2');
        $cartService->addItem($cart2, $product, null, 1);

        $checkoutService->createOrderFromCart($cart1, $buyer1, [], $address1);

        try {
            $checkoutService->createOrderFromCart($cart2, $buyer2, [], $address2);
        } catch (\DomainException) {
            // انتظار می‌رود
        }

        $this->assertDatabaseCount('orders', 1);
        $this->assertEquals(0, app(InventoryService::class)->availableQuantity($product, null));
    }
}
