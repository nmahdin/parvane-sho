<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Database\Seeders\DemoChallengeSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * آماده‌سازی پنل با یک دستور: اجرای مهاجرت‌ها، ساخت مدیر اصلی و
 * (در صورت درخواست) ساخت داده‌های نمونه.
 *
 * php artisan parvane:setup
 * php artisan parvane:setup --demo
 */
class PanelSetupCommand extends Command
{
    protected $signature = 'parvane:setup {--demo : چالش و شرکت‌کننده‌های نمونه هم ساخته شوند}';

    protected $description = 'آماده‌سازی دیتابیس و ساخت مدیر اصلی پنل (نام کاربری و رمز عبور پیش‌فرض)';

    public function handle(): int
    {
        $this->components->info('۱) اجرای مهاجرت‌ها...');
        $this->call('migrate', ['--force' => true]);

        if (! Schema::hasTable('admins')) {
            $this->components->error('جدول مدیران ساخته نشد.');
            $this->components->warn('اگر دیتابیس نیمه‌کاره مانده، این دستور همه‌چیز را از نو می‌سازد (دیتای قبلی پاک می‌شود): php artisan migrate:fresh --seed');

            return self::FAILURE;
        }

        $this->components->info('۲) بررسی مدیر اصلی...');
        $admin = $this->ensureSuperAdmin();

        $this->components->info('۳) ثبت تنظیمات سایت...');
        $this->call('db:seed', ['--class' => SettingsSeeder::class, '--force' => true]);

        if ($this->option('demo')) {
            $this->components->info('۴) ساخت چالش و شرکت‌کننده‌های نمونه...');
            $this->call('db:seed', ['--class' => DemoChallengeSeeder::class, '--force' => true]);
        }

        $this->newLine();
        $this->components->info('پنل آماده است ✅');
        $this->line('  نشانی پنل: <fg=green>'.url('/panel').'</>');
        $this->line('  نام کاربری: <fg=green>'.$admin->username.'</>');
        $this->line('  رمز عبور: <fg=green>'.config('panel.default_admin_password').'</>');
        $this->newLine();
        $this->components->warn('اگر این رمز را از قبل عوض کرده‌ای، همان رمز قبلی معتبر است.');
        $this->renderStatus();

        return self::SUCCESS;
    }

    /**
     * گزارش کوتاه وضعیت، تا اگر چیزی سر جایش نبود همان اول معلوم شود.
     */
    private function renderStatus(): void
    {
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database", '');
        $pending = $this->pendingMigrations();

        $this->newLine();
        $this->line('  <fg=gray>گزارش وضعیت</>');
        $this->line('  دیتابیس: '.$connection.' — '.$database);
        $this->line('  مهاجرت‌های اجرانشده: '.fa_num($pending));
        $this->line('  تعداد مدیران: '.fa_num((int) Admin::query()->count()));
        $this->line('  فهرست مدیران و عوض کردن رمزشان: <fg=green>php artisan parvane:admin --list</>');

        if ($pending > 0) {
            $this->components->warn('چند مهاجرت اجرا نشده است؛ اگر بخشی از پنل کار نکرد «php artisan migrate --force» را بزن.');
        }
    }

    private function pendingMigrations(): int
    {
        try {
            $migrator = app('migrator');
            $files = array_keys($migrator->getMigrationFiles(database_path('migrations')));

            return count(array_diff($files, $migrator->getRepository()->getRan()));
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * اگر جدول مدیران خالی باشد، مدیر اصلی را می‌سازد.
     */
    private function ensureSuperAdmin(): Admin
    {
        $username = (string) config('panel.default_admin_username', 'admin');

        try {
            $existing = Admin::query()->where('username', $username)->first();

            if ($existing) {
                if (! $existing->isSuper()) {
                    $existing->update(['is_super' => true, 'is_active' => true]);
                    $this->components->warn('حساب «'.$username.'» به مدیر اصلی تبدیل شد.');
                }

                return $existing;
            }

            $admin = Admin::create([
                'name' => (string) config('panel.default_admin_name', 'مدیر اصلی'),
                'username' => $username,
                'password' => (string) config('panel.default_admin_password', 'a12345678'),
                'is_super' => true,
                'is_active' => true,
            ]);

            $this->components->info('مدیر اصلی «'.$username.'» ساخته شد.');

            return $admin;
        } catch (Throwable $e) {
            $this->components->error('ساخت مدیر اصلی ناموفق بود: '.$e->getMessage());

            throw $e;
        }
    }
}
