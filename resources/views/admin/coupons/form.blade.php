@extends('admin.layout')

@section('title', $coupon->exists ? 'ویرایش کد تخفیف' : 'کد تخفیف جدید')

@section('content')
<a href="{{ route('admin.coupons.index') }}" class="admin-back-link">← بازگشت به لیست</a>
<h1 class="admin-page-title">{{ $coupon->exists ? 'ویرایش کد تخفیف' : 'کد تخفیف جدید' }}</h1>

@if($errors->any())
    <div class="admin-alert admin-alert--error">
        <ul style="margin:0; padding-right: 18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST"
      action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}"
      class="admin-form">
    @csrf
    @if($coupon->exists) @method('PUT') @endif

    <div class="admin-form__row">
        <div class="admin-form__field">
            <label>کد تخفیف</label>
            <input type="text" name="code" value="{{ old('code', $coupon->code) }}" required style="text-transform: uppercase;">
        </div>
        <div class="admin-form__field">
            <label>وضعیت</label>
            <select name="status">
                <option value="active" @selected(old('status', $coupon->status ?? 'active') === 'active')>فعال</option>
                <option value="inactive" @selected(old('status', $coupon->status) === 'inactive')>غیرفعال</option>
            </select>
        </div>
    </div>

    <div class="admin-form__row">
        <div class="admin-form__field">
            <label>نوع تخفیف</label>
            <select name="type">
                <option value="percentage" @selected(old('type', $coupon->type ?? 'percentage') === 'percentage')>درصدی</option>
                <option value="fixed" @selected(old('type', $coupon->type) === 'fixed')>مبلغ ثابت</option>
            </select>
        </div>
        <div class="admin-form__field">
            <label>مقدار (درصد یا تومان)</label>
            <input type="number" name="value" value="{{ old('value', $coupon->value) }}" required>
        </div>
    </div>

    <div class="admin-form__row">
        <div class="admin-form__field">
            <label>حداقل مبلغ سفارش (تومان)</label>
            <input type="number" name="min_order_amount" value="{{ old('min_order_amount', $coupon->min_order_amount ?? 0) }}">
        </div>
        <div class="admin-form__field">
            <label>سقف تخفیف — فقط برای نوع درصدی (اختیاری)</label>
            <input type="number" name="max_discount_amount" value="{{ old('max_discount_amount', $coupon->max_discount_amount) }}">
        </div>
    </div>

    <div class="admin-form__row">
        <div class="admin-form__field">
            <label>تاریخ شروع (اختیاری)</label>
            <input type="date" name="starts_at" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d')) }}">
        </div>
        <div class="admin-form__field">
            <label>تاریخ پایان (اختیاری)</label>
            <input type="date" name="ends_at" value="{{ old('ends_at', $coupon->ends_at?->format('Y-m-d')) }}">
        </div>
    </div>

    <div class="admin-form__row">
        <div class="admin-form__field">
            <label>سقف تعداد کل استفاده (اختیاری)</label>
            <input type="number" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}">
        </div>
        <div class="admin-form__field">
            <label>سقف استفاده هر کاربر (اختیاری)</label>
            <input type="number" name="per_user_limit" value="{{ old('per_user_limit', $coupon->per_user_limit) }}">
        </div>
    </div>

    <button type="submit" class="admin-btn-primary">{{ $coupon->exists ? 'ذخیره تغییرات' : 'ایجاد کد تخفیف' }}</button>
</form>
@endsection
