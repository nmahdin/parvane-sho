@if (session('status'))
    <div class="animate-rise flex items-start gap-3 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-600/10">
        <span class="mt-0.5">✅</span>
        <span>{{ session('status') }}</span>
    </div>
@endif

@if ($errors->any())
    <div class="animate-rise flex items-start gap-3 rounded-2xl bg-rose-50 px-4 py-3 text-sm text-rose-800 ring-1 ring-rose-600/10">
        <span class="mt-0.5">⚠️</span>
        <ul class="space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
