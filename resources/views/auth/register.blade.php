@extends('layouts.app')

@section('title', 'ثبت‌نام')

@push('styles')
    @vite(['resources/css/pages/auth.css'])
@endpush

@section('content')
<div class="auth-page">
    <div class="auth-brand-panel">
        <h2>عضویت در فروشگاه ما</h2>
        <p>با ساخت حساب کاربری، سفارش‌هایتان را پیگیری کنید و از تخفیف‌های ویژه اعضا بهره‌مند شوید.</p>
    </div>

    <div class="auth-card">
        <h1 class="auth-title">ساخت حساب کاربری</h1>
        <p class="auth-subtitle">فقط چند قدم تا شروع خرید فاصله دارید.</p>

        <div class="auth-general-error" data-general-error></div>

        <form id="register-form" novalidate>
            <div class="auth-field">
                <label for="name">نام و نام‌خانوادگی</label>
                <input type="text" id="name" name="name" required autocomplete="name">
                <div class="auth-field-error" data-error-for="name" hidden></div>
            </div>

            <div class="auth-field">
                <label for="email">ایمیل</label>
                <input type="email" id="email" name="email" required autocomplete="email">
                <div class="auth-field-error" data-error-for="email" hidden></div>
            </div>

            <div class="auth-field">
                <label for="phone">شماره موبایل (اختیاری)</label>
                <input type="tel" id="phone" name="phone" autocomplete="tel">
                <div class="auth-field-error" data-error-for="phone" hidden></div>
            </div>

            <div class="auth-field">
                <label for="password">رمز عبور</label>
                <input type="password" id="password" name="password" required autocomplete="new-password">
                <div class="auth-field-error" data-error-for="password" hidden></div>
            </div>

            <div class="auth-field">
                <label for="password_confirmation">تکرار رمز عبور</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                <div class="auth-field-error" data-error-for="password_confirmation" hidden></div>
            </div>

            <button type="submit" class="auth-submit">ثبت‌نام</button>
        </form>

        <p class="auth-footer-link">
            قبلاً ثبت‌نام کرده‌اید؟ <a href="{{ url('/login') }}">وارد شوید</a>
        </p>
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/auth.js'])
@endpush
