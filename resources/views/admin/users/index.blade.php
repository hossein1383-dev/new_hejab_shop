@extends('admin.layout')
@section('title', 'مدیریت کاربران')
@section('content')
<h1 class="admin-page-title">کاربران</h1>
<form method="get" class="admin-filter-bar">
    <input type="text" name="search" placeholder="جستجوی نام/ایمیل..." value="{{ request('search') }}">
    <button type="submit">جستجو</button>
</form>
<div class="admin-orders-desktop">
    <table class="admin-table">
        <thead><tr><th>نام</th><th>ایمیل</th><th>نقش‌ها</th><th>وضعیت</th><th></th></tr></thead>
        <tbody>
            @forelse($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->roles->pluck('name')->join('، ') ?: '—' }}</td>
                    <td><span class="admin-status-badge admin-status-badge--{{ $user->status === 'active' ? 'paid' : 'cancelled' }}">{{ $user->status }}</span></td>
                    <td><a href="{{ route('admin.users.edit', $user) }}" class="admin-table__link">ویرایش</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="admin-empty">کاربری یافت نشد.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="admin-orders-mobile">
    @forelse($users as $user)
        <div class="admin-order-card">
            <div class="admin-order-card__row"><strong>{{ $user->name }}</strong><span class="admin-status-badge admin-status-badge--{{ $user->status === 'active' ? 'paid' : 'cancelled' }}">{{ $user->status }}</span></div>
            <div class="admin-order-card__row admin-order-card__row--muted"><span>{{ $user->email }}</span></div>
            <div class="admin-order-card__row"><span>{{ $user->roles->pluck('name')->join('، ') ?: 'بدون نقش' }}</span></div>
            <div class="admin-order-card__row"><a href="{{ route('admin.users.edit', $user) }}" class="admin-table__link">ویرایش</a></div>
        </div>
    @empty
        <p class="admin-empty">کاربری یافت نشد.</p>
    @endforelse
</div>
<div class="admin-pagination">{{ $users->links() }}</div>
@endsection
