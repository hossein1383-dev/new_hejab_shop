<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductPageTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(): User
    {
        $role = Role::create(['name' => 'Editor', 'slug' => 'editor']);
        $permission = Permission::firstOrCreate(['slug' => 'products.manage'], ['name' => 'Manage Products', 'group' => 'Products']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    public function test_staff_can_view_product_list(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser();
        $product = Product::factory()->create(['name' => 'محصول تست پنل']);

        $this->actingAs($staff)->get('/admin/products')
            ->assertOk()->assertSee('محصول تست پنل');
    }

    public function test_staff_can_create_product_through_page_form(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser();
        $category = Category::factory()->create();

        $response = $this->actingAs($staff)->post('/admin/products', [
            'name' => 'محصول جدید از پنل',
            'sku' => 'PANEL-SKU-001',
            'category_id' => $category->id,
            'price' => 150000,
            'status' => 'active',
            'tags_text' => 'تست, پنل',
        ]);

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('products', ['name' => 'محصول جدید از پنل', 'sku' => 'PANEL-SKU-001']);

        $product = Product::where('sku', 'PANEL-SKU-001')->first();
        $this->assertEquals(2, $product->tags()->count());
    }

    public function test_staff_can_set_product_weight_for_shipping_calculation(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser();
        $category = Category::factory()->create();

        $this->actingAs($staff)->post('/admin/products', [
            'name' => 'محصول با وزن',
            'sku' => 'WEIGHT-SKU-001',
            'category_id' => $category->id,
            'price' => 150000,
            'status' => 'active',
            'weight' => 0.6,
            'dimensions' => '30x20x10',
        ]);

        $this->assertDatabaseHas('products', ['sku' => 'WEIGHT-SKU-001', 'weight' => 0.6, 'dimensions' => '30x20x10']);
    }

    public function test_product_validation_errors_are_shown(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser();

        $response = $this->actingAs($staff)->post('/admin/products', [
            'name' => '',
            'sku' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'sku', 'category_id', 'price', 'status']);
    }

    public function test_can_reuse_sku_after_product_is_soft_deleted(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser();
        $category = Category::factory()->create();

        $this->actingAs($staff)->post('/admin/products', [
            'name' => 'محصول اول',
            'sku' => 'REUSE-PRODUCT-SKU',
            'category_id' => $category->id,
            'price' => 100000,
            'status' => 'active',
        ]);
        $product = Product::where('sku', 'REUSE-PRODUCT-SKU')->firstOrFail();
        $this->actingAs($staff)->delete("/admin/products/{$product->id}");

        $response = $this->actingAs($staff)->post('/admin/products', [
            'name' => 'محصول دوم',
            'sku' => 'REUSE-PRODUCT-SKU',
            'category_id' => $category->id,
            'price' => 150000,
            'status' => 'active',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', ['sku' => 'REUSE-PRODUCT-SKU', 'name' => 'محصول دوم', 'deleted_at' => null]);
    }
    public function test_editor_without_permission_cannot_delete_product(): void
    {
        // نقش 'editor' برای عبور از دروازه ورودی پنل کافی است، ولی بدون
        // Permission واقعی 'products.manage'، نباید بتواند محصول حذف کند.
        $role = Role::create(['name' => 'Editor', 'slug' => 'editor']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $product = Product::factory()->create();

        $this->actingAs($user)->delete("/admin/products/{$product->id}")->assertStatus(403);
    }
}
