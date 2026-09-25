<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // بخش ۳۲/۳۳: لیست محصولات حالا Cache می‌شود؛ چون Cache بین تست‌ها
        // Reset نمی‌شود (برخلاف دیتابیس)، برای اطمینان کامل آن را پاک می‌کنیم.
        \Illuminate\Support\Facades\Cache::flush();
    }

    public function test_products_index_lists_active_products(): void
    {
        $this->withoutVite();

        Product::factory()->create(['name' => 'محصول فعال تست', 'status' => 'active']);
        Product::factory()->create(['name' => 'محصول غیرفعال تست', 'status' => 'draft']);

        $response = $this->get('/products');

        $response->assertOk()
            ->assertSee('محصول فعال تست')
            ->assertDontSee('محصول غیرفعال تست');
    }

    public function test_products_index_filters_by_category(): void
    {
        $this->withoutVite();

        $categoryA = Category::factory()->create(['slug' => 'category-a']);
        $categoryB = Category::factory()->create(['slug' => 'category-b']);

        Product::factory()->create(['name' => 'محصول دسته آ', 'category_id' => $categoryA->id, 'status' => 'active']);
        Product::factory()->create(['name' => 'محصول دسته ب', 'category_id' => $categoryB->id, 'status' => 'active']);

        $response = $this->get('/category/category-a');

        $response->assertOk()
            ->assertSee('محصول دسته آ')
            ->assertDontSee('محصول دسته ب');
    }

    public function test_products_index_filters_by_brand(): void
    {
        $this->withoutVite();

        $brandA = Brand::factory()->create(['name' => 'برند آ']);
        $brandB = Brand::factory()->create(['name' => 'برند ب']);

        Product::factory()->create(['name' => 'محصول برند آ', 'brand_id' => $brandA->id, 'status' => 'active']);
        Product::factory()->create(['name' => 'محصول برند ب', 'brand_id' => $brandB->id, 'status' => 'active']);

        $response = $this->get('/products?brands[]=' . $brandA->id);

        $response->assertOk()
            ->assertSee('محصول برند آ')
            ->assertDontSee('محصول برند ب');
    }

    public function test_product_detail_page_shows_product(): void
    {
        $this->withoutVite();

        $product = Product::factory()->create([
            'name' => 'محصول جزئیات تست',
            'slug' => 'product-detail-test',
            'status' => 'active',
        ]);

        $response = $this->get('/products/' . $product->slug);

        $response->assertOk()->assertSee('محصول جزئیات تست');
    }

    public function test_product_detail_page_404_for_inactive_product(): void
    {
        $this->withoutVite();

        $product = Product::factory()->create(['slug' => 'draft-product', 'status' => 'draft']);

        $response = $this->get('/products/' . $product->slug);

        $response->assertStatus(404);
    }

    public function test_product_detail_shows_related_products_from_same_category(): void
    {
        $this->withoutVite();

        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'slug' => 'main-product',
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        Product::factory()->create([
            'name' => 'محصول مرتبط تست',
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $response = $this->get('/products/' . $product->slug);

        $response->assertOk()->assertSee('محصول مرتبط تست');
    }

    public function test_product_detail_shows_approved_review_but_not_pending(): void
    {
        $this->withoutVite();

        $product = Product::factory()->create(['slug' => 'reviewed-product', 'status' => 'active']);
        $reviewer1 = \App\Models\User::factory()->create();
        $reviewer2 = \App\Models\User::factory()->create();

        \App\Models\Review::create([
            'product_id' => $product->id, 'user_id' => $reviewer1->id,
            'rating' => 5, 'comment' => 'نظر تاییدشده تست', 'status' => 'approved',
        ]);
        \App\Models\Review::create([
            'product_id' => $product->id, 'user_id' => $reviewer2->id,
            'rating' => 3, 'comment' => 'نظر در انتظار تست', 'status' => 'pending',
        ]);

        $response = $this->get('/products/' . $product->slug);

        $response->assertOk()
            ->assertSee('نظر تاییدشده تست')
            ->assertDontSee('نظر در انتظار تست');
    }

    public function test_products_index_shows_average_rating_when_reviews_exist(): void
    {
        $this->withoutVite();
        $product = Product::factory()->create(['status' => 'active']);
        $user1 = \App\Models\User::factory()->create();
        $user2 = \App\Models\User::factory()->create();
        $product->reviews()->create(['user_id' => $user1->id, 'rating' => 5, 'status' => 'approved', 'comment' => 'عالی بود']);
        $product->reviews()->create(['user_id' => $user2->id, 'rating' => 3, 'status' => 'approved', 'comment' => 'معمولی بود']);

        $response = $this->get('/products');

        // میانگین (5+3)/2 = 4 → باید دقیقاً ۴ ستاره پر نشان داده شود
        $response->assertOk();
        $filledStars = substr_count($response->getContent(), 'fill="#F59E0B"');
        $this->assertEquals(4, $filledStars);
    }

    public function test_products_index_hides_rating_when_no_reviews(): void
    {
        $this->withoutVite();
        Product::factory()->create(['status' => 'active']);

        $response = $this->get('/products');

        // ظرف امتیاز همیشه رزرو می‌شود (برای هم‌ترازی قیمت/دکمه بین کارت‌ها)
        // ولی وقتی نظری نیست، نباید هیچ ستاره‌ای (fill زرد) داخلش باشد.
        $response->assertOk();
        $this->assertStringNotContainsString('fill="#F59E0B"', $response->getContent());
    }

    public function test_product_page_shows_total_quantity_across_variants(): void
    {
        $this->withoutVite();
        $product = Product::factory()->create(['status' => 'active']);
        $variant1 = \App\Models\ProductVariant::factory()->create(['product_id' => $product->id]);
        $variant2 = \App\Models\ProductVariant::factory()->create(['product_id' => $product->id]);
        app(\App\Services\InventoryService::class)->recordMovement($product, $variant1, 'purchase', 10);
        app(\App\Services\InventoryService::class)->recordMovement($product, $variant2, 'purchase', 15);

        $response = $this->get("/products/{$product->slug}");

        $response->assertOk()->assertSee('موجودی کل: 25 عدد');
    }
}
