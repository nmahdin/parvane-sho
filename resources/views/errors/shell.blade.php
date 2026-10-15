{{--
    قالب مشترک صفحه‌های خطا (۴۱۹، ۴۰۳، ۴۰۴...) با رنگ‌های خود سایت.
    متغیرها: icon، title، body، hint (اختیاری)، primary (اختیاری: متن و آدرس دکمه اصلی)
--}}
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#075741">

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="16x16 32x32 48x48">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    <title>{{ $title }} — {{ config('panel.site_name') }}</title>

    @vite(['resources/css/app.css'])
</head>
<body class="grid min-h-screen place-items-center bg-canvas px-4 py-10">
    <div class="w-full max-w-lg text-center">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
            <img src="{{ asset('favicon.svg') }}" alt="{{ config('panel.site_name') }}" class="size-12 rounded-2xl bg-white p-2 shadow-sm ring-1 ring-line">
            <span class="text-base font-semibold text-ink">{{ config('panel.site_name') }}</span>
        </a>

        <div class="animate-rise mt-6 rounded-3xl bg-paper p-7 shadow-sm ring-1 ring-line">
            <p class="text-4xl">{{ $icon }}</p>
            <h1 class="mt-4 text-lg">{{ $title }}</h1>
            <p class="mt-3 text-sm leading-7 text-ink/80">{{ $body }}</p>

            @isset($hint)
                <p class="mt-4 rounded-2xl bg-cream px-4 py-3 text-xs leading-6 text-muted">{{ $hint }}</p>
            @endisset

            <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-center">
                @isset($primary)
                    <a href="{{ $primary['url'] }}" class="btn btn-primary">{{ $primary['label'] }}</a>
                @endisset

                <a href="{{ route('home') }}" class="btn btn-white">صفحه اصلی سایت</a>
            </div>
        </div>
    </div>
</body>
</html>
