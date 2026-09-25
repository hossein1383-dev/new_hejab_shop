<?php

namespace Tests\Feature;

use App\Contracts\SmsGatewayContract;
use App\Models\Address;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\HeropostParcelService;
use App\Services\HeropostClient;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بخش ۵۵: پیامک تایید سفارش و پیامک ارسال مرسوله به خودِ مشتری — با اسم
 * واقعی اگر عوض کرده، وگرنه شماره تلفن.
 */
class CustomerSmsNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sms_display_name_returns_phone_when_name_is_still_default(): void
    {
        $user = User::factory()->create(['phone' => '09129999585', 'name' => 'کاربر 9585']);

        $this->assertEquals('09129999585', $user->smsDisplayName());
    }

    public function test_sms_display_name_returns_actual_name_when_changed(): void
    {
        $user = User::factory()->create(['phone' => '09129999585', 'name' => 'حسین رحمتیان']);

        $this->assertEquals('حسین رحمتیان', $user->smsDisplayName());
    }

    private function placeOrder(User $user): \App\Models\Order
    {
        $address = Address::factory()->create(['user_id' => $user->id, 'heropost_city_id' => 471]);
        $product = Product::factory()->create(['price' => 100000]);
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 5);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user, 'session-sms-' . uniqid());
        $cartService->addItem($cart, $product, null, 1);

        return app(CheckoutService::class)->createOrderFromCart($cart, $user, [], $address);
    }

    public function test_order_confirmation_sms_sent_with_phone_for_default_name_user(): void
    {
        $user = User::factory()->create(['phone' => '09129999585', 'name' => 'کاربر 9585']);

        $this->mock(SmsGatewayContract::class, function ($mock) {
            $mock->shouldReceive('sendOtp')->zeroOrMoreTimes();
            $mock->shouldReceive('sendNewOrderAdminAlert')->zeroOrMoreTimes();
            $mock->shouldReceive('sendOrderConfirmation')->once()->with('09129999585', '09129999585');
        });

        $order = $this->placeOrder($user);
        app(CheckoutService::class)->notifyOrderCreated($order);
    }

    public function test_order_confirmation_sms_sent_with_real_name_when_changed(): void
    {
        $user = User::factory()->create(['phone' => '09129999586', 'name' => 'مریم احمدی']);

        $this->mock(SmsGatewayContract::class, function ($mock) {
            $mock->shouldReceive('sendOtp')->zeroOrMoreTimes();
            $mock->shouldReceive('sendNewOrderAdminAlert')->zeroOrMoreTimes();
            $mock->shouldReceive('sendOrderConfirmation')->once()->with('09129999586', 'مریم احمدی');
        });

        $order = $this->placeOrder($user);
        app(CheckoutService::class)->notifyOrderCreated($order);
    }

    public function test_parcel_shipped_sms_sent_after_successful_registration(): void
    {
        $user = User::factory()->create(['phone' => '09129999587', 'name' => 'مریم احمدی']);
        $order = $this->placeOrder($user);
        $order->update(['status' => 'paid']);

        $this->mock(HeropostClient::class, function ($mock) {
            $mock->shouldReceive('createParcel')->once()->andReturn(['ParcelCode' => 'TRK-999']);
        });

        $this->mock(SmsGatewayContract::class, function ($mock) {
            $mock->shouldReceive('sendParcelShipped')->once()->with('09129999587', 'مریم احمدی', 'TRK-999');
        });

        app(HeropostParcelService::class)->createParcelForOrder($order);
    }
}
