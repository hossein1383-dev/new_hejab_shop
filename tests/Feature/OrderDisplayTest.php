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
 * بخش ۵۱: به‌جای شماره سفارش، نام محصولات (حداکثر ۴ کلمه + ...)، و
 * به‌جای وضعیت انگلیسی/کل تاریخچه، فقط آخرین وضعیت به فارسی.
 */
class OrderDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function createRealOrder(string $productName, int $qty = 1): array
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['name' => $productName, 'price' => 100000]);
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 10);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user, 'session-order-display-' . uniqid());
        $cartService->addItem($cart, $product, null, $qty);
        $order = app(CheckoutService::class)->createOrderFromCart($cart, $user, [], $address);

        return [$order, $user];
    }

    public function test_product_names_summary_truncates_after_four_words(): void
    {
        [$order] = $this->createRealOrder('روسری ابریشمی طرح دار جدید فصل بهار');

        $summary = $order->fresh(['items'])->productNamesSummary();

        $this->assertEquals('روسری ابریشمی طرح دار...', $summary);
    }

    public function test_product_names_summary_shows_full_text_when_four_words_or_fewer(): void
    {
        [$order] = $this->createRealOrder('عبا مشکی ساده');

        $summary = $order->fresh(['items'])->productNamesSummary();

        $this->assertEquals('عبا مشکی ساده', $summary);
    }

    public function test_status_label_translates_to_persian(): void
    {
        [$order] = $this->createRealOrder('محصول تست وضعیت');

        $this->assertEquals('در انتظار پرداخت', $order->statusLabel());

        $order->status = 'shipped';
        $this->assertEquals('ارسال شده', $order->statusLabel());
    }

    public function test_order_list_page_shows_persian_status_not_raw_english(): void
    {
        $this->withoutVite();
        [$order, $user] = $this->createRealOrder('محصول تست وضعیت فارسی');
        $order->update(['status' => 'paid']);

        $response = $this->actingAs($user)->get('/account/orders');

        $response->assertOk()
            ->assertSee('پرداخت موفق')
            ->assertDontSee('>paid<', false);
    }

    public function test_order_detail_page_shows_only_latest_status_not_full_history(): void
    {
        $this->withoutVite();
        [$order, $user] = $this->createRealOrder('محصول تست تاریخچه');
        $order->update(['status' => 'shipped']);
        $order->statusHistories()->create(['from_status' => 'paid', 'to_status' => 'processing']);
        $order->statusHistories()->create(['from_status' => 'processing', 'to_status' => 'shipped']);

        $response = $this->actingAs($user)->get("/account/orders/{$order->id}");

        // فقط آخرین وضعیت (ارسال شده) دیده شود، نه وضعیت‌های میانی قبلی
        $response->assertOk()
            ->assertSee('ارسال شده')
            ->assertDontSee('در حال پردازش');
    }
}
