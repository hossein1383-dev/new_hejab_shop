<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_page_redirects_to_cart_when_empty(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/checkout');

        $response->assertRedirect(route('cart.show'));
    }

    public function test_checkout_page_renders_with_saved_address_when_cart_has_items(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        Address::factory()->create(['user_id' => $user->id, 'title' => 'خانه']);
        $product = Product::factory()->create(['name' => 'محصول چکاوت تست']);
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 10);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user, 'irrelevant');
        $cartService->addItem($cart, $product, null, 1);

        $response = $this->actingAs($user)->get('/checkout');

        $response->assertOk()
            ->assertSee('خانه')
            ->assertSee('محصول چکاوت تست');
    }

    public function test_checkout_page_redirects_guest_with_empty_cart_to_cart_page(): void
    {
        $this->withoutVite();

        $response = $this->get('/checkout');

        $response->assertRedirect(route('cart.show'));
    }

    public function test_checkout_page_redirects_guest_with_items_to_login(): void
    {
        $this->withoutVite();
        $product = Product::factory()->create();
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 10);

        $sessionId = 'guest-checkout-test-session';
        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart(null, $sessionId);
        $cartService->addItem($cart, $product, null, 1);

        // بخش ۵۰: به‌جای دست‌وپنجه نرم‌کردن با مکانیزم واقعی Session لاراول
        // (که در تست قابل‌اعتماد نبود)، مستقیم یک Session ساختگی به
        // Request می‌چسبانیم که فقط getId() را همان مقدار دلخواه برمی‌گرداند.
        $sessionMock = \Mockery::mock(\Illuminate\Contracts\Session\Session::class);
        $sessionMock->shouldReceive('getId')->andReturn($sessionId);

        $request = \Illuminate\Http\Request::create('/checkout', 'GET');
        $request->setLaravelSession($sessionMock);

        $response = app(\App\Http\Controllers\CheckoutController::class)->index($request);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertStringContainsString('/login', $response->getTargetUrl());
    }
}
