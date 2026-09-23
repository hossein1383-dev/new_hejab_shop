@extends('layouts.app')

@section('title', 'فراموشی رمز عبور')

@push('styles')
    @vite(['resources/css/pages/auth.css'])
@endpush

@section('content')
<div class="auth-page">
    <div class="auth-brand-panel">
        <h2>بازیابی دسترسی به حساب</h2>
        <p>ایمیل خود را وارد کنید تا لینک بازیابی رمز عبور برایتان ارسال شود.</p>
    </div>

    <div class="auth-card">
        <h1 class="auth-title">فراموشی رمز عبور</h1>
        <p class="auth-subtitle">ایمیل حساب کاربری خود را وارد کنید.</p>

        <div class="auth-general-error" data-general-error></div>
        <div class="auth-general-error" data-general-success style="background: color-mix(in srgb, var(--success) 10%, transparent); color: var(--success);" hidden></div>

        <form id="forgot-password-form" novalidate>
            <div class="auth-field">
                <label for="email">ایمیل</label>
                <input type="email" id="email" name="email" required autocomplete="email">
                <div class="auth-field-error" data-error-for="email" hidden></div>
            </div>

            <button type="submit" class="auth-submit">ارسال لینک بازیابی</button>
        </form>

        <p class="auth-footer-link">
            <a href="{{ url('/login') }}">بازگشت به ورود</a>
        </p>
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/auth.js'])
@endpush
