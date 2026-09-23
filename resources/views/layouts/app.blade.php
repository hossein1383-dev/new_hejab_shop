<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'فروشگاه')</title>

    {{-- SEO — بخش ۳۱ --}}
    <meta name="description" content="@yield('meta_description', 'خرید آنلاین با ارسال سریع و ضمانت اصالت کالا.')">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', 'فروشگاه')">
    <meta property="og:description" content="@yield('meta_description', 'خرید آنلاین با ارسال سریع و ضمانت اصالت کالا.')">
    <meta property="og:url" content="{{ url()->current() }}">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    @php
        $organizationSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('app.name'),
            'url' => url('/'),
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($organizationSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @stack('structured_data')

    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css">
    @vite(['resources/css/variables.css', 'resources/css/base.css', 'resources/css/components/footer.css'])
    @stack('styles')
</head>
<body data-page="@yield('page-identifier')">
    @yield('content')

    @vite(['resources/js/app.js'])
    @stack('scripts')
</body>
</html>
