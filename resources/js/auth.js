/**
 * auth.js — ورود کاملاً با شماره تلفن + OTP (بخش ۴، جایگزین کامل رمز عبور).
 */

const requestForm = document.getElementById('otp-request-form');
const verifyForm = document.getElementById('otp-verify-form');
let resendTimerInterval = null;

function showGeneralError(message) {
    const el = document.querySelector('[data-general-error]');
    if (!el) return;
    el.textContent = message;
    el.style.display = message ? 'block' : 'none';
}

function startResendTimer() {
    const resendBtn = document.querySelector('[data-otp-resend]');
    const timerEl = document.querySelector('[data-resend-timer]');
    let seconds = 60;
    resendBtn.disabled = true;

    clearInterval(resendTimerInterval);
    resendTimerInterval = setInterval(() => {
        seconds -= 1;
        timerEl.textContent = new Intl.NumberFormat('fa-IR').format(seconds);
        if (seconds <= 0) {
            clearInterval(resendTimerInterval);
            resendBtn.disabled = false;
            resendBtn.textContent = 'ارسال دوباره کد';
        }
    }, 1000);
}

function goToVerifyStep(phone) {
    requestForm.hidden = true;
    verifyForm.hidden = false;
    verifyForm.querySelector('input[name="phone"]').value = phone;
    verifyForm.querySelector('#code').focus();
    startResendTimer();
}

function goToRequestStep() {
    verifyForm.hidden = true;
    requestForm.hidden = false;
    clearInterval(resendTimerInterval);
    requestForm.querySelector('#phone').focus();
}

if (requestForm) {
    requestForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const phone = requestForm.querySelector('#phone').value.trim();
        const button = requestForm.querySelector('.auth-submit');

        button.disabled = true;
        button.textContent = 'در حال ارسال...';
        showGeneralError('');
        window.showFieldErrors(requestForm, {});

        try {
            await window.apiFetch('/auth/otp/request', {
                method: 'POST',
                body: JSON.stringify({ phone }),
            });
            goToVerifyStep(phone);
        } catch (error) {
            if (error.status === 422) {
                window.showFieldErrors(requestForm, error.errors);
            } else {
                showGeneralError(error.message);
            }
        } finally {
            button.disabled = false;
            button.textContent = 'دریافت کد ورود';
        }
    });
}

if (verifyForm) {
    verifyForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const phone = verifyForm.querySelector('input[name="phone"]').value;
        const code = verifyForm.querySelector('#code').value.trim();
        const button = verifyForm.querySelector('.auth-submit');

        button.disabled = true;
        button.textContent = 'در حال بررسی...';
        showGeneralError('');
        window.showFieldErrors(verifyForm, {});

        try {
            await window.apiFetch('/auth/otp/verify', {
                method: 'POST',
                body: JSON.stringify({ phone, code }),
            });
            window.location.href = '/';
        } catch (error) {
            if (error.status === 422) {
                window.showFieldErrors(verifyForm, error.errors);
                showGeneralError(error.message);
            } else {
                showGeneralError(error.message);
            }
        } finally {
            button.disabled = false;
            button.textContent = 'ورود';
        }
    });

    document.querySelector('[data-otp-edit-phone]')?.addEventListener('click', goToRequestStep);

    document.querySelector('[data-otp-resend]')?.addEventListener('click', async () => {
        const phone = verifyForm.querySelector('input[name="phone"]').value;
        try {
            await window.apiFetch('/auth/otp/request', {
                method: 'POST',
                body: JSON.stringify({ phone }),
            });
            startResendTimer();
            window.showToast('کد جدید ارسال شد.');
        } catch (error) {
            showGeneralError(error.message);
        }
    });
}
