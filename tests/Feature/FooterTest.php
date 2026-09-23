<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FooterTest extends TestCase
{
    use RefreshDatabase;

    public function test_footer_shows_site_name_and_copyright(): void
    {
        $this->withoutVite();

        $this->get('/')
            ->assertOk()
            ->assertSee('همه حقوق محفوظ است');
    }

    public function test_footer_shows_active_pages(): void
    {
        $this->withoutVite();
        Page::create(['title' => 'قوانین و مقررات', 'slug' => 'terms', 'content' => 'متن', 'status' => 'active']);
        Page::create(['title' => 'پیش‌نویس مخفی', 'slug' => 'draft-hidden', 'content' => 'متن', 'status' => 'inactive']);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('قوانین و مقررات')
            ->assertDontSee('پیش‌نویس مخفی');
    }

    public function test_footer_shows_root_categories(): void
    {
        $this->withoutVite();
        Category::factory()->create(['name' => 'دسته فوتر تست', 'status' => 'active', 'parent_id' => null]);

        $this->get('/')->assertOk()->assertSee('دسته فوتر تست');
    }

    public function test_footer_shows_social_links_when_configured(): void
    {
        $this->withoutVite();
        app(SettingService::class)->setMany(['instagram_url' => 'https://instagram.com/testshop']);

        $this->get('/')->assertOk()->assertSee('https://instagram.com/testshop', false);
    }

    public function test_footer_hides_social_section_when_not_configured(): void
    {
        $this->withoutVite();

        $response = $this->get('/');

        $response->assertOk()->assertDontSee('site-footer__social', false);
    }

    public function test_footer_shows_working_hours_when_configured(): void
    {
        $this->withoutVite();
        app(SettingService::class)->setMany(['working_days' => 'شنبه تا پنجشنبه', 'working_hours' => 'از ۹ تا ۱۸']);

        $this->get('/')->assertOk()->assertSee('شنبه تا پنجشنبه')->assertSee('از ۹ تا ۱۸');
    }

    public function test_footer_hides_working_hours_section_when_not_configured(): void
    {
        $this->withoutVite();

        $response = $this->get('/');

        $response->assertOk()->assertDontSee('site-footer__hours', false);
    }

    public function test_footer_shows_enamad_badge_when_configured(): void
    {
        $this->withoutVite();
        app(SettingService::class)->setMany(['enamad_id' => '123456', 'enamad_code' => 'TestCode']);

        $response = $this->get('/');

        $response->assertOk()->assertSee('trustseal.enamad.ir/?id=123456&Code=TestCode', false);
    }

    public function test_footer_hides_enamad_badge_when_not_configured(): void
    {
        $this->withoutVite();

        $response = $this->get('/');

        $response->assertOk()->assertDontSee('trustseal.enamad.ir', false);
    }

    public function test_footer_shows_eitaa_link_when_configured(): void
    {
        $this->withoutVite();
        app(SettingService::class)->setMany(['eitaa_url' => 'https://eitaa.com/testshop']);

        $this->get('/')->assertOk()->assertSee('https://eitaa.com/testshop', false);
    }
}
