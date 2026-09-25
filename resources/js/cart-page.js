/**
 * cart-page.js — بخش ۹. تغییر تعداد و حذف آیتم با AJAX (بدون Reload کامل صفحه)،
 * به‌روزرسانی آنی جمع‌ها و Badge سبد در Header (بخش ۲۹.۱: Feedback فوری).
 */

function formatToman(amount) {
    return new Intl.NumberFormat('fa-IR').format(amount) + ' تومان';
}

function updateSummary(data) {
    document.querySelectorAll('[data-cart-subtotal]').forEach((el) => {
        el.textContent = formatToman(data.total);
    });
    document.querySelectorAll('[data-items-count]').forEach((el) => {
        el.textContent = data.items_count;
    });
    window.updateCartBadges?.(data.items_count);

    if (data.items_count === 0) {
        showEmptyState();
    }
}

function showEmptyState() {
    const layout = document.querySelector('[data-cart-layout]');
    if (!layout) return;

    layout.outerHTML = `
        <div class="cart-page__empty" data-cart-empty>
            <p>سبد خرید شما خالی است.</p>
            <a href="/products" class="cart-page__empty-cta">مشاهده محصولات</a>
        </div>
    `;
}

function updateItemRow(itemId, itemData) {
    const row = document.querySelector(`[data-cart-item="${itemId}"]`);
    if (!row) return;

    row.querySelectorAll('[data-qty-value]').forEach((el) => {
        el.textContent = itemData.quantity;
    });
    row.querySelectorAll('[data-line-total]').forEach((el) => {
        el.textContent = formatToman(itemData.line_total);
    });
}

document.querySelectorAll('[data-cart-item]').forEach((row) => {
    const itemId = row.dataset.cartItem;

    async function changeQuantity(delta) {
        const currentQty = Number(row.querySelector('[data-qty-value]').textContent);
        const newQty = currentQty + delta;
        if (newQty < 0) return;

        try {
            const response = await window.apiFetch(`/cart/${itemId}`, {
                method: 'PUT',
                body: JSON.stringify({ quantity: newQty }),
            });

            const itemData = response.data.items.find((i) => i.id == itemId);

            if (!itemData) {
                // یعنی تعداد صفر شد و آیتم حذف شد
                row.remove();
            } else {
                updateItemRow(itemId, itemData);
            }

            updateSummary(response.data);
        } catch (error) {
            window.showToast(error.message || 'به‌روزرسانی سبد ممکن نشد.', 'error');
        }
    }

    row.querySelectorAll('[data-qty-increase]').forEach((btn) => {
        btn.addEventListener('click', () => changeQuantity(1));
    });
    row.querySelectorAll('[data-qty-decrease]').forEach((btn) => {
        btn.addEventListener('click', () => changeQuantity(-1));
    });

    row.querySelectorAll('[data-remove-item]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            try {
                const response = await window.apiFetch(`/cart/${itemId}`, { method: 'DELETE' });
                row.remove();
                updateSummary(response.data);
                window.showToast('آیتم از سبد حذف شد.');
            } catch (error) {
                window.showToast(error.message || 'حذف آیتم ممکن نشد.', 'error');
            }
        });
    });
});

// انتقال به صفحه Checkout
document.querySelector('[data-checkout-btn]')?.addEventListener('click', () => {
    window.location.href = '/checkout';
});

// پرداخت مجدد سفارش در انتظار — بخش ۵۳
document.querySelectorAll('[data-retry-payment]').forEach((btn) => {
    btn.addEventListener('click', async () => {
        const orderId = btn.dataset.retryPayment;
        btn.disabled = true;
        btn.textContent = 'در حال انتقال...';

        try {
            const response = await window.apiFetch(`/orders/${orderId}/retry-payment`, { method: 'POST' });
            window.location.href = response.data.redirect_url;
        } catch (error) {
            window.showToast?.(error.message || 'اتصال به درگاه ممکن نشد.', 'error');
            btn.disabled = false;
            btn.textContent = 'پرداخت';
        }
    });
});

// لغو سفارش در انتظار پرداخت توسط خودِ مشتری — بخش ۶۷
document.querySelectorAll('[data-cancel-pending-order]').forEach((btn) => {
    btn.addEventListener('click', async () => {
        if (!confirm('این سفارش حذف شود؟ موجودی رزروشده آزاد می‌شود.')) return;

        const orderId = btn.dataset.cancelPendingOrder;
        btn.disabled = true;

        try {
            const response = await window.apiFetch(`/orders/${orderId}/cancel-own`, { method: 'DELETE' });
            btn.closest('[data-pending-order]').remove();
        } catch (error) {
            window.showToast?.(error.message || 'حذف سفارش ممکن نشد.', 'error');
            btn.disabled = false;
        }
    });
});
