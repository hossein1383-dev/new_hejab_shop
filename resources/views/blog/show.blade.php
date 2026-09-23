@extends('layouts.store')

@section('title', $post->title)

@push('styles')
    @vite([
        'resources/css/components/header.css',
        'resources/css/components/media-loading.css',
        'resources/css/pages/blog.css',
    ])
@endpush

@section('body')
<div class="blog-article">
    <nav class="breadcrumb">
        <a href="{{ url('/') }}">خانه</a>
        <span>/</span>
        <a href="{{ url('/blog') }}">وبلاگ</a>
        <span>/</span>
        <span>{{ $post->title }}</span>
    </nav>

    <a href="{{ url('/blog') }}" class="blog-article__back">← بازگشت به وبلاگ</a>

    <h1 class="blog-article__title">{{ $post->title }}</h1>
    <span class="blog-article__date">{{ \App\Support\JalaliDate::format($post->published_at, 'j M Y') }}</span>

    @if($post->featured_image)
        <div class="blog-article__image-wrap">
            <img
                src="{{ asset('storage/' . $post->featured_image) }}"
                alt="{{ $post->title }}"
                loading="lazy"
                class="blog-article__image"
            >
        </div>
    @endif

    <div class="blog-article__content">{!! nl2br(e($post->content)) !!}</div>

    @if($relatedPosts->isNotEmpty())
        <div class="blog-article__related">
            <h2 class="blog-article__related-title">مطالب مرتبط</h2>
            <div class="blog-grid">
                @foreach($relatedPosts as $related)
                    <a href="{{ url('/blog/' . $related->slug) }}" class="blog-card">
                        <div class="blog-card__image-wrap">
                            <img
                                src="{{ $related->featured_image ? asset('storage/' . $related->featured_image) : asset('images/placeholder-product.svg') }}"
                                alt="{{ $related->title }}"
                                loading="lazy"
                                class="blog-card__image"
                            >
                        </div>
                        <div class="blog-card__body">
                            <h3 class="blog-card__title">{{ $related->title }}</h3>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
    @vite(['resources/js/header.js', 'resources/js/cart.js', 'resources/js/media-loading.js'])
@endpush
