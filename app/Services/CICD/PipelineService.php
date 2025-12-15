<?php

declare(strict_types=1);

namespace App\Services\CICD;

use App\Models\Pipeline;
use App\Models\PipelineRun;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        $this->setupWebhook($project);

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
     * Generate Bitbucket Pipelines configuration
     */
    protected function generateBitbucketPipelinesConfig(Project $project, array $config): array
    {
        $phpVersion = $project->php_version ?? '8.2';

        $pipeline = [
            'image' => "php:{$phpVersion}",
            'definitions' => [
                'caches' => [
                    'composer' => 'vendor',
                    'npm' => 'node_modules',
                ],
                'services' => [],
            ],
            'pipelines' => [
                'default' => [
                    [
                        'step' => [
                            'name' => 'Install & Test',
                            'caches' => ['composer'],
                            'script' => [
                                'apt-get update && apt-get install -y git unzip',
                                'curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer',
                                'composer install --prefer-dist --no-progress',
                                'cp .env.example .env',
                                'php artisan key:generate',
                            ],
                        ],
                    ],
                ],
                'branches' => [
                    'main' => [
                        [
                            'step' => [
                                'name' => 'Test',
                                'caches' => ['composer'],
                                'script' => [
                                    'composer install --prefer-dist --no-progress',
                                    'cp .env.example .env',
                                    'php artisan key:generate',
                                    'php artisan test',
                                ],
                            ],
                        ],
                        [
                            'step' => [
                                'name' => 'Build Docker Image',
                                'services' => ['docker'],
                                'script' => [
                                    'docker build -t $BITBUCKET_REPO_SLUG:$BITBUCKET_COMMIT .',
                                    'docker tag $BITBUCKET_REPO_SLUG:$BITBUCKET_COMMIT $DOCKER_REGISTRY/$BITBUCKET_REPO_SLUG:$BITBUCKET_COMMIT',
                                    'echo $DOCKER_PASSWORD | docker login -u $DOCKER_USERNAME --password-stdin $DOCKER_REGISTRY',
                                    'docker push $DOCKER_REGISTRY/$BITBUCKET_REPO_SLUG:$BITBUCKET_COMMIT',
                                ],
                            ],
                        ],
                        [
                            'step' => [
                                'name' => 'Deploy to Production',
                                'deployment' => 'production',
                                'trigger' => $config['auto_deploy'] ?? false ? 'automatic' : 'manual',
                                'script' => [
                                    'curl -X POST -H "Authorization: Bearer $DEVFLOW_API_TOKEN" -H "Content-Type: application/json" -d \'{"commit_hash": "\'$BITBUCKET_COMMIT\'", "branch": "main"}\' https://devflow.yourdomain.com/api/v1/projects/'.$project->id.'/deploy',
                                ],
                            ],
                        ],
                    ],
                    'develop' => [
                        [
                            'step' => [
                                'name' => 'Test',
                                'caches' => ['composer'],
                                'script' => [
                                    'composer install --prefer-dist --no-progress',
                                    'cp .env.example .env',
                                    'php artisan key:generate',
                                    'php artisan test',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Add database service if needed
        if ($project->uses_database) {
            $pipeline['definitions']['services']['mysql'] = [
                'image' => 'mysql:8.0',
                'variables' => [
                    'MYSQL_DATABASE' => 'test_db',
                    'MYSQL_ROOT_PASSWORD' => 'password',
                ],
            ];

            // Add service to test steps
            foreach ($pipeline['pipelines']['branches']['main'] as &$step) {
                if (($step['step']['name'] ?? '') === 'Test') {
                    $step['step']['services'] = ['mysql'];
                }
            }
        }

        // Add Redis service if needed
        if ($project->uses_redis) {
            $pipeline['definitions']['services']['redis'] = [
                'image' => 'redis:alpine',
            ];
        }

        // Add frontend build if project has frontend
        if ($project->has_frontend) {
            array_splice($pipeline['pipelines']['branches']['main'], 1, 0, [
                [
                    'step' => [
                        'name' => 'Build Frontend',
                        'image' => 'node:18',
                        'caches' => ['npm'],
                        'script' => [
                            'npm ci',
                            'npm run build',
                        ],
                        'artifacts' => [
                            'public/build/**',
                        ],
                    ],
                ],
            ]);
        }

        return $pipeline;
    }

    /**
     * Generate Jenkins Pipeline (Jenkinsfile) configuration
     */
    protected function generateJenkinsConfig(Project $project, array $config): array
    {
        $phpVersion = $project->php_version ?? '8.2';

        return [
            'pipeline' => [
                'agent' => [
                    'docker' => [
                        'image' => "php:{$phpVersion}",
                    ],
                ],
                'environment' => [
                    'COMPOSER_HOME' => '${WORKSPACE}/.composer',
                    'DEVFLOW_PROJECT_ID' => $project->id,
                ],
                'stages' => [
                    [
                        'name' => 'Checkout',
                        'steps' => [
                            'checkout scm',
                        ],
                    ],
                    [
                        'name' => 'Install Dependencies',
                        'steps' => [
                            'sh "composer install --prefer-dist --no-progress"',
                            'sh "cp .env.example .env"',
                            'sh "php artisan key:generate"',
                        ],
                    ],
                    [
                        'name' => 'Test',
                        'steps' => [
                            'sh "php artisan test --parallel"',
                        ],
                    ],
                    [
                        'name' => 'Build',
                        'when' => [
                            'branch' => 'main',
                        ],
                        'steps' => [
                            'sh "docker build -t ${DOCKER_REGISTRY}/'.$project->slug.':${BUILD_NUMBER} ."',
                            'sh "docker push ${DOCKER_REGISTRY}/'.$project->slug.':${BUILD_NUMBER}"',
                        ],
                    ],
                    [
                        'name' => 'Deploy',
                        'when' => [
                            'branch' => 'main',
                        ],
                        'steps' => [
                            'sh """curl -X POST \\
                                -H "Authorization: Bearer ${DEVFLOW_API_TOKEN}" \\
                                -H "Content-Type: application/json" \\
                                -d \'{"commit_hash": "${GIT_COMMIT}", "branch": "main"}\' \\
                                https://devflow.yourdomain.com/api/v1/projects/'.$project->id.'/deploy"""',
                        ],
                    ],
                ],
                'post' => [
                    'always' => [
                        'cleanWs()',
                    ],
                    'success' => [
                        'echo "Pipeline completed successfully"',
                    ],
                    'failure' => [
                        'echo "Pipeline failed"',
                    ],
                ],
            ],
            'jenkinsfile_content' => $this->generateJenkinsfileContent($project, $config),
        ];
    }

    /**
     * Generate Jenkinsfile content as a string
     */
    protected function generateJenkinsfileContent(Project $project, array $config): string
    {
        $phpVersion = $project->php_version ?? '8.2';
        $hasDatabase = $project->uses_database ? 'true' : 'false';

        return <<<JENKINSFILE
pipeline {
    agent {
        docker {
            image 'php:{$phpVersion}'
            args '-v /var/run/docker.sock:/var/run/docker.sock'
        }
    }

    environment {
        COMPOSER_HOME = "\${WORKSPACE}/.composer"
        DEVFLOW_PROJECT_ID = '{$project->id}'
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Install Dependencies') {
            steps {
                sh 'apt-get update && apt-get install -y git unzip'
                sh 'curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer'
                sh 'composer install --prefer-dist --no-progress'
                sh 'cp .env.example .env'
                sh 'php artisan key:generate'
            }
        }

        stage('Test') {
            steps {
                sh 'php artisan test --parallel'
            }
        }

        stage('Build Docker Image') {
            when {
                branch 'main'
            }
            steps {
                script {
                    docker.build("{$project->slug}:\${BUILD_NUMBER}")
                }
            }
        }

        stage('Push to Registry') {
            when {
                branch 'main'
            }
            steps {
                script {
                    docker.withRegistry("\${DOCKER_REGISTRY}", 'docker-credentials') {
                        docker.image("{$project->slug}:\${BUILD_NUMBER}").push()
                        docker.image("{$project->slug}:\${BUILD_NUMBER}").push('latest')
                    }
                }
            }
        }

        stage('Deploy') {
            when {
                branch 'main'
            }
            steps {
                sh '''
                    curl -X POST \\
                        -H "Authorization: Bearer \${DEVFLOW_API_TOKEN}" \\
                        -H "Content-Type: application/json" \\
                        -d '{"commit_hash": "'\${GIT_COMMIT}'", "branch": "main"}' \\
                        https://devflow.yourdomain.com/api/v1/projects/{$project->id}/deploy
                '''
            }
        }
    }

    post {
        always {
            cleanWs()
        }
        success {
            echo 'Pipeline completed successfully!'
        }
        failure {
            echo 'Pipeline failed!'
        }
    }
}
JENKINSFILE;
    }

    /**
     * Generate custom pipeline configuration
     */
    protected function generateCustomConfig(Project $project, array $config): array
    {
        return [
            'name' => $config['name'] ?? "{$project->name} Custom Pipeline",
            'version' => '1.0',
            'stages' => [
                [
                    'name' => 'install',
                    'steps' => [
                        [
                            'name' => 'Install Composer Dependencies',
                            'run' => 'composer install --prefer-dist --no-progress',
                        ],
                        [
                            'name' => 'Setup Environment',
                            'run' => 'cp .env.example .env && php artisan key:generate',
                        ],
                    ],
                ],
                [
                    'name' => 'test',
                    'steps' => [
                        [
                            'name' => 'Run Tests',
                            'run' => 'php artisan test',
                        ],
                    ],
                ],
                [
                    'name' => 'build',
                    'steps' => [
                        [
                            'name' => 'Build Assets',
                            'run' => 'npm install && npm run build',
                            'condition' => $project->has_frontend,
                        ],
                        [
                            'name' => 'Cache Config',
                            'run' => 'php artisan config:cache && php artisan route:cache && php artisan view:cache',
                        ],
                    ],
                ],
                [
                    'name' => 'deploy',
                    'steps' => [
                        [
                            'name' => 'Run Migrations',
                            'run' => 'php artisan migrate --force',
                        ],
                        [
                            'name' => 'Clear Caches',
                            'run' => 'php artisan cache:clear',
                        ],
                        [
                            'name' => 'Restart Queue Workers',
                            'run' => 'php artisan queue:restart',
                        ],
                    ],
                ],
            ],
            'notifications' => [
                'on_success' => $config['notify_on_success'] ?? true,
                'on_failure' => $config['notify_on_failure'] ?? true,
                'channels' => $config['notification_channels'] ?? ['email'],
            ],
            'timeout' => $config['timeout'] ?? 600,
            'retry' => [
                'max_attempts' => $config['retry_attempts'] ?? 1,
                'delay' => $config['retry_delay'] ?? 30,
            ],
        ];
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
     * Trigger GitLab Pipeline
     */
    protected function triggerGitLabPipeline(Pipeline $pipeline, PipelineRun $run): void
    {
        $project = $pipeline->project;
        $gitlabProjectId = $this->extractGitLabProjectId($project->repository_url);
        $gitlabToken = config('services.gitlab.token');
        $gitlabUrl = config('services.gitlab.url', 'https://gitlab.com');

        if (empty($gitlabToken)) {
            $run->update([
                'status' => 'failed',
                'error' => 'GitLab API token not configured. Set GITLAB_TOKEN in .env',
            ]);
            Log::error('GitLab pipeline trigger failed: Missing API token', [
                'pipeline_id' => $pipeline->id,
                'project_id' => $project->id,
            ]);

            return;
        }

        if (empty($gitlabProjectId)) {
            $run->update([
                'status' => 'failed',
                'error' => 'Could not extract GitLab project ID from repository URL',
            ]);

            return;
        }

        try {
            $response = Http::withToken($gitlabToken)
                ->timeout(30)
                ->post("{$gitlabUrl}/api/v4/projects/{$gitlabProjectId}/pipeline", [
                    'ref' => $project->branch,
                    'variables' => [
                        [
                            'key' => 'DEVFLOW_PIPELINE_RUN_ID',
                            'value' => (string) $run->id,
                        ],
                        [
                            'key' => 'DEVFLOW_TRIGGERED',
                            'value' => 'true',
                        ],
                    ],
                ]);

            if ($response->successful()) {
                $pipelineData = $response->json();
                $run->update([
                    'status' => 'running',
                    'external_id' => $pipelineData['id'] ?? null,
                    'external_url' => $pipelineData['web_url'] ?? null,
                ]);

                Log::info('GitLab pipeline triggered successfully', [
                    'pipeline_id' => $pipeline->id,
                    'gitlab_pipeline_id' => $pipelineData['id'] ?? null,
                ]);
            } else {
                $error = $response->json('message') ?? $response->body();
                $run->update([
                    'status' => 'failed',
                    'error' => "GitLab API error: {$error}",
                ]);

                Log::error('GitLab pipeline trigger failed', [
                    'pipeline_id' => $pipeline->id,
                    'status_code' => $response->status(),
                    'error' => $error,
                ]);
            }
        } catch (\Exception $e) {
            $run->update([
                'status' => 'failed',
                'error' => "GitLab API exception: {$e->getMessage()}",
            ]);

            Log::error('GitLab pipeline trigger exception', [
                'pipeline_id' => $pipeline->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Trigger Jenkins Build
     */
    protected function triggerJenkinsBuild(Pipeline $pipeline, PipelineRun $run): void
    {
        $project = $pipeline->project;
        $jenkinsUrl = config('services.jenkins.url');
        $jenkinsUser = config('services.jenkins.user');
        $jenkinsToken = config('services.jenkins.token');
        $jobName = $pipeline->configuration['jenkins_job_name'] ?? $project->slug;

        if (empty($jenkinsUrl) || empty($jenkinsUser) || empty($jenkinsToken)) {
            $run->update([
                'status' => 'failed',
                'error' => 'Jenkins configuration incomplete. Set JENKINS_URL, JENKINS_USER, and JENKINS_TOKEN in .env',
            ]);
            Log::error('Jenkins build trigger failed: Missing configuration', [
                'pipeline_id' => $pipeline->id,
                'project_id' => $project->id,
            ]);

            return;
        }

        try {
            // Jenkins uses Basic Auth with user:token
            $response = Http::withBasicAuth($jenkinsUser, $jenkinsToken)
                ->timeout(30)
                ->post("{$jenkinsUrl}/job/{$jobName}/buildWithParameters", [
                    'BRANCH' => $project->branch,
                    'COMMIT_HASH' => $run->commit_hash,
                    'DEVFLOW_PIPELINE_RUN_ID' => $run->id,
                    'DEVFLOW_PROJECT_ID' => $project->id,
                ]);

            // Jenkins returns 201 for successful build trigger
            if ($response->status() === 201 || $response->successful()) {
                // Try to get the queue item location from headers
                $queueLocation = $response->header('Location');

                $run->update([
                    'status' => 'running',
                    'external_url' => $queueLocation ?? "{$jenkinsUrl}/job/{$jobName}",
                ]);

                Log::info('Jenkins build triggered successfully', [
                    'pipeline_id' => $pipeline->id,
                    'jenkins_job' => $jobName,
                    'queue_location' => $queueLocation,
                ]);

                // Optionally poll for the build number
                if ($queueLocation) {
                    $this->pollJenkinsQueueForBuildNumber($run, $queueLocation, $jenkinsUser, $jenkinsToken);
                }
            } else {
                $run->update([
                    'status' => 'failed',
                    'error' => "Jenkins API error: HTTP {$response->status()} - {$response->body()}",
                ]);

                Log::error('Jenkins build trigger failed', [
                    'pipeline_id' => $pipeline->id,
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            $run->update([
                'status' => 'failed',
                'error' => "Jenkins API exception: {$e->getMessage()}",
            ]);

            Log::error('Jenkins build trigger exception', [
                'pipeline_id' => $pipeline->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Poll Jenkins queue to get the actual build number
     */
    protected function pollJenkinsQueueForBuildNumber(PipelineRun $run, string $queueLocation, string $user, string $token): void
    {
        // Poll up to 10 times with 2 second intervals
        for ($i = 0; $i < 10; $i++) {
            sleep(2);

            try {
                $response = Http::withBasicAuth($user, $token)
                    ->timeout(10)
                    ->get("{$queueLocation}api/json");

                if ($response->successful()) {
                    $data = $response->json();

                    // Check if build has started (executable will be present)
                    if (isset($data['executable']['number'])) {
                        $buildNumber = $data['executable']['number'];
                        $buildUrl = $data['executable']['url'] ?? null;

                        $run->update([
                            'external_id' => (string) $buildNumber,
                            'external_url' => $buildUrl,
                        ]);

                        Log::info('Jenkins build number retrieved', [
                            'run_id' => $run->id,
                            'build_number' => $buildNumber,
                        ]);

                        return;
                    }

                    // If cancelled
                    if (isset($data['cancelled']) && $data['cancelled']) {
                        $run->update([
                            'status' => 'failed',
                            'error' => 'Jenkins build was cancelled in queue',
                        ]);

                        return;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to poll Jenkins queue', [
                    'run_id' => $run->id,
                    'attempt' => $i + 1,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Extract GitLab project ID from repository URL
     * Supports both numeric IDs and URL-encoded paths
     */
    protected function extractGitLabProjectId(string $url): string
    {
        // Handle SSH URLs: git@gitlab.com:group/project.git
        if (preg_match('/git@[^:]+:(.+?)(?:\.git)?$/', $url, $matches)) {
            return urlencode($matches[1]);
        }

        // Handle HTTPS URLs: https://gitlab.com/group/project.git
        if (preg_match('/gitlab\.[^\/]+\/(.+?)(?:\.git)?$/', $url, $matches)) {
            return urlencode($matches[1]);
        }

        // Handle self-hosted GitLab URLs
        if (preg_match('/\/\/[^\/]+\/(.+?)(?:\.git)?$/', $url, $matches)) {
            return urlencode($matches[1]);
        }

        return '';
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
     *
     * @return array{success: bool, webhook_id?: string, webhook_url?: string, error?: string}
     */
    public function setupWebhook(Project $project): array
    {
        try {
            // Generate webhook secret if not already set
            if (! $project->webhook_secret) {
                $project->webhook_secret = $project->generateWebhookSecret();
                $project->save();
            }

            // Determine provider from repository URL
            $provider = $this->detectGitProvider($project->repository_url);

            // Setup webhook based on provider
            $result = match ($provider) {
                'github' => $this->setupGitHubWebhook($project),
                'gitlab' => $this->setupGitLabWebhook($project),
                'bitbucket' => $this->setupBitbucketWebhook($project),
                default => ['success' => false, 'error' => "Unsupported provider: {$provider}"],
            };

            if ($result['success']) {
                // Update project with webhook details - only set provider if it's a valid enum value
                $project->webhook_enabled = true;
                $project->webhook_provider = in_array($provider, ['github', 'gitlab', 'bitbucket', 'custom'], true) ? $provider : 'custom';
                $project->webhook_id = $result['webhook_id'] ?? null;
                $project->webhook_url = $result['webhook_url'] ?? null;
                $project->save();

                Log::info('Webhook created successfully', [
                    'project_id' => $project->id,
                    'provider' => $provider,
                    'webhook_id' => $result['webhook_id'] ?? null,
                ]);
            } else {
                Log::error('Webhook creation failed', [
                    'project_id' => $project->id,
                    'provider' => $provider,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Webhook setup exception', [
                'project_id' => $project->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete webhook from Git provider
     */
    public function deleteWebhook(Project $project): bool
    {
        try {
            if (! $project->webhook_enabled || ! $project->webhook_id) {
                Log::warning('No webhook to delete', ['project_id' => $project->id]);

                return true;
            }

            $result = match ($project->webhook_provider) {
                'github' => $this->deleteGitHubWebhook($project),
                'gitlab' => $this->deleteGitLabWebhook($project),
                'bitbucket' => $this->deleteBitbucketWebhook($project),
                default => false,
            };

            if ($result) {
                // Clear webhook data from project
                $project->webhook_enabled = false;
                $project->webhook_id = null;
                $project->webhook_url = null;
                $project->save();

                Log::info('Webhook deleted successfully', [
                    'project_id' => $project->id,
                    'provider' => $project->webhook_provider,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Webhook deletion exception', [
                'project_id' => $project->id,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Verify webhook signature from incoming request
     */
    public function verifyWebhookSignature(\Illuminate\Http\Request $request, Project $project): bool
    {
        try {
            if (! $project->webhook_secret || ! $project->webhook_enabled) {
                Log::warning('Webhook verification failed: No webhook configured', [
                    'project_id' => $project->id,
                ]);

                return false;
            }

            $provider = $project->webhook_provider ?? $this->detectGitProvider($project->repository_url);

            $isValid = match ($provider) {
                'github' => $this->verifyGitHubSignature($request, $project->webhook_secret),
                'gitlab' => $this->verifyGitLabSignature($request, $project->webhook_secret),
                'bitbucket' => $this->verifyBitbucketSignature($request, $project->webhook_secret),
                default => false,
            };

            if (! $isValid) {
                Log::warning('Webhook signature verification failed', [
                    'project_id' => $project->id,
                    'provider' => $provider,
                    'ip' => $request->ip(),
                ]);
            }

            return $isValid;
        } catch (\Exception $e) {
            Log::error('Webhook verification exception', [
                'project_id' => $project->id,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Detect Git provider from repository URL
     */
    protected function detectGitProvider(string $url): string
    {
        if (str_contains($url, 'github.com')) {
            return 'github';
        }

        if (str_contains($url, 'gitlab.com') || str_contains($url, 'gitlab')) {
            return 'gitlab';
        }

        if (str_contains($url, 'bitbucket.org')) {
            return 'bitbucket';
        }

        return 'custom';
    }

    /**
     * Setup GitHub webhook
     *
     * @return array{success: bool, webhook_id?: string, webhook_url?: string, error?: string}
     */
    protected function setupGitHubWebhook(Project $project): array
    {
        $token = config('services.github.token');
        if (! $token) {
            return ['success' => false, 'error' => 'GitHub token not configured'];
        }

        $owner = $this->extractGitHubOwner($project->repository_url);
        $repo = $this->extractGitHubRepo($project->repository_url);

        if (! $owner || ! $repo) {
            return ['success' => false, 'error' => 'Invalid GitHub repository URL'];
        }

        $webhookUrl = url("/api/webhooks/github/{$project->id}");

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->post("https://api.github.com/repos/{$owner}/{$repo}/hooks", [
                    'name' => 'web',
                    'active' => true,
                    'events' => ['push', 'pull_request', 'release'],
                    'config' => [
                        'url' => $webhookUrl,
                        'content_type' => 'json',
                        'secret' => $project->webhook_secret,
                        'insecure_ssl' => '0',
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'webhook_id' => (string) $data['id'],
                    'webhook_url' => $webhookUrl,
                ];
            }

            return [
                'success' => false,
                'error' => "GitHub API error: {$response->status()} - " . $response->json('message', $response->body()),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => "GitHub API exception: {$e->getMessage()}"];
        }
    }

    /**
     * Setup GitLab webhook
     *
     * @return array{success: bool, webhook_id?: string, webhook_url?: string, error?: string}
     */
    protected function setupGitLabWebhook(Project $project): array
    {
        $token = config('services.gitlab.token');
        $gitlabUrl = config('services.gitlab.url', 'https://gitlab.com');

        if (! $token) {
            return ['success' => false, 'error' => 'GitLab token not configured'];
        }

        $projectId = $this->extractGitLabProjectId($project->repository_url);
        if (! $projectId) {
            return ['success' => false, 'error' => 'Invalid GitLab repository URL'];
        }

        $webhookUrl = url("/api/webhooks/gitlab/{$project->id}");

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->post("{$gitlabUrl}/api/v4/projects/{$projectId}/hooks", [
                    'url' => $webhookUrl,
                    'token' => $project->webhook_secret,
                    'push_events' => true,
                    'merge_requests_events' => true,
                    'tag_push_events' => true,
                    'releases_events' => true,
                    'enable_ssl_verification' => true,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'webhook_id' => (string) $data['id'],
                    'webhook_url' => $webhookUrl,
                ];
            }

            return [
                'success' => false,
                'error' => "GitLab API error: {$response->status()} - " . $response->json('message', $response->body()),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => "GitLab API exception: {$e->getMessage()}"];
        }
    }

    /**
     * Setup Bitbucket webhook
     *
     * @return array{success: bool, webhook_id?: string, webhook_url?: string, error?: string}
     */
    protected function setupBitbucketWebhook(Project $project): array
    {
        $username = config('services.bitbucket.username');
        $appPassword = config('services.bitbucket.app_password');
        $bitbucketUrl = config('services.bitbucket.url', 'https://api.bitbucket.org/2.0');

        if (! $username || ! $appPassword) {
            return ['success' => false, 'error' => 'Bitbucket credentials not configured'];
        }

        [$workspace, $repoSlug] = $this->extractBitbucketInfo($project->repository_url);
        if (! $workspace || ! $repoSlug) {
            return ['success' => false, 'error' => 'Invalid Bitbucket repository URL'];
        }

        $webhookUrl = url("/api/webhooks/bitbucket/{$project->id}");

        try {
            $response = Http::withBasicAuth($username, $appPassword)
                ->timeout(30)
                ->post("{$bitbucketUrl}/repositories/{$workspace}/{$repoSlug}/hooks", [
                    'description' => "DevFlow Pro - {$project->name}",
                    'url' => $webhookUrl,
                    'active' => true,
                    'events' => [
                        'repo:push',
                        'pullrequest:created',
                        'pullrequest:updated',
                        'pullrequest:fulfilled',
                    ],
                    // Bitbucket doesn't support secrets in webhooks, we'll use IP whitelisting
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'webhook_id' => $data['uuid'] ?? (string) ($data['id'] ?? ''),
                    'webhook_url' => $webhookUrl,
                ];
            }

            return [
                'success' => false,
                'error' => "Bitbucket API error: {$response->status()} - " . $response->json('error.message', $response->body()),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => "Bitbucket API exception: {$e->getMessage()}"];
        }
    }

    /**
     * Delete GitHub webhook
     */
    protected function deleteGitHubWebhook(Project $project): bool
    {
        $token = config('services.github.token');
        if (! $token) {
            return false;
        }

        $owner = $this->extractGitHubOwner($project->repository_url);
        $repo = $this->extractGitHubRepo($project->repository_url);

        if (! $owner || ! $repo || ! $project->webhook_id) {
            return false;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->delete("https://api.github.com/repos/{$owner}/{$repo}/hooks/{$project->webhook_id}");

            return $response->successful() || $response->status() === 404; // 404 means already deleted
        } catch (\Exception $e) {
            Log::error('GitHub webhook deletion failed', [
                'project_id' => $project->id,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete GitLab webhook
     */
    protected function deleteGitLabWebhook(Project $project): bool
    {
        $token = config('services.gitlab.token');
        $gitlabUrl = config('services.gitlab.url', 'https://gitlab.com');

        if (! $token) {
            return false;
        }

        $projectId = $this->extractGitLabProjectId($project->repository_url);
        if (! $projectId || ! $project->webhook_id) {
            return false;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->delete("{$gitlabUrl}/api/v4/projects/{$projectId}/hooks/{$project->webhook_id}");

            return $response->successful() || $response->status() === 404;
        } catch (\Exception $e) {
            Log::error('GitLab webhook deletion failed', [
                'project_id' => $project->id,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete Bitbucket webhook
     */
    protected function deleteBitbucketWebhook(Project $project): bool
    {
        $username = config('services.bitbucket.username');
        $appPassword = config('services.bitbucket.app_password');
        $bitbucketUrl = config('services.bitbucket.url', 'https://api.bitbucket.org/2.0');

        if (! $username || ! $appPassword) {
            return false;
        }

        [$workspace, $repoSlug] = $this->extractBitbucketInfo($project->repository_url);
        if (! $workspace || ! $repoSlug || ! $project->webhook_id) {
            return false;
        }

        try {
            $response = Http::withBasicAuth($username, $appPassword)
                ->timeout(30)
                ->delete("{$bitbucketUrl}/repositories/{$workspace}/{$repoSlug}/hooks/{$project->webhook_id}");

            return $response->successful() || $response->status() === 404;
        } catch (\Exception $e) {
            Log::error('Bitbucket webhook deletion failed', [
                'project_id' => $project->id,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Verify GitHub webhook signature
     */
    protected function verifyGitHubSignature(\Illuminate\Http\Request $request, string $secret): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        if (! $signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify GitLab webhook signature
     */
    protected function verifyGitLabSignature(\Illuminate\Http\Request $request, string $secret): bool
    {
        $token = $request->header('X-Gitlab-Token');

        return $token === $secret;
    }

    /**
     * Verify Bitbucket webhook signature
     * Note: Bitbucket doesn't support webhook secrets, so we verify using IP whitelisting
     */
    protected function verifyBitbucketSignature(\Illuminate\Http\Request $request, string $secret): bool
    {
        // Bitbucket IP ranges (as of 2025)
        $bitbucketIpRanges = [
            '104.192.136.0/21', // Bitbucket Cloud
            '185.166.140.0/22',
            '18.205.93.0/25',
            '18.234.32.128/25',
            '13.52.5.0/25',
        ];

        $requestIp = $request->ip();
        if (! $requestIp) {
            return false;
        }

        foreach ($bitbucketIpRanges as $range) {
            if ($this->ipInRange($requestIp, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP address is within CIDR range
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        if (! str_contains($range, '/')) {
            return $ip === $range;
        }

        [$subnet, $bits] = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - (int) $bits);
        $subnet &= $mask;

        return ($ip & $mask) === $subnet;
    }

    /**
     * Extract Bitbucket workspace and repository slug from URL
     *
     * @return array{0: string, 1: string}
     */
    protected function extractBitbucketInfo(string $url): array
    {
        // Handle SSH URLs: git@bitbucket.org:workspace/repo.git
        if (preg_match('/bitbucket\.org:([^\/]+)\/([^\.]+)/', $url, $matches)) {
            return [$matches[1], $matches[2]];
        }

        // Handle HTTPS URLs: https://bitbucket.org/workspace/repo.git
        if (preg_match('/bitbucket\.org\/([^\/]+)\/([^\.\/]+)/', $url, $matches)) {
            return [$matches[1], $matches[2]];
        }

        return ['', ''];
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
