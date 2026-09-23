<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderShippedNotification;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(private readonly AuditLogService $auditLogService)
    {
    }

    /**
     * نقشه انتقال‌های مجاز وضعیت سفارش (بخش ۱۳: State Transition ها باید
     * کنترل‌شده و معتبر باشند). پرداخت/لغو خودکار جای دیگری (PaymentService/
     * CheckoutService) مدیریت می‌شود؛ این فقط انتقال‌های دستی Admin است.
     */
    private const ALLOWED_TRANSITIONS = [
        'paid' => ['processing', 'cancelled'],
        'processing' => ['preparing', 'cancelled'],
        'preparing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered'],
        'delivered' => ['returned'],
        'cancelled' => ['refunded'],
    ];

    public function updateStatus(Order $order, string $newStatus, User $admin, ?string $note = null): Order
    {
        $allowed = self::ALLOWED_TRANSITIONS[$order->status] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            throw new \DomainException("امکان تغییر وضعیت از «{$order->status}» به «{$newStatus}» وجود ندارد.");
        }

        return DB::transaction(function () use ($order, $newStatus, $admin, $note) {
            $oldStatus = $order->status;
            $order->update(['status' => $newStatus]);

            $order->statusHistories()->create([
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'user_id' => $admin->id,
                'note' => $note,
            ]);

            $this->auditLogService->log(
                $admin,
                'order.status_changed',
                $order,
                ['status' => $oldStatus],
                ['status' => $newStatus]
            );

            return $order->fresh();
        });
    }

    /** بعد از Commit صدا زده می‌شود تا Notification با Transaction مسابقه ندهد. */
    public function notifyIfShipped(Order $order): void
    {
        if ($order->status === 'shipped') {
            $order->user?->notify(new OrderShippedNotification($order));
        }
    }
}
