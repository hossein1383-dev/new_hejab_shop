<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront - Public read-only (بخش ۶ / ۱۰)
|--------------------------------------------------------------------------
| این مسیرها واقعاً Stateless هستند (فقط خواندنی، بدون نیاز به Session)
| پس با پیشوند خودکار /api که Laravel به این فایل می‌دهد مشکلی ندارند.
| نتیجه واقعی: GET /api/categories, GET /api/products, GET /api/products/{id}
*/
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Admin - نیازمند Session (همان Login وب‌سایت) + Permission (در Policy هر Controller)
|--------------------------------------------------------------------------
| توجه: Guard اینجا "auth" (Session-based) است، نه "auth:sanctum".
| چون پنل ادمین هم بخشی از همان اپلیکیشن Blade هم‌مبدأ است، نه یک
| Client جداگانه که به Token نیاز داشته باشد (بخش ۴۵: از Overengineering پرهیز کن).
|
| توجه مهم: چون این فایل خودکار زیر پیشوند /api است ولی به Session نیاز
| داریم، گروه middleware "web" را صریحاً اضافه می‌کنیم تا StartSession/CSRF
| فعال شود. نتیجه واقعی: /api/admin/categories, /api/admin/products
*/
Route::middleware(['web', 'auth'])->prefix('admin')->group(function () {
    Route::apiResource('categories', CategoryController::class)->except(['index']);
    Route::apiResource('products', ProductController::class)->except(['index', 'show']);
});

/*
|--------------------------------------------------------------------------
| توجه: Cart, Wishlist و Auth دیگر اینجا نیستند.
|--------------------------------------------------------------------------
| این‌ها در routes/web.php تعریف شده‌اند (بدون پیشوند /api)، چون بخشی از
| همان اپلیکیشن Blade + AJAX هم‌مبدأ هستند، نه یک API جدا برای Client دیگر.
| نگه‌داشتن یک Feature در دو فایل مختلف باعث سردرگمی و Route تکراری می‌شود
| (دقیقاً همان مشکلی که باعث خطای ۴۰۵ شد) — پس فقط در web.php می‌مانند.
*/
