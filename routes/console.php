<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Monitor servers
Schedule::command('devflow:monitor-servers')->everyMinute();

// Collect server metrics with real-time broadcast (every minute)
Schedule::command('servers:collect-metrics --broadcast')->everyMinute();

// Check SSL expiration
Schedule::command('devflow:check-ssl')->daily();

// Clean old metrics
Schedule::command('devflow:cleanup-metrics')->daily();

// Process scheduled deployments
Schedule::command('deployments:process-scheduled')->everyMinute();

// Run scheduled database backups
Schedule::command('backups:run')->everyFifteenMinutes();

// Clean up old backups (daily at midnight)
Schedule::command('backup:cleanup')->daily()->at('00:00');

// Run health checks
Schedule::command('health:check')->everyMinute();

// Check SSL expiry and auto-renew (daily at 3 AM)
Schedule::command('ssl:check-expiry --days=14 --renew')->daily()->at('03:00');

// Renew SSL certificates (daily at 2 AM)
Schedule::command('ssl:renew')->daily()->at('02:00');

