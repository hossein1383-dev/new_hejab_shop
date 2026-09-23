@extends('admin.layout')

@section('title', $category->exists ? 'ویرایش دسته‌بندی' : 'دسته‌بندی جدید')

@section('content')
<a href="{{ route('admin.categories.index') }}" class="admin-back-link">← بازگشت به لیست</a>
<h1 class="admin-page-title">{{ $category->exists ? 'ویرایش دسته‌بندی' : 'دسته‌بندی جدید' }}</h1>

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
      action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
      enctype="multipart/form-data" class="admin-form">
    @csrf
    @if($category->exists) @method('PUT') @endif

    <div class="admin-form__row">
        <div class="admin-form__field">
            <label>نام دسته‌بندی</label>
            <input type="text" name="name" value="{{ old('name', $category->name) }}" required>
        </div>
        <div class="admin-form__field">
            <label>دسته والد (اختیاری)</label>
            <select name="parent_id">
                <option value="">— دسته اصلی —</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id) == $parent->id)>{{ $parent->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="admin-form__row">
        <div class="admin-form__field">
            <label>وضعیت</label>
            <select name="status">
                <option value="active" @selected(old('status', $category->status ?? 'active') === 'active')>فعال</option>
                <option value="inactive" @selected(old('status', $category->status) === 'inactive')>غیرفعال</option>
            </select>
        </div>
        <div class="admin-form__field">
            <label>ترتیب نمایش</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}">
        </div>
    </div>

    <div class="admin-form__field">
        <label>توضیحات</label>
        <textarea name="description" rows="3">{{ old('description', $category->description) }}</textarea>
    </div>

    <div class="admin-form__field">
        <label>تصویر</label>
        <input type="file" name="image" accept="image/*">
        @if($category->image)
            <img src="{{ asset('storage/' . $category->image) }}" alt="" class="admin-form__preview-image">
        @endif
    </div>

    <button type="submit" class="admin-btn-primary">{{ $category->exists ? 'ذخیره تغییرات' : 'ایجاد دسته‌بندی' }}</button>
</form>
@endsection
