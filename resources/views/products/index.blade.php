@extends('layouts.store')

@section('title', $currentCategory?->name ?? 'همه محصولات')

@push('styles')
    @vite([
        'resources/css/components/header.css',
        'resources/css/components/product-card.css',
        'resources/css/components/toast.css',
        'resources/css/components/media-loading.css',
        'resources/css/pages/products.css',
    ])
@endpush

@section('body')
    <div class="products-page">
        <nav class="breadcrumb">
            <a href="{{ url('/') }}">خانه</a>
            <span>/</span>
            <span>{{ $currentCategory?->name ?? 'همه محصولات' }}</span>
        </nav>

        {{-- فیلتر موبایل: دکمه باز کردن Bottom Sheet --}}
        <div class="products-page__mobile-filters">
            @include('components.filters.filters-mobile')
        </div>

        <div class="products-page__layout">
            {{-- فیلتر دسکتاپ: Sidebar ثابت --}}
            <div class="products-page__sidebar">
                @include('components.filters.filters-desktop')
            </div>

            <div class="products-page__main">
                <div class="products-page__toolbar">
                    <h1 class="products-page__title">{{ $currentCategory?->name ?? 'همه محصولات' }}</h1>
                    <span class="products-page__count">{{ $products->total() }} محصول</span>

                    {{-- مرتب‌سازی دسکتاپ (در موبایل داخل همان Bottom Sheet فیلتر است) --}}
                    <form method="get" class="products-page__sort-desktop" data-sort-form>
                        <select name="sort" data-sort-select>
                            <option value="newest" @selected($appliedFilters['sort'] === 'newest')>جدیدترین</option>
                            <option value="cheapest" @selected($appliedFilters['sort'] === 'cheapest')>ارزان‌ترین</option>
                            <option value="expensive" @selected($appliedFilters['sort'] === 'expensive')>گران‌ترین</option>
                            <option value="bestseller" @selected($appliedFilters['sort'] === 'bestseller')>پرفروش‌ترین</option>
                        </select>
                    </form>
                </div>

                @if($products->isEmpty())
                    <div class="empty-state">
                        <p>محصولی با این فیلترها پیدا نشد.</p>
                        <a href="{{ $currentCategory ? url('/category/' . $currentCategory->slug) : url('/products') }}">حذف فیلترها</a>
                    </div>
                @else
                    <div class="product-grid">
                        @foreach($products as $product)
                            @include('components.product-card', ['product' => $product])
                        @endforeach
                    </div>

                    <div class="products-page__pagination">
                        {{ $products->onEachSide(1)->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite([
        'resources/js/header.js',
        'resources/js/filters.js',
        'resources/js/cart.js',
        'resources/js/wishlist.js',
        'resources/js/toast.js',
        'resources/js/media-loading.js',
    ])
@endpush
