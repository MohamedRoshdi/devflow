<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\DTOs\RepositoryAnalysis;
use App\Jobs\ProcessProjectSetupJob;
use App\Livewire\Concerns\HasWizardSteps;
use App\Models\Domain;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\Server;
use App\Services\ProjectSetupService;
use App\Services\RepositoryAnalyzerService;
use App\Services\ServerConnectivityService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;

class ProjectCreate extends Component
{
    use HasWizardSteps;

    public int $totalSteps = 4;

    public bool $showProgressModal = false;

    public ?int $createdProjectId = null;

    // Step 1: Basic Info
    public string $name = '';

    public string $slug = '';

    public string $server_id = '';

    public string $repository_url = '';

    public string $branch = 'main';

    // Step 2: Framework & Build
    public string $framework = '';

    public string $deployment_method = 'docker';

    public string $php_version = '8.3';

    public string $node_version = '20';

    public string $root_directory = '/';

    public string $deployPath = '/var/www/';

    public bool $useOctane = false;

    public string $octaneServer = 'frankenphp';

    public string $build_command = '';

    public string $start_command = '';

    public bool $auto_deploy = false;

    public ?float $latitude = null;

    public ?float $longitude = null;

    public ?string $notes = null;

    // Repository Analysis
    /** @var array<string, mixed>|null */
    public ?array $analysisResult = null;

    public bool $analyzing = false;

    public ?string $analysisError = null;

    // Step 3: Setup Options
    public bool $existingProject = false;

    public bool $enableSsl = true;

    public bool $enableWebhooks = true;

    public bool $enableHealthChecks = true;

    public bool $enableBackups = true;

    public bool $enableNotifications = true;

    public bool $enableAutoDeploy = false;

    // Template
    public ?int $selectedTemplateId = null;

    /** @var array<int, string> */
    public array $install_commands = [];

    /** @var array<int, string> */
    public array $build_commands = [];

    /** @var array<int, string> */
    public array $post_deploy_commands = [];

    /** @var Collection<int, Server> */
    public Collection $servers;

    /** @var Collection<int, ProjectTemplate> */
    public Collection $templates;

    public function mount(): void
    {
        $this->loadServersAndTemplates();
        $this->loadUserDefaults();
        $this->preselectServerFromQuery();
        $this->deployPath = '/var/www/';
    }

    protected function preselectServerFromQuery(): void
    {
        $serverId = request()->query('server');
        if ($serverId !== null && $this->servers->contains('id', (int) $serverId)) {
            $this->server_id = (string) $serverId;
        }
    }

    protected function loadServersAndTemplates(): void
    {
        $this->servers = Server::orderByRaw("CASE status WHEN 'online' THEN 1 WHEN 'maintenance' THEN 2 WHEN 'offline' THEN 3 WHEN 'error' THEN 4 ELSE 5 END")
            ->get();

        $this->templates = ProjectTemplate::active()->get();
    }

    protected function loadUserDefaults(): void
    {
        $user = auth()->user();
        if ($user === null) {
            return;
        }

        $settings = $user->getSettings();
        $this->enableSsl = $settings->default_enable_ssl;
        $this->enableWebhooks = $settings->default_enable_webhooks;
        $this->enableHealthChecks = $settings->default_enable_health_checks;
        $this->enableBackups = $settings->default_enable_backups;
        $this->enableNotifications = $settings->default_enable_notifications;
        $this->enableAutoDeploy = $settings->default_enable_auto_deploy;
    }

    /**
     * Validate a specific step (required by HasWizardSteps trait)
     */
    protected function validateStep(int $step): void
    {
        match ($step) {
            1 => $this->validateBasicInfo(),
            2 => $this->validateFrameworkBuild(),
            default => null, // Steps 3 & 4 have no required fields
        };
    }

    protected function validateBasicInfo(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:projects,slug',
            'server_id' => 'required|exists:servers,id',
            'repository_url' => ['required', 'regex:/^(https?:\/\/|git@)[\w\-\.]+[\/:][\w\-\.]+\/[\w\-\.]+\.git$/'],
            'branch' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_\-\.\/]+$/'],
        ]);
    }

    protected function validateFrameworkBuild(): void
    {
        $this->validate([
            'framework' => 'nullable|string|max:255',
            'deployment_method' => 'required|in:docker,standard',
            'php_version' => 'nullable|string|max:255',
            'node_version' => 'nullable|string|max:255',
            'root_directory' => 'required|string|max:255',
            'deployPath' => 'nullable|string|max:500',
            'build_command' => 'nullable|string',
            'start_command' => 'nullable|string',
            'useOctane' => 'boolean',
            'octaneServer' => 'nullable|string|in:frankenphp,swoole,roadrunner',
        ]);

        // Bare Metal deployment only works with Laravel (or no framework specified)
        if ($this->deployment_method === 'standard' && $this->framework !== '' && $this->framework !== 'laravel') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'deployment_method' => 'Bare Metal deployment is only supported for Laravel projects. Please select Docker instead.',
            ]);
        }
    }

    public function selectTemplate(?int $templateId): void
    {
        $this->selectedTemplateId = $templateId;

        if (! $templateId) {
            return;
        }

        $template = ProjectTemplate::find($templateId);
        if (! $template) {
            return;
        }

        $this->framework = $template->framework;
        $this->branch = $template->default_branch;
        $this->php_version = $template->php_version ?? '8.3';
        $this->node_version = $template->node_version ?? '20';
        $this->install_commands = $template->install_commands ?? [];
        $this->build_commands = $template->build_commands ?? [];
        $this->post_deploy_commands = $template->post_deploy_commands ?? [];
        $this->build_command = $template->build_commands[0] ?? '';

        $this->updatedFramework();
    }

    public function clearTemplate(): void
    {
        $this->selectedTemplateId = null;
        $this->framework = '';
        $this->branch = 'main';
        $this->php_version = '8.3';
        $this->node_version = '20';
        $this->install_commands = [];
        $this->build_commands = [];
        $this->post_deploy_commands = [];
        $this->build_command = '';
    }

    public function updatedName(): void
    {
        $this->slug = Str::slug($this->name);
        $this->updatedSlug();
    }

    public function updatedSlug(): void
    {
        if ($this->slug !== '') {
            $this->deployPath = '/var/www/'.$this->slug;
        } else {
            $this->deployPath = '/var/www/';
        }
    }

    public function updatedExistingProject(): void
    {
        if ($this->existingProject) {
            $this->enableAutoDeploy = false;
        }
    }

    public function updatedRepositoryUrl(): void
    {
        $this->clearAnalysisCache();
    }

    public function updatedBranch(): void
    {
        $this->clearAnalysisCache();
    }

    /**
     * Override nextStep to trigger repository analysis when moving from Step 1 to Step 2.
     */
    public function nextStep(): void
    {
        $this->validateStep($this->currentStep);

        $movingToStep2 = $this->currentStep === 1;

        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }

        if ($movingToStep2) {
            $this->triggerAnalysisIfNeeded();
        }
    }

    public function analyzeRepository(): void
    {
        $this->analyzing = true;
        $this->analysisError = null;

        try {
            $analysis = app(RepositoryAnalyzerService::class)->analyze(
                $this->repository_url,
                $this->branch
            );

            $this->analysisResult = $analysis->toArray();
            $this->cacheAnalysisResult();
            $this->applyAnalysisResults($analysis);
        } catch (\Throwable $e) {
            $this->analysisError = 'Could not analyze repository: '.$e->getMessage();
            Log::warning('Repository analysis failed in wizard', [
                'url' => $this->repository_url,
                'branch' => $this->branch,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->analyzing = false;
        }
    }

    public function reanalyzeRepository(): void
    {
        $this->clearAnalysisCache();
        $this->analysisResult = null;
        $this->analysisError = null;
        $this->analyzeRepository();
    }

    protected function applyAnalysisResults(RepositoryAnalysis $analysis): void
    {
        if ($analysis->framework !== null) {
            $this->framework = $analysis->framework;
        }

        if ($analysis->phpVersion !== null) {
            $this->php_version = $analysis->phpVersion;
        }

        if ($analysis->nodeVersion !== null) {
            $this->node_version = $analysis->nodeVersion;
        }

        if ($analysis->suggestedDeploymentMethod !== null) {
            $this->deployment_method = $analysis->suggestedDeploymentMethod;
        }

        if ($analysis->buildCommand !== null) {
            $this->build_command = $analysis->buildCommand;
        }

        if ($analysis->startCommand !== null) {
            $this->start_command = $analysis->startCommand;
        }

        // Apply generated deploy commands (only if user hasn't customized them)
        if (! empty($analysis->installCommands) && empty($this->install_commands)) {
            $this->install_commands = $analysis->installCommands;
        }
        if (! empty($analysis->buildCommands) && empty($this->build_commands)) {
            $this->build_commands = $analysis->buildCommands;
        }
        if (! empty($analysis->postDeployCommands) && empty($this->post_deploy_commands)) {
            $this->post_deploy_commands = $analysis->postDeployCommands;
        }

        // Cascade UI changes based on detected framework
        $this->updatedFramework();
    }

    private function triggerAnalysisIfNeeded(): void
    {
        if ($this->repository_url === '') {
            return;
        }

        $cached = $this->getCachedAnalysis();
        if ($cached !== null) {
            $this->analysisResult = $cached;
            $this->applyAnalysisResults(RepositoryAnalysis::fromArray($cached));

            return;
        }

        $this->analyzeRepository();
    }

    private function getAnalysisCacheKey(): string
    {
        return 'repo_analysis_'.md5($this->repository_url.'|'.$this->branch);
    }

    private function clearAnalysisCache(): void
    {
        session()->forget($this->getAnalysisCacheKey());
        $this->analysisResult = null;
        $this->analysisError = null;
    }

    private function cacheAnalysisResult(): void
    {
        if ($this->analysisResult !== null) {
            session()->put($this->getAnalysisCacheKey(), $this->analysisResult);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getCachedAnalysis(): ?array
    {
        $cached = session()->get($this->getAnalysisCacheKey());

        return is_array($cached) ? $cached : null;
    }

    public function addCommand(string $type, string $command): void
    {
        if ($command === '') {
            return;
        }

        match ($type) {
            'install' => $this->install_commands[] = $command,
            'build' => $this->build_commands[] = $command,
            'post_deploy' => $this->post_deploy_commands[] = $command,
            default => null,
        };
    }

    public function removeCommand(string $type, int $index): void
    {
        if ($type === 'install' && isset($this->install_commands[$index])) {
            unset($this->install_commands[$index]);
            $this->install_commands = array_values($this->install_commands);
        } elseif ($type === 'build' && isset($this->build_commands[$index])) {
            unset($this->build_commands[$index]);
            $this->build_commands = array_values($this->build_commands);
        } elseif ($type === 'post_deploy' && isset($this->post_deploy_commands[$index])) {
            unset($this->post_deploy_commands[$index]);
            $this->post_deploy_commands = array_values($this->post_deploy_commands);
        }
    }

    public function refreshServerStatus(int $serverId): void
    {
        $server = Server::find($serverId);

        if (! $server) {
            return;
        }

        app(ServerConnectivityService::class)->pingAndUpdateStatus($server);
        $this->loadServersAndTemplates();
        session()->flash('server_status_updated', 'Server status refreshed');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:projects,slug',
            'server_id' => 'required|exists:servers,id',
            'repository_url' => ['required', 'regex:/^(https?:\/\/|git@)[\w\-\.]+[\/:][\w\-\.]+\/[\w\-\.]+\.git$/'],
            'branch' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_\-\.\/]+$/'],
            'framework' => 'nullable|string|max:255',
            'deployment_method' => 'required|in:docker,standard',
            'php_version' => 'nullable|string|max:255',
            'node_version' => 'nullable|string|max:255',
            'root_directory' => 'required|string|max:255',
            'deployPath' => 'nullable|string|max:500',
            'build_command' => 'nullable|string',
            'start_command' => 'nullable|string',
            'auto_deploy' => 'boolean',
            'useOctane' => 'boolean',
            'octaneServer' => 'nullable|string|in:frankenphp,swoole,roadrunner',
            'existingProject' => 'boolean',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    public function createProject(): void
    {
        $this->validate();

        $port = $this->findNextAvailablePort();
        $setupConfig = $this->buildSetupConfig();
        $postDeployCommands = $this->buildFinalPostDeployCommands();

        $project = Project::create([
            'user_id' => auth()->id(),
            'server_id' => $this->server_id,
            'template_id' => $this->selectedTemplateId,
            'name' => $this->name,
            'slug' => $this->slug,
            'repository_url' => $this->repository_url,
            'branch' => $this->branch,
            'framework' => $this->framework,
            'deployment_method' => $this->deployment_method,
            'php_version' => $this->php_version,
            'node_version' => $this->node_version,
            'port' => $port,
            'root_directory' => $this->root_directory,
            'deploy_path' => $this->deployPath ?: null,
            'use_octane' => $this->useOctane,
            'octane_server' => $this->useOctane ? $this->octaneServer : null,
            'build_command' => $this->build_command,
            'start_command' => $this->start_command,
            'auto_deploy' => $this->auto_deploy,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'notes' => $this->notes ?: null,
            'status' => 'stopped',
            'setup_status' => 'pending',
            'setup_config' => $setupConfig,
            'install_commands' => $this->install_commands,
            'build_commands' => $this->build_commands,
            'post_deploy_commands' => $postDeployCommands,
        ]);

        $this->createDefaultDomains($project, $port);
        $this->initializeProjectSetup($project, $setupConfig);

        $this->createdProjectId = $project->id;
        $this->showProgressModal = true;

        $this->dispatch('project-created');
        $this->dispatch('toast', type: 'success', message: 'Project created successfully on port '.$port.'!');
        session()->flash('success', 'Project created successfully on port '.$port.'!');
    }

    /** @return array<string, bool> */
    protected function buildSetupConfig(): array
    {
        return [
            'ssl' => $this->enableSsl,
            'webhook' => $this->enableWebhooks,
            'health_check' => $this->enableHealthChecks,
            'backup' => $this->enableBackups,
            'notifications' => $this->enableNotifications,
            'deployment' => $this->enableAutoDeploy,
        ];
    }

    /**
     * Build the final post-deploy command list, injecting required Laravel commands
     * and the correct process-reload command for bare-metal Laravel projects.
     *
     * @return array<int, string>
     */
    protected function buildFinalPostDeployCommands(): array
    {
        $commands = $this->post_deploy_commands;

        if ($this->framework !== 'laravel' || $this->deployment_method !== 'standard') {
            return $commands;
        }

        // Inject missing Laravel essential commands (before the process-reload)
        $essentialCommands = [
            'php artisan storage:link',
            'php artisan event:cache',
        ];

        foreach ($essentialCommands as $cmd) {
            if (! in_array($cmd, $commands, true)) {
                $commands[] = $cmd;
            }
        }

        // Add process-reload as the last command
        $reloadCommand = $this->useOctane
            ? 'php artisan octane:reload'
            : 'sudo systemctl reload php8.4-fpm';

        // Only append if not already present
        if (! in_array($reloadCommand, $commands, true)) {
            $commands[] = $reloadCommand;
        }

        return array_values($commands);
    }

    protected function findNextAvailablePort(): int
    {
        $startPort = 8001;
        $maxPort = 9000;

        // Only count active (non-deleted) projects for port allocation
        $usedPorts = Project::where('server_id', $this->server_id)
            ->whereNotNull('port')
            ->whereNull('deleted_at')
            ->pluck('port')
            ->toArray();

        for ($port = $startPort; $port <= $maxPort; $port++) {
            if (! in_array($port, $usedPorts)) {
                return $port;
            }
        }

        return $maxPort + count($usedPorts) + 1;
    }

    protected function createDefaultDomains(Project $project, int $port): void
    {
        $server = Server::find($this->server_id);
        $baseDomain = config('app.base_domain');

        if ($baseDomain) {
            $subdomain = $project->slug.'.'.$baseDomain;

            Domain::create([
                'project_id' => $project->id,
                'domain' => $subdomain,
                'is_primary' => true,
                'ssl_enabled' => false,
                'dns_configured' => false,
                'status' => 'pending',
            ]);
        }

        if ($server?->ip_address) {
            Domain::create([
                'project_id' => $project->id,
                'domain' => $server->ip_address.':'.$port,
                'is_primary' => false,
                'ssl_enabled' => false,
                'dns_configured' => true,
                'status' => 'active',
                'metadata' => ['type' => 'ip_port'],
            ]);
        }
    }

    /**
     * @param  array<string, bool>  $setupConfig
     */
    protected function initializeProjectSetup(Project $project, array $setupConfig): void
    {
        if (empty(array_filter($setupConfig))) {
            return;
        }

        try {
            app(ProjectSetupService::class)->initializeSetup($project, $setupConfig);
            ProcessProjectSetupJob::dispatch($project)->afterCommit();
        } catch (\Exception $e) {
            Log::error('Failed to initialize project setup', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function closeProgressAndRedirect(): void
    {
        $this->showProgressModal = false;

        $project = Project::find($this->createdProjectId);
        $this->redirect(route('projects.show', $project?->slug ?? $this->createdProjectId), navigate: true);
    }

    /** @return array<string, mixed> */
    public function getSetupProgressProperty(): array
    {
        if (! $this->createdProjectId) {
            return [];
        }

        try {
            $project = Project::with('setupTasks')->find($this->createdProjectId);
            if (! $project) {
                return [];
            }

            return app(ProjectSetupService::class)->getSetupProgress($project);
        } catch (\Exception $e) {
            Log::error('Failed to get setup progress', [
                'project_id' => $this->createdProjectId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /** @return array<string, string> */
    public function getFrameworksProperty(): array
    {
        return [
            '' => '-- Select Framework --',
            'static' => 'Static Site (HTML/CSS/JS)',
            'laravel' => 'Laravel',
            'nodejs' => 'Node.js / Express',
            'react' => 'React',
            'vue' => 'Vue.js',
            'nextjs' => 'Next.js',
            'nuxt' => 'Nuxt.js',
        ];
    }

    /**
     * Per-framework configuration presets.
     *
     * @return array<string, array{category: string, standard_allowed: bool, needs_php: bool, needs_node: bool, build_command: string, start_command: string}>
     */
    public function getFrameworkPresetsProperty(): array
    {
        return [
            'laravel' => ['category' => 'php', 'standard_allowed' => true, 'needs_php' => true, 'needs_node' => true, 'build_command' => 'npm run build', 'start_command' => ''],
            'nodejs' => ['category' => 'node', 'standard_allowed' => false, 'needs_php' => false, 'needs_node' => true, 'build_command' => '', 'start_command' => 'node index.js'],
            'react' => ['category' => 'node', 'standard_allowed' => false, 'needs_php' => false, 'needs_node' => true, 'build_command' => 'npm run build', 'start_command' => 'npx serve -s build'],
            'vue' => ['category' => 'node', 'standard_allowed' => false, 'needs_php' => false, 'needs_node' => true, 'build_command' => 'npm run build', 'start_command' => 'npx serve -s dist'],
            'nextjs' => ['category' => 'node', 'standard_allowed' => false, 'needs_php' => false, 'needs_node' => true, 'build_command' => 'npm run build', 'start_command' => 'npm start'],
            'nuxt' => ['category' => 'node', 'standard_allowed' => false, 'needs_php' => false, 'needs_node' => true, 'build_command' => 'npm run build', 'start_command' => 'npm start'],
            'static' => ['category' => 'static', 'standard_allowed' => false, 'needs_php' => false, 'needs_node' => false, 'build_command' => '', 'start_command' => ''],
        ];
    }

    public function getIsStandardAllowedProperty(): bool
    {
        $preset = $this->frameworkPresets[$this->framework] ?? null;

        return $preset === null || $preset['standard_allowed'];
    }

    public function getNeedsPhpProperty(): bool
    {
        $preset = $this->frameworkPresets[$this->framework] ?? null;

        // No framework selected: show PHP if standard deployment is chosen
        if ($preset === null) {
            return $this->deployment_method === 'standard';
        }

        return $preset['needs_php'];
    }

    public function getNeedsNodeProperty(): bool
    {
        $preset = $this->frameworkPresets[$this->framework] ?? null;

        // No framework selected: show Node if standard deployment is chosen
        if ($preset === null) {
            return $this->deployment_method === 'standard';
        }

        return $preset['needs_node'];
    }

    public function updatedFramework(): void
    {
        // If the current deployment method is not supported, switch to docker
        if (! $this->isStandardAllowed && $this->deployment_method === 'standard') {
            $this->deployment_method = 'docker';
        }

        // Collect all known preset default commands so we can detect untouched values
        $knownBuildDefaults = array_unique(array_column($this->frameworkPresets, 'build_command'));
        $knownStartDefaults = array_unique(array_column($this->frameworkPresets, 'start_command'));

        $preset = $this->frameworkPresets[$this->framework] ?? null;

        // Replace build/start commands if empty OR still set to a preset default (not manually edited)
        if ($preset !== null) {
            if ($this->build_command === '' || in_array($this->build_command, $knownBuildDefaults, true)) {
                $this->build_command = $preset['build_command'];
            }
            if ($this->start_command === '' || in_array($this->start_command, $knownStartDefaults, true)) {
                $this->start_command = $preset['start_command'];
            }
        }
    }

    /** @return array<string, string> */
    public function getPhpVersionsProperty(): array
    {
        return [
            '8.4' => 'PHP 8.4 (Latest)',
            '8.3' => 'PHP 8.3',
            '8.2' => 'PHP 8.2',
            '8.1' => 'PHP 8.1',
            '8.0' => 'PHP 8.0',
            '7.4' => 'PHP 7.4 (Legacy)',
        ];
    }

    public function render(): View
    {
        return view('livewire.projects.project-create');
    }
}
