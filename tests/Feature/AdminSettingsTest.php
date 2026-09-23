<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_update_social_media_links(): void
    {
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);
        $permission = \App\Models\Permission::firstOrCreate(['slug' => 'settings.manage'], ['name' => 'Manage Settings', 'group' => 'Settings']);
        $role->permissions()->attach($permission);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->put('/admin/settings', [
            'instagram_url' => 'https://instagram.com/myshop',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('settings', ['key' => 'instagram_url', 'value' => 'https://instagram.com/myshop']);
    }

    public function test_super_admin_can_update_settings(): void
    {
        $this->withoutVite();
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);
        // در محیط واقعی، RolePermissionSeeder همه Permission ها را به Super Admin
        // می‌دهد؛ چون Seeder در تست اجرا نمی‌شود، اینجا صریحاً می‌دهیم.
        $permission = \App\Models\Permission::firstOrCreate(['slug' => 'settings.manage'], ['name' => 'Manage Settings', 'group' => 'Settings']);
        $role->permissions()->attach($permission);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->put('/admin/settings', [
            'site_name' => 'فروشگاه تست',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('settings', ['key' => 'site_name', 'value' => 'فروشگاه تست']);
    }

    public function test_admin_role_cannot_manage_settings(): void
    {
        // طبق تصمیم بخش ۴۶، نقش Admin همه‌چیز به‌جز settings.manage را دارد
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->actingAs($user)->get('/admin/settings')->assertStatus(403);
    }
}
