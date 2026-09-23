/**
 * admin.js — باز/بسته‌کردن Sidebar در موبایل (بخش ۲۰.۱)
 */
function toggleAdminSidebar(open) {
    document.querySelector('[data-admin-sidebar]')?.classList.toggle('is-open', open);
    document.querySelector('[data-admin-sidebar-overlay]')?.toggleAttribute('hidden', !open);
}

document.querySelector('[data-admin-sidebar-toggle]')?.addEventListener('click', () => toggleAdminSidebar(true));
document.querySelector('[data-admin-sidebar-overlay]')?.addEventListener('click', () => toggleAdminSidebar(false));

/**
 * جایگزین امن onsubmit="return confirm(...)" و onchange="this.form.submit()"
 * درون‌خطی — CSP سایت (script-src 'self') هر رویداد جاوااسکریپت نوشته‌شده
 * مستقیم در HTML را بی‌صدا نادیده می‌گیرد (نه خطا در Console، نه در Log)،
 * پس این‌ها باید این‌جا و به‌صورت فایل خارجی مدیریت شوند.
 */
document.addEventListener('submit', (event) => {
    const message = event.target.dataset?.confirm;
    if (message && !confirm(message)) {
        event.preventDefault();
    }
});

document.querySelectorAll('select[data-auto-submit]').forEach((select) => {
    select.addEventListener('change', function () {
        this.form.submit();
    });
});
