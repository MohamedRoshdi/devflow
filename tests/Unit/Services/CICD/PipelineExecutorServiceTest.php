<?php

declare(strict_types=1);

namespace Tests\Unit\Services\CICD;

use App\Models\Pipeline;
use App\Models\PipelineRun;
use App\Models\Project;
use App\Services\CICD\PipelineExecutorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class PipelineExecutorServiceTest extends TestCase
{
    use RefreshDatabase;

    private PipelineExecutorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PipelineExecutorService();
    }

    public function test_extract_github_owner_from_https_url(): void
    {
        $url = 'https://github.com/devflow/test-project.git';

        $owner = $this->service->extractGitHubOwner($url);

        $this->assertEquals('devflow', $owner);
    }

    public function test_extract_github_owner_from_ssh_url(): void
    {
        $url = 'git@github.com:devflow/test-project.git';

        $owner = $this->service->extractGitHubOwner($url);

        $this->assertEquals('devflow', $owner);
    }

    public function test_extract_github_repo_from_https_url(): void
    {
        $url = 'https://github.com/devflow/test-project.git';

        $repo = $this->service->extractGitHubRepo($url);

        $this->assertEquals('test-project', $repo);
    }

    public function test_extract_github_repo_from_ssh_url(): void
    {
        $url = 'git@github.com:devflow/test-project.git';

        $repo = $this->service->extractGitHubRepo($url);

        $this->assertEquals('test-project', $repo);
    }

    public function test_extract_gitlab_project_id_from_https_url(): void
    {
        $url = 'https://gitlab.com/devflow/test-project.git';

        $projectId = $this->service->extractGitLabProjectId($url);

        $this->assertEquals(urlencode('devflow/test-project'), $projectId);
    }

    public function test_extract_gitlab_project_id_from_ssh_url(): void
    {
        $url = 'git@gitlab.com:devflow/test-project.git';

        $projectId = $this->service->extractGitLabProjectId($url);

        $this->assertEquals(urlencode('devflow/test-project'), $projectId);
    }

    public function test_extract_gitlab_project_id_handles_nested_groups(): void
    {
        $url = 'https://gitlab.com/devflow/group/subgroup/test-project.git';

        $projectId = $this->service->extractGitLabProjectId($url);

        $this->assertEquals(urlencode('devflow/group/subgroup/test-project'), $projectId);
    }

    public function test_execute_pipeline_creates_pipeline_run(): void
    {
        $project = Project::factory()->create([
            'framework' => 'laravel',
            'slug' => 'test-project',
        ]);

        $pipeline = Pipeline::factory()->create([
            'project_id' => $project->id,
            'provider' => 'custom',
            'configuration' => [
                'stages' => [],
            ],
        ]);

        Process::fake();

        $run = $this->service->executePipeline($pipeline, 'manual');

        $this->assertInstanceOf(PipelineRun::class, $run);
        $this->assertEquals($pipeline->id, $run->pipeline_id);
        $this->assertEquals('manual', $run->trigger);
    }

    public function test_trigger_github_actions_calls_github_api(): void
    {
        Http::fake([
            'api.github.com/*' => Http::response([], 204),
        ]);

        $project = Project::factory()->create([
            'repository_url' => 'https://github.com/devflow/test-project.git',
            'branch' => 'main',
        ]);

        $pipeline = Pipeline::factory()->create([
            'project_id' => $project->id,
            'provider' => 'github',
        ]);

        $run = PipelineRun::factory()->create([
            'pipeline_id' => $pipeline->id,
            'status' => 'queued',
        ]);

        config(['services.github.token' => 'test-token']);

        $this->service->triggerGitHubActions($pipeline, $run);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.github.com') &&
                   $request->method() === 'POST';
        });
    }

    public function test_trigger_gitlab_pipeline_calls_gitlab_api(): void
    {
        Http::fake([
            'gitlab.com/*' => Http::response([
                'id' => 123,
                'web_url' => 'https://gitlab.com/pipeline/123',
            ], 201),
        ]);

        $project = Project::factory()->create([
            'repository_url' => 'https://gitlab.com/devflow/test-project.git',
            'branch' => 'main',
        ]);

        $pipeline = Pipeline::factory()->create([
            'project_id' => $project->id,
            'provider' => 'gitlab',
        ]);

        $run = PipelineRun::factory()->create([
            'pipeline_id' => $pipeline->id,
            'status' => 'queued',
        ]);

        config(['services.gitlab.token' => 'test-token']);

        $this->service->triggerGitLabPipeline($pipeline, $run);

        $run->refresh();
        $this->assertEquals('running', $run->status);
        $this->assertEquals('123', $run->external_id);
    }

    public function test_trigger_gitlab_pipeline_handles_missing_token(): void
    {
        $project = Project::factory()->create([
            'repository_url' => 'https://gitlab.com/devflow/test-project.git',
            'branch' => 'main',
        ]);

        $pipeline = Pipeline::factory()->create([
            'project_id' => $project->id,
            'provider' => 'gitlab',
        ]);

        $run = PipelineRun::factory()->create([
            'pipeline_id' => $pipeline->id,
            'status' => 'queued',
        ]);

        config(['services.gitlab.token' => null]);

        $this->service->triggerGitLabPipeline($pipeline, $run);

        $run->refresh();
        $this->assertEquals('failed', $run->status);
        $this->assertStringContainsString('GitLab API token not configured', $run->error);
    }

    public function test_trigger_jenkins_build_calls_jenkins_api(): void
    {
        Http::fake([
            '*jenkins*' => Http::response([], 201, [
                'Location' => 'http://jenkins/queue/item/123',
            ]),
        ]);

        $project = Project::factory()->create([
            'slug' => 'test-project',
            'branch' => 'main',
        ]);

        $pipeline = Pipeline::factory()->create([
            'project_id' => $project->id,
            'provider' => 'jenkins',
            'configuration' => [
                'jenkins_job_name' => 'test-project',
            ],
        ]);

        $run = PipelineRun::factory()->create([
            'pipeline_id' => $pipeline->id,
            'status' => 'queued',
            'commit_hash' => 'abc123',
        ]);

        config([
            'services.jenkins.url' => 'http://jenkins',
            'services.jenkins.user' => 'admin',
            'services.jenkins.token' => 'token',
        ]);

        $this->service->triggerJenkinsBuild($pipeline, $run);

        $run->refresh();
        $this->assertEquals('running', $run->status);
    }

    public function test_trigger_jenkins_build_handles_missing_config(): void
    {
        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $pipeline = Pipeline::factory()->create([
            'project_id' => $project->id,
            'provider' => 'jenkins',
        ]);

        $run = PipelineRun::factory()->create([
            'pipeline_id' => $pipeline->id,
            'status' => 'queued',
        ]);

        config([
            'services.jenkins.url' => null,
            'services.jenkins.user' => null,
            'services.jenkins.token' => null,
        ]);

        $this->service->triggerJenkinsBuild($pipeline, $run);

        $run->refresh();
        $this->assertEquals('failed', $run->status);
        $this->assertStringContainsString('Jenkins configuration incomplete', $run->error);
    }

    public function test_execute_custom_pipeline_runs_stages(): void
    {
        Process::fake([
            '*' => Process::result(output: 'Success'),
        ]);

        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $pipeline = Pipeline::factory()->create([
            'project_id' => $project->id,
            'provider' => 'custom',
            'configuration' => [
                'stages' => [
                    [
                        'name' => 'test',
                        'steps' => [
                            [
                                'name' => 'Run tests',
                                'run' => 'php artisan test',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $run = PipelineRun::factory()->create([
            'pipeline_id' => $pipeline->id,
            'status' => 'queued',
        ]);

        $this->service->executeCustomPipeline($pipeline, $run);

        $run->refresh();
        $this->assertEquals('success', $run->status);
        $this->assertNotNull($run->completed_at);
    }

    public function test_execute_custom_pipeline_handles_failure(): void
    {
        Process::fake([
            '*' => Process::result(exitCode: 1, errorOutput: 'Test failed'),
        ]);

        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $pipeline = Pipeline::factory()->create([
            'project_id' => $project->id,
            'provider' => 'custom',
            'configuration' => [
                'stages' => [
                    [
                        'name' => 'test',
                        'steps' => [
                            [
                                'name' => 'Run tests',
                                'run' => 'php artisan test',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $run = PipelineRun::factory()->create([
            'pipeline_id' => $pipeline->id,
            'status' => 'queued',
        ]);

        $this->service->executeCustomPipeline($pipeline, $run);

        $run->refresh();
        $this->assertEquals('failed', $run->status);
        $this->assertNotNull($run->error);
    }

    public function test_cancel_github_workflow(): void
    {
        Http::fake([
            'api.github.com/*' => Http::response([], 202),
        ]);

        $project = Project::factory()->create([
            'repository_url' => 'https://github.com/devflow/test-project.git',
        ]);

        $pipeline = Pipeline::factory()->create([
            'project_id' => $project->id,
            'provider' => 'github',
        ]);

        $run = PipelineRun::factory()->create([
            'pipeline_id' => $pipeline->id,
            'status' => 'running',
            'external_id' => '12345',
        ]);

        config(['services.github.token' => 'test-token']);

        $result = $this->service->cancelPipeline($pipeline, $run);

        $this->assertTrue($result['success']);
        $run->refresh();
        $this->assertEquals('cancelled', $run->status);
    }

    public function test_retry_pipeline_creates_new_run(): void
    {
        Process::fake();

        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $pipeline = Pipeline::factory()->create([
            'project_id' => $project->id,
            'provider' => 'custom',
            'configuration' => ['stages' => []],
        ]);

        $failedRun = PipelineRun::factory()->create([
            'pipeline_id' => $pipeline->id,
            'status' => 'failed',
        ]);

        $newRun = $this->service->retryPipeline($pipeline, $failedRun);

        $this->assertNotEquals($failedRun->id, $newRun->id);
        $this->assertEquals('retry', $newRun->trigger);
    }

    public function test_get_current_commit_hash(): void
    {
        Process::fake([
            'git rev-parse HEAD' => Process::result(output: "abc123def456\n"),
        ]);

        $project = Project::factory()->create([
            'slug' => 'test-project',
        ]);

        $hash = $this->service->getCurrentCommitHash($project);

        $this->assertEquals('abc123def456', $hash);
    }
}
