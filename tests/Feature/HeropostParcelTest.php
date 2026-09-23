<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\HeropostClient;
use App\Services\HeropostParcelService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بخش ۵۲: ثبت و لغو مرسوله واقعی در هیروپست برای یک سفارش.
 */
class HeropostParcelTest extends TestCase
{
    use RefreshDatabase;

    private function createOrderWithHeropostCity(): array
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id, 'heropost_city_id' => 42]);
        $product = Product::factory()->create(['price' => 100000]);
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 5);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user, 'session-parcel-' . uniqid());
        $cartService->addItem($cart, $product, null, 1);
        $order = app(CheckoutService::class)->createOrderFromCart($cart, $user, [], $address);
        $order->update(['status' => 'paid']);

        return [$order, $user];
    }

    public function test_creates_parcel_and_saves_tracking_code(): void
    {
        [$order] = $this->createOrderWithHeropostCity();

        $this->mock(HeropostClient::class, function ($mock) {
            $mock->shouldReceive('createParcel')->once()->andReturn([
                'ParcelCode' => 'TRACK-ABC-999',
            ]);
        });

        $updatedOrder = app(HeropostParcelService::class)->createParcelForOrder($order);

        $this->assertEquals('TRACK-ABC-999', $updatedOrder->heropost_parcel_id);
        $this->assertEquals('TRACK-ABC-999', $updatedOrder->heropost_tracking_code);
    }

    public function test_cannot_create_parcel_twice_for_same_order(): void
    {
        [$order] = $this->createOrderWithHeropostCity();
        $order->update(['heropost_tracking_code' => 'ALREADY-SET']);

        $this->expectException(\DomainException::class);
        app(HeropostParcelService::class)->createParcelForOrder($order);
    }

    public function test_cannot_create_parcel_without_heropost_city_id(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id, 'heropost_city_id' => null]);
        $product = Product::factory()->create(['price' => 100000]);
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 5);
        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user, 'session-no-city-' . uniqid());
        $cartService->addItem($cart, $product, null, 1);
        $order = app(CheckoutService::class)->createOrderFromCart($cart, $user, [], $address);
        $order->update(['status' => 'paid']);

        $this->expectException(\DomainException::class);
        app(HeropostParcelService::class)->createParcelForOrder($order);
    }

    public function test_cancel_parcel_calls_client_when_parcel_exists(): void
    {
        [$order] = $this->createOrderWithHeropostCity();
        $order->update(['heropost_parcel_id' => 'P-555']);

        $this->mock(HeropostClient::class, function ($mock) {
            $mock->shouldReceive('cancelParcel')->once()->with('P-555')->andReturn(['success' => true]);
        });

        app(HeropostParcelService::class)->cancelParcelForOrder($order->fresh());
        // اگر Exception نداد یعنی موفق بود (Mockery خودش expectation را چک می‌کند)
        $this->assertTrue(true);
    }

    public function test_cancel_parcel_does_nothing_when_no_parcel_registered(): void
    {
        [$order] = $this->createOrderWithHeropostCity();

        $this->mock(HeropostClient::class, function ($mock) {
            $mock->shouldNotReceive('cancelParcel');
        });

        app(HeropostParcelService::class)->cancelParcelForOrder($order);
        $this->assertTrue(true);
    }

    public function test_cancel_parcel_does_not_throw_when_heropost_fails(): void
    {
        [$order] = $this->createOrderWithHeropostCity();
        $order->update(['heropost_parcel_id' => 'P-555']);

        $this->mock(HeropostClient::class, function ($mock) {
            $mock->shouldReceive('cancelParcel')->andThrow(new \RuntimeException('قطعی هیروپست'));
        });

        // نباید Exception پرتاب شود — فقط در Log ثبت می‌شود
        app(HeropostParcelService::class)->cancelParcelForOrder($order->fresh());
        $this->assertTrue(true);
    }

    public function test_cancelling_order_also_cancels_its_heropost_parcel(): void
    {
        [$order] = $this->createOrderWithHeropostCity();
        $order->update(['heropost_parcel_id' => 'P-777']);

        $this->mock(HeropostClient::class, function ($mock) {
            $mock->shouldReceive('cancelParcel')->once()->with('P-777')->andReturn(['success' => true]);
        });

        app(CheckoutService::class)->cancelOrder($order->fresh());

        $this->assertEquals('refunded', $order->fresh()->status);
    }
}
