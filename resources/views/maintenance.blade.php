<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#659287">

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="16x16 32x32 48x48">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <title>در حال تعمیر — {{ $site['site_name'] ?? config('panel.site_name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen place-items-center bg-canvas px-4 py-10">
    <main class="w-full max-w-md text-center">
        <div class="animate-rise card space-y-5 py-10">
            <span class="mx-auto grid size-20 place-items-center rounded-3xl bg-white p-3 ring-1 ring-line">
                <img src="{{ asset('favicon.svg') }}" alt="پروانه شو" class="size-full" loading="lazy" decoding="async">
            </span>

            <div>
                <span class="badge bg-gold/15 text-gold-ink ring-gold/50">🛠️ در حال تعمیر</span>

                <h1 class="mt-4 text-xl text-forest">{{ $site['site_name'] ?? config('panel.site_name') }}</h1>

                <p class="mt-4 text-sm leading-8 text-ink">{{ $message }}</p>
            </div>

            <p class="text-[11px] leading-6 text-hint">
                اگر لینک اختصاصی داری، همچنان می‌توانی وارد داشبورد خودت بشوی.
                <br>
                کمی دیگر دوباره سر بزن. 🦋
            </p>
        </div>
    </main>
</body>
</html>
