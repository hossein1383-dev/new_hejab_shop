{{--
    گالری دسکتاپ: Thumbnail عمودی کنار تصویر اصلی + Zoom با Hover (بخش ۱۱).
    برخلاف موبایل، اینجا فضای کافی برای دیدن همه Thumbnail هم‌زمان هست،
    پس نیازی به Swipe یا Dots نیست.
--}}
<div class="product-gallery-desktop">
    <div class="product-gallery-desktop__thumbnails">
        @forelse($product->images as $index => $image)
            <button
                type="button"
                class="product-gallery-desktop__thumb @if($index === 0) is-active @endif"
                data-gallery-thumb
                data-image-url="{{ asset('storage/' . $image->path) }}"
            >
                <img src="{{ asset('storage/' . $image->path) }}" alt="{{ $product->name }}">
            </button>
        @empty
            <button type="button" class="product-gallery-desktop__thumb is-active" data-gallery-thumb data-image-url="{{ asset('images/placeholder-product.svg') }}">
                <img src="{{ asset('images/placeholder-product.svg') }}" alt="{{ $product->name }}">
            </button>
        @endforelse
    </div>

    <div class="product-gallery-desktop__main" data-gallery-main-wrapper>
        <img
            src="{{ $product->images->first() ? asset('storage/' . $product->images->first()->path) : asset('images/placeholder-product.svg') }}"
            alt="{{ $product->name }}"
            class="product-gallery-desktop__main-image"
            data-gallery-main
        >
    </div>
</div>
