@extends('account.layout')

@section('title', 'سفارش ' . $order->order_number)

@push('styles')
    @vite(['resources/css/components/header.css', 'resources/css/pages/account.css'])
@endpush

@section('account_content')
<a href="{{ route('account.orders') }}" class="account-back-link">← بازگشت به سفارش‌ها</a>

<div class="account-order-header">
    <h1 class="account-page__title">سفارش {{ $order->order_number }}</h1>
    <span class="account-status-badge account-status-badge--{{ $order->status }}">{{ $order->statusLabel() }}</span>
</div>

<div class="account-card">
    <h2>اقلام سفارش</h2>
    @foreach($order->items as $item)
        <div class="account-order-item">
            <span>{{ $item->product_name }} × {{ $item->quantity }}</span>
            <span>{{ number_format($item->line_total) }} تومان</span>
        </div>
    @endforeach
    <div class="account-order-item account-order-item--total">
        <span>هزینه ارسال</span>
        <span>{{ number_format($order->shipping_cost) }} تومان</span>
    </div>
    @if($order->discount_total > 0)
        <div class="account-order-item">
            <span>تخفیف</span>
            <span>−{{ number_format($order->discount_total) }} تومان</span>
        </div>
    @endif
    <div class="account-order-item account-order-item--total">
        <span>جمع کل</span>
        <span>{{ number_format($order->total) }} تومان</span>
    </div>
</div>

<div class="account-card">
    <h2>آدرس ارسال</h2>
    <p>{{ $order->shipping_name }} — {{ $order->shipping_phone }}</p>
    <p>{{ $order->shipping_province }}، {{ $order->shipping_city }}، {{ $order->shipping_address_line }}</p>
</div>

@if($order->shipment && $order->shipment->tracking_number)
    <div class="account-card">
        <h2>پیگیری مرسوله</h2>
        <p>کد رهگیری: {{ $order->shipment->tracking_number }}</p>
    </div>
@endif

@if($order->heropost_tracking_code)
    <div class="account-card">
        <h2>کد رهگیری پستی</h2>
        <p dir="ltr" style="font-weight:var(--font-weight-bold); font-size:1.1rem;">{{ $order->heropost_tracking_code }}</p>
    </div>
@endif

<div class="account-card">
    <h2>وضعیت سفارش</h2>
    <ul class="account-timeline">
        <li>
            <strong>{{ $order->statusLabel() }}</strong>
            <span>{{ \App\Support\JalaliDate::format($order->updated_at) }}</span>
        </li>
    </ul>
</div>
@endsection
