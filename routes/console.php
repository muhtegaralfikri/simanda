<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduler Registration for SIMANDA
Schedule::command('simanda:alerts:generate')->dailyAt('07:00')->withoutOverlapping();
Schedule::command('simanda:backup --type=daily')->dailyAt('01:30')->withoutOverlapping();
Schedule::command('simanda:backup --type=weekly')->weeklyOn(0, '02:00')->withoutOverlapping();
Schedule::command('simanda:backup --type=monthly')->monthlyOn(1, '02:30')->withoutOverlapping();
Schedule::command('simanda:backup:verify')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('simanda:scheduler:heartbeat')->hourly()->withoutOverlapping();
