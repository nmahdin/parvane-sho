<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#7c3aed">

    <title>ورود به پنل مدیریت — {{ $site['site_name'] ?? config('panel.site_name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen place-items-center bg-gradient-to-tl from-brand-700 via-brand-600 to-fuchsia-600 px-4 py-10">
    <div class="w-full max-w-md">
        <div class="mb-6 text-center text-white">
            <span class="mx-auto grid size-14 place-items-center rounded-3xl bg-white/15 text-2xl backdrop-blur">🦋</span>
            <h1 class="mt-4 text-xl text-white">ورود مدیر {{ $site['site_name'] ?? config('panel.site_name') }}</h1>
        </div>

        <div class="animate-rise rounded-[2rem] bg-white p-7 shadow-xl ring-1 ring-slate-900/5">
            <x-flash />

            <form method="POST" action="{{ route('panel.login.attempt') }}" class="space-y-5 {{ $errors->any() || session('status') ? 'mt-4' : '' }}">
                @csrf

                <div>
                    <label for="password" class="label">رمز عبور</label>

                    <div class="relative">
                        <input id="password"
                               name="password"
                               type="password"
                               required
                               autofocus
                               autocomplete="current-password"
                               class="input pe-12"
                               placeholder="••••••••">

                        <button type="button"
                                data-password-toggle="#password"
                                class="absolute inset-y-0 end-0 grid w-12 place-items-center text-slate-400 hover:text-slate-600"
                                aria-label="نمایش رمز">
                            👁
                        </button>
                    </div>

                    @error('password')
                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary w-full py-3">ورود به پنل</button>
            </form>

        </div>

        <p class="mt-6 text-center text-xs text-white/75">
            <a href="{{ route('home') }}" class="hover:text-white">بازگشت به سایت</a>
        </p>
    </div>
</body>
</html>
