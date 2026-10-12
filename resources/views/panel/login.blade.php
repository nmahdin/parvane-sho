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

    <title>ورود به پنل مدیریت — {{ $site['site_name'] ?? config('panel.site_name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="grid min-h-screen place-items-center bg-gradient-to-tl from-forest via-jade to-leaf px-4 py-10">
    <div class="w-full max-w-md">
        <div class="mb-6 text-center text-white">
            <span class="mx-auto grid size-16 place-items-center rounded-3xl bg-white/15 backdrop-blur"><img src="{{ asset('favicon.svg') }}" alt="{{ $site['site_name'] ?? config('panel.site_name') }}" class="size-11"></span>
            <h1 class="mt-4 text-xl text-white">ورود به پنل {{ $site['site_name'] ?? config('panel.site_name') }}</h1>
        </div>

        <div class="animate-rise rounded-[2rem] bg-paper p-7 shadow-xl ring-1 ring-line">
            <x-flash />

            <form method="POST" action="{{ route('panel.login.attempt') }}" class="space-y-5 {{ $errors->any() || session('status') ? 'mt-4' : '' }}">
                @csrf

                <div>
                    <label for="username" class="label">نام کاربری</label>

                    <input id="username"
                           name="username"
                           type="text"
                           required
                           autofocus
                           dir="ltr"
                           autocomplete="username"
                           value="{{ old('username') }}"
                           class="input text-center">

                    @error('username')
                        <p class="mt-2 text-xs text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="label">رمز عبور</label>

                    <div class="relative">
                        <input id="password"
                               name="password"
                               type="password"
                               required
                               dir="ltr"
                               autocomplete="current-password"
                               class="input pe-12 text-center"
                               placeholder="••••••••">

                        <button type="button"
                                data-password-toggle="#password"
                                class="absolute inset-y-0 end-0 grid w-12 place-items-center text-muted/80 hover:text-ink/80"
                                aria-label="نمایش رمز">
                            👁
                        </button>
                    </div>

                    @error('password')
                        <p class="mt-2 text-xs text-danger">{{ $message }}</p>
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
