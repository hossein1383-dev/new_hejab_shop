@extends('admin.layout')
@section('title', 'مدیریت موجودی')
@section('content')
    <h1 class="admin-page-title">موجودی انبار</h1>
    <form method="get" class="admin-filter-bar">
        <input type="text" name="search" placeholder="جستجوی محصول..." value="{{ request('search') }}">
        <button type="submit">جستجو</button>
    </form>

    @foreach ($products as $product)
        @php
            $totalQty =
                ($product->inventory?->availableQuantity() ?? 0) +
                $product->variants->sum(fn($v) => $v->inventory?->availableQuantity() ?? 0);
        @endphp
        <div class="admin-card">
            <h2>{{ $product->name }}</h2>
            <p class="admin-help-text">موجودی کل: <strong>{{ number_format($totalQty) }}</strong> عدد</p>
            @if ($product->variants->isEmpty())
                <div class="admin-order-item">
                    <span>موجودی پایه: {{ $product->inventory?->availableQuantity() ?? 0 }}</span>
                </div>
            @endif
            @foreach ($product->variants as $variant)
                <div class="admin-order-item">
                    <span>{{ $variant->sku }}: {{ $variant->inventory?->availableQuantity() ?? 0 }}</span>
                </div>
            @endforeach

            <form method="POST" action="{{ route('admin.inventory.adjust', $product) }}"
                style="display:flex; gap:8px; flex-wrap:wrap; margin-top: var(--space-3);">
                @csrf
                @if ($product->variants->isNotEmpty())
                    <select name="product_variant_id"
                        style="height:36px; border:1px solid var(--border); border-radius:var(--radius-input);">
                        <option value="">محصول پایه (بدون Variant)</option>
                        @foreach ($product->variants as $variant)
                            <option value="{{ $variant->id }}">{{ $variant->sku }}</option>
                        @endforeach
                    </select>
                @endif
                <select name="type"
                    style="height:36px; border:1px solid var(--border); border-radius:var(--radius-input);">
                    <option value="purchase">خرید/ورود کالا (+)</option>
                    <option value="return">مرجوعی (+)</option>
                    <option value="damage">خرابی (−)</option>
                    <option value="adjustment">اصلاح دستی</option>
                </select>
                <input type="number" name="quantity" placeholder="تعداد" min="1" required
                    style="width:100px; height:36px; border:1px solid var(--border); border-radius:var(--radius-input); padding:0 8px;">
                <input type="text" name="note" placeholder="یادداشت (اختیاری)"
                    style="height:36px; border:1px solid var(--border); border-radius:var(--radius-input); padding:0 8px;">
                <button type="submit" class="admin-table__link">ثبت تغییر</button>
            </form>
        </div>
    @endforeach

    <div class="admin-pagination">{{ $products->links() }}</div>
@endsection
