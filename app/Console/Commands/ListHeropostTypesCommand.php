<?php

namespace App\Console\Commands;

use App\Services\HeropostClient;
use Illuminate\Console\Command;

/**
 * بخش ۵۲: نمایش لیست واقعی انواع سرویس/سایز بسته/دسته‌بندی/نوع پاکت —
 * طبق مستندات کامل هیروپست، این‌ها وب‌سرویس اختصاصی خودشان را دارند
 * (نه نیاز به حدس‌زدن با آزمایش قیمت، مثل کاری که برای استان‌ها کردیم).
 */
class ListHeropostTypesCommand extends Command
{
    protected $signature = 'heropost:list-types';
    protected $description = 'نمایش لیست واقعی انواع سرویس پستی، سایز بسته، دسته‌بندی محتویات، و نوع پاکت از هیروپست';

    public function handle(HeropostClient $client): int
    {
        $this->showList('انواع سرویس پستی (ServiceTypeID) — همان چیزی که برای «پیشتاز» دنبالش بودیم', fn () => $client->getServiceTypes());
        $this->showList('سایزهای بسته (BoxSizeID)', fn () => $client->getBoxSizes());
        $this->showList('دسته‌بندی محتویات (ParcelCategoryID)', fn () => $client->getParcelCategories());
        $this->showList('انواع پاکت (PacketTypeID)', fn () => $client->getPacketTypes());

        return self::SUCCESS;
    }

    private function showList(string $title, \Closure $fetch): void
    {
        $this->info("\n=== {$title} ===");

        try {
            $items = $fetch();
            if (empty($items)) {
                $this->warn('پاسخ خالی بود.');

                return;
            }
            $this->line(json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
            $this->error('خطا: ' . $e->getMessage());
        }
    }
}
