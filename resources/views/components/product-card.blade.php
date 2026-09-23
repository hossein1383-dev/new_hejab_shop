{{--
    ProductCard — بخش ۲۵. Markup مشترک بین موبایل و دسکتاپ (فقط Hover در
    دسکتاپ معنا دارد؛ در موبایل خودش Hover ندارد، پس تفاوت صرفاً با CSS
    :hover طبیعتاً غیرفعال می‌ماند، نیازی به دو Component جدا نیست).
--}}
<article class="product-card" data-product-id="{{ $product->id }}">
    <a href="{{ url('/products/' . $product->slug) }}" class="product-card__image-link">
        @if($product->hasDiscount())
            <span class="product-card__badge product-card__badge--discount">تخفیف</span>
        @elseif(($badgeType ?? null) === 'new')
            <span class="product-card__badge product-card__badge--new">جدید</span>
        @endif

        @if(($badgeType ?? null) === 'bestseller' && isset($badgeNumber))
            <span class="product-card__rank-badge">{{ $badgeNumber }}</span>
        @endif

        @php $isWishlisted = in_array($product->id, $wishlistProductIds ?? []); @endphp
        <button
            type="button"
            class="product-card__wishlist-btn @if($isWishlisted) is-active @endif"
            data-wishlist-toggle
            data-product-id="{{ $product->id }}"
            aria-label="{{ $isWishlisted ? 'حذف از علاقه‌مندی‌ها' : 'افزودن به علاقه‌مندی‌ها' }}"
        >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="{{ $isWishlisted ? 'currentColor' : 'none' }}" aria-hidden="true">
                <path d="M12 20s-7-4.35-9.5-8.5C.7 8 2 4.5 5.5 4A5 5 0 0 1 12 7a5 5 0 0 1 6.5-3c3.5.5 4.8 4 3 7.5C19 15.65 12 20 12 20z" stroke="currentColor" stroke-width="2"/>
            </svg>
        </button>

        <img
            src="{{ $product->thumbnail() ? asset('storage/' . $product->thumbnail()->path) : asset('images/placeholder-product.svg') }}"
            alt="{{ $product->name }}"
            loading="lazy"
            class="product-card__image"
        >
    </a>

    <div class="product-card__body">
        <a href="{{ url('/products/' . $product->slug) }}" class="product-card__name">{{ $product->name }}</a>

        @php $rating = ($ratingsMap ?? [])[$product->id] ?? null; @endphp
        <div class="product-card__rating" aria-label="{{ $rating ? 'امتیاز ' . $rating['average'] . ' از ۵' : 'بدون امتیاز' }}">
            @if($rating)
                @php $roundedRating = round($rating['average']); @endphp
                @for($i = 1; $i <= 5; $i++)
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="{{ $i <= $roundedRating ? '#F59E0B' : 'none' }}" stroke="#F59E0B" stroke-width="1.5" aria-hidden="true">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                @endfor
            @endif
        </div>

        <div class="product-card__price-row">
            <span class="product-card__price">{{ number_format($product->price) }} تومان</span>
            @if($product->hasDiscount())
                <span class="product-card__old-price">{{ number_format($product->compare_price) }}</span>
            @endif
        </div>

        @if($product->variants->isEmpty())
            <button
                type="button"
                class="product-card__quick-add"
                data-add-to-cart
                data-product-id="{{ $product->id }}"
            >
                افزودن به سبد
            </button>
        @else
            {{-- محصولاتی که رنگ/سایز دارند از همین کارت اضافه نمی‌شوند تا ادمین
                 بدون دانستن سایز/رنگ دقیق سفارش، مجبور به حدس‌زدن نشود --}}
            <a href="{{ url('/products/' . $product->slug) }}" class="product-card__quick-add product-card__quick-add--link">
                مشاهده محصول
            </a>
        @endif
    </div>
</article>
