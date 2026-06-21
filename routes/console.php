<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Example scheduled jobs — uncomment when implementing
// Schedule::command('reports:daily-summary')->dailyAt('23:00');
// Schedule::command('payroll:process-pending')->monthlyOn(28, '00:00');
