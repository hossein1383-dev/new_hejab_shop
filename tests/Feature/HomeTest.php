<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // بخش ۳۲/۳۳: صفحه اصلی حالا محصولات را Cache می‌کند؛ چون Cache بین
        // تست‌ها (برخلاف دیتابیس) خودکار Reset نمی‌شود، هر تست باید مستقل شروع کند.
        Cache::forget('home.products');
        Cache::forget('home.category_rows');
    }

    public function test_home_page_shows_six_products_per_category_with_see_more_link(): void
    {
        $this->withoutVite();

        $category = Category::factory()->create(['name' => 'دسته تست ردیف', 'status' => 'active', 'parent_id' => null]);
        $products = Product::factory()->count(8)->create(['category_id' => $category->id, 'status' => 'active']);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('دسته تست ردیف')
            ->assertSee('مشاهده بیشتر');

        // فقط باید ۶ تا از این ۸ محصول در صفحه اصلی نمایش داده شوند
        $shownCount = $products->filter(fn ($product) => str_contains($response->getContent(), 'data-product-id="' . $product->id . '"'))->count();
        $this->assertEquals(6, $shownCount);
    }

    public function test_home_page_renders_successfully(): void
    {
        $this->withoutVite();

        $response = $this->get('/');

        $response->assertOk();
    }

    public function test_home_page_shows_active_categories(): void
    {
        $this->withoutVite();

        $category = Category::factory()->create(['name' => 'موبایل و تبلت', 'status' => 'active']);
        Category::factory()->create(['name' => 'دسته غیرفعال', 'status' => 'inactive']);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('موبایل و تبلت')
            ->assertDontSee('دسته غیرفعال');
    }

    public function test_home_page_shows_featured_products(): void
    {
        $this->withoutVite();

        Product::factory()->create([
            'name' => 'گوشی تست ویژه',
            'status' => 'active',
            'is_featured' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk()->assertSee('گوشی تست ویژه');
    }

    public function test_home_page_hides_inactive_products(): void
    {
        $this->withoutVite();

        Product::factory()->create([
            'name' => 'محصول غیرفعال تست',
            'status' => 'draft',
            'is_featured' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk()->assertDontSee('محصول غیرفعال تست');
    }

    public function test_home_page_shows_latest_published_blog_posts(): void
    {
        $this->withoutVite();
        \App\Models\BlogPost::create([
            'title' => 'راهنمای انتخاب حجاب مناسب',
            'slug' => 'hijab-guide-test',
            'excerpt' => 'یک راهنمای کوتاه',
            'content' => 'متن کامل',
            'status' => 'published',
            'published_at' => now(),
        ]);
        \App\Models\BlogPost::create([
            'title' => 'پیش‌نویس منتشرنشده',
            'slug' => 'draft-test',
            'content' => 'متن',
            'status' => 'draft',
        ]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('راهنمای انتخاب حجاب مناسب')
            ->assertDontSee('پیش‌نویس منتشرنشده');
    }

    public function test_home_page_hides_blog_banner_when_no_published_posts(): void
    {
        $this->withoutVite();

        $response = $this->get('/');

        $response->assertOk()->assertDontSee('blog-carousel', false);
    }

    public function test_home_page_shows_top_rated_products_section(): void
    {
        $this->withoutVite();
        $product = Product::factory()->create(['name' => 'محصول پراستقبال تست', 'status' => 'active']);
        $user = \App\Models\User::factory()->create();
        $product->reviews()->create(['user_id' => $user->id, 'rating' => 5, 'status' => 'approved', 'comment' => 'عالی']);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('بهترین کالاها از نگاه مشتری‌ها')
            ->assertSee('محصول پراستقبال تست');
    }

    public function test_bestseller_section_shows_rank_badges(): void
    {
        $this->withoutVite();
        Product::factory()->create(['is_bestseller' => true, 'status' => 'active']);

        $response = $this->get('/');

        $response->assertOk()->assertSee('product-card__rank-badge', false);
    }

    public function test_new_products_section_shows_new_badge(): void
    {
        $this->withoutVite();
        Product::factory()->create(['is_new' => true, 'status' => 'active', 'price' => 100000, 'compare_price' => null]);

        $response = $this->get('/');

        $response->assertOk()->assertSee('جدید');
    }

    public function test_blog_carousel_shows_nav_buttons_when_multiple_posts(): void
    {
        $this->withoutVite();
        \App\Models\BlogPost::create(['title' => 'پست اول', 'slug' => 'post-1', 'content' => 'متن', 'status' => 'published', 'published_at' => now()]);
        \App\Models\BlogPost::create(['title' => 'پست دوم', 'slug' => 'post-2', 'content' => 'متن', 'status' => 'published', 'published_at' => now()]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('data-carousel-prev', false)
            ->assertSee('data-carousel-next', false);
    }

    public function test_blog_carousel_hides_nav_buttons_with_single_post(): void
    {
        $this->withoutVite();
        \App\Models\BlogPost::create(['title' => 'تنها پست', 'slug' => 'only-post', 'content' => 'متن', 'status' => 'published', 'published_at' => now()]);

        $response = $this->get('/');

        $response->assertOk()
            ->assertDontSee('data-carousel-prev', false)
            ->assertDontSee('data-carousel-next', false);
    }

    public function test_home_blog_carousel_images_have_loading_shimmer_class(): void
    {
        $this->withoutVite();
        \App\Models\BlogPost::create([
            'title' => 'پست با عکس', 'slug' => 'post-with-image', 'content' => 'متن',
            'status' => 'published', 'published_at' => now(), 'featured_image' => 'blog/test.jpg',
        ]);

        $response = $this->get('/');

        $response->assertOk()->assertSee('blog-carousel__image-wrap', false);
    }
}
