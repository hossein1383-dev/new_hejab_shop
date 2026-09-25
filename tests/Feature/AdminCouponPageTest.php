<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCouponPageTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(): User
    {
        $role = Role::create(['name' => 'Manager', 'slug' => 'manager']);
        $permission = Permission::firstOrCreate(['slug' => 'coupons.manage'], ['name' => 'Manage Coupons', 'group' => 'Discounts']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    public function test_staff_can_list_coupons(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser();
        Coupon::factory()->create(['code' => 'LISTCODE']);

        $this->actingAs($staff)->get('/admin/coupons')
            ->assertOk()->assertSee('LISTCODE');
    }

    public function test_staff_can_create_coupon(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser();

        $response = $this->actingAs($staff)->post('/admin/coupons', [
            'code' => 'newcode10',
            'type' => 'percentage',
            'value' => 10,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.coupons.index'));
        $this->assertDatabaseHas('coupons', ['code' => 'NEWCODE10']);
    }

    public function test_duplicate_coupon_code_is_rejected(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser();
        Coupon::factory()->create(['code' => 'DUPLICATE']);

        $response = $this->actingAs($staff)->post('/admin/coupons', [
            'code' => 'DUPLICATE',
            'type' => 'percentage',
            'value' => 10,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_customer_cannot_manage_coupons(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get('/admin/coupons')->assertStatus(403);
    }

    public function test_admin_can_send_gift_sms_to_all_customers_with_phone(): void
    {
        $staff = $this->staffUser();
        $coupon = Coupon::factory()->create(['code' => 'GIFT20']);
        $withPhone1 = User::factory()->create(['phone' => '09120000001', 'name' => 'کاربر 0001']);
        $withPhone2 = User::factory()->create(['phone' => '09120000002', 'name' => 'زهرا کریمی']);
        $withoutPhone = User::factory()->create(['phone' => null]);

        $this->mock(\App\Contracts\SmsGatewayContract::class, function ($mock) {
            $mock->shouldReceive('sendCouponGift')->twice();
        });

        $response = $this->actingAs($staff)->post(route('admin.coupons.send-gift-sms', $coupon));

        $response->assertRedirect();
    }

    public function test_sms_failure_for_one_customer_does_not_stop_sending_to_others(): void
    {
        $staff = $this->staffUser();
        $coupon = Coupon::factory()->create(['code' => 'GIFT30']);
        User::factory()->create(['phone' => '09120000003']);
        User::factory()->create(['phone' => '09120000004']);

        $this->mock(\App\Contracts\SmsGatewayContract::class, function ($mock) {
            $mock->shouldReceive('sendCouponGift')
                ->twice()
                ->andReturnUsing(function () {
                    static $calls = 0;
                    $calls++;
                    if ($calls === 1) {
                        throw new \RuntimeException('قطعی سرویس');
                    }
                });
        });

        $response = $this->actingAs($staff)->post(route('admin.coupons.send-gift-sms', $coupon));

        $response->assertRedirect();
    }
}
