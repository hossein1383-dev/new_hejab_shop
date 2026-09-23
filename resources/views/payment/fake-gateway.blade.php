<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>درگاه پرداخت شبیه‌سازی‌شده (Development)</title>
    <style>
        body { font-family: sans-serif; max-width: 420px; margin: 80px auto; text-align: center; }
        .amount { font-size: 1.5rem; font-weight: bold; margin: 20px 0; }
        button { display: block; width: 100%; padding: 14px; margin: 8px 0; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; }
        .success { background: #16A34A; color: #fff; }
        .fail { background: #DC2626; color: #fff; }
        .warning { background: #fff3cd; padding: 10px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="warning">⚠️ این یک درگاه ساختگی برای توسعه/تست است، نه پرداخت واقعی.</div>
    <h2>پرداخت</h2>
    <div class="amount">{{ number_format($amount) }} تومان</div>

    <form method="POST" action="{{ $callbackUrl }}">
        @csrf
        <input type="hidden" name="reference" value="{{ $reference }}">
        <input type="hidden" name="amount" value="{{ $amount }}">
        <input type="hidden" name="outcome" value="success">
        <button type="submit" class="success">شبیه‌سازی پرداخت موفق</button>
    </form>

    <form method="POST" action="{{ $callbackUrl }}">
        @csrf
        <input type="hidden" name="reference" value="{{ $reference }}">
        <input type="hidden" name="amount" value="{{ $amount }}">
        <input type="hidden" name="outcome" value="failed">
        <button type="submit" class="fail">شبیه‌سازی پرداخت ناموفق</button>
    </form>
</body>
</html>
