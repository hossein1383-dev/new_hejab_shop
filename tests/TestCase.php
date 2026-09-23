<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    /**
     * چون چند صفحه (اصلی، Sitemap، و حالا Footer) نتیجه‌شان را Cache
     * می‌کنند، بدون این پاک‌سازی سراسری، داده باقی‌مانده از یک تست می‌تواند
     * روی تست بعدی اثر بگذارد (چون Cache برخلاف دیتابیس بین تست‌ها
     * خودکار Reset نمی‌شود).
     */
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }
}
