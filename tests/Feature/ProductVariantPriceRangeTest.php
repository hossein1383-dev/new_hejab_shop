<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بخش ۵۸: نمایش «از X تومان» در کارت محصول وقتی واریانت‌ها (مثلاً جنس
 * ندا/حریر) قیمت‌های متفاوت دارند.
 */
class ProductVariantPriceRangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_range_returns_no_range_when_variants_have_same_price(): void
    {
        $product = Product::factory()->create(['price' => 500000]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'price' => 500000]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'price' => 500000]);

        $range = $product->fresh()->load('variants')->priceRange();

        $this->assertFalse($range['has_range']);
        $this->assertEquals(500000, $range['min']);
    }

    public function test_price_range_detects_different_variant_prices(): void
    {
        $product = Product::factory()->create(['price' => 500000]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'price' => 500000]); // ندا
        ProductVariant::factory()->create(['product_id' => $product->id, 'price' => 750000]); // حریر

        $range = $product->fresh()->load('variants')->priceRange();

        $this->assertTrue($range['has_range']);
        $this->assertEquals(500000, $range['min']);
        $this->assertEquals(750000, $range['max']);
    }

    public function test_variant_without_own_price_falls_back_to_product_price(): void
    {
        $product = Product::factory()->create(['price' => 400000]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'price' => null]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'price' => 600000]);

        $range = $product->fresh()->load('variants')->priceRange();

        $this->assertTrue($range['has_range']);
        $this->assertEquals(400000, $range['min']);
        $this->assertEquals(600000, $range['max']);
    }

    public function test_inactive_variants_are_excluded_from_price_range(): void
    {
        $product = Product::factory()->create(['price' => 500000]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'price' => 500000, 'status' => 'active']);
        ProductVariant::factory()->create(['product_id' => $product->id, 'price' => 999000, 'status' => 'inactive']);

        $range = $product->fresh()->load('variants')->priceRange();

        $this->assertFalse($range['has_range']);
        $this->assertEquals(500000, $range['max']);
    }

    public function test_product_card_shows_starting_from_price_when_variants_differ(): void
    {
        $this->withoutVite();
        $product = Product::factory()->create(['name' => 'روسری تست دو قیمت', 'price' => 500000, 'status' => 'active']);
        ProductVariant::factory()->create(['product_id' => $product->id, 'price' => 500000]);
        ProductVariant::factory()->create(['product_id' => $product->id, 'price' => 750000]);

        $response = $this->get('/products');

        $response->assertOk()->assertSee('از 500,000 تومان');
    }
}
