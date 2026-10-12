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

    public function run(): void
    {
        if (Challenge::query()->exists()) {
            $this->say('چالش از قبل وجود دارد؛ چالش نمونه ساخته نشد.');

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
            'sort_order' => 1,
        ]);

        $this->seedTasks($challenge);
        $this->seedTips($challenge);
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

    private function seedTips(Challenge $challenge): void
    {
        $challenge->tips()->createMany([
            ['emoji' => '💧', 'title' => 'آب کافی بنوش', 'body' => 'روزی ۶ تا ۸ لیوان آب؛ خیلی از بی‌حوصلگی‌های بعدازظهر از کم‌آبی است.', 'sort_order' => 1],
            ['emoji' => '⏰', 'title' => 'یک ساعت ثابت انتخاب کن', 'body' => 'اگر کارها را همیشه در یک ساعت مشخص انجام بدهی، سریع‌تر به عادت تبدیل می‌شوند.', 'sort_order' => 2],
            ['emoji' => '⚠️', 'title' => 'با خودت سختگیر نباش', 'body' => 'اگر یک روز کامل نشد، اشکالی ندارد؛ همان روز را بعداً کامل کن.', 'sort_order' => 3],
        ]);
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
