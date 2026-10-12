<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ورود به پنل مدیریت فقط با رمز عبور انجام می‌شود؛ این میان‌افزار بررسی می‌کند
 * که نشست فعلی قبلاً با رمز درست باز شده باشد.
 */
class EnsurePanelIsAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get(config('panel.session_key'), false)) {
            return redirect()->guest(route('panel.login'));
        }

        return $next($request);
    }
}
