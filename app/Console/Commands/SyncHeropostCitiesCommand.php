<?php

namespace App\Console\Commands;

use App\Models\HeropostCity;
use App\Services\HeropostClient;
use Illuminate\Console\Command;

/**
 * لیست شهرهای هیروپست را یک‌بار می‌گیرد و محلی ذخیره می‌کند — بخش ۱۵.
 * باید بعد از تنظیم اطلاعات ورود در .env، دستی اجرا شود (و بعداً می‌توان
 * در Scheduler هفتگی گذاشت اگر لیست شهرها به‌ندرت عوض شود).
 *
 * ⚠️ چون مستندات API نمونه دقیق پاسخ این سرویس را نشان نداده بود، این
 * دستور چند نام کلید محتمل را امتحان می‌کند؛ بعد از اولین اجرای واقعی با
 * اطلاعات ورود واقعی، اگر شهری ذخیره نشد، خروجی --dump را برای دیدن پاسخ
 * خام و اصلاح نگاشت کلیدها استفاده کنید.
 */
class SyncHeropostCitiesCommand extends Command
{
    protected $signature = 'heropost:sync-cities {--dump : نمایش پاسخ خام API برای عیب‌یابی نگاشت کلیدها} {--verify : تایید زنده نگاشت شناسه استان با چند استان شناخته‌شده}';
    protected $description = 'دریافت و ذخیره محلی لیست شهرهای هیروپست برای استعلام قیمت پستی';

    /**
     * مرکز هر ۳۱ استان ایران — اگر نگاشت PROVINCE_NAMES درست باشد،
     * وقتی این province_id را زنده از هیروپست بخواهیم، باید همین شهر در
     * لیست برگشتی باشد.
     */
    public const KNOWN_CAPITALS = [
        1 => 'تهران', 2 => 'رشت', 3 => 'تبریز', 4 => 'اهواز',
        5 => 'شیراز', 6 => 'اصفهان', 7 => 'مشهد', 8 => 'قزوین',
        9 => 'سمنان', 10 => 'قم', 11 => 'اراک', 12 => 'زنجان',
        13 => 'ساری', 14 => 'گرگان', 15 => 'اردبیل', 16 => 'ارومیه',
        17 => 'همدان', 18 => 'سنندج', 19 => 'کرمانشاه', 20 => 'خرم‌آباد',
        21 => 'بوشهر', 22 => 'کرمان', 23 => 'بندرعباس', 24 => 'شهرکرد',
        25 => 'یزد', 26 => 'زاهدان', 27 => 'ایلام', 28 => 'یاسوج',
        29 => 'بجنورد', 30 => 'بیرجند', 31 => 'کرج',
    ];

    public function handle(HeropostClient $client): int
    {
        if ($this->option('verify')) {
            return $this->verifyProvinceMapping($client);
        }

        try {
            $cities = $client->getCities();
        } catch (\Throwable $e) {
            $this->error('اتصال به هیروپست ممکن نشد: ' . $e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('dump')) {
            $this->line(json_encode($cities, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }

        if (empty($cities)) {
            $this->warn('پاسخ API خالی بود — با --dump پاسخ خام را ببینید.');

            return self::FAILURE;
        }

        $saved = 0;
        foreach ($cities as $city) {
            $id = $city['id'] ?? $city['CityID'] ?? $city['ID'] ?? null;
            $name = $city['name'] ?? $city['CityName'] ?? $city['Name'] ?? null;
            $provinceId = $city['province_id'] ?? $city['ProvinceID'] ?? null;

            if (! $id || ! $name) {
                continue; // نگاشت کلید ناشناخته — با --dump بررسی شود
            }

            HeropostCity::updateOrCreate(
                ['heropost_city_id' => $id],
                [
                    'name' => $name,
                    'province_name' => HeropostCity::provinceNameFor($provinceId ? (int) $provinceId : null),
                    'province_code' => $provinceId,
                ]
            );
            $saved++;
        }

        $this->info("{$saved} شهر ذخیره شد.");

        if ($saved === 0) {
            $this->warn('هیچ شهری ذخیره نشد — احتمالاً نام کلیدهای پاسخ API با حدس این دستور فرق دارد. با --dump پاسخ خام را بررسی کنید.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * یکسان‌سازی رسم‌الخط فارسی/عربی برای مقایسه: «ي» عربی و «ی» فارسی
     * (و ك/ک) از نظر کدپوینت یونیکد متفاوتند؛ همچنین فاصله و نیم‌فاصله
     * در اسم‌های دوتکه مثل «خرم‌آباد»/«شهرکرد» ممکن است جور دیگری نوشته
     * شده باشد — همه این‌ها قبل از مقایسه حذف می‌شوند.
     */
    private function normalizeArabic(string $text): string
    {
        $text = str_replace(['ي', 'ك'], ['ی', 'ک'], $text);

        return str_replace([' ', "\u{200C}"], '', $text); // فاصله معمولی و نیم‌فاصله
    }

    /**
     * برای هر ۳۱ استان، شهرهایش را زنده از هیروپست می‌گیرد و چک می‌کند
     * شهر مرکز موردانتظار در لیست باشد — اگر بود، نگاشت PROVINCE_NAMES
     * درست است؛ اگر نبود، شماره‌گذاری هیروپست با استاندارد رایج فرق دارد.
     */
    private function verifyProvinceMapping(HeropostClient $client): int
    {
        $allOk = true;

        foreach (self::KNOWN_CAPITALS as $provinceId => $expectedCity) {
            $provinceName = HeropostCity::provinceNameFor($provinceId);
            $expectedNormalized = $this->normalizeArabic($expectedCity);

            try {
                $cities = $client->getCitiesByProvince($provinceId);
            } catch (\Throwable $e) {
                $this->error("province_id={$provinceId} ({$provinceName}): خطا در اتصال — " . $e->getMessage());
                $allOk = false;

                continue;
            }

            $names = collect($cities)->map(fn ($c) => $this->normalizeArabic(trim($c['name'] ?? $c['CityName'] ?? $c['Name'] ?? '')));
            $found = $names->contains(fn ($name) => str_contains($name, $expectedNormalized));

            if ($found) {
                $this->info("✓ province_id={$provinceId} ({$provinceName}) — شهر «{$expectedCity}» پیدا شد. نگاشت درست است.");
            } else {
                $this->error("✗ province_id={$provinceId} ({$provinceName}) — شهر «{$expectedCity}» در لیست نبود! نگاشت احتمالاً اشتباه است.");
                $this->line('   شهرهای واقعی این province_id: ' . $names->take(5)->implode('، '));
                $allOk = false;
            }
        }

        return $allOk ? self::SUCCESS : self::FAILURE;
    }
}
