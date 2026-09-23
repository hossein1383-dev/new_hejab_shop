<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // توجه: بدون statefulApi() — چون Frontend این پروژه Blade + AJAX هم‌مبدأ
        // است (بخش ۲۰)، نه یک SPA جدا، پس Sanctum Stateful لازم نیست
        // (بخش ۴۵: از Package/Complexity غیرضروری پرهیز کن).
        // مسیرهای نیازمند Session از گروه استاندارد "web" استفاده می‌کنند.

        $middleware->alias([
            'admin.staff' => \App\Http\Middleware\EnsureIsAdminStaff::class,
        ]);

        $middleware->append(\App\Http\Middleware\AddSecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
