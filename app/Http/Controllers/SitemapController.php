<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /** Sitemap پویا — بخش ۳۱. با Cache چون تغییرات محصولات هر لحظه رخ نمی‌دهد (بخش ۳۳). */
    public function index(): Response
    {
        $xml = Cache::remember('sitemap.xml', now()->addHours(6), function () {
            $urls = collect([
                ['loc' => url('/'), 'priority' => '1.0'],
                ['loc' => url('/products'), 'priority' => '0.8'],
                ['loc' => url('/blog'), 'priority' => '0.6'],
            ]);

            Category::active()->get()->each(function ($category) use ($urls) {
                $urls->push(['loc' => url('/category/' . $category->slug), 'priority' => '0.7']);
            });

            Product::active()->get()->each(function ($product) use ($urls) {
                $urls->push(['loc' => url('/products/' . $product->slug), 'priority' => '0.6']);
            });

            BlogPost::published()->get()->each(function ($post) use ($urls) {
                $urls->push(['loc' => url('/blog/' . $post->slug), 'priority' => '0.5']);
            });

            Page::active()->get()->each(function ($page) use ($urls) {
                $urls->push(['loc' => url('/page/' . $page->slug), 'priority' => '0.4']);
            });

            $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xmlContent .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            foreach ($urls as $url) {
                $xmlContent .= '<url><loc>' . e($url['loc']) . '</loc><priority>' . $url['priority'] . '</priority></url>' . "\n";
            }
            $xmlContent .= '</urlset>';

            return $xmlContent;
        });

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
