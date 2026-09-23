{{--
    فیلتر موبایل: Bottom Sheet/Drawer تمام‌صفحه (بخش ۲۰.۱).
    باز/بسته‌شدن با JS (filters.js)؛ اعمال فیلتر با Submit فرم معمولی
    (Reload صفحه با Query String) — طبق بخش ۱۰ فقط «در صورت مفید بودن»
    باید بدون Reload باشد؛ برای اولین نسخه، سادگی و پایداری اولویت دارد.
--}}
<button type="button" class="filters-mobile__open-btn" data-filters-open>
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M4 6h16M7 12h10M10 18h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    </svg>
    فیلتر و مرتب‌سازی
</button>

<div class="filters-mobile__sheet" data-filters-sheet hidden>
    <div class="filters-mobile__header">
        <span>فیلتر و مرتب‌سازی</span>
        <button type="button" data-filters-close aria-label="بستن">✕</button>
    </div>

    <form method="get" class="filters-mobile__form">
        <div class="filters-mobile__section">
            <h3>مرتب‌سازی</h3>
            <select name="sort">
                <option value="newest" @selected($appliedFilters['sort'] === 'newest')>جدیدترین</option>
                <option value="cheapest" @selected($appliedFilters['sort'] === 'cheapest')>ارزان‌ترین</option>
                <option value="expensive" @selected($appliedFilters['sort'] === 'expensive')>گران‌ترین</option>
                <option value="bestseller" @selected($appliedFilters['sort'] === 'bestseller')>پرفروش‌ترین</option>
            </select>
        </div>

        @if($facets['brands']->isNotEmpty())
            <div class="filters-mobile__section">
                <h3>برند</h3>
                @foreach($facets['brands'] as $brand)
                    <label class="filters-mobile__checkbox">
                        <input type="checkbox" name="brands[]" value="{{ $brand->id }}"
                            @checked(in_array($brand->id, (array) ($appliedFilters['brand_ids'] ?? [])))>
                        {{ $brand->name }}
                    </label>
                @endforeach
            </div>
        @endif

        <div class="filters-mobile__section">
            <h3>بازه قیمت (تومان)</h3>
            <div class="filters-mobile__price-row">
                <input type="number" name="min_price" placeholder="از" value="{{ $appliedFilters['min_price'] }}">
                <input type="number" name="max_price" placeholder="تا" value="{{ $appliedFilters['max_price'] }}">
            </div>
        </div>

        <div class="filters-mobile__footer">
            <a href="{{ url()->current() }}" class="filters-mobile__reset">حذف فیلترها</a>
            <button type="submit" class="filters-mobile__apply">نمایش نتایج</button>
        </div>
    </form>
</div>
<div class="filters-mobile__overlay" data-filters-overlay hidden></div>
