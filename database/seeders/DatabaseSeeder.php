<?php

namespace Database\Seeders;

use App\Models\Challenge;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

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

        $challenge = Challenge::create([
            'title' => 'چالش ۷ روزه شکرگزاری و آرامش',
            'slug' => 'shokr-gozari-7',
            'tagline' => 'هفت روز، هر روز دو کار کوچک برای آرام‌تر شدن و شکرگزارتر بودن.',
            'description' => "این چالش نمونه ساخته شده تا ببینی همه‌چیز چطور کار می‌کند.\nهر روز دو کار داری؛ هر وقت انجامشان دادی در داشبورد خودت تیک بزن. اگر خواستی، همین چالش را ویرایش کن یا پاکش کن و چالش خودت را بساز.",
            'emoji' => '🦋',
            'accent' => 'violet',
            'starts_on' => Carbon::today()->subDays(2),
            'ends_on' => Carbon::today()->addDays(4),
            'is_published' => true,
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

        $names = ['زهرا محمدی', 'امیر رضایی', 'نگار کریمی'];
        $tasks = $challenge->tasks()->get();

        foreach ($names as $index => $name) {
            $participant = $challenge->participants()->create([
                'name' => $name,
                'note' => null,
            ]);

            // هر نفر کمی از کارها را انجام داده است
            $completed = $tasks->take(max(1, 12 - ($index * 4)));

            foreach ($completed as $offset => $task) {
                $participant->completions()->create([
                    'challenge_task_id' => $task->id,
                    'completed_at' => Carbon::today()->subDays(max(0, $task->day_number ? 3 - $offset % 3 : 0))->setTime(9 + ($offset % 8), 15),
                ]);
            }
        }
    }
}
