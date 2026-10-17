<?php

namespace Tests\Feature;

use App\Models\Challenge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * نوار پیشرفت آپلود فایل:
 * فرم‌های آپلود با XHR ثبت می‌شوند تا درصد پیشرفت نشان داده شود؛
 * مسیر ajax-save (فایل روزها) و مسیر معمولی (ضمائم چالش) هر دو نوار دارند.
 */
class UploadProgressTest extends TestCase
{
    use RefreshDatabase;

    private function makeChallenge(): Challenge
    {
        return Challenge::create([
            'title' => 'چالش آپلود',
            'slug' => 'upload-progress',
            'is_published' => true,
            'daily_release' => true,
            'starts_on' => now()->subDay(),
            'ends_on' => now()->addDays(6),
        ]);
    }

    private function builtAsset(string $type): string
    {
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);

        $entry = $type === 'css' ? 'resources/css/app.css' : 'resources/js/app.js';
        $file = $manifest[$entry]['file'] ?? null;

        $this->assertNotNull($file, 'دارایی '.$type.' در manifest پیدا نشد.');

        return file_get_contents(public_path('build/'.$file));
    }

    public function test_the_upload_progress_handler_is_in_the_built_js(): void
    {
        $js = $this->builtAsset('js');

        $this->assertStringContainsString('initUploadProgress', $js);
        $this->assertStringContainsString('showUploadProgress', $js);
        $this->assertStringContainsString('xhrUpload', $js);
        $this->assertStringContainsString('data-upload-progress', $js);
        $this->assertStringContainsString('در حال آپلود فایل', $js);
        $this->assertStringContainsString('X-Requested-With', $js);

        // آپلود با XHR می‌رود تا رویداد progress در دسترس باشد
        // (نام‌های متغیر در بیلد کوتاه می‌شوند، پس الگوها بدون نام متغیر بررسی می‌شوند)
        $this->assertStringContainsString('new XMLHttpRequest', $js);
        $this->assertMatchesRegularExpression('/upload\.addEventListener\(.progress./', $js);
        $this->assertStringContainsString('lengthComputable', $js);
    }

    public function test_the_upload_progress_bar_style_is_in_the_built_css(): void
    {
        $css = $this->builtAsset('css');

        $this->assertStringContainsString('.upload-progress', $css);
        $this->assertStringContainsString('upload-progress-track', $css);
        $this->assertStringContainsString('upload-progress-fill', $css);
    }

    public function test_the_ajax_upload_path_still_saves_the_day_file(): void
    {
        $this->loginAsAdmin();
        $challenge = $this->makeChallenge();

        // همان چیزی که مسیر XHR در مرورگر می‌فرستد:
        // POST با هدر ajax و فایل ضمیمه‌شده
        $this->post(
            route('panel.day-files.store', $challenge),
            [
                'day_number' => 1,
                'title' => 'صوت با نوار پیشرفت',
                'file' => UploadedFile::fake()->create('note.mp3', 64, 'audio/mpeg'),
            ],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        )->assertRedirect();

        $file = $challenge->dayFiles()->firstOrFail();

        $this->assertSame('صوت با نوار پیشرفت', $file->title);
        $this->assertTrue($file->is_audio);
        Storage::disk('local')->assertExists($file->path);
    }

    public function test_the_plain_upload_path_still_saves_the_attachment(): void
    {
        $this->loginAsAdmin();
        $challenge = $this->makeChallenge();

        $this->post(route('panel.attachments.store', $challenge), [
            'title' => 'ضمیمه با نوار پیشرفت',
            'file' => UploadedFile::fake()->createWithContent('plan.txt', 'برنامه'),
        ])->assertRedirect();

        $attachment = $challenge->attachments()->firstOrFail();

        $this->assertSame('ضمیمه با نوار پیشرفت', $attachment->title);
        Storage::disk('local')->assertExists($attachment->path);
    }

    public function test_a_validation_error_on_upload_answers_with_json_for_the_toast(): void
    {
        $this->loginAsAdmin();
        $challenge = $this->makeChallenge();

        $response = $this->postJson(
            route('panel.day-files.store', $challenge),
            [
                'day_number' => 1,
                'title' => 'بدون فایل',
            ],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );

        $response->assertStatus(422);
        $this->assertArrayHasKey('file', $response->json('errors'));
    }
}
