@extends('admin.layout')

@section('title', 'مدیریت محصولات')

@section('content')
<div class="admin-page-header">
    <h1 class="admin-page-title">محصولات</h1>
    <a href="{{ route('admin.products.create') }}" class="admin-btn-primary">+ محصول جدید</a>
</div>

<form method="get" class="admin-filter-bar">
    <input type="text" name="search" placeholder="جستجوی نام یا SKU..." value="{{ request('search') }}">
    <select name="status" data-auto-submit>
        <option value="">همه وضعیت‌ها</option>
        <option value="draft" @selected(request('status') === 'draft')>پیش‌نویس</option>
        <option value="active" @selected(request('status') === 'active')>فعال</option>
        <option value="inactive" @selected(request('status') === 'inactive')>غیرفعال</option>
    </select>
    <button type="submit">جستجو</button>
</form>

@include('components.admin.products-table-mobile')
@include('components.admin.products-table-desktop')

<div class="admin-pagination">
    {{ $products->onEachSide(1)->links() }}
</div>
@endsection
