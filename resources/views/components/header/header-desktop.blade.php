{{--
    Header دسکتاپ: Logo + Categories (Mega Menu) + Search + Wishlist + Cart + Account (بخش ۲۴).
--}}
<header class="header-desktop" data-sticky>
    <div class="header-desktop__row">
        <a href="{{ url('/') }}" class="header-desktop__logo">{{ $siteName }}</a>

        <nav class="header-desktop__categories">
            @foreach($categories->take(6) as $category)
                <div class="header-desktop__category-item" data-mega-menu-trigger>
                    <a href="{{ url('/category/' . $category->slug) }}">{{ $category->name }}</a>

                    @if($category->children->isNotEmpty())
                        <div class="header-desktop__mega-menu">
                            @foreach($category->children as $child)
                                <a href="{{ url('/category/' . $child->slug) }}">{{ $child->name }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </nav>

        <form action="{{ url('/products') }}" method="get" class="header-desktop__search">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="جستجوی محصول، برند یا دسته‌بندی...">
            <button type="submit" aria-label="جستجو">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                    <path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </form>

        <div class="header-desktop__actions">
            <a href="{{ url('/wishlist') }}" class="header-desktop__icon-btn" aria-label="علاقه‌مندی‌ها">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 20s-7-4.35-9.5-8.5C.7 8 2 4.5 5.5 4A5 5 0 0 1 12 7a5 5 0 0 1 6.5-3c3.5.5 4.8 4 3 7.5C19 15.65 12 20 12 20z" stroke="currentColor" stroke-width="2"/>
                </svg>
            </a>

            <a href="{{ url('/cart') }}" class="header-desktop__icon-btn" aria-label="سبد خرید">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 6h15l-1.5 9h-12z" stroke="currentColor" stroke-width="2"/>
                    <circle cx="9" cy="20" r="1.5" fill="currentColor"/>
                    <circle cx="18" cy="20" r="1.5" fill="currentColor"/>
                </svg>
                @if($cartItemsCount > 0)
                    <span class="header-desktop__badge">{{ $cartItemsCount }}</span>
                @endif
            </a>

            @auth
                <a href="{{ url('/account') }}" class="header-desktop__icon-btn" aria-label="حساب کاربری">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                        <path d="M4 20c0-4 4-6 8-6s8 2 8 6" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </a>
                <button type="button" class="header-desktop__icon-btn" data-logout-btn aria-label="خروج از حساب">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M16 17l5-5-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M21 12H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            @else
                <a href="{{ url('/login') }}" class="header-desktop__account-link">ورود / ثبت‌نام</a>
            @endauth
        </div>
    </div>
</header>
