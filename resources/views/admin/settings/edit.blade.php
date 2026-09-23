@extends('admin.layout')

@section('title', 'تنظیمات سایت')

@section('content')
<h1 class="admin-page-title">تنظیمات سایت</h1>

<form method="POST" action="{{ route('admin.settings.update') }}" class="admin-form">
    @csrf
    @method('PUT')

    <div class="admin-form__field">
        <label>نام سایت</label>
        <input type="text" name="site_name" value="{{ old('site_name', $settings['site_name'] ?? '') }}">
    </div>
    <div class="admin-form__field">
        <label>توضیح کوتاه سایت</label>
        <input type="text" name="site_description" value="{{ old('site_description', $settings['site_description'] ?? '') }}">
    </div>
    <div class="admin-form__row">
        <div class="admin-form__field">
            <label>ایمیل پشتیبانی</label>
            <input type="email" name="contact_email" value="{{ old('contact_email', $settings['contact_email'] ?? '') }}">
        </div>
        <div class="admin-form__field">
            <label>تلفن پشتیبانی</label>
            <input type="text" name="contact_phone" value="{{ old('contact_phone', $settings['contact_phone'] ?? '') }}">
        </div>
    </div>
    <div class="admin-form__field">
        <label>آدرس</label>
        <textarea name="contact_address" rows="2">{{ old('contact_address', $settings['contact_address'] ?? '') }}</textarea>
    </div>

    <h2 style="font-size:1rem; margin: var(--space-6) 0 var(--space-3);">شبکه‌های اجتماعی (برای نمایش در فوتر)</h2>
    <div class="admin-form__row">
        <div class="admin-form__field">
            <label>لینک اینستاگرام</label>
            <input type="url" name="instagram_url" value="{{ old('instagram_url', $settings['instagram_url'] ?? '') }}" dir="ltr" placeholder="https://instagram.com/...">
        </div>
        <div class="admin-form__field">
            <label>لینک تلگرام</label>
            <input type="url" name="telegram_url" value="{{ old('telegram_url', $settings['telegram_url'] ?? '') }}" dir="ltr" placeholder="https://t.me/...">
        </div>
    </div>
    <div class="admin-form__field">
        <label>لینک واتساپ</label>
        <input type="url" name="whatsapp_url" value="{{ old('whatsapp_url', $settings['whatsapp_url'] ?? '') }}" dir="ltr" placeholder="https://wa.me/...">
    </div>
    <div class="admin-form__field">
        <label>لینک ایتا</label>
        <input type="url" name="eitaa_url" value="{{ old('eitaa_url', $settings['eitaa_url'] ?? '') }}" dir="ltr" placeholder="https://eitaa.com/...">
    </div>

    <h2 style="font-size:1rem; margin: var(--space-6) 0 var(--space-3);">روزها و ساعت کاری (برای نمایش در فوتر)</h2>
    <div class="admin-form__row">
        <div class="admin-form__field">
            <label>روزهای کاری</label>
            <input type="text" name="working_days" value="{{ old('working_days', $settings['working_days'] ?? '') }}" placeholder="مثلاً: شنبه تا پنجشنبه">
        </div>
        <div class="admin-form__field">
            <label>ساعت کاری</label>
            <input type="text" name="working_hours" value="{{ old('working_hours', $settings['working_hours'] ?? '') }}" placeholder="مثلاً: ۹ صبح تا ۹ شب">
        </div>
    </div>

    <h2 style="font-size:1rem; margin: var(--space-6) 0 var(--space-3);">نماد اعتماد الکترونیکی (اینماد)</h2>
    <div class="admin-form__row">
        <div class="admin-form__field">
            <label>شناسه اینماد (Id)</label>
            <input type="text" name="enamad_id" value="{{ old('enamad_id', $settings['enamad_id'] ?? '') }}" dir="ltr" placeholder="مثلاً: 6940282">
        </div>
        <div class="admin-form__field">
            <label>کد اینماد (Code)</label>
            <input type="text" name="enamad_code" value="{{ old('enamad_code', $settings['enamad_code'] ?? '') }}" dir="ltr" placeholder="کد یکتا از پنل اینماد">
        </div>
    </div>
    <p style="font-size:0.8rem; color:var(--text-muted); margin-top:-8px;">
        این دو مقدار را از کد Embed که پنل اینماد به شما داده برمی‌دارید — همان اعدادی که در آدرس‌های id= و Code= در آن کد هست.
    </p>

    <h2 style="font-size:1rem; margin: var(--space-6) 0 var(--space-3);">اطلاعات فرستنده برای هیروپست (الزامی برای ثبت مرسوله)</h2>
    <div class="admin-form__row">
        <div class="admin-form__field">
            <label>نام فرستنده (نام فروشگاه/مسئول)</label>
            <input type="text" name="heropost_sender_name" value="{{ old('heropost_sender_name', $settings['heropost_sender_name'] ?? '') }}">
        </div>
        <div class="admin-form__field">
            <label>موبایل فرستنده</label>
            <input type="text" name="heropost_sender_mobile" value="{{ old('heropost_sender_mobile', $settings['heropost_sender_mobile'] ?? '') }}" dir="ltr">
        </div>
    </div>
    <div class="admin-form__row">
        <div class="admin-form__field">
            <label>آدرس کامل فرستنده (محل تحویل بسته به پیک)</label>
            <input type="text" name="heropost_sender_address" value="{{ old('heropost_sender_address', $settings['heropost_sender_address'] ?? '') }}">
        </div>
        <div class="admin-form__field">
            <label>شناسه شهر هیروپست فرستنده</label>
            <input type="number" name="heropost_sender_city_id" value="{{ old('heropost_sender_city_id', $settings['heropost_sender_city_id'] ?? '') }}" dir="ltr" placeholder="مثلاً همان شناسه شهر خودتان">
        </div>
    </div>

    <button type="submit" class="admin-btn-primary">ذخیره تنظیمات</button>
</form>
@endsection
