<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Support\ChallengeExport;
use App\Support\UploadLimits;
use App\Support\Jalali;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * فایل‌ها، فایل‌های صوتی و ویدیوهای هر روز: افزودن از پنل (تا ۶۰ مگابایت)،
 * نمایش داخل کارت همان روز، پخش‌کننده اختصاصی صوت و ویدیو (نه پخش‌کننده
 * پیش‌فرض مرورگر) و قفل بودن روزهای بعدی.
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

        $this->assertStringContainsString('فایل‌های روز', $panel);
        $this->assertStringNotContainsString('فایل‌ها و فایل‌های صوتی این روز', $panel, 'عنوان بخش باید فقط «فایل‌های روز» باشد.');
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

        // فایل روز هیچ‌وقت عوض نمی‌شود؛ مرورگر یک ماه بی‌پرسش کش می‌کند
        $this->assertSame('max-age=2592000, public', (string) $response->headers->get('Cache-Control'));

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

    public function test_image_day_files_are_shown_as_a_full_image_on_the_challenge_page(): void
    {
        $challenge = $this->makeChallenge(['starts_on' => now()->subDay(), 'ends_on' => now()->addDays(6)]);

        $image = $challenge->dayFiles()->create([
            'day_number' => 1,
            'title' => 'عکس اردوی روز اول',
            'path' => 'day-files/camp.png',
            'original_name' => 'camp.png',
            'size' => 40960,
            'mime' => 'image/png',
            'is_audio' => false,
        ]);

        $document = $challenge->dayFiles()->create([
            'day_number' => 1,
            'title' => 'جدول تمرین',
            'path' => 'day-files/plan.txt',
            'original_name' => 'plan.txt',
            'size' => 32,
            'mime' => 'text/plain',
            'is_audio' => false,
        ]);

        Storage::disk('local')->put('day-files/camp.png', 'PNG....');
        Storage::disk('local')->put('day-files/plan.txt', 'برنامه');

        $box = $this->dayBoxHtml(
            $this->get(route('challenges.show', $challenge))->assertOk()->getContent(),
            1
        );

        // عکس کامل و به اندازه عرض باکس روز نمایش داده می‌شود
        $this->assertStringContainsString('تصاویر این روز', $box);
        $this->assertStringContainsString('class="day-image img-reveal"', $box, 'عکس باید با کلاس تمام‌عرض day-image رندر شود.');
        $this->assertStringContainsString('src="'.e($image->url()).'"', $box);
        $this->assertStringContainsString('alt="عکس اردوی روز اول"', $box);
        $this->assertStringContainsString('loading="lazy"', $box);

        // فایل غیرعکسی همان ردیف دانلود می‌ماند و عکس به <img> تبدیل شده نه لینک دانلود تنها
        $this->assertStringContainsString('جدول تمرین', $box);
        $this->assertMatchesRegularExpression('/<a [^>]*class="day-image-download"/', $box);
    }

    public function test_image_day_files_are_shown_as_a_full_image_on_the_participant_dashboard(): void
    {
        $challenge = $this->makeChallenge(['starts_on' => now()->subDay(), 'ends_on' => now()->addDays(6)]);
        $participant = $challenge->participants()->create([
            'name' => 'زهرا محمدی',
            'pledged_at' => now(),
        ]);

        $challenge->dayFiles()->create([
            'day_number' => 1,
            'title' => 'عکس اردو',
            'path' => 'day-files/camp.jpg',
            'original_name' => 'camp.jpg',
            'size' => 40960,
            'mime' => 'image/jpeg',
            'is_audio' => false,
        ]);

        Storage::disk('local')->put('day-files/camp.jpg', 'JPEG....');

        $html = $this->get(route('participant.show', $participant))->assertOk()->getContent();

        $this->assertStringContainsString('تصاویر این روز', $html);
        $this->assertStringContainsString('class="day-image img-reveal"', $html);
        $this->assertStringContainsString('alt="عکس اردو"', $html);
    }

    public function test_audio_files_can_be_up_to_sixty_megabytes(): void
    {
        $this->loginAsAdmin();
        $challenge = $this->makeChallenge(['starts_on' => now()->subDay(), 'ends_on' => now()->addDays(6)]);

        // ۶۰ مگابایت دقیقاً مجاز است (مرز بالا)
        $this->post(route('panel.day-files.store', $challenge), [
            'day_number' => 1,
            'title' => 'صوت شصت مگابایتی',
            'file' => UploadedFile::fake()->create('big.mp3', 60 * 1024, 'audio/mpeg'),
        ])->assertRedirect();

        $file = $challenge->dayFiles()->firstOrFail();
        $this->assertSame(60 * 1024 * 1024, $file->size);
        $this->assertTrue($file->is_audio);

        // بیشتر از ۶۰ مگابایت رد می‌شود و پیام فارسی حد تازه را می‌گوید
        $challenge->dayFiles()->delete();

        $response = $this->post(route('panel.day-files.store', $challenge), [
            'day_number' => 1,
            'title' => 'صوت خیلی بزرگ',
            'file' => UploadedFile::fake()->create('huge.mp3', (60 * 1024) + 1, 'audio/mpeg'),
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertSame(0, $challenge->dayFiles()->count());

        $errors = session('errors')->first('file');
        $this->assertStringContainsString('۶۰ مگابایت', $errors);

        // راهنمای فرم آپلود هم حد تازه را نشان می‌دهد
        $html = $this->get(route('panel.challenges.show', $challenge))->assertOk()->getContent();
        $this->assertStringContainsString('تا ۶۰ مگابایت', $html);
    }

    public function test_the_is_image_helper_detects_pictures(): void
    {
        $challenge = $this->makeChallenge(['starts_on' => now()->subDay(), 'ends_on' => now()->addDays(6)]);

        $image = $challenge->dayFiles()->create([
            'day_number' => 1,
            'title' => 'عکس',
            'path' => 'day-files/a.webp',
            'original_name' => 'a.webp',
            'size' => 10,
            'mime' => 'image/webp',
            'is_audio' => false,
        ]);

        $text = $challenge->dayFiles()->create([
            'day_number' => 1,
            'title' => 'متن',
            'path' => 'day-files/a.txt',
            'original_name' => 'a.txt',
            'size' => 10,
            'mime' => 'text/plain',
            'is_audio' => false,
        ]);

        $this->assertTrue($image->isImage());
        $this->assertFalse($text->isImage());
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

    public function test_every_upload_can_be_up_to_sixty_megabytes(): void
    {
        $this->loginAsAdmin();
        $challenge = $this->makeChallenge(['starts_on' => now()->subDay(), 'ends_on' => now()->addDays(6)]);

        // یک فایل معمولی (غیرصوتی) هم تا ۶۰ مگابایت مجاز است
        $this->post(route('panel.day-files.store', $challenge), [
            'day_number' => 1,
            'title' => 'جدول شصت مگابایتی',
            'file' => UploadedFile::fake()->create('table.pdf', 60 * 1024, 'application/pdf'),
        ])->assertRedirect();

        $pdf = $challenge->dayFiles()->firstOrFail();
        $this->assertSame(60 * 1024 * 1024, $pdf->size);
        $this->assertFalse($pdf->is_audio);
        $this->assertFalse($pdf->isVideo());

        // ویدیو هم تا همان ۶۰ مگابایت
        $this->post(route('panel.day-files.store', $challenge), [
            'day_number' => 1,
            'title' => 'ویدیوی شصت مگابایتی',
            'file' => UploadedFile::fake()->create('movie.mp4', 60 * 1024, 'video/mp4'),
        ])->assertRedirect();

        $video = $challenge->dayFiles()->where('original_name', 'movie.mp4')->firstOrFail();
        $this->assertSame(60 * 1024 * 1024, $video->size);
        $this->assertTrue($video->is_video, 'فایل mp4 باید ویدیو ثبت شود.');
        $this->assertFalse($video->is_audio);
        $this->assertSame('ویدیو', $video->typeLabel());

        // بیشتر از ۶۰ مگابایت رد می‌شود (پیام فارسی حد تازه)
        $challenge->dayFiles()->delete();

        $this->post(route('panel.day-files.store', $challenge), [
            'day_number' => 1,
            'title' => 'فایل خیلی بزرگ',
            'file' => UploadedFile::fake()->create('huge.pdf', (60 * 1024) + 1, 'application/pdf'),
        ])->assertSessionHasErrors('file');

        $this->assertStringContainsString('۶۰ مگابایت', session('errors')->first('file'));
        $this->assertSame(0, $challenge->dayFiles()->count());

        // ضمیمهٔ خود چالش هم همان ۶۰ مگابایت را می‌پذیرد
        $this->post(route('panel.attachments.store', $challenge), [
            'title' => 'پیوست بزرگ',
            'file' => UploadedFile::fake()->create('attachment.pdf', 60 * 1024, 'application/pdf'),
        ])->assertRedirect();

        $this->assertSame(1, $challenge->attachments()->count());

        $this->post(route('panel.attachments.store', $challenge), [
            'title' => 'پیوست خیلی بزرگ',
            'file' => UploadedFile::fake()->create('huge.pdf', (60 * 1024) + 1, 'application/pdf'),
        ])->assertSessionHasErrors('file');

        $this->assertStringContainsString('۶۰ مگابایت', session('errors')->first('file'));
        $this->assertSame(1, $challenge->attachments()->count());

        // راهنمای هر دو فرم، حد تازه را می‌گوید
        $html = $this->get(route('panel.challenges.show', $challenge))->assertOk()->getContent();
        $this->assertStringContainsString('حجم هر فایل تا ۶۰ مگابایت', $html);
    }

    public function test_a_video_uses_the_dedicated_video_player(): void
    {
        $challenge = $this->makeChallenge(['starts_on' => now()->subDay(), 'ends_on' => now()->addDays(6)]);

        $video = $challenge->dayFiles()->create([
            'day_number' => 1,
            'title' => 'ویدیوی تنفس روز اول',
            'path' => 'day-files/breath.mp4',
            'original_name' => 'breath.mp4',
            'size' => 20 * 1024 * 1024,
            'mime' => 'video/mp4',
            'is_audio' => false,
            'is_video' => true,
        ]);

        Storage::disk('local')->put('day-files/breath.mp4', '....ftypisom');

        $html = $this->get(route('challenges.show', $challenge))->assertOk()->getContent();
        $box = $this->dayBoxHtml($html, 1, 12000);

        // پخش‌کننده اختصاصی سایت، نه کنترل‌های پیش‌فرض مرورگر
        $this->assertStringContainsString('ویدیوهای این روز', $box);
        $this->assertStringContainsString('data-video-player', $box);
        $this->assertStringContainsString('data-video-stage', $box);
        $this->assertStringContainsString('data-video-toggle', $box);
        $this->assertStringContainsString('data-video-seek', $box);
        $this->assertStringContainsString('data-video-fullscreen', $box);
        $this->assertStringContainsString('preload="none"', $box, 'تا پیش از پخش نباید بایتی از ویدیو دانلود شود.');
        $this->assertStringContainsString('data-video-src="'.e($video->url()).'"', $box);
        $this->assertStringContainsString('ویدیوی تنفس روز اول', $box);
        $this->assertMatchesRegularExpression('/<video(?![^>]*\bcontrols\b)[^>]*>/', $box, 'ویدیو نباید کنترل پیش‌فرض مرورگر داشته باشد.');

        // ویدیو به‌جای لینک دانلود، پخش‌کننده می‌گیرد
        $this->assertStringNotContainsString('⬇ دانلود', $box);

        // داشبورد شرکت‌کننده هم همان پخش‌کننده را نشان می‌دهد
        $participant = $challenge->participants()->create(['name' => 'زهرا محمدی', 'pledged_at' => now()]);
        $this->assertStringContainsString('data-video-player', $this->get(route('participant.show', $participant))->assertOk()->getContent());

        // پنل هم پیش‌نمایش ویدیو را دارد
        $this->loginAsAdmin();
        $panel = $this->get(route('panel.challenges.show', $challenge))->assertOk()->getContent();
        $this->assertStringContainsString('🎬 ویدیو', $panel);
        $this->assertStringContainsString('data-video-player', $panel);
    }

    public function test_the_video_streams_inline_with_range_and_counts_plays_not_downloads(): void
    {
        $challenge = $this->makeChallenge(['starts_on' => now()->subDay(), 'ends_on' => now()->addDays(6)]);

        $video = $challenge->dayFiles()->create([
            'day_number' => 1,
            'title' => 'ویدیوی تنفس',
            'path' => 'day-files/breath.mp4',
            'original_name' => 'breath.mp4',
            'size' => 64,
            'mime' => 'video/mp4',
            'is_audio' => false,
            'is_video' => true,
        ]);

        Storage::disk('local')->put('day-files/breath.mp4', str_repeat('0123456789', 8));

        $response = $this->get(route('challenge.day-files.show', [$challenge, $video]));

        $response->assertOk();
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
        $this->assertSame('max-age=2592000, public', (string) $response->headers->get('Cache-Control'));
        $this->assertSame('bytes', (string) $response->headers->get('Accept-Ranges'));
        $this->assertSame(0, $video->fresh()->downloads, 'پخش ویدیو نباید دانلود شمرده شود.');

        // جلو/عقب بردن ویدیو با Range
        $partial = $this->get(route('challenge.day-files.show', [$challenge, $video]), ['HTTP_RANGE' => 'bytes=0-9']);
        $partial->assertStatus(206);
        $this->assertStringContainsString('bytes 0-9/', (string) $partial->headers->get('Content-Range'));

        // شمار پخش با beacon پخش‌کننده
        $this->post(route('challenge.day-files.plays', [$challenge, $video]))->assertNoContent();
        $this->assertSame(1, $video->fresh()->plays);
    }

    public function test_the_video_player_reaches_the_built_assets(): void
    {
        $script = glob(public_path('build/assets/app-*.js'));
        $style = glob(public_path('build/assets/app-*.css'));

        $this->assertNotEmpty($script, 'باندل اسکریپت ساخته نشده است.');
        $this->assertNotEmpty($style, 'باندل استایل ساخته نشده است.');

        $js = (string) file_get_contents($script[0]);

        $this->assertStringContainsString('initVideoPlayers', $js);
        $this->assertStringContainsString('data-video-player', $js);
        $this->assertStringContainsString('data-video-fullscreen', $js);
        $this->assertStringContainsString('pv-video-', $js, 'کلید ادامه از جای قبلی ویدیو در باندل نیست.');

        /* قاعدهٔ «فقط یک رسانه (صوت یا ویدیو) پخش شود» در یک تابع مشترک است
           و هر دو پخش‌کننده از آن رد می‌شوند (نام تابع در باندل کوتاه می‌شود،
           پس روی خود منبع بررسی می‌شود). */
        $source = (string) file_get_contents(base_path('resources/js/modules/media.js'));

        $this->assertStringContainsString('let currentMedia = null;', $source);
        $this->assertGreaterThanOrEqual(2, substr_count($source, 'playExclusive('), 'پخش صوت و ویدیو باید از یک قاعدهٔ مشترک رد شود.');

        $css = (string) file_get_contents($style[0]);

        $this->assertStringContainsString('video-player', $css);
        $this->assertStringContainsString('video-screen', $css);
        $this->assertStringContainsString('--video-progress', $css);
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

    public function test_the_panel_warns_when_the_server_accepts_less_than_sixty_megabytes(): void
    {
        // تبدیل مقدارهای php.ini به بایت
        $this->assertSame(8 * 1024 * 1024, UploadLimits::toBytes('8M'));
        $this->assertSame(512 * 1024, UploadLimits::toBytes('512K'));
        $this->assertSame(2 * 1024 ** 3, UploadLimits::toBytes('2G'));
        $this->assertSame(1048576, UploadLimits::toBytes('1048576'));
        $this->assertSame(0, UploadLimits::toBytes('-1'), '-1 یعنی نامحدود');

        $this->loginAsAdmin();
        $challenge = $this->makeChallenge(['starts_on' => now()->subDay(), 'ends_on' => now()->addDays(6)]);

        // سروری که فقط ۸ مگابایت می‌پذیرد: هشدار در پنل دیده می‌شود
        UploadLimits::fake(8 * 1024 * 1024);

        $this->assertTrue(UploadLimits::isBelowAppLimit());
        $this->assertSame('۸ مگابایت', UploadLimits::serverMaxForHumans());
        $this->assertNotNull(UploadLimits::warning());

        $panel = $this->get(route('panel.challenges.show', $challenge))->assertOk()->getContent();

        $this->assertStringContainsString('سرور این سایت الان حداکثر ۸ مگابایت', $panel);
        $this->assertStringContainsString('upload_max_filesize', $panel);

        // سروری که ۶۴ مگابایت می‌پذیرد: هشداری در کار نیست
        UploadLimits::fake(64 * 1024 * 1024);

        $this->assertFalse(UploadLimits::isBelowAppLimit());
        $this->assertNull(UploadLimits::warning());

        $panel = $this->get(route('panel.challenges.show', $challenge))->assertOk()->getContent();

        $this->assertStringNotContainsString('سرور این سایت الان حداکثر', $panel);
        $this->assertStringContainsString('حجم هر فایل تا ۶۰ مگابایت', $panel);

        UploadLimits::fake(null);
    }

    public function test_a_request_bigger_than_the_server_limit_gets_a_clear_message(): void
    {
        $challenge = $this->makeChallenge(['starts_on' => now()->subDay(), 'ends_on' => now()->addDays(6)]);

        /* وقتی حجم درخواست از post_max_size بیشتر شود، PHP کل $_POST را خالی
           می‌کند و Laravel فقط «توکن CSRF نامتمبر» می‌بیند؛ باید پیام مربوط به
           حجم فایل برگردد، نه خطای بی‌ربط. (در آزمون، میان‌افزار CSRF خاموش
           است، پس خودِ هندلر خطا با همان درخواست صدا زده می‌شود.) */
        UploadLimits::fake(2 * 1024 * 1024);

        $request = Request::create(
            route('panel.day-files.store', $challenge),
            'POST',
            [],
            [],
            [],
            [
                'CONTENT_LENGTH' => (string) (3 * 1024 * 1024),
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        $response = app(ExceptionHandler::class)->render($request, new TokenMismatchException);

        $this->assertSame(413, $response->getStatusCode());

        $message = json_decode($response->getContent(), true)['message'] ?? '';

        $this->assertStringContainsString('حد مجاز سرور', $message);
        $this->assertStringContainsString('upload_max_filesize', $message);
        $this->assertStringContainsString('php.ini', $message);
        $this->assertStringContainsString(UploadLimits::serverMaxForHumans(), $message);

        // درخواست کوچک‌تر از سقف سرور دست‌نخورده به مسیر عادی خودش می‌رود
        $small = Request::create(route('panel.day-files.store', $challenge), 'POST', [], [], [], [
            'CONTENT_LENGTH' => '1024',
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertNotSame(413, app(ExceptionHandler::class)->render($small, new TokenMismatchException)->getStatusCode());

        UploadLimits::fake(null);
    }

    public function test_the_upload_forms_carry_the_effective_size_limit(): void
    {
        $this->loginAsAdmin();
        $challenge = $this->makeChallenge(['starts_on' => now()->subDay(), 'ends_on' => now()->addDays(6)]);

        // سروری که ۶۴ مگابایت می‌پذیرد: سقف مؤثر همان ۶۰ مگابایت اپلیکیشن است
        UploadLimits::fake(64 * 1024 * 1024);
        $this->assertSame(60 * 1024 * 1024, UploadLimits::limitBytes());
        $this->assertSame('۶۰ مگابایت', UploadLimits::limitForHumans());

        $panel = $this->get(route('panel.challenges.show', $challenge))->assertOk()->getContent();

        $this->assertStringContainsString('data-max-upload-bytes="'.(60 * 1024 * 1024).'"', $panel);
        $this->assertStringContainsString('data-max-upload-label="۶۰ مگابایت"', $panel);

        // سروری که فقط ۸ مگابایت می‌پذیرد: سقف مؤثر همان ۸ مگابایت می‌شود
        UploadLimits::fake(8 * 1024 * 1024);
        $this->assertSame(8 * 1024 * 1024, UploadLimits::limitBytes());

        $panel = $this->get(route('panel.challenges.show', $challenge))->assertOk()->getContent();

        $this->assertStringContainsString('data-max-upload-bytes="'.(8 * 1024 * 1024).'"', $panel);
        $this->assertStringContainsString('data-max-upload-label="۸ مگابایت"', $panel);

        UploadLimits::fake(null);
    }

    public function test_the_built_assets_report_upload_failures_precisely(): void
    {
        $bundles = glob(public_path('build/assets/app-*.js'));

        $this->assertNotEmpty($bundles, 'باندل اسکریپت ساخته نشده است.');

        $js = (string) file_get_contents($bundles[0]);

        // پیش از آپلود، حجم فایل با سقف مجاز سنجیده می‌شود
        $this->assertStringContainsString('oversizedFile', $js);
        $this->assertStringContainsString('maxUploadBytes', $js);

        // خطاهای آپلود با کد وضعیت گزارش می‌شوند تا علت از خود پیام پیدا باشد
        $this->assertStringContainsString('نشست شما پایان یافته', $js);
        $this->assertStringContainsString('از حد مجاز سرور یا پروکسی بیشتر است', $js);

        // خطای ۵۰۰ هم تنها «کد ۵۰۰» نمی‌ماند: اگر سرور توضیحی فرستاده باشد
        // (حالت دیباگ) همان در توست نشان داده می‌شود
        $this->assertStringContainsString('failureMessage', $js);
        $this->assertStringContainsString('ذخیره انجام نشد (کد ', $js);

        // تازه‌کردن بخش‌ها best-effort است؛ ذخیرهٔ موفق «ناموفق» گزارش نمی‌شود
        $this->assertStringContainsString('safeRefresh', $js);
        $this->assertStringNotContainsString("const message = await this.refreshDynamicSections(dayNumber);", $js);
    }
}
