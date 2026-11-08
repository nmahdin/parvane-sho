<?php

namespace App\Console\Commands;

use App\Models\PushSubscription;
use App\Models\Setting;
use App\Support\WebPush;
use Illuminate\Console\Command;

/**
 * یادآور روزانه: به شرکت‌کننده‌هایی که پوش نوتیفیکیشن را فعال کرده‌اند
 * یک اعلان «کارهای امروزت منتظرته» می‌فرستد.
 * زمان‌بندی: Schedule در bootstrap/app.php هر روز ساعت ۲۰:۰۰.
 */
class SendDailyReminders extends Command
{
    protected $signature = 'reminders:send {--dry : فقط بشمار و چیزی نفرست}';

    protected $description = 'ارسال پوش نوتیفیکیشن یادآور روزانه به شرکت‌کننده‌ها';

    public function handle(): int
    {
        $subscriptions = PushSubscription::query()
            ->with('participant.challenge')
            ->get()
            ->filter(fn (PushSubscription $sub) => $sub->participant
                && $sub->participant->challenge
                && $sub->participant->challenge->is_published);

        if ($subscriptions->isEmpty()) {
            $this->info('هیچ اشتراک فعالی نیست؛ چیزی فرستاده نشد.');

            return self::SUCCESS;
        }

        if ($this->option('dry')) {
            $this->info('حالت خشک: '.$subscriptions->count().' اشتراک پیدا شد.');

            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($subscriptions as $subscription) {
            $participant = $subscription->participant;

            try {
                $response = WebPush::send([
                    'endpoint' => $subscription->endpoint,
                    'p256dh' => $subscription->p256dh,
                    'auth' => $subscription->auth,
                ], [
                    'title' => 'یادآوری '.($participant->challenge->title ?? 'چالش'),
                    'body' => 'کارهای امروزت منتظرته! 🌱 چند دقیقه وقت بگذار و روزت را کامل کن.',
                    'url' => route('participant.show', $participant),
                ]);

                // اشتراک‌های مرده (۴۰۴/۴۱۰) پاک می‌شوند
                if (in_array($response->status(), [404, 410], true)) {
                    $subscription->delete();
                    $failed++;

                    continue;
                }

                $sent++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        $this->info('یادآور روزانه: '.fa_num($sent).' ارسال شد، '.fa_num($failed).' ناموفق.');

        return self::SUCCESS;
    }
}
