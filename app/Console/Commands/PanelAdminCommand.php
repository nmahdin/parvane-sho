<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * مدیریت حساب مدیران از ترمینال (بیشتر برای وقتی که ورود یک مدیر کار نمی‌کند).
 *
 * فهرست مدیران:
 *     php artisan parvane:admin --list
 *
 * عوض کردن رمز یک مدیر:
 *     php artisan parvane:admin maryam --password=RamzJadid123
 *
 * فعال کردن حساب:
 *     php artisan parvane:admin maryam --active
 */
class PanelAdminCommand extends Command
{
    protected $signature = 'parvane:admin
        {username? : نام کاربری مدیری که ساخته یا اصلاح می‌شود}
        {--name= : نام نمایشی مدیر}
        {--password= : رمز عبور (اگر نداده باشی، پرسیده می‌شود)}
        {--super : این مدیر، مدیر اصلی شود}
        {--no-super : این مدیر، مدیر چالش شود}
        {--active : حساب فعال شود}
        {--inactive : حساب غیرفعال شود}
        {--list : فقط فهرست مدیران را نشان بده}';

    protected $description = 'ساخت، فهرست یا اصلاح حساب مدیران پنل (مثلاً عوض کردن رمز)';

    public function handle(): int
    {
        if (! Schema::hasTable('admins')) {
            $this->components->error('جدول مدیران وجود ندارد. اول «php artisan migrate --seed» یا «php artisan parvane:setup» را اجرا کن.');

            return self::FAILURE;
        }

        $username = is_string($this->argument('username')) ? trim((string) $this->argument('username')) : '';

        if ($this->option('list') || ($username === '' && ! $this->hasChanges())) {
            $this->renderList();

            return self::SUCCESS;
        }

        if ($username === '') {
            $this->components->error('نام کاربری را بگو؛ مثلاً: php artisan parvane:admin maryam --password=Ramz123');

            return self::FAILURE;
        }

        if (! preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
            $this->components->error('نام کاربری فقط با حروف انگلیسی، عدد، نقطه، خط تیره و زیرخط مجاز است.');

            return self::FAILURE;
        }

        $admin = Admin::query()->whereRaw('LOWER(username) = ?', [mb_strtolower($username)])->first();

        return $admin instanceof Admin
            ? $this->updateExisting($admin)
            : $this->createNew($username);
    }

    /**
     * آیا کاربر با سوییچ‌ها چیزی برای تغییر خواسته است؟
     */
    private function hasChanges(): bool
    {
        return $this->option('name') !== null
            || $this->option('password') !== null
            || $this->option('super')
            || $this->option('no-super')
            || $this->option('active')
            || $this->option('inactive');
    }

    private function renderList(): void
    {
        $admins = Admin::query()
            ->withCount('challenges')
            ->orderByDesc('is_super')
            ->orderBy('username')
            ->get();

        if ($admins->isEmpty()) {
            $this->components->warn('هیچ مدیری ثبت نشده است. «php artisan parvane:setup» را اجرا کن.');

            return;
        }

        $this->table(
            ['#', 'نام', 'نام کاربری', 'نقش', 'وضعیت', 'چالش‌ها', 'آخرین ورود'],
            $admins->map(fn (Admin $admin): array => [
                $admin->id,
                $admin->name,
                $admin->username,
                $admin->isSuper() ? 'مدیر اصلی' : 'مدیر چالش',
                $admin->isActive() ? 'فعال' : 'غیرفعال',
                (string) $admin->challenges_count,
                $admin->last_login_at?->diffForHumans() ?? '—',
            ])->all(),
        );

        $this->components->info('برای عوض کردن رمز: php artisan parvane:admin <نام کاربری> --password=<رمز-جدید>');
    }

    private function updateExisting(Admin $admin): int
    {
        $changes = [];

        if (($name = $this->option('name')) !== null) {
            $changes['name'] = (string) $name;
        }

        if (($password = $this->option('password')) !== null) {
            $changes['password'] = trim((string) $password);
        }

        if ($this->option('super')) {
            $changes['is_super'] = true;
        }

        if ($this->option('no-super')) {
            $changes['is_super'] = false;
        }

        if ($this->option('active')) {
            $changes['is_active'] = true;
        }

        if ($this->option('inactive')) {
            $changes['is_active'] = false;
        }

        if ($changes === []) {
            $this->components->warn('چیزی برای تغییر ندادی. رمز را این‌طور عوض کن: --password=...');

            return self::SUCCESS;
        }

        try {
            $admin->update($changes);
        } catch (Throwable $e) {
            $this->components->error('ذخیره نشد: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->components->info('حساب «'.$admin->username.'» به‌روز شد.');

        if (array_key_exists('password', $changes)) {
            $this->line('  رمز عبور جدید: <fg=green>'.$changes['password'].'</>');
        }

        $this->line('  وضعیت: '.($admin->refresh()->isActive() ? 'فعال' : 'غیرفعال').' — نقش: '.$admin->roleLabel());

        return self::SUCCESS;
    }

    private function createNew(string $username): int
    {
        $password = $this->option('password') !== null
            ? trim((string) $this->option('password'))
            : (string) $this->secret('رمز عبور برای «'.$username.'»');

        if (mb_strlen($password) < 6) {
            $this->components->error('رمز عبور باید حداقل ۶ کاراکتر باشد.');

            return self::FAILURE;
        }

        $name = (string) ($this->option('name') ?? Str::of($username)->headline());

        try {
            $admin = Admin::create([
                'name' => $name,
                'username' => $username,
                'password' => $password,
                'is_super' => (bool) $this->option('super'),
                'is_active' => ! $this->option('inactive'),
            ]);
        } catch (Throwable $e) {
            $this->components->error('ساخته نشد: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->components->info('مدیر «'.$admin->name.'» ساخته شد.');
        $this->line('  نام کاربری: <fg=green>'.$admin->username.'</>');
        $this->line('  رمز عبور: <fg=green>'.$password.'</>');
        $this->line('  نقش: '.$admin->roleLabel());

        return self::SUCCESS;
    }
}
