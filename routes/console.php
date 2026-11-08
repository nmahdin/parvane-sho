<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/* یادآور روزانه: هر شب ساعت ۲۰ به وقت ایران به شرکت‌کننده‌هایی که
   پوش نوتیفیکیشن را فعال کرده‌اند یادآوری می‌شود. */
Schedule::command('reminders:send')
    ->dailyAt('20:00')
    ->timezone('Asia/Tehran')
    ->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
