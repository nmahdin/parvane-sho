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
            <span class="grid size-10 place-items-center rounded-2xl bg-gradient-to-tl from-forest to-leaf text-lg text-white shadow-sm">🦋</span>
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

        <button type="button" data-menu-toggle="#mobile-menu" class="btn btn-ghost ms-auto sm:hidden" aria-label="منو">
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
