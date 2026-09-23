<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPageTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(): User
    {
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $permission = Permission::firstOrCreate(['slug' => 'settings.manage'], ['name' => 'Manage Settings', 'group' => 'Settings']);
        $role->permissions()->attach($permission);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    public function test_staff_can_create_static_page(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser();

        // Slug صریح ارسال می‌شود تا تست به رفتار Transliteration خودکار
        // Str::slug() روی متن فارسی (که قابل اعتماد نیست) وابسته نباشد.
        $this->actingAs($staff)->post('/admin/pages', [
            'title' => 'درباره ما',
            'slug' => 'about-us',
            'content' => 'متن درباره ما.',
            'status' => 'active',
        ])->assertRedirect(route('admin.pages.index'));

        $this->assertDatabaseHas('pages', ['title' => 'درباره ما', 'slug' => 'about-us']);
    }

    public function test_public_can_view_active_page(): void
    {
        $this->withoutVite();
        Page::create(['title' => 'حریم خصوصی', 'slug' => 'privacy', 'content' => 'متن حریم خصوصی.', 'status' => 'active']);

        $response = $this->get('/page/privacy');

        $response->assertOk()->assertSee('حریم خصوصی')->assertSee('متن حریم خصوصی.');
    }

    public function test_inactive_page_returns_404(): void
    {
        $this->withoutVite();
        Page::create(['title' => 'پیش‌نویس', 'slug' => 'draft-page', 'content' => 'x', 'status' => 'inactive']);

        $this->get('/page/draft-page')->assertStatus(404);
    }
}
