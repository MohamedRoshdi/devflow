<?php

declare(strict_types=1);

namespace App\Services\CICD;

use App\Models\Project;

/**
 * Handles CI/CD pipeline configuration generation.
 *
 * Responsible for generating pipeline configurations for various providers:
 * GitHub Actions, GitLab CI, Bitbucket Pipelines, Jenkins, and custom.
 */
class PipelineBuilderService
{
    /**
     * Generate pipeline configuration based on provider
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function generatePipelineConfig(Project $project, array $config): array
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
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function generateGitHubActionsConfig(Project $project, array $config): array
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
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
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

        $triggers['workflow_dispatch'] = null;

        return $triggers;
    }

    /**
     * Generate test job configuration
     *
     * @return array<string, mixed>
     */
    public function generateTestJob(Project $project): array
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

        if ($project->uses_redis) {
            $job['services']['redis'] = [
                'image' => 'redis:alpine',
                'ports' => ['6379:6379'],
                'options' => '--health-cmd="redis-cli ping" --health-interval=10s --health-timeout=5s --health-retries=3',
            ];
        }

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
     *
     * @return array<string, mixed>
     */
    public function generateBuildJob(Project $project): array
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
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function generateDeployJob(Project $project, array $config): array
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

        $job['steps'] = match ($deploymentStrategy) {
            'kubernetes' => array_merge($job['steps'], $this->generateKubernetesDeploySteps($project)),
            'docker' => array_merge($job['steps'], $this->generateDockerDeploySteps($project)),
            'ssh' => array_merge($job['steps'], $this->generateSSHDeploySteps($project)),
            default => array_merge($job['steps'], $this->generateDevFlowDeploySteps($project)),
        };

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
     *
     * @return array<int, array<string, mixed>>
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
     *
     * @return array<int, array<string, mixed>>
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
     *
     * @return array<int, array<string, mixed>>
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
     *
     * @return array<int, array<string, mixed>>
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
     *
     * @return array<string, mixed>
     */
    public function generateSecurityJob(Project $project): array
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
     *
     * @return array<string, mixed>
     */
    public function generateQualityJob(Project $project): array
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
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function generateGitLabCIConfig(Project $project, array $config): array
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
     *
     * @return array<int, string>
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
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function generateBitbucketPipelinesConfig(Project $project, array $config): array
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
                                'trigger' => ($config['auto_deploy'] ?? false) ? 'automatic' : 'manual',
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

        if ($project->uses_database) {
            $pipeline['definitions']['services']['mysql'] = [
                'image' => 'mysql:8.0',
                'variables' => [
                    'MYSQL_DATABASE' => 'test_db',
                    'MYSQL_ROOT_PASSWORD' => 'password',
                ],
            ];

            foreach ($pipeline['pipelines']['branches']['main'] as &$step) {
                if (($step['step']['name'] ?? '') === 'Test') {
                    $step['step']['services'] = ['mysql'];
                }
            }
        }

        if ($project->uses_redis) {
            $pipeline['definitions']['services']['redis'] = [
                'image' => 'redis:alpine',
            ];
        }

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
     * Generate Jenkins Pipeline configuration
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function generateJenkinsConfig(Project $project, array $config): array
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
     *
     * @param  array<string, mixed>  $config
     */
    public function generateJenkinsfileContent(Project $project, array $config): string
    {
        $phpVersion = $project->php_version ?? '8.2';

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
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function generateCustomConfig(Project $project, array $config): array
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
}
