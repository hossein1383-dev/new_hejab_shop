<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayContract;
use App\Contracts\ShippingGatewayContract;
use App\Contracts\SmsGatewayContract;
use App\Models\Category;
use App\Models\Page;
use App\Services\HeropostClient;
use App\Services\PaymentGateways\FakePaymentGateway;
use App\Services\PaymentGateways\ZibalGateway;
use App\Services\SettingService;
use App\Services\ShippingGateways\FlatRateShippingGateway;
use App\Services\ShippingGateways\HeropostShippingGateway;
use App\Services\SmsGateways\FakeSmsGateway;
use App\Services\SmsGateways\SmsIrGateway;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // بخش ۱۴/۱۵: انتخاب Gateway واقعی با PAYMENT_GATEWAY_DRIVER در .env
        // تعیین می‌شود. تا وقتی این مقدار روی zibal نباشد (یا merchant
        // تنظیم نشده باشد)، همیشه همان FakePaymentGateway قبلی استفاده
        // می‌شود — دقیقاً همان رفتاری که قبل از افزودن Zibal وجود داشت.
        $useZibal = config('services.zibal.driver') === 'zibal' && config('services.zibal.merchant');

        if ($useZibal) {
            $merchant = config('services.zibal.merchant');
            $this->app->singleton(PaymentGatewayContract::class, fn () => new ZibalGateway($merchant));
        } else {
            $this->app->singleton(PaymentGatewayContract::class, fn () => new FakePaymentGateway());
        }

        // ⚠️ برای Production باید به یک سرویس واقعی پیامک (کاوه‌نگار، ملی‌پیامک و...) تغییر کند.
        // بخش ۱۴: SMS.ir فقط وقتی فعال می‌شود که driver=smsir و شناسه الگوی
        // OTP در .env تنظیم شده باشد؛ تا آن زمان همان Fake قبلی (فقط در
        // Log) کار می‌کند — دقیقاً همان رفتار قبل از این تغییر.
        $smsConfig = config('services.smsir', []);
        $smsirConfigured = ($smsConfig['driver'] ?? null) === 'smsir' && ($smsConfig['otp_template_id'] ?? null);

        if ($smsirConfigured) {
            $this->app->bind(SmsGatewayContract::class, fn () => new SmsIrGateway(
                (int) $smsConfig['otp_template_id'],
                ($smsConfig['new_order_template_id'] ?? null) ? (int) $smsConfig['new_order_template_id'] : null,
                ($smsConfig['order_confirmation_template_id'] ?? null) ? (int) $smsConfig['order_confirmation_template_id'] : null,
                ($smsConfig['parcel_shipped_template_id'] ?? null) ? (int) $smsConfig['parcel_shipped_template_id'] : null,
                ($smsConfig['coupon_gift_template_id'] ?? null) ? (int) $smsConfig['coupon_gift_template_id'] : null,
            ));
        } else {
            $this->app->bind(SmsGatewayContract::class, FakeSmsGateway::class);
        }

        // بخش ۱۵: هیروپست فقط وقتی فعال می‌شود که هر ۴ مقدار ورود در .env
        // پر شده باشند؛ تا آن زمان همان نرخ ثابت قبلی بدون تغییر کار می‌کند.
        $heropostConfig = config('services.heropost', []);
        $heropostConfigured = ($heropostConfig['driver'] ?? null) === 'heropost'
            && ($heropostConfig['username'] ?? null)
            && ($heropostConfig['password'] ?? null)
            && ($heropostConfig['security_code'] ?? null);

        $this->app->singleton(HeropostClient::class, fn () => new HeropostClient(
            $heropostConfig['base_url'] ?? 'https://heropost.ir/api',
            $heropostConfig['username'] ?? '',
            $heropostConfig['password'] ?? '',
            $heropostConfig['security_code'] ?? '',
        ));

        if ($heropostConfigured) {
            $this->app->singleton(ShippingGatewayContract::class, fn ($app) => new HeropostShippingGateway(
                $app->make(HeropostClient::class),
                new FlatRateShippingGateway()
            ));
        } else {
            $this->app->singleton(ShippingGatewayContract::class, fn () => new FlatRateShippingGateway());
        }
    }

    public function boot(): void
    {
        // بخش ۲۲: استفاده راحت در Blade — مثال: {{ jalali($order->created_at) }}
        \Illuminate\Support\Facades\Blade::directive('jalali', function ($expression) {
            return "<?php echo \\App\\Support\\JalaliDate::format({$expression}); ?>";
        });

        // بخش ۵۷: اسم فروشگاه در هدر (موبایل/دسکتاپ) — از همان تنظیم
        // site_name که برای فوتر هم استفاده می‌شود، با همان Cache.
        View::composer(['components.header.header-mobile', 'components.header.header-desktop'], function ($view) {
            $view->with('siteName', Cache::remember('site-name', now()->addMinutes(30), fn () => app(SettingService::class)->all()['site_name'] ?? config('app.name')));
        });

        // فوتر در همه صفحات فروشگاه یکسان است، پس به‌جای پاس‌دادن دستی از هر
        // Controller، یک‌بار اینجا با Cache کوتاه محاسبه و به View تزریق
        // می‌شود (بخش ۳۲/۳۳).
        View::composer('components.footer', function ($view) {
            $view->with(Cache::remember('footer.data', now()->addMinutes(30), function () {
                return [
                    'footerSettings' => app(SettingService::class)->all(),
                    'footerPages' => Page::active()->orderBy('title')->get(['title', 'slug']),
                    'footerCategories' => Category::active()->roots()->orderBy('sort_order')->take(8)->get(['name', 'slug']),
                ];
            }));
        });
    }
}
