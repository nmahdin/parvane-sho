<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * ورود مدیران (هر مدیر با نام کاربری و رمز عبور اختصاصی خودش).
 */
class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('panel.login');
    }

    public function login(Request $request): RedirectResponse
    {
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

        $throttleKey = 'panel-login:'.$request->ip().'|'.mb_strtolower($validated['username']);
        $maxAttempts = (int) config('panel.login_max_attempts');
        $decaySeconds = (int) config('panel.login_decay_seconds');

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'username' => 'تعداد تلاش‌های ناموفق زیاد بوده است. '.fa_num($seconds).' ثانیه دیگر دوباره امتحان کنید.',
            ]);
        }

        $credentials = [
            'username' => trim($validated['username']),
            'password' => $validated['password'],
        ];

        if (! Auth::guard('admin')->attempt($credentials)) {
            RateLimiter::hit($throttleKey, $decaySeconds);

            throw ValidationException::withMessages([
                'username' => 'نام کاربری یا رمز عبور درست نیست.',
            ]);
        }

        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

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

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('panel.login')->with('status', 'از پنل خارج شدید.');
    }
}
