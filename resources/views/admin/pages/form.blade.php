@extends('admin.layout')
@section('title', $page->exists ? 'ویرایش صفحه' : 'صفحه جدید')
@section('content')
<a href="{{ route('admin.pages.index') }}" class="admin-back-link">← بازگشت</a>
<h1 class="admin-page-title">{{ $page->exists ? 'ویرایش صفحه' : 'صفحه جدید' }}</h1>
@if($errors->any())
    <div class="admin-alert admin-alert--error"><ul style="margin:0;padding-right:18px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif
<form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}" class="admin-form">
    @csrf
    @if($page->exists) @method('PUT') @endif
    <div class="admin-form__field">
        <label>عنوان</label>
        <input type="text" name="title" value="{{ old('title', $page->title) }}" required>
    </div>
    <div class="admin-form__field">
        <label>Slug (اختیاری — خودکار از عنوان ساخته می‌شود)</label>
        <input type="text" name="slug" value="{{ old('slug', $page->slug) }}">
    </div>
    <div class="admin-form__field">
        <label>محتوا</label>
        <textarea name="content" rows="10">{{ old('content', $page->content) }}</textarea>
    </div>
    <div class="admin-form__field">
        <label>وضعیت</label>
        <select name="status">
            <option value="active" @selected(old('status', $page->status ?? 'active') === 'active')>فعال</option>
            <option value="inactive" @selected(old('status', $page->status) === 'inactive')>غیرفعال</option>
        </select>
    </div>
    <button type="submit" class="admin-btn-primary">{{ $page->exists ? 'ذخیره' : 'ایجاد' }}</button>
</form>
@endsection
