@extends('admin.layout')
@section('title', 'ویرایش کاربر')
@section('content')
<a href="{{ route('admin.users.index') }}" class="admin-back-link">← بازگشت</a>
<h1 class="admin-page-title">{{ $user->name }}</h1>
<form method="POST" action="{{ route('admin.users.update', $user) }}" class="admin-form">
    @csrf @method('PUT')
    <div class="admin-form__field">
        <label>وضعیت حساب</label>
        <select name="status">
            <option value="active" @selected($user->status === 'active')>فعال</option>
            <option value="inactive" @selected($user->status === 'inactive')>غیرفعال</option>
            <option value="banned" @selected($user->status === 'banned')>مسدود</option>
        </select>
    </div>
    <div class="admin-form__field">
        <label>نقش‌ها</label>
        @foreach($roles as $role)
            <label style="display:flex; align-items:center; gap:8px; padding: 6px 0;">
                <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked($user->roles->contains($role->id))>
                {{ $role->name }}
            </label>
        @endforeach
    </div>
    <button type="submit" class="admin-btn-primary">ذخیره تغییرات</button>
</form>
@endsection
