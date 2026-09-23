@extends('admin.layout')
@section('title', 'گزارش رخدادها (Audit Log)')
@section('content')
<h1 class="admin-page-title">گزارش رخدادها</h1>
<div class="admin-orders-desktop">
    <table class="admin-table">
        <thead><tr><th>کاربر</th><th>عملیات</th><th>مدل</th><th>تاریخ</th></tr></thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->user?->name ?? 'سیستم' }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ class_basename($log->model_type) }} #{{ $log->model_id }}</td>
                    <td>{{ $log->created_at->format('Y/m/d H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="admin-empty">رخدادی ثبت نشده است.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="admin-orders-mobile">
    @forelse($logs as $log)
        <div class="admin-order-card">
            <div class="admin-order-card__row"><strong>{{ $log->action }}</strong><span>{{ $log->created_at->format('Y/m/d H:i') }}</span></div>
            <div class="admin-order-card__row admin-order-card__row--muted"><span>{{ $log->user?->name ?? 'سیستم' }}</span><span>{{ class_basename($log->model_type) }} #{{ $log->model_id }}</span></div>
        </div>
    @empty
        <p class="admin-empty">رخدادی ثبت نشده است.</p>
    @endforelse
</div>
<div class="admin-pagination">{{ $logs->links() }}</div>
@endsection
