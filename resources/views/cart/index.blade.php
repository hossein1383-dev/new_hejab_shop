@extends('layouts.store')

@section('title', 'سبد خرید')

@push('styles')
    @vite([
        'resources/css/components/header.css',
        'resources/css/components/toast.css',
        'resources/css/pages/cart.css',
    ])
@endpush

@section('body')
    <div class="cart-page">
        <nav class="breadcrumb">
            <a href="{{ url('/') }}">خانه</a>
            <span>/</span>
            <span>سبد خرید</span>
        </nav>

        @if($pendingOrders->isNotEmpty())
            <div class="cart-pending-orders">
                <h2 class="cart-pending-orders__title">⚠️ سفارش‌های در انتظار پرداخت</h2>
                @foreach($pendingOrders as $pendingOrder)
                    <div class="cart-pending-orders__item" data-pending-order="{{ $pendingOrder->id }}">
                        <div>
                            <strong>{{ $pendingOrder->productNamesSummary() }}</strong>
                            <span>{{ number_format($pendingOrder->total) }} تومان</span>
                        </div>
                        <button type="button" class="cart-pending-orders__pay-btn" data-retry-payment="{{ $pendingOrder->id }}">
                            پرداخت
                        </button>
                    </div>
                @endforeach
            </div>
        @endif

        @if($cart->items->isEmpty())
            <div class="cart-page__empty" data-cart-empty>
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 6h15l-1.5 9h-12z" stroke="currentColor" stroke-width="1.5"/>
                    <circle cx="9" cy="20" r="1.5" fill="currentColor"/>
                    <circle cx="18" cy="20" r="1.5" fill="currentColor"/>
                </svg>
                <p>سبد خرید شما خالی است.</p>
                <a href="{{ url('/products') }}" class="cart-page__empty-cta">مشاهده محصولات</a>
            </div>
        @else
            <div class="cart-page__layout" data-cart-layout>
                <div class="cart-page__items" data-cart-items>
                    @foreach($cart->items as $item)
                        <div class="cart-item" data-cart-item="{{ $item->id }}">
                            <a href="{{ url('/products/' . $item->product->slug) }}" class="cart-item__image-link">
                                <img src="{{ $item->product->images->isNotEmpty() ? asset('storage/' . $item->product->images->first()->path) : asset('images/placeholder-product.svg') }}" alt="{{ $item->product->name }}">
                            </a>

                            <div class="cart-item__info">
                                <a href="{{ url('/products/' . $item->product->slug) }}" class="cart-item__name">{{ $item->product->name }}</a>
                                @if($item->variant)
                                    <span class="cart-item__variant">{{ $item->variant->attributeValues->pluck('value')->join('، ') }}</span>
                                @endif
                                <span class="cart-item__unit-price" data-unit-price="{{ $item->unit_price }}">{{ number_format($item->unit_price) }} تومان</span>

                                {{-- کنترل تعداد/حذف نسخه موبایل --}}
                                <div class="cart-item__mobile-controls">
                                    <div class="cart-item__stepper">
                                        <button type="button" data-qty-decrease>−</button>
                                        <span data-qty-value>{{ $item->quantity }}</span>
                                        <button type="button" data-qty-increase>+</button>
                                    </div>
                                    <button type="button" class="cart-item__remove" data-remove-item aria-label="حذف">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M4 7h16M9 7V4h6v3m-8 0 1 13h8l1-13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {{-- کنترل تعداد/حذف/جمع ردیف نسخه دسکتاپ --}}
                            <div class="cart-item__desktop-controls">
                                <div class="cart-item__stepper">
                                    <button type="button" data-qty-decrease>−</button>
                                    <span data-qty-value>{{ $item->quantity }}</span>
                                    <button type="button" data-qty-increase>+</button>
                                </div>
                                <span class="cart-item__line-total" data-line-total>{{ number_format($item->lineTotal()) }} تومان</span>
                                <button type="button" class="cart-item__remove" data-remove-item aria-label="حذف">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M4 7h16M9 7V4h6v3m-8 0 1 13h8l1-13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                    </svg>
                                </button>
                            </div>

                            <span class="cart-item__line-total-mobile" data-line-total>{{ number_format($item->lineTotal()) }} تومان</span>
                        </div>
                    @endforeach
                </div>

                <aside class="cart-summary">
                    <h2 class="cart-summary__title">خلاصه سفارش</h2>
                    <div class="cart-summary__row">
                        <span>جمع کالاها (<span data-items-count>{{ $cart->itemsCount() }}</span>)</span>
                        <span data-cart-subtotal>{{ number_format($cart->total()) }} تومان</span>
                    </div>
                    <div class="cart-summary__row cart-summary__row--muted">
                        <span>هزینه ارسال</span>
                        <span>در مرحله بعد محاسبه می‌شود</span>
                    </div>
                    <div class="cart-summary__divider"></div>
                    <div class="cart-summary__row cart-summary__row--total">
                        <span>جمع کل</span>
                        <span data-cart-subtotal>{{ number_format($cart->total()) }} تومان</span>
                    </div>

                    <button type="button" class="cart-summary__checkout-btn" data-checkout-btn>
                        تسویه‌حساب و ادامه خرید
                    </button>
                    <a href="{{ url('/products') }}" class="cart-summary__continue-link">ادامه خرید</a>
                </aside>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    @vite([
        'resources/js/header.js',
        'resources/js/cart-page.js',
        'resources/js/toast.js',
    ])
@endpush
