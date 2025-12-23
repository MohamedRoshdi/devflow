<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Health checks - Every 5 minutes
        $schedule->command('health:check')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Server metrics collection - Every minute
        $schedule->command('servers:collect-metrics')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        // Alert checks - Every 5 minutes
        $schedule->command('alerts:check')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // SSL certificate expiry check - Daily at 1 AM
        $schedule->command('ssl:check-expiry')
            ->dailyAt('01:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Process scheduled deployments - Every minute
        $schedule->command('deployments:process-scheduled')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        // Run backups - Daily at 3 AM
        $schedule->command('backups:run')
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Server backups - Daily at 4 AM
        $schedule->command('server:backups')
            ->dailyAt('04:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Sync logs from all servers every 5 minutes
        $schedule->command('logs:sync')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Clean old logs (older than 30 days) daily at 2 AM
        $schedule->call(function () {
            \App\Services\LogAggregationService::class;
            app(\App\Services\LogAggregationService::class)->cleanOldLogs(30);
        })->dailyAt('02:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
