@extends('admin.layout')
@section('title', 'گزارش‌ها')
@php
    $maxSale = $dailySales->max('total') ?: 1;
    $barWidth = 60;
    $gap = 20;
    $chartHeight = 160;
@endphp
@section('content')
<h1 class="admin-page-title">گزارش‌ها</h1>

<div class="admin-card">
    <h2>فروش ۷ روز اخیر</h2>
    <svg viewBox="0 0 {{ ($barWidth + $gap) * 7 }} {{ $chartHeight + 30 }}" style="width:100%; max-width:600px; height:auto;">
        @foreach($dailySales as $i => $day)
            @php $barHeight = $maxSale > 0 ? ($day['total'] / $maxSale) * $chartHeight : 0; @endphp
            <rect x="{{ $i * ($barWidth + $gap) }}" y="{{ $chartHeight - $barHeight }}" width="{{ $barWidth }}" height="{{ $barHeight }}" fill="#2563EB" rx="4"></rect>
            <text x="{{ $i * ($barWidth + $gap) + $barWidth / 2 }}" y="{{ $chartHeight + 20 }}" text-anchor="middle" font-size="12" fill="#6B7280">{{ $day['label'] }}</text>
        @endforeach
    </svg>
</div>

<div class="admin-order-detail__grid">
    <div class="admin-card">
        <h2>پرفروش‌ترین دسته‌بندی‌ها</h2>
        @forelse($topCategories as $category)
            <div class="admin-order-item">
                <span>{{ $category->name }}</span>
                <span>{{ number_format($category->total) }} تومان</span>
            </div>
        @empty
            <p class="admin-empty">داده‌ای موجود نیست.</p>
        @endforelse
    </div>

    <div class="admin-card">
        <h2>پرفروش‌ترین محصولات</h2>
        @forelse($bestSellers as $product)
            <div class="admin-order-item">
                <span>{{ $product->name }}</span>
                <span>{{ (int) $product->sold_quantity }} فروش</span>
            </div>
        @empty
            <p class="admin-empty">داده‌ای موجود نیست.</p>
        @endforelse
    </div>
</div>
@endsection
