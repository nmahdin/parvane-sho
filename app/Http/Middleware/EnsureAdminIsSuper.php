<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * فقط مدیر اصلی به مدیریت مدیران و تنظیمات کلی سایت دسترسی دارد.
 */
class EnsureAdminIsSuper
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Auth::guard('admin')->user()?->isSuper(), 403);

        return $next($request);
    }
}
