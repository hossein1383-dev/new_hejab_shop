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
}
