/**
 * wishlist.js — افزودن/حذف علاقه‌مندی از روی ProductCard با Toggle کامل
 * (بخش ۹ و ۲۵). وضعیت اولیه هر قلب از سمت Server تعیین می‌شود (کلاس
 * is-active روی دکمه)؛ این فایل فقط حالت بعد از کلیک را عوض می‌کند.
 */

function setHeartVisualState(button, isActive) {
    const path = button.querySelector('svg path');
    if (isActive) {
        button.classList.add('is-active');
        path?.setAttribute('fill', 'currentColor');
        button.setAttribute('aria-label', 'حذف از علاقه‌مندی‌ها');
    } else {
        button.classList.remove('is-active');
        path?.setAttribute('fill', 'none');
        button.setAttribute('aria-label', 'افزودن به علاقه‌مندی‌ها');
    }
}

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-wishlist-toggle]');
    if (!button) return;

    // این دکمه معمولاً داخل لینک تصویر محصول است؛ بدون این دو خط، کلیک روی
    // قلب باعث می‌شد مرورگر همزمان به صفحه محصول هم برود.
    event.preventDefault();
    event.stopPropagation();

    const productId = button.dataset.productId;
    const wasActive = button.classList.contains('is-active');
    button.disabled = true;

    try {
        if (wasActive) {
            await window.apiFetch(`/wishlist/${productId}`, { method: 'DELETE' });
            setHeartVisualState(button, false);
            window.showToast('از علاقه‌مندی‌ها حذف شد.');

            // در صفحه علاقه‌مندی‌ها، با حذف باید کل کارت محصول هم از صفحه برود
            if (document.body.dataset.page === 'wishlist') {
                button.closest('[data-product-id]')?.remove();
            }
        } else {
            await window.apiFetch(`/wishlist/${productId}`, { method: 'POST' });
            setHeartVisualState(button, true);
            window.showToast('به علاقه‌مندی‌ها اضافه شد.');
        }
    } catch (error) {
        if (error.status === 401) {
            window.showToast('برای افزودن به علاقه‌مندی‌ها ابتدا وارد شوید.', 'error');
        } else {
            window.showToast(error.message || 'این عملیات ممکن نشد.', 'error');
        }
    } finally {
        button.disabled = false;
    }
});
