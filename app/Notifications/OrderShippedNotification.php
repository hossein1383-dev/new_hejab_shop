<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderShippedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Order $order)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'سفارش ارسال شد',
            'message' => "سفارش شماره {$this->order->order_number} ارسال شد.",
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
        ];
    }
}
