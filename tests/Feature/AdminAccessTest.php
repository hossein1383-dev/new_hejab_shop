<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_regular_customer_cannot_access_admin_dashboard(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get('/admin/dashboard')->assertStatus(403);
    }

    public function test_staff_role_can_access_admin_dashboard(): void
    {
        $this->withoutVite();

        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertOk()->assertSee('داشبورد');
    }
}
