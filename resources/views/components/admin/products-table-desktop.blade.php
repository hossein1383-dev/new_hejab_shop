<div class="admin-orders-desktop">
    <table class="admin-table">
        <thead>
            <tr>
                <th>محصول</th>
                <th>SKU</th>
                <th>دسته‌بندی</th>
                <th>قیمت</th>
                <th>وضعیت</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
                <tr>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->sku }}</td>
                    <td>{{ $product->category?->name }}</td>
                    <td>{{ number_format($product->price) }} تومان</td>
                    <td><span class="admin-status-badge admin-status-badge--{{ $product->status === 'active' ? 'paid' : 'cancelled' }}">{{ $product->status }}</span></td>
                    <td>
                        <a href="{{ route('admin.products.variants.index', $product) }}" class="admin-table__link">Variant</a>
                        <a href="{{ route('admin.products.edit', $product) }}" class="admin-table__link">ویرایش</a>
                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" style="display:inline" data-confirm="حذف این محصول؟">
                            @csrf @method('DELETE')
                            <button type="submit" class="admin-table__link admin-table__link--danger">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="admin-empty">محصولی یافت نشد.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
