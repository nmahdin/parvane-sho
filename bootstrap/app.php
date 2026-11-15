<?php

use App\Http\Middleware\ConfigureSessionCookie;
use App\Http\Middleware\EnsureAdminIsSuper;
use App\Http\Middleware\EnsurePanelIsAuthenticated;
use App\Http\Middleware\MaintenanceMode;
use App\Support\UploadLimits;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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

        // حالت تعمیر سایت (از پنل فعال/غیرفعال می‌شود)
        $middleware->appendToGroup('web', MaintenanceMode::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        /* وقتی حجم درخواست از post_max_size بیشتر شود، PHP کل $_POST و
           $_FILES را خالی می‌کند؛ آن‌وقت Laravel فقط «توکن CSRF نامعتبر»
           می‌بیند و کاربر پیام بی‌ربط (یا «ذخیره انجام نشد») می‌گیرد.
           اینجا پیام درست دربارهٔ حجم فایل داده می‌شود. */
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            // Laravel پیش از این هندلرها TokenMismatchException را به
            // HttpException با کد ۴۱۹ تبدیل می‌کند.
            if ($exception->getStatusCode() !== 419) {
                return null;
            }

            $limit = UploadLimits::serverMaxBytes();
            $length = (int) $request->server('CONTENT_LENGTH');

            if ($limit <= 0 || $length <= $limit) {
                return null;
            }

            $message = 'حجم فایل بیشتر از حد مجاز سرور ('.UploadLimits::serverMaxForHumans().') است.'
                .' برای آپلود تا ۶۰ مگابایت، upload_max_filesize و post_max_size را در php.ini بالا ببر.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => $message], 413);
            }

            return back()->withErrors(['file' => $message])->withInput();
        });
    })->create();
