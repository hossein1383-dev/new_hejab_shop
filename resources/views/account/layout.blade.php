@extends('layouts.store')

@section('body')
<div class="account-page">
    <nav class="account-nav">
        <a href="{{ route('account.profile') }}" class="@if(request()->routeIs('account.profile')) is-active @endif">اطلاعات حساب</a>
        <a href="{{ route('account.orders') }}" class="@if(request()->routeIs('account.orders*')) is-active @endif">سفارش‌های من</a>
        <a href="{{ route('account.addresses') }}" class="@if(request()->routeIs('account.addresses')) is-active @endif">آدرس‌های من</a>
        <a href="{{ url('/wishlist') }}" class="@if(request()->routeIs('wishlist.*')) is-active @endif">علاقه‌مندی‌ها</a>
        <a href="{{ url('/wallet') }}" class="@if(request()->routeIs('wallet.*')) is-active @endif">کیف پول</a>
        <button type="button" id="account-logout-btn">خروج از حساب</button>
    </nav>

    <div class="account-content">
        @if(session('order_success'))
            <div class="account-alert account-alert--success">{{ session('order_success') }}</div>
        @endif
        @if(session('order_error'))
            <div class="account-alert account-alert--error">{{ session('order_error') }}</div>
        @endif

        @yield('account_content')
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/header.js', 'resources/js/toast.js', 'resources/js/account.js'])
@endpush
