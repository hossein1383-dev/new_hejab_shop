@extends('layouts.app')

@section('title', 'تنظیم رمز عبور جدید')

@push('styles')
    @vite(['resources/css/pages/auth.css'])
@endpush

@section('content')
<div class="auth-page">
    <div class="auth-brand-panel">
        <h2>یک قدم تا بازگشت</h2>
        <p>رمز عبور جدید خود را تنظیم کنید.</p>
    </div>

    <div class="auth-card">
        <h1 class="auth-title">رمز عبور جدید</h1>

        <div class="auth-general-error" data-general-error></div>

        <form id="reset-password-form" novalidate>
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="auth-field">
                <label for="email">ایمیل</label>
                <input type="email" id="email" name="email" value="{{ $email }}" required autocomplete="email">
                <div class="auth-field-error" data-error-for="email" hidden></div>
            </div>
            <div class="auth-field">
                <label for="password">رمز عبور جدید</label>
                <input type="password" id="password" name="password" required autocomplete="new-password">
                <div class="auth-field-error" data-error-for="password" hidden></div>
            </div>
            <div class="auth-field">
                <label for="password_confirmation">تکرار رمز عبور جدید</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                <div class="auth-field-error" data-error-for="password_confirmation" hidden></div>
            </div>

            <button type="submit" class="auth-submit">تغییر رمز عبور</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/auth.js'])
@endpush
