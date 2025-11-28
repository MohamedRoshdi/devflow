<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Monitor servers
Schedule::command('devflow:monitor-servers')->everyMinute();

// Check SSL expiration
Schedule::command('devflow:check-ssl')->daily();

// Clean old metrics
Schedule::command('devflow:cleanup-metrics')->daily();

// Process scheduled deployments
Schedule::command('deployments:process-scheduled')->everyMinute();

