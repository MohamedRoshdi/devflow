<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Events\DeploymentLogUpdated;
use App\Jobs\DeployProjectJob;
use App\Jobs\InstallDockerJob;
use App\Jobs\ProcessProjectSetupJob;
use App\Models\Deployment;
use App\Models\PipelineStage;
use App\Models\Project;
use App\Models\Server;
use App\Services\CICD\PipelineExecutionService;
use App\Services\DockerInstallationService;
use App\Services\DockerService;
use App\Services\GitService;
use App\Services\ProjectSetupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class JobsTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        Process::fake();
        Queue::fake();
        Event::fake();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ========================================
    // DeployProjectJob Tests
    // ========================================

    #[Test]
    public function deploy_project_job_can_be_instantiated(): void
    {
        $deployment = Deployment::factory()->create();

        $job = new DeployProjectJob($deployment);

        $this->assertInstanceOf(DeployProjectJob::class, $job);
        $this->assertSame($deployment->id, $job->deployment->id);
    }

    #[Test]
    public function deploy_project_job_implements_should_queue_interface(): void
    {
        $deployment = Deployment::factory()->create();

        $job = new DeployProjectJob($deployment);

        $this->assertInstanceOf(ShouldQueue::class, $job);
    }

    #[Test]
    public function deploy_project_job_uses_required_traits(): void
    {
        $usesDispatchable = method_exists(DeployProjectJob::class, 'dispatch');
        $usesQueueable = in_array(Queueable::class, class_uses(DeployProjectJob::class));
        $usesInteractsWithQueue = in_array(InteractsWithQueue::class, class_uses(DeployProjectJob::class));
        $usesSerializesModels = in_array(SerializesModels::class, class_uses(DeployProjectJob::class));

        $this->assertTrue($usesDispatchable, 'DeployProjectJob should use Dispatchable trait');
        $this->assertTrue($usesQueueable, 'DeployProjectJob should use Queueable trait');
        $this->assertTrue($usesInteractsWithQueue, 'DeployProjectJob should use InteractsWithQueue trait');
        $this->assertTrue($usesSerializesModels, 'DeployProjectJob should use SerializesModels trait');
    }

    #[Test]
    public function deploy_project_job_can_be_dispatched(): void
    {
        $deployment = Deployment::factory()->create();

        DeployProjectJob::dispatch($deployment);

        Queue::assertPushed(DeployProjectJob::class, function ($job) use ($deployment) {
            return $job->deployment->id === $deployment->id;
        });
    }

    #[Test]
    public function deploy_project_job_has_correct_timeout(): void
    {
        $deployment = Deployment::factory()->create();
        $job = new DeployProjectJob($deployment);

        $this->assertEquals(1200, $job->timeout);
    }

    #[Test]
    public function deploy_project_job_updates_deployment_status_to_running(): void
    {
        $project = Project::factory()->create();
        $server = Server::factory()->create();
        $project->server_id = $server->id;
        $project->save();

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        // Mock services
        $dockerService = Mockery::mock(DockerService::class);
        $dockerService->shouldReceive('buildContainer')->andReturn(['success' => true, 'output' => '']);
        $dockerService->shouldReceive('stopContainer')->andReturn(['success' => true]);
        $dockerService->shouldReceive('startContainer')->andReturn(['success' => true]);
        $dockerService->shouldReceive('usesDockerCompose')->andReturn(false);
        $dockerService->shouldReceive('getAppContainerName')->andReturn($project->slug);

        $gitService = Mockery::mock(GitService::class);
        $gitService->shouldReceive('getCurrentCommit')->andReturn([
            'hash' => 'abc123',
            'short_hash' => 'abc123',
            'author' => 'Test Author',
            'message' => 'Test commit',
            'timestamp' => now()->timestamp,
        ]);

        $this->app->instance(DockerService::class, $dockerService);
        $this->app->instance(GitService::class, $gitService);

        Process::fake([
            '*test -d*' => Process::result('not_exists'),
            '*git clone*' => Process::result('Cloned successfully'),
            '*docker exec*' => Process::result('Success'),
            'ssh*' => Process::result('Success'),
        ]);

        $job = new DeployProjectJob($deployment);
        $job->handle();

        $this->assertEquals('success', $deployment->fresh()->status);
    }

    #[Test]
    public function deploy_project_job_handles_pipeline_deployment_when_stages_exist(): void
    {
        $project = Project::factory()->create();
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        // Create a pipeline stage
        PipelineStage::factory()->create([
            'project_id' => $project->id,
        ]);

        $pipelineService = Mockery::mock(PipelineExecutionService::class);
        $pipelineRun = Mockery::mock(\App\Models\PipelineRun::class)->makePartial();
        $pipelineRun->id = 1;
        $pipelineRun->status = 'success';

        $pipelineService->shouldReceive('executePipeline')
            ->once()
            ->andReturn($pipelineRun);

        $this->app->instance(PipelineExecutionService::class, $pipelineService);

        $job = new DeployProjectJob($deployment);
        $job->handle();

        $this->assertEquals('success', $deployment->fresh()->status);
    }

    #[Test]
    public function deploy_project_job_broadcasts_log_events(): void
    {
        $project = Project::factory()->create();
        $server = Server::factory()->create();
        $project->server_id = $server->id;
        $project->save();

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        // Mock services
        $dockerService = Mockery::mock(DockerService::class);
        $dockerService->shouldReceive('buildContainer')->andReturn(['success' => true, 'output' => '']);
        $dockerService->shouldReceive('stopContainer')->andReturn(['success' => true]);
        $dockerService->shouldReceive('startContainer')->andReturn(['success' => true]);
        $dockerService->shouldReceive('usesDockerCompose')->andReturn(false);
        $dockerService->shouldReceive('getAppContainerName')->andReturn($project->slug);

        $gitService = Mockery::mock(GitService::class);
        $gitService->shouldReceive('getCurrentCommit')->andReturn(null);

        $this->app->instance(DockerService::class, $dockerService);
        $this->app->instance(GitService::class, $gitService);

        Process::fake([
            '*test -d*' => Process::result('not_exists'),
            '*git clone*' => Process::result('Cloned successfully'),
            '*docker exec*' => Process::result('Success'),
            'ssh*' => Process::result('Success'),
        ]);

        $job = new DeployProjectJob($deployment);
        $job->handle();

        Event::assertDispatched(DeploymentLogUpdated::class);
    }

    #[Test]
    public function deploy_project_job_handles_failure_gracefully(): void
    {
        $project = Project::factory()->create();
        $server = Server::factory()->create();
        $project->server_id = $server->id;
        $project->save();

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        // Mock DockerService to throw exception
        $dockerService = Mockery::mock(DockerService::class);
        $dockerService->shouldReceive('buildContainer')
            ->andThrow(new \Exception('Build failed'));

        $gitService = Mockery::mock(GitService::class);
        $gitService->shouldReceive('getCurrentCommit')->andReturn(null);

        $this->app->instance(DockerService::class, $dockerService);
        $this->app->instance(GitService::class, $gitService);

        Process::fake([
            '*test -d*' => Process::result('not_exists'),
            '*git clone*' => Process::result('Cloned successfully'),
            'ssh*' => Process::result('Success'),
        ]);

        $job = new DeployProjectJob($deployment);
        $job->handle();

        $freshDeployment = $deployment->fresh();
        $this->assertEquals('failed', $freshDeployment->status);
        $this->assertNotNull($freshDeployment->error_log);
    }

    #[Test]
    public function deploy_project_job_throws_exception_when_project_not_found(): void
    {
        // Create a project then delete it to simulate missing project
        $project = Project::factory()->create();
        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
        ]);

        // Delete the project to simulate it not being found
        $project->delete();

        $job = new DeployProjectJob($deployment);
        $job->handle();

        $freshDeployment = $deployment->fresh();
        $this->assertEquals('failed', $freshDeployment->status);
        $this->assertStringContainsString('Project not found', $freshDeployment->error_log);
    }

    #[Test]
    public function deploy_project_job_records_deployment_duration(): void
    {
        $project = Project::factory()->create();
        $server = Server::factory()->create();
        $project->server_id = $server->id;
        $project->save();

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        // Mock services
        $dockerService = Mockery::mock(DockerService::class);
        $dockerService->shouldReceive('buildContainer')->andReturn(['success' => true, 'output' => '']);
        $dockerService->shouldReceive('stopContainer')->andReturn(['success' => true]);
        $dockerService->shouldReceive('startContainer')->andReturn(['success' => true]);
        $dockerService->shouldReceive('usesDockerCompose')->andReturn(false);
        $dockerService->shouldReceive('getAppContainerName')->andReturn($project->slug);

        $gitService = Mockery::mock(GitService::class);
        $gitService->shouldReceive('getCurrentCommit')->andReturn(null);

        $this->app->instance(DockerService::class, $dockerService);
        $this->app->instance(GitService::class, $gitService);

        Process::fake([
            '*test -d*' => Process::result('not_exists'),
            '*git clone*' => Process::result('Cloned successfully'),
            '*docker exec*' => Process::result('Success'),
            'ssh*' => Process::result('Success'),
        ]);

        $job = new DeployProjectJob($deployment);
        $job->handle();

        $freshDeployment = $deployment->fresh();
        $this->assertNotNull($freshDeployment->duration_seconds);
        $this->assertGreaterThanOrEqual(0, $freshDeployment->duration_seconds);
    }

    #[Test]
    public function deploy_project_job_updates_project_status_on_success(): void
    {
        $project = Project::factory()->create(['status' => 'stopped']);
        $server = Server::factory()->create();
        $project->server_id = $server->id;
        $project->save();

        $deployment = Deployment::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        // Mock services
        $dockerService = Mockery::mock(DockerService::class);
        $dockerService->shouldReceive('buildContainer')->andReturn(['success' => true, 'output' => '']);
        $dockerService->shouldReceive('stopContainer')->andReturn(['success' => true]);
        $dockerService->shouldReceive('startContainer')->andReturn(['success' => true]);
        $dockerService->shouldReceive('usesDockerCompose')->andReturn(false);
        $dockerService->shouldReceive('getAppContainerName')->andReturn($project->slug);

        $gitService = Mockery::mock(GitService::class);
        $gitService->shouldReceive('getCurrentCommit')->andReturn(null);

        $this->app->instance(DockerService::class, $dockerService);
        $this->app->instance(GitService::class, $gitService);

        Process::fake([
            '*test -d*' => Process::result('not_exists'),
            '*git clone*' => Process::result('Cloned successfully'),
            '*docker exec*' => Process::result('Success'),
            'ssh*' => Process::result('Success'),
        ]);

        $job = new DeployProjectJob($deployment);
        $job->handle();

        $this->assertEquals('running', $project->fresh()->status);
        $this->assertNotNull($project->fresh()->last_deployed_at);
    }

    // ========================================
    // InstallDockerJob Tests
    // ========================================

    #[Test]
    public function install_docker_job_can_be_instantiated(): void
    {
        $server = Server::factory()->create();

        $job = new InstallDockerJob($server);

        $this->assertInstanceOf(InstallDockerJob::class, $job);
        $this->assertSame($server->id, $job->server->id);
    }

    #[Test]
    public function install_docker_job_implements_should_queue_interface(): void
    {
        $server = Server::factory()->create();

        $job = new InstallDockerJob($server);

        $this->assertInstanceOf(ShouldQueue::class, $job);
    }

    #[Test]
    public function install_docker_job_uses_required_traits(): void
    {
        $usesDispatchable = method_exists(InstallDockerJob::class, 'dispatch');
        $usesQueueable = in_array(Queueable::class, class_uses(InstallDockerJob::class));
        $usesInteractsWithQueue = in_array(InteractsWithQueue::class, class_uses(InstallDockerJob::class));
        $usesSerializesModels = in_array(SerializesModels::class, class_uses(InstallDockerJob::class));

        $this->assertTrue($usesDispatchable, 'InstallDockerJob should use Dispatchable trait');
        $this->assertTrue($usesQueueable, 'InstallDockerJob should use Queueable trait');
        $this->assertTrue($usesInteractsWithQueue, 'InstallDockerJob should use InteractsWithQueue trait');
        $this->assertTrue($usesSerializesModels, 'InstallDockerJob should use SerializesModels trait');
    }

    #[Test]
    public function install_docker_job_can_be_dispatched(): void
    {
        $server = Server::factory()->create();

        InstallDockerJob::dispatch($server);

        Queue::assertPushed(InstallDockerJob::class, function ($job) use ($server) {
            return $job->server->id === $server->id;
        });
    }

    #[Test]
    public function install_docker_job_has_correct_timeout_and_tries(): void
    {
        $server = Server::factory()->create();
        $job = new InstallDockerJob($server);

        $this->assertEquals(600, $job->timeout);
        $this->assertEquals(1, $job->tries);
    }

    #[Test]
    public function install_docker_job_executes_installation_successfully(): void
    {
        $server = Server::factory()->create();

        $installationService = Mockery::mock(DockerInstallationService::class);
        $installationService->shouldReceive('installDocker')
            ->once()
            ->with(Mockery::on(function ($arg) use ($server) {
                return $arg->id === $server->id;
            }))
            ->andReturn([
                'success' => true,
                'message' => 'Docker installed successfully',
                'version' => '24.0.7',
            ]);

        $job = new InstallDockerJob($server);
        $job->handle($installationService);

        $cacheKey = "docker_install_{$server->id}";
        $cachedData = Cache::get($cacheKey);

        $this->assertNotNull($cachedData);
        $this->assertEquals('completed', $cachedData['status']);
        $this->assertEquals('Docker installed successfully', $cachedData['message']);
        $this->assertEquals('24.0.7', $cachedData['version']);
        $this->assertEquals(100, $cachedData['progress']);
    }

    #[Test]
    public function install_docker_job_handles_installation_failure(): void
    {
        $server = Server::factory()->create();

        $installationService = Mockery::mock(DockerInstallationService::class);
        $installationService->shouldReceive('installDocker')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Installation failed',
                'error' => 'Network error',
            ]);

        $job = new InstallDockerJob($server);
        $job->handle($installationService);

        $cacheKey = "docker_install_{$server->id}";
        $cachedData = Cache::get($cacheKey);

        $this->assertNotNull($cachedData);
        $this->assertEquals('failed', $cachedData['status']);
        $this->assertEquals('Installation failed', $cachedData['message']);
        $this->assertEquals('Network error', $cachedData['error']);
        $this->assertEquals(0, $cachedData['progress']);
    }

    #[Test]
    public function install_docker_job_updates_cache_with_progress(): void
    {
        $server = Server::factory()->create();

        $installationService = Mockery::mock(DockerInstallationService::class);
        $installationService->shouldReceive('installDocker')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Docker installed successfully',
                'version' => '24.0.7',
            ]);

        $job = new InstallDockerJob($server);
        $job->handle($installationService);

        $cacheKey = "docker_install_{$server->id}";
        $cachedData = Cache::get($cacheKey);

        $this->assertArrayHasKey('status', $cachedData);
        $this->assertArrayHasKey('message', $cachedData);
        $this->assertArrayHasKey('progress', $cachedData);
        $this->assertArrayHasKey('completed_at', $cachedData);
    }

    #[Test]
    public function install_docker_job_handles_exception_during_installation(): void
    {
        $server = Server::factory()->create();

        $installationService = Mockery::mock(DockerInstallationService::class);
        $installationService->shouldReceive('installDocker')
            ->once()
            ->andThrow(new \Exception('Unexpected error occurred'));

        $job = new InstallDockerJob($server);

        try {
            $job->handle($installationService);
        } catch (\Exception $e) {
            // Expected exception
        }

        $cacheKey = "docker_install_{$server->id}";
        $cachedData = Cache::get($cacheKey);

        $this->assertNotNull($cachedData);
        $this->assertEquals('failed', $cachedData['status']);
        $this->assertStringContainsString('Unexpected error occurred', $cachedData['message']);
    }

    #[Test]
    public function install_docker_job_failed_method_updates_cache(): void
    {
        $server = Server::factory()->create();
        $job = new InstallDockerJob($server);

        $exception = new \Exception('Job failed unexpectedly');
        $job->failed($exception);

        $cacheKey = "docker_install_{$server->id}";
        $cachedData = Cache::get($cacheKey);

        $this->assertNotNull($cachedData);
        $this->assertEquals('failed', $cachedData['status']);
        $this->assertStringContainsString('Job failed unexpectedly', $cachedData['message']);
        $this->assertEquals(0, $cachedData['progress']);
    }

    #[Test]
    public function install_docker_job_logs_installation_start(): void
    {
        Log::spy();

        $server = Server::factory()->create();

        $installationService = Mockery::mock(DockerInstallationService::class);
        $installationService->shouldReceive('installDocker')
            ->andReturn(['success' => true, 'message' => 'Installed', 'version' => '24.0.7']);

        $job = new InstallDockerJob($server);
        $job->handle($installationService);

        Log::shouldHaveReceived('info')
            ->with('Docker installation job started', ['server_id' => $server->id])
            ->atLeast()
            ->once();

        $this->assertTrue(true); // PHPUnit requires at least one assertion
    }

    #[Test]
    public function install_docker_job_logs_installation_completion(): void
    {
        Log::spy();

        $server = Server::factory()->create();

        $installationService = Mockery::mock(DockerInstallationService::class);
        $installationService->shouldReceive('installDocker')
            ->andReturn(['success' => true, 'message' => 'Installed', 'version' => '24.0.7']);

        $job = new InstallDockerJob($server);
        $job->handle($installationService);

        Log::shouldHaveReceived('info')
            ->with('Docker installation completed', Mockery::on(function ($context) use ($server) {
                return $context['server_id'] === $server->id && $context['version'] === '24.0.7';
            }))
            ->atLeast()
            ->once();

        $this->assertTrue(true); // PHPUnit requires at least one assertion
    }

    #[Test]
    public function install_docker_job_logs_installation_failure(): void
    {
        Log::spy();

        $server = Server::factory()->create();

        $installationService = Mockery::mock(DockerInstallationService::class);
        $installationService->shouldReceive('installDocker')
            ->andReturn(['success' => false, 'message' => 'Installation failed']);

        $job = new InstallDockerJob($server);
        $job->handle($installationService);

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Docker installation failed', Mockery::on(function ($context) use ($server) {
                return $context['server_id'] === $server->id;
            }));

        $this->assertTrue(true); // PHPUnit requires at least one assertion
    }

    // ========================================
    // ProcessProjectSetupJob Tests
    // ========================================

    #[Test]
    public function process_project_setup_job_can_be_instantiated(): void
    {
        $project = Project::factory()->create();

        $job = new ProcessProjectSetupJob($project);

        $this->assertInstanceOf(ProcessProjectSetupJob::class, $job);
        $this->assertSame($project->id, $job->project->id);
    }

    #[Test]
    public function process_project_setup_job_implements_should_queue_interface(): void
    {
        $project = Project::factory()->create();

        $job = new ProcessProjectSetupJob($project);

        $this->assertInstanceOf(ShouldQueue::class, $job);
    }

    #[Test]
    public function process_project_setup_job_uses_required_traits(): void
    {
        $usesDispatchable = method_exists(ProcessProjectSetupJob::class, 'dispatch');
        $usesQueueable = in_array(Queueable::class, class_uses(ProcessProjectSetupJob::class));
        $usesInteractsWithQueue = in_array(InteractsWithQueue::class, class_uses(ProcessProjectSetupJob::class));
        $usesSerializesModels = in_array(SerializesModels::class, class_uses(ProcessProjectSetupJob::class));

        $this->assertTrue($usesDispatchable, 'ProcessProjectSetupJob should use Dispatchable trait');
        $this->assertTrue($usesQueueable, 'ProcessProjectSetupJob should use Queueable trait');
        $this->assertTrue($usesInteractsWithQueue, 'ProcessProjectSetupJob should use InteractsWithQueue trait');
        $this->assertTrue($usesSerializesModels, 'ProcessProjectSetupJob should use SerializesModels trait');
    }

    #[Test]
    public function process_project_setup_job_can_be_dispatched(): void
    {
        $project = Project::factory()->create();

        ProcessProjectSetupJob::dispatch($project);

        Queue::assertPushed(ProcessProjectSetupJob::class, function ($job) use ($project) {
            return $job->project->id === $project->id;
        });
    }

    #[Test]
    public function process_project_setup_job_has_correct_timeout_and_tries(): void
    {
        $project = Project::factory()->create();
        $job = new ProcessProjectSetupJob($project);

        $this->assertEquals(600, $job->timeout);
        $this->assertEquals(3, $job->tries);
    }

    #[Test]
    public function process_project_setup_job_executes_setup_successfully(): void
    {
        $project = Project::factory()->create(['setup_status' => 'pending']);

        $setupService = Mockery::mock(ProjectSetupService::class);
        $setupService->shouldReceive('executeSetup')
            ->once()
            ->with(Mockery::on(function ($arg) use ($project) {
                return $arg->id === $project->id;
            }));

        $job = new ProcessProjectSetupJob($project);
        $job->handle($setupService);

        // Verify the service was called
        $this->assertTrue(true);
    }

    #[Test]
    public function process_project_setup_job_updates_project_status_on_failure(): void
    {
        $project = Project::factory()->create(['setup_status' => 'pending']);

        $job = new ProcessProjectSetupJob($project);
        $exception = new \Exception('Setup failed');
        $job->failed($exception);

        $this->assertEquals('failed', $project->fresh()->setup_status);
    }

    #[Test]
    public function process_project_setup_job_logs_failure(): void
    {
        Log::spy();

        $project = Project::factory()->create(['setup_status' => 'pending']);

        $job = new ProcessProjectSetupJob($project);
        $exception = new \Exception('Setup failed');
        $job->failed($exception);

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Project setup job failed', Mockery::on(function ($context) use ($project) {
                return $context['project_id'] === $project->id
                    && $context['error'] === 'Setup failed';
            }));

        $this->assertTrue(true); // PHPUnit requires at least one assertion
    }

    #[Test]
    public function process_project_setup_job_has_tags_method(): void
    {
        $project = Project::factory()->create();
        $job = new ProcessProjectSetupJob($project);

        $tags = $job->tags();

        $this->assertIsArray($tags);
        $this->assertContains('project-setup', $tags);
        $this->assertContains('project:'.$project->id, $tags);
    }

    #[Test]
    public function process_project_setup_job_handles_service_exception(): void
    {
        Log::spy();

        $project = Project::factory()->create(['setup_status' => 'pending']);

        $setupService = Mockery::mock(ProjectSetupService::class);
        $setupService->shouldReceive('executeSetup')
            ->once()
            ->andThrow(new \Exception('Service error'));

        $job = new ProcessProjectSetupJob($project);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Service error');

        $job->handle($setupService);
    }

    #[Test]
    public function process_project_setup_job_can_be_dispatched_with_delay(): void
    {
        $project = Project::factory()->create();

        ProcessProjectSetupJob::dispatch($project)->delay(now()->addMinutes(5));

        Queue::assertPushed(ProcessProjectSetupJob::class);
    }

    #[Test]
    public function process_project_setup_job_can_be_dispatched_on_specific_queue(): void
    {
        $project = Project::factory()->create();

        ProcessProjectSetupJob::dispatch($project)->onQueue('project-setup');

        Queue::assertPushed(ProcessProjectSetupJob::class, function ($job) {
            return $job->queue === 'project-setup';
        });
    }

    #[Test]
    public function process_project_setup_job_tags_include_project_id(): void
    {
        $project = Project::factory()->create();
        $job = new ProcessProjectSetupJob($project);

        $tags = $job->tags();

        $this->assertCount(2, $tags);
        $this->assertEquals('project-setup', $tags[0]);
        $this->assertEquals('project:'.$project->id, $tags[1]);
    }

    #[Test]
    public function deploy_project_job_can_be_dispatched_conditionally(): void
    {
        $deployment = Deployment::factory()->create();

        DeployProjectJob::dispatchIf(true, $deployment);

        Queue::assertPushed(DeployProjectJob::class);
    }

    #[Test]
    public function install_docker_job_can_be_dispatched_conditionally(): void
    {
        $server = Server::factory()->create();

        InstallDockerJob::dispatchIf(false, $server);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function jobs_can_be_dispatched_synchronously_for_testing(): void
    {
        Queue::fake();

        $project = Project::factory()->create();

        ProcessProjectSetupJob::dispatchSync($project);

        // When dispatched sync with Queue::fake(), it still goes to fake queue
        Queue::assertPushed(ProcessProjectSetupJob::class);
    }
}
