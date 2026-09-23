@extends('admin.layout')
@section('title', 'صفحات ثابت')
@section('content')
<div class="admin-page-header">
    <h1 class="admin-page-title">صفحات ثابت</h1>
    <a href="{{ route('admin.pages.create') }}" class="admin-btn-primary">+ صفحه جدید</a>
</div>
<div class="admin-orders-desktop">
    <table class="admin-table">
        <thead><tr><th>عنوان</th><th>Slug</th><th>وضعیت</th><th></th></tr></thead>
        <tbody>
            @forelse($pages as $page)
                <tr>
                    <td>{{ $page->title }}</td>
                    <td>{{ $page->slug }}</td>
                    <td><span class="admin-status-badge admin-status-badge--{{ $page->status === 'active' ? 'paid' : 'cancelled' }}">{{ $page->status }}</span></td>
                    <td>
                        <a href="{{ route('admin.pages.edit', $page) }}" class="admin-table__link">ویرایش</a>
                        <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" style="display:inline" data-confirm="حذف؟">
                            @csrf @method('DELETE')
                            <button type="submit" class="admin-table__link admin-table__link--danger">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="admin-empty">صفحه‌ای یافت نشد.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="admin-orders-mobile">
    @forelse($pages as $page)
        <div class="admin-order-card">
            <div class="admin-order-card__row"><strong>{{ $page->title }}</strong><span class="admin-status-badge admin-status-badge--{{ $page->status === 'active' ? 'paid' : 'cancelled' }}">{{ $page->status }}</span></div>
            <div class="admin-order-card__row">
                <a href="{{ route('admin.pages.edit', $page) }}" class="admin-table__link">ویرایش</a>
                <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" data-confirm="حذف؟">
                    @csrf @method('DELETE')
                    <button type="submit" class="admin-table__link admin-table__link--danger">حذف</button>
                </form>
            </div>
        </div>
    @empty
        <p class="admin-empty">صفحه‌ای یافت نشد.</p>
    @endforelse
</div>
<div class="admin-pagination">{{ $pages->links() }}</div>
@endsection
