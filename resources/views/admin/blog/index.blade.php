@extends('admin.layout')
@section('title', 'وبلاگ')
@section('content')
<div class="admin-page-header">
    <h1 class="admin-page-title">مطالب وبلاگ</h1>
    <div style="display:flex; gap: var(--space-2);">
        <a href="{{ route('admin.blog.categories.index') }}" class="admin-table__link" style="align-self:center;">دسته‌بندی‌ها</a>
        <a href="{{ route('admin.blog.create') }}" class="admin-btn-primary">+ مطلب جدید</a>
    </div>
</div>
<div class="admin-orders-desktop">
    <table class="admin-table">
        <thead><tr><th>عنوان</th><th>دسته</th><th>وضعیت</th><th></th></tr></thead>
        <tbody>
            @forelse($posts as $post)
                <tr>
                    <td>{{ $post->title }}</td>
                    <td>{{ $post->category?->name ?? '—' }}</td>
                    <td><span class="admin-status-badge admin-status-badge--{{ $post->status === 'published' ? 'paid' : 'pending_payment' }}">{{ $post->status }}</span></td>
                    <td>
                        <a href="{{ route('admin.blog.edit', $post) }}" class="admin-table__link">ویرایش</a>
                        <form method="POST" action="{{ route('admin.blog.destroy', $post) }}" style="display:inline" data-confirm="حذف؟">
                            @csrf @method('DELETE')
                            <button type="submit" class="admin-table__link admin-table__link--danger">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="admin-empty">مطلبی یافت نشد.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="admin-orders-mobile">
    @forelse($posts as $post)
        <div class="admin-order-card">
            <div class="admin-order-card__row"><strong>{{ $post->title }}</strong><span class="admin-status-badge admin-status-badge--{{ $post->status === 'published' ? 'paid' : 'pending_payment' }}">{{ $post->status }}</span></div>
            <div class="admin-order-card__row">
                <a href="{{ route('admin.blog.edit', $post) }}" class="admin-table__link">ویرایش</a>
                <form method="POST" action="{{ route('admin.blog.destroy', $post) }}" data-confirm="حذف؟">
                    @csrf @method('DELETE')
                    <button type="submit" class="admin-table__link admin-table__link--danger">حذف</button>
                </form>
            </div>
        </div>
    @empty
        <p class="admin-empty">مطلبی یافت نشد.</p>
    @endforelse
</div>
<div class="admin-pagination">{{ $posts->links() }}</div>
@endsection
