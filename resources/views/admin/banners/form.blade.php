@extends('admin.layout')

@section('title', $banner->exists ? 'ویرایش بنر' : 'بنر جدید')

@section('content')
<a href="{{ route('admin.banners.index') }}" class="admin-back-link">← بازگشت به لیست</a>
<h1 class="admin-page-title">{{ $banner->exists ? 'ویرایش بنر' : 'بنر جدید' }}</h1>

@if($errors->any())
    <div class="admin-alert admin-alert--error">
        <ul style="margin:0; padding-right: 18px;">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $banner->exists ? route('admin.banners.update', $banner) : route('admin.banners.store') }}"
      enctype="multipart/form-data" class="admin-form">
    @csrf
    @if($banner->exists) @method('PUT') @endif

    <div class="admin-form__field">
        <label>عنوان</label>
        <input type="text" name="title" value="{{ old('title', $banner->title) }}" required>
    </div>
    <div class="admin-form__field">
        <label>تصویر دسکتاپ (اختیاری — ولی حداقل یکی از این دو تصویر لازم است)</label>
        <p style="font-size:0.8rem; color:var(--text-muted); margin: 0 0 var(--space-2);">نسبت پیشنهادی: عریض (مثلاً ۲۱:۹ یا ۱۶:۵) — برای نمایش لپ‌تاپ/دسکتاپ.</p>
        <input type="file" name="image" accept="image/*">
        @if($banner->image)
            <img src="{{ asset('storage/' . $banner->image) }}" alt="" class="admin-form__preview-image" style="width:200px;height:auto;">
        @endif
    </div>
    <div class="admin-form__field">
        <label>تصویر موبایل (اختیاری — ولی حداقل یکی از این دو تصویر لازم است)</label>
        <p style="font-size:0.8rem; color:var(--text-muted); margin: 0 0 var(--space-2);">
            نسبت پیشنهادی: باریک و بلند (مثلاً ۴:۵ یا ۱:۱). چون نسبت صفحه گوشی و لپ‌تاپ خیلی فرق دارد، یک عکس روی هر دو خوب درنمی‌آید — اگر خالی بگذارید، همان تصویر دسکتاپ (احتمالاً بریده‌شده و نامناسب) در موبایل هم نشان داده می‌شود.
        </p>
        <input type="file" name="image_mobile" accept="image/*">
        @if($banner->image_mobile)
            <img src="{{ asset('storage/' . $banner->image_mobile) }}" alt="" class="admin-form__preview-image" style="width:120px;height:auto;">
        @endif
    </div>
    <div class="admin-form__field">
        <label>لینک مقصد (اختیاری)</label>
        <input type="url" name="link_url" value="{{ old('link_url', $banner->link_url) }}">
    </div>
    <div class="admin-form__row">
        <div class="admin-form__field">
            <label>ترتیب نمایش</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $banner->sort_order ?? 0) }}">
        </div>
        <div class="admin-form__field">
            <label>وضعیت</label>
            <select name="status">
                <option value="active" @selected(old('status', $banner->status ?? 'active') === 'active')>فعال</option>
                <option value="inactive" @selected(old('status', $banner->status) === 'inactive')>غیرفعال</option>
            </select>
        </div>
    </div>
    <input type="hidden" name="position" value="home_hero">

    <button type="submit" class="admin-btn-primary">{{ $banner->exists ? 'ذخیره تغییرات' : 'ایجاد بنر' }}</button>
</form>
@endsection
