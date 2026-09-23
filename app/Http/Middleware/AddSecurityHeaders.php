<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * هدرهای امنیتی پایه روی هر پاسخ — بخش ۳۴.
 */
class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Permissions-Policy', 'geolocation=(), camera=(), microphone=()');
        $response->headers->set('Content-Security-Policy', $this->buildCspHeader());

        return $response;
    }

    /**
     * CSP سخت‌گیرانه برای Production، با استثنای Vite Dev Server فقط در Local
     * (بخش ۳۴). توجه: JSON-LD (type="application/ld+json") زیرمجموعه
     * script-src نیست چون نوع اجرایی JS ندارد، پس نیازی به استثنا برایش نیست.
     */
    private function buildCspHeader(): string
    {
        $scriptSrc = "'self'";
        $connectSrc = "'self'";

        if (app()->environment('local')) {
            // آدرس واقعی را از .env بخوانید اگر پورت Vite را عوض کرده‌اید
            $viteDevServer = env('VITE_DEV_SERVER_URL', 'http://localhost:5173');
            $scriptSrc .= " {$viteDevServer}";
            $connectSrc .= " {$viteDevServer} ws://localhost:5173";
        }

        $directives = [
            "default-src 'self'",
            "script-src {$scriptSrc}",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net", // بعضی صفحات Admin از style="" درون‌خطی استفاده می‌کنند + فونت وزیرمتن
            "img-src 'self' data: https:",
            "font-src 'self' data: https://cdn.jsdelivr.net",
            "connect-src {$connectSrc}",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        return implode('; ', $directives);
    }
}
