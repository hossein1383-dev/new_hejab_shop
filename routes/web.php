<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BlogCategoryController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\CategoryPageController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\InventoryController as AdminInventoryController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\ProductPageController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VariantController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\CartController as CartApiController;
use App\Http\Controllers\Api\CheckoutController as CheckoutApiController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\OtpAuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sitemap (بخش ۳۱)
|--------------------------------------------------------------------------
*/
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

/*
| robots.txt به‌صورت پیش‌فرض توسط Web Server واقعی (Nginx/Apache) مستقیماً از
| public/ سرو می‌شود، ولی این Route هم برای محیط تست و php artisan serve
| (که فایل استاتیک را مستقیم نمی‌بینند) به‌عنوان Fallback اضافه شده.
*/
Route::get('/robots.txt', function () {
    return response(file_get_contents(public_path('robots.txt')), 200, ['Content-Type' => 'text/plain']);
});

/*
|--------------------------------------------------------------------------
| صفحه اصلی (بخش ۲۳)
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');

/*
|--------------------------------------------------------------------------
| لیست محصولات / دسته‌بندی / جزئیات محصول (بخش ۱۰ و ۱۱)
|--------------------------------------------------------------------------
*/
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/category/{category:slug}', [ProductController::class, 'index'])->name('products.category');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

/*
|--------------------------------------------------------------------------
| صفحات ثابت و وبلاگ (بخش ۳۷)
|--------------------------------------------------------------------------
*/
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/page/{slug}', [PageController::class, 'show'])->name('pages.show');

/*
|--------------------------------------------------------------------------
| Auth Page (نمایش View — بخش ۲۰.۱)
|--------------------------------------------------------------------------
*/
Route::get('/login', fn () => view('auth.login'))->name('login');

/*
|--------------------------------------------------------------------------
| Auth با شماره‌تلفن + OTP — جایگزین کامل ایمیل/رمز عبور (بخش ۴، تصمیم صریح)
|--------------------------------------------------------------------------
| این مسیرها عمداً در web.php هستند نه api.php، چون به Session/CSRF نیاز دارند.
*/
Route::post('/auth/otp/request', [OtpAuthController::class, 'requestOtp'])
    ->middleware('throttle:5,2') // جلوگیری از Spam پیامک (بخش ۳۴)
    ->name('auth.otp.request');

Route::post('/auth/otp/verify', [OtpAuthController::class, 'verify'])
    ->middleware('throttle:10,2')
    ->name('auth.otp.verify');

Route::post('/auth/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('auth.logout');

/*
|--------------------------------------------------------------------------
| Cart - هم Guest هم User (بخش ۹). به Session نیاز دارد چون سبد مهمان
| با Cookie امن ردیابی می‌شود.
|--------------------------------------------------------------------------
*/
Route::get('/cart', [CartController::class, 'index'])->name('cart.show');
Route::post('/cart', [CartApiController::class, 'store'])->name('cart.store');
Route::put('/cart/{item}', [CartApiController::class, 'update'])->name('cart.update');
Route::delete('/cart/{item}', [CartApiController::class, 'destroy'])->name('cart.destroy');

/*
|--------------------------------------------------------------------------
| Wishlist - فقط کاربر لاگین‌شده (بخش ۹)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/wishlist', [\App\Http\Controllers\WishlistPageController::class, 'show'])->name('wishlist.index');
    Route::post('/wishlist/{product}', [WishlistController::class, 'store'])->name('wishlist.store');
    Route::delete('/wishlist/{product}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');
});

/*
|--------------------------------------------------------------------------
| آدرس‌ها — فقط کاربر لاگین‌شده (بخش ۳). Guest آدرس را مستقیم در Checkout وارد می‌کند.
|--------------------------------------------------------------------------
*/
Route::get('/heropost-cities', [\App\Http\Controllers\Api\HeropostCityController::class, 'index'])->name('heropost.cities');
Route::middleware('auth')->group(function () {
    Route::get('/addresses', [AddressController::class, 'index'])->name('addresses.index');
    Route::post('/addresses', [AddressController::class, 'store'])->name('addresses.store');
    Route::put('/addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');
});

/*
|--------------------------------------------------------------------------
| Checkout / Payment / Order — بخش ۱۲، ۱۳ و ۱۴. Guest Checkout مجاز است
| (بخش ۲۹.۱)، پس این مسیرها نیازی به auth ندارند.
|--------------------------------------------------------------------------
*/
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.show');
Route::post('/checkout', [CheckoutApiController::class, 'store'])->middleware(['auth', 'throttle:10,1'])->name('checkout.store');
Route::post('/checkout/estimate-shipping', [\App\Http\Controllers\Api\ShippingEstimateController::class, 'estimate'])->name('checkout.estimate-shipping');
Route::post('/coupons/validate', [CouponController::class, 'validateCode'])->middleware('throttle:20,1')->name('coupons.validate');

Route::get('/payment/fake/{reference}', [PaymentController::class, 'showFakeGateway'])->name('payment.fake.show');
Route::match(['get', 'post'], '/payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');

Route::get('/orders/{order}/confirmation', [OrderController::class, 'confirmation'])->name('orders.confirmation');
Route::middleware('auth')->post('/orders/{order}/retry-payment', [OrderController::class, 'retryPayment'])->name('orders.retry-payment');
Route::middleware('auth')->delete('/orders/{order}/cancel-own', [OrderController::class, 'cancelOwn'])->name('orders.cancel-own');

/*
|--------------------------------------------------------------------------
| Review / Question & Answer (بخش ۱۷)
|--------------------------------------------------------------------------
*/
Route::get('/products/{product}/reviews', [ReviewController::class, 'index'])->name('reviews.index');
Route::get('/products/{product}/questions', [QuestionController::class, 'index'])->name('questions.index');

Route::middleware('auth')->group(function () {
    Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::post('/reviews/{review}/moderate', [ReviewController::class, 'moderate'])->name('reviews.moderate');
    Route::post('/products/{product}/questions', [QuestionController::class, 'store'])->name('questions.store');
    Route::post('/questions/{question}/answer', [QuestionController::class, 'answer'])->name('questions.answer');
});

/*
|--------------------------------------------------------------------------
| کیف پول — فقط کاربر لاگین‌شده (بخش ۱۸)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->get('/wallet', [\App\Http\Controllers\WalletPageController::class, 'show'])->name('wallet.show');
Route::middleware('auth')->post('/wallet/topup', [\App\Http\Controllers\WalletTopupController::class, 'initiate'])->middleware('throttle:10,1')->name('wallet.topup.initiate');
Route::match(['get', 'post'], '/wallet/topup/callback', [\App\Http\Controllers\WalletTopupController::class, 'callback'])->name('wallet.topup.callback');

/*
|--------------------------------------------------------------------------
| حساب کاربری — پروفایل، سفارش‌ها، آدرس‌ها (فقط کاربر لاگین‌شده)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'profile'])->name('profile');
    Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::get('/orders', [AccountController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}', [AccountController::class, 'orderShow'])->name('orders.show');
    Route::get('/addresses', [AccountController::class, 'addresses'])->name('addresses');
});

/*
|--------------------------------------------------------------------------
| پنل مدیریت (بخش ۱۹) — فقط کاربران دارای یکی از نقش‌های Staff (بخش ۴)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin.staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::put('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.update-status');
Route::post('/orders/{order}/cancel', [AdminOrderController::class, 'cancel'])->name('orders.cancel');
Route::post('/orders/{order}/create-parcel', [AdminOrderController::class, 'createParcel'])->name('orders.create-parcel');

    Route::get('/categories', [CategoryPageController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [CategoryPageController::class, 'create'])->name('categories.create');
    Route::post('/categories', [CategoryPageController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [CategoryPageController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [CategoryPageController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryPageController::class, 'destroy'])->name('categories.destroy');

    Route::get('/products', [ProductPageController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductPageController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductPageController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [ProductPageController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductPageController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductPageController::class, 'destroy'])->name('products.destroy');
    Route::delete('/product-images/{image}', [ProductPageController::class, 'destroyImage'])->name('products.images.destroy');

    Route::get('/coupons', [AdminCouponController::class, 'index'])->name('coupons.index');
    Route::get('/coupons/create', [AdminCouponController::class, 'create'])->name('coupons.create');
    Route::post('/coupons', [AdminCouponController::class, 'store'])->name('coupons.store');
    Route::get('/coupons/{coupon}/edit', [AdminCouponController::class, 'edit'])->name('coupons.edit');
    Route::put('/coupons/{coupon}', [AdminCouponController::class, 'update'])->name('coupons.update');
    Route::delete('/coupons/{coupon}', [AdminCouponController::class, 'destroy'])->name('coupons.destroy');
    Route::post('/coupons/{coupon}/send-gift-sms', [AdminCouponController::class, 'sendGiftSms'])->name('coupons.send-gift-sms');

    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::put('/reviews/{review}/moderate', [AdminReviewController::class, 'moderate'])->name('reviews.moderate');

    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

    Route::get('/banners', [BannerController::class, 'index'])->name('banners.index');
    Route::get('/banners/create', [BannerController::class, 'create'])->name('banners.create');
    Route::post('/banners', [BannerController::class, 'store'])->name('banners.store');
    Route::get('/banners/{banner}/edit', [BannerController::class, 'edit'])->name('banners.edit');
    Route::put('/banners/{banner}', [BannerController::class, 'update'])->name('banners.update');
    Route::delete('/banners/{banner}', [BannerController::class, 'destroy'])->name('banners.destroy');

    Route::get('/pages', [AdminPageController::class, 'index'])->name('pages.index');
    Route::get('/pages/create', [AdminPageController::class, 'create'])->name('pages.create');
    Route::post('/pages', [AdminPageController::class, 'store'])->name('pages.store');
    Route::get('/pages/{page}/edit', [AdminPageController::class, 'edit'])->name('pages.edit');
    Route::put('/pages/{page}', [AdminPageController::class, 'update'])->name('pages.update');
    Route::delete('/pages/{page}', [AdminPageController::class, 'destroy'])->name('pages.destroy');

    Route::get('/blog', [BlogPostController::class, 'index'])->name('blog.index');
    Route::get('/blog/create', [BlogPostController::class, 'create'])->name('blog.create');
    Route::post('/blog', [BlogPostController::class, 'store'])->name('blog.store');
    Route::get('/blog/{blog_post}/edit', [BlogPostController::class, 'edit'])->name('blog.edit');
    Route::put('/blog/{blog_post}', [BlogPostController::class, 'update'])->name('blog.update');
    Route::delete('/blog/{blog_post}', [BlogPostController::class, 'destroy'])->name('blog.destroy');
    Route::get('/blog-categories', [BlogCategoryController::class, 'index'])->name('blog.categories.index');
    Route::post('/blog-categories', [BlogCategoryController::class, 'store'])->name('blog.categories.store');
    Route::delete('/blog-categories/{blog_category}', [BlogCategoryController::class, 'destroy'])->name('blog.categories.destroy');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');

    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

    Route::get('/attributes', [AttributeController::class, 'index'])->name('attributes.index');
    Route::post('/attributes', [AttributeController::class, 'store'])->name('attributes.store');
    Route::post('/attributes/{attribute}/values', [AttributeController::class, 'storeValue'])->name('attributes.values.store');
    Route::delete('/attribute-values/{value}', [AttributeController::class, 'destroyValue'])->name('attribute-values.destroy');

    Route::get('/products/{product}/variants', [VariantController::class, 'index'])->name('products.variants.index');
    Route::post('/products/{product}/variants', [VariantController::class, 'bulkStore'])->name('products.variants.store');
    Route::delete('/products/{product}/variants/{variant}', [VariantController::class, 'destroy'])->name('products.variants.destroy');

    Route::get('/inventory', [AdminInventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/{product}/adjust', [AdminInventoryController::class, 'adjust'])->name('inventory.adjust');
});
