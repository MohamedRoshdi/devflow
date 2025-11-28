<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Monitor servers
Schedule::command('devflow:monitor-servers')->everyMinute();

// Collect server metrics (every 5 minutes)
Schedule::command('servers:collect-metrics')->everyFiveMinutes();

// Check SSL expiration
Schedule::command('devflow:check-ssl')->daily();

// Clean old metrics
Schedule::command('devflow:cleanup-metrics')->daily();

// Process scheduled deployments
Schedule::command('deployments:process-scheduled')->everyMinute();

// Run scheduled database backups
Schedule::command('backups:run')->everyFifteenMinutes();

// Run health checks
Schedule::command('health:check')->everyMinute();

// Renew SSL certificates (daily at 2 AM)
Schedule::command('ssl:renew')->daily()->at('02:00');

