<div class="admin-orders-desktop">
    <table class="admin-table">
        <thead>
            <tr>
                <th>نام</th>
                <th>والد</th>
                <th>ترتیب</th>
                <th>وضعیت</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                    <td>{{ $category->parent?->name ?? '—' }}</td>
                    <td>{{ $category->sort_order }}</td>
                    <td><span class="admin-status-badge admin-status-badge--{{ $category->status === 'active' ? 'paid' : 'cancelled' }}">{{ $category->status }}</span></td>
                    <td>
                        <a href="{{ route('admin.categories.edit', $category) }}" class="admin-table__link">ویرایش</a>
                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" style="display:inline" data-confirm="حذف این دسته‌بندی؟">
                            @csrf @method('DELETE')
                            <button type="submit" class="admin-table__link admin-table__link--danger">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="admin-empty">دسته‌بندی‌ای یافت نشد.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
