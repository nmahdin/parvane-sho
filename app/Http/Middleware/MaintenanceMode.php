<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * حالت تعمیر سایت (بدون نیاز به artisan down):
 * وقتی از پنل فعال شود، صفحه‌های عمومی سایت با وضعیت ۵۰۳ و پیام مدیر نشان
 * داده می‌شوند؛ پنل مدیریت و داشبورد اختصاصی شرکت‌کننده‌ها (لینک شخصی و
 * ورود با کد) باز می‌مانند تا کار چالش‌ها نخوابد.
 */
class MaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Setting::maintenanceMode()) {
            return $next($request);
        }

        // پنل و مسیرهای شرکت‌کننده‌ها از حالت تعمیر مستثنا هستند
        if ($request->is('panel', 'panel/*') || $request->routeIs('participant.*')) {
            return $next($request);
        }

        return response()
            ->view('maintenance', ['message' => Setting::maintenanceMessage()], 503)
            ->withHeaders(['Retry-After' => 3600]);
    }
}
