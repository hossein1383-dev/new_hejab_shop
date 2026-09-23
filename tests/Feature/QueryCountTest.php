<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * بخش ۳: «از N+1 Query جلوگیری کن». این تست‌ها اثبات می‌کنند تعداد Query ها
 * با افزایش تعداد محصولات رشد خطی پیدا نمی‌کند (که نشانه کلاسیک N+1 است).
 */
class QueryCountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function countQueriesFor(callable $action): int
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $action();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_home_page_query_count_does_not_grow_with_more_products(): void
    {
        $this->withoutVite();

        Product::factory()->count(3)->create(['is_featured' => true]);
        $queriesWithFew = $this->countQueriesFor(fn () => $this->get('/'));

        Cache::flush(); // Cache صفحه اصلی را پاک می‌کنیم تا واقعاً دوباره کوئری بزند
        Product::factory()->count(15)->create(['is_featured' => true]);
        $queriesWithMany = $this->countQueriesFor(fn () => $this->get('/'));

        $this->assertEquals(
            $queriesWithFew,
            $queriesWithMany,
            "تعداد Query صفحه اصلی با افزایش محصولات از {$queriesWithFew} به {$queriesWithMany} رشد کرد — نشانه N+1."
        );
    }

    public function test_product_listing_query_count_does_not_grow_with_more_products(): void
    {
        $this->withoutVite();

        Product::factory()->count(3)->create(['status' => 'active']);
        $queriesWithFew = $this->countQueriesFor(fn () => $this->get('/products'));

        Cache::flush();
        Product::factory()->count(15)->create(['status' => 'active']);
        $queriesWithMany = $this->countQueriesFor(fn () => $this->get('/products'));

        $this->assertEquals(
            $queriesWithFew,
            $queriesWithMany,
            "تعداد Query لیست محصولات با افزایش تعداد محصولات از {$queriesWithFew} به {$queriesWithMany} رشد کرد — نشانه N+1."
        );
    }
}
