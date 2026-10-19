<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\DayFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * مدیریت منابع: مدیریت فایل‌ها، ارتباط‌ها و حجم مصرفی.
 */
class SuperDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function makeChallenge(array $attributes = []): Challenge
    {
        return Challenge::create(array_merge([
            'title' => 'چالش داشبورد کل',
            'slug' => 'super-dashboard',
            'is_published' => true,
            'daily_release' => true,
            'starts_on' => today(),
        ], $attributes));
    }

    public function test_the_super_admin_can_open_the_super_dashboard(): void
    {
        $this->loginAsAdmin();

        $html = $this->get(route('panel.super.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('مدیریت منابع', $html);
        $this->assertStringContainsString('حجم مصرفی', $html);
        $this->assertStringContainsString('ارتباط‌ها', $html);
        $this->assertStringContainsString('مدیریت فایل‌ها', $html);
    }

    public function test_a_regular_admin_can_not_open_the_super_dashboard(): void
    {
        $this->loginAsAdmin($this->makeChallengeAdmin());

        $this->get(route('panel.super.dashboard'))->assertForbidden();
        $this->delete(route('panel.super.files.destroy', ['type' => 'day-files', 'file' => 1]))->assertForbidden();
        $this->post(route('panel.super.files.cleanup'))->assertForbidden();
    }

    public function test_the_sidebar_shows_the_super_link_only_to_the_super_admin(): void
    {
        $super = $this->loginAsAdmin();

        $html = $this->get(route('panel.dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('مدیریت منابع', $html);

        $this->post(route('panel.logout'));

        $this->loginAsAdmin($this->makeChallengeAdmin(['username' => 'other']));

        $html = $this->get(route('panel.dashboard'))->assertOk()->getContent();
        $this->assertStringNotContainsString('مدیریت منابع', $html);

        $this->assertNotNull($super);
    }

    public function test_the_relations_table_shows_each_challenge_with_its_links(): void
    {
        $this->loginAsAdmin();

        $challenge = $this->makeChallenge(['starts_on' => now()->subDay(), 'ends_on' => now()->addDays(6)]);
        $challenge->tasks()->create(['title' => 'کار نمونه', 'day_number' => 1]);
        $challenge->tips()->create(['title' => 'نکته نمونه', 'body' => 'متن']);
        $challenge->questions()->create(['question' => 'پرسش نمونه؟', 'day_number' => 1, 'is_published' => true]);
        $challenge->participants()->create(['name' => 'زهرا محمدی']);

        $html = $this->get(route('panel.super.dashboard'))->assertOk()->getContent();

        $row = substr($html, (int) strpos($html, 'چالش داشبورد کل'));

        $this->assertStringContainsString('چالش داشبورد کل', $html);
        $this->assertStringContainsString($challenge->admin?->username ?? '', $row);
        $this->assertStringContainsString('مدیریت', $row);
    }

    public function test_the_files_table_lists_day_files_with_size_and_status(): void
    {
        $this->loginAsAdmin();

        $challenge = $this->makeChallenge(['starts_on' => now()->subDay(), 'ends_on' => now()->addDays(6)]);

        $challenge->dayFiles()->create([
            'day_number' => 1,
            'title' => 'صوت بزرگ روز اول',
            'path' => 'day-files/big.wav',
            'original_name' => 'big.wav',
            'size' => 1024000,
            'mime' => 'audio/wav',
            'is_audio' => true,
        ]);

        Storage::disk('local')->put('day-files/big.wav', 'RIFF....WAVE');

        $html = $this->get(route('panel.super.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('صوت بزرگ روز اول', $html);
        $this->assertStringContainsString('big.wav', $html);
        $this->assertStringContainsString('🎧 صوتی', $html);
        $this->assertStringContainsString('✓ موجود', $html);
        $this->assertStringContainsString('۱۰۰۰ کیلوبایت', $html, 'حجم باید به واحد خوانای فارسی نشان داده شود.');
    }

    public function test_the_super_admin_can_delete_a_file_with_its_storage(): void
    {
        $this->loginAsAdmin();

        $challenge = $this->makeChallenge(['starts_on' => now()->subDay(), 'ends_on' => now()->addDays(6)]);

        $file = $challenge->dayFiles()->create([
            'day_number' => 2,
            'title' => 'صوت حذف‌شدنی',
            'path' => 'day-files/remove.wav',
            'original_name' => 'remove.wav',
            'size' => 2048,
            'mime' => 'audio/wav',
            'is_audio' => true,
        ]);

        Storage::disk('local')->put('day-files/remove.wav', 'RIFF....WAVE');

        $this->delete(route('panel.super.files.destroy', ['type' => 'day-files', 'file' => $file->id]))
            ->assertRedirect();

        $this->assertSame(0, DayFile::query()->count());
        Storage::disk('local')->assertMissing('day-files/remove.wav');
    }

    public function test_the_super_admin_can_delete_an_attachment_with_its_storage(): void
    {
        $this->loginAsAdmin();

        $challenge = $this->makeChallenge();

        $attachment = $challenge->attachments()->create([
            'title' => 'ضمیمه حذف‌شدنی',
            'path' => 'day-files/attach.txt',
            'original_name' => 'attach.txt',
            'size' => 12,
            'mime' => 'text/plain',
        ]);

        Storage::disk('local')->put('day-files/attach.txt', 'سلام');

        $this->delete(route('panel.super.files.destroy', ['type' => 'attachments', 'file' => $attachment->id]))
            ->assertRedirect();

        $this->assertSame(0, $challenge->attachments()->count());
        Storage::disk('local')->assertMissing('day-files/attach.txt');
    }

    public function test_cleanup_removes_only_records_whose_file_is_missing(): void
    {
        $this->loginAsAdmin();

        $challenge = $this->makeChallenge(['starts_on' => now()->subDay(), 'ends_on' => now()->addDays(6)]);

        $challenge->dayFiles()->create([
            'day_number' => 1,
            'title' => 'فایل گمشده',
            'path' => 'day-files/gone.wav',
            'original_name' => 'gone.wav',
            'size' => 10,
            'mime' => 'audio/wav',
            'is_audio' => true,
        ]);

        $kept = $challenge->dayFiles()->create([
            'day_number' => 1,
            'title' => 'فایل سالم',
            'path' => 'day-files/ok.wav',
            'original_name' => 'ok.wav',
            'size' => 10,
            'mime' => 'audio/wav',
            'is_audio' => true,
        ]);

        Storage::disk('local')->put('day-files/ok.wav', 'RIFF....WAVE');

        $this->post(route('panel.super.files.cleanup'))->assertRedirect();

        $this->assertSame(1, DayFile::query()->count());
        $this->assertSame('فایل سالم', DayFile::query()->firstOrFail()->title);
        $this->assertNotNull($kept);
    }

    public function test_the_files_table_can_be_filtered_by_type_and_search(): void
    {
        $this->loginAsAdmin();

        $challenge = $this->makeChallenge(['starts_on' => now()->subDay(), 'ends_on' => now()->addDays(6)]);

        $challenge->dayFiles()->create([
            'day_number' => 1,
            'title' => 'صوت فیلتر',
            'path' => 'day-files/a.wav',
            'original_name' => 'a.wav',
            'size' => 20,
            'mime' => 'audio/wav',
            'is_audio' => true,
        ]);

        $challenge->dayFiles()->create([
            'day_number' => 1,
            'title' => 'عکس فیلتر',
            'path' => 'day-files/a.png',
            'original_name' => 'a.png',
            'size' => 30,
            'mime' => 'image/png',
            'is_audio' => false,
        ]);

        $audioOnly = $this->get(route('panel.super.dashboard', ['type' => 'audio']))->assertOk()->getContent();
        $this->assertStringContainsString('صوت فیلتر', $audioOnly);
        $this->assertStringNotContainsString('عکس فیلتر', $audioOnly);

        $imageOnly = $this->get(route('panel.super.dashboard', ['type' => 'image']))->assertOk()->getContent();
        $this->assertStringContainsString('عکس فیلتر', $imageOnly);
        $this->assertStringNotContainsString('صوت فیلتر', $imageOnly);

        $search = $this->get(route('panel.super.dashboard', ['q' => 'صوت فیلتر']))->assertOk()->getContent();
        $this->assertStringContainsString('صوت فیلتر', $search);
        $this->assertStringNotContainsString('عکس فیلتر', $search);
    }
}
