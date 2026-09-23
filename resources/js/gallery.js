/**
 * gallery.js — بخش ۱۱
 * موبایل: همگام‌سازی نقطه‌های صفحه با Scroll افقی (Swipe).
 * دسکتاپ: کلیک روی Thumbnail، تصویر اصلی را عوض می‌کند.
 */

const track = document.querySelector('[data-gallery-track]');
const dots = document.querySelectorAll('[data-gallery-dots] .product-gallery-mobile__dot');

if (track && dots.length > 0) {
    track.addEventListener('scroll', () => {
        const index = Math.round(track.scrollLeft / track.clientWidth);
        dots.forEach((dot, i) => dot.classList.toggle('is-active', i === index));
    }, { passive: true });
}

document.querySelectorAll('[data-gallery-thumb]').forEach((thumb) => {
    thumb.addEventListener('click', () => {
        document.querySelectorAll('[data-gallery-thumb]').forEach((t) => t.classList.remove('is-active'));
        thumb.classList.add('is-active');

        const mainImage = document.querySelector('[data-gallery-main]');
        if (mainImage) {
            mainImage.src = thumb.dataset.imageUrl;
        }
    });
});
