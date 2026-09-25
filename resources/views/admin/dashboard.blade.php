@extends('admin.layout')

@section('title', 'داشبورد مدیریت')

@section('content')
<h1 class="admin-page-title">داشبورد</h1>

<div class="admin-stats-grid">
    <a href="{{ route('admin.orders.index', ['date' => 'today']) }}" class="admin-stat-card admin-stat-card--link">
        <span class="admin-stat-card__label">فروش امروز</span>
        <span class="admin-stat-card__value">{{ number_format($stats['today_sales']) }} تومان</span>
    </a>
    <a href="{{ route('admin.reports.index') }}" class="admin-stat-card admin-stat-card--link">
        <span class="admin-stat-card__label">فروش این ماه</span>
        <span class="admin-stat-card__value">{{ number_format($stats['monthly_sales']) }} تومان</span>
    </a>
    <div class="admin-stat-card">
        <span class="admin-stat-card__label">تعداد سفارش‌ها</span>
        <span class="admin-stat-card__value">{{ number_format($stats['orders_count']) }}</span>
    </div>
    <div class="admin-stat-card @if($stats['pending_orders_count'] > 0) is-warning @endif">
        <span class="admin-stat-card__label">در انتظار پرداخت</span>
        <span class="admin-stat-card__value">{{ number_format($stats['pending_orders_count']) }}</span>
    </div>
    <a href="{{ route('admin.users.index') }}" class="admin-stat-card admin-stat-card--link">
        <span class="admin-stat-card__label">تعداد مشتریان</span>
        <span class="admin-stat-card__value">{{ number_format($stats['customers_count']) }}</span>
    </a>
    <div class="admin-stat-card">
        <span class="admin-stat-card__label">تعداد محصولات</span>
        <span class="admin-stat-card__value">{{ number_format($stats['products_count']) }}</span>
    </div>
    <a href="{{ route('admin.inventory.index', ['low_stock' => 1]) }}" class="admin-stat-card admin-stat-card--link @if($stats['low_stock_count'] > 0) is-warning @endif">
        <span class="admin-stat-card__label">موجودی کم (≤۵)</span>
        <span class="admin-stat-card__value">{{ number_format($stats['low_stock_count']) }}</span>
    </a>
    <a href="{{ route('admin.reviews.index') }}" class="admin-stat-card admin-stat-card--link @if($stats['pending_reviews_count'] > 0) is-warning @endif">
        <span class="admin-stat-card__label">نظرات در انتظار تایید</span>
        <span class="admin-stat-card__value">{{ number_format($stats['pending_reviews_count']) }}</span>
    </a>
</div>

<h2 class="admin-section-title">آخرین سفارش‌ها</h2>
@include('components.admin.orders-table-mobile', ['orders' => $recentOrders])
@include('components.admin.orders-table-desktop', ['orders' => $recentOrders])
@endsection
