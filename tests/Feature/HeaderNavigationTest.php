<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بخش ۵۰: لینک وبلاگ و علاقه‌مندی‌ها در منوی موبایل، و دکمه خروج در هدر
 * (هم موبایل هم دسکتاپ) که قبلاً فقط در صفحه پروفایل بود.
 */
class HeaderNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_menu_includes_blog_link_for_guest(): void
    {
        $this->withoutVite();

        $response = $this->get('/');

        $response->assertOk()->assertSee(url('/blog'));
    }

    public function test_mobile_menu_includes_wishlist_link_for_authenticated_user(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertOk()->assertSee(url('/wishlist'));
    }

    public function test_mobile_menu_has_logout_button_for_authenticated_user(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertOk()->assertSee('data-logout-btn', false);
    }

    public function test_desktop_header_has_logout_button_for_authenticated_user(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertOk()->assertSee('data-logout-btn', false);
    }

    public function test_guest_does_not_see_logout_button(): void
    {
        $this->withoutVite();

        $response = $this->get('/');

        $response->assertOk()->assertDontSee('data-logout-btn', false);
    }

    public function test_header_shows_dynamic_site_name(): void
    {
        $this->withoutVite();
        app(\App\Services\SettingService::class)->setMany(['site_name' => 'حجاب مروارید']);

        $response = $this->get('/');

        $response->assertOk()->assertSee('حجاب مروارید');
    }
}
