<?php

namespace App\Services\CICD;

use App\Models\Pipeline;
use App\Models\PipelineRun;
use App\Models\Project;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Yaml\Yaml;

class PipelineService
{
    /** @var array<int, string> */
    protected array $supportedProviders = ['github', 'gitlab', 'bitbucket', 'jenkins', 'custom'];

    /**
     * Create and configure CI/CD pipeline for a project
     */
    public function createPipeline(Project $project, array $config): Pipeline
    {
        $pipeline = Pipeline::create([
            'project_id' => $project->id,
            'name' => $config['name'] ?? "{$project->name} Pipeline",
            'provider' => $config['provider'] ?? 'github',
            'trigger_events' => $config['trigger_events'] ?? ['push', 'pull_request'],
            'branch_filters' => $config['branch_filters'] ?? ['main', 'develop'],
            'configuration' => $this->generatePipelineConfig($project, $config),
            'enabled' => $config['enabled'] ?? true,
        ]);

        // Set up webhook for automatic triggering
        $this->setupWebhook($pipeline);

        // Create pipeline file in repository
        $this->createPipelineFile($pipeline);

        return $pipeline;
    }

    /**
     * Generate pipeline configuration based on project type
     */
    protected function generatePipelineConfig(Project $project, array $config): array
    {
        $provider = $config['provider'] ?? 'github';

        return match ($provider) {
            'github' => $this->generateGitHubActionsConfig($project, $config),
            'gitlab' => $this->generateGitLabCIConfig($project, $config),
            'bitbucket' => $this->generateBitbucketPipelinesConfig($project, $config),
            'jenkins' => $this->generateJenkinsConfig($project, $config),
            default => $this->generateCustomConfig($project, $config),
        };
    }

    /**
     * Generate GitHub Actions workflow
     */
    protected function generateGitHubActionsConfig(Project $project, array $config): array
    {
        $workflow = [
            'name' => $config['name'] ?? 'DevFlow Pro CI/CD',
            'on' => $this->generateGitHubTriggers($config),
            'env' => [
                'PHP_VERSION' => $project->php_version,
                'NODE_VERSION' => '18',
            ],
            'jobs' => [
                'test' => $this->generateTestJob($project),
                'build' => $this->generateBuildJob($project),
                'deploy' => $this->generateDeployJob($project, $config),
            ],
        ];

        if ($config['enable_security_scan'] ?? true) {
            $workflow['jobs']['security'] = $this->generateSecurityJob($project);
        }

        if ($config['enable_quality_check'] ?? true) {
            $workflow['jobs']['quality'] = $this->generateQualityJob($project);
        }

        return $workflow;
    }

    /**
     * Generate GitHub triggers configuration
     */
    protected function generateGitHubTriggers(array $config): array
    {
        $triggers = [];

        if (in_array('push', $config['trigger_events'] ?? [])) {
            $triggers['push'] = [
                'branches' => $config['branch_filters'] ?? ['main', 'develop'],
                'paths-ignore' => ['**.md', 'docs/**'],
            ];
        }

        if (in_array('pull_request', $config['trigger_events'] ?? [])) {
            $triggers['pull_request'] = [
                'branches' => $config['branch_filters'] ?? ['main'],
            ];
        }

        if (in_array('schedule', $config['trigger_events'] ?? [])) {
            $triggers['schedule'] = [
                ['cron' => $config['schedule'] ?? '0 2 * * *'],
            ];
        }

        $triggers['workflow_dispatch'] = null; // Allow manual triggering

        return $triggers;
    }

    /**
     * Generate test job configuration
     */
    protected function generateTestJob(Project $project): array
    {
        $job = [
            'runs-on' => 'ubuntu-latest',
            'services' => [],
            'steps' => [
                [
                    'name' => 'Checkout code',
                    'uses' => 'actions/checkout@v3',
                ],
            ],
        ];

        // Add database service if needed
        if ($project->uses_database) {
            $job['services']['mysql'] = [
                'image' => 'mysql:8.0',
                'env' => [
                    'MYSQL_ROOT_PASSWORD' => 'password',
                    'MYSQL_DATABASE' => 'test_db',
                ],
                'ports' => ['3306:3306'],
                'options' => '--health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3',
            ];
        }

        // Add Redis service if needed
        if ($project->uses_redis) {
            $job['services']['redis'] = [
                'image' => 'redis:alpine',
                'ports' => ['6379:6379'],
                'options' => '--health-cmd="redis-cli ping" --health-interval=10s --health-timeout=5s --health-retries=3',
            ];
        }

        // PHP setup for Laravel/PHP projects
        if (in_array($project->framework, ['laravel', 'symfony'])) {
            $job['steps'] = array_merge($job['steps'], [
                [
                    'name' => 'Setup PHP',
                    'uses' => 'shivammathur/setup-php@v2',
                    'with' => [
                        'php-version' => $project->php_version,
                        'extensions' => 'mbstring, dom, fileinfo, mysql, redis',
                        'coverage' => 'xdebug',
                    ],
                ],
                [
                    'name' => 'Cache Composer dependencies',
                    'uses' => 'actions/cache@v3',
                    'with' => [
                        'path' => 'vendor',
                        'key' => '${{ runner.os }}-composer-${{ hashFiles(\'**/composer.lock\') }}',
                        'restore-keys' => '${{ runner.os }}-composer-',
                    ],
                ],
                [
                    'name' => 'Install dependencies',
                    'run' => 'composer install --prefer-dist --no-progress --no-suggest',
                ],
                [
                    'name' => 'Copy .env',
                    'run' => 'cp .env.example .env',
                ],
                [
                    'name' => 'Generate key',
                    'run' => 'php artisan key:generate',
                ],
                [
                    'name' => 'Run migrations',
                    'run' => 'php artisan migrate --force',
                ],
                [
                    'name' => 'Run tests',
                    'run' => 'php artisan test --parallel',
                ],
            ]);
        }

        // Node.js setup for frontend
        if ($project->has_frontend) {
            $job['steps'] = array_merge($job['steps'], [
                [
                    'name' => 'Setup Node.js',
                    'uses' => 'actions/setup-node@v3',
                    'with' => [
                        'node-version' => '18',
                        'cache' => 'npm',
                    ],
                ],
                [
                    'name' => 'Install NPM dependencies',
                    'run' => 'npm ci',
                ],
                [
                    'name' => 'Run frontend tests',
                    'run' => 'npm test',
                ],
            ]);
        }

        return $job;
    }

    /**
     * Generate build job configuration
     */
    protected function generateBuildJob(Project $project): array
    {
        return [
            'runs-on' => 'ubuntu-latest',
            'needs' => 'test',
            'steps' => [
                [
                    'name' => 'Checkout code',
                    'uses' => 'actions/checkout@v3',
                ],
                [
                    'name' => 'Set up Docker Buildx',
                    'uses' => 'docker/setup-buildx-action@v2',
                ],
                [
                    'name' => 'Log in to Docker Registry',
                    'uses' => 'docker/login-action@v2',
                    'with' => [
                        'registry' => '${{ secrets.DOCKER_REGISTRY }}',
                        'username' => '${{ secrets.DOCKER_USERNAME }}',
                        'password' => '${{ secrets.DOCKER_PASSWORD }}',
                    ],
                ],
                [
                    'name' => 'Build and push Docker image',
                    'uses' => 'docker/build-push-action@v4',
                    'with' => [
                        'context' => '.',
                        'push' => true,
                        'tags' => implode(',', [
                            '${{ secrets.DOCKER_REGISTRY }}/'.$project->slug.':latest',
                            '${{ secrets.DOCKER_REGISTRY }}/'.$project->slug.':${{ github.sha }}',
                        ]),
                        'cache-from' => 'type=gha',
                        'cache-to' => 'type=gha,mode=max',
                    ],
                ],
                [
                    'name' => 'Create deployment artifact',
                    'run' => implode("\n", [
                        'tar czf deploy.tar.gz \\',
                        '  --exclude=node_modules \\',
                        '  --exclude=vendor \\',
                        '  --exclude=.git \\',
                        '  .',
                    ]),
                ],
                [
                    'name' => 'Upload artifact',
                    'uses' => 'actions/upload-artifact@v3',
                    'with' => [
                        'name' => 'deployment-artifact',
                        'path' => 'deploy.tar.gz',
                    ],
                ],
            ],
        ];
    }

    /**
     * Generate deploy job configuration
     */
    protected function generateDeployJob(Project $project, array $config): array
    {
        $deploymentStrategy = $config['deployment_strategy'] ?? 'rolling';

        $job = [
            'runs-on' => 'ubuntu-latest',
            'needs' => ['test', 'build'],
            'if' => "github.ref == 'refs/heads/main' && github.event_name == 'push'",
            'environment' => [
                'name' => 'production',
                'url' => 'https://'.($project->domains->first()->full_domain ?? 'example.com'),
            ],
            'steps' => [
                [
                    'name' => 'Checkout code',
                    'uses' => 'actions/checkout@v3',
                ],
                [
                    'name' => 'Download artifact',
                    'uses' => 'actions/download-artifact@v3',
                    'with' => [
                        'name' => 'deployment-artifact',
                    ],
                ],
            ],
        ];

        // Add deployment steps based on strategy
        switch ($deploymentStrategy) {
            case 'kubernetes':
                $job['steps'] = array_merge($job['steps'], $this->generateKubernetesDeploySteps($project));
                break;

            case 'docker':
                $job['steps'] = array_merge($job['steps'], $this->generateDockerDeploySteps($project));
                break;

            case 'ssh':
                $job['steps'] = array_merge($job['steps'], $this->generateSSHDeploySteps($project));
                break;

            default:
                $job['steps'] = array_merge($job['steps'], $this->generateDevFlowDeploySteps($project));
        }

        // Add notification step
        $job['steps'][] = [
            'name' => 'Notify deployment status',
            'if' => 'always()',
            'uses' => 'DevFlowPro/notify-action@v1',
            'with' => [
                'status' => '${{ job.status }}',
                'webhook_url' => '${{ secrets.DEVFLOW_WEBHOOK_URL }}',
                'deployment_id' => '${{ github.run_id }}',
            ],
        ];

        return $job;
    }

    /**
     * Generate Kubernetes deployment steps
     */
    protected function generateKubernetesDeploySteps(Project $project): array
    {
        return [
            [
                'name' => 'Configure kubectl',
                'run' => implode("\n", [
                    'echo "${{ secrets.KUBE_CONFIG }}" | base64 -d > kubeconfig',
                    'export KUBECONFIG=$(pwd)/kubeconfig',
                ]),
            ],
            [
                'name' => 'Deploy to Kubernetes',
                'run' => implode("\n", [
                    'kubectl set image deployment/'.$project->slug.'-deployment \\',
                    '  app=${{ secrets.DOCKER_REGISTRY }}/'.$project->slug.':${{ github.sha }} \\',
                    '  -n '.$project->slug,
                    'kubectl rollout status deployment/'.$project->slug.'-deployment -n '.$project->slug,
                ]),
            ],
        ];
    }

    /**
     * Generate Docker deployment steps
     */
    protected function generateDockerDeploySteps(Project $project): array
    {
        return [
            [
                'name' => 'Deploy via SSH',
                'uses' => 'appleboy/ssh-action@v0.1.5',
                'with' => [
                    'host' => '${{ secrets.DEPLOY_HOST }}',
                    'username' => '${{ secrets.DEPLOY_USER }}',
                    'key' => '${{ secrets.DEPLOY_SSH_KEY }}',
                    'script' => implode("\n", [
                        'cd /opt/devflow/projects/'.$project->slug,
                        'docker-compose pull',
                        'docker-compose down',
                        'docker-compose up -d',
                        'docker-compose exec -T app php artisan migrate --force',
                    ]),
                ],
            ],
        ];
    }

    /**
     * Generate SSH deployment steps
     */
    protected function generateSSHDeploySteps(Project $project): array
    {
        return [
            [
                'name' => 'Deploy via SSH',
                'uses' => 'appleboy/ssh-action@v0.1.5',
                'with' => [
                    'host' => '${{ secrets.DEPLOY_HOST }}',
                    'username' => '${{ secrets.DEPLOY_USER }}',
                    'key' => '${{ secrets.DEPLOY_SSH_KEY }}',
                    'script' => implode("\n", [
                        'cd /var/www/'.$project->slug,
                        'git pull origin main',
                        'composer install --no-dev --optimize-autoloader',
                        'npm install && npm run build',
                        'php artisan config:cache',
                        'php artisan route:cache',
                        'php artisan view:cache',
                        'php artisan migrate --force',
                        'php artisan queue:restart',
                    ]),
                ],
            ],
        ];
    }

    /**
     * Generate DevFlow API deployment steps
     */
    protected function generateDevFlowDeploySteps(Project $project): array
    {
        return [
            [
                'name' => 'Trigger DevFlow deployment',
                'run' => implode("\n", [
                    'curl -X POST \\',
                    '  -H "Authorization: Bearer ${{ secrets.DEVFLOW_API_TOKEN }}" \\',
                    '  -H "Content-Type: application/json" \\',
                    '  -d \'{"commit_hash": "${{ github.sha }}", "branch": "main"}\' \\',
                    '  https://devflow.yourdomain.com/api/v1/projects/'.$project->id.'/deploy',
                ]),
            ],
        ];
    }

    /**
     * Generate security scanning job
     */
    protected function generateSecurityJob(Project $project): array
    {
        return [
            'runs-on' => 'ubuntu-latest',
            'steps' => [
                [
                    'name' => 'Checkout code',
                    'uses' => 'actions/checkout@v3',
                ],
                [
                    'name' => 'Run Trivy vulnerability scanner',
                    'uses' => 'aquasecurity/trivy-action@master',
                    'with' => [
                        'scan-type' => 'fs',
                        'scan-ref' => '.',
                        'format' => 'sarif',
                        'output' => 'trivy-results.sarif',
                    ],
                ],
                [
                    'name' => 'Upload Trivy results',
                    'uses' => 'github/codeql-action/upload-sarif@v2',
                    'with' => [
                        'sarif_file' => 'trivy-results.sarif',
                    ],
                ],
                [
                    'name' => 'Run OWASP Dependency Check',
                    'uses' => 'dependency-check/dependency-check-action@main',
                    'with' => [
                        'project' => $project->name,
                        'path' => '.',
                        'format' => 'HTML',
                    ],
                ],
            ],
        ];
    }

    /**
     * Generate code quality job
     */
    protected function generateQualityJob(Project $project): array
    {
        $steps = [
            [
                'name' => 'Checkout code',
                'uses' => 'actions/checkout@v3',
            ],
        ];

        if (in_array($project->framework, ['laravel', 'symfony'])) {
            $steps = array_merge($steps, [
                [
                    'name' => 'Setup PHP',
                    'uses' => 'shivammathur/setup-php@v2',
                    'with' => [
                        'php-version' => $project->php_version,
                        'tools' => 'phpcs, phpstan, phpmd',
                    ],
                ],
                [
                    'name' => 'Run PHP CodeSniffer',
                    'run' => 'phpcs --standard=PSR12 app/',
                ],
                [
                    'name' => 'Run PHPStan',
                    'run' => 'phpstan analyse --level=5 app/',
                ],
                [
                    'name' => 'Run PHP Mess Detector',
                    'run' => 'phpmd app/ text cleancode,codesize,controversial,design,naming,unusedcode',
                ],
            ]);
        }

        if ($project->has_frontend) {
            $steps = array_merge($steps, [
                [
                    'name' => 'Setup Node.js',
                    'uses' => 'actions/setup-node@v3',
                    'with' => [
                        'node-version' => '18',
                    ],
                ],
                [
                    'name' => 'Run ESLint',
                    'run' => 'npm run lint',
                ],
            ]);
        }

        $steps[] = [
            'name' => 'SonarCloud Scan',
            'uses' => 'SonarSource/sonarcloud-github-action@master',
            'env' => [
                'GITHUB_TOKEN' => '${{ secrets.GITHUB_TOKEN }}',
                'SONAR_TOKEN' => '${{ secrets.SONAR_TOKEN }}',
            ],
        ];

        return [
            'runs-on' => 'ubuntu-latest',
            'steps' => $steps,
        ];
    }

    /**
     * Generate GitLab CI configuration
     */
    protected function generateGitLabCIConfig(Project $project, array $config): array
    {
        return [
            'stages' => ['test', 'build', 'deploy'],
            'variables' => [
                'PHP_VERSION' => $project->php_version,
            ],
            'test' => [
                'stage' => 'test',
                'image' => 'php:'.$project->php_version,
                'services' => $this->getGitLabServices($project),
                'before_script' => [
                    'apt-get update && apt-get install -y git unzip',
                    'curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer',
                    'composer install',
                ],
                'script' => [
                    'cp .env.example .env',
                    'php artisan key:generate',
                    'php artisan migrate --force',
                    'php artisan test',
                ],
            ],
            'build' => [
                'stage' => 'build',
                'image' => 'docker:latest',
                'services' => ['docker:dind'],
                'script' => [
                    'docker build -t $CI_REGISTRY_IMAGE:$CI_COMMIT_SHA .',
                    'docker push $CI_REGISTRY_IMAGE:$CI_COMMIT_SHA',
                ],
            ],
            'deploy' => [
                'stage' => 'deploy',
                'script' => [
                    'curl -X POST -H "Authorization: Bearer $DEVFLOW_TOKEN" https://devflow.yourdomain.com/api/v1/deploy',
                ],
                'only' => ['main'],
            ],
        ];
    }

    /**
     * Get GitLab services configuration
     */
    protected function getGitLabServices(Project $project): array
    {
        $services = [];

        if ($project->uses_database) {
            $services[] = 'mysql:8.0';
        }

        if ($project->uses_redis) {
            $services[] = 'redis:alpine';
        }

        return $services;
    }

    /**
     * Execute pipeline run
     */
    public function executePipeline(Pipeline $pipeline, string $trigger = 'manual'): PipelineRun
    {
        $run = PipelineRun::create([
            'pipeline_id' => $pipeline->id,
            'status' => 'queued',
            'trigger' => $trigger,
            'commit_hash' => $this->getCurrentCommitHash($pipeline->project),
            'branch' => $pipeline->project->branch,
            'started_at' => now(),
        ]);

        // Execute pipeline based on provider
        switch ($pipeline->provider) {
            case 'github':
                $this->triggerGitHubActions($pipeline, $run);
                break;

            case 'gitlab':
                $this->triggerGitLabPipeline($pipeline, $run);
                break;

            case 'jenkins':
                $this->triggerJenkinsBuild($pipeline, $run);
                break;

            default:
                $this->executeCustomPipeline($pipeline, $run);
        }

        return $run;
    }

    /**
     * Trigger GitHub Actions workflow
     */
    protected function triggerGitHubActions(Pipeline $pipeline, PipelineRun $run): void
    {
        $project = $pipeline->project;
        $repoOwner = $this->extractGitHubOwner($project->repository_url);
        $repoName = $this->extractGitHubRepo($project->repository_url);

        $response = Http::withToken(config('services.github.token'))
            ->post("https://api.github.com/repos/{$repoOwner}/{$repoName}/actions/workflows/devflow.yml/dispatches", [
                'ref' => $project->branch,
                'inputs' => [
                    'pipeline_run_id' => $run->id,
                ],
            ]);

        if ($response->successful()) {
            $run->update(['status' => 'running']);
        } else {
            $run->update([
                'status' => 'failed',
                'error' => $response->body(),
            ]);
        }
    }

    /**
     * Execute custom pipeline
     */
    protected function executeCustomPipeline(Pipeline $pipeline, PipelineRun $run): void
    {
        $run->update(['status' => 'running']);

        try {
            $stages = $pipeline->configuration['stages'] ?? [];

            foreach ($stages as $stage) {
                $this->executeStage($pipeline, $run, $stage);
            }

            $run->update([
                'status' => 'success',
                'completed_at' => now(),
            ]);

        } catch (\Exception $e) {
            $run->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Execute pipeline stage
     */
    protected function executeStage(Pipeline $pipeline, PipelineRun $run, array $stage): void
    {
        $project = $pipeline->project;
        $projectPath = "/opt/devflow/projects/{$project->slug}";

        foreach ($stage['steps'] ?? [] as $step) {
            $command = $step['run'] ?? '';

            if (empty($command)) {
                continue;
            }

            $result = Process::path($projectPath)
                ->timeout(600)
                ->run($command);

            if (! $result->successful()) {
                throw new \Exception("Step failed: {$step['name']} - {$result->errorOutput()}");
            }

            // Log step output
            $run->logs()->create([
                'stage' => $stage['name'],
                'step' => $step['name'],
                'output' => $result->output(),
                'error' => $result->errorOutput(),
                'exit_code' => $result->exitCode(),
            ]);
        }
    }

    /**
     * Setup webhook for automatic pipeline triggering
     */
    protected function setupWebhook(Pipeline $pipeline): void
    {
        // Implementation would depend on the provider
        // This is a placeholder for the webhook setup logic
    }

    /**
     * Create pipeline file in repository
     */
    protected function createPipelineFile(Pipeline $pipeline): void
    {
        $project = $pipeline->project;
        $projectPath = "/opt/devflow/projects/{$project->slug}";

        switch ($pipeline->provider) {
            case 'github':
                $filePath = "{$projectPath}/.github/workflows/devflow.yml";
                $content = Yaml::dump($pipeline->configuration);
                break;

            case 'gitlab':
                $filePath = "{$projectPath}/.gitlab-ci.yml";
                $content = Yaml::dump($pipeline->configuration);
                break;

            case 'bitbucket':
                $filePath = "{$projectPath}/bitbucket-pipelines.yml";
                $content = Yaml::dump($pipeline->configuration);
                break;

            default:
                return;
        }

        // Create directory if it doesn't exist
        $directory = dirname($filePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Write pipeline file
        file_put_contents($filePath, $content);

        // Commit and push the file
        Process::path($projectPath)->run('git add .');
        Process::path($projectPath)->run('git commit -m "Add DevFlow CI/CD pipeline"');
        Process::path($projectPath)->run('git push origin '.$project->branch);
    }

    /**
     * Get current commit hash
     */
    protected function getCurrentCommitHash(Project $project): string
    {
        $projectPath = "/opt/devflow/projects/{$project->slug}";
        $result = Process::path($projectPath)->run('git rev-parse HEAD');

        return trim($result->output());
    }

    /**
     * Extract GitHub owner from repository URL
     */
    protected function extractGitHubOwner(string $url): string
    {
        if (preg_match('/github\.com[\/:]([^\/]+)\//', $url, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Extract GitHub repository name from URL
     */
    protected function extractGitHubRepo(string $url): string
    {
        if (preg_match('/github\.com[\/:]([^\/]+)\/([^\.]+)/', $url, $matches)) {
            return $matches[2];
        }

        return '';
    }
}
