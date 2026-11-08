@php
    $siteName = $site['site_name'] ?? config('panel.site_name');
    $links = [
        ['label' => 'خانه', 'url' => route('home'), 'active' => request()->routeIs('home')],
        ['label' => 'چالش‌ها', 'url' => route('challenges.index'), 'active' => request()->routeIs('challenges.*')],
        ['label' => 'چطور کار می‌کند', 'url' => route('home').'#how', 'active' => false],
        ['label' => 'سوال‌های پرتکرار', 'url' => route('home').'#faq', 'active' => false],
    ];
@endphp

<header class="no-print sticky top-0 z-40 border-b border-line bg-white/85 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center gap-4 px-4 py-3 sm:px-6">
        <a href="{{ route('home') }}" class="flex items-center gap-3">
            <img src="{{ \App\Models\Setting::get('site_logo') ? route('branding', 'logo') : asset('favicon.svg') }}" alt="{{ $site['site_name'] ?? config('panel.site_name') }}" class="size-10 rounded-2xl bg-white p-1.5 shadow-sm" loading="lazy" decoding="async">
            <span class="text-base font-semibold text-ink">{{ $siteName }}</span>
        </a>

        <nav class="ms-auto hidden items-center gap-1 sm:flex">
            @foreach ($links as $link)
                <a href="{{ $link['url'] }}"
                   class="rounded-xl px-3 py-2 text-sm transition-colors {{ $link['active'] ? 'bg-brand-50 text-brand-700' : 'text-ink/80 hover:bg-line/60 hover:text-ink' }}">
                    {{ $link['label'] }}
                </a>
            @endforeach

            <a href="{{ route('participant.lookup') }}" class="btn btn-primary btn-sm ms-2">کد اختصاصی من</a>
        </nav>

        <a href="{{ route('participant.lookup') }}" class="btn btn-soft btn-sm ms-auto shrink-0 sm:hidden">کد من</a>

        <button type="button" data-theme-toggle class="icon-btn shrink-0" aria-label="تاریک کردن سایت">
                <svg data-theme-icon="moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4"><path d="M20.5 14.5A8.5 8.5 0 0 1 9.5 3.5a8.5 8.5 0 1 0 11 11z"/></svg>
                <svg data-theme-icon="sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" class="hidden size-4"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4l1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4m11.4-11.4l1.4-1.4"/></svg>
            </button>

        <button type="button" data-menu-toggle="#mobile-menu" class="btn btn-ghost sm:hidden" aria-label="منو">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>
    </div>

    <div id="mobile-menu" class="hidden border-t border-line bg-paper px-4 py-2 sm:hidden">
        @foreach ($links as $link)
            <a href="{{ $link['url'] }}" class="block rounded-xl px-3 py-2.5 text-sm text-ink hover:bg-line/60">{{ $link['label'] }}</a>
        @endforeach
        <a href="{{ route('participant.lookup') }}" class="block rounded-xl px-3 py-2.5 text-sm font-medium text-brand-700 hover:bg-brand-50">کد اختصاصی من</a>
    </div>
</header>
