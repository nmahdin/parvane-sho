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

    <title>@yield('title', 'پنل مدیریت') — {{ $site['site_name'] ?? config('panel.site_name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas">
    @php
        $currentAdmin = auth('admin')->user();

        $navigation = [
            ['label' => 'داشبورد', 'short' => 'داشبورد', 'icon' => '📊', 'url' => route('panel.dashboard'), 'active' => request()->routeIs('panel.dashboard')],
            ['label' => 'چالش‌ها', 'short' => 'چالش‌ها', 'icon' => '🦋', 'url' => route('panel.challenges.index'), 'active' => request()->routeIs('panel.challenges.*') || request()->routeIs('panel.tasks.*')],
            ['label' => 'شرکت‌کننده‌ها', 'short' => 'شرکت‌کننده', 'icon' => '👥', 'url' => route('panel.participants.index'), 'active' => request()->routeIs('panel.participants.*')],
            ['label' => 'تنظیمات', 'short' => 'تنظیمات', 'icon' => '⚙️', 'url' => route('panel.settings.edit'), 'active' => request()->routeIs('panel.settings.*')],
        ];

        if ($currentAdmin?->isSuper()) {
            $navigation[] = ['label' => 'داشبورد مدیر کل', 'short' => 'مدیر کل', 'icon' => '🗂️', 'url' => route('panel.super.dashboard'), 'active' => request()->routeIs('panel.super.*')];
            $navigation[] = ['label' => 'مدیران', 'short' => 'مدیران', 'icon' => '🧑‍💼', 'url' => route('panel.admins.index'), 'active' => request()->routeIs('panel.admins.*')];
        }
    @endphp

    <div class="mx-auto flex max-w-[90rem] gap-6 px-4 py-5 pb-28 sm:px-6 lg:py-6 lg:pb-6">

        {{-- نوار کناری: یک باکس واحد — سربرگ، ناوبری و کارهای پایین --}}
        <aside class="hidden w-64 shrink-0 lg:block">
            <div class="panel-sidebar sticky top-6">
                <a href="{{ route('panel.dashboard') }}" class="flex items-center gap-3 border-b border-line px-4 py-4">
                    <img src="{{ asset('favicon.svg') }}" alt="پنل مدیریت" class="size-10 rounded-2xl bg-white p-1.5 ring-1 ring-line">
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-semibold text-ink">{{ $site['site_name'] ?? config('panel.site_name') }}</span>
                        <span class="block text-[11px] text-hint">پنل مدیریت چالش‌ها</span>
                    </span>
                </a>

                <nav class="px-3 py-3" aria-label="ناوبری پنل">
                    <p class="px-2 pb-2 text-[10px] font-semibold tracking-wide text-hint">بخش‌ها</p>

                    <ul class="space-y-1">
                        @foreach ($navigation as $item)
                            <li>
                                <a href="{{ $item['url'] }}"
                                   class="sidebar-link {{ $item['active'] ? 'is-active' : '' }}"
                                   @if ($item['active']) aria-current="page" @endif>
                                    <span class="grid size-8 shrink-0 place-items-center rounded-xl bg-paper text-sm ring-1 ring-line">{{ $item['icon'] }}</span>
                                    <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                                    @if ($item['active'])
                                        <span class="shrink-0 text-jade" aria-hidden="true">◂</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <div class="space-y-2 border-t border-line px-3 py-3">
                    <a href="{{ route('home') }}" target="_blank" class="sidebar-link">
                        <span class="grid size-8 shrink-0 place-items-center rounded-xl bg-paper text-sm ring-1 ring-line">🌐</span>
                        <span class="min-w-0 flex-1 truncate">مشاهده سایت</span>
                    </a>

                    <form method="POST" action="{{ route('panel.logout') }}">
                        @csrf
                        <button type="submit" class="sidebar-link w-full text-danger hover:bg-danger-soft hover:text-danger">
                            <span class="grid size-8 shrink-0 place-items-center rounded-xl bg-paper text-sm ring-1 ring-line">🚪</span>
                            <span class="min-w-0 flex-1 truncate">خروج از پنل</span>
                        </button>
                    </form>

                    <p class="px-2 pt-1 text-[11px] leading-5 text-hint">
                        رمز عبور خودت را از «تنظیمات» می‌توانی عوض کنی.
                    </p>
                </div>
            </div>
        </aside>

        {{-- محتوای اصلی --}}
        <div class="min-w-0 flex-1 space-y-6">
            <header class="sticky top-0 z-30 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-paper/95 px-4 py-3 shadow-sm ring-1 ring-line backdrop-blur sm:gap-4 sm:px-5 lg:static lg:rounded-3xl">
                <div class="min-w-0">
                    <h1 class="truncate text-lg">@yield('page-title', 'داشبورد')</h1>
                    <p id="page-subtitle" class="mt-0.5 truncate text-xs text-muted">@yield('page-subtitle', 'مدیریت چالش‌ها، شرکت‌کننده‌ها و لینک‌های اختصاصی')</p>
                </div>

                <div class="flex items-center gap-2">
                    @yield('page-actions')

                    @if ($currentAdmin)
                        <span class="hidden items-center gap-2 rounded-2xl bg-cream px-3 py-2 text-[11px] text-ink/80 ring-1 ring-line sm:flex">
                            <span>👤</span>
                            {{ $currentAdmin->name }}
                            <span class="badge {{ $currentAdmin->roleClasses() }}">{{ $currentAdmin->roleLabel() }}</span>
                        </span>
                    @endif

                    <form method="POST" action="{{ route('panel.logout') }}" class="lg:hidden">
                        @csrf
                        <button type="submit" class="btn btn-white btn-sm">خروج</button>
                    </form>
                </div>
            </header>

            <x-flash />

            <main class="space-y-6">
                @yield('content')
            </main>

            <footer class="pt-4 text-center text-[11px] text-hint">
                {{ $site['site_name'] ?? config('panel.site_name') }} — امروز {{ fa_date(now(), true) }}
            </footer>
        </div>
    </div>

    {{-- ناوبری پایین صفحه در موبایل: دسترسی یک‌لمسی به بخش‌های اصلی --}}
    <nav class="no-print fixed inset-x-0 bottom-0 z-40 border-t border-line bg-paper/95 shadow-[0_-4px_16px_rgba(36,71,60,0.06)] backdrop-blur lg:hidden">
        <div class="safe-bottom mx-auto flex max-w-xl items-stretch">
            @foreach ($navigation as $item)
                <a href="{{ $item['url'] }}"
                   class="flex flex-1 flex-col items-center gap-1 px-1 pt-2 pb-1 text-[11px] leading-4 transition-colors {{ $item['active'] ? 'font-semibold text-brand-700' : 'text-muted' }}"
                   @if ($item['active']) aria-current="page" @endif>
                    <span class="text-lg leading-none {{ $item['active'] ? '' : 'opacity-70' }}">{{ $item['icon'] }}</span>
                    <span class="truncate">{{ $item['short'] }}</span>
                </a>
            @endforeach
        </div>
    </nav>
</body>
</html>
