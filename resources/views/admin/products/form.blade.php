@extends('admin.layout')

@section('title', $product->exists ? 'ویرایش محصول' : 'محصول جدید')

@section('content')
<a href="{{ route('admin.products.index') }}" class="admin-back-link">← بازگشت به لیست</a>
<h1 class="admin-page-title">{{ $product->exists ? 'ویرایش محصول' : 'محصول جدید' }}</h1>

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
      action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
      enctype="multipart/form-data" class="admin-form">
    @csrf
    @if($product->exists) @method('PUT') @endif

    <div class="admin-form__row">
        <div class="admin-form__field">
            <label>نام محصول</label>
            <input type="text" name="name" value="{{ old('name', $product->name) }}" required>
        </div>
        <div class="admin-form__field">
            <label>SKU</label>
            <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" required>
        </div>
    </div>

    <div class="admin-form__row">
        <div class="admin-form__field">
            <label>دسته‌بندی</label>
            <select name="category_id" required>
                <option value="">انتخاب کنید</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="admin-form__field">
            <label>برند (اختیاری)</label>
            <select name="brand_id">
                <option value="">— بدون برند —</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id) == $brand->id)>{{ $brand->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="admin-form__row">
        <div class="admin-form__field">
            <label>قیمت (تومان)</label>
            <input type="number" name="price" value="{{ old('price', $product->price) }}" required>
        </div>
        <div class="admin-form__field">
            <label>قیمت قبل از تخفیف (اختیاری)</label>
            <input type="number" name="compare_price" value="{{ old('compare_price', $product->compare_price) }}">
        </div>
    </div>

    <div class="admin-form__row">
        <div class="admin-form__field">
            <label>وزن (کیلوگرم — اختیاری)</label>
            <p style="font-size:0.8rem; color:var(--text-muted); margin: 0 0 var(--space-2);">
                برای محاسبه دقیق هزینه ارسال واقعی از هیروپست استفاده می‌شود. اگر خالی بماند، وزن پیش‌فرض ۳۰۰ گرم برای هر عدد در نظر گرفته می‌شود.
            </p>
            <input type="number" step="0.01" min="0" name="weight" value="{{ old('weight', $product->weight) }}" placeholder="مثلاً 0.6">
        </div>
        <div class="admin-form__field">
            <label>ابعاد (اختیاری)</label>
            <input type="text" name="dimensions" value="{{ old('dimensions', $product->dimensions) }}" placeholder="مثلاً 30x20x10 سانتی‌متر">
        </div>
    </div>

    <div class="admin-form__row">
        <div class="admin-form__field">
            <label>وضعیت</label>
            <select name="status">
                <option value="draft" @selected(old('status', $product->status ?? 'draft') === 'draft')>پیش‌نویس</option>
                <option value="active" @selected(old('status', $product->status) === 'active')>فعال</option>
                <option value="inactive" @selected(old('status', $product->status) === 'inactive')>غیرفعال</option>
            </select>
        </div>
        <div class="admin-form__field admin-form__field--checkboxes">
            <label><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured))> ویژه</label>
            <label><input type="checkbox" name="is_new" value="1" @checked(old('is_new', $product->is_new))> جدید</label>
            <label><input type="checkbox" name="is_bestseller" value="1" @checked(old('is_bestseller', $product->is_bestseller))> پرفروش</label>
        </div>
    </div>

    <div class="admin-form__field">
        <label>توضیح کوتاه</label>
        <input type="text" name="short_description" value="{{ old('short_description', $product->short_description) }}">
    </div>

    <div class="admin-form__field">
        <label>توضیحات کامل</label>
        <textarea name="description" rows="4">{{ old('description', $product->description) }}</textarea>
    </div>

    <div class="admin-form__field">
        <label>برچسب‌ها (با کاما جدا کنید)</label>
        <input type="text" name="tags_text" value="{{ old('tags_text', $product->exists ? $product->tags->pluck('name')->join(', ') : '') }}">
    </div>

    <div class="admin-form__field">
        <label>تصاویر</label>
        <input type="file" name="images[]" accept="image/*" multiple>
        @if($product->exists && $product->images->isNotEmpty())
            <div class="admin-form__image-row">
                @foreach($product->images as $image)
                    <div style="position:relative; display:inline-block;">
                        <img src="{{ asset('storage/' . $image->path) }}" alt="" class="admin-form__preview-image">
                        <form method="POST" action="{{ route('admin.products.images.destroy', $image) }}" style="position:absolute; top:2px; left:2px;" data-confirm="این تصویر حذف شود؟">
                            @csrf @method('DELETE')
                            <button type="submit" style="background:var(--danger); color:#fff; border:none; border-radius:50%; width:22px; height:22px; cursor:pointer; line-height:1;">×</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <button type="submit" class="admin-btn-primary">{{ $product->exists ? 'ذخیره تغییرات' : 'ایجاد محصول' }}</button>
</form>
@endsection
