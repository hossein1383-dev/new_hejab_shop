<div class="admin-orders-mobile">
    @forelse($products as $product)
        <div class="admin-order-card">
            <div class="admin-order-card__row">
                <strong>{{ $product->name }}</strong>
                <span class="admin-status-badge admin-status-badge--{{ $product->status === 'active' ? 'paid' : 'cancelled' }}">{{ $product->status }}</span>
            </div>
            <div class="admin-order-card__row admin-order-card__row--muted">
                <span>{{ $product->sku }}</span>
                <span>{{ $product->category?->name }}</span>
            </div>
            <div class="admin-order-card__row">
                <span>{{ number_format($product->price) }} تومان</span>
            </div>
            <div class="admin-order-card__row">
                <a href="{{ route('admin.products.variants.index', $product) }}" class="admin-table__link">Variant</a>
                <a href="{{ route('admin.products.edit', $product) }}" class="admin-table__link">ویرایش</a>
                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" data-confirm="حذف این محصول؟">
                    @csrf @method('DELETE')
                    <button type="submit" class="admin-table__link admin-table__link--danger">حذف</button>
                </form>
            </div>
        </div>
    @empty
        <p class="admin-empty">محصولی یافت نشد.</p>
    @endforelse
</div>
