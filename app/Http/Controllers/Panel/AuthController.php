<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * ورود مدیران (هر مدیر با نام کاربری و رمز عبور اختصاصی خودش).
 */
class AuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        // اگر همین حالا با یک حساب وارد شده باشد، به جای پرتاب کردنش به
        // داشبورد، فرم ورود را با یک یادآوری نشان می‌دهیم تا بتواند با حساب
        // دیگری (مثلاً مدیری که تازه ساخته) هم وارد شود.
        $current = Auth::guard('admin')->user();

        return view('panel.login', [
            'databaseReady' => $this->databaseReady(),
            'currentAdmin' => $current instanceof Admin ? $current : null,
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        // اگر دیتابیس آماده نباشد (مهاجرت‌ها اجرا نشده یا مدیری ساخته نشده)،
        // به جای خطای سرور، پیام روشن و راهنما نشان می‌دهیم.
        if (! $this->databaseReady()) {
            throw ValidationException::withMessages([
                'username' => 'هنوز هیچ مدیری در دیتابیس ثبت نشده است. در ترمینال پروژه دستور «php artisan migrate --seed» را اجرا کن (یا «php artisan parvane:setup»).',
            ]);
        }

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'نام کاربری را وارد کنید.',
            'password.required' => 'رمز عبور را وارد کنید.',
        ], [
            'username' => 'نام کاربری',
            'password' => 'رمز عبور',
        ]);

        $username = trim($validated['username']);
        $throttleKey = 'panel-login:'.$request->ip().'|'.mb_strtolower($username);
        $maxAttempts = (int) config('panel.login_max_attempts');
        $decaySeconds = (int) config('panel.login_decay_seconds');

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'username' => 'تعداد تلاش‌های ناموفق زیاد بوده است. '.fa_num($seconds).' ثانیه دیگر دوباره امتحان کنید.',
            ]);
        }

        // نام کاربری را بدون حساسیت به بزرگی و کوچکی حروف و فاصله‌های اضافه
        // پیدا می‌کنیم؛ اگر کسی «Maryam» ساخته باشد، ورود با «maryam» هم
        // باید کار کند (مخصوصاً روی SQLite که مقایسه به بزرگی/کوچکی حساس است).
        $candidates = Admin::query()
            ->whereRaw('LOWER(username) = ?', [mb_strtolower($username)])
            ->orderByDesc('is_super')
            ->orderBy('id')
            ->get();

        if ($candidates->isEmpty()) {
            RateLimiter::hit($throttleKey, $decaySeconds);

            throw ValidationException::withMessages([
                'username' => 'این نام کاربری در پنل ثبت نشده است. املای آن را بررسی کن یا از مدیر اصلی بخواه آن را بسازد.',
            ]);
        }

        $admin = null;

        // رمز را اول دقیقاً همان‌طور که تایپ شده و بعد بدون فاصله‌های اضافه
        // امتحان می‌کنیم تا رمزهایی که قبلاً با فاصلهٔ اشتباهی ساخته شده‌اند
        // هم کار کنند.
        $passwords = array_values(array_unique([
            $validated['password'],
            trim($validated['password']),
        ]));

        foreach ($candidates as $candidate) {
            foreach ($passwords as $password) {
                if (Auth::guard('admin')->attempt([
                    'username' => $candidate->username,
                    'password' => $password,
                ])) {
                    $admin = $candidate;

                    break 2;
                }
            }
        }

        if (! $admin instanceof Admin) {
            RateLimiter::hit($throttleKey, $decaySeconds);

            throw ValidationException::withMessages([
                'username' => 'نام کاربری درست است ولی رمز عبور نمی‌خواند. دقت کن حروف بزرگ و کوچک، فاصله‌های اضافه و زبان صفحه‌کلید (فارسی/انگلیسی) درست باشد؛ اگر رمز را فراموش کرده‌ای، از ترمینال دستور «php artisan parvane:admin '.$candidates->first()->username.' --password=رمز-جدید» را اجرا کن.',
            ]);
        }

        if (! $admin->isActive()) {
            Auth::guard('admin')->logout();
            RateLimiter::hit($throttleKey, $decaySeconds);

            throw ValidationException::withMessages([
                'username' => 'حساب شما غیرفعال است. با مدیر اصلی تماس بگیرید.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        $admin->forceFill(['last_login_at' => now()])->save();

        $request->session()->regenerate();

        return redirect()
            ->intended(route('panel.dashboard'))
            ->with('status', 'خوش آمدید '.$admin->name.' 🌿');
    }

    /**
     * آیا دیتابیس و جدول مدیران آماده است؟
     */
    private function databaseReady(): bool
    {
        try {
            return Schema::hasTable('admins') && Admin::query()->exists();
        } catch (Throwable) {
            return false;
        }
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('panel.login')->with('status', 'از پنل خارج شدید.');
    }
}
