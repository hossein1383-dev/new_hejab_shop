@extends('layouts.app')

@section('title', 'وضعیت سفارش')

@section('content')
<div style="max-width: 480px; margin: 60px auto; text-align: center; font-family: sans-serif; padding: 0 16px;">
    @if($order->status === 'paid')
        <h1 style="color: #16A34A;">پرداخت با موفقیت انجام شد ✓</h1>
    @elseif($order->status === 'cancelled')
        <h1 style="color: #DC2626;">پرداخت ناموفق بود</h1>
    @else
        <h1>در انتظار پرداخت</h1>
    @endif

    <p>شماره سفارش: <strong>{{ $order->order_number }}</strong></p>
    <p>مبلغ کل: {{ number_format($order->total) }} تومان</p>

    <a href="{{ url('/') }}" style="display:inline-block; margin-top: 20px; color: #2563EB;">بازگشت به صفحه اصلی</a>
</div>
{{--
    توجه: این نسخه Minimal است و طبق بخش ۲۰.۱ نسخه جدا موبایل/دسکتاپ ندارد
    چون فعلاً فقط برای تکمیل Flow پرداخت (بخش ۱۴) ساخته شده. طراحی کامل
    (Timeline وضعیت سفارش، جزئیات آیتم‌ها) در فاز Account/Order History
    با هر دو نسخه ساخته خواهد شد.
--}}
@endsection
