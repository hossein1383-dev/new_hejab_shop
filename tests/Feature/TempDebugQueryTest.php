<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TempDebugQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_dump_home_page_queries(): void
    {
        $this->withoutVite();
        Product::factory()->count(5)->create(['is_featured' => true]);

        DB::listen(function ($query) {
            if (str_contains($query->sql, '"reviews"') && str_contains($query->sql, '= ?')) {
                $trace = collect(debug_backtrace())
                    ->first(fn ($frame) => isset($frame['file']) && ! str_contains($frame['file'], '/vendor/'));
                fwrite(STDERR, "\n[REVIEWS QUERY] از: " . ($trace['file'] ?? '?') . ':' . ($trace['line'] ?? '?') . "\n");
            }
        });

        $this->get('/');

        $this->assertTrue(true);
    }
}