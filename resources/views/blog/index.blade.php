@extends('layouts.store')

@section('title', 'وبلاگ')

@push('styles')
    @vite([
        'resources/css/components/header.css',
        'resources/css/components/media-loading.css',
        'resources/css/pages/blog.css',
    ])
@endpush

@section('body')
<div class="blog-page">
    <nav class="breadcrumb">
        <a href="{{ url('/') }}">خانه</a>
        <span>/</span>
        <span>وبلاگ</span>
    </nav>

    <h1 class="blog-page__title">وبلاگ</h1>

    @if($posts->isNotEmpty())
        <div class="blog-grid">
            @foreach($posts as $post)
                <a href="{{ url('/blog/' . $post->slug) }}" class="blog-card">
                    <div class="blog-card__image-wrap">
                        <img
                            src="{{ $post->featured_image ? asset('storage/' . $post->featured_image) : asset('images/placeholder-product.svg') }}"
                            alt="{{ $post->title }}"
                            loading="lazy"
                            class="blog-card__image"
                        >
                    </div>
                    <div class="blog-card__body">
                        <h3 class="blog-card__title">{{ $post->title }}</h3>
                        @if($post->excerpt)
                            <p class="blog-card__excerpt">{{ $post->excerpt }}</p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
        <div class="blog-page__pagination">{{ $posts->links() }}</div>
    @else
        <div class="blog-page__empty">
            <p>هنوز مطلبی منتشر نشده است.</p>
        </div>
    @endif
</div>
@endsection

@push('scripts')
    @vite(['resources/js/header.js', 'resources/js/cart.js', 'resources/js/media-loading.js'])
@endpush
