<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('sitemap.xml');
    }

    public function test_sitemap_includes_active_products(): void
    {
        $product = Product::factory()->create(['slug' => 'sitemap-product-test', 'status' => 'active']);

        $response = $this->get('/sitemap.xml');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee('sitemap-product-test');
    }

    public function test_robots_txt_is_accessible(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk()->assertSee('Sitemap');
    }
}
