<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * ورود به پنل با نام کاربری و رمز عبور انجام می‌شود؛ این میان‌افزار بررسی می‌کند
 * که مدیر وارد شده باشد و حسابش هنوز فعال باشد.
 */
class EnsurePanelIsAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Admin|null $admin */
        $admin = Auth::guard('admin')->user();

        if (! $admin) {
            return redirect()->guest(route('panel.login'));
        }

        if (! $admin->isActive()) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('panel.login')
                ->with('error', 'حساب شما غیرفعال شده است. با مدیر اصلی تماس بگیرید.');
        }

        return $next($request);
    }
}
