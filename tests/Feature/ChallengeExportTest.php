<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\ChallengeTask;
use App\Models\Participant;
use App\Support\ChallengeExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

/**
 * خروجی اکسل اطلاعات چالش: دانلود فایل، برگه‌های درست و نام کسانی
 * که یک کار را انجام داده‌اند یا نداده‌اند.
 */
class ChallengeExportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * چالش دو روزه با دو کار، یک پرسش و دو شرکت‌کننده.
     *
     * @return array{0: Challenge, 1: Participant, 2: Participant, 3: ChallengeTask}
     */
    private function makeChallenge(): array
    {
        $challenge = Challenge::create([
            'title' => 'چالش شکرگزاری',
            'slug' => 'shokr-export',
            'is_published' => true,
            'daily_release' => true,
            'starts_on' => today()->subDay(),
        ]);

        $first = $challenge->tasks()->create(['day_number' => 1, 'title' => 'نوشتن سه نعمت']);
        $challenge->tasks()->create(['day_number' => 2, 'title' => 'تماس با خانواده']);
        $challenge->questions()->create(['day_number' => 1, 'question' => 'امروز بابت چه چیزی سپاسگزاری؟']);

        $zahra = $challenge->participants()->create(['name' => 'زهرا محمدی', 'contact' => '۰۹۱۲۳۴۵۶۷۸۹']);
        $amir = $challenge->participants()->create(['name' => 'امیر رضایی']);

        // زهرا کار اول را انجام داده، امیر نداده است
        $zahra->toggleTask($first);

        return [$challenge, $zahra, $amir, $first];
    }

    /**
     * خواندن متن‌های داخل یک فایل اکسل (sharedStrings).
     *
     * @return list<string>
     */
    private function stringsOf(string $binary): array
    {
        $path = tempnam(sys_get_temp_dir(), 'test-xlsx').'.xlsx';
        file_put_contents($path, $binary);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true, 'فایل اکسل باید یک بسته zip سالم باشد.');

        $sheetNames = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $sheetNames[] = $zip->getNameIndex($i);
        }

        $this->assertContains('xl/workbook.xml', $sheetNames);
        $this->assertContains('xl/sharedStrings.xml', $sheetNames);
        $this->assertContains('xl/worksheets/sheet1.xml', $sheetNames);

        $shared = $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();
        unlink($path);

        $this->assertIsString($shared);

        preg_match_all('/<t[^>]*>(.*?)<\/t>/s', $shared, $matches);

        return array_map(fn (string $value) => html_entity_decode($value, ENT_QUOTES | ENT_XML1, 'UTF-8'), $matches[1]);
    }

    /**
     * @return array<string, string>
     */
    private function sheetsOf(string $binary): array
    {
        $path = tempnam(sys_get_temp_dir(), 'test-xlsx').'.xlsx';
        file_put_contents($path, $binary);

        $zip = new ZipArchive;
        $zip->open($path);

        $workbook = (string) $zip->getFromName('xl/workbook.xml');
        $zip->close();
        unlink($path);

        preg_match_all('/<sheet name="(.*?)"/', $workbook, $matches);

        $names = array_map(fn (string $value) => html_entity_decode($value, ENT_QUOTES | ENT_XML1, 'UTF-8'), $matches[1]);

        return array_combine($names, $names);
    }

    public function test_the_challenge_page_has_an_excel_export_button(): void
    {
        [$challenge] = $this->makeChallenge();

        $this->loginAsAdmin($this->superAdmin());

        $this->get(route('panel.challenges.show', $challenge))
            ->assertOk()
            ->assertSee('خروجی اکسل')
            ->assertSee(route('panel.challenges.export', $challenge), false);
    }

    public function test_the_download_is_a_real_excel_file(): void
    {
        [$challenge] = $this->makeChallenge();

        $this->loginAsAdmin($this->superAdmin());

        $response = $this->get(route('panel.challenges.export', $challenge))->assertOk();

        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('content-type')
        );

        $this->assertStringContainsString(
            'challenge-'.$challenge->slug.'-',
            (string) $response->headers->get('content-disposition')
        );

        ob_start();
        $response->sendContent();
        $binary = (string) ob_get_clean();

        $this->assertStringStartsWith('PK', $binary, 'خروجی باید فایل اکسل (بسته zip) باشد.');

        $sheets = $this->sheetsOf($binary);

        foreach (['خلاصه چالش', 'شرکت‌کننده‌ها', 'وضعیت روز به روز', 'کارها و پرسش‌ها'] as $name) {
            $this->assertArrayHasKey($name, $sheets, 'برگه «'.$name.'» در فایل اکسل نیست.');
        }
    }

    public function test_the_export_names_who_has_done_a_task_and_who_has_not(): void
    {
        [$challenge, $zahra, $amir] = $this->makeChallenge();

        $strings = $this->stringsOf(ChallengeExport::content($challenge));

        // سرستون‌های برگه کارها و پرسش‌ها
        $this->assertContains('انجام داده‌اند', $strings);
        $this->assertContains('انجام نداده‌اند', $strings);

        // زهرا کار اول را انجام داده و امیر نداده است
        $this->assertContains($zahra->name, $strings);
        $this->assertContains($amir->name, $strings);
        $this->assertContains('۱ از ۲', $strings);
        $this->assertContains('کار', $strings);
        $this->assertContains('پرسش', $strings);
        $this->assertContains('نوشتن سه نعمت', $strings);
        $this->assertContains('امروز بابت چه چیزی سپاسگزاری؟', $strings);
    }

    public function test_the_summary_sheet_holds_the_real_numbers(): void
    {
        [$challenge] = $this->makeChallenge();

        $summary = ChallengeExport::sheets($challenge)['خلاصه چالش'];

        $numbers = [];

        foreach (array_slice($summary, 1) as $row) {
            $numbers[$row[0]] = $row[1];
        }

        $this->assertSame('چالش شکرگزاری', $numbers['نام چالش']);
        $this->assertSame(2, $numbers['تعداد روزها']);
        $this->assertSame(2, $numbers['تعداد کارها']);
        $this->assertSame(1, $numbers['تعداد پرسش‌ها']);
        $this->assertSame(2, $numbers['تعداد شرکت‌کننده‌ها']);
        $this->assertSame(17, $numbers['پیشرفت کلی چالش (٪)'], 'یک کار از شش مورد (دو کار + یک پرسش × دو نفر) = ۱۷٪');
    }

    public function test_another_admin_cannot_export_someone_elses_challenge(): void
    {
        [$challenge] = $this->makeChallenge();

        $this->loginAsAdmin($this->makeChallengeAdmin(['username' => 'export-other']));

        $this->get(route('panel.challenges.export', $challenge))->assertNotFound();
    }

    public function test_the_challenge_admin_can_export_his_own_challenge(): void
    {
        $challengeAdmin = $this->makeChallengeAdmin(['username' => 'export-owner']);

        $challenge = Challenge::create([
            'title' => 'چالش خودم',
            'slug' => 'my-own-export',
            'is_published' => true,
            'admin_id' => $challengeAdmin->getKey(),
        ]);

        $challenge->participants()->create(['name' => 'نگار کریمی']);

        $this->loginAsAdmin($challengeAdmin);

        $this->get(route('panel.challenges.export', $challenge))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
