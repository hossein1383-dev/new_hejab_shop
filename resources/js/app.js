/**
 * app.js — نقطه ورود مشترک JavaScript (بخش ۲۰: JS باید Modular و Feature-based باشد)
 * شامل یک تابع کمکی برای فراخوانی API با فرمت یکدست بخش ۲۱ (Success/Error Response).
 */

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

/**
 * فراخوانی API با هدرهای استاندارد و پارس خودکار پاسخ JSON.
 * در صورت خطای Validation (422) یا هر خطای دیگر، یک Error با پیام و errors پرتاب می‌شود
 * تا فراخوان بتواند پیام را کنار فیلد مربوطه نمایش دهد (بخش ۲۹.۱: کمک به تشخیص خطا).
 */
window.apiFetch = async function apiFetch(url, options = {}) {
    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken ?? '',
            ...(options.headers ?? {}),
        },
        ...options,
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const error = new Error(data.message || 'خطایی رخ داد. لطفاً دوباره تلاش کنید.');
        error.status = response.status;
        error.errors = data.errors ?? {};
        throw error;
    }

    return data;
};

/** نمایش خطای Validation کنار فیلد مربوطه (بخش ۲۹.۱: نمایش خطا نزدیک فیلد). */
window.showFieldErrors = function showFieldErrors(form, errors) {
    form.querySelectorAll('[data-error-for]').forEach((el) => {
        el.textContent = '';
        el.hidden = true;
    });

    Object.entries(errors).forEach(([field, messages]) => {
        const el = form.querySelector(`[data-error-for="${field}"]`);
        if (el) {
            el.textContent = messages[0];
            el.hidden = false;
        }
    });
};
