<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Challenge;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSettings();
        $this->seedDemoChallenge();
    }

    private function seedSettings(): void
    {
        Setting::put('site_name', config('panel.site_name'));
        Setting::put('site_tagline', config('panel.site_tagline'));
        Setting::put('site_about', config('panel.site_about'));
    }

    /**
     * یک چالش نمونه با چند شرکت‌کننده تا پنل و داشبوردها خالی نباشند.
     */
    private function seedDemoChallenge(): void
    {
        if (Challenge::query()->exists()) {
            return;
        }

        // چالش نمونه به نام مدیر اصلی ساخته می‌شود
        $adminId = Admin::query()->where('username', 'admin')->value('id');

        $challenge = Challenge::create([
            'admin_id' => $adminId,
            'title' => 'چالش ۷ روزه شکرگزاری و آرامش',
            'slug' => 'shokr-gozari-7',
            'tagline' => 'هفت روز، هر روز دو کار کوچک برای آرام‌تر شدن و شکرگزارتر بودن.',
            'description' => "این چالش نمونه ساخته شده تا ببینی همه‌چیز چطور کار می‌کند.\nهر روز دو کار داری؛ هر وقت انجامشان دادی در داشبورد خودت تیک بزن. اگر خواستی، همین چالش را ویرایش کن یا پاکش کن و چالش خودت را بساز.",
            'emoji' => '🦋',
            'accent' => 'jade',
            'starts_on' => Carbon::today()->subDays(2),
            'ends_on' => Carbon::today()->addDays(4),
            'is_published' => true,
            'daily_release' => true,
            'sort_order' => 1,
        ]);

        $days = [
            1 => ['نوشتن سه جمله شکرگزاری', 'سه چیز کوچک که امروز بابتشان خوشحال بودی.'],
            2 => ['۲۰ دقیقه پیاده‌روی', 'بدون گوشی؛ فقط قدم زدن و نگاه کردن.'],
            3 => ['۱۰ دقیقه تمرین تنفس عمیق', 'چهار ثانیه دم، هفت ثانیه نگه‌داشتن، هشت ثانیه بازدم.'],
            4 => ['گفتن یک تعریف صادقانه', 'به یک نفر بگو چه چیزی در او را دوست داری.'],
            5 => ['۲۵ دقیقه بدون موبایل', 'گوشی را در اتاق دیگری بگذار و به کار خودت برس.'],
            6 => ['کمک کوچک به یک نفر', 'حتی یک کار ساده، مثل شستن ظرف‌ها.'],
            7 => ['مرور هفته و نوشتن سه درس', 'چه چیزی خوب پیش رفت و چه چیزی را می‌خواهی بهتر کنی؟'],
        ];

        $sortOrder = 1;

        foreach ($days as $day => $tasks) {
            foreach ($tasks as $title) {
                $challenge->tasks()->create([
                    'day_number' => $day,
                    'title' => is_array($title) ? $title[0] : $title,
                    'description' => is_array($title) ? ($title[1] ?? null) : null,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        $challenge->update([
            'long_description' => "هدف این چالش ساده است: هر روز یک قدم کوچک برای آرام‌تر بودن و شکرگزارتر بودن.\n\n## قوانین ساده\n- هر کار را همان روز انجام بده؛ اگر نشد، آخر هفته جبران کن.\n- تیکِ انجام‌شده یعنی واقعاً انجام شده.\n- اگر یک روز از دست رفت، چالش تمام نمی‌شود؛ روز بعد ادامه بده.\n\n## چه چیزی به دست می‌آوری؟\nتمرین روزانه شکرگزاری و آرامش بعد از یک هفته به عادت کوچکی تبدیل می‌شود که خودت اثرش را حس می‌کنی. متن‌های **پررنگ** و فهرست‌ها در همین توضیحات پشتیبانی می‌شوند.",
            'contact_note' => 'اگر سوالی داشتی، به آیدی @parvaneh_admin پیام بده.',
        ]);

        $challenge->tips()->createMany([
            ['emoji' => '💧', 'title' => 'آب کافی بنوش', 'body' => 'روزی ۶ تا ۸ لیوان آب؛ خیلی از بی‌حوصلگی‌های بعدازظهر از کم‌آبی است.', 'sort_order' => 1],
            ['emoji' => '⏰', 'title' => 'یک ساعت ثابت انتخاب کن', 'body' => 'اگر کارها را همیشه در یک ساعت مشخص انجام بدهی، سریع‌تر به عادت تبدیل می‌شوند.', 'sort_order' => 2],
            ['emoji' => '⚠️', 'title' => 'با خودت سختگیر نباش', 'body' => 'اگر یک روز کامل نشد، اشکالی ندارد؛ همان روز را بعداً کامل کن.', 'sort_order' => 3],
        ]);

        $challenge->questions()->createMany([
            [
                'day_number' => 1,
                'question' => 'اگر یک روز را از دست بدهم چه می‌شود؟',
                'asked_by' => 'زهرا محمدی',
                'is_published' => true,
                'sort_order' => 1,
            ],
            [
                'day_number' => 2,
                'question' => 'باید کارها را همان روز انجام بدهم؟',
                'asked_by' => null,
                'is_published' => true,
                'sort_order' => 2,
            ],
            [
                'question' => 'آیا می‌توانم چالش را با یک دوست انجام بدهم؟',
                'day_number' => null,
                'asked_by' => 'امیر رضایی',
                'is_published' => true,
                'sort_order' => 3,
            ],
        ]);

        $this->seedDemoAttachment($challenge);
        $this->seedDemoParticipants($challenge);
    }

    /**
     * سه شرکت‌کننده نمونه با وضعیت‌های متفاوت: یکی تقریباً کامل، یکی در میانه
     * راه و یکی هنوز شروع‌نکرده. پاسخ پرسش‌ها هم اینجا ساخته می‌شود تا هم
     * پیشرفت واقعی باشد و هم مدیر پاسخ‌ها و «جواب نداده»ها را ببیند.
     */
    private function seedDemoParticipants(Challenge $challenge): void
    {
        $tasks = $challenge->tasks()->get();
        $questions = $challenge->questions()->orderBy('sort_order')->get();

        // [نام, تعداد کارهای انجام‌شده, شماره پرسش‌هایی که پاسخ داده‌اند]
        $people = [
            ['name' => 'زهرا محمدی', 'tasks' => 6, 'answers' => [0, 1, 2]],
            ['name' => 'امیر رضایی', 'tasks' => 3, 'answers' => [0]],
            ['name' => 'نگار کریمی', 'tasks' => 0, 'answers' => []],
        ];

        foreach ($people as $index => $person) {
            $participant = $challenge->participants()->create([
                'name' => $person['name'],
                'contact' => $index === 0 ? '@zahra' : null,
                'last_seen_at' => Carbon::now()->subHours($index * 5),
            ]);

            // کارها را از روزهای اول (باز‌شده‌ها) به ترتیب تیک می‌زنیم
            $unlockedTasks = $tasks
                ->filter(fn ($task) => $challenge->isDayUnlocked($task->day_number))
                ->values()
                ->take($person['tasks']);

            foreach ($unlockedTasks as $offset => $task) {
                $participant->completions()->create([
                    'challenge_task_id' => $task->id,
                    'completed_at' => Carbon::today()
                        ->subDays(max(0, 2 - intdiv($offset, 2)))
                        ->setTime(9 + ($offset % 6), 20),
                ]);
            }

            foreach ($person['answers'] as $offset) {
                $question = $questions->get($offset);

                if (! $question) {
                    continue;
                }

                $participant->questionAnswers()->create([
                    'challenge_question_id' => $question->id,
                    'body' => $this->demoAnswer($person['name'], $question->question),
                ]);
            }
        }
    }

    /**
     * متن پاسخ نمونه برای هر پرسش.
     */
    private function demoAnswer(string $name, string $question): string
    {
        return match (true) {
            str_contains($question, 'یک روز را از دست بدهم') => 'برای من هم پیش آمده؛ دیدم چیزی خراب نمی‌شود و فقط زنجیره‌ام صفر شد، برای همین این بار سعی می‌کنم همان روز را جبران کنم.',
            str_contains($question, 'همان روز انجام') => 'من عصرها بعد از کار انجام می‌دهم؛ غروب سختی دارم که این کارها به بخش ثابت روزم تبدیل شده.',
            default => $name.' هستم و این چالش برایم تمرین خوبی است تا منظم‌تر بمانم.',
        };
    }

    /**
     * یک فایل ضمیمه نمونه، تا بخش فایل‌های چالش هم در پنل و سایت دیده شود.
     */
    private function seedDemoAttachment(Challenge $challenge): void
    {
        $contents = "راهنمای چالش ۷ روزه\n\n۱. هر روز دو کار داری؛ هر وقت انجام دادی در داشبورد تیک بزن.\n۲. اگر یک روز کامل نشد، همان روز را بعداً کامل کن.\n۳. یادداشت شخصی‌ات را در داشبورد خودت بنویس.\n";

        $path = 'challenge-files/'.$challenge->id.'/guide.txt';

        Storage::disk('local')->put($path, $contents);

        $challenge->attachments()->create([
            'title' => 'راهنمای چالش (نمونه)',
            'path' => $path,
            'original_name' => 'parvaneh-sho-guide.txt',
            'size' => strlen($contents),
            'mime' => 'text/plain',
            'sort_order' => 1,
        ]);
    }
}
