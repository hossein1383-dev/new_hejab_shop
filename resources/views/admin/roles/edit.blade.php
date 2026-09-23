@extends('admin.layout')
@section('title', 'دسترسی‌های ' . $role->name)
@section('content')
<a href="{{ route('admin.roles.index') }}" class="admin-back-link">← بازگشت</a>
<h1 class="admin-page-title">دسترسی‌های نقش «{{ $role->name }}»</h1>
<form method="POST" action="{{ route('admin.roles.update', $role) }}" class="admin-form" style="max-width:960px;">
    @csrf @method('PUT')
    @foreach($permissionsByGroup as $group => $permissions)
        <div class="admin-form__field">
            <label>{{ $group }}</label>
            <div style="display:flex; flex-wrap:wrap; gap: 16px;">
                @foreach($permissions as $permission)
                    <label style="display:flex; align-items:center; gap:6px; font-weight:400;">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, $assignedIds))>
                        {{ $permission->slug }}
                    </label>
                @endforeach
            </div>
        </div>
    @endforeach
    <button type="submit" class="admin-btn-primary">ذخیره دسترسی‌ها</button>
</form>
@endsection
