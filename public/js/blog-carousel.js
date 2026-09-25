/**
 * blog-carousel.js — اسکرول افقی خودکار ردیف وبلاگ صفحه اصلی، با توقف
 * روی Hover/لمس و دکمه‌های قبلی/بعدی دستی (بخش ۲۹ و ۳۷).
 *
 * تصمیم طراحی: کاملاً خودکار و بدون امکان توقف برای کاربر، هم برای
 * Accessibility مشکل‌ساز است (کاربرانی که به حرکت حساسیت دارند) و هم اگر
 * کاربر دقیق در حال خواندن باشد آزاردهنده می‌شود — پس با ورود ماوس/لمس
 * متوقف و با خروج دوباره از سر گرفته می‌شود.
 */
document.querySelectorAll('[data-blog-carousel]').forEach((carousel) => {
    const track = carousel.querySelector('[data-carousel-track]');
    const prevBtn = carousel.querySelector('[data-carousel-prev]');
    const nextBtn = carousel.querySelector('[data-carousel-next]');
    if (!track) return;

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const AUTO_ADVANCE_MS = 4000;
    let autoAdvanceTimer = null;

    function scrollByOneCard(direction) {
        const card = track.querySelector('.blog-carousel__card');
        if (!card) return;
        const distance = (card.offsetWidth + 12) * direction; // 12px = gap
        track.scrollBy({ left: distance, behavior: 'smooth' });
    }

    function advance() {
        const isAtEnd = track.scrollLeft <= -(track.scrollWidth - track.clientWidth - 5) || track.scrollLeft >= (track.scrollWidth - track.clientWidth - 5);
        // چون صفحه RTL است، جهت اسکرول منفی به‌جلو می‌رود؛ اگر به انتها رسیدیم، به ابتدا برمی‌گردیم
        if (Math.abs(track.scrollLeft) + track.clientWidth >= track.scrollWidth - 5) {
            track.scrollTo({ left: 0, behavior: 'smooth' });
        } else {
            scrollByOneCard(1);
        }
    }

    function startAutoAdvance() {
        if (prefersReducedMotion || track.querySelectorAll('.blog-carousel__card').length <= 1) return;
        stopAutoAdvance();
        autoAdvanceTimer = setInterval(advance, AUTO_ADVANCE_MS);
    }

    function stopAutoAdvance() {
        if (autoAdvanceTimer) clearInterval(autoAdvanceTimer);
    }

    carousel.addEventListener('mouseenter', stopAutoAdvance);
    carousel.addEventListener('mouseleave', startAutoAdvance);
    carousel.addEventListener('touchstart', stopAutoAdvance, { passive: true });
    carousel.addEventListener('touchend', () => setTimeout(startAutoAdvance, AUTO_ADVANCE_MS));

    prevBtn?.addEventListener('click', () => {
        stopAutoAdvance();
        scrollByOneCard(1);
        startAutoAdvance();
    });

    nextBtn?.addEventListener('click', () => {
        stopAutoAdvance();
        scrollByOneCard(-1);
        startAutoAdvance();
    });

    startAutoAdvance();
});