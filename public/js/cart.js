/**
 * cart.js — افزودن سریع محصول به سبد از روی ProductCard (بخش ۲۵ و ۲۹.۱)
 */

function updateCartBadges(count) {
    document.querySelectorAll('.header-mobile__badge, .header-desktop__badge').forEach((badge) => {
        badge.textContent = String(count);
        badge.hidden = count <= 0;
    });
}
window.updateCartBadges = updateCartBadges;

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-add-to-cart]');
    if (!button) return;

    const productId = button.dataset.productId;
    const originalText = button.textContent;

    button.disabled = true;
    button.textContent = 'در حال افزودن...';

    try {
        const response = await window.apiFetch('/cart', {
            method: 'POST',
            body: JSON.stringify({ product_id: productId, quantity: 1 }),
        });

        updateCartBadges(response.data.items_count);
        window.showToast('به سبد خرید اضافه شد.');
    } catch (error) {
        // بخش ۲۹.۱: پیام خطا انسانی و راه‌حل‌محور، نه کد خام
        window.showToast(error.message || 'افزودن به سبد ممکن نشد.', 'error');
    } finally {
        button.disabled = false;
        button.textContent = originalText;
    }
});
