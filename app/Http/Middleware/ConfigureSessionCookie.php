<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * کوکی سشن پشت پروکسی https (پیش‌نمایش‌های ابری، دامنه https و iframe) باید
 * Secure و SameSite=None باشد، وگرنه مرورگر کوکی را برنمی‌گرداند و فرم‌ها
 * با خطای «صفحه منقضی شد» رد می‌شوند. روی http محلی رفتار پیش‌فرض (Lax)
 * حفظ می‌شود تا توسعه محلی هم بدون مشکل کار کند.
 */
class ConfigureSessionCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isSecure()) {
            config([
                'session.secure' => true,
                'session.same_site' => 'none',
            ]);
        }

        return $next($request);
    }
}
