<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FailedJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

class QueueMonitorService
{
    /**
     * Get comprehensive queue statistics
     */
    public function getQueueStatistics(): array
    {
        return [
            'pending_jobs' => $this->getPendingJobsCount(),
            'processing_jobs' => $this->getProcessingJobsCount(),
            'failed_jobs' => $this->getFailedJobsCount(),
            'jobs_per_hour' => $this->getJobsPerHour(),
            'worker_status' => $this->getWorkerStatus(),
            'queues' => $this->getQueueBreakdown(),
        ];
    }

    /**
     * Get count of pending jobs
     */
    public function getPendingJobsCount(): int
    {
        try {
            return DB::table('jobs')
                ->where('reserved_at', null)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get count of jobs currently being processed
     */
    public function getProcessingJobsCount(): int
    {
        try {
            return DB::table('jobs')
                ->whereNotNull('reserved_at')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get count of failed jobs
     */
    public function getFailedJobsCount(): int
    {
        try {
            return FailedJob::count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get recent jobs list
     */
    public function getRecentJobs(int $limit = 50): array
    {
        try {
            $jobs = DB::table('jobs')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($job) {
                    $payload = json_decode($job->payload, true);

                    return [
                        'id' => $job->id,
                        'queue' => $job->queue,
                        'attempts' => $job->attempts,
                        'created_at' => $job->created_at,
                        'available_at' => $job->available_at,
                        'reserved_at' => $job->reserved_at,
                        'job_class' => $payload['displayName'] ?? 'Unknown',
                    ];
                });

            return $jobs->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get failed jobs with details
     */
    public function getFailedJobs(int $limit = 50, int $offset = 0): array
    {
        try {
            $failedJobs = FailedJob::orderBy('failed_at', 'desc')
                ->skip($offset)
                ->take($limit)
                ->get()
                ->map(function (FailedJob $job) {
                    return [
                        'id' => $job->id,
                        'uuid' => $job->uuid,
                        'connection' => $job->connection,
                        'queue' => $job->queue,
                        'job_class' => $job->job_class,
                        'short_exception' => $job->short_exception,
                        'exception' => $job->exception,
                        'failed_at' => $job->failed_at,
                        'failed_at_human' => $job->failed_at->diffForHumans(),
                    ];
                });

            return $failedJobs->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get jobs processed per hour (approximate)
     */
    public function getJobsPerHour(): int
    {
        $cacheKey = 'queue_monitor:jobs_per_hour';

        return Cache::remember($cacheKey, 60, function () {
            try {
                // Count jobs in the last hour
                $oneHourAgo = now()->subHour()->timestamp;

                $jobsLastHour = DB::table('jobs')
                    ->where('created_at', '>=', $oneHourAgo)
                    ->count();

                // Also count recently failed jobs
                $failedJobsLastHour = FailedJob::where('failed_at', '>=', now()->subHour())
                    ->count();

                return $jobsLastHour + $failedJobsLastHour;
            } catch (\Exception $e) {
                return 0;
            }
        });
    }

    /**
     * Check if queue workers are running
     */
    public function getWorkerStatus(): array
    {
        try {
            // Check for queue:work processes
            $result = Process::run('ps aux | grep -E "queue:work|horizon" | grep -v grep');

            $isRunning = $result->successful() && ! empty(trim($result->output()));

            // Count the number of workers
            $workerCount = 0;
            if ($isRunning) {
                $lines = explode("\n", trim($result->output()));
                $workerCount = count(array_filter($lines));
            }

            return [
                'is_running' => $isRunning,
                'worker_count' => $workerCount,
                'status' => $isRunning ? 'running' : 'stopped',
                'status_text' => $isRunning ? "Running ({$workerCount} workers)" : 'Stopped',
            ];
        } catch (\Exception $e) {
            return [
                'is_running' => false,
                'worker_count' => 0,
                'status' => 'unknown',
                'status_text' => 'Unknown',
            ];
        }
    }

    /**
     * Get breakdown of jobs by queue
     */
    public function getQueueBreakdown(): array
    {
        try {
            $breakdown = DB::table('jobs')
                ->select('queue', DB::raw('count(*) as count'))
                ->groupBy('queue')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->queue => $item->count];
                })
                ->toArray();

            return $breakdown;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Retry a specific failed job
     */
    public function retryFailedJob(int $jobId): bool
    {
        try {
            $job = FailedJob::findOrFail($jobId);

            return $job->retry();
        } catch (\Exception $e) {
            \Log::error("Failed to retry job {$jobId}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Retry all failed jobs
     */
    public function retryAllFailedJobs(): bool
    {
        try {
            return FailedJob::retryAll();
        } catch (\Exception $e) {
            \Log::error('Failed to retry all jobs: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Delete a specific failed job
     */
    public function deleteFailedJob(int $jobId): bool
    {
        try {
            $job = FailedJob::findOrFail($jobId);

            return $job->forget();
        } catch (\Exception $e) {
            \Log::error("Failed to delete job {$jobId}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Clear all failed jobs
     */
    public function clearAllFailedJobs(): bool
    {
        try {
            return FailedJob::forgetAll();
        } catch (\Exception $e) {
            \Log::error('Failed to clear all failed jobs: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Get job processing rate statistics
     */
    public function getProcessingRate(): array
    {
        $cacheKey = 'queue_monitor:processing_rate';

        return Cache::remember($cacheKey, 60, function () {
            try {
                $now = now();

                // Jobs in last 5 minutes
                $jobs5min = DB::table('jobs')
                    ->where('created_at', '>=', $now->copy()->subMinutes(5)->timestamp)
                    ->count();

                // Jobs in last hour
                $jobs1hour = DB::table('jobs')
                    ->where('created_at', '>=', $now->copy()->subHour()->timestamp)
                    ->count();

                // Failed jobs in last hour
                $failed1hour = FailedJob::where('failed_at', '>=', $now->copy()->subHour())
                    ->count();

                return [
                    'jobs_5min' => $jobs5min,
                    'jobs_1hour' => $jobs1hour,
                    'failed_1hour' => $failed1hour,
                    'success_rate' => $jobs1hour > 0 ? round((($jobs1hour - $failed1hour) / $jobs1hour) * 100, 2) : 100,
                ];
            } catch (\Exception $e) {
                return [
                    'jobs_5min' => 0,
                    'jobs_1hour' => 0,
                    'failed_1hour' => 0,
                    'success_rate' => 100,
                ];
            }
        });
    }
}
