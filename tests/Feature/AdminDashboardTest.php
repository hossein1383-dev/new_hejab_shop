<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(): User
    {
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $permission = Permission::firstOrCreate(['slug' => 'reports.view'], ['name' => 'View Reports', 'group' => 'Reports']);
        $role->permissions()->attach($permission);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    public function test_low_stock_count_excludes_soft_deleted_products(): void
    {
        // باگ واقعی: Inventory::where(...) به‌تنهایی به Soft Delete محصول
        // کاری نداشت، پس موجودی محصولات حذف‌شده را هم می‌شمرد.
        $this->withoutVite();
        $staff = $this->staffUser();
        $inventoryService = app(InventoryService::class);

        $activeLowStock = Product::factory()->create();
        $inventoryService->recordMovement($activeLowStock, null, 'purchase', 3);

        $deletedLowStock = Product::factory()->create();
        $inventoryService->recordMovement($deletedLowStock, null, 'purchase', 2);
        $deletedLowStock->delete(); // Soft Delete — موجودی‌اش در جدول inventories باقی می‌ماند

        $response = $this->actingAs($staff)->get('/admin/dashboard');

        $response->assertOk();
        $viewData = $response->viewData('stats');
        $this->assertEquals(1, $viewData['low_stock_count']);
    }

    public function test_low_stock_count_excludes_soft_deleted_variant(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser();
        $inventoryService = app(InventoryService::class);

        $product = Product::factory()->create();
        $variant = $product->variants()->create(['sku' => 'DASH-TEST-VARIANT', 'status' => 'active']);
        $inventoryService->recordMovement($product, $variant, 'purchase', 2);
        $variant->update(['sku' => $variant->sku . '-deleted-' . $variant->id]);
        $variant->delete();

        $response = $this->actingAs($staff)->get('/admin/dashboard');

        $this->assertEquals(0, $response->viewData('stats')['low_stock_count']);
    }
}
