<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

/**
 * پشتیبان کامل از داده‌های سایت — قبل از هر به‌روزرسانی یا مهاجرت اجرا شود:
 *
 *     php artisan parvane:backup
 *
 * - SQLite: خود فایل دیتابیس کپی می‌شود
 * - MySQL/MariaDB: با mysqldump (اگر روی سرور نصب باشد) خروجی .sql گرفته می‌شود
 * - در هر حالت، همه فایل‌های آپلودشده (storage/app) هم zip می‌شوند
 */
class BackupCommand extends Command
{
    protected $signature = 'parvane:backup
        {--dir= : پوشه مقصد پشتیبان (پیش‌فرض storage/app/backups)}';

    protected $description = 'پشتیبان کامل از دیتابیس و فایل‌های آپلودشده (قبل از به‌روزرسانی/مهاجرت اجرا شود)';

    public function handle(): int
    {
        $dir = $this->option('dir') ?: storage_path('app/backups');

        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            $this->error('پوشه پشتیبان ساخته نشد: '.$dir);

            return self::FAILURE;
        }

        $stamp = now()->format('Y-m-d-His');
        $connection = (string) config('database.default');

        $dbFile = $connection === 'sqlite'
            ? $this->backupSqlite($dir, $stamp)
            : $this->backupWithDump($dir, $stamp, $connection);

        if ($dbFile === null) {
            return self::FAILURE;
        }

        $zipFile = $this->backupUploadedFiles($dir, $stamp);

        $this->info('✅ پشتیبان گرفته شد:');
        $this->line('   دیتابیس: '.$dbFile.' ('.$this->humanSize((int) filesize($dbFile)).')');

        if ($zipFile !== null) {
            $this->line('   فایل‌ها: '.$zipFile.' ('.$this->humanSize((int) filesize($zipFile)).')');
        }

        $this->newLine();
        $this->warn('یادت باشد: «php artisan migrate» فقط تغییرات جدید را اضافه می‌کند و داده‌ها را پاک نمی‌کند؛');
        $this->warn('دستورهای ویران‌گر (migrate:fresh و...) در محیط production بسته‌اند.');

        return self::SUCCESS;
    }

    private function backupSqlite(string $dir, string $stamp): ?string
    {
        $path = config('database.connections.sqlite.database') ?: database_path('database.sqlite');

        if (! is_file($path)) {
            $this->error('فایل دیتابیس SQLite پیدا نشد: '.$path);

            return null;
        }

        $target = $dir.'/parvane-'.$stamp.'.sqlite';

        if (! copy($path, $target)) {
            $this->error('کپی فایل دیتابیس انجام نشد.');

            return null;
        }

        return $target;
    }

    private function backupWithDump(string $dir, string $stamp, string $connection): ?string
    {
        $config = config('database.connections.'.$connection);
        $binary = str_starts_with($connection, 'mysql') || $config['driver'] === 'mariadb'
            ? 'mysqldump'
            : ($config['driver'] === 'pgsql' ? 'pg_dump' : null);

        if ($binary === null) {
            $this->error('برای درایور «'.$config['driver'].'» پشتیبان خودکار نداریم؛ دستی از دیتابیس خروجی بگیر.');

            return null;
        }

        $target = $dir.'/parvane-'.$stamp.'.sql';

        $command = [$binary, '--host='.$config['host'], '--port='.$config['port'], '--user='.$config['username']];

        if (! empty($config['password'])) {
            $command[] = '--password='.$config['password'];
        }

        $command[] = $config['database'];

        try {
            $process = new Process($command, timeout: 300);
            $process->mustRun();

            file_put_contents($target, $process->getOutput());

            return $target;
        } catch (Throwable $exception) {
            $this->error('خروجی گرفتن از دیتابیس ناموفق بود: '.$exception->getMessage());
            $this->warn('اگر '.$binary.' روی سرور نصب نیست، دستی پشتیبان بگیر.');

            return null;
        }
    }

    private function backupUploadedFiles(string $dir, string $stamp): ?string
    {
        if (! class_exists(ZipArchive::class)) {
            $this->warn('افزونه zip در PHP فعال نیست؛ از پوشه storage/app دستی کپی بگیر.');

            return null;
        }

        $root = storage_path('app');
        $target = $dir.'/parvane-files-'.$stamp.'.zip';

        $zip = new ZipArchive;

        if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->warn('ساخت فایل zip ناموفق بود؛ از پوشه storage/app دستی کپی بگیر.');

            return null;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        $added = 0;

        foreach ($iterator as $file) {
            $relative = ltrim(str_replace($root, '', (string) $file), '/\\');

            // از خود پوشه پشتیبان‌ها دوباره پشتیبان نمی‌گیریم
            if ($file->isFile() && ! str_starts_with($relative, 'backups'.DIRECTORY_SEPARATOR)) {
                $zip->addFile((string) $file, $relative);
                $added++;
            }
        }

        $zip->close();

        if ($added === 0) {
            @unlink($target);

            return null;
        }

        return $target;
    }

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $size = (float) $bytes;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 1).' '.$units[$i];
    }
}
