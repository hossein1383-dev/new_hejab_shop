@extends('admin.layout')
@section('title', 'ویژگی‌ها (رنگ، سایز و ...)')
@section('content')
<h1 class="admin-page-title">ویژگی‌های محصول</h1>

<form method="POST" action="{{ route('admin.attributes.store') }}" class="admin-filter-bar">
    @csrf
    <input type="text" name="name" placeholder="نام ویژگی جدید (مثلاً رنگ)..." required>
    <button type="submit">افزودن ویژگی</button>
</form>
@error('name')<p style="color:var(--danger); font-size:0.85rem;">{{ $message }}</p>@enderror
@error('value')<p style="color:var(--danger); font-size:0.85rem;">{{ $message }}</p>@enderror

@foreach($attributes as $attribute)
    <div class="admin-card">
        <h2>{{ $attribute->name }}</h2>
        <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom: var(--space-3);">
            @foreach($attribute->values as $value)
                <span class="admin-status-badge" style="display:flex; align-items:center; gap:6px;">
                    {{ $value->value }}
                    <form method="POST" action="{{ route('admin.attribute-values.destroy', $value) }}" data-confirm="حذف؟" style="display:inline;">
                        @csrf @method('DELETE')
                        <button type="submit" style="background:none;border:none;color:var(--danger);cursor:pointer;">✕</button>
                    </form>
                </span>
            @endforeach
        </div>
        <form method="POST" action="{{ route('admin.attributes.values.store', $attribute) }}" style="display:flex; gap:8px;">
            @csrf
            <input type="text" name="value" placeholder="مقدار جدید (مثلاً قرمز)..." required style="height:36px; border:1px solid var(--border); border-radius:var(--radius-input); padding:0 8px;">
            <button type="submit" class="admin-table__link">افزودن مقدار</button>
        </form>
    </div>
@endforeach
@endsection
