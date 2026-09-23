<?php

namespace App\Console\Commands;

use App\Models\Coupon;
use Illuminate\Console\Command;

/**
 * غیرفعال‌سازی خودکار کوپن‌های منقضی‌شده — بخش ۳۳ (Scheduler: Expired Coupons).
 */
class DeactivateExpiredCouponsCommand extends Command
{
    protected $signature = 'coupons:deactivate-expired';
    protected $description = 'کوپن‌های منقضی‌شده را به‌صورت خودکار غیرفعال می‌کند';

    public function handle(): int
    {
        $count = Coupon::where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->update(['status' => 'inactive']);

        $this->info("{$count} کد تخفیف منقضی‌شده غیرفعال شد.");

        return self::SUCCESS;
    }
}
