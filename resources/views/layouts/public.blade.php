<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#075741">

    <title>@yield('title', $site['site_name'] ?? config('panel.site_name'))</title>
    <meta name="description" content="@yield('description', $site['site_tagline'] ?? config('panel.site_tagline'))">

    @stack('meta')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-cream">
    <x-site-header />

    <main>
        @yield('content')
    </main>

    <x-site-footer />
</body>
</html>
