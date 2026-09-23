@extends('admin.layout')
@section('title', 'Variant های ' . $product->name)
@section('content')
<a href="{{ route('admin.products.index') }}" class="admin-back-link">← بازگشت به محصولات</a>
<h1 class="admin-page-title">Variant های «{{ $product->name }}»</h1>

<div class="admin-card">
    <h2>Variant های فعلی ({{ $product->variants->count() }})</h2>
    @forelse($product->variants as $variant)
        <div class="admin-order-item">
            <span>
                {{ $variant->sku }} —
                {{ $variant->attributeValues->pluck('value')->join('، ') }}
                (موجودی: {{ $variant->inventory?->availableQuantity() ?? 0 }})
            </span>
            <form method="POST" action="{{ route('admin.products.variants.destroy', [$product, $variant]) }}" data-confirm="حذف این Variant؟">
                @csrf @method('DELETE')
                <button type="submit" class="admin-table__link admin-table__link--danger">حذف</button>
            </form>
        </div>
    @empty
        <p class="admin-empty">این محصول هنوز Variant ندارد.</p>
    @endforelse
</div>

<div class="admin-card">
    <h2>ساخت دسته‌جمعی Variant</h2>
    @if($attributes->isEmpty())
        <p class="admin-empty">ابتدا باید <a href="{{ route('admin.attributes.index') }}">ویژگی و مقدار</a> تعریف کنید.</p>
    @else
        @if($errors->any())
            <div class="admin-alert admin-alert--error">
                <ul style="margin:0; padding-right: 18px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom: var(--space-3);">
            از هر ویژگی، چند مقدار که می‌خواهید انتخاب کنید (مثلاً از «رنگ»: مشکی+سفید، از «سایز»: M+L). سیستم خودش همه ترکیب‌ها (اینجا ۲×۲=۴ Variant) را با یک SKU خودکار و موجودی اولیه یکسان می‌سازد.
        </p>

        <form method="POST" action="{{ route('admin.products.variants.store', $product) }}" class="admin-form" style="border:none; padding:0;">
            @csrf

            @foreach($attributes as $attribute)
                <div class="admin-form__field">
                    <label>{{ $attribute->name }}</label>
                    <div style="display:flex; flex-wrap:wrap; gap:12px;">
                        @foreach($attribute->values as $value)
                            <label style="display:flex; align-items:center; gap:6px; font-weight:400;">
                                <input type="checkbox" name="values[{{ $attribute->id }}][]" value="{{ $value->id }}"
                                    @checked(collect(old('values.' . $attribute->id, []))->contains($value->id))>
                                {{ $value->value }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="admin-form__row">
                <div class="admin-form__field">
                    <label>قیمت اختصاصی (اختیاری — خالی = قیمت محصول اصلی)</label>
                    <input type="number" name="price" value="{{ old('price') }}">
                </div>
                <div class="admin-form__field">
                    <label>قیمت قبل از تخفیف (اختیاری)</label>
                    <input type="number" name="compare_price" value="{{ old('compare_price') }}">
                </div>
            </div>

            <div class="admin-form__row">
                <div class="admin-form__field">
                    <label>موجودی اولیه هر Variant (اختیاری — برای همه ترکیب‌ها یکسان)</label>
                    <input type="number" name="initial_stock" min="0" value="{{ old('initial_stock', 0) }}">
                </div>
                <div class="admin-form__field">
                    <label>وضعیت</label>
                    <select name="status">
                        <option value="active" @selected(old('status', 'active') === 'active')>فعال</option>
                        <option value="inactive" @selected(old('status') === 'inactive')>غیرفعال</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="admin-btn-primary">ساخت همه ترکیب‌ها</button>
        </form>
    @endif
</div>
@endsection
