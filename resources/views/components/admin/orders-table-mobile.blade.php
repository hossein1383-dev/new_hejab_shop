{{--
    جدول سفارش‌های موبایل: نمایش Card‌محور به‌جای جدول با اسکرول افقی
    (بخش ۲۰.۱ بند ب) — چون ستون‌های زیاد یک جدول واقعی در موبایل غیرقابل‌خواندن
    می‌شود، این یک Component کاملاً جدا از نسخه دسکتاپ است.
--}}
<div class="admin-orders-mobile">
    @forelse($orders as $order)
        <a href="{{ route('admin.orders.show', $order) }}" class="admin-order-card">
            <div class="admin-order-card__row">
                <strong>{{ $order->order_number }}</strong>
                <span class="admin-status-badge admin-status-badge--{{ $order->status }}">{{ $order->status }}</span>
            </div>
            <div class="admin-order-card__row admin-order-card__row--muted">
                <span>{{ $order->user?->name ?? $order->guest_name ?? 'مهمان' }}</span>
                <span>{{ $order->created_at->format('Y/m/d') }}</span>
            </div>
            <div class="admin-order-card__row">
                <span>{{ number_format($order->total) }} تومان</span>
            </div>
        </a>
    @empty
        <p class="admin-empty">سفارشی یافت نشد.</p>
    @endforelse
</div>
