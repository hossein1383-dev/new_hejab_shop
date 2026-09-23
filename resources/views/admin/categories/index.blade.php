@extends('admin.layout')

@section('title', 'مدیریت دسته‌بندی‌ها')

@section('content')
<div class="admin-page-header">
    <h1 class="admin-page-title">دسته‌بندی‌ها</h1>
    <a href="{{ route('admin.categories.create') }}" class="admin-btn-primary">+ دسته‌بندی جدید</a>
</div>

<form method="get" class="admin-filter-bar">
    <input type="text" name="search" placeholder="جستجوی نام..." value="{{ request('search') }}">
    <button type="submit">جستجو</button>
</form>

@include('components.admin.categories-table-mobile')
@include('components.admin.categories-table-desktop')

<div class="admin-pagination">
    {{ $categories->onEachSide(1)->links() }}
</div>
@endsection
