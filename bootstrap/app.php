<?php

use App\Http\Middleware\ConfigureSessionCookie;
use App\Http\Middleware\EnsureAdminIsSuper;
use App\Http\Middleware\EnsurePanelIsAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // پروکسی/بالانسر جلوی اپلیکیشن (مثل هاست‌های ابری و دامنه‌های https)
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '*'));

        // باید داخل گروه web و پیش از StartSession اجرا شود تا کوکی سشن
        // پشت پروکسی https درست (Secure + SameSite=None) تنظیم شود.
        $middleware->prependToGroup('web', ConfigureSessionCookie::class);

        $middleware->alias([
            'panel.auth' => EnsurePanelIsAuthenticated::class,
            'panel.super' => EnsureAdminIsSuper::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
