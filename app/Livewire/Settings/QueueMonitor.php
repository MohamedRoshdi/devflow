<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Services\QueueMonitorService;
use Livewire\Attributes\On;
use Livewire\Component;

class QueueMonitor extends Component
{
    /**
     * @var array<string, mixed>
     */
    public array $queueStats = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $failedJobs = [];

    /**
     * @var array<string, mixed>
     */
    public array $selectedJob = [];

    public bool $showJobDetails = false;

    public bool $isLoading = true;

    private QueueMonitorService $queueMonitor;

    public function boot(QueueMonitorService $queueMonitor): void
    {
        $this->queueMonitor = $queueMonitor;
    }

    public function mount(): void
    {
        $this->loadStats();
        $this->isLoading = false;
    }

    public function loadStats(): void
    {
        try {
            $this->queueStats = $this->queueMonitor->getQueueStatistics();
            $this->failedJobs = $this->queueMonitor->getFailedJobs(50);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load queue statistics: '.$e->getMessage());
            $this->queueStats = [
                'pending_jobs' => 0,
                'processing_jobs' => 0,
                'failed_jobs' => 0,
                'jobs_per_hour' => 0,
                'worker_status' => [
                    'is_running' => false,
                    'worker_count' => 0,
                    'status' => 'unknown',
                    'status_text' => 'Unknown',
                ],
                'queues' => [],
            ];
            $this->failedJobs = [];
        }
    }

    public function refreshStats(): void
    {
        $this->isLoading = true;
        $this->loadStats();
        $this->isLoading = false;

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Queue statistics refreshed',
        ]);
    }

    public function viewJobDetails(int $jobId): void
    {
        $job = collect($this->failedJobs)->firstWhere('id', $jobId);

        if ($job) {
            $this->selectedJob = $job;
            $this->showJobDetails = true;
        }
    }

    public function closeJobDetails(): void
    {
        $this->showJobDetails = false;
        $this->selectedJob = [];
    }

    public function retryJob(int $jobId): void
    {
        try {
            $success = $this->queueMonitor->retryFailedJob($jobId);

            if ($success) {
                $this->loadStats();
                session()->flash('message', 'Job queued for retry successfully!');

                $this->dispatch('notification', [
                    'type' => 'success',
                    'message' => 'Job queued for retry',
                ]);
            } else {
                session()->flash('error', 'Failed to retry job');

                $this->dispatch('notification', [
                    'type' => 'error',
                    'message' => 'Failed to retry job',
                ]);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error retrying job: '.$e->getMessage());

            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Error retrying job',
            ]);
        }
    }

    public function retryAllFailed(): void
    {
        try {
            $success = $this->queueMonitor->retryAllFailedJobs();

            if ($success) {
                $this->loadStats();
                session()->flash('message', 'All failed jobs queued for retry!');

                $this->dispatch('notification', [
                    'type' => 'success',
                    'message' => 'All failed jobs queued for retry',
                ]);
            } else {
                session()->flash('error', 'Failed to retry all jobs');

                $this->dispatch('notification', [
                    'type' => 'error',
                    'message' => 'Failed to retry all jobs',
                ]);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error retrying jobs: '.$e->getMessage());

            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Error retrying jobs',
            ]);
        }
    }

    public function deleteJob(int $jobId): void
    {
        try {
            $success = $this->queueMonitor->deleteFailedJob($jobId);

            if ($success) {
                $this->loadStats();
                session()->flash('message', 'Failed job deleted successfully!');

                $this->dispatch('notification', [
                    'type' => 'success',
                    'message' => 'Failed job deleted',
                ]);
            } else {
                session()->flash('error', 'Failed to delete job');

                $this->dispatch('notification', [
                    'type' => 'error',
                    'message' => 'Failed to delete job',
                ]);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting job: '.$e->getMessage());

            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Error deleting job',
            ]);
        }
    }

    public function clearAllFailed(): void
    {
        try {
            $success = $this->queueMonitor->clearAllFailedJobs();

            if ($success) {
                $this->loadStats();
                session()->flash('message', 'All failed jobs cleared successfully!');

                $this->dispatch('notification', [
                    'type' => 'success',
                    'message' => 'All failed jobs cleared',
                ]);
            } else {
                session()->flash('error', 'Failed to clear all jobs');

                $this->dispatch('notification', [
                    'type' => 'error',
                    'message' => 'Failed to clear all jobs',
                ]);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error clearing jobs: '.$e->getMessage());

            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Error clearing jobs',
            ]);
        }
    }

    #[On('queue-stats-refresh')]
    public function handleQueueRefresh(): void
    {
        $this->loadStats();
    }

    public function render()
    {
        return view('livewire.settings.queue-monitor');
    }
}
