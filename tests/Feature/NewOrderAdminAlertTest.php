<?php

namespace Tests\Feature;

use App\Contracts\SmsGatewayContract;
use App\Models\Address;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بخش ۵۴: پیامک هشدار به سوپر ادمین هنگام ثبت سفارش جدید.
 */
class NewOrderAdminAlertTest extends TestCase
{
    use RefreshDatabase;

    private function placeOrder(): \App\Models\Order
    {
        $user = User::factory()->create(['phone' => '09120000001']);
        $address = Address::factory()->create(['user_id' => $user->id, 'phone' => '09120000001']);
        $product = Product::factory()->create(['price' => 100000]);
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 5);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user, 'session-alert-' . uniqid());
        $cartService->addItem($cart, $product, null, 1);

        return app(CheckoutService::class)->createOrderFromCart($cart, $user, [], $address);
    }

    public function test_super_admin_with_phone_receives_new_order_alert(): void
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $superAdmin = User::factory()->create(['name' => 'مدیر اصلی', 'phone' => '09350000000']);
        $superAdmin->roles()->attach($role);

        $this->mock(SmsGatewayContract::class, function ($mock) {
            $mock->shouldReceive('sendOtp')->zeroOrMoreTimes();
            $mock->shouldReceive('sendNewOrderAdminAlert')
                ->once()
                ->with('09350000000', 'مدیر اصلی', '09120000001');
        });

        $order = $this->placeOrder();
        app(CheckoutService::class)->notifyOrderCreated($order);
    }

    public function test_regular_staff_without_super_admin_role_does_not_receive_alert(): void
    {
        $role = Role::create(['name' => 'Manager', 'slug' => 'manager']);
        $manager = User::factory()->create(['phone' => '09350000001']);
        $manager->roles()->attach($role);

        $this->mock(SmsGatewayContract::class, function ($mock) {
            $mock->shouldReceive('sendOtp')->zeroOrMoreTimes();
            $mock->shouldNotReceive('sendNewOrderAdminAlert');
        });

        $order = $this->placeOrder();
        app(CheckoutService::class)->notifyOrderCreated($order);
    }

    public function test_super_admin_without_phone_is_skipped(): void
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $superAdmin = User::factory()->create(['phone' => null]);
        $superAdmin->roles()->attach($role);

        $this->mock(SmsGatewayContract::class, function ($mock) {
            $mock->shouldReceive('sendOtp')->zeroOrMoreTimes();
            $mock->shouldNotReceive('sendNewOrderAdminAlert');
        });

        $order = $this->placeOrder();
        app(CheckoutService::class)->notifyOrderCreated($order);
    }

    public function test_sms_failure_does_not_break_order_notification_flow(): void
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $superAdmin = User::factory()->create(['phone' => '09350000002']);
        $superAdmin->roles()->attach($role);

        $this->mock(SmsGatewayContract::class, function ($mock) {
            $mock->shouldReceive('sendOtp')->zeroOrMoreTimes();
            $mock->shouldReceive('sendNewOrderAdminAlert')->andThrow(new \RuntimeException('قطعی سرویس پیامک'));
        });

        $order = $this->placeOrder();

        // نباید Exception پرتاب شود
        app(CheckoutService::class)->notifyOrderCreated($order);
        $this->assertTrue(true);
    }
}
