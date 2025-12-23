<?php

declare(strict_types=1);

namespace Tests\Feature\Performance;

use App\Livewire\Projects\ProjectShow;
use App\Models\Project;
use App\Models\Server;
use App\Models\Team;
use App\Models\User;
use App\Services\GitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

/**
 * Performance tests for Git update status caching in ProjectShow
 *
 * Verifies that Git status checks are cached to avoid expensive SSH calls
 */
class GitCachingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Team $team;
    private Server $server;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->team = Team::factory()->create(['owner_id' => $this->user->id]);
        $this->user->teams()->attach($this->team->id, ['role' => 'owner']);
        $this->user->update(['current_team_id' => $this->team->id]);

        $this->server = Server::factory()->create([
            'team_id' => $this->team->id,
            'status' => 'online',
        ]);

        $this->project = Project::factory()->create([
            'team_id' => $this->team->id,
            'server_id' => $this->server->id,
            'status' => 'running',
        ]);
    }

    public function test_git_update_status_is_cached(): void
    {
        Cache::flush();

        $mockGitService = Mockery::mock(GitService::class);
        $mockGitService->shouldReceive('checkForUpdates')
            ->once() // Should only be called once due to caching
            ->andReturn([
                'success' => true,
                'has_updates' => false,
                'commits_behind' => 0,
            ]);

        $this->app->instance(GitService::class, $mockGitService);

        // First load - should call GitService
        Livewire::actingAs($this->user)
            ->test(ProjectShow::class, ['project' => $this->project])
            ->assertSet('updateStatusLoaded', true);

        // Verify cache was set
        $cacheKey = "project.{$this->project->id}.git_update_status";
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_cached_status_is_reused(): void
    {
        Cache::flush();

        $callCount = 0;
        $mockGitService = Mockery::mock(GitService::class);
        $mockGitService->shouldReceive('checkForUpdates')
            ->andReturnUsing(function () use (&$callCount) {
                $callCount++;
                return [
                    'success' => true,
                    'has_updates' => false,
                    'commits_behind' => 0,
                ];
            });

        $this->app->instance(GitService::class, $mockGitService);

        // First load
        Livewire::actingAs($this->user)
            ->test(ProjectShow::class, ['project' => $this->project]);

        // Second load - should use cache
        Livewire::actingAs($this->user)
            ->test(ProjectShow::class, ['project' => $this->project]);

        // GitService should only be called once
        $this->assertEquals(1, $callCount);
    }

    public function test_refresh_clears_cache(): void
    {
        $cacheKey = "project.{$this->project->id}.git_update_status";

        // Pre-populate cache
        Cache::put($cacheKey, [
            'success' => true,
            'has_updates' => true,
            'commits_behind' => 5,
        ], now()->addMinutes(5));

        $this->assertTrue(Cache::has($cacheKey));

        $mockGitService = Mockery::mock(GitService::class);
        $mockGitService->shouldReceive('checkForUpdates')
            ->once()
            ->andReturn([
                'success' => true,
                'has_updates' => false,
                'commits_behind' => 0,
            ]);

        $this->app->instance(GitService::class, $mockGitService);

        // Call refreshUpdateStatus which should clear cache and fetch fresh
        Livewire::actingAs($this->user)
            ->test(ProjectShow::class, ['project' => $this->project])
            ->call('refreshUpdateStatus')
            ->assertSet('updateStatus.commits_behind', 0);
    }

    public function test_deployment_completed_clears_cache(): void
    {
        $cacheKey = "project.{$this->project->id}.git_update_status";

        // Pre-populate cache
        Cache::put($cacheKey, [
            'success' => true,
            'has_updates' => true,
            'commits_behind' => 5,
        ], now()->addMinutes(5));

        $this->assertTrue(Cache::has($cacheKey));

        $mockGitService = Mockery::mock(GitService::class);
        $mockGitService->shouldReceive('checkForUpdates')
            ->andReturn([
                'success' => true,
                'has_updates' => false,
                'commits_behind' => 0,
            ]);

        $this->app->instance(GitService::class, $mockGitService);

        // Dispatch deployment-completed event
        Livewire::actingAs($this->user)
            ->test(ProjectShow::class, ['project' => $this->project])
            ->dispatch('deployment-completed');

        // Cache should have been cleared (the event handler clears it before re-fetching)
        // New status should show 0 commits behind
        $cachedValue = Cache::get($cacheKey);
        $this->assertEquals(0, $cachedValue['commits_behind']);
    }

    public function test_cache_has_correct_ttl(): void
    {
        Cache::flush();

        $mockGitService = Mockery::mock(GitService::class);
        $mockGitService->shouldReceive('checkForUpdates')
            ->andReturn([
                'success' => true,
                'has_updates' => false,
            ]);

        $this->app->instance(GitService::class, $mockGitService);

        Livewire::actingAs($this->user)
            ->test(ProjectShow::class, ['project' => $this->project]);

        $cacheKey = "project.{$this->project->id}.git_update_status";

        // Cache should exist
        $this->assertTrue(Cache::has($cacheKey));

        // Simulate time passing (5 minutes + 1 second)
        $this->travel(6)->minutes();

        // Cache should be expired (5 minute TTL)
        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_failed_git_check_does_not_cache(): void
    {
        Cache::flush();

        $mockGitService = Mockery::mock(GitService::class);
        $mockGitService->shouldReceive('checkForUpdates')
            ->andReturn([
                'success' => false,
                'error' => 'Connection failed',
            ]);

        $this->app->instance(GitService::class, $mockGitService);

        Livewire::actingAs($this->user)
            ->test(ProjectShow::class, ['project' => $this->project])
            ->assertSet('updateStatusLoaded', false);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
