/**
 * reveal.js — با IntersectionObserver، هر عنصر با کلاس reveal-on-scroll
 * وقتی وارد دید کاربر شد، کلاس is-visible می‌گیرد (بخش ۲۹: تجربه کاربری).
 * چون فقط یک‌بار لازم است ظاهر شود، بعد از دیده‌شدن از Observer خارج می‌شود
 * (نه اینکه با اسکرول به بالا دوباره محو شود — رفتار مرسوم در سایت‌های برند).
 */

const revealElements = document.querySelectorAll('.reveal-on-scroll');

if (revealElements.length > 0 && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                obs.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.15,
        rootMargin: '0px 0px -40px 0px',
    });

    revealElements.forEach((el) => observer.observe(el));
} else {
    // مرورگر خیلی قدیمی یا بدون پشتیبانی IntersectionObserver — همه‌چیز مستقیم نشان داده شود
    revealElements.forEach((el) => el.classList.add('is-visible'));
}
