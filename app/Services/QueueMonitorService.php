<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FailedJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;

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
                        'status' => $job->reserved_at === null ? 'pending' : 'processing',
                    ];
                });

            return $jobs->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to retrieve recent jobs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

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
            Log::error('Failed to retrieve failed jobs', [
                'error' => $e->getMessage(),
                'limit' => $limit,
                'offset' => $offset,
                'trace' => $e->getTraceAsString(),
            ]);

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
                    return [$item->queue => (int) $item->count];
                })
                ->toArray();

            return $breakdown;
        } catch (\Exception $e) {
            Log::error('Failed to get queue breakdown', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

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

            Log::info("Retrying failed job {$jobId}", [
                'job_id' => $jobId,
                'queue' => $job->queue,
                'job_class' => $job->job_class,
            ]);

            return $job->retry();
        } catch (\Exception $e) {
            Log::error("Failed to retry job {$jobId}", [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Retry all failed jobs
     */
    public function retryAllFailedJobs(): bool
    {
        try {
            $count = FailedJob::count();

            Log::info('Retrying all failed jobs', ['count' => $count]);

            $result = FailedJob::retryAll();

            if ($result) {
                Log::info('Successfully retried all failed jobs', ['count' => $count]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to retry all jobs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

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

            Log::info("Deleting failed job {$jobId}", [
                'job_id' => $jobId,
                'queue' => $job->queue,
                'job_class' => $job->job_class,
            ]);

            return $job->forget();
        } catch (\Exception $e) {
            Log::error("Failed to delete job {$jobId}", [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Clear all failed jobs
     */
    public function clearAllFailedJobs(): bool
    {
        try {
            $count = FailedJob::count();

            Log::info('Clearing all failed jobs', ['count' => $count]);

            $result = FailedJob::forgetAll();

            if ($result) {
                Log::info('Successfully cleared all failed jobs', ['count' => $count]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to clear all failed jobs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

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

                $successRate = $jobs1hour > 0 ? round((($jobs1hour - $failed1hour) / $jobs1hour) * 100, 2) : 100.0;

                return [
                    'jobs_5min' => $jobs5min,
                    'jobs_1hour' => $jobs1hour,
                    'failed_1hour' => $failed1hour,
                    'success_rate' => $successRate,
                    'throughput_per_minute' => $jobs5min > 0 ? round($jobs5min / 5, 2) : 0.0,
                ];
            } catch (\Exception $e) {
                Log::error('Failed to get processing rate', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return [
                    'jobs_5min' => 0,
                    'jobs_1hour' => 0,
                    'failed_1hour' => 0,
                    'success_rate' => 100.0,
                    'throughput_per_minute' => 0.0,
                ];
            }
        });
    }

    /**
     * Get detailed queue health status
     */
    public function getQueueHealth(): array
    {
        try {
            $pendingJobs = $this->getPendingJobsCount();
            $processingJobs = $this->getProcessingJobsCount();
            $failedJobs = $this->getFailedJobsCount();
            $workerStatus = $this->getWorkerStatus();
            $processingRate = $this->getProcessingRate();

            // Determine health status based on metrics
            $health = 'healthy';
            $issues = [];

            if (! $workerStatus['is_running']) {
                $health = 'critical';
                $issues[] = 'Queue workers are not running';
            }

            if ($failedJobs > 100) {
                $health = $health === 'critical' ? 'critical' : 'warning';
                $issues[] = "High number of failed jobs ({$failedJobs})";
            }

            if ($pendingJobs > 1000) {
                $health = $health === 'critical' ? 'critical' : 'warning';
                $issues[] = "Large queue backlog ({$pendingJobs} pending jobs)";
            }

            if ($processingRate['success_rate'] < 90) {
                $health = $health === 'critical' ? 'critical' : 'warning';
                $issues[] = "Low success rate ({$processingRate['success_rate']}%)";
            }

            return [
                'status' => $health,
                'issues' => $issues,
                'metrics' => [
                    'pending_jobs' => $pendingJobs,
                    'processing_jobs' => $processingJobs,
                    'failed_jobs' => $failedJobs,
                    'worker_count' => $workerStatus['worker_count'],
                    'success_rate' => $processingRate['success_rate'],
                    'throughput' => $processingRate['throughput_per_minute'],
                ],
                'timestamp' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get queue health', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 'unknown',
                'issues' => ['Failed to retrieve queue health metrics'],
                'metrics' => [],
                'timestamp' => now()->toIso8601String(),
            ];
        }
    }

    /**
     * Get queue size for a specific queue
     */
    public function getQueueSize(string $queueName = 'default'): int
    {
        try {
            return DB::table('jobs')
                ->where('queue', $queueName)
                ->count();
        } catch (\Exception $e) {
            Log::error("Failed to get queue size for {$queueName}", [
                'queue' => $queueName,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Purge jobs from a specific queue
     */
    public function purgeQueue(string $queueName): bool
    {
        try {
            $count = DB::table('jobs')
                ->where('queue', $queueName)
                ->count();

            Log::info("Purging queue {$queueName}", [
                'queue' => $queueName,
                'job_count' => $count,
            ]);

            DB::table('jobs')
                ->where('queue', $queueName)
                ->delete();

            Log::info("Successfully purged queue {$queueName}", [
                'queue' => $queueName,
                'jobs_deleted' => $count,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to purge queue {$queueName}", [
                'queue' => $queueName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Get average job processing time (in seconds)
     */
    public function getAverageProcessingTime(): float
    {
        $cacheKey = 'queue_monitor:avg_processing_time';

        return Cache::remember($cacheKey, 300, function () {
            try {
                // This is an approximation based on reserved jobs
                $processingJobs = DB::table('jobs')
                    ->whereNotNull('reserved_at')
                    ->select('reserved_at', 'created_at')
                    ->get();

                if ($processingJobs->isEmpty()) {
                    return 0.0;
                }

                $totalTime = 0;
                $now = time();

                foreach ($processingJobs as $job) {
                    $processingTime = $now - $job->reserved_at;
                    $totalTime += $processingTime;
                }

                return round($totalTime / $processingJobs->count(), 2);
            } catch (\Exception $e) {
                Log::error('Failed to get average processing time', [
                    'error' => $e->getMessage(),
                ]);

                return 0.0;
            }
        });
    }

    /**
     * Get jobs that have been stuck for too long (likely failed but not marked as such)
     */
    public function getStuckJobs(int $thresholdMinutes = 30): array
    {
        try {
            $threshold = now()->subMinutes($thresholdMinutes)->timestamp;

            $stuckJobs = DB::table('jobs')
                ->whereNotNull('reserved_at')
                ->where('reserved_at', '<', $threshold)
                ->get()
                ->map(function ($job) {
                    $payload = json_decode($job->payload, true);
                    $stuckDuration = time() - $job->reserved_at;

                    return [
                        'id' => $job->id,
                        'queue' => $job->queue,
                        'attempts' => $job->attempts,
                        'reserved_at' => $job->reserved_at,
                        'stuck_duration_minutes' => round($stuckDuration / 60, 2),
                        'job_class' => $payload['displayName'] ?? 'Unknown',
                    ];
                })
                ->toArray();

            return $stuckJobs;
        } catch (\Exception $e) {
            Log::error('Failed to get stuck jobs', [
                'threshold_minutes' => $thresholdMinutes,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * Clear cache for queue monitoring metrics
     */
    public function clearMonitoringCache(): bool
    {
        try {
            Cache::forget('queue_monitor:processing_rate');
            Cache::forget('queue_monitor:avg_processing_time');
            Cache::forget('queue_monitor:jobs_per_hour');

            Log::info('Queue monitoring cache cleared');

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear monitoring cache', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
