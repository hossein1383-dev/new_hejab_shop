<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * اعلان ثبت سفارش — بخش ۱۸. با Queue پردازش می‌شود (بخش ۳۳: کارهای سنگین
 * Notification نباید Request اصلی را کند کنند).
 */
class OrderCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Order $order)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
        // برای فعال‌سازی ایمیل، 'mail' را اضافه کنید (نیازمند تنظیم SMTP در .env)
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'سفارش شما ثبت شد',
            'message' => "سفارش شماره {$this->order->order_number} با موفقیت ثبت شد و در انتظار پرداخت است.",
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
        ];
    }
}
