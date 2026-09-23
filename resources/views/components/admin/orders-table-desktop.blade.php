{{--
    جدول سفارش‌های دسکتاپ: جدول کامل با تمام ستون‌ها (بخش ۲۰.۱ بند ب) —
    چون در دسکتاپ فضای افقی برای دیدن هم‌زمان همه ستون‌ها کافی است.
--}}
<div class="admin-orders-desktop">
    <table class="admin-table">
        <thead>
            <tr>
                <th>شماره سفارش</th>
                <th>مشتری</th>
                <th>تاریخ</th>
                <th>مبلغ کل</th>
                <th>وضعیت</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ $order->user?->name ?? $order->guest_name ?? 'مهمان' }}</td>
                    <td>{{ $order->created_at->format('Y/m/d H:i') }}</td>
                    <td>{{ number_format($order->total) }} تومان</td>
                    <td><span class="admin-status-badge admin-status-badge--{{ $order->status }}">{{ $order->status }}</span></td>
                    <td><a href="{{ route('admin.orders.show', $order) }}" class="admin-table__link">مشاهده</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="admin-empty">سفارشی یافت نشد.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
