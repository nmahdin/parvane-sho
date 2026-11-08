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

        foreach (['خلاصه چالش', 'شرکت‌کننده‌ها', 'کارها و پاسخ‌های هر نفر', 'وضعیت روز به روز', 'کارها و پرسش‌ها', 'نکته‌ها'] as $name) {
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

    public function test_the_export_lists_every_persons_tasks_and_question_answers(): void
    {
        [$challenge, $zahra, $amir] = $this->makeChallenge();

        $question = $challenge->questions()->firstOrFail();
        $zahra->questionAnswers()->create([
            'challenge_question_id' => $question->getKey(),
            'body' => 'بابت سلامتی و خانواده‌ام.',
        ]);

        $sheets = ChallengeExport::sheets($challenge);

        $this->assertArrayHasKey('کارها و پاسخ‌های هر نفر', $sheets, 'برگه کارها و پاسخ‌های هر نفر ساخته نشد.');

        $rows = $sheets['کارها و پاسخ‌های هر نفر'];

        $this->assertSame([
            'نام شرکت‌کننده', 'کار یا پرسش', 'روز', 'تاریخ روز', 'عنوان یا متن', 'وضعیت', 'زمان انجام یا پاسخ', 'پاسخ خودش',
        ], $rows[0]);

        $body = array_slice($rows, 1);

        // هر نفر: دو کار + یک پرسش
        foreach ([$zahra->name, $amir->name] as $name) {
            $mine = array_values(array_filter($body, fn (array $row) => $row[0] === $name));
            $this->assertCount(3, $mine, 'سطرهای «'.$name.'» کامل نیست.');
        }

        $find = fn (string $name, string $title) => collect($body)->first(
            fn (array $row) => $row[0] === $name && $row[4] === $title
        );

        // زهرا کار اول را انجام داده است
        $done = $find($zahra->name, 'نوشتن سه نعمت');
        $this->assertNotNull($done);
        $this->assertSame('کار', $done[1]);
        $this->assertSame('✓ انجام داده', $done[5]);
        $this->assertNotSame('—', $done[6], 'زمان انجام کار ثبت نشده است.');
        $this->assertStringStartsWith('روز ۱ از ۲', (string) $done[2]);
        $this->assertSame('—', $done[7], 'کارها ستون «پاسخ خودش» را خط تیره می‌گیرند.');

        // امیر همان کار را انجام نداده است
        $notDone = $find($amir->name, 'نوشتن سه نعمت');
        $this->assertNotNull($notDone);
        $this->assertSame('انجام نشده', $notDone[5]);
        $this->assertSame('—', $notDone[6]);

        // پاسخ زهرا به پرسش، با متن خودش
        $answered = $find($zahra->name, $question->question);
        $this->assertNotNull($answered);
        $this->assertSame('پرسش', $answered[1]);
        $this->assertSame('✓ پاسخ داده', $answered[5]);
        $this->assertSame('بابت سلامتی و خانواده‌ام.', $answered[7]);

        // امیر به پرسش جواب نداده است
        $unanswered = $find($amir->name, $question->question);
        $this->assertNotNull($unanswered);
        $this->assertSame('جواب نداده است', $unanswered[5]);
        $this->assertSame('—', $unanswered[7]);
    }

    public function test_the_items_sheet_shows_the_participants_own_answers(): void
    {
        [$challenge, $zahra] = $this->makeChallenge();

        $question = $challenge->questions()->firstOrFail();
        $zahra->questionAnswers()->create([
            'challenge_question_id' => $question->getKey(),
            'body' => 'بابت سلامتی و خانواده‌ام.',
        ]);

        $rows = ChallengeExport::sheets($challenge)['کارها و پرسش‌ها'];

        $this->assertContains('پاسخ شرکت‌کننده‌ها', $rows[0]);

        $questionRow = collect(array_slice($rows, 1))->first(fn (array $row) => $row[2] === 'پرسش');
        $taskRow = collect(array_slice($rows, 1))->first(fn (array $row) => $row[2] === 'کار');

        $this->assertNotNull($questionRow);
        $this->assertNotNull($taskRow);

        $this->assertSame($zahra->name.': بابت سلامتی و خانواده‌ام.', $questionRow[7]);
        $this->assertSame('—', $taskRow[7], 'کارها در ستون پاسخ شرکت‌کننده‌ها خط تیره دارند.');
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
