<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminBannerTest extends TestCase
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

    public function test_staff_can_create_banner_and_it_appears_on_homepage(): void
    {
        $this->withoutVite();
        Storage::fake('public');
        $staff = $this->staffUser();

        $this->actingAs($staff)->post('/admin/banners', [
            'title' => 'بنر تست',
            'image' => UploadedFile::fake()->image('banner.jpg'),
            'position' => 'home_hero',
            'status' => 'active',
        ])->assertRedirect(route('admin.banners.index'));

        $this->assertDatabaseHas('banners', ['title' => 'بنر تست', 'status' => 'active']);

        $response = $this->get('/');
        $response->assertOk()->assertSee('hero--banner', false);
    }

    public function test_staff_can_create_banner_with_only_mobile_image(): void
    {
        $this->withoutVite();
        Storage::fake('public');
        $staff = $this->staffUser();

        $this->actingAs($staff)->post('/admin/banners', [
            'title' => 'بنر فقط موبایل',
            'image_mobile' => UploadedFile::fake()->image('only-mobile.jpg'),
            'position' => 'home_hero',
            'status' => 'active',
        ])->assertRedirect(route('admin.banners.index'));

        $banner = \App\Models\Banner::where('title', 'بنر فقط موبایل')->firstOrFail();
        $this->assertNull($banner->image);
        $this->assertNotNull($banner->image_mobile);
        $this->assertEquals($banner->image_mobile, $banner->desktopImage());
    }

    public function test_banner_creation_fails_when_neither_image_provided(): void
    {
        $this->withoutVite();
        Storage::fake('public');
        $staff = $this->staffUser();

        $response = $this->actingAs($staff)->post('/admin/banners', [
            'title' => 'بنر بدون تصویر',
            'position' => 'home_hero',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('image');
        $this->assertDatabaseMissing('banners', ['title' => 'بنر بدون تصویر']);
    }

    public function test_staff_can_upload_separate_mobile_image(): void
    {
        $this->withoutVite();
        Storage::fake('public');
        $staff = $this->staffUser();

        $this->actingAs($staff)->post('/admin/banners', [
            'title' => 'بنر با دو تصویر',
            'image' => UploadedFile::fake()->image('desktop.jpg'),
            'image_mobile' => UploadedFile::fake()->image('mobile.jpg'),
            'position' => 'home_hero',
            'status' => 'active',
        ])->assertRedirect(route('admin.banners.index'));

        $banner = \App\Models\Banner::where('title', 'بنر با دو تصویر')->firstOrFail();
        $this->assertNotNull($banner->image_mobile);
        $this->assertNotEquals($banner->image, $banner->image_mobile);
    }

    public function test_mobile_image_falls_back_to_desktop_image_when_not_uploaded(): void
    {
        $this->withoutVite();
        Storage::fake('public');
        $staff = $this->staffUser();

        $this->actingAs($staff)->post('/admin/banners', [
            'title' => 'بنر فقط دسکتاپ',
            'image' => UploadedFile::fake()->image('only-desktop.jpg'),
            'position' => 'home_hero',
            'status' => 'active',
        ]);

        $banner = \App\Models\Banner::where('title', 'بنر فقط دسکتاپ')->firstOrFail();
        $this->assertNull($banner->image_mobile);
        $this->assertEquals($banner->image, $banner->mobileImage());
    }

    public function test_homepage_renders_both_desktop_and_mobile_image_variables(): void
    {
        $this->withoutVite();
        Storage::fake('public');
        $staff = $this->staffUser();

        $this->actingAs($staff)->post('/admin/banners', [
            'title' => 'بنر تست دو حالته',
            'image' => UploadedFile::fake()->image('desktop.jpg'),
            'image_mobile' => UploadedFile::fake()->image('mobile.jpg'),
            'position' => 'home_hero',
            'status' => 'active',
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('--hero-bg-desktop:', false)
            ->assertSee('--hero-bg-mobile:', false);
    }
}
