@extends('account.layout')

@section('title', 'سفارش‌های من')

@push('styles')
    @vite(['resources/css/components/header.css', 'resources/css/pages/account.css'])
@endpush

@section('account_content')
<h1 class="account-page__title">سفارش‌های من</h1>

@forelse($orders as $order)
    <a href="{{ route('account.orders.show', $order) }}" class="account-order-card">
        <div class="account-order-card__row">
            <strong>{{ $order->productNamesSummary() }}</strong>
            <span class="account-status-badge account-status-badge--{{ $order->status }}">{{ $order->statusLabel() }}</span>
        </div>
        <div class="account-order-card__row account-order-card__row--muted">
            <span>{{ \App\Support\JalaliDate::format($order->created_at, 'Y/m/d') }}</span>
            <span>{{ number_format($order->total) }} تومان</span>
        </div>
    </a>
@empty
    <div class="account-empty">
        <p>هنوز سفارشی ثبت نکرده‌اید.</p>
        <a href="{{ url('/products') }}">مشاهده محصولات</a>
    </div>
@endforelse

<div class="account-pagination">{{ $orders->links() }}</div>
@endsection
