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
            <input type="text" id="starts_at_jalali" class="jalali-datepicker" autocomplete="off" placeholder="مثلاً ۱۴۰۵/۰۷/۰۱"
                value="{{ old('starts_at') ? \App\Support\JalaliDate::format(old('starts_at'), 'Y/m/d') : ($coupon->starts_at ? \App\Support\JalaliDate::format($coupon->starts_at, 'Y/m/d') : '') }}">
            <input type="hidden" name="starts_at" id="starts_at" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d')) }}">
        </div>
        <div class="admin-form__field">
            <label>تاریخ پایان (اختیاری)</label>
            <input type="text" id="ends_at_jalali" class="jalali-datepicker" autocomplete="off" placeholder="مثلاً ۱۴۰۵/۰۸/۰۱"
                value="{{ old('ends_at') ? \App\Support\JalaliDate::format(old('ends_at'), 'Y/m/d') : ($coupon->ends_at ? \App\Support\JalaliDate::format($coupon->ends_at, 'Y/m/d') : '') }}">
            <input type="hidden" name="ends_at" id="ends_at" value="{{ old('ends_at', $coupon->ends_at?->format('Y-m-d')) }}">
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

@push('scripts')
<script>
/**
 * تبدیل تاریخ شمسی به میلادی — بخش ۵۶. الگوریتم استاندارد بدون نیاز به
 * کتابخانه خارجی (تا وابسته به CDN/شبکه هاست نباشد).
 */
function jalaliToGregorian(jy, jm, jd) {
    jy = parseInt(jy, 10) - 979;
    jm = parseInt(jm, 10) - 1;
    jd = parseInt(jd, 10) - 1;

    let jDayNo = 365 * jy + Math.floor(jy / 33) * 8 + Math.floor(((jy % 33) + 3) / 4);
    for (let i = 0; i < jm; ++i) jDayNo += [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29][i];
    jDayNo += jd;

    let gDayNo = jDayNo + 79;
    let gy = 1600 + 400 * Math.floor(gDayNo / 146097);
    gDayNo = gDayNo % 146097;

    let leap = true;
    if (gDayNo >= 36525) {
        gDayNo--;
        gy += 100 * Math.floor(gDayNo / 36524);
        gDayNo = gDayNo % 36524;
        if (gDayNo >= 365) gDayNo++; else leap = false;
    }

    gy += 4 * Math.floor(gDayNo / 1461);
    gDayNo %= 1461;

    if (gDayNo >= 366) {
        leap = false;
        gDayNo--;
        gy += Math.floor(gDayNo / 365);
        gDayNo = gDayNo % 365;
    }

    const gDaysInMonth = [31, leap && ((gy % 4 === 0 && gy % 100 !== 0) || gy % 400 === 0) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    let gm = 0;
    while (gm < 12 && gDayNo >= gDaysInMonth[gm]) {
        gDayNo -= gDaysInMonth[gm];
        gm++;
    }

    const pad = (n) => String(n).padStart(2, '0');
    return `${gy}-${pad(gm + 1)}-${pad(gDayNo + 1)}`;
}

function bindJalaliField(textId, hiddenId) {
    const textInput = document.getElementById(textId);
    const hiddenInput = document.getElementById(hiddenId);
    if (!textInput || !hiddenInput) return;

    textInput.addEventListener('change', () => {
        const value = textInput.value.trim();
        if (!value) {
            hiddenInput.value = '';
            return;
        }
        const parts = value.split(/[\/\-]/).map((p) => p.trim());
        if (parts.length !== 3) {
            window.showToast?.('فرمت تاریخ باید مثل ۱۴۰۵/۰۷/۰۱ باشد.', 'error');
            return;
        }
        hiddenInput.value = jalaliToGregorian(parts[0], parts[1], parts[2]);
    });
}

bindJalaliField('starts_at_jalali', 'starts_at');
bindJalaliField('ends_at_jalali', 'ends_at');
</script>
@endpush
@endsection
