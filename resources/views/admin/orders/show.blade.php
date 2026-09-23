@extends('admin.layout')

@section('title', 'سفارش ' . $order->order_number)

@php
    $transitions = [
        'paid' => ['processing', 'cancelled'],
        'processing' => ['preparing', 'cancelled'],
        'preparing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered'],
        'delivered' => ['returned'],
        'cancelled' => ['refunded'],
    ];
    $availableTransitions = $transitions[$order->status] ?? [];
@endphp

@section('content')
<a href="{{ route('admin.orders.index') }}" class="admin-back-link">← بازگشت به لیست سفارش‌ها</a>

<div class="admin-order-detail">
    <div class="admin-order-detail__header">
        <h1 class="admin-page-title">سفارش {{ $order->order_number }}</h1>
        <span class="admin-status-badge admin-status-badge--{{ $order->status }}">{{ $order->status }}</span>
    </div>

    <div class="admin-order-detail__grid">
        <div class="admin-card">
            <h2>اقلام سفارش</h2>
            @foreach($order->items as $item)
                <div class="admin-order-item">
                    <span>{{ $item->product_name }} ({{ $item->sku }}) × {{ $item->quantity }}</span>
                    <span>{{ number_format($item->line_total) }} تومان</span>
                </div>
            @endforeach
            <div class="admin-order-item admin-order-item--total">
                <span>جمع کل</span>
                <span>{{ number_format($order->total) }} تومان</span>
            </div>
        </div>

        <div class="admin-card">
            <h2>آدرس ارسال</h2>
            <p>{{ $order->shipping_name }} — {{ $order->shipping_phone }}</p>
            <p>{{ $order->shipping_province }}، {{ $order->shipping_city }}، {{ $order->shipping_address_line }}</p>
        </div>

        <div class="admin-card">
            <h2>تغییر وضعیت</h2>
            @if(empty($availableTransitions))
                <p class="admin-empty">این سفارش در وضعیت نهایی است و قابل تغییر دستی نیست.</p>
            @else
                <form method="POST" action="{{ route('admin.orders.update-status', $order) }}" class="admin-status-form">
                    @csrf
                    @method('PUT')
                    <select name="status" required>
                        <option value="">انتخاب وضعیت جدید</option>
                        @foreach($availableTransitions as $status)
                            <option value="{{ $status }}">{{ $status }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="note" placeholder="یادداشت (اختیاری)">
                    <button type="submit">ثبت تغییر</button>
                </form>
            @endif
        </div>

        <div class="admin-card">
            <h2>مرسوله پستی (هیروپست)</h2>
            @if($order->heropost_tracking_code)
                <p class="admin-help-text">کد رهگیری: <strong dir="ltr">{{ $order->heropost_tracking_code }}</strong></p>
            @elseif(in_array($order->status, ['paid', 'processing', 'preparing'], true))
                <p class="admin-help-text">هنوز مرسوله‌ای برای این سفارش ثبت نشده.</p>
                <form method="POST" action="{{ route('admin.orders.create-parcel', $order) }}" class="admin-status-form">
                    @csrf
                    <button type="submit" class="admin-btn-primary">ثبت مرسوله در هیروپست</button>
                </form>
            @else
                <p class="admin-help-text">برای ثبت مرسوله، ابتدا سفارش باید پرداخت‌شده باشد.</p>
            @endif
        </div>

        @if(in_array($order->status, ['pending_payment', 'paid', 'preparing'], true))
            <div class="admin-card admin-card--danger">
                <h2>لغو سفارش</h2>
                <p class="admin-help-text">
                    اگر سفارش پرداخت شده باشد، وجه خودکار برمی‌گردد — ابتدا تلاش می‌شود مستقیم به کارت،
                    اگر نشد به کیف پول مشتری اعتبار داده می‌شود. اگر مرسوله‌ای هم ثبت شده باشد، آن هم لغو می‌شود.
                </p>
                <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" class="admin-status-form" data-confirm="این سفارش لغو و وجه آن (در صورت پرداخت‌شدن) بازگردانده می‌شود. مطمئنید؟">
                    @csrf
                    <input type="text" name="note" placeholder="دلیل لغو (اختیاری)">
                    <button type="submit" class="admin-btn-danger">لغو سفارش و بازگشت وجه</button>
                </form>
            </div>
        @endif

        <div class="admin-card">
            <h2>تاریخچه وضعیت</h2>
            <ul class="admin-timeline">
                @foreach($order->statusHistories as $history)
                    <li>
                        <strong>{{ $history->to_status }}</strong>
                        <span>{{ $history->created_at->format('Y/m/d H:i') }}</span>
                        @if($history->user)
                            <span>توسط {{ $history->user->name }}</span>
                        @endif
                        @if($history->note)
                            <p>{{ $history->note }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
