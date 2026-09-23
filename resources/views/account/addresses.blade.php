@extends('account.layout')

@section('title', 'آدرس‌های من')

@push('styles')
    @vite(['resources/css/components/header.css', 'resources/css/pages/account.css'])
@endpush

@section('account_content')
<div class="account-page-header">
    <h1 class="account-page__title">آدرس‌های من</h1>
    <button type="button" class="account-btn-primary" data-toggle-address-form>+ آدرس جدید</button>
</div>

<div class="account-general-message" data-address-message hidden></div>

<form id="new-address-form" class="account-form" hidden>
    <div class="account-field-row">
        <div class="account-field">
            <label>عنوان (اختیاری)</label>
            <input type="text" name="title" placeholder="خانه، محل کار...">
        </div>
        <div class="account-field">
            <label>نام گیرنده</label>
            <input type="text" name="receiver_name" required>
        </div>
    </div>
    <div class="account-field-row">
        <div class="account-field">
            <label>شماره موبایل</label>
            <input type="tel" name="phone" required dir="ltr">
        </div>
        <div class="account-field">
            <label>کد پستی (اختیاری)</label>
            <input type="text" name="postal_code" dir="ltr">
        </div>
    </div>
    <div class="account-field-row">
        @if($heropostProvinces->isNotEmpty())
            <div class="account-field">
                <label>استان</label>
                <select name="province" id="address-province-select" required>
                    <option value="">— انتخاب کنید —</option>
                    @foreach($heropostProvinces as $province)
                        <option value="{{ $province }}">{{ $province }}</option>
                    @endforeach
                </select>
            </div>
            <div class="account-field">
                <label>شهر</label>
                <select name="city" id="address-city-select" required disabled>
                    <option value="">ابتدا استان را انتخاب کنید</option>
                </select>
                <input type="hidden" name="heropost_city_id" id="address-heropost-city-id">
            </div>
        @else
            {{-- لیست شهرهای هیروپست هنوز همگام‌سازی نشده — فرم قدیمی متنی --}}
            <div class="account-field">
                <label>استان</label>
                <input type="text" name="province" required>
            </div>
            <div class="account-field">
                <label>شهر</label>
                <input type="text" name="city" required>
            </div>
        @endif
    </div>
    <div class="account-field">
        <label>آدرس کامل</label>
        <textarea name="address_line" rows="2" required></textarea>
    </div>
    <label class="account-checkbox">
        <input type="checkbox" name="is_default"> تنظیم به‌عنوان آدرس پیش‌فرض
    </label>
    <button type="submit" class="account-btn-primary">ذخیره آدرس</button>
</form>

<div class="account-addresses-list" data-addresses-list>
    @forelse($addresses as $address)
        <div class="account-address-card" data-address-id="{{ $address->id }}">
            @if($address->is_default)
                <span class="account-address-card__badge">پیش‌فرض</span>
            @endif
            <strong>{{ $address->title ?? $address->receiver_name }}</strong>
            <p>{{ $address->receiver_name }} — {{ $address->phone }}</p>
            <p>{{ $address->province }}، {{ $address->city }}، {{ $address->address_line }}</p>
            <button type="button" class="account-table__link account-table__link--danger" data-delete-address="{{ $address->id }}">حذف</button>
        </div>
    @empty
        <div class="account-empty">
            <p>هنوز آدرسی ثبت نکرده‌اید.</p>
        </div>
    @endforelse
</div>
@endsection
