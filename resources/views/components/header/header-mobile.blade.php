{{--
    Header موبایل: Menu (Hamburger) + Logo + Search + Cart (بخش ۲۴).
    این یک Component کاملاً جدا از نسخه دسکتاپ است (نه همان Markup با CSS مخفی)
    چون الگوی Navigation موبایل (Hamburger + Drawer) از نظر رفتاری با
    Mega Menu دسکتاپ کاملاً متفاوت است (بخش ۲۰.۱ بند ب).
--}}
<header class="header-mobile" data-sticky>
    <button type="button" class="header-mobile__menu-btn" aria-label="باز کردن منو" data-menu-toggle>
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
    </button>

<a href="{{ url('/') }}" class="header-mobile__logo">{{ $siteName }}</a>

    <div class="header-mobile__actions">
        <button type="button" class="header-mobile__icon-btn" aria-label="جستجو" data-search-toggle>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                <path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>

        <a href="{{ url('/cart') }}" class="header-mobile__icon-btn" aria-label="سبد خرید">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M6 6h15l-1.5 9h-12z" stroke="currentColor" stroke-width="2"/>
                <circle cx="9" cy="20" r="1.5" fill="currentColor"/>
                <circle cx="18" cy="20" r="1.5" fill="currentColor"/>
            </svg>
            @if($cartItemsCount > 0)
                <span class="header-mobile__badge">{{ $cartItemsCount }}</span>
            @endif
        </a>
    </div>
</header>

{{-- Bottom Sheet برای جستجو (بخش ۲۹: Mobile Search) --}}
<div class="header-mobile__search-sheet" data-search-sheet hidden>
    <form action="{{ url('/products') }}" method="get" class="header-mobile__search-form">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="جستجوی محصول..." autofocus>
        <button type="button" data-search-close aria-label="بستن">✕</button>
    </form>
</div>

{{-- Drawer منوی موبایل (بخش ۲۹.۱ بند ۳: کنترل و آزادی کاربر — راه بازگشت واضح) --}}
<div class="header-mobile__drawer" data-menu-drawer hidden>
    <div class="header-mobile__drawer-header">
        <span>دسته‌بندی‌ها</span>
        <button type="button" data-menu-close aria-label="بستن منو">✕</button>
    </div>
    <nav class="header-mobile__drawer-nav">
        @auth
            <a href="{{ url('/account') }}">حساب کاربری من</a>
            <a href="{{ url('/account/orders') }}">سفارش‌های من</a>
            <a href="{{ url('/wishlist') }}">علاقه‌مندی‌های من</a>
        @else
            <a href="{{ url('/login') }}">ورود / ثبت‌نام</a>
        @endauth
        <a href="{{ url('/blog') }}">وبلاگ</a>
        @foreach($categories as $category)
            <a href="{{ url('/category/' . $category->slug) }}">{{ $category->name }}</a>
        @endforeach
        @auth
            <button type="button" class="header-mobile__drawer-logout" data-logout-btn>خروج از حساب</button>
        @endauth
    </nav>
</div>
<div class="header-mobile__overlay" data-menu-overlay hidden></div>
