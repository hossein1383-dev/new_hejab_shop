@extends('admin.layout')
@section('title', $post->exists ? 'ویرایش مطلب' : 'مطلب جدید')
@section('content')
<a href="{{ route('admin.blog.index') }}" class="admin-back-link">← بازگشت</a>
<h1 class="admin-page-title">{{ $post->exists ? 'ویرایش مطلب' : 'مطلب جدید' }}</h1>
@if($errors->any())
    <div class="admin-alert admin-alert--error"><ul style="margin:0;padding-right:18px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif
<form method="POST" action="{{ $post->exists ? route('admin.blog.update', $post) : route('admin.blog.store') }}" enctype="multipart/form-data" class="admin-form">
    @csrf
    @if($post->exists) @method('PUT') @endif
    <div class="admin-form__field">
        <label>عنوان</label>
        <input type="text" name="title" value="{{ old('title', $post->title) }}" required>
    </div>
    <div class="admin-form__row">
        <div class="admin-form__field">
            <label>دسته‌بندی (اختیاری)</label>
            <select name="blog_category_id">
                <option value="">— بدون دسته —</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('blog_category_id', $post->blog_category_id) == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="admin-form__field">
            <label>وضعیت</label>
            <select name="status">
                <option value="draft" @selected(old('status', $post->status ?? 'draft') === 'draft')>پیش‌نویس</option>
                <option value="published" @selected(old('status', $post->status) === 'published')>منتشرشده</option>
            </select>
        </div>
    </div>
    <div class="admin-form__field">
        <label>خلاصه (اختیاری)</label>
        <textarea name="excerpt" rows="2">{{ old('excerpt', $post->excerpt) }}</textarea>
    </div>
    <div class="admin-form__field">
        <label>محتوا</label>
        <textarea name="content" rows="10">{{ old('content', $post->content) }}</textarea>
    </div>
    <div class="admin-form__field">
        <label>تصویر شاخص (اختیاری)</label>
        <input type="file" name="featured_image" accept="image/*">
        @if($post->featured_image)
            <img src="{{ asset('storage/' . $post->featured_image) }}" alt="" class="admin-form__preview-image">
        @endif
    </div>
    <button type="submit" class="admin-btn-primary">{{ $post->exists ? 'ذخیره' : 'ایجاد' }}</button>
</form>
@endsection
