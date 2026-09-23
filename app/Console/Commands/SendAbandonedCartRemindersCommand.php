<?php

namespace App\Console\Commands;

use App\Models\Cart;
use App\Notifications\AbandonedCartNotification;
use Illuminate\Console\Command;

/**
 * یادآوری سبد خرید رهاشده — بخش ۳۸.
 * معیار «رهاشده»: سبد User (نه Guest) که حداقل ۲۴ ساعت تغییری نکرده،
 * آیتم دارد، و هنوز یادآوری برایش ارسال نشده — فقط یک‌بار در طول عمر سبد
 * (Assumption ساده و مستند طبق بخش ۴۶؛ اگر یادآوری دوره‌ای/چندمرحله‌ای
 * لازم شد، به‌راحتی قابل تغییر است).
 */
class SendAbandonedCartRemindersCommand extends Command
{
    protected $signature = 'carts:send-abandoned-reminders';
    protected $description = 'برای سبدهای خرید رهاشده کاربران لاگین‌کرده یادآوری ارسال می‌کند';

    public function handle(): int
    {
        $carts = Cart::query()
            ->whereNotNull('user_id')
            ->where('status', 'active')
            ->whereNull('abandoned_reminder_sent_at')
            ->where('updated_at', '<=', now()->subHours(24))
            ->whereHas('items')
            ->with('user')
            ->get();

        foreach ($carts as $cart) {
            $cart->user->notify(new AbandonedCartNotification($cart));
            $cart->update(['abandoned_reminder_sent_at' => now()]);
        }

        $this->info("{$carts->count()} یادآوری سبد رهاشده ارسال شد.");

        return self::SUCCESS;
    }
}
