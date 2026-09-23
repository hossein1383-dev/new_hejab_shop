<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'پنل مدیریت')</title>
    @vite(['resources/css/variables.css', 'resources/css/base.css', 'resources/css/pages/admin.css'])
    @stack('styles')
</head>
<body class="admin-body">
    <button type="button" class="admin-sidebar-toggle" data-admin-sidebar-toggle aria-label="باز کردن منو">☰</button>

    <aside class="admin-sidebar" data-admin-sidebar>
        <div class="admin-sidebar__logo">پنل مدیریت</div>
        <nav class="admin-sidebar__nav">
            <a href="{{ route('admin.dashboard') }}" class="@if(request()->routeIs('admin.dashboard')) is-active @endif">داشبورد</a>
            <a href="{{ route('admin.orders.index') }}" class="@if(request()->routeIs('admin.orders.*')) is-active @endif">سفارش‌ها</a>
            <a href="{{ route('admin.products.index') }}" class="@if(request()->routeIs('admin.products.*')) is-active @endif">محصولات</a>
            <a href="{{ route('admin.categories.index') }}" class="@if(request()->routeIs('admin.categories.*')) is-active @endif">دسته‌بندی‌ها</a>
            <a href="{{ route('admin.attributes.index') }}" class="@if(request()->routeIs('admin.attributes.*')) is-active @endif">ویژگی‌ها</a>
            <a href="{{ route('admin.inventory.index') }}" class="@if(request()->routeIs('admin.inventory.*')) is-active @endif">موجودی انبار</a>
            <a href="{{ route('admin.coupons.index') }}" class="@if(request()->routeIs('admin.coupons.*')) is-active @endif">کدهای تخفیف</a>
            <a href="{{ route('admin.reviews.index') }}" class="@if(request()->routeIs('admin.reviews.*')) is-active @endif">نظرات</a>
            <a href="{{ route('admin.banners.index') }}" class="@if(request()->routeIs('admin.banners.*')) is-active @endif">بنرها</a>
            <a href="{{ route('admin.pages.index') }}" class="@if(request()->routeIs('admin.pages.*')) is-active @endif">صفحات ثابت</a>
            <a href="{{ route('admin.blog.index') }}" class="@if(request()->routeIs('admin.blog.*')) is-active @endif">وبلاگ</a>
            <a href="{{ route('admin.users.index') }}" class="@if(request()->routeIs('admin.users.*')) is-active @endif">کاربران</a>
            <a href="{{ route('admin.roles.index') }}" class="@if(request()->routeIs('admin.roles.*')) is-active @endif">نقش‌ها</a>
            <a href="{{ route('admin.reports.index') }}" class="@if(request()->routeIs('admin.reports.*')) is-active @endif">گزارش‌ها</a>
            <a href="{{ route('admin.activity-logs.index') }}" class="@if(request()->routeIs('admin.activity-logs.*')) is-active @endif">گزارش رخدادها</a>
            <a href="{{ route('admin.settings.edit') }}" class="@if(request()->routeIs('admin.settings.*')) is-active @endif">تنظیمات</a>
        </nav>
        <a href="{{ url('/') }}" class="admin-sidebar__back">← بازگشت به فروشگاه</a>
    </aside>
    <div class="admin-sidebar-overlay" data-admin-sidebar-overlay hidden></div>

    <main class="admin-main">
        @if(session('order_success'))
            <div class="admin-alert admin-alert--success">{{ session('order_success') }}</div>
        @endif
        @if(session('order_error'))
            <div class="admin-alert admin-alert--error">{{ session('order_error') }}</div>
        @endif

        @yield('content')
    </main>

    @vite(['resources/js/admin.js'])
    @stack('scripts')
</body>
</html>
