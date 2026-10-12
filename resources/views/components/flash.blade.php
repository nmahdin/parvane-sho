@if (session('status'))
    <div class="animate-rise flex items-start gap-3 rounded-2xl bg-leaf/10 px-4 py-3 text-sm text-forest ring-1 ring-leaf/30">
        <span class="mt-0.5">✅</span>
        <span>{{ session('status') }}</span>
    </div>
@endif

@if (session('error'))
    <div class="animate-rise flex items-start gap-3 rounded-2xl bg-gold/15 px-4 py-3 text-sm text-ink ring-1 ring-gold/50">
        <span class="mt-0.5">🔒</span>
        <span>{{ session('error') }}</span>
    </div>
@endif

@if ($errors->any())
    <div class="animate-rise flex items-start gap-3 rounded-2xl bg-danger-soft px-4 py-3 text-sm text-danger ring-1 ring-danger/20">
        <span class="mt-0.5">⚠️</span>
        <ul class="space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
