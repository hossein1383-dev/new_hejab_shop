<?php

namespace App\Notifications;

use App\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * یادآوری سبد خرید رهاشده — بخش ۳۸ (Extension محتمل). فقط برای کاربر
 * لاگین‌کرده معنا دارد چون مهمان راه تماس ثبت‌شده‌ای ندارد.
 */
class AbandonedCartNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Cart $cart)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'کالاهایی در سبد خرید شما منتظرند',
            'message' => 'شما ' . $this->cart->itemsCount() . ' کالا در سبد خرید خود دارید. برای تکمیل خرید کلیک کنید.',
            'cart_id' => $this->cart->id,
        ];
    }
}
