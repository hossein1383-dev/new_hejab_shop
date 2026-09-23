@extends('admin.layout')

@section('title', 'مدیریت سفارش‌ها')

@section('content')
<h1 class="admin-page-title">سفارش‌ها</h1>

<form method="get" class="admin-filter-bar">
    <input type="text" name="search" placeholder="جستجوی شماره سفارش..." value="{{ request('search') }}">
    <select name="status" data-auto-submit>
        <option value="">همه وضعیت‌ها</option>
        @foreach(['pending_payment','paid','processing','preparing','shipped','delivered','cancelled','refunded','returned'] as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
        @endforeach
    </select>
    <button type="submit">جستجو</button>
</form>

@include('components.admin.orders-table-mobile', ['orders' => $orders])
@include('components.admin.orders-table-desktop', ['orders' => $orders])

<div class="admin-pagination">
    {{ $orders->onEachSide(1)->links() }}
</div>
@endsection
