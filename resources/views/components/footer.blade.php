<footer class="site-footer">
    @if(!empty($footerSettings['working_days']) || !empty($footerSettings['working_hours']))
        <div class="site-footer__hours">
            @if(!empty($footerSettings['working_days']))
                <div class="site-footer__hours-item">
                    <h4>روزهای کاری</h4>
                    <p>{{ $footerSettings['working_days'] }}</p>
                </div>
            @endif
            @if(!empty($footerSettings['working_hours']))
                <div class="site-footer__hours-item">
                    <h4>ساعت کاری</h4>
                    <p>{{ $footerSettings['working_hours'] }}</p>
                </div>
            @endif
        </div>
    @endif

    <div class="site-footer__intro">
        <h3 class="site-footer__brand">{{ $footerSettings['site_name'] ?? config('app.name') }}</h3>
        @if(!empty($footerSettings['site_description']))
            <p class="site-footer__desc">{{ $footerSettings['site_description'] }}</p>
        @endif

        @if(!empty($footerSettings['instagram_url']) || !empty($footerSettings['eitaa_url']) || !empty($footerSettings['telegram_url']) || !empty($footerSettings['whatsapp_url']))
            <div class="site-footer__social">
                @if(!empty($footerSettings['instagram_url']))
                    <a href="{{ $footerSettings['instagram_url'] }}" target="_blank" rel="noopener" class="site-footer__social-icon" aria-label="اینستاگرام">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>
                    </a>
                @endif
                @if(!empty($footerSettings['eitaa_url']))
                    <a href="{{ $footerSettings['eitaa_url'] }}" target="_blank" rel="noopener" class="site-footer__social-icon" aria-label="ایتا">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                @endif
                @if(!empty($footerSettings['telegram_url']))
                    <a href="{{ $footerSettings['telegram_url'] }}" target="_blank" rel="noopener" class="site-footer__social-icon" aria-label="تلگرام">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 4L2.5 11.5l6 2M21 4l-3 16-8.5-6.5M21 4L8.5 13.5" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                    </a>
                @endif
                @if(!empty($footerSettings['whatsapp_url']))
                    <a href="{{ $footerSettings['whatsapp_url'] }}" target="_blank" rel="noopener" class="site-footer__social-icon" aria-label="واتساپ">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 20l1.3-4.5A7.5 7.5 0 1 1 9 18.5L4 20z" stroke="currentColor" stroke-width="2"/></svg>
                    </a>
                @endif
            </div>
        @endif
    </div>

    <div class="site-footer__grid">
        @if($footerCategories->isNotEmpty())
            <div class="site-footer__col">
                <h4 class="site-footer__heading">دسته‌بندی‌ها</h4>
                <ul class="site-footer__links">
                    @foreach($footerCategories as $category)
                        <li><a href="{{ url('/category/' . $category->slug) }}">{{ $category->name }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($footerPages->isNotEmpty())
            <div class="site-footer__col">
                <h4 class="site-footer__heading">درباره فروشگاه</h4>
                <ul class="site-footer__links">
                    @foreach($footerPages as $page)
                        <li><a href="{{ url('/page/' . $page->slug) }}">{{ $page->title }}</a></li>
                    @endforeach
                    <li><a href="{{ url('/blog') }}">وبلاگ</a></li>
                </ul>
            </div>
        @endif

        @if(!empty($footerSettings['contact_email']) || !empty($footerSettings['contact_phone']) || !empty($footerSettings['contact_address']))
            <div class="site-footer__col">
                <h4 class="site-footer__heading">تماس با ما</h4>
                <ul class="site-footer__contact">
                    @if(!empty($footerSettings['contact_address']))
                        <li>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s7-6.5 7-12a7 7 0 1 0-14 0c0 5.5 7 12 7 12z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="9" r="2.5" stroke="currentColor" stroke-width="2"/></svg>
                            <span>{{ $footerSettings['contact_address'] }}</span>
                        </li>
                    @endif
                    @if(!empty($footerSettings['contact_phone']))
                        <li>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 3a2 2 0 0 1-.5 2.1L8 10a16 16 0 0 0 6 6l1.2-1.3a2 2 0 0 1 2.1-.5c1 .3 2 .5 3 .7a2 2 0 0 1 1.7 2z" stroke="currentColor" stroke-width="2"/></svg>
                            <span>شماره تماس: <span dir="ltr">{{ $footerSettings['contact_phone'] }}</span></span>
                        </li>
                    @endif
                    @if(!empty($footerSettings['contact_email']))
                        <li>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2" stroke="currentColor" stroke-width="2"/><path d="M2 7l10 6 10-6" stroke="currentColor" stroke-width="2"/></svg>
                            <span dir="ltr">{{ $footerSettings['contact_email'] }}</span>
                        </li>
                    @endif
                </ul>
            </div>
        @endif

        @if(!empty($footerSettings['enamad_id']) && !empty($footerSettings['enamad_code']))
            <div class="site-footer__col site-footer__col--enamad">
                <a referrerpolicy="origin" target="_blank" href="https://trustseal.enamad.ir/?id={{ $footerSettings['enamad_id'] }}&Code={{ $footerSettings['enamad_code'] }}">
                    <img referrerpolicy="origin" src="https://trustseal.enamad.ir/logo.aspx?id={{ $footerSettings['enamad_id'] }}&Code={{ $footerSettings['enamad_code'] }}" alt="نماد اعتماد الکترونیکی" class="site-footer__enamad">
                </a>
            </div>
        @endif
    </div>

    <div class="site-footer__bottom">
        <span>© {{ now()->format('Y') }} {{ $footerSettings['site_name'] ?? config('app.name') }} — همه حقوق محفوظ است.</span>
    </div>
</footer>
