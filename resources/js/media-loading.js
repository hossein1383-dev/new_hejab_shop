/**
 * media-loading.js — جلوگیری از «پاپ ناگهانی» تصاویر (کارت محصول، گالری
 * صفحه محصول) و بنر هنگام بارگذاری اولیه صفحه. تا تصویر واقعی کاملاً لود
 * نشده، Skeleton (در CSS) نشان داده می‌شود؛ بعد از لود، با Fade نرم
 * جایگزین می‌شود — نه فقط کدرشدن، بلکه واقعاً پنهان تا لحظه آماده‌شدن.
 */

function setupFadeInImage(img, wrapperSelector) {
    const markLoaded = () => {
        img.classList.add('is-loaded');
        wrapperSelector && img.closest(wrapperSelector)?.classList.add('is-loaded');
    };

    // اگر تصویر از Cache مرورگر آمده باشد، رویداد load دیگر شلیک نمی‌شود
    if (img.complete && img.naturalWidth > 0) {
        markLoaded();
    } else {
        img.addEventListener('load', markLoaded, { once: true });
        img.addEventListener('error', markLoaded, { once: true }); // حتی اگر عکس خراب بود، Shimmer برای همیشه نماند
    }
}

document.querySelectorAll('.product-card__image').forEach((img) => {
    setupFadeInImage(img, '.product-card__image-link');
});

document.querySelectorAll('.product-gallery-desktop__main-image').forEach((img) => {
    setupFadeInImage(img, '.product-gallery-desktop__main');
});

document.querySelectorAll('.product-gallery-mobile__slide').forEach((img) => {
    setupFadeInImage(img, '.product-gallery-mobile__track');
});

document.querySelectorAll('.blog-card__image').forEach((img) => {
    setupFadeInImage(img, '.blog-card__image-wrap');
});

document.querySelectorAll('.blog-article__image').forEach((img) => {
    setupFadeInImage(img, '.blog-article__image-wrap');
});

document.querySelectorAll('.blog-carousel__image').forEach((img) => {
    setupFadeInImage(img, '.blog-carousel__image-wrap');
});

/**
 * بنر Hero پس‌زمینه CSS است نه <img>، پس با Image() از قبل بارگذاری
 * می‌شود. کلاس is-loaded فقط بعد از اتمام کامل دانلود اضافه می‌شود — تا
 * آن لحظه CSS فقط Shimmer نشان می‌دهد (نه خودِ عکس با opacity کم، چون
 * مرورگر آن را بلافاصله بعد از دانلود نشان می‌دهد و جلوه بد می‌شود).
 */
const heroBanner = document.querySelector('.hero--banner');
if (heroBanner) {
    const style = getComputedStyle(heroBanner);
    const isMobile = window.matchMedia('(max-width: 1023px)').matches;
    const cssVar = isMobile ? '--hero-bg-mobile' : '--hero-bg-desktop';
    const rawUrl = style.getPropertyValue(cssVar).trim();
    const urlMatch = rawUrl.match(/url\(["']?(.*?)["']?\)/);

    if (urlMatch && urlMatch[1]) {
        const preloadImg = new Image();
        preloadImg.onload = () => heroBanner.classList.add('is-loaded');
        preloadImg.onerror = () => heroBanner.classList.add('is-loaded');
        preloadImg.src = urlMatch[1];
    } else {
        heroBanner.classList.add('is-loaded');
    }
}
