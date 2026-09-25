@extends('layouts.app')

@section('title', 'ورود به حساب کاربری')

@push('styles')
    @vite(['resources/css/pages/auth.css'])
@endpush

@section('content')
<div class="auth-page">
    <div class="auth-image-panel">
        <img src="{{ asset('images/auth-illustration.png') }}" alt="خرید آسان از فروشگاه" loading="eager">
    </div>

    <div class="auth-form-panel">
        <div class="auth-card">
        <h1 class="auth-title">ورود / ثبت‌نام</h1>
        <p class="auth-subtitle">شماره موبایل خود را وارد کنید تا کد ورود برایتان پیامک شود.</p>

        <div class="auth-general-error" data-general-error></div>

        <form id="otp-request-form" novalidate>
            <div class="auth-field">
                <label for="phone">شماره موبایل</label>
                <input type="tel" id="phone" name="phone" required autocomplete="tel" placeholder="09121234567" dir="ltr">
                <div class="auth-field-error" data-error-for="phone" hidden></div>
            </div>

            <button type="submit" class="auth-submit">دریافت کد ورود</button>
        </form>

        <form id="otp-verify-form" novalidate hidden>
            <input type="hidden" name="phone">

            <div class="auth-field">
                <label for="code">کد پیامک‌شده</label>
                <input type="text" id="code" name="code" required inputmode="numeric" maxlength="5" placeholder="12345" dir="ltr">
                <div class="auth-field-error" data-error-for="code" hidden></div>
            </div>

            <button type="submit" class="auth-submit">ورود</button>

            <button type="button" class="auth-footer-link" data-otp-edit-phone style="background:none;border:none;width:100%;margin-top:var(--space-3);cursor:pointer;">
                ویرایش شماره موبایل
            </button>
            <button type="button" class="auth-footer-link" data-otp-resend style="background:none;border:none;width:100%;cursor:pointer;" disabled>
                ارسال دوباره کد (<span data-resend-timer>۶۰</span>)
            </button>
        </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/auth.js'])
@endpush
