<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('ota:cleanup-expired-access')->hourly();
Schedule::command('ota:send-daily-report')->dailyAt('08:00');
Schedule::command('ota:send-weekly-report')->weeklyOn(1, '08:00');
Schedule::command('ota:send-monthly-report')->monthlyOn(1, '08:00');
Schedule::command('ota:send-monthly-ledgers')->monthlyOn(1, '09:00');
