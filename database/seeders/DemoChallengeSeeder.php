<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Challenge;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * چالش نمونه «۷ روزه شکرگزاری و آرامش» با کارهای هر روز، نکته‌ها،
 * پرسش‌ها و یک فایل ضمیمه؛ بعد از ساخت، شرکت‌کننده‌های نمونه را هم می‌سازد.
 */
class DemoChallengeSeeder extends Seeder
{
    public const SLUG = 'shokr-gozari-7';

    /**
     * عنوان و توضیح هر روز از چالش نمونه؛ روزها خودشان از بازه تاریخ شروع و
     * پایان ساخته می‌شوند و این فهرست فقط «باکس هر روز» را پر می‌کند.
     *
     * @var array<int, array{0: string, 1: string}>
     */
    public const DEMO_DAYS = [
        1 => ['آشنایی با شکرگزاری', 'امروز فقط شروع می‌کنیم: سه چیز کوچک که بابتشان خوشحالی را بنویس و سه نفس آرام بکش.
هدف این روز توجه به چیزهایی است که معمولاً نادیده می‌گیریم.'],
        2 => ['حرکت و آب', 'امروز بدن هم سهم دارد: بیست دقیقه پیاده‌روی بدون گوشی و برنامه‌ریزی برای آب کافی.
اگر وقت کم داری، پیاده‌روی را به دو نوبت ده‌دقیقه‌ای بشکن.'],
        3 => ['آرام‌کردن ذهن', 'روز سوم درباره آرام‌کردن ذهن است؛ تمرین تنفس و ده دقیقه سکوت بدون صفحه.
همین دو کار کوچک، تمرکزت را برای بقیه روز بهتر می‌کند.'],
        4 => ['محبت به دیگران', 'امروز یک تعریف صادقانه به کسی بگو و برای یک دوست قدیمی پیام محبت‌آمیز بفرست.
شکرگزاری وقتی به دیگران هم می‌رسد، اثر بیشتری دارد.'],
        5 => ['تمرکز و نظم کوچک', 'بیست‌وپنج دقیقه بدون موبایل کار کن و یک گوشه کوچک از فضای اطرافت را مرتب کن.
نظم کوچک، حالِ درونی را هم مرتب می‌کند.'],
        6 => ['مهربانی در عمل', 'یک کمک کوچک به یک نفر بکن و سه دقیقه نرمش کن.
مهربانی لازم نیست بزرگ باشد؛ فقط لازم است واقعی باشد.'],
        7 => ['جمع‌بندی هفته', 'روز آخر: هفته را مرور کن، سه درس بنویس و یک هدف کوچک برای هفته بعد انتخاب کن.
به خودت هم یک جایزه کوچک بده.'],
    ];

    public function run(): void
    {
        if (Challenge::query()->exists()) {
            $this->say('چالش از قبل وجود دارد؛ چالش نمونه ساخته نشد.');

            // اگر سایت از قبل ساخته شده بود، عنوان و توضیح روزهای چالش نمونه را
            // تکمیل می‌کنیم (روزهای موجود دست‌نخورده می‌مانند).
            $filled = self::seedDaysFor(
                Challenge::query()->where('slug', self::SLUG)->first()
            );

            if ($filled > 0) {
                $this->say('عنوان و توضیح '.$filled.' روز برای «باکس روزها»ی چالش نمونه ثبت شد.');
            }

            return;
        }

        $challenge = Challenge::create([
            'admin_id' => $this->superAdminId(),
            'title' => 'چالش ۷ روزه شکرگزاری و آرامش',
            'slug' => self::SLUG,
            'tagline' => 'هفت روز، هر روز دو کار کوچک برای آرام‌تر شدن و شکرگزارتر بودن.',
            'description' => "این چالش نمونه ساخته شده تا ببینی همه‌چیز چطور کار می‌کند.\nهر روز دو کار داری؛ هر وقت انجامشان دادی در داشبورد خودت تیک بزن. اگر خواستی، همین چالش را ویرایش کن یا پاکش کن و چالش خودت را بساز.",
            'emoji' => '🦋',
            'accent' => 'jade',
            'starts_on' => Carbon::today()->subDays(2),
            'ends_on' => Carbon::today()->addDays(4),
            'is_published' => true,
            'daily_release' => true,
            'pledge_enabled' => true,
            'pledge_title' => 'تعهدنامهٔ چالش ۷ روزه شکرگزاری',
            'pledge_text' => "با ثبت این تعهدنامه قبول می‌کنم:\n- هر روز فقط به کارهای همان روز رسیدگی کنم و صادقانه تیک بزنم.\n- اگر یک روز را از دست دادم، چالش را رها نکنم و روز بعد ادامه بدهم.\n- نکته‌ها را بخوانم و تمرین‌ها را با حوصله انجام بدهم.\n\nمی‌دانم این تعهدنامه برای رشد خودم است، نه برای امتیاز گرفتن از کسی.",
            'sort_order' => 1,
        ]);

        $this->seedTasks($challenge);
        $this->seedDays($challenge);
        $this->seedTips($challenge);
        $this->seedDayTips($challenge);
        $this->seedQuestions($challenge);

        $challenge->update([
            'long_description' => "هدف این چالش ساده است: هر روز یک قدم کوچک برای آرام‌تر بودن و شکرگزارتر بودن.\n\n## قوانین ساده\n- هر کار را همان روز انجام بده؛ اگر نشد، آخر هفته جبران کن.\n- تیکِ انجام‌شده یعنی واقعاً انجام شده.\n- اگر یک روز از دست رفت، چالش تمام نمی‌شود؛ روز بعد ادامه بده.\n\n## چه چیزی به دست می‌آوری؟\nتمرین روزانه شکرگزاری و آرامش بعد از یک هفته به عادت کوچکی تبدیل می‌شود که خودت اثرش را حس می‌کنی. متن‌های **پررنگ** و فهرست‌ها در همین توضیحات پشتیبانی می‌شوند.",
            'contact_note' => 'اگر سوالی داشتی، به آیدی @parvaneh_admin پیام بده.',
        ]);

        $this->seedAttachment($challenge);

        $this->say('چالش نمونه «'.$challenge->title.'» ساخته شد.');

        $this->call(DemoParticipantsSeeder::class);
    }

    /**
     * دو کار برای هر روز از هفت روز چالش.
     */
    private function seedTasks(Challenge $challenge): void
    {
        // هر روز دو کار کوچک (مثل توضیح چالش: «هر روز دو کار داری»)
        $days = [
            1 => [
                ['نوشتن سه جمله شکرگزاری', 'سه چیز کوچک که امروز بابتشان خوشحال بودی.'],
                ['سه نفس عمیق، همین حالا', 'دست نگه دار و سه نفس آرام بکش؛ بعد به کارت برگرد.'],
            ],
            2 => [
                ['۲۰ دقیقه پیاده‌روی', 'بدون گوشی؛ فقط قدم زدن و نگاه کردن.'],
                ['خوردن آب کافی', '۶ تا ۸ لیوان آب را برای امروزت برنامه‌ریزی کن.'],
            ],
            3 => [
                ['۱۰ دقیقه تمرین تنفس عمیق', 'چهار ثانیه دم، هفت ثانیه نگه‌داشتن، هشت ثانیه بازدم.'],
                ['۱۰ دقیقه بی‌صدا بودن', 'بدون موسیقی و بدون صفحه؛ فقط سکوت و خودت.'],
            ],
            4 => [
                ['گفتن یک تعریف صادقانه', 'به یک نفر بگو چه چیزی در او را دوست داری.'],
                ['پیام محبت‌آمیز به یک دوست قدیمی', 'برای کسی که مدت‌هاست ازش خبر نداری پیام بفرست.'],
            ],
            5 => [
                ['۲۵ دقیقه بدون موبایل', 'گوشی را در اتاق دیگری بگذار و به کار خودت برس.'],
                ['مرتب‌کردن یک گوشه کوچک', 'یک کشو یا یک گوشه میز؛ فقط همان.'],
            ],
            6 => [
                ['کمک کوچک به یک نفر', 'حتی یک کار ساده، مثل شستن ظرف‌ها.'],
                ['سه دقیقه کشش و نرمش', 'گردن، شانه و کمرت را آرام کش بیاور.'],
            ],
            7 => [
                ['مرور هفته و نوشتن سه درس', 'چه چیزی خوب پیش رفت و چه چیزی را می‌خواهی بهتر کنی؟'],
                ['تعیین یک هدف کوچک برای هفته بعد', 'یک کار کوچک که می‌خواهی ادامه بدهی.'],
            ],
        ];

        $sortOrder = 1;

        foreach ($days as $day => $tasksOfDay) {
            foreach ($tasksOfDay as [$title, $description]) {
                $challenge->tasks()->create([
                    'day_number' => $day,
                    'title' => $title,
                    'description' => $description,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }
    }

    /**
     * عنوان و توضیح هر روز از چالش؛ روزها پیش‌فرض از بازه شروع و پایان ساخته
     * می‌شوند و اینجا برای هرکدام یک عنوان و یک توضیح نمونه می‌گذاریم.
     */
    private function seedDays(Challenge $challenge): void
    {
        self::seedDaysFor($challenge);
    }

    /**
     * ثبت عنوان و توضیح روزها برای یک چالش (فقط روزهایی که هنوز ثبت نشده‌اند).
     * روی چالش موجود هم قابل استفاده است تا سایت‌های قدیمی به‌روز شوند.
     *
     * @return int تعداد روزهایی که تازه ثبت شدند
     */
    public static function seedDaysFor(?Challenge $challenge): int
    {
        if (! $challenge) {
            return 0;
        }

        $created = 0;

        foreach (self::DEMO_DAYS as $day => [$title, $description]) {
            $existing = $challenge->days()->where('day_number', $day)->first();

            if ($existing) {
                // اگر سایت قبلاً ساخته شده و متن روزها با «\n» خام ذخیره شده
                // بود، همان‌جا اصلاحش می‌کنیم تا متن چندخطی درست دیده شود.
                if ($existing->description && str_contains($existing->description, '\\n')) {
                    $existing->update(['description' => str_replace('\\n', "\n", $existing->description)]);
                }

                continue;
            }

            $challenge->days()->create([
                'day_number' => $day,
                'title' => $title,
                'description' => $description,
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * نکته‌های کلی چالش (بدون روز مشخص).
     */
    private function seedTips(Challenge $challenge): void
    {
        $challenge->tips()->createMany([
            ['emoji' => '💧', 'title' => 'آب کافی بنوش', 'body' => 'روزی ۶ تا ۸ لیوان آب؛ خیلی از بی‌حوصلگی‌های بعدازظهر از کم‌آبی است.', 'sort_order' => 1],
            ['emoji' => '⏰', 'title' => 'یک ساعت ثابت انتخاب کن', 'body' => 'اگر کارها را همیشه در یک ساعت مشخص انجام بدهی، سریع‌تر به عادت تبدیل می‌شوند.', 'sort_order' => 2],
            ['emoji' => '⚠️', 'title' => 'با خودت سختگیر نباش', 'body' => 'اگر یک روز کامل نشد، اشکالی ندارد؛ همان روز را بعداً کامل کن.', 'sort_order' => 3],
        ]);
    }

    /**
     * هر روز چالش هم نکته‌های مخصوص خودش را دارد.
     */
    private function seedDayTips(Challenge $challenge): void
    {
        $dayTips = [
            1 => ['emoji' => '📝', 'title' => 'سه چیز، هر شب', 'body' => 'بهترین وقت برای نوشتن شکرگزاری، همان چند دقیقه قبل از خواب است.'],
            2 => ['emoji' => '🚶', 'title' => 'بدون گوشی قدم بزن', 'body' => 'اگر گوشی همراهت باشد، ذهنت به آن برمی‌گردد؛ فقط قدم و نگاه.'],
            3 => ['emoji' => '🌬️', 'title' => 'شمارش نفس‌ها', 'body' => '۴ ثانیه دم، ۷ ثانیه نگه‌داشتن، ۸ ثانیه بازدم؛ همین الگو آرامش می‌آورد.'],
            4 => ['emoji' => '💬', 'title' => 'تعریف باید مشخص باشد', 'body' => '«تو مهربانی» خوب است؛ «وقتی بدون اینکه بگویم کمکم کردی...» بهتر است.'],
            5 => ['emoji' => '📵', 'title' => 'نیم ساعت بدون صفحه', 'body' => 'نیم ساعت بدون صفحه‌نمایش، تمرکز و خواب امشب را بهتر می‌کند.'],
            6 => ['emoji' => '🍵', 'title' => 'آهسته و با حوصله', 'body' => 'یکی از کارهای امروز را با آرامش و بدون عجله انجام بده.'],
            7 => ['emoji' => '🎉', 'title' => 'به خودت جایزه بده', 'body' => 'یک هفته زحمت کشیدی؛ یک کار کوچک که خوشحالت می‌کند برای خودت انجام بده.'],
        ];

        foreach ($dayTips as $day => $tip) {
            $challenge->tips()->create([
                'day_number' => $day,
                'emoji' => $tip['emoji'],
                'title' => $tip['title'],
                'body' => $tip['body'],
                'sort_order' => 10 + $day,
            ]);
        }
    }

    private function seedQuestions(Challenge $challenge): void
    {
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
    }

    /**
     * یک فایل ضمیمه نمونه، تا بخش فایل‌های چالش هم در پنل و سایت دیده شود.
     */
    private function seedAttachment(Challenge $challenge): void
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

    /**
     * شناسه مدیر اصلی؛ اگر نبود، اولین مدیر فعال.
     */
    private function superAdminId(): ?int
    {
        return Admin::query()->where('username', 'admin')->value('id')
            ?? Admin::query()->orderBy('id')->value('id');
    }

    private function say(string $message): void
    {
        if ($this->command) {
            $this->command->info($message);
        }
    }
}
