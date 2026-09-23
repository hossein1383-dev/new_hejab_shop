<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserRoleTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(): User
    {
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        foreach (['users.manage', 'users.view'] as $slug) {
            $permission = Permission::firstOrCreate(['slug' => $slug], ['name' => $slug, 'group' => 'Users']);
            $role->permissions()->attach($permission);
        }
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    public function test_staff_can_assign_role_to_user(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser();
        $targetRole = Role::create(['name' => 'Customer', 'slug' => 'customer']);
        $targetUser = User::factory()->create();

        $this->actingAs($staff)->put("/admin/users/{$targetUser->id}", [
            'roles' => [$targetRole->id],
            'status' => 'active',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertTrue($targetUser->fresh()->roles->contains($targetRole->id));
    }

    public function test_super_admin_role_permissions_cannot_be_edited_from_panel(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser();
        $superAdminRole = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin']);

        $response = $this->actingAs($staff)->put("/admin/roles/{$superAdminRole->id}", ['permissions' => []]);

        $response->assertRedirect();
        $response->assertSessionHas('order_error');
    }

    public function test_staff_can_update_role_permissions(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser();
        $role = Role::create(['name' => 'Editor', 'slug' => 'editor']);
        $permission = Permission::firstOrCreate(['slug' => 'products.manage'], ['name' => 'Manage Products', 'group' => 'Products']);

        $this->actingAs($staff)->put("/admin/roles/{$role->id}", [
            'permissions' => [$permission->id],
        ])->assertRedirect(route('admin.roles.index'));

        $this->assertTrue($role->fresh()->permissions->contains($permission->id));
    }
}
