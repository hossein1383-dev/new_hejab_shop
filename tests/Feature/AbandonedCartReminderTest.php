<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AbandonedCartReminderTest extends TestCase
{
    use RefreshDatabase;

    private function makeCartWithItem(?User $user, int $hoursOld): \App\Models\Cart
    {
        $product = Product::factory()->create();
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 10);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user, 'session-' . uniqid());
        $cartService->addItem($cart, $product, null, 1);

        DB::table('carts')->where('id', $cart->id)->update(['updated_at' => now()->subHours($hoursOld)]);

        return $cart->fresh();
    }

    public function test_sends_reminder_for_eligible_abandoned_cart(): void
    {
        $user = User::factory()->create();
        $cart = $this->makeCartWithItem($user, 30);

        $this->artisan('carts:send-abandoned-reminders')->assertSuccessful();

        $this->assertNotNull($cart->fresh()->abandoned_reminder_sent_at);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $user->id]);
    }

    public function test_skips_guest_carts(): void
    {
        $cart = $this->makeCartWithItem(null, 30);

        $this->artisan('carts:send-abandoned-reminders');

        $this->assertNull($cart->fresh()->abandoned_reminder_sent_at);
    }

    public function test_skips_recently_updated_carts(): void
    {
        $user = User::factory()->create();
        $cart = $this->makeCartWithItem($user, 2); // فقط ۲ ساعت پیش

        $this->artisan('carts:send-abandoned-reminders');

        $this->assertNull($cart->fresh()->abandoned_reminder_sent_at);
    }

    public function test_does_not_send_duplicate_reminder(): void
    {
        $user = User::factory()->create();
        $cart = $this->makeCartWithItem($user, 30);
        $cart->update(['abandoned_reminder_sent_at' => now()->subDay()]);

        $this->artisan('carts:send-abandoned-reminders');

        $this->assertDatabaseCount('notifications', 0);
    }
}
