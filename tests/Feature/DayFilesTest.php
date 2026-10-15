<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Support\ChallengeExport;
use App\Support\Jalali;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * فایل‌ها و فایل‌های صوتی هر روز: افزودن از پنل، نمایش داخل کارت همان روز،
 * پخش‌کننده اختصاصی صوت (نه پخش‌کننده پیش‌فرض مرورگر) و قفل بودن روزهای بعدی.
 */
class DayFilesTest extends TestCase
{
    use RefreshDatabase;

    private function makeChallenge(array $attributes = []): Challenge
    {
        return Challenge::create(array_merge([
            'title' => 'چالش فایل روزها',
            'slug' => 'day-files',
            'is_published' => true,
            'daily_release' => true,
            'starts_on' => today(),
        ], $attributes));
    }

    public function test_an_admin_can_attach_a_file_and_an_audio_file_to_a_day(): void
    {
        $this->loginAsAdmin();
        $challenge = $this->makeChallenge(['starts_on' => now()->subDay(), 'ends_on' => now()->addDays(6)]);

        $this->post(route('panel.day-files.store', $challenge), [
            'day_number' => 1,
            'title' => 'برنامه روز اول',
            'file' => UploadedFile::fake()->createWithContent('plan.txt', 'برنامه روز اول'),
        ])->assertRedirect();

        $this->post(route('panel.day-files.store', $challenge), [
            'day_number' => 1,
            'title' => 'صوت روز اول',
            'file' => UploadedFile::fake()->create('note.mp3', 64, 'audio/mpeg'),
        ])->assertRedirect();

        $files = $challenge->dayFiles()->orderBy('id')->get();

        $this->assertCount(2, $files);
        $this->assertSame('برنامه روز اول', $files[0]->title);
        $this->assertFalse($files[0]->is_audio, 'فایل متنی نباید صوتی ثبت شود.');
        $this->assertSame('صوت روز اول', $files[1]->title);
        $this->assertTrue($files[1]->is_audio, 'فایل mp3 باید صوتی ثبت شود.');
        $this->assertSame(1, $files[0]->day_number);
        $this->assertSame(1, $files[1]->day_number);

        foreach ($files as $file) {
            Storage::disk('local')->assertExists($file->path);
        }

        // در پنل، باکس همان روز هر دو فایل را نشان می‌دهد
        $panel = $this->get(route('panel.challenges.show', $challenge))->assertOk()->getContent();

        $this->assertStringContainsString('فایل‌ها و فایل‌های صوتی این روز', $panel);
        $this->assertStringContainsString('برنامه روز اول', $panel);
        $this->assertStringContainsString('صوت روز اول', $panel);
        $this->assertStringContainsString('🎧 فایل صوتی', $panel);
    }

    public function test_the_day_number_can_be_given_as_a_jalali_date(): void
    {
        $this->loginAsAdmin();
        $challenge = $this->makeChallenge(['starts_on' => now()->subDays(2), 'ends_on' => now()->addDays(4)]);

        $this->post(route('panel.day-files.store', $challenge), [
            'date' => Jalali::inputValue($challenge->dateForDay(2)),
            'title' => 'فایل روز دوم',
            'file' => UploadedFile::fake()->createWithContent('day2.txt', 'روز دوم'),
        ])->assertRedirect();

        $file = $challenge->dayFiles()->firstOrFail();

        $this->assertSame(2, $file->day_number, 'فایل باید در روزی بنشیند که تاریخش داده شده.');
    }

    public function test_audio_files_play_with_the_sites_own_player_on_the_challenge_page(): void
    {
        $challenge = $this->makeChallenge(['starts_on' => now()->subDay(), 'ends_on' => now()->addDays(6)]);

        $challenge->dayFiles()->create([
            'day_number' => 1,
            'title' => 'صوت آرام‌سازی',
            'path' => 'day-files/note.wav',
            'original_name' => 'note.wav',
            'size' => 2048,
            'mime' => 'audio/wav',
            'is_audio' => true,
        ]);

        Storage::disk('local')->put('day-files/note.wav', 'RIFF....WAVE');

        $html = $this->get(route('challenges.show', $challenge))->assertOk()->getContent();

        // پخش‌کننده اختصاصی سایت، نه تگ audio پیش‌فرض مرورگر
        $this->assertStringContainsString('data-audio-player', $html);
        $this->assertStringContainsString('data-audio-toggle', $html);
        $this->assertStringContainsString('data-audio-seek', $html);
        $this->assertStringNotContainsString('<audio', $html, 'پخش‌کننده پیش‌فرض مرورگر نباید استفاده شود.');

        // صوتی که به روز ۱ تعلق دارد، داخل کارت همان روز می‌آید
        $box = $this->dayBoxHtml($html, 1);
        $this->assertStringContainsString('فایل‌های صوتی این روز', $box);
        $this->assertStringContainsString('صوت آرام‌سازی', $box);

        // و فایل صوتی با disposition inline می‌آید تا همان‌جا پخش شود
        $file = $challenge->dayFiles()->firstOrFail();
        $response = $this->get(route('challenge.day-files.show', [$challenge, $file]));
        $response->assertOk();
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));

        // برای جلو/عقب بردن صوت، پاسخ باید Range را پشتیبانی کند
        $this->assertSame('bytes', (string) $response->headers->get('Accept-Ranges'));
        $partial = $this->get(route('challenge.day-files.show', [$challenge, $file]), ['HTTP_RANGE' => 'bytes=0-9']);
        $partial->assertStatus(206);
        $this->assertStringContainsString('bytes 0-9/', (string) $partial->headers->get('Content-Range'));
    }

    public function test_plain_day_files_are_offered_as_a_download(): void
    {
        $challenge = $this->makeChallenge(['starts_on' => now()->subDay(), 'ends_on' => now()->addDays(6)]);

        $challenge->dayFiles()->create([
            'day_number' => 1,
            'title' => 'جدول تمرین',
            'path' => 'day-files/plan.txt',
            'original_name' => 'plan.txt',
            'size' => 32,
            'mime' => 'text/plain',
            'is_audio' => false,
        ]);

        Storage::disk('local')->put('day-files/plan.txt', 'برنامه');

        $response = $this->get(route('challenge.day-files.show', [$challenge, $challenge->dayFiles()->firstOrFail()]));
        $response->assertOk();
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));

        $box = $this->dayBoxHtml(
            $this->get(route('challenges.show', $challenge))->assertOk()->getContent(),
            1
        );

        $this->assertStringContainsString('جدول تمرین', $box);
        $this->assertStringContainsString('⬇ دانلود', $box);
    }

    public function test_the_participant_dashboard_shows_the_day_files_too(): void
    {
        $challenge = $this->makeChallenge(['starts_on' => now()->subDay(), 'ends_on' => now()->addDays(6)]);
        $participant = $challenge->participants()->create([
            'name' => 'زهرا محمدی',
            'pledged_at' => now(),
        ]);

        $challenge->dayFiles()->create([
            'day_number' => 1,
            'title' => 'صوت روز اول',
            'path' => 'day-files/first.wav',
            'original_name' => 'first.wav',
            'size' => 1024,
            'mime' => 'audio/wav',
            'is_audio' => true,
        ]);

        $html = $this->get(route('participant.show', $participant))->assertOk()->getContent();

        $this->assertStringContainsString('data-audio-player', $html);
        $this->assertStringContainsString('صوت روز اول', $html);
    }

    public function test_files_of_locked_days_stay_hidden(): void
    {
        $challenge = $this->makeChallenge([
            'daily_release' => true,
            'starts_on' => now()->subDay(),
            'ends_on' => now()->addDays(6),
        ]);

        Storage::disk('local')->put('day-files/locked.wav', 'RIFF....WAVE');

        $challenge->dayFiles()->create([
            'day_number' => 5,
            'title' => 'صوت روز پنجم',
            'path' => 'day-files/locked.wav',
            'original_name' => 'locked.wav',
            'size' => 1024,
            'mime' => 'audio/wav',
            'is_audio' => true,
        ]);

        $this->assertFalse($challenge->isDayUnlocked(5), 'روز ۵ باید قفل باشد.');

        // صفحه چالش: نه عنوان فایل دیده می‌شود نه پخش‌کننده‌اش
        $html = $this->get(route('challenges.show', $challenge))->assertOk()->getContent();
        $this->assertStringNotContainsString('صوت روز پنجم', $html);
        $this->assertStringNotContainsString('data-audio-player', $html);

        // دانلود مستقیم هم ۴۰۴ می‌دهد
        $file = $challenge->dayFiles()->firstOrFail();
        $this->get(route('challenge.day-files.show', [$challenge, $file]))->assertNotFound();

        // ولی مدیر در پنل همان روز را با فایلش می‌بیند
        $panel = $this->actingAs($this->superAdmin(), 'admin')
            ->get(route('panel.challenges.show', $challenge))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('صوت روز پنجم', $panel);
    }

    public function test_an_admin_can_edit_and_delete_a_day_file(): void
    {
        $this->loginAsAdmin();
        $challenge = $this->makeChallenge(['starts_on' => now()->subDay(), 'ends_on' => now()->addDays(6)]);

        $this->post(route('panel.day-files.store', $challenge), [
            'day_number' => 1,
            'title' => 'فایل اولیه',
            'file' => UploadedFile::fake()->createWithContent('a.txt', 'محتوا'),
        ])->assertRedirect();

        $file = $challenge->dayFiles()->firstOrFail();
        $path = $file->path;

        $this->put(route('panel.day-files.update', [$challenge, $file]), [
            'day_number' => 3,
            'title' => 'فایل منتقل‌شده',
        ])->assertRedirect();

        $file->refresh();
        $this->assertSame('فایل منتقل‌شده', $file->title);
        $this->assertSame(3, $file->day_number);

        $this->delete(route('panel.day-files.destroy', [$challenge, $file]))->assertRedirect();

        $this->assertSame(0, $challenge->dayFiles()->count());
        Storage::disk('local')->assertMissing($path);
    }

    public function test_the_excel_export_lists_the_day_files(): void
    {
        $challenge = $this->makeChallenge(['starts_on' => now()->subDay(), 'ends_on' => now()->addDays(6)]);

        $challenge->dayFiles()->create([
            'day_number' => 2,
            'title' => 'صوت روز دوم',
            'path' => 'day-files/second.mp3',
            'original_name' => 'second.mp3',
            'size' => 4096,
            'mime' => 'audio/mpeg',
            'is_audio' => true,
        ]);

        $sheets = ChallengeExport::sheets($challenge->fresh());

        $this->assertArrayHasKey('فایل‌های روزها', $sheets);

        $rows = $sheets['فایل‌های روزها'];
        $this->assertSame('روز', $rows[0][0]);
        $this->assertContains('صوت روز دوم', $rows[1]);
        $this->assertContains('فایل صوتی', $rows[1]);
    }

    /**
     * برش HTML یک باکس روز (از `id="day-N"` تا باکس روز بعدی).
     */
    private function dayBoxHtml(string $html, int $day, int $limit = 6000): string
    {
        $start = strpos($html, 'id="day-'.$day.'"');

        $this->assertNotFalse($start, 'باکس روز '.fa_num($day).' در صفحه پیدا نشد.');

        $end = strpos($html, 'id="day-'.($day + 1).'"', $start);

        return substr($html, $start, ($end === false ? $limit : $end - $start));
    }
}
