<?php

namespace Tests\Feature;

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

class AdminOrderTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(array $permissionSlugs): User
    {
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);

        foreach ($permissionSlugs as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'group' => 'Orders']);
            $role->permissions()->attach($permission);
        }

        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    private function createPaidLikeOrder(string $status = 'paid')
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['price' => 100000]);
        app(InventoryService::class)->recordMovement($product, null, 'purchase', 10);

        $cartService = app(CartService::class);
        $cart = $cartService->getOrCreateCart($user, 'session-admin-order-' . uniqid());
        $cartService->addItem($cart, $product, null, 1);

        $order = app(CheckoutService::class)->createOrderFromCart($cart, $user, [], $address);
        $order->update(['status' => $status]);

        return $order;
    }

    public function test_staff_with_view_permission_can_list_and_view_orders(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser(['orders.view']);
        $order = $this->createPaidLikeOrder();

        $this->actingAs($staff)->get('/admin/orders')
            ->assertOk()->assertSee($order->order_number);

        $this->actingAs($staff)->get("/admin/orders/{$order->id}")
            ->assertOk()->assertSee($order->order_number);
    }

    public function test_staff_without_manage_permission_cannot_update_status(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser(['orders.view']);
        $order = $this->createPaidLikeOrder();

        $this->actingAs($staff)
            ->put("/admin/orders/{$order->id}/status", ['status' => 'processing'])
            ->assertStatus(403);
    }

    public function test_staff_with_manage_permission_can_update_status_through_valid_transition(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser(['orders.manage']);
        $order = $this->createPaidLikeOrder(status: 'paid');

        $this->actingAs($staff)
            ->put("/admin/orders/{$order->id}/status", ['status' => 'processing'])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'processing']);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id, 'from_status' => 'paid', 'to_status' => 'processing',
        ]);
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser(['orders.manage']);
        $order = $this->createPaidLikeOrder(status: 'paid');

        // پرش مستقیم از paid به delivered مجاز نیست (باید از processing/preparing/shipped رد شود)
        $this->actingAs($staff)
            ->put("/admin/orders/{$order->id}/status", ['status' => 'delivered'])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);
    }

    public function test_orders_list_can_be_filtered_by_status(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser(['orders.view']);
        $paidOrder = $this->createPaidLikeOrder('paid');
        $cancelledOrder = $this->createPaidLikeOrder('cancelled');

        $response = $this->actingAs($staff)->get('/admin/orders?status=paid');

        $response->assertOk()
            ->assertSee($paidOrder->order_number)
            ->assertDontSee($cancelledOrder->order_number);
    }
}
