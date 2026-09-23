<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $permission = Permission::create(['slug' => 'categories.manage', 'name' => 'Manage Categories', 'group' => 'Categories']);
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    public function test_admin_can_create_category(): void
    {
        $response = $this->actingAs($this->adminUser())->postJson('/api/admin/categories', [
            'name' => 'موبایل',
            'status' => 'active',
        ]);

        $response->assertStatus(201)->assertJson(['success' => true]);
        $this->assertDatabaseHas('categories', ['name' => 'موبایل']);
    }

    public function test_regular_user_cannot_create_category(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/admin/categories', [
            'name' => 'لپ‌تاپ',
            'status' => 'active',
        ]);

        $response->assertStatus(403);
    }

    public function test_cannot_delete_category_with_children(): void
    {
        $parent = Category::factory()->create();
        Category::factory()->create(['parent_id' => $parent->id]);

        $response = $this->actingAs($this->adminUser())->deleteJson("/api/admin/categories/{$parent->id}");

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertDatabaseHas('categories', ['id' => $parent->id]);
    }

    public function test_cannot_delete_category_with_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($this->adminUser())->deleteJson("/api/admin/categories/{$category->id}");

        $response->assertStatus(422);
    }
}
