<?php

namespace App\Services\CustomScripts;

use App\Models\Deployment;
use App\Models\DeploymentScript;
use App\Models\Project;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Validator;

class DeploymentScriptService
{
    /** @var array<string, string> */
    protected array $availableVariables = [
        '{{PROJECT_NAME}}' => 'Project name',
        '{{PROJECT_SLUG}}' => 'Project slug',
        '{{PROJECT_PATH}}' => 'Full project path',
        '{{BRANCH}}' => 'Current branch',
        '{{COMMIT_HASH}}' => 'Current commit hash',
        '{{PREVIOUS_COMMIT}}' => 'Previous commit hash',
        '{{DEPLOYMENT_ID}}' => 'Current deployment ID',
        '{{TIMESTAMP}}' => 'Current timestamp',
        '{{ENV}}' => 'Environment (production/staging)',
        '{{DOCKER_IMAGE}}' => 'Docker image name',
        '{{DOMAIN}}' => 'Primary domain',
        '{{PHP_VERSION}}' => 'PHP version',
        '{{FRAMEWORK}}' => 'Framework type',
    ];

    /**
     * Create a custom deployment script
     */
    public function createScript(array $data): DeploymentScript
    {
        $validated = $this->validateScript($data);

        $script = DeploymentScript::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'] ?? 'deployment',
            'language' => $validated['language'] ?? 'bash',
            'content' => $validated['content'],
            'variables' => $validated['variables'] ?? [],
            'hooks' => $validated['hooks'] ?? [],
            'timeout' => $validated['timeout'] ?? 600,
            'retry_on_failure' => $validated['retry_on_failure'] ?? false,
            'max_retries' => $validated['max_retries'] ?? 3,
            'enabled' => $validated['enabled'] ?? true,
        ]);

        // Validate script syntax if possible
        $this->validateScriptSyntax($script);

        return $script;
    }

    /**
     * Execute deployment script for a project
     */
    public function executeScript(
        Project $project,
        DeploymentScript $script,
        Deployment $deployment,
        array $additionalVars = []
    ): array {
        $startTime = microtime(true);

        // Prepare script content with variables
        $scriptContent = $this->prepareScript($script, $project, $deployment, $additionalVars);

        // Create temporary script file
        $tempScriptPath = $this->createTempScriptFile($scriptContent, $script->language);

        try {
            // Execute pre-hooks
            if (! empty($script->hooks['pre'])) {
                $this->executeHooks($script->hooks['pre'], $project, 'pre');
            }

            // Execute main script
            $result = $this->runScript($tempScriptPath, $project, $script);

            // Execute post-hooks on success
            if ($result['success'] && ! empty($script->hooks['post'])) {
                $this->executeHooks($script->hooks['post'], $project, 'post');
            }

            // Execute error-hooks on failure
            if (! $result['success'] && ! empty($script->hooks['error'])) {
                $this->executeHooks($script->hooks['error'], $project, 'error');
            }

            // Handle retry logic
            if (! $result['success'] && $script->retry_on_failure) {
                $result = $this->retryScript($tempScriptPath, $project, $script);
            }

            $result['execution_time'] = round(microtime(true) - $startTime, 2);

            return $result;

        } finally {
            // Clean up temporary file
            if (file_exists($tempScriptPath)) {
                unlink($tempScriptPath);
            }
        }
    }

    /**
     * Prepare script content with variable substitution
     */
    protected function prepareScript(
        DeploymentScript $script,
        Project $project,
        Deployment $deployment,
        array $additionalVars = []
    ): string {
        $content = $script->content;

        // Default variable values
        $variables = [
            '{{PROJECT_NAME}}' => $project->name,
            '{{PROJECT_SLUG}}' => $project->slug,
            '{{PROJECT_PATH}}' => "/opt/devflow/projects/{$project->slug}",
            '{{BRANCH}}' => $project->branch,
            '{{COMMIT_HASH}}' => $deployment->commit_hash,
            '{{PREVIOUS_COMMIT}}' => $deployment->previous_commit_hash ?? 'HEAD~1',
            '{{DEPLOYMENT_ID}}' => $deployment->id,
            '{{TIMESTAMP}}' => now()->format('Y-m-d H:i:s'),
            '{{ENV}}' => app()->environment(),
            '{{DOCKER_IMAGE}}' => "{$project->slug}:latest",
            '{{DOMAIN}}' => $project->domains->first()->full_domain ?? 'localhost',
            '{{PHP_VERSION}}' => $project->php_version,
            '{{FRAMEWORK}}' => $project->framework,
        ];

        // Merge with custom variables from script configuration
        if (! empty($script->variables)) {
            $variables = array_merge($variables, $script->variables);
        }

        // Merge with additional runtime variables
        $variables = array_merge($variables, $additionalVars);

        // Replace variables in content
        foreach ($variables as $key => $value) {
            $content = str_replace($key, $value, $content);
        }

        // Add script header based on language
        $header = $this->getScriptHeader($script->language);
        if ($header && ! str_starts_with($content, $header)) {
            $content = $header."\n".$content;
        }

        return $content;
    }

    /**
     * Create temporary script file
     */
    protected function createTempScriptFile(string $content, string $language): string
    {
        $extension = $this->getScriptExtension($language);
        $tempPath = sys_get_temp_dir().'/devflow_script_'.uniqid().'.'.$extension;

        file_put_contents($tempPath, $content);
        chmod($tempPath, 0755);

        return $tempPath;
    }

    /**
     * Run the script
     */
    protected function runScript(string $scriptPath, Project $project, DeploymentScript $script): array
    {
        $projectPath = "/opt/devflow/projects/{$project->slug}";

        // Determine interpreter based on language
        $interpreter = $this->getInterpreter($script->language);

        // Build command
        $command = "{$interpreter} {$scriptPath}";

        // Execute script
        $result = Process::path($projectPath)
            ->timeout($script->timeout)
            ->env($this->getEnvironmentVariables($project))
            ->run($command);

        return [
            'success' => $result->successful(),
            'output' => $result->output(),
            'error' => $result->errorOutput(),
            'exit_code' => $result->exitCode(),
        ];
    }

    /**
     * Retry script execution
     */
    protected function retryScript(string $scriptPath, Project $project, DeploymentScript $script): array
    {
        $attempt = 1;
        $lastResult = null;

        while ($attempt <= $script->max_retries) {
            sleep(min($attempt * 5, 30)); // Exponential backoff

            $lastResult = $this->runScript($scriptPath, $project, $script);

            if ($lastResult['success']) {
                $lastResult['retries'] = $attempt;

                return $lastResult;
            }

            $attempt++;
        }

        $lastResult['retries'] = $script->max_retries;
        $lastResult['retry_failed'] = true;

        return $lastResult;
    }

    /**
     * Execute hook scripts
     */
    protected function executeHooks(array $hooks, Project $project, string $type): void
    {
        foreach ($hooks as $hook) {
            if (is_string($hook)) {
                // Simple command
                Process::path("/opt/devflow/projects/{$project->slug}")
                    ->run($hook);
            } elseif (is_array($hook) && isset($hook['script_id'])) {
                // Reference to another script
                $hookScript = DeploymentScript::find($hook['script_id']);
                if ($hookScript) {
                    $this->executeScript($project, $hookScript, new Deployment);
                }
            }
        }
    }

    /**
     * Validate script content
     */
    protected function validateScript(array $data): array
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|in:deployment,rollback,maintenance,backup,custom',
            'language' => 'nullable|in:bash,sh,python,php,node,ruby',
            'content' => 'required|string',
            'variables' => 'nullable|array',
            'hooks' => 'nullable|array',
            'timeout' => 'nullable|integer|min:10|max:3600',
            'retry_on_failure' => 'nullable|boolean',
            'max_retries' => 'nullable|integer|min:1|max:10',
            'enabled' => 'nullable|boolean',
        ]);

        return $validator->validate();
    }

    /**
     * Validate script syntax
     */
    protected function validateScriptSyntax(DeploymentScript $script): bool
    {
        $tempPath = $this->createTempScriptFile($script->content, $script->language);

        try {
            switch ($script->language) {
                case 'bash':
                case 'sh':
                    $result = Process::run("bash -n {$tempPath}");
                    break;

                case 'python':
                    $result = Process::run("python -m py_compile {$tempPath}");
                    break;

                case 'php':
                    $result = Process::run("php -l {$tempPath}");
                    break;

                case 'node':
                    $result = Process::run("node --check {$tempPath}");
                    break;

                default:
                    return true; // Skip validation for unsupported languages
            }

            if (! $result->successful()) {
                throw new \Exception('Script syntax error: '.$result->errorOutput());
            }

            return true;

        } finally {
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }

    /**
     * Get script header based on language
     */
    protected function getScriptHeader(string $language): ?string
    {
        return match ($language) {
            'bash' => '#!/bin/bash',
            'sh' => '#!/bin/sh',
            'python' => '#!/usr/bin/env python3',
            'php' => '#!/usr/bin/env php',
            'node' => '#!/usr/bin/env node',
            'ruby' => '#!/usr/bin/env ruby',
            default => null,
        };
    }

    /**
     * Get script file extension
     */
    protected function getScriptExtension(string $language): string
    {
        return match ($language) {
            'bash', 'sh' => 'sh',
            'python' => 'py',
            'php' => 'php',
            'node' => 'js',
            'ruby' => 'rb',
            default => 'txt',
        };
    }

    /**
     * Get interpreter command
     */
    protected function getInterpreter(string $language): string
    {
        return match ($language) {
            'bash' => 'bash',
            'sh' => 'sh',
            'python' => 'python3',
            'php' => 'php',
            'node' => 'node',
            'ruby' => 'ruby',
            default => 'bash',
        };
    }

    /**
     * Get environment variables for script execution
     */
    protected function getEnvironmentVariables(Project $project): array
    {
        $env = [
            'PATH' => getenv('PATH'),
            'HOME' => getenv('HOME'),
            'PROJECT_ENV' => app()->environment(),
            'PROJECT_ID' => $project->id,
            'PROJECT_SLUG' => $project->slug,
        ];

        // Add project-specific environment variables
        if ($project->env_variables) {
            $env = array_merge($env, $project->env_variables);
        }

        return $env;
    }

    /**
     * Generate script from template
     */
    public function generateFromTemplate(string $template, Project $project): DeploymentScript
    {
        $templates = $this->getAvailableTemplates();

        if (! isset($templates[$template])) {
            throw new \Exception("Template '{$template}' not found");
        }

        $templateData = $templates[$template];

        // Customize template for project
        $templateData['name'] = "{$project->name} - {$templateData['name']}";
        $templateData['content'] = $this->customizeTemplateForProject($templateData['content'], $project);

        return $this->createScript($templateData);
    }

    /**
     * Get available script templates
     */
    public function getAvailableTemplates(): array
    {
        return [
            'laravel_deployment' => [
                'name' => 'Laravel Deployment',
                'description' => 'Standard Laravel deployment with migrations and cache clearing',
                'type' => 'deployment',
                'language' => 'bash',
                'content' => $this->getLaravelDeploymentTemplate(),
                'timeout' => 600,
            ],
            'node_deployment' => [
                'name' => 'Node.js Deployment',
                'description' => 'Node.js application deployment with PM2',
                'type' => 'deployment',
                'language' => 'bash',
                'content' => $this->getNodeDeploymentTemplate(),
                'timeout' => 600,
            ],
            'database_backup' => [
                'name' => 'Database Backup',
                'description' => 'MySQL database backup script',
                'type' => 'backup',
                'language' => 'bash',
                'content' => $this->getDatabaseBackupTemplate(),
                'timeout' => 1800,
            ],
            'rollback' => [
                'name' => 'Emergency Rollback',
                'description' => 'Quick rollback to previous version',
                'type' => 'rollback',
                'language' => 'bash',
                'content' => $this->getRollbackTemplate(),
                'timeout' => 300,
            ],
            'health_check' => [
                'name' => 'Health Check',
                'description' => 'Comprehensive health check script',
                'type' => 'custom',
                'language' => 'bash',
                'content' => $this->getHealthCheckTemplate(),
                'timeout' => 60,
            ],
            'cache_warmer' => [
                'name' => 'Cache Warmer',
                'description' => 'Pre-warm application cache',
                'type' => 'custom',
                'language' => 'python',
                'content' => $this->getCacheWarmerTemplate(),
                'timeout' => 300,
            ],
        ];
    }

    /**
     * Get Laravel deployment template
     */
    protected function getLaravelDeploymentTemplate(): string
    {
        return <<<'BASH'
#!/bin/bash
set -e

echo "Starting Laravel deployment for {{PROJECT_NAME}}"
cd {{PROJECT_PATH}}

# Pull latest code
echo "Pulling latest code from {{BRANCH}} branch..."
git fetch origin {{BRANCH}}
git reset --hard origin/{{BRANCH}}

# Install dependencies
echo "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Install NPM dependencies and build assets
echo "Building frontend assets..."
npm ci
npm run build

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Clear and rebuild caches
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Clear old cache
php artisan cache:clear
php artisan queue:restart

# Set permissions
echo "Setting permissions..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Health check
echo "Performing health check..."
curl -f http://{{DOMAIN}}/health || exit 1

echo "Deployment completed successfully!"
BASH;
    }

    /**
     * Get Node.js deployment template
     */
    protected function getNodeDeploymentTemplate(): string
    {
        return <<<'BASH'
#!/bin/bash
set -e

echo "Starting Node.js deployment for {{PROJECT_NAME}}"
cd {{PROJECT_PATH}}

# Pull latest code
echo "Pulling latest code from {{BRANCH}} branch..."
git fetch origin {{BRANCH}}
git reset --hard origin/{{BRANCH}}

# Install dependencies
echo "Installing NPM dependencies..."
npm ci --production

# Build application
echo "Building application..."
npm run build

# Stop existing PM2 process
echo "Stopping existing process..."
pm2 stop {{PROJECT_SLUG}} || true

# Start application with PM2
echo "Starting application..."
pm2 start ecosystem.config.js --env production
pm2 save

# Health check
echo "Performing health check..."
sleep 5
curl -f http://{{DOMAIN}}/health || exit 1

echo "Deployment completed successfully!"
BASH;
    }

    /**
     * Get database backup template
     */
    protected function getDatabaseBackupTemplate(): string
    {
        return <<<'BASH'
#!/bin/bash
set -e

echo "Starting database backup for {{PROJECT_NAME}}"

# Set variables
DB_NAME="{{PROJECT_SLUG}}_db"
BACKUP_DIR="/opt/devflow/backups/{{PROJECT_SLUG}}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/backup_${TIMESTAMP}.sql.gz"

# Create backup directory if it doesn't exist
mkdir -p ${BACKUP_DIR}

# Perform backup
echo "Backing up database ${DB_NAME}..."
mysqldump \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    ${DB_NAME} | gzip > ${BACKUP_FILE}

# Verify backup
if [ -f ${BACKUP_FILE} ]; then
    SIZE=$(stat -c%s ${BACKUP_FILE})
    echo "Backup created successfully: ${BACKUP_FILE} (${SIZE} bytes)"
else
    echo "Backup failed!"
    exit 1
fi

# Clean old backups (keep last 30)
echo "Cleaning old backups..."
ls -t ${BACKUP_DIR}/backup_*.sql.gz | tail -n +31 | xargs -r rm

echo "Database backup completed!"
BASH;
    }

    /**
     * Get rollback template
     */
    protected function getRollbackTemplate(): string
    {
        return <<<'BASH'
#!/bin/bash
set -e

echo "Starting emergency rollback for {{PROJECT_NAME}}"
cd {{PROJECT_PATH}}

# Store current commit
CURRENT_COMMIT={{COMMIT_HASH}}

# Rollback to previous commit
echo "Rolling back to previous commit {{PREVIOUS_COMMIT}}..."
git reset --hard {{PREVIOUS_COMMIT}}

# Reinstall dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# Rebuild assets
npm ci && npm run build

# Clear caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache

# Restart services
php artisan queue:restart
supervisorctl restart {{PROJECT_SLUG}}-worker:*

# Health check
echo "Performing health check..."
if ! curl -f http://{{DOMAIN}}/health; then
    echo "Health check failed! Attempting to restore to ${CURRENT_COMMIT}..."
    git reset --hard ${CURRENT_COMMIT}
    exit 1
fi

echo "Rollback completed successfully!"
BASH;
    }

    /**
     * Get health check template
     */
    protected function getHealthCheckTemplate(): string
    {
        return <<<'BASH'
#!/bin/bash

echo "Running health check for {{PROJECT_NAME}}"

# Function to check endpoint
check_endpoint() {
    local url=$1
    local name=$2

    response=$(curl -s -o /dev/null -w "%{http_code}" $url)

    if [ $response -eq 200 ]; then
        echo "✓ $name is healthy (HTTP $response)"
        return 0
    else
        echo "✗ $name is unhealthy (HTTP $response)"
        return 1
    fi
}

# Check main application
check_endpoint "http://{{DOMAIN}}" "Main application"

# Check health endpoint
check_endpoint "http://{{DOMAIN}}/health" "Health endpoint"

# Check database connection
echo "Checking database connection..."
php {{PROJECT_PATH}}/artisan db:show > /dev/null 2>&1 && \
    echo "✓ Database connection is healthy" || \
    echo "✗ Database connection failed"

# Check Redis connection
echo "Checking Redis connection..."
redis-cli ping > /dev/null 2>&1 && \
    echo "✓ Redis connection is healthy" || \
    echo "✗ Redis connection failed"

# Check disk space
echo "Checking disk space..."
USAGE=$(df {{PROJECT_PATH}} | tail -1 | awk '{print $5}' | sed 's/%//')
if [ $USAGE -lt 90 ]; then
    echo "✓ Disk usage is healthy (${USAGE}%)"
else
    echo "✗ Disk usage is critical (${USAGE}%)"
fi

# Check memory usage
echo "Checking memory usage..."
MEMORY=$(free | grep Mem | awk '{print int($3/$2 * 100)}')
if [ $MEMORY -lt 90 ]; then
    echo "✓ Memory usage is healthy (${MEMORY}%)"
else
    echo "✗ Memory usage is critical (${MEMORY}%)"
fi

echo "Health check completed!"
BASH;
    }

    /**
     * Get cache warmer template (Python)
     */
    protected function getCacheWarmerTemplate(): string
    {
        return <<<'PYTHON'
#!/usr/bin/env python3

import requests
import time
from concurrent.futures import ThreadPoolExecutor, as_completed
from urllib.parse import urljoin

print(f"Starting cache warming for {{PROJECT_NAME}}")

base_url = "http://{{DOMAIN}}"
urls_to_warm = [
    "/",
    "/about",
    "/contact",
    "/products",
    "/api/health",
]

def warm_url(url):
    full_url = urljoin(base_url, url)
    try:
        start_time = time.time()
        response = requests.get(full_url, timeout=10)
        elapsed_time = time.time() - start_time

        return {
            "url": url,
            "status": response.status_code,
            "time": round(elapsed_time, 2),
            "success": response.status_code == 200
        }
    except Exception as e:
        return {
            "url": url,
            "status": 0,
            "time": 0,
            "success": False,
            "error": str(e)
        }

# Warm URLs concurrently
results = []
with ThreadPoolExecutor(max_workers=10) as executor:
    futures = [executor.submit(warm_url, url) for url in urls_to_warm]

    for future in as_completed(futures):
        result = future.result()
        results.append(result)

        status_symbol = "✓" if result["success"] else "✗"
        print(f"{status_symbol} {result['url']} - {result['status']} ({result['time']}s)")

# Summary
successful = sum(1 for r in results if r["success"])
total = len(results)
avg_time = sum(r["time"] for r in results) / total if total > 0 else 0

print(f"\nCache warming completed!")
print(f"Success rate: {successful}/{total} ({successful*100//total}%)")
print(f"Average response time: {avg_time:.2f}s")
PYTHON;
    }

    /**
     * Customize template for specific project
     */
    protected function customizeTemplateForProject(string $template, Project $project): string
    {
        // Add project-specific customizations
        if ($project->framework === 'laravel') {
            // Add Laravel-specific commands
            $template = str_replace(
                'php artisan migrate --force',
                "php artisan migrate --force\nphp artisan db:seed --class=ProductionSeeder --force",
                $template
            );
        }

        if ($project->uses_docker) {
            // Wrap commands in docker-compose exec
            $result = preg_replace(
                '/^(composer|php|npm|node)/m',
                'docker-compose exec -T app $1',
                $template
            );
            if ($result !== null) {
                $template = $result;
            }
        }

        return $template;
    }
}
