/**
 * header.js — تعاملات Header موبایل (بخش ۲۹: Bottom Sheet/Drawer) + Sticky Shadow
 */

function toggleDrawer(open) {
    document.querySelector('[data-menu-drawer]')?.toggleAttribute('hidden', !open);
    document.querySelector('[data-menu-overlay]')?.toggleAttribute('hidden', !open);
    document.querySelector('[data-menu-drawer]')?.classList.toggle('is-open', open);
    document.body.style.overflow = open ? 'hidden' : '';
}

function toggleSearchSheet(open) {
    const sheet = document.querySelector('[data-search-sheet]');
    if (!sheet) return;
    sheet.toggleAttribute('hidden', !open);
    // یک فریم صبر می‌کنیم تا transform Transition واقعاً اجرا شود
    requestAnimationFrame(() => sheet.classList.toggle('is-open', open));
    if (open) {
        sheet.querySelector('input')?.focus();
    }
}

document.querySelector('[data-menu-toggle]')?.addEventListener('click', () => toggleDrawer(true));
document.querySelector('[data-menu-close]')?.addEventListener('click', () => toggleDrawer(false));
document.querySelector('[data-menu-overlay]')?.addEventListener('click', () => toggleDrawer(false));

document.querySelector('[data-search-toggle]')?.addEventListener('click', () => toggleSearchSheet(true));
document.querySelector('[data-search-close]')?.addEventListener('click', () => toggleSearchSheet(false));

// Shadow ظریف هنگام Scroll (بخش ۲۴)
const headers = document.querySelectorAll('.header-mobile, .header-desktop');
window.addEventListener('scroll', () => {
    const scrolled = window.scrollY > 4;
    headers.forEach((header) => header.classList.toggle('is-scrolled', scrolled));
}, { passive: true });

// دکمه خروج از حساب — بخش ۵۰. حالا در هدر (موبایل و دسکتاپ) هم هست، نه
// فقط صفحه پروفایل.
document.querySelectorAll('[data-logout-btn]').forEach((btn) => {
    btn.addEventListener('click', async () => {
        try {
            await window.apiFetch('/auth/logout', { method: 'POST' });
            window.location.href = '/';
        } catch (error) {
            window.showToast?.(error.message || 'خروج ممکن نشد.', 'error');
        }
    });
});
