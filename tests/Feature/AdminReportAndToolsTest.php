<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Role;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportAndToolsTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(array $slugs): User
    {
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        foreach ($slugs as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'group' => 'Misc']);
            $role->permissions()->attach($permission);
        }
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    public function test_staff_can_view_reports_page(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser(['reports.view']);

        $this->actingAs($staff)->get('/admin/reports')->assertOk()->assertSee('فروش ۷ روز اخیر');
    }

    public function test_staff_can_view_activity_logs(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser(['settings.manage']);

        $this->actingAs($staff)->get('/admin/activity-logs')->assertOk();
    }

    public function test_staff_can_create_attribute_and_value(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser(['products.manage']);

        $this->actingAs($staff)->post('/admin/attributes', ['name' => 'رنگ'])->assertRedirect();
        $attribute = Attribute::where('name', 'رنگ')->firstOrFail();

        $this->actingAs($staff)
            ->post("/admin/attributes/{$attribute->id}/values", ['value' => 'قرمز'])
            ->assertRedirect();

        $this->assertDatabaseHas('attribute_values', ['attribute_id' => $attribute->id, 'value' => 'قرمز']);
    }

    public function test_duplicate_attribute_value_is_rejected_with_friendly_message(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser(['products.manage']);
        $attribute = Attribute::create(['name' => 'رنگ', 'slug' => 'color']);
        $attribute->values()->create(['value' => 'سفید', 'slug' => 'sfyd']);

        $response = $this->actingAs($staff)->post("/admin/attributes/{$attribute->id}/values", ['value' => 'سفید']);

        $response->assertSessionHasErrors('value');
        $this->assertDatabaseCount('attribute_values', 1);
    }

    public function test_staff_can_create_variant_for_product(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser(['products.manage']);
        $product = Product::factory()->create(['sku' => 'BASEPROD']);
        $attribute = Attribute::create(['name' => 'سایز', 'slug' => 'size']);
        $value = AttributeValue::create(['attribute_id' => $attribute->id, 'value' => 'L', 'slug' => 'l']);

        $response = $this->actingAs($staff)->post("/admin/products/{$product->id}/variants", [
            'status' => 'active',
            'values' => [$attribute->id => [$value->id]],
        ]);

        $response->assertRedirect(route('admin.products.variants.index', $product));
        $this->assertDatabaseHas('product_variants', ['product_id' => $product->id, 'sku' => 'BASEPROD-L']);
    }

    public function test_bulk_create_generates_all_combinations_with_initial_stock(): void
    {
        // ۲ رنگ × ۲ سایز باید دقیقاً ۴ Variant با موجودی اولیه یکسان بسازد
        $this->withoutVite();
        $staff = $this->staffUser(['products.manage']);
        $product = Product::factory()->create(['sku' => 'TSHIRT']);

        $colorAttr = Attribute::create(['name' => 'رنگ', 'slug' => 'color']);
        $black = AttributeValue::create(['attribute_id' => $colorAttr->id, 'value' => 'مشکی', 'slug' => 'black']);
        $white = AttributeValue::create(['attribute_id' => $colorAttr->id, 'value' => 'سفید', 'slug' => 'white']);

        $sizeAttr = Attribute::create(['name' => 'سایز', 'slug' => 'size']);
        $sizeM = AttributeValue::create(['attribute_id' => $sizeAttr->id, 'value' => 'M', 'slug' => 'm']);
        $sizeL = AttributeValue::create(['attribute_id' => $sizeAttr->id, 'value' => 'L', 'slug' => 'l']);

        $response = $this->actingAs($staff)->post("/admin/products/{$product->id}/variants", [
            'status' => 'active',
            'initial_stock' => 15,
            'values' => [
                $colorAttr->id => [$black->id, $white->id],
                $sizeAttr->id => [$sizeM->id, $sizeL->id],
            ],
        ]);

        $response->assertRedirect(route('admin.products.variants.index', $product));
        $this->assertDatabaseCount('product_variants', 4);
        $this->assertDatabaseHas('product_variants', ['sku' => 'TSHIRT-BLACK-M']);
        $this->assertDatabaseHas('product_variants', ['sku' => 'TSHIRT-WHITE-L']);

        $blackM = ProductVariant::where('sku', 'TSHIRT-BLACK-M')->firstOrFail();
        $this->assertEquals(15, app(\App\Services\InventoryService::class)->availableQuantity($product, $blackM));
    }

    public function test_bulk_create_skips_already_existing_combination(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser(['products.manage']);
        $product = Product::factory()->create(['sku' => 'HAT']);
        $attribute = Attribute::create(['name' => 'رنگ', 'slug' => 'color']);
        $red = AttributeValue::create(['attribute_id' => $attribute->id, 'value' => 'قرمز', 'slug' => 'red']);

        // اولین ساخت
        $this->actingAs($staff)->post("/admin/products/{$product->id}/variants", [
            'status' => 'active',
            'values' => [$attribute->id => [$red->id]],
        ]);
        $this->assertDatabaseCount('product_variants', 1);

        // تلاش دوم با همان ترکیب دقیق باید رد شود، نه خطا بدهد
        $this->actingAs($staff)->post("/admin/products/{$product->id}/variants", [
            'status' => 'active',
            'values' => [$attribute->id => [$red->id]],
        ]);
        $this->assertDatabaseCount('product_variants', 1);
    }

    public function test_can_reuse_sku_after_variant_is_soft_deleted(): void
    {
        // باگ واقعی: چون ProductVariant Soft Delete دارد، unique پیش‌فرض Laravel
        // رکورد حذف‌شده را هم می‌بیند و اشتباهاً SKU را «تکراری» رد می‌کرد.
        $this->withoutVite();
        $staff = $this->staffUser(['products.manage']);
        $product = Product::factory()->create(['sku' => 'REUSEPROD']);
        $attribute = Attribute::create(['name' => 'رنگ', 'slug' => 'color']);
        $value = AttributeValue::create(['attribute_id' => $attribute->id, 'value' => 'قرمز', 'slug' => 'red']);

        $this->actingAs($staff)->post("/admin/products/{$product->id}/variants", [
            'status' => 'active',
            'values' => [$attribute->id => [$value->id]],
        ]);
        $variant = ProductVariant::where('sku', 'REUSEPROD-RED')->firstOrFail();

        $this->actingAs($staff)->delete("/admin/products/{$product->id}/variants/{$variant->id}");

        $response = $this->actingAs($staff)->post("/admin/products/{$product->id}/variants", [
            'status' => 'active',
            'values' => [$attribute->id => [$value->id]],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('product_variants', ['sku' => 'REUSEPROD-RED', 'deleted_at' => null]);
    }

    public function test_staff_can_adjust_inventory(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser(['inventory.manage']);
        $product = Product::factory()->create();

        $this->actingAs($staff)->post("/admin/inventory/{$product->id}/adjust", [
            'type' => 'purchase',
            'quantity' => 20,
        ])->assertRedirect();

        $inventoryService = app(InventoryService::class);
        $this->assertEquals(20, $inventoryService->availableQuantity($product, null));
    }
}
