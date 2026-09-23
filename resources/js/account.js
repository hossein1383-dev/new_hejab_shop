/**
 * account.js — خروج از حساب + مدیریت آدرس‌ها (بخش ۹.۱ صفحه حساب کاربری)
 */

document.getElementById('account-logout-btn')?.addEventListener('click', async () => {
    try {
        await window.apiFetch('/auth/logout', { method: 'POST' });
        window.location.href = '/';
    } catch (error) {
        window.showToast(error.message || 'خروج ممکن نشد.', 'error');
    }
});

const toggleBtn = document.querySelector('[data-toggle-address-form]');
const newAddressForm = document.getElementById('new-address-form');

toggleBtn?.addEventListener('click', () => {
    newAddressForm.hidden = !newAddressForm.hidden;
});

newAddressForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const formData = new FormData(newAddressForm);
    const data = Object.fromEntries(formData.entries());
    data.is_default = newAddressForm.querySelector('[name="is_default"]').checked;

    const button = newAddressForm.querySelector('.account-btn-primary');
    button.disabled = true;

    try {
        await window.apiFetch('/addresses', {
            method: 'POST',
            body: JSON.stringify(data),
        });
        window.showToast('آدرس ذخیره شد.');
        window.location.reload();
    } catch (error) {
        const messageEl = document.querySelector('[data-address-message]');
        messageEl.textContent = error.message || 'ذخیره آدرس ممکن نشد.';
        messageEl.hidden = false;
    } finally {
        button.disabled = false;
    }
});

document.querySelectorAll('[data-delete-address]').forEach((button) => {
    button.addEventListener('click', async () => {
        if (!confirm('این آدرس حذف شود؟')) return;

        const addressId = button.dataset.deleteAddress;
        try {
            await window.apiFetch(`/addresses/${addressId}`, { method: 'DELETE' });
            button.closest('[data-address-id]').remove();
            window.showToast('آدرس حذف شد.');
        } catch (error) {
            window.showToast(error.message || 'حذف آدرس ممکن نشد.', 'error');
        }
    });
});

document.getElementById('address-province-select')?.addEventListener('change', async (event) => {
    const province = event.target.value;
    const citySelect = document.getElementById('address-city-select');
    const hiddenCityId = document.getElementById('address-heropost-city-id');

    citySelect.disabled = true;
    hiddenCityId.value = '';
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

document.getElementById('address-city-select')?.addEventListener('change', (event) => {
    const selectedOption = event.target.selectedOptions[0];
    document.getElementById('address-heropost-city-id').value = selectedOption?.dataset.id || '';
});
const walletTopupForm = document.getElementById('wallet-topup-form');
walletTopupForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const amount = walletTopupForm.querySelector('#topup-amount').value;
    const button = walletTopupForm.querySelector('.account-btn-primary');
    const messageEl = document.querySelector('[data-topup-message]');
    messageEl.hidden = true;
    button.disabled = true;
    button.textContent = 'در حال اتصال...';

    try {
        const response = await window.apiFetch('/wallet/topup', {
            method: 'POST',
            body: JSON.stringify({ amount: Number(amount) }),
        });
        window.location.href = response.data.redirect_url;
    } catch (error) {
        messageEl.textContent = error.message || 'اتصال به درگاه پرداخت ممکن نشد.';
        messageEl.hidden = false;
        button.disabled = false;
        button.textContent = 'شارژ از طریق درگاه';
    }
});
