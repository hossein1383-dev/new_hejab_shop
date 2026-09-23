{{--
    گالری موبایل: Swipe افقی تمام‌عرض با Scroll-Snap (بخش ۱۱ و ۲۰.۱).
    برخلاف دسکتاپ، اینجا Thumbnail جدا و Zoom با Hover بی‌معنا هستند
    (چون روی Touch اصلاً Hover وجود ندارد) — به‌جایش نقطه‌های صفحه (Dots) نشان
    می‌دهند کاربر کجای گالری است.
--}}
<div class="product-gallery-mobile">
    <div class="product-gallery-mobile__track" data-gallery-track>
        @forelse($product->images as $image)
            <img src="{{ asset('storage/' . $image->path) }}" alt="{{ $product->name }}" class="product-gallery-mobile__slide">
        @empty
            <img src="{{ asset('images/placeholder-product.svg') }}" alt="{{ $product->name }}" class="product-gallery-mobile__slide">
        @endforelse
    </div>

    @if($product->images->count() > 1)
        <div class="product-gallery-mobile__dots" data-gallery-dots>
            @foreach($product->images as $index => $image)
                <span class="product-gallery-mobile__dot @if($index === 0) is-active @endif"></span>
            @endforeach
        </div>
    @endif
</div>
