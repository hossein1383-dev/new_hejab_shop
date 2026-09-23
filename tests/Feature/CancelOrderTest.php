<?php

namespace Tests\Feature;

use App\Contracts\PaymentGatewayContract;
use App\Models\Address;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\InventoryService;
use App\Services\PaymentService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بخش ۴۹: لغو سفارش + بازگشت خودکار وجه — چه پرداخت‌نشده باشد (فقط لغو)
 * چه پرداخت‌شده (لغو + بازگشت به کارت یا، اگر نشد، به کیف پول).
 */
class CancelOrderTest extends TestCase
{
    use RefreshDatabase;

    private function createPendingOrder(int $price = 100000, int $qty = 2, int $stock = 10)
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['price' => $price]);
        app(InventoryService::class)->recordMovement($product, null, 'purchase', $stock);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user, 'session-cancel-' . uniqid());
        $cartService->addItem($cart, $product, null, $qty);

        $order = app(CheckoutService::class)->createOrderFromCart($cart, $user, [], $address);

        return [$order, $product, $user];
    }

    public function test_cancelling_unpaid_order_releases_inventory_and_needs_no_refund(): void
    {
        [$order, $product] = $this->createPendingOrder(price: 100000, qty: 2, stock: 10);
        $inventoryService = app(InventoryService::class);

        $this->assertEquals(8, $inventoryService->availableQuantity($product, null)); // ۲ رزرو شده

        app(CheckoutService::class)->cancelOrder($order);

        $this->assertEquals('cancelled', $order->fresh()->status);
        $this->assertEquals(10, $inventoryService->availableQuantity($product, null)); // آزاد شد
    }

    public function test_cancelling_wallet_paid_order_refunds_to_wallet(): void
    {
        [$order, , $user] = $this->createPendingOrder(price: 100000, qty: 1, stock: 5);
        app(WalletService::class)->credit($user, $order->total);
        app(PaymentService::class)->payWithWallet($order, $user);

        app(CheckoutService::class)->cancelOrder($order->fresh());

        $this->assertEquals('refunded', $order->fresh()->status);
        $this->assertEquals($order->total, app(WalletService::class)->getOrCreate($user)->balance);
    }

    public function test_cancelling_gateway_paid_order_falls_back_to_wallet_when_card_refund_fails(): void
    {
        [$order, , $user] = $this->createPendingOrder(price: 100000, qty: 1, stock: 5);

        $this->mock(PaymentGatewayContract::class, function ($mock) use ($order) {
            $mock->shouldReceive('initiate')->andReturn(['redirect_url' => 'http://test', 'reference' => 'REF-1']);
            // مبلغ باید دقیقاً همان total واقعی سفارش باشد (شامل هزینه ارسال)
            // وگرنه PaymentService به‌خاطر «عدم تطابق مبلغ» رد می‌کند.
            $mock->shouldReceive('verify')->andReturn(['success' => true, 'reference' => 'REF-1', 'amount' => $order->total]);
            $mock->shouldReceive('refund')->once()->andReturn(false); // بازگشت به کارت ناموفق
        });

        $initResult = app(PaymentService::class)->initiate($order);
        app(PaymentService::class)->handleCallback(['reference' => $initResult['reference'], 'success' => '1', 'trackId' => $initResult['reference']]);

        app(CheckoutService::class)->cancelOrder($order->fresh());

        $this->assertEquals('refunded', $order->fresh()->status);
        $this->assertEquals($order->total, app(WalletService::class)->getOrCreate($user)->balance);
    }

    public function test_cannot_cancel_already_shipped_order(): void
    {
        [$order] = $this->createPendingOrder();
        $order->update(['status' => 'shipped']);

        $this->expectException(\DomainException::class);
        app(CheckoutService::class)->cancelOrder($order);
    }

    public function test_admin_can_cancel_order_through_panel(): void
    {
        [$order] = $this->createPendingOrder();
        $staff = $this->staffUserForOrders();

        $response = $this->actingAs($staff)->post(route('admin.orders.cancel', $order), ['note' => 'درخواست مشتری']);

        $response->assertRedirect();
        $this->assertEquals('cancelled', $order->fresh()->status);
    }

    public function test_admin_can_create_parcel_through_panel(): void
    {
        [$order] = $this->createPendingOrder();
        $order->update(['status' => 'paid']);
        \App\Models\Address::where('id', $order->address_id)->update(['heropost_city_id' => 42]);
        $order->update(['shipping_heropost_city_id' => 42]);
        $staff = $this->staffUserForOrders();

        $this->mock(\App\Services\HeropostClient::class, function ($mock) {
            $mock->shouldReceive('createParcel')->once()->andReturn(['ParcelCode' => 'TRK-1']);
        });

        $response = $this->actingAs($staff)->post(route('admin.orders.create-parcel', $order));

        $response->assertRedirect();
        $this->assertEquals('TRK-1', $order->fresh()->heropost_tracking_code);
    }

    private function staffUserForOrders(): User
    {
        $role = \App\Models\Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $permission = \App\Models\Permission::firstOrCreate(['slug' => 'orders.manage'], ['name' => 'Manage Orders', 'group' => 'Orders']);
        $role->permissions()->attach($permission);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
