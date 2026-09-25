@extends('layouts.store')

@section('title', $product->meta_title ?? $product->name)
@section('meta_description', $product->meta_description ?? \Illuminate\Support\Str::limit(strip_tags($product->short_description ?? $product->description ?? ''), 155))

@php
    $baseInventory = $product->inventory()->first();

    $productSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'sku' => $product->sku,
        'image' => $product->images->map(fn ($img) => asset('storage/' . $img->path))->values(),
        'description' => strip_tags($product->short_description ?? ''),
        'offers' => [
            '@type' => 'Offer',
            'priceCurrency' => 'IRT',
            'price' => $product->price,
            'availability' => ($baseInventory?->availableQuantity() ?? 0) > 0
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
            'url' => url('/products/' . $product->slug),
        ],
    ];

    if ($product->reviewsCount() > 0) {
        $productSchema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $product->averageRating(),
            'reviewCount' => $product->reviewsCount(),
        ];
    }

    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $product->category->name, 'item' => url('/category/' . $product->category->slug)],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $product->name],
        ],
    ];
@endphp

@push('structured_data')
<script type="application/ld+json">{!! json_encode($productSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@push('styles')
    @vite([
        'resources/css/components/header.css',
        'resources/css/components/product-card.css',
        'resources/css/components/toast.css',
        'resources/css/components/media-loading.css',
        'resources/css/pages/product.css',
    ])
@endpush

@php
    $variantsPayload = $product->variants->map(function ($variant) {
        return [
            'id' => $variant->id,
            'price' => $variant->effectivePrice(),
            'compare_price' => $variant->compare_price,
            'stock' => $variant->inventory?->availableQuantity() ?? 0,
            'image' => $variant->image ? asset('storage/' . $variant->image) : null,
            'attribute_value_ids' => $variant->attributeValues->pluck('id')->sort()->values(),
        ];
    });
@endphp

@section('body')
    <div class="product-page">
        <nav class="breadcrumb">
            <a href="{{ url('/') }}">خانه</a>
            <span>/</span>
            <a href="{{ url('/category/' . $product->category->slug) }}">{{ $product->category->name }}</a>
            <span>/</span>
            <span>{{ $product->name }}</span>
        </nav>

        <div class="product-page__layout">
            <div class="product-page__gallery">
                <div class="product-page__gallery-mobile">
                    @include('components.gallery.product-gallery-mobile')
                </div>
                <div class="product-page__gallery-desktop">
                    @include('components.gallery.product-gallery-desktop')
                </div>
            </div>

            <div class="product-page__info">
                @if($product->brand)
                    <a href="{{ url('/products?brands[]=' . $product->brand->id) }}" class="product-page__brand">{{ $product->brand->name }}</a>
                @endif

                <h1 class="product-page__name">{{ $product->name }}</h1>

                @if($product->reviewsCount() > 0)
                    <div class="product-page__rating">
                        <span class="product-page__rating-stars">{{ str_repeat('★', round($product->averageRating())) }}{{ str_repeat('☆', 5 - round($product->averageRating())) }}</span>
                        <span>{{ $product->averageRating() }} ({{ $product->reviewsCount() }} نظر)</span>
                    </div>
                @endif

                <div class="product-page__price-row" data-price-display>
                    <span class="product-page__price" data-current-price>{{ number_format($product->price) }} تومان</span>
                    @if($product->hasDiscount())
                        <span class="product-page__old-price" data-old-price>{{ number_format($product->compare_price) }}</span>
                    @endif
                </div>

                <div class="product-page__stock" data-stock-display>
                    @php $availableStock = $baseInventory?->availableQuantity() ?? 0; @endphp
                    @if($availableStock > 0)
                        <span class="product-page__in-stock">موجود در انبار</span>
                    @else
                        <span class="product-page__out-of-stock">ناموجود</span>
                    @endif
                </div>

                @if($product->short_description)
                    <p class="product-page__short-desc">{{ $product->short_description }}</p>
                @endif

                {{-- Variant Selector — فقط اگر محصول Variant داشته باشد (بخش ۷) --}}
                @if($product->variants->isNotEmpty())
                    @php
                        $attributeGroups = $product->variants
                            ->flatMap->attributeValues
                            ->unique('id')
                            ->groupBy(fn ($value) => $value->attribute->name);
                    @endphp

                    <div class="product-page__variants" data-variant-selector>
                        @foreach($attributeGroups as $attributeName => $values)
                            <div class="product-page__variant-group">
                                <span class="product-page__variant-label">{{ $attributeName }}</span>
                                <div class="product-page__variant-options">
                                    @foreach($values as $value)
                                        <button
                                            type="button"
                                            class="product-page__variant-option"
                                            data-attribute-value-id="{{ $value->id }}"
                                        >{{ $value->value }}</button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="product-page__quantity-row">
                    <span class="product-page__quantity-label">تعداد</span>
                    <div class="product-page__quantity-stepper">
                        <button type="button" data-qty-decrease aria-label="کم کردن">−</button>
                        <input type="number" value="1" min="1" data-qty-input readonly>
                        <button type="button" data-qty-increase aria-label="زیاد کردن">+</button>
                    </div>
                </div>

                @if($totalAvailableQuantity > 0)
                    <p class="product-page__total-stock">موجودی کل: {{ number_format($totalAvailableQuantity) }} عدد</p>
                @endif

                <div class="product-page__actions">
                    <button type="button" class="product-page__add-to-cart" data-add-to-cart-detail
                        data-product-id="{{ $product->id }}">
                        افزودن به سبد
                    </button>
                    <button type="button" class="product-page__buy-now" data-buy-now
                        data-product-id="{{ $product->id }}">
                        خرید سریع
                    </button>
                    <button type="button" class="product-page__wishlist @if($isInWishlist) is-active @endif" data-wishlist-toggle
                        data-product-id="{{ $product->id }}" aria-label="{{ $isInWishlist ? 'حذف از علاقه‌مندی‌ها' : 'افزودن به علاقه‌مندی‌ها' }}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="{{ $isInWishlist ? 'currentColor' : 'none' }}" aria-hidden="true">
                            <path d="M12 20s-7-4.35-9.5-8.5C.7 8 2 4.5 5.5 4A5 5 0 0 1 12 7a5 5 0 0 1 6.5-3c3.5.5 4.8 4 3 7.5C19 15.65 12 20 12 20z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </button>
                </div>

                <div class="product-page__trust">
                    <span>✓ ضمانت اصالت کالا</span>
                    <span>✓ امکان بازگشت کالا تا ۷ روز</span>
                </div>
            </div>
        </div>

        {{-- توضیحات / مشخصات — Progressive Disclosure با Accordion (بخش ۲۹.۱) --}}
        <div class="product-page__tabs">
            @if($product->description)
                <details class="product-page__accordion" open>
                    <summary>توضیحات محصول</summary>
                    <div class="product-page__accordion-body">{!! nl2br(e($product->description)) !!}</div>
                </details>
            @endif

            @if($product->tags->isNotEmpty())
                <details class="product-page__accordion">
                    <summary>برچسب‌ها</summary>
                    <div class="product-page__accordion-body">
                        {{ $product->tags->pluck('name')->join('، ') }}
                    </div>
                </details>
            @endif

            <details class="product-page__accordion">
                <summary>نظرات کاربران ({{ $product->reviewsCount() }})</summary>
                <div class="product-page__accordion-body">
                    @forelse($reviews as $review)
                        <div class="review-item">
                            <div class="review-item__header">
                                <strong>{{ $review->user->name }}</strong>
                                <span>{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</span>
                            </div>
                            @if($review->comment)
                                <p class="review-item__comment">{{ $review->comment }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="qa-empty">هنوز نظری برای این محصول ثبت نشده است.</p>
                    @endforelse

                    {{ $reviews->links() }}

                    @auth
                        @unless($userHasReviewed)
                            <form id="review-form" class="qa-form" data-product-id="{{ $product->id }}">
                                <div class="qa-form__stars" data-rating-input>
                                    @for($i = 1; $i <= 5; $i++)
                                        <button type="button" data-rating-star="{{ $i }}">☆</button>
                                    @endfor
                                    <input type="hidden" name="rating" value="0">
                                </div>
                                <textarea name="comment" placeholder="نظر شما درباره این محصول (اختیاری)" rows="3"></textarea>
                                <button type="submit" class="qa-form__submit">ثبت نظر</button>
                            </form>
                        @else
                            <p class="qa-empty">شما قبلاً برای این محصول نظر ثبت کرده‌اید.</p>
                        @endunless
                    @else
                        <p class="qa-empty"><a href="{{ url('/login') }}">وارد شوید</a> تا بتوانید نظر ثبت کنید.</p>
                    @endauth
                </div>
            </details>

            <details class="product-page__accordion">
                <summary>پرسش و پاسخ ({{ $questions->total() }})</summary>
                <div class="product-page__accordion-body">
                    @forelse($questions as $question)
                        <div class="qa-item">
                            <p class="qa-item__question"><strong>س:</strong> {{ $question->body }}</p>
                            @foreach($question->answers as $answer)
                                <p class="qa-item__answer"><strong>ج:</strong> {{ $answer->body }}</p>
                            @endforeach
                        </div>
                    @empty
                        <p class="qa-empty">هنوز سوالی برای این محصول ثبت نشده است.</p>
                    @endforelse

                    {{ $questions->links() }}

                    @auth
                        <form id="question-form" class="qa-form" data-product-id="{{ $product->id }}">
                            <textarea name="body" placeholder="سوال خود را درباره این محصول بپرسید" rows="2" required></textarea>
                            <button type="submit" class="qa-form__submit">ثبت سوال</button>
                        </form>
                    @else
                        <p class="qa-empty"><a href="{{ url('/login') }}">وارد شوید</a> تا بتوانید سوال بپرسید.</p>
                    @endauth
                </div>
            </details>
        </div>

        {{-- محصولات مرتبط (بخش ۱۱) --}}
        @if($relatedProducts->isNotEmpty())
            <section class="home-section">
                <h2 class="home-section__title">محصولات مرتبط</h2>
                <div class="product-scroller">
                    @foreach($relatedProducts as $related)
                        @include('components.product-card', ['product' => $related])
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    {{-- Sticky Purchase CTA — فقط در موبایل نمایش داده می‌شود (بخش ۲۰.۱) --}}
    <div class="product-page__sticky-cta">
        <div class="product-page__sticky-price">{{ number_format($product->price) }} تومان</div>
        <button type="button" class="product-page__sticky-add" data-add-to-cart-detail
            data-product-id="{{ $product->id }}">افزودن به سبد</button>
    </div>

    <script type="application/json" id="product-data">
        {!! json_encode([
            'productId' => $product->id,
            'basePrice' => $product->price,
            'baseComparePrice' => $product->compare_price,
            'baseStock' => $availableStock,
            'variants' => $variantsPayload,
        ]) !!}
    </script>
@endsection

@push('scripts')
    @vite([
        'resources/js/header.js',
        'resources/js/gallery.js',
        'resources/js/product.js',
        'resources/js/reviews.js',
        'resources/js/cart.js',
        'resources/js/wishlist.js',
        'resources/js/toast.js',
        'resources/js/media-loading.js',
    ])
@endpush
