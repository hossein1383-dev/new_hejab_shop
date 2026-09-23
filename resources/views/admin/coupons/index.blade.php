@extends('admin.layout')

@section('title', 'مدیریت کدهای تخفیف')

@section('content')
<div class="admin-page-header">
    <h1 class="admin-page-title">کدهای تخفیف</h1>
    <a href="{{ route('admin.coupons.create') }}" class="admin-btn-primary">+ کد جدید</a>
</div>

<form method="get" class="admin-filter-bar">
    <input type="text" name="search" placeholder="جستجوی کد..." value="{{ request('search') }}">
    <button type="submit">جستجو</button>
</form>

@include('components.admin.coupons-table-mobile')
@include('components.admin.coupons-table-desktop')

<div class="admin-pagination">
    {{ $coupons->onEachSide(1)->links() }}
</div>
@endsection
