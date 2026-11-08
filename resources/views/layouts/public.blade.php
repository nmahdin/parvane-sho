<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <x-theme-vars />
    {{-- اعمال حالت تاریک پیش از رندر تا صفحه پرش نکند --}}
    <script>
        (function () {
            try {
                var theme = localStorage.getItem('pv-theme');

                if (theme === 'dark' || (!theme && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#659287">

    <title>@yield('title', $site['site_name'] ?? config('panel.site_name'))</title>
    <meta name="description" content="@yield('description', $site['site_tagline'] ?? config('panel.site_tagline'))">

    {{-- آیکون پروانه سبز یشمی --}}
    <x-head-extras />
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <meta property="og:image" content="{{ asset('icon-512.png') }}">

    @stack('meta')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas">
    <x-site-header />

    <main>
        @yield('content')
    </main>

    <x-site-footer />
</body>
</html>
