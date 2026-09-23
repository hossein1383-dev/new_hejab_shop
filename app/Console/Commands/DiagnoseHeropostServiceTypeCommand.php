<?php

namespace App\Console\Commands;

use App\Models\HeropostCity;
use App\Services\HeropostClient;
use Illuminate\Console\Command;

/**
 * بخش ۵۲: پیدا کردن مقدار واقعی ServiceTypeID/PostTypeID/PacketTypeID —
 * چون مستندات هیروپست این‌ها را با اسم (مثل «پیشتاز») مشخص نکرده بود،
 * دقیقاً مثل کاری که برای province_id کردیم، این دستور چند مقدار محتمل
 * (۱ تا ۱۰) را روی یک شهر واقعی امتحان می‌کند و خودِ نتیجه/خطای هیروپست
 * (که معمولاً اسم سرویس را در پیام خطا می‌آورد) را نشان می‌دهد.
 */
class DiagnoseHeropostServiceTypeCommand extends Command
{
    protected $signature = 'heropost:diagnose-service-type {--to-city= : شناسه شهر مقصد برای تست (پیش‌فرض: اولین شهر ذخیره‌شده)}';
    protected $description = 'تست مقادیر مختلف ServiceTypeID روی هیروپست تا مشخص شود کدام «پیشتاز»/«عادی»/... است';

    public function handle(HeropostClient $client): int
    {
        $toCityId = $this->option('to-city') ?: HeropostCity::query()->value('heropost_city_id');

        if (! $toCityId) {
            $this->error('هیچ شهری ذخیره نشده — اول php artisan heropost:sync-cities را بزنید.');

            return self::FAILURE;
        }

        $this->info("تست روی شهر مقصد با شناسه: {$toCityId}\n");

        foreach (range(1, 10) as $serviceTypeId) {
            try {
                $result = $client->calculatePrice([
                    'ToCityID' => $toCityId,
                    'ParcelCategoryID' => 3,
                    'BoxSizeID' => 8,
                    'ServiceTypeID' => $serviceTypeId,
                    'PacketTypeID' => 2,
                    'Weight' => 500,
                    'ParcelValue' => 1000000,
                    'NonStandardPackage' => 0,
                ]);

                $price = $result['TotalPrice'] ?? '?';
                $this->info("✓ ServiceTypeID={$serviceTypeId} → قیمت: {$price} ریال");
            } catch (\Throwable $e) {
                $this->line("✗ ServiceTypeID={$serviceTypeId} → رد شد: " . $e->getMessage());
            }
        }

        $this->line("\nبرای PostTypeID/PacketTypeID هم می‌توانید همین دستور را با تغییر مقدار ثابت در کد امتحان کنید.");

        return self::SUCCESS;
    }
}
