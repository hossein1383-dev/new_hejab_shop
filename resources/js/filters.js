/**
 * filters.js — مدیریت Bottom Sheet فیلتر در موبایل (بخش ۲۰.۱)
 */
function toggleFiltersSheet(open) {
    document.querySelector('[data-filters-sheet]')?.toggleAttribute('hidden', !open);
    document.querySelector('[data-filters-overlay]')?.toggleAttribute('hidden', !open);
    requestAnimationFrame(() => {
        document.querySelector('[data-filters-sheet]')?.classList.toggle('is-open', open);
    });
    document.body.style.overflow = open ? 'hidden' : '';
}

document.querySelector('[data-filters-open]')?.addEventListener('click', () => toggleFiltersSheet(true));
document.querySelector('[data-filters-close]')?.addEventListener('click', () => toggleFiltersSheet(false));
document.querySelector('[data-filters-overlay]')?.addEventListener('click', () => toggleFiltersSheet(false));

// بخش ۲۰.۱: به‌جای onchange="this.form.submit()" درون‌خطی — چون CSP سایت
// (script-src 'self') هر رویداد جاوااسکریپت درون‌خطی در HTML را مسدود
// می‌کند و در مرورگر بی‌صدا نادیده گرفته می‌شود (نه خطا، نه لاگ).
document.querySelector('[data-sort-select]')?.addEventListener('change', function () {
    this.form.submit();
});

document.querySelectorAll('[data-brand-filter-checkbox]').forEach((checkbox) => {
    checkbox.addEventListener('change', function () {
        this.form.submit();
    });
});
