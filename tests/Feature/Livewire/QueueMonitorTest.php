<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\Settings\QueueMonitor;
use App\Models\User;
use App\Services\QueueMonitorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class QueueMonitorTest extends TestCase
{
    // use RefreshDatabase; // Commented to use DatabaseTransactions from base TestCase

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaultStats(): array
    {
        return [
            'pending_jobs' => 10,
            'processing_jobs' => 2,
            'failed_jobs' => 3,
            'jobs_per_hour' => 100,
            'worker_status' => [
                'is_running' => true,
                'worker_count' => 4,
                'status' => 'running',
                'status_text' => 'Running',
            ],
            'queues' => [
                ['name' => 'default', 'size' => 5],
                ['name' => 'high', 'size' => 3],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getDefaultFailedJobs(): array
    {
        return [
            [
                'id' => 1,
                'uuid' => 'uuid-1',
                'connection' => 'redis',
                'queue' => 'default',
                'payload' => '{}',
                'exception' => 'TestException',
                'failed_at' => now()->toDateTimeString(),
            ],
            [
                'id' => 2,
                'uuid' => 'uuid-2',
                'connection' => 'redis',
                'queue' => 'high',
                'payload' => '{}',
                'exception' => 'AnotherException',
                'failed_at' => now()->subHour()->toDateTimeString(),
            ],
        ];
    }

    private function mockQueueMonitorService(
        ?array $stats = null,
        ?array $failedJobs = null,
        bool $retrySuccess = true,
        bool $deleteSuccess = true,
        bool $clearSuccess = true
    ): void {
        $stats = $stats ?? $this->getDefaultStats();
        $failedJobs = $failedJobs ?? $this->getDefaultFailedJobs();

        $this->mock(QueueMonitorService::class, function (MockInterface $mock) use (
            $stats,
            $failedJobs,
            $retrySuccess,
            $deleteSuccess,
            $clearSuccess
        ): void {
            $mock->shouldReceive('getQueueStatistics')->andReturn($stats);
            $mock->shouldReceive('getFailedJobs')->andReturn($failedJobs);
            $mock->shouldReceive('retryFailedJob')->andReturn($retrySuccess);
            $mock->shouldReceive('retryAllFailedJobs')->andReturn($retrySuccess);
            $mock->shouldReceive('deleteFailedJob')->andReturn($deleteSuccess);
            $mock->shouldReceive('clearAllFailedJobs')->andReturn($clearSuccess);
        });
    }

    // ==================== RENDERING TESTS ====================

    public function test_component_renders_successfully(): void
    {
        $this->mockQueueMonitorService();

        Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->assertStatus(200);
    }

    public function test_initial_loading_state(): void
    {
        $this->mockQueueMonitorService();

        Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->assertSet('isLoading', true);
    }

    // ==================== LOAD STATS TESTS ====================

    public function test_can_load_stats(): void
    {
        $this->mockQueueMonitorService();

        Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('loadStats')
            ->assertSet('isLoading', false)
            ->assertSet('queueStats.pending_jobs', 10)
            ->assertSet('queueStats.processing_jobs', 2)
            ->assertSet('queueStats.failed_jobs', 3);
    }

    public function test_load_stats_populates_failed_jobs(): void
    {
        $this->mockQueueMonitorService();

        $component = Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('loadStats');

        $failedJobs = $component->get('failedJobs');
        $this->assertCount(2, $failedJobs);
    }

    public function test_load_stats_handles_exception(): void
    {
        $this->mock(QueueMonitorService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getQueueStatistics')
                ->andThrow(new \Exception('Connection failed'));
        });

        Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('loadStats')
            ->assertSessionHas('error')
            ->assertSet('queueStats.pending_jobs', 0)
            ->assertSet('queueStats.failed_jobs', 0);
    }

    public function test_load_stats_sets_fallback_values_on_error(): void
    {
        $this->mock(QueueMonitorService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getQueueStatistics')
                ->andThrow(new \Exception('Error'));
        });

        $component = Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('loadStats');

        $stats = $component->get('queueStats');
        $this->assertEquals(0, $stats['pending_jobs']);
        $this->assertEquals(0, $stats['processing_jobs']);
        $this->assertEquals(0, $stats['failed_jobs']);
        $this->assertEquals(0, $stats['jobs_per_hour']);
        $this->assertFalse($stats['worker_status']['is_running']);
    }

    // ==================== REFRESH STATS TESTS ====================

    public function test_can_refresh_stats(): void
    {
        $this->mockQueueMonitorService();

        Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('refreshStats')
            ->assertSet('isLoading', false)
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'refreshed');
            });
    }

    public function test_refresh_sets_loading_state(): void
    {
        $this->mockQueueMonitorService();

        // After refresh completes, isLoading should be false
        Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('refreshStats')
            ->assertSet('isLoading', false);
    }

    // ==================== JOB DETAILS TESTS ====================

    public function test_can_view_job_details(): void
    {
        $this->mockQueueMonitorService();

        Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('loadStats')
            ->call('viewJobDetails', 1)
            ->assertSet('showJobDetails', true);
    }

    public function test_view_job_details_sets_selected_job(): void
    {
        $this->mockQueueMonitorService();

        $component = Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('loadStats')
            ->call('viewJobDetails', 1);

        $selectedJob = $component->get('selectedJob');
        $this->assertEquals(1, $selectedJob['id']);
    }

    public function test_view_job_details_ignores_invalid_id(): void
    {
        $this->mockQueueMonitorService();

        Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('loadStats')
            ->call('viewJobDetails', 99999)
            ->assertSet('showJobDetails', false);
    }

    public function test_can_close_job_details(): void
    {
        $this->mockQueueMonitorService();

        Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('loadStats')
            ->call('viewJobDetails', 1)
            ->assertSet('showJobDetails', true)
            ->call('closeJobDetails')
            ->assertSet('showJobDetails', false)
            ->assertSet('selectedJob', []);
    }

    // ==================== RETRY SINGLE JOB TESTS ====================

    public function test_can_retry_job(): void
    {
        $this->mockQueueMonitorService(retrySuccess: true);

        Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('loadStats')
            ->call('retryJob', 1)
            ->assertSessionHas('message', 'Job queued for retry successfully!')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'success';
            });
    }

    public function test_retry_job_failure_shows_error(): void
    {
        $this->mockQueueMonitorService(retrySuccess: false);

        Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('loadStats')
            ->call('retryJob', 1)
            ->assertSessionHas('error', 'Failed to retry job')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'error';
            });
    }

    public function test_retry_job_handles_exception(): void
    {
        $this->mock(QueueMonitorService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getQueueStatistics')->andReturn($this->getDefaultStats());
            $mock->shouldReceive('getFailedJobs')->andReturn($this->getDefaultFailedJobs());
            $mock->shouldReceive('retryFailedJob')
                ->andThrow(new \Exception('Network error'));
        });

        Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('loadStats')
            ->call('retryJob', 1)
            ->assertSessionHas('error')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'error';
            });
    }

    // ==================== RETRY ALL TESTS ====================

    public function test_can_retry_all_failed(): void
    {
        $this->mockQueueMonitorService(retrySuccess: true);

        Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('loadStats')
            ->call('retryAllFailed')
            ->assertSessionHas('message', 'All failed jobs queued for retry!')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'All failed jobs');
            });
    }

    public function test_retry_all_failure_shows_error(): void
    {
        $this->mockQueueMonitorService(retrySuccess: false);

        Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('loadStats')
            ->call('retryAllFailed')
            ->assertSessionHas('error')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'error';
            });
    }

    public function test_retry_all_handles_exception(): void
    {
        $this->mock(QueueMonitorService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getQueueStatistics')->andReturn($this->getDefaultStats());
            $mock->shouldReceive('getFailedJobs')->andReturn($this->getDefaultFailedJobs());
            $mock->shouldReceive('retryAllFailedJobs')
                ->andThrow(new \Exception('Database error'));
        });

        Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('loadStats')
            ->call('retryAllFailed')
            ->assertSessionHas('error')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'error';
            });
    }

    // ==================== DELETE JOB TESTS ====================

    public function test_can_delete_job(): void
    {
        $this->mockQueueMonitorService(deleteSuccess: true);

        Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('loadStats')
            ->call('deleteJob', 1)
            ->assertSessionHas('message', 'Failed job deleted successfully!')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'deleted');
            });
    }

    public function test_delete_job_failure_shows_error(): void
    {
        $this->mockQueueMonitorService(deleteSuccess: false);

        Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('loadStats')
            ->call('deleteJob', 1)
            ->assertSessionHas('error')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'error';
            });
    }

    public function test_delete_job_handles_exception(): void
    {
        $this->mock(QueueMonitorService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getQueueStatistics')->andReturn($this->getDefaultStats());
            $mock->shouldReceive('getFailedJobs')->andReturn($this->getDefaultFailedJobs());
            $mock->shouldReceive('deleteFailedJob')
                ->andThrow(new \Exception('Delete failed'));
        });

        Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('loadStats')
            ->call('deleteJob', 1)
            ->assertSessionHas('error')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'error';
            });
    }

    // ==================== CLEAR ALL TESTS ====================

    public function test_can_clear_all_failed(): void
    {
        $this->mockQueueMonitorService(clearSuccess: true);

        Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('loadStats')
            ->call('clearAllFailed')
            ->assertSessionHas('message', 'All failed jobs cleared successfully!')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'success' &&
                    str_contains($data['message'], 'cleared');
            });
    }

    public function test_clear_all_failure_shows_error(): void
    {
        $this->mockQueueMonitorService(clearSuccess: false);

        Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('loadStats')
            ->call('clearAllFailed')
            ->assertSessionHas('error')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'error';
            });
    }

    public function test_clear_all_handles_exception(): void
    {
        $this->mock(QueueMonitorService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getQueueStatistics')->andReturn($this->getDefaultStats());
            $mock->shouldReceive('getFailedJobs')->andReturn($this->getDefaultFailedJobs());
            $mock->shouldReceive('clearAllFailedJobs')
                ->andThrow(new \Exception('Clear failed'));
        });

        Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('loadStats')
            ->call('clearAllFailed')
            ->assertSessionHas('error')
            ->assertDispatched('notification', function ($name, $data) {
                return $data['type'] === 'error';
            });
    }

    // ==================== EVENT LISTENER TESTS ====================

    public function test_responds_to_queue_stats_refresh_event(): void
    {
        $this->mockQueueMonitorService();

        Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->dispatch('queue-stats-refresh')
            ->assertSet('isLoading', false);
    }

    // ==================== DEFAULT VALUES TESTS ====================

    public function test_default_values(): void
    {
        $this->mockQueueMonitorService();

        Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->assertSet('queueStats', [])
            ->assertSet('failedJobs', [])
            ->assertSet('selectedJob', [])
            ->assertSet('showJobDetails', false)
            ->assertSet('isLoading', true);
    }

    // ==================== STATS STRUCTURE TESTS ====================

    public function test_stats_includes_worker_status(): void
    {
        $this->mockQueueMonitorService();

        $component = Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('loadStats');

        $stats = $component->get('queueStats');
        $this->assertArrayHasKey('worker_status', $stats);
        $this->assertTrue($stats['worker_status']['is_running']);
        $this->assertEquals(4, $stats['worker_status']['worker_count']);
    }

    public function test_stats_includes_queues_list(): void
    {
        $this->mockQueueMonitorService();

        $component = Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('loadStats');

        $stats = $component->get('queueStats');
        $this->assertArrayHasKey('queues', $stats);
        $this->assertCount(2, $stats['queues']);
    }

    // ==================== EMPTY STATE TESTS ====================

    public function test_handles_empty_failed_jobs(): void
    {
        $this->mockQueueMonitorService(failedJobs: []);

        $component = Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('loadStats');

        $failedJobs = $component->get('failedJobs');
        $this->assertEmpty($failedJobs);
    }

    public function test_handles_zero_pending_jobs(): void
    {
        $stats = $this->getDefaultStats();
        $stats['pending_jobs'] = 0;
        $this->mockQueueMonitorService(stats: $stats);

        Livewire::actingAs($this->user)
            ->test(QueueMonitor::class)
            ->call('loadStats')
            ->assertSet('queueStats.pending_jobs', 0);
    }
}
