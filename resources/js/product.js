/**
 * product.js — منطق صفحه محصول: انتخاب Variant، Quantity، Add to Cart/Buy Now
 * (بخش ۷ و ۱۱). قیمت/موجودی نهایی همیشه توسط Backend هنگام افزودن به سبد
 * دوباره بررسی می‌شود (بخش ۳۴)؛ اینجا فقط برای UX فوری (Perceived Performance) است.
 */

const productDataEl = document.getElementById('product-data');
if (productDataEl) {
    const productData = JSON.parse(productDataEl.textContent);
    const selectedValues = new Set();
    let selectedVariantId = null;
    let quantity = 1;

    const qtyInput = document.querySelector('[data-qty-input]');
    const priceEl = document.querySelector('[data-current-price]');
    const oldPriceEl = document.querySelector('[data-old-price]');
    const stockEl = document.querySelector('[data-stock-display]');

    function formatToman(amount) {
        return new Intl.NumberFormat('fa-IR').format(amount) + ' تومان';
    }

    function findMatchingVariant() {
        if (productData.variants.length === 0) return null;

        return productData.variants.find((variant) => {
            const ids = variant.attribute_value_ids;
            return ids.length === selectedValues.size && ids.every((id) => selectedValues.has(id));
        });
    }

    function updateSelectionUI() {
        const variant = findMatchingVariant();
        selectedVariantId = variant?.id ?? null;

        const price = variant ? variant.price : productData.basePrice;
        const comparePrice = variant ? variant.compare_price : productData.baseComparePrice;
        const stock = variant ? variant.stock : productData.baseStock;

        if (priceEl) priceEl.textContent = formatToman(price);

        if (oldPriceEl) {
            if (comparePrice && comparePrice > price) {
                oldPriceEl.textContent = new Intl.NumberFormat('fa-IR').format(comparePrice);
                oldPriceEl.hidden = false;
            } else {
                oldPriceEl.hidden = true;
            }
        }

        if (stockEl) {
            stockEl.innerHTML = stock > 0
                ? '<span class="product-page__in-stock">موجود در انبار</span>'
                : '<span class="product-page__out-of-stock">ناموجود</span>';
        }

        document.querySelectorAll('[data-add-to-cart-detail], [data-buy-now]').forEach((btn) => {
            btn.disabled = stock <= 0 || (productData.variants.length > 0 && !variant);
        });
    }

    document.querySelectorAll('[data-attribute-value-id]').forEach((button) => {
        button.addEventListener('click', () => {
            const id = Number(button.dataset.attributeValueId);
            const group = button.closest('.product-page__variant-group');

            // هر گروه (مثلاً رنگ) فقط یک انتخاب دارد؛ انتخاب قبلی همان گروه پاک می‌شود
            group.querySelectorAll('[data-attribute-value-id]').forEach((btn) => {
                if (btn !== button) {
                    selectedValues.delete(Number(btn.dataset.attributeValueId));
                    btn.classList.remove('is-selected');
                }
            });

            button.classList.toggle('is-selected');
            if (button.classList.contains('is-selected')) {
                selectedValues.add(id);
            } else {
                selectedValues.delete(id);
            }

            updateSelectionUI();
        });
    });

    document.querySelector('[data-qty-decrease]')?.addEventListener('click', () => {
        if (quantity > 1) {
            quantity -= 1;
            qtyInput.value = quantity;
        }
    });

    document.querySelector('[data-qty-increase]')?.addEventListener('click', () => {
        quantity += 1;
        qtyInput.value = quantity;
    });

    async function addToCart() {
        return window.apiFetch('/cart', {
            method: 'POST',
            body: JSON.stringify({
                product_id: productData.productId,
                product_variant_id: selectedVariantId,
                quantity,
            }),
        });
    }

    document.querySelectorAll('[data-add-to-cart-detail]').forEach((button) => {
        button.addEventListener('click', async () => {
            button.disabled = true;
            const originalText = button.textContent;
            button.textContent = 'در حال افزودن...';

            try {
                const response = await addToCart();
                window.updateCartBadges(response.data.items_count);
                window.showToast('به سبد خرید اضافه شد.');
            } catch (error) {
                window.showToast(error.message || 'افزودن به سبد ممکن نشد.', 'error');
            } finally {
                button.disabled = false;
                button.textContent = originalText;
            }
        });
    });

    // خرید سریع: افزودن به سبد و انتقال مستقیم — Checkout در فاز بعدی ساخته می‌شود
    document.querySelector('[data-buy-now]')?.addEventListener('click', async (event) => {
        const button = event.currentTarget;
        button.disabled = true;

        try {
            await addToCart();
            window.location.href = '/cart';
        } catch (error) {
            window.showToast(error.message || 'خرید سریع ممکن نشد.', 'error');
            button.disabled = false;
        }
    });

    updateSelectionUI();
}
