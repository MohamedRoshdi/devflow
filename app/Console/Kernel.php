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
