{{--
    بازنویسی View پیش‌فرض Pagination لاراول — بخش ۲۲. پیش‌فرض لاراول برای
    Tailwind طراحی شده (آیکون‌های SVG بدون کلاس‌های محدودکننده اندازه)، ولی
    این پروژه از Tailwind استفاده نمی‌کند، پس آیکون‌ها در سایز خام/غول‌پیکر
    نمایش داده می‌شدند. با گذاشتن این فایل در همین مسیر، لاراول خودکار
    به‌جای View داخلی خودش از همین استفاده می‌کند — نیازی به تغییر هیچ
    Controller/Blade دیگری نیست، همه‌جای سایت که ()->links می‌زند اصلاح می‌شود.
--}}
@if($paginator->hasPages())
    <nav class="app-pagination" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <ul class="app-pagination__list">
            {{-- قبلی --}}
            @if($paginator->onFirstPage())
                <li class="app-pagination__item app-pagination__item--disabled" aria-disabled="true">
                    <span class="app-pagination__link">‹</span>
                </li>
            @else
                <li class="app-pagination__item">
                    <a class="app-pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹</a>
                </li>
            @endif

            {{-- شماره صفحات --}}
            @foreach($elements as $element)
                @if(is_string($element))
                    <li class="app-pagination__item app-pagination__item--dots"><span class="app-pagination__link">{{ $element }}</span></li>
                @endif

                @if(is_array($element))
                    @foreach($element as $page => $url)
                        @if($page == $paginator->currentPage())
                            <li class="app-pagination__item app-pagination__item--active" aria-current="page">
                                <span class="app-pagination__link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="app-pagination__item">
                                <a class="app-pagination__link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- بعدی --}}
            @if($paginator->hasMorePages())
                <li class="app-pagination__item">
                    <a class="app-pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next">›</a>
                </li>
            @else
                <li class="app-pagination__item app-pagination__item--disabled" aria-disabled="true">
                    <span class="app-pagination__link">›</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
