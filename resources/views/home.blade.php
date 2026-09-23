@extends('layouts.store')

@section('title', 'فروشگاه اینترنتی — صفحه اصلی')

@push('styles')
    @vite([
        'resources/css/components/header.css',
        'resources/css/components/product-card.css',
        'resources/css/components/toast.css',
        'resources/css/components/reveal.css',
        'resources/css/components/media-loading.css',
        'resources/css/pages/home.css',
    ])
@endpush

@section('body')
    {{-- Hero — از بنرهای مدیریت‌شده در پنل استفاده می‌شود؛ در نبود بنر، محتوای ثابت پیش‌فرض نمایش داده می‌شود --}}
    @if($banners->isNotEmpty())
        <section class="hero hero--banner" style="--hero-bg-desktop: url('{{ asset('storage/' . $banners->first()->desktopImage()) }}'); --hero-bg-mobile: url('{{ asset('storage/' . $banners->first()->mobileImage()) }}');">
            @if($banners->first()->link_url)
                <a href="{{ $banners->first()->link_url }}" class="hero__banner-link" aria-label="{{ $banners->first()->title }}"></a>
            @endif
        </section>
    @else
        <section class="hero">
            <div class="hero__content">
                <h1>خرید آسان، ارسال سریع</h1>
                <p>جدیدترین محصولات را با بهترین قیمت از فروشگاه ما تهیه کنید.</p>
                <a href="{{ url('/products') }}" class="hero__cta">مشاهده محصولات</a>
            </div>
        </section>
    @endif

    {{-- دسته‌بندی‌های محبوب --}}
    @if($categories->isNotEmpty())
        <section class="home-section reveal-on-scroll">
            <h2 class="home-section__title">دسته‌بندی‌ها</h2>
            <div class="category-scroller">
                @foreach($categories as $category)
                    <a href="{{ url('/category/' . $category->slug) }}" class="category-pill">
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- محصولات ویژه — تم گرادیانت گرم برای جلب توجه بیشتر --}}
    @if($featuredProducts->isNotEmpty())
        <section class="home-section home-section--featured reveal-on-scroll">
            <h2 class="home-section__title home-section__title--on-dark">🔥 محصولات ویژه</h2>
            <div class="product-grid">
                @foreach($featuredProducts as $product)
                    @include('components.product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif

    {{-- بهترین کالاها — بر اساس امتیاز واقعی مشتری‌ها --}}
    @if($topRatedProducts->isNotEmpty())
        <section class="home-section reveal-on-scroll">
            <h2 class="home-section__title">⭐ بهترین کالاها از نگاه مشتری‌ها</h2>
            <div class="product-grid">
                @foreach($topRatedProducts as $product)
                    @include('components.product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif

    {{-- جدیدترین‌ها --}}
    @if($newProducts->isNotEmpty())
        <section class="home-section reveal-on-scroll">
            <h2 class="home-section__title">✨ جدیدترین محصولات</h2>
            <div class="product-grid">
                @foreach($newProducts as $product)
                    @include('components.product-card', ['product' => $product, 'badgeType' => 'new'])
                @endforeach
            </div>
        </section>
    @endif

    {{-- پرفروش‌ترین‌ها — نشان شماره‌ای رتبه --}}
    @if($bestSellers->isNotEmpty())
        <section class="home-section reveal-on-scroll">
            <h2 class="home-section__title">🏆 پرفروش‌ترین‌ها</h2>
            <div class="product-grid">
                @foreach($bestSellers as $index => $product)
                    @include('components.product-card', ['product' => $product, 'badgeType' => 'bestseller', 'badgeNumber' => $index + 1])
                @endforeach
            </div>
        </section>
    @endif

    {{-- وبلاگ — بین محصولات و ردیف‌های دسته‌بندی؛ اسکرول افقی خودکار با
         توقف روی Hover/لمس، به‌علاوه دکمه‌های قبلی/بعدی دستی --}}
    @if($latestBlogPosts->isNotEmpty())
        <section class="home-section reveal-on-scroll">
            <div class="home-section__header">
                <h2 class="home-section__title">📰 آخرین مطالب وبلاگ</h2>
                <a href="{{ url('/blog') }}" class="home-section__more">مشاهده همه ←</a>
            </div>
            <div class="blog-carousel" data-blog-carousel>
                @if($latestBlogPosts->count() > 1)
                    <button type="button" class="blog-carousel__nav blog-carousel__nav--prev" data-carousel-prev aria-label="مطلب قبلی">‹</button>
                @endif
                <div class="blog-carousel__track" data-carousel-track>
                    @foreach($latestBlogPosts as $post)
                        <a href="{{ url('/blog/' . $post->slug) }}" class="blog-carousel__card">
                            <div class="blog-carousel__image-wrap">
                                <img
                                    src="{{ $post->featured_image ? asset('storage/' . $post->featured_image) : asset('images/placeholder-product.svg') }}"
                                    alt="{{ $post->title }}"
                                    loading="lazy"
                                    class="blog-carousel__image"
                                >
                            </div>
                            <div class="blog-carousel__body">
                                <h3 class="blog-carousel__title">{{ $post->title }}</h3>
                                <span class="blog-carousel__date">{{ \App\Support\JalaliDate::format($post->published_at, 'j M Y') }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
                @if($latestBlogPosts->count() > 1)
                    <button type="button" class="blog-carousel__nav blog-carousel__nav--next" data-carousel-next aria-label="مطلب بعدی">›</button>
                @endif
            </div>
        </section>
    @endif

    {{-- ردیف هر دسته‌بندی: ۶ محصول + مشاهده بیشتر --}}
    @foreach($categoryRows as $row)
        <section class="home-section reveal-on-scroll">
            <div class="home-section__header">
                <h2 class="home-section__title">{{ $row['category']->name }}</h2>
                <a href="{{ url('/category/' . $row['category']->slug) }}" class="home-section__more">مشاهده بیشتر ←</a>
            </div>
            <div class="product-grid">
                @foreach($row['products'] as $product)
                    @include('components.product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endforeach

    @if($featuredProducts->isEmpty() && $newProducts->isEmpty() && $bestSellers->isEmpty() && $topRatedProducts->isEmpty() && $categoryRows->isEmpty())
        <section class="home-section reveal-on-scroll">
            <div class="empty-state">
                <p>فعلاً محصولی برای نمایش وجود ندارد.</p>
            </div>
        </section>
    @endif
@endsection

@push('scripts')
    @vite([
        'resources/js/header.js',
        'resources/js/cart.js',
        'resources/js/wishlist.js',
        'resources/js/toast.js',
        'resources/js/reveal.js',
        'resources/js/media-loading.js',
        'resources/js/blog-carousel.js',
    ])
@endpush
