<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_responses_include_basic_security_headers(): void
    {
        $this->withoutVite();

        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_csp_allows_vite_dev_server_only_in_local_environment(): void
    {
        $this->withoutVite();

        $response = $this->get('/');
        $csp = $response->headers->get('Content-Security-Policy');

        // در محیط تست (testing) نباید Vite Dev Server در CSP باز باشد
        $this->assertStringNotContainsString('5173', $csp);
    }

    public function test_csp_allows_font_cdn_for_vazirmatn_font(): void
    {
        $this->withoutVite();

        $response = $this->get('/');
        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('style-src', $csp);
        $this->assertMatchesRegularExpression('/style-src[^;]*cdn\.jsdelivr\.net/', $csp);
        $this->assertMatchesRegularExpression('/font-src[^;]*cdn\.jsdelivr\.net/', $csp);
    }
}
