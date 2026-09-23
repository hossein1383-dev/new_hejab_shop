@extends('account.layout')

@section('title', 'علاقه‌مندی‌های من')
@section('page-identifier', 'wishlist')

@push('styles')
    @vite(['resources/css/components/header.css', 'resources/css/components/product-card.css', 'resources/css/components/media-loading.css', 'resources/css/pages/account.css'])
@endpush

@section('account_content')
<h1 class="account-page__title">علاقه‌مندی‌های من</h1>

@if($products->isNotEmpty())
    @php $wishlistProductIds = $products->pluck('id')->all(); @endphp
    <div class="product-grid">
        @foreach($products as $product)
            @include('components.product-card', ['product' => $product])
        @endforeach
    </div>
@else
    <div class="account-empty">
        <p>هنوز محصولی به علاقه‌مندی‌ها اضافه نکرده‌اید.</p>
        <a href="{{ url('/products') }}">مشاهده محصولات</a>
    </div>
@endif
@endsection

@push('scripts')
    @vite(['resources/js/wishlist.js', 'resources/js/cart.js', 'resources/js/media-loading.js'])
@endpush
