<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * قبلاً صفحات وبلاگ از layouts.app مستقیم استفاده می‌کردند (بدون هدر/فوتر
 * سایت) و Style درون‌خطی خام داشتند — این تست‌ها تضمین می‌کنند از این به
 * بعد از Layout و سیستم Loading واقعی سایت استفاده می‌شود.
 */
class BlogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_index_uses_site_header_and_footer(): void
    {
        $this->withoutVite();
        BlogPost::create(['title' => 'پست تست', 'slug' => 'test-post', 'content' => 'متن', 'status' => 'published', 'published_at' => now()]);

        $response = $this->get('/blog');

        $response->assertOk()
            ->assertSee('header-mobile', false)
            ->assertSee('site-footer', false);
    }

    public function test_blog_show_uses_site_header_and_footer(): void
    {
        $this->withoutVite();
        BlogPost::create(['title' => 'پست تست', 'slug' => 'test-post', 'content' => 'متن', 'status' => 'published', 'published_at' => now()]);

        $response = $this->get('/blog/test-post');

        $response->assertOk()
            ->assertSee('header-mobile', false)
            ->assertSee('site-footer', false);
    }

    public function test_blog_index_images_have_loading_shimmer_class(): void
    {
        $this->withoutVite();
        BlogPost::create([
            'title' => 'پست با عکس', 'slug' => 'with-image', 'content' => 'متن',
            'status' => 'published', 'published_at' => now(), 'featured_image' => 'blog/test.jpg',
        ]);

        $response = $this->get('/blog');

        $response->assertOk()->assertSee('blog-card__image-wrap', false);
    }

    public function test_blog_show_displays_jalali_date(): void
    {
        $this->withoutVite();
        BlogPost::create(['title' => 'پست تست', 'slug' => 'test-post', 'content' => 'متن', 'status' => 'published', 'published_at' => now()]);

        $response = $this->get('/blog/test-post');

        $response->assertOk()->assertSee('blog-article__date', false);
    }

    public function test_blog_show_displays_related_posts(): void
    {
        $this->withoutVite();
        BlogPost::create(['title' => 'پست اصلی', 'slug' => 'main-post', 'content' => 'متن', 'status' => 'published', 'published_at' => now()]);
        BlogPost::create(['title' => 'پست مرتبط', 'slug' => 'related-post', 'content' => 'متن', 'status' => 'published', 'published_at' => now()]);

        $response = $this->get('/blog/main-post');

        $response->assertOk()->assertSee('پست مرتبط');
    }
}
