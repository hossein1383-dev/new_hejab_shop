<div class="admin-orders-mobile">
    @forelse($coupons as $coupon)
        <div class="admin-order-card">
            <div class="admin-order-card__row">
                <strong>{{ $coupon->code }}</strong>
                <span class="admin-status-badge admin-status-badge--{{ $coupon->status === 'active' ? 'paid' : 'cancelled' }}">{{ $coupon->status }}</span>
            </div>
            <div class="admin-order-card__row admin-order-card__row--muted">
                <span>{{ $coupon->type === 'percentage' ? $coupon->value . '%' : number_format($coupon->value) . ' تومان' }}</span>
                <span>{{ $coupon->usages_count }} استفاده</span>
            </div>
            <div class="admin-order-card__row">
                <a href="{{ route('admin.coupons.edit', $coupon) }}" class="admin-table__link">ویرایش</a>
                <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" data-confirm="حذف این کد تخفیف؟">
                    @csrf @method('DELETE')
                    <button type="submit" class="admin-table__link admin-table__link--danger">حذف</button>
                </form>
            </div>
        </div>
    @empty
        <p class="admin-empty">کد تخفیفی یافت نشد.</p>
    @endforelse
</div>
