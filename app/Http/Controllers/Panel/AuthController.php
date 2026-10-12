<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * ورود به پنل با «فقط رمز عبور» — بدون نام کاربری و ایمیل.
 */
class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('panel.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'رمز عبور را وارد کنید.',
        ]);

        $throttleKey = 'panel-login:'.$request->ip();
        $maxAttempts = (int) config('panel.login_max_attempts');
        $decaySeconds = (int) config('panel.login_decay_seconds');

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'password' => 'تعداد تلاش‌های ناموفق زیاد بوده است. '.fa_num($seconds).' ثانیه دیگر دوباره امتحان کنید.',
            ]);
        }

        if (! $this->passwordMatches((string) $request->input('password'))) {
            RateLimiter::hit($throttleKey, $decaySeconds);

            throw ValidationException::withMessages([
                'password' => 'رمز عبور اشتباه است.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        $request->session()->regenerate();
        $request->session()->put(config('panel.session_key'), true);
        $request->session()->put('panel_logged_in_at', now()->timestamp);

        return redirect()
            ->intended(route('panel.dashboard'))
            ->with('status', 'خوش آمدید 🌿');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(config('panel.session_key'));
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('panel.login')->with('status', 'از پنل خارج شدید.');
    }

    /**
     * رمز ذخیره‌شده در پنل (هش) یا رمز پیش‌فرض فایل .env را بررسی می‌کند.
     */
    private function passwordMatches(string $password): bool
    {
        $hash = Setting::panelPasswordHash();

        if ($hash) {
            return Hash::check($password, $hash);
        }

        $default = (string) config('panel.password');

        return $default !== '' && hash_equals($default, $password);
    }
}
