@extends('account.layout')

@section('title', 'اطلاعات حساب کاربری')

@push('styles')
    @vite(['resources/css/components/header.css', 'resources/css/pages/account.css'])
@endpush

@section('account_content')
<h1 class="account-page__title">اطلاعات حساب</h1>

<form method="POST" action="{{ route('account.profile.update') }}" class="account-form">
    @csrf
    @method('PUT')

    <div class="account-field">
        <label>شماره موبایل</label>
        <input type="text" value="{{ $user->phone }}" disabled dir="ltr">
        <span class="account-field__hint">شماره موبایل قابل تغییر نیست.</span>
    </div>

    <div class="account-field">
        <label for="name">نام و نام‌خانوادگی</label>
        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
        @error('name')<span class="account-field__error">{{ $message }}</span>@enderror
    </div>

    <div class="account-field">
        <label for="email">ایمیل (اختیاری)</label>
        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" dir="ltr">
        @error('email')<span class="account-field__error">{{ $message }}</span>@enderror
    </div>

    <button type="submit" class="account-btn-primary">ذخیره تغییرات</button>
</form>
@endsection
