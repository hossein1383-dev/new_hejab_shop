/**
 * reviews.js — بخش ۱۷: ثبت نظر (با انتخاب ستاره) و پرسش، با Feedback فوری.
 */

// انتخاب امتیاز ستاره‌ای
document.querySelectorAll('[data-rating-star]').forEach((star) => {
    star.addEventListener('click', () => {
        const value = Number(star.dataset.ratingStar);
        const container = star.closest('[data-rating-input]');
        container.querySelector('input[name="rating"]').value = value;

        container.querySelectorAll('[data-rating-star]').forEach((s) => {
            s.textContent = Number(s.dataset.ratingStar) <= value ? '★' : '☆';
        });
    });
});

document.getElementById('review-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.target;
    const productId = form.dataset.productId;
    const rating = Number(form.querySelector('input[name="rating"]').value);
    const comment = form.querySelector('textarea[name="comment"]').value;

    if (rating < 1) {
        window.showToast('لطفاً امتیاز را انتخاب کنید.', 'error');
        return;
    }

    const button = form.querySelector('.qa-form__submit');
    button.disabled = true;

    try {
        await window.apiFetch(`/products/${productId}/reviews`, {
            method: 'POST',
            body: JSON.stringify({ rating, comment }),
        });

        window.showToast('نظر شما ثبت شد و پس از تایید نمایش داده می‌شود.');
        form.reset();
        form.remove();
    } catch (error) {
        window.showToast(error.message || 'ثبت نظر ممکن نشد.', 'error');
    } finally {
        button.disabled = false;
    }
});

document.getElementById('question-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.target;
    const productId = form.dataset.productId;
    const body = form.querySelector('textarea[name="body"]').value;

    const button = form.querySelector('.qa-form__submit');
    button.disabled = true;

    try {
        await window.apiFetch(`/products/${productId}/questions`, {
            method: 'POST',
            body: JSON.stringify({ body }),
        });

        window.showToast('سوال شما ثبت شد و پس از بررسی نمایش داده می‌شود.');
        form.reset();
    } catch (error) {
        window.showToast(error.message || 'ثبت سوال ممکن نشد.', 'error');
    } finally {
        button.disabled = false;
    }
});
