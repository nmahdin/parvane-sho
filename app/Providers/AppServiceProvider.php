<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once app_path('Support/helpers.php');
    }

    public function boot(): void
    {
        // در محیط واقعی، دستورهای ویران‌گر دیتابیس بسته‌اند تا هیچ‌وقت
        // اتفاقی داده‌های جدول‌ها پاک نشود:
        // migrate:fresh، migrate:refresh، migrate:reset، migrate:rollback و db:wipe.
        // «php artisan migrate» (که فقط مهاجرت‌های جدید را اضافه می‌کند) آزاد است.
        DB::prohibitDestructiveCommands($this->app->isProduction());

        View::composer('*', function ($view) {
            $view->with('site', Setting::site());
        });
    }
}
