<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

/**
 * مدیران پنل:
 * - مدیر اصلی با نام کاربری و رمز پیش‌فرض (config/panel.php و .env).
 * - دو مدیر چالش نمونه تا ببینی هر مدیر چالش‌ها و شرکت‌کننده‌های خودش را دارد.
 *
 * اجرای دوباره چیزی را خراب نمی‌کند: مدیرهای موجود دست‌نخورده می‌مانند و
 * رمز کسی عوض نمی‌شود.
 *
 * مستقل هم اجرا می‌شود: php artisan db:seed --class=AdminSeeder
 */
class AdminSeeder extends Seeder
{
    /**
     * مدیران چالش نمونه: نام کاربری => [نام نمایشی, رمز عبور].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const DEMO_ADMINS = [
        'maryam' => ['مریم احمدی', 'maryam12345'],
        'sara' => ['سارا کریمی', 'sara12345'],
    ];

    public function run(): void
    {
        $superUsername = (string) config('panel.default_admin_username', 'admin');
        $super = $this->superAdmin($superUsername);

        $created = [];

        foreach (self::DEMO_ADMINS as $username => [$name, $password]) {
            if (Admin::query()->where('username', $username)->exists()) {
                continue;
            }

            Admin::query()->create([
                'name' => $name,
                'username' => $username,
                'password' => $password,
                'is_super' => false,
                'is_active' => true,
            ]);

            $created[$username] = $password;
        }

        $this->say('مدیر اصلی: '.$super->username.' ('.config('panel.default_admin_password').')');

        foreach ($created as $username => $password) {
            $this->say('مدیر چالش نمونه: '.$username.' ('.$password.') — نقش: مدیر چالش');
        }

        if ($created === []) {
            $this->say('مدیران نمونه از قبل وجود دارند؛ چیزی عوض نشد.');
        }

        $this->say('فهرست مدیران و عوض کردن رمزشان: php artisan parvane:admin --list');
    }

    /**
     * مدیر اصلی را می‌سازد (اگر نباشد) و برمی‌گرداند؛ رمز موجود را عوض نمی‌کند.
     */
    private function superAdmin(string $username): Admin
    {
        $existing = Admin::query()->where('username', $username)->first();

        if ($existing) {
            if (! $existing->isSuper() || ! $existing->isActive()) {
                $existing->update(['is_super' => true, 'is_active' => true]);
            }

            return $existing;
        }

        // اگر جدول مدیران خالی است، این اولین مدیر است؛ پس مدیر اصلی می‌شود.
        return Admin::query()->create([
            'name' => (string) config('panel.default_admin_name', 'مدیر اصلی'),
            'username' => $username,
            'password' => (string) config('panel.default_admin_password', 'a12345678'),
            'is_super' => true,
            'is_active' => true,
        ]);
    }

    private function say(string $message): void
    {
        if ($this->command) {
            $this->command->info($message);
        }
    }
}
