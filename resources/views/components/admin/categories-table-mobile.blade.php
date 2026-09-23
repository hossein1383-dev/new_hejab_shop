<div class="admin-orders-mobile">
    @forelse($categories as $category)
        <div class="admin-order-card">
            <div class="admin-order-card__row">
                <strong>{{ $category->name }}</strong>
                <span class="admin-status-badge admin-status-badge--{{ $category->status === 'active' ? 'paid' : 'cancelled' }}">{{ $category->status }}</span>
            </div>
            <div class="admin-order-card__row admin-order-card__row--muted">
                <span>{{ $category->parent?->name ?? 'دسته اصلی' }}</span>
            </div>
            <div class="admin-order-card__row">
                <a href="{{ route('admin.categories.edit', $category) }}" class="admin-table__link">ویرایش</a>
                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" data-confirm="حذف این دسته‌بندی؟">
                    @csrf @method('DELETE')
                    <button type="submit" class="admin-table__link admin-table__link--danger">حذف</button>
                </form>
            </div>
        </div>
    @empty
        <p class="admin-empty">دسته‌بندی‌ای یافت نشد.</p>
    @endforelse
</div>
