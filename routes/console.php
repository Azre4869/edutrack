<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwal mingguan EWS setiap Senin jam 06:00 pagi
Schedule::command('ews:run')
    ->weeklyOn(1, '06:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/ews.log'));

// Jadwal mingguan AI Analisis setelah EWS
Schedule::command('ai:analisis')
    ->weeklyOn(1, '06:15')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/ai.log'));

