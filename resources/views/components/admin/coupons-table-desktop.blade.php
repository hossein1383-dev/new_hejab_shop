<div class="admin-orders-desktop">
    <table class="admin-table">
        <thead>
            <tr>
                <th>کد</th>
                <th>نوع/مقدار</th>
                <th>حداقل سفارش</th>
                <th>استفاده‌شده</th>
                <th>وضعیت</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($coupons as $coupon)
                <tr>
                    <td>{{ $coupon->code }}</td>
                    <td>{{ $coupon->type === 'percentage' ? $coupon->value . '%' : number_format($coupon->value) . ' تومان' }}</td>
                    <td>{{ number_format($coupon->min_order_amount) }} تومان</td>
                    <td>{{ $coupon->usages_count }}@if($coupon->usage_limit) / {{ $coupon->usage_limit }} @endif</td>
                    <td><span class="admin-status-badge admin-status-badge--{{ $coupon->status === 'active' ? 'paid' : 'cancelled' }}">{{ $coupon->status }}</span></td>
                    <td>
                        <a href="{{ route('admin.coupons.edit', $coupon) }}" class="admin-table__link">ویرایش</a>
                        <form method="POST" action="{{ route('admin.coupons.send-gift-sms', $coupon) }}" style="display:inline" data-confirm="پیامک این کد تخفیف برای همه مشتریان (با شماره تلفن) ارسال شود؟">
                            @csrf
                            <button type="submit" class="admin-table__link">ارسال پیامک هدیه</button>
                        </form>
                        <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" style="display:inline" data-confirm="حذف این کد تخفیف؟">
                            @csrf @method('DELETE')
                            <button type="submit" class="admin-table__link admin-table__link--danger">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="admin-empty">کد تخفیفی یافت نشد.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
