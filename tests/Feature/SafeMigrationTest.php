<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * مهاجرت امن: داده‌های جدول‌ها با به‌روزرسانی پاک نمی‌شوند.
 * - «php artisan migrate» فقط مهاجرت‌های جدید را اضافه می‌کند
 * - دستورهای ویران‌گر (migrate:fresh و...) در محیط production بسته‌اند
 * - دستور parvane:backup قبل از به‌روزرسانی پشتیبان کامل می‌گیرد
 */
class SafeMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function makeChallenge(): Challenge
    {
        return Challenge::create([
            'title' => 'چالش با داده',
            'slug' => 'safe-migration',
            'is_published' => true,
            'daily_release' => true,
            'starts_on' => now()->subDay(),
            'ends_on' => now()->addDays(6),
        ]);
    }

    public function test_plain_migrate_keeps_existing_rows(): void
    {
        $challenge = $this->makeChallenge();
        $challenge->tasks()->create(['day_number' => 1, 'title' => 'کار مهم']);

        // اجرای دوباره «migrate» (بدون مهاجرت جدید) — داده‌ها باید دست‌نخورده بمانند
        $this->artisan('migrate')->assertSuccessful();

        $this->assertDatabaseHas('challenges', ['id' => $challenge->id]);
        $this->assertDatabaseHas('challenge_tasks', ['title' => 'کار مهم']);
    }

    public function test_destructive_commands_are_prohibited_in_production(): void
    {
        $challenge = $this->makeChallenge();

        // شبیه‌سازی محیط production و اجرای دوباره boot سرویس‌پروایدر
        app()->detectEnvironment(fn () => 'production');
        (new AppServiceProvider(app()))->boot();

        try {
            $exit = Artisan::call('migrate:fresh', ['--force' => true]);
            $output = Artisan::output();

            // دستور اجرا نشد...
            $this->assertNotSame(0, $exit);
            $this->assertStringContainsString('prohibited', $output);

            // ...و داده‌ها سر جایشان ماندند
            $this->assertDatabaseHas('challenges', ['id' => $challenge->id]);
        } finally {
            // پرچم ایستا است — برای تست‌های بعدی (RefreshDatabase) آزاد شود
            DB::prohibitDestructiveCommands(false);
            app()->detectEnvironment(fn () => 'testing');
        }
    }

    public function test_backup_command_copies_database_and_uploaded_files(): void
    {
        $this->makeChallenge();

        // دیتابیس تست در حافظه است؛ یک فایل واقعی به دستور معرفی می‌کنیم
        $fakeDb = tempnam(sys_get_temp_dir(), 'parvane-db-').'.sqlite';
        file_put_contents($fakeDb, 'fake-db-content');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $fakeDb]);

        // حداقل یک فایل آپلودشده هم باشد تا zip ساخته شود
        \Illuminate\Support\Facades\Storage::disk('local')->put('test-backup/note.txt', 'سلام');

        $dir = storage_path('app/backups-test-'.uniqid());

        $this->artisan('parvane:backup', ['--dir' => $dir])->assertSuccessful();

        $files = glob($dir.'/*') ?: [];

        $this->assertNotEmpty(preg_grep('/\.sqlite$/', $files), 'کپی دیتابیس ساخته نشد');
        $this->assertNotEmpty(preg_grep('/parvane-files-.*\.zip$/', $files), 'zip فایل‌ها ساخته نشد');

        array_map('unlink', glob($dir.'/*') ?: []);
        @rmdir($dir);
        @unlink($fakeDb);
    }
}
