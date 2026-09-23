{{--
    فیلتر دسکتاپ: Sidebar ثابت کنار Grid محصولات (بخش ۲۰.۱) — برخلاف موبایل،
    همیشه در معرض دید است چون فضای افقی کافی وجود دارد و کاربر دسکتاپ عادت
    دارد فیلترها را کنار نتایج ببیند، نه پشت یک دکمه پنهان.
--}}
<aside class="filters-desktop">
    <form method="get" class="filters-desktop__form">
        <div class="filters-desktop__section">
            <h3>برند</h3>
            @forelse($facets['brands'] as $brand)
                <label class="filters-desktop__checkbox">
                    <input type="checkbox" name="brands[]" value="{{ $brand->id }}"
                        data-brand-filter-checkbox
                        @checked(in_array($brand->id, (array) ($appliedFilters['brand_ids'] ?? [])))>
                    {{ $brand->name }}
                </label>
            @empty
                <p class="filters-desktop__empty">برندی موجود نیست</p>
            @endforelse
        </div>

        <div class="filters-desktop__section">
            <h3>بازه قیمت (تومان)</h3>
            <div class="filters-desktop__price-row">
                <input type="number" name="min_price" placeholder="از" value="{{ $appliedFilters['min_price'] }}">
                <input type="number" name="max_price" placeholder="تا" value="{{ $appliedFilters['max_price'] }}">
            </div>
            <button type="submit" class="filters-desktop__apply-price">اعمال</button>
        </div>

        @if(($appliedFilters['brand_ids'] ?? []) || $appliedFilters['min_price'] || $appliedFilters['max_price'])
            <a href="{{ url()->current() }}" class="filters-desktop__reset">حذف همه فیلترها</a>
        @endif
    </form>
</aside>
