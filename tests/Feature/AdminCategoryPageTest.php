<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryPageTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(): User
    {
        $role = Role::create(['name' => 'Editor', 'slug' => 'editor']);
        $permission = Permission::firstOrCreate(['slug' => 'categories.manage'], ['name' => 'Manage Categories', 'group' => 'Categories']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    public function test_staff_can_view_category_list(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser();
        $category = Category::factory()->create(['name' => 'دسته تست پنل']);

        $this->actingAs($staff)->get('/admin/categories')
            ->assertOk()->assertSee('دسته تست پنل');
    }

    public function test_staff_can_create_category_through_page_form(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser();

        $response = $this->actingAs($staff)->post('/admin/categories', [
            'name' => 'دسته جدید از پنل',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', ['name' => 'دسته جدید از پنل']);
    }

    public function test_staff_can_update_category(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser();
        $category = Category::factory()->create(['name' => 'نام قدیمی']);

        $this->actingAs($staff)->put("/admin/categories/{$category->id}", [
            'name' => 'نام جدید',
            'status' => 'active',
        ])->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'نام جدید']);
    }

    public function test_customer_cannot_access_category_admin_pages(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get('/admin/categories')->assertStatus(403);
    }
}
