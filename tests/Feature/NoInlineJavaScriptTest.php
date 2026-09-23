<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * باگ واقعی: چون CSP سایت (script-src 'self') هر onclick/onchange/onsubmit
 * نوشته‌شده مستقیم در HTML را بی‌صدا مسدود می‌کند (بدون خطا در Console)،
 * فیلتر مرتب‌سازی دسکتاپ و تایید حذف در کل پنل کار نمی‌کردند. این تست
 * جلوی برگشت این الگو را می‌گیرد.
 */
class NoInlineJavaScriptTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(): User
    {
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $permission = Permission::firstOrCreate(['slug' => 'products.manage'], ['name' => 'Manage Products', 'group' => 'Products']);
        $role->permissions()->attach($permission);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    public function test_products_page_sort_has_no_inline_onchange(): void
    {
        $this->withoutVite();
        Category::factory()->create();

        $response = $this->get('/products');

        $response->assertOk();
        $this->assertStringNotContainsString('onchange=', $response->getContent());
        $this->assertStringContainsString('data-sort-select', $response->getContent());
    }

    public function test_admin_products_page_has_no_inline_event_handlers(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser();
        Product::factory()->create();

        $response = $this->actingAs($staff)->get('/admin/products');

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringNotContainsString('onclick=', $content);
        $this->assertStringNotContainsString('onchange=', $content);
        $this->assertStringNotContainsString('onsubmit=', $content);
        $this->assertStringContainsString('data-confirm=', $content);
    }
}
