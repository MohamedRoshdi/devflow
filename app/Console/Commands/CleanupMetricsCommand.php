<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServerMetric;
use App\Models\ProjectAnalytic;

class CleanupMetricsCommand extends Command
{
    protected $signature = 'devflow:cleanup-metrics {--days=90}';
    protected $description = 'Clean up old metrics data';

    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = now()->subDays($days);

        $this->info("Cleaning up metrics older than {$days} days...");

        // Clean server metrics
        $deletedServerMetrics = ServerMetric::where('recorded_at', '<', $cutoffDate)->delete();
        $this->info("Deleted {$deletedServerMetrics} server metrics");

        // Clean project analytics
        $deletedProjectAnalytics = ProjectAnalytic::where('recorded_at', '<', $cutoffDate)->delete();
        $this->info("Deleted {$deletedProjectAnalytics} project analytics");

        $this->info('Cleanup completed!');
        return 0;
    }
}

