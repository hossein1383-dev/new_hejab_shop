/**
 * checkout.js — بخش ۱۲ و ۲۹.۱
 * ناوبری بین مراحل، پیشگیری از خطا قبل از ادامه، و ثبت نهایی سفارش با AJAX.
 */

function goToStep(stepNumber) {
    document.querySelectorAll('.checkout-step').forEach((el) => {
        el.classList.toggle('is-active', el.dataset.step === String(stepNumber));
    });
    document.querySelectorAll('[data-step-indicator]').forEach((el) => {
        const step = Number(el.dataset.stepIndicator);
        el.classList.toggle('is-active', step === stepNumber);
        el.classList.toggle('is-done', step < stepNumber);
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/** بخش ۲۹.۱ بند ۵: پیشگیری از خطا — قبل از رفتن به مرحله بعد، اعتبارسنجی ساده انجام می‌شود. */
function validateAddressStep() {
    const newAddressForm = document.querySelector('[data-new-address-form]');
    const isFormVisible = newAddressForm && !newAddressForm.hidden;

    if (!isFormVisible) {
        return true; // یک آدرس ذخیره‌شده انتخاب شده
    }

    const required = ['address[receiver_name]', 'address[phone]', 'address[province]', 'address[city]', 'address[address_line]'];
    const missing = required.some((name) => {
        const field = newAddressForm.querySelector(`[name="${name}"]`);
        return !field || !field.value.trim();
    });

    if (missing) {
        window.showToast('لطفاً همه فیلدهای آدرس را پر کنید.', 'error');
        return false;
    }

    return true;
}

document.querySelectorAll('[data-go-step]').forEach((button) => {
    button.addEventListener('click', () => {
        const targetStep = Number(button.dataset.goStep);
        const currentStep = Number(button.closest('.checkout-step').dataset.step);

        if (targetStep > currentStep && currentStep === 1 && !validateAddressStep()) {
            return;
        }

        goToStep(targetStep);
    });
});

// انتخاب "آدرس جدید" فرم را نشان می‌دهد؛ انتخاب آدرس ذخیره‌شده آن را مخفی می‌کند
document.querySelectorAll('input[name="address_id"]').forEach((radio) => {
    radio.addEventListener('change', () => {
        const form = document.querySelector('[data-new-address-form]');
        if (form) {
            form.hidden = radio.value !== 'new';
        }
    });
});

let appliedCouponCode = null;

function formatToman(amount) {
    return new Intl.NumberFormat('fa-IR').format(amount) + ' تومان';
}

document.querySelector('[data-apply-coupon]')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    const input = document.querySelector('[data-coupon-input]');
    const message = document.querySelector('[data-coupon-message]');
    const code = input?.value.trim();

    if (!code) return;

    button.disabled = true;

    try {
        const response = await window.apiFetch('/coupons/validate', {
            method: 'POST',
            body: JSON.stringify({ code }),
        });

        appliedCouponCode = code;

        message.textContent = 'کد تخفیف با موفقیت اعمال شد.';
        message.style.color = 'var(--success)';
        message.hidden = false;

        const payButton = document.querySelector('[data-place-order]');
        const baseTotal = Number(payButton.dataset.baseTotal);
        const newTotal = baseTotal - response.data.discount_amount;

        document.querySelector('[data-coupon-summary-row]').hidden = false;
        document.querySelector('[data-coupon-discount-display]').textContent = '−' + formatToman(response.data.discount_amount);
        document.querySelector('[data-summary-total]').textContent = formatToman(newTotal);
        document.querySelector('[data-pay-amount]').textContent = `پرداخت ${formatToman(newTotal)}`;
    } catch (error) {
        appliedCouponCode = null;
        message.textContent = error.message || 'کد تخفیف نامعتبر است.';
        message.style.color = 'var(--danger)';
        message.hidden = false;
    } finally {
        button.disabled = false;
    }
});

document.querySelector('[data-place-order]')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'در حال ثبت سفارش...';

    const generalError = document.querySelector('[data-general-error]');
    if (generalError) generalError.hidden = true;

    const payload = {};

    const addressRadio = document.querySelector('input[name="address_id"]:checked');
    if (addressRadio && addressRadio.value !== 'new') {
        payload.address_id = addressRadio.value;
    } else {
        payload.address = {
            receiver_name: document.getElementById('receiver_name')?.value,
            phone: document.getElementById('phone')?.value,
            province: document.getElementById('province')?.value,
            city: document.getElementById('city')?.value,
            heropost_city_id: document.getElementById('heropost_city_id')?.value || null,
            address_line: document.getElementById('address_line')?.value,
            postal_code: document.getElementById('postal_code')?.value,
        };
    }

    payload.shipping_method = document.querySelector('input[name="shipping_method"]:checked')?.value || 'standard';
    payload.payment_method = document.querySelector('input[name="payment_method"]:checked')?.value || 'gateway';

    if (appliedCouponCode) {
        payload.coupon_code = appliedCouponCode;
    }

    try {
        const response = await window.apiFetch('/checkout', {
            method: 'POST',
            body: JSON.stringify(payload),
        });

        window.location.href = response.data.redirect_url;
    } catch (error) {
        if (error.status === 422) {
            window.showFieldErrors(document.querySelector('.checkout-page__form'), error.errors);
            goToStep(1); // خطاهای Validation معمولاً مربوط به آدرس هستند
        }

        if (generalError) {
            generalError.textContent = error.message || 'ثبت سفارش ممکن نشد.';
            generalError.hidden = false;
        }

        button.disabled = false;
        button.textContent = originalText;
    }
});

document.getElementById('province')?.addEventListener('change', async (event) => {
    const province = event.target.value;
    const citySelect = document.getElementById('city');
    const hiddenCityId = document.getElementById('heropost_city_id');
    if (!citySelect || citySelect.tagName !== 'SELECT') return; // حالت Fallback متنی

    citySelect.disabled = true;
    if (hiddenCityId) hiddenCityId.value = '';
    citySelect.innerHTML = '<option value="">در حال بارگذاری...</option>';

    if (!province) {
        citySelect.innerHTML = '<option value="">ابتدا استان را انتخاب کنید</option>';
        return;
    }

    try {
        const response = await fetch(`/heropost-cities?province=${encodeURIComponent(province)}`);
        const result = await response.json();
        citySelect.innerHTML = '<option value="">— انتخاب کنید —</option>';
        result.data.forEach((city) => {
            const option = document.createElement('option');
            option.value = city.name;
            option.dataset.id = city.id;
            option.textContent = city.name;
            citySelect.appendChild(option);
        });
        citySelect.disabled = false;
    } catch (error) {
        citySelect.innerHTML = '<option value="">خطا در بارگذاری شهرها</option>';
    }
});

document.getElementById('city')?.addEventListener('change', (event) => {
    if (event.target.tagName !== 'SELECT') return;
    const hiddenCityId = document.getElementById('heropost_city_id');
    if (hiddenCityId) {
        hiddenCityId.value = event.target.selectedOptions[0]?.dataset.id || '';
    }
});

/**
 * تخمین زنده هزینه ارسال — بخش ۱۵. هر بار آدرس (موجود یا تازه) عوض شود،
 * جمع کل صفحه به‌روزرسانی می‌شود. مبلغ نهایی واقعی همیشه توسط سرور هنگام
 * ثبت سفارش دوباره محاسبه می‌شود؛ این فقط برای نمایش زنده به کاربر است.
 */
(function () {
    const checkoutData = JSON.parse(document.getElementById('checkout-data')?.textContent || '{}');
    const shippingDisplay = document.querySelector('[data-shipping-cost-display]');
    const totalDisplay = document.querySelector('[data-summary-total]');
    const payButton = document.querySelector('[data-place-order]');
    const payAmountDisplay = payButton?.querySelector('[data-pay-amount]');

    if (!shippingDisplay) return;

    async function updateShippingEstimate(payload) {
        shippingDisplay.textContent = 'در حال محاسبه...';

        try {
            const response = await window.apiFetch('/checkout/estimate-shipping', {
                method: 'POST',
                body: JSON.stringify(payload),
            });

            const { shipping_cost: shippingCost, total } = response.data;
            shippingDisplay.textContent = shippingCost === 0
                ? 'رایگان'
                : `${new Intl.NumberFormat('fa-IR').format(shippingCost)} تومان`;

            if (totalDisplay) totalDisplay.textContent = `${new Intl.NumberFormat('fa-IR').format(total)} تومان`;
            if (payButton) payButton.dataset.baseTotal = total;
            if (payAmountDisplay) payAmountDisplay.textContent = `پرداخت ${new Intl.NumberFormat('fa-IR').format(total)} تومان`;
        } catch (error) {
            shippingDisplay.textContent = 'بعد از انتخاب آدرس';
        }
    }

    function estimateForSelectedAddress() {
        const checkedRadio = document.querySelector('input[name="address_id"]:checked');
        if (!checkedRadio) return;

        if (checkedRadio.value !== 'new') {
            updateShippingEstimate({ address_id: checkedRadio.value });
        } else {
            const heropostCityId = document.getElementById('heropost_city_id')?.value;
            const province = document.getElementById('province')?.value;
            const city = document.getElementById('city')?.value;
            if (province && city) {
                updateShippingEstimate({ province, city, heropost_city_id: heropostCityId || null });
            }
        }
    }

    document.querySelectorAll('input[name="address_id"]').forEach((radio) => {
        radio.addEventListener('change', estimateForSelectedAddress);
    });

    document.getElementById('city')?.addEventListener('change', estimateForSelectedAddress);

    // اگر از قبل یک آدرس ذخیره‌شده به‌طور پیش‌فرض انتخاب شده، همان لحظه تخمین بزن
    estimateForSelectedAddress();
})();
