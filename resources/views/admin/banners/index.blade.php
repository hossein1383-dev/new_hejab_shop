@extends('admin.layout')

@section('title', 'مدیریت بنرها')

@section('content')
<div class="admin-page-header">
    <h1 class="admin-page-title">بنرهای صفحه اصلی</h1>
    <a href="{{ route('admin.banners.create') }}" class="admin-btn-primary">+ بنر جدید</a>
</div>

<div class="admin-orders-mobile">
    @forelse($banners as $banner)
        <div class="admin-order-card">
            <img src="{{ asset('storage/' . $banner->image) }}" alt="" style="width:100%; border-radius: var(--radius-input); margin-bottom: var(--space-2);">
            <div class="admin-order-card__row">
                <strong>{{ $banner->title }}</strong>
                <span class="admin-status-badge admin-status-badge--{{ $banner->status === 'active' ? 'paid' : 'cancelled' }}">{{ $banner->status }}</span>
            </div>
            <div class="admin-order-card__row">
                <a href="{{ route('admin.banners.edit', $banner) }}" class="admin-table__link">ویرایش</a>
                <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" data-confirm="حذف این بنر؟">
                    @csrf @method('DELETE')
                    <button type="submit" class="admin-table__link admin-table__link--danger">حذف</button>
                </form>
            </div>
        </div>
    @empty
        <p class="admin-empty">بنری یافت نشد.</p>
    @endforelse
</div>

<div class="admin-orders-desktop">
    <table class="admin-table">
        <thead><tr><th>تصویر</th><th>عنوان</th><th>ترتیب</th><th>وضعیت</th><th></th></tr></thead>
        <tbody>
            @forelse($banners as $banner)
                <tr>
                    <td><img src="{{ asset('storage/' . $banner->image) }}" alt="" style="width:80px; border-radius: var(--radius-input);"></td>
                    <td>{{ $banner->title }}</td>
                    <td>{{ $banner->sort_order }}</td>
                    <td><span class="admin-status-badge admin-status-badge--{{ $banner->status === 'active' ? 'paid' : 'cancelled' }}">{{ $banner->status }}</span></td>
                    <td>
                        <a href="{{ route('admin.banners.edit', $banner) }}" class="admin-table__link">ویرایش</a>
                        <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" style="display:inline" data-confirm="حذف این بنر؟">
                            @csrf @method('DELETE')
                            <button type="submit" class="admin-table__link admin-table__link--danger">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="admin-empty">بنری یافت نشد.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="admin-pagination">{{ $banners->links() }}</div>
@endsection
