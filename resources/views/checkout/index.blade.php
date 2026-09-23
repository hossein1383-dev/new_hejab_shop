@extends('layouts.store')

@section('title', 'تسویه‌حساب')

@push('styles')
    @vite([
        'resources/css/components/header.css',
        'resources/css/components/toast.css',
        'resources/css/pages/checkout.css',
    ])
@endpush

@php
    $itemsPayload = $cart->items->map(fn ($item) => [
        'name' => $item->product->name,
        'variant' => $item->variant?->attributeValues->pluck('value')->join('، '),
        'quantity' => $item->quantity,
        'line_total' => $item->lineTotal(),
    ]);
@endphp

@section('body')
<div class="checkout-page">
    <nav class="breadcrumb">
        <a href="{{ url('/cart') }}">سبد خرید</a>
        <span>/</span>
        <span>تسویه‌حساب</span>
    </nav>

    {{-- وضوح وضعیت سیستم — کاربر همیشه بداند کجای مسیر است (بخش ۲۹.۱ بند ۱) --}}
    <div class="checkout-progress" data-checkout-progress>
        <div class="checkout-progress__step is-active" data-step-indicator="1">
            <span class="checkout-progress__circle">۱</span>
            <span class="checkout-progress__label">آدرس</span>
        </div>
        <div class="checkout-progress__line"></div>
        <div class="checkout-progress__step" data-step-indicator="2">
            <span class="checkout-progress__circle">۲</span>
            <span class="checkout-progress__label">ارسال</span>
        </div>
        <div class="checkout-progress__line"></div>
        <div class="checkout-progress__step" data-step-indicator="3">
            <span class="checkout-progress__circle">۳</span>
            <span class="checkout-progress__label">پرداخت</span>
        </div>
    </div>

    <div class="checkout-page__layout">
        <div class="checkout-page__form">
            <div class="checkout-general-error" data-general-error hidden></div>

            {{-- مرحله ۱: آدرس --}}
            <section class="checkout-step is-active" data-step="1">
                <h2 class="checkout-step__title">آدرس ارسال</h2>

                @auth
                    @if($addresses->isNotEmpty())
                        <div class="checkout-address-list">
                            @foreach($addresses as $address)
                                <label class="checkout-address-option">
                                    <input type="radio" name="address_id" value="{{ $address->id }}" @checked($loop->first)>
                                    <span class="checkout-address-option__body">
                                        <strong>{{ $address->title ?? $address->receiver_name }}</strong>
                                        <span>{{ $address->province }}، {{ $address->city }}، {{ $address->address_line }}</span>
                                        <span>{{ $address->phone }}</span>
                                    </span>
                                </label>
                            @endforeach
                            <label class="checkout-address-option">
                                <input type="radio" name="address_id" value="new" data-new-address-radio>
                                <span class="checkout-address-option__body"><strong>+ آدرس جدید</strong></span>
                            </label>
                        </div>
                    @endif
                @endauth

                <div class="checkout-address-form" data-new-address-form @if($addresses->isNotEmpty()) hidden @endif>

                    <div class="checkout-field-row">
                        <div class="checkout-field">
                            <label for="receiver_name">نام گیرنده</label>
                            <input type="text" id="receiver_name" name="address[receiver_name]">
                            <div class="checkout-field-error" data-error-for="address.receiver_name" hidden></div>
                        </div>
                        <div class="checkout-field">
                            <label for="phone">شماره موبایل</label>
                            <input type="tel" id="phone" name="address[phone]">
                            <div class="checkout-field-error" data-error-for="address.phone" hidden></div>
                        </div>
                    </div>

                    <div class="checkout-field-row">
                        @if($heropostProvinces->isNotEmpty())
                            <div class="checkout-field">
                                <label for="province">استان</label>
                                <select id="province" name="address[province]">
                                    <option value="">— انتخاب کنید —</option>
                                    @foreach($heropostProvinces as $province)
                                        <option value="{{ $province }}">{{ $province }}</option>
                                    @endforeach
                                </select>
                                <div class="checkout-field-error" data-error-for="address.province" hidden></div>
                            </div>
                            <div class="checkout-field">
                                <label for="city">شهر</label>
                                <select id="city" name="address[city]" disabled>
                                    <option value="">ابتدا استان را انتخاب کنید</option>
                                </select>
                                <input type="hidden" id="heropost_city_id" name="address[heropost_city_id]">
                                <div class="checkout-field-error" data-error-for="address.city" hidden></div>
                            </div>
                        @else
                            <div class="checkout-field">
                                <label for="province">استان</label>
                                <input type="text" id="province" name="address[province]">
                                <div class="checkout-field-error" data-error-for="address.province" hidden></div>
                            </div>
                            <div class="checkout-field">
                                <label for="city">شهر</label>
                                <input type="text" id="city" name="address[city]">
                                <div class="checkout-field-error" data-error-for="address.city" hidden></div>
                            </div>
                        @endif
                    </div>

                    <div class="checkout-field">
                        <label for="address_line">آدرس کامل</label>
                        <textarea id="address_line" name="address[address_line]" rows="2"></textarea>
                        <div class="checkout-field-error" data-error-for="address.address_line" hidden></div>
                    </div>

                    <div class="checkout-field">
                        <label for="postal_code">کد پستی (اختیاری)</label>
                        <input type="text" id="postal_code" name="address[postal_code]">
                    </div>
                </div>

                <button type="button" class="checkout-btn-next" data-go-step="2">ادامه به ارسال</button>
            </section>

            {{-- مرحله ۲: ارسال --}}
            <section class="checkout-step" data-step="2">
                <h2 class="checkout-step__title">روش ارسال</h2>

                <label class="checkout-shipping-option">
                    <input type="radio" name="service_type" value="1" checked data-service-type-radio>
                    <span class="checkout-shipping-option__body">
                        <strong>پیشتاز</strong>
                        <span data-service-price="1">ابتدا آدرس را انتخاب کنید</span>
                    </span>
                </label>
                <label class="checkout-shipping-option">
                    <input type="radio" name="service_type" value="5" data-service-type-radio>
                    <span class="checkout-shipping-option__body">
                        <strong>اکسپرس</strong>
                        <span data-service-price="5">ابتدا آدرس را انتخاب کنید</span>
                    </span>
                </label>
                <input type="hidden" name="shipping_method" value="standard">

                <div class="checkout-step__actions">
                    <button type="button" class="checkout-btn-back" data-go-step="1">بازگشت</button>
                    <button type="button" class="checkout-btn-next" data-go-step="3">ادامه به پرداخت</button>
                </div>
            </section>

            {{-- مرحله ۳: پرداخت --}}
            <section class="checkout-step" data-step="3">
                <h2 class="checkout-step__title">بازبینی و پرداخت</h2>

                <div class="checkout-review-items">
                    @foreach($cart->items as $item)
                        <div class="checkout-review-item">
                            <span>{{ $item->product->name }} @if($item->variant)({{ $item->variant->attributeValues->pluck('value')->join('، ') }})@endif × {{ $item->quantity }}</span>
                            <span>{{ number_format($item->lineTotal()) }} تومان</span>
                        </div>
                    @endforeach
                </div>

                <div class="checkout-coupon">
                    <input type="text" id="coupon_code" placeholder="کد تخفیف دارید؟" data-coupon-input>
                    <button type="button" data-apply-coupon>اعمال کد</button>
                </div>
                <div class="checkout-coupon__message" data-coupon-message hidden></div>

                @auth
                    <div class="checkout-payment-method">
                        <h3 class="checkout-payment-method__title">روش پرداخت</h3>
                        <label class="checkout-payment-method__option">
                            <input type="radio" name="payment_method" value="gateway" checked>
                            <span>پرداخت آنلاین (کارت بانکی)</span>
                        </label>
                        <label class="checkout-payment-method__option @if($walletBalance < $cart->total()) is-disabled @endif">
                            <input type="radio" name="payment_method" value="wallet" @if($walletBalance < $cart->total()) disabled @endif>
                            <span>
                                پرداخت از کیف پول
                                <small>(موجودی: {{ number_format($walletBalance) }} تومان{{ $walletBalance < $cart->total() ? ' — کافی نیست' : '' }})</small>
                            </span>
                        </label>
                    </div>
                @endauth

                <div class="checkout-step__actions">
                    <button type="button" class="checkout-btn-back" data-go-step="2">بازگشت</button>
                    <button type="button" class="checkout-btn-pay" data-place-order data-base-total="{{ $cart->total() }}">
                        <span data-pay-amount>پرداخت {{ number_format($cart->total()) }} تومان</span>
                    </button>
                </div>
            </section>
        </div>

        {{-- خلاصه سفارش — همیشه در دسکتاپ نمایان، در موبایل فقط خلاصه کوچک بالای فرم (بخش ۲۰.۱) --}}
        <aside class="checkout-summary">
            <h2 class="checkout-summary__title">خلاصه سفارش ({{ $cart->itemsCount() }} کالا)</h2>
            <div class="checkout-summary__row">
                <span>جمع کالاها</span>
                <span>{{ number_format($cart->total()) }} تومان</span>
            </div>
            <div class="checkout-summary__row">
                <span>هزینه ارسال</span>
                <span data-shipping-cost-display>بعد از انتخاب آدرس</span>
            </div>
            <div class="checkout-summary__row" data-coupon-summary-row hidden>
                <span>تخفیف کد</span>
                <span data-coupon-discount-display>۰ تومان</span>
            </div>
            <div class="checkout-summary__divider"></div>
            <div class="checkout-summary__row checkout-summary__row--total">
                <span>جمع کل</span>
                <span data-summary-total>{{ number_format($cart->total()) }} تومان</span>
            </div>
        </aside>
    </div>
</div>

<script type="application/json" id="checkout-data">
    {!! json_encode(['isAuthenticated' => auth()->check(), 'hasAddresses' => $addresses->isNotEmpty(), 'cartSubtotal' => $cart->total()]) !!}
</script>
@endsection

@push('scripts')
    @vite([
        'resources/js/header.js',
        'resources/js/checkout.js',
        'resources/js/toast.js',
    ])
@endpush
