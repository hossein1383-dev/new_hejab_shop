@extends('admin.layout')
@section('title', 'مدیریت نقش‌ها')
@section('content')
<h1 class="admin-page-title">نقش‌ها</h1>
<div class="admin-orders-desktop">
    <table class="admin-table">
        <thead><tr><th>نام</th><th>تعداد کاربر</th><th></th></tr></thead>
        <tbody>
            @foreach($roles as $role)
                <tr>
                    <td>{{ $role->name }}</td>
                    <td>{{ $role->users_count }}</td>
                    <td>
                        @if($role->slug === 'super-admin')
                            <span class="admin-empty" style="padding:0;">غیرقابل ویرایش</span>
                        @else
                            <a href="{{ route('admin.roles.edit', $role) }}" class="admin-table__link">ویرایش دسترسی‌ها</a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="admin-orders-mobile">
    @foreach($roles as $role)
        <div class="admin-order-card">
            <div class="admin-order-card__row"><strong>{{ $role->name }}</strong><span>{{ $role->users_count }} کاربر</span></div>
            <div class="admin-order-card__row">
                @if($role->slug === 'super-admin')
                    <span class="admin-empty" style="padding:0;">غیرقابل ویرایش</span>
                @else
                    <a href="{{ route('admin.roles.edit', $role) }}" class="admin-table__link">ویرایش دسترسی‌ها</a>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endsection
