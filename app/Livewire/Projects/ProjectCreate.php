<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Jobs\ProcessProjectSetupJob;
use App\Livewire\Concerns\HasWizardSteps;
use App\Models\Domain;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\Server;
use App\Services\ProjectSetupService;
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

    public string $build_command = '';

    public string $start_command = '';

    public bool $auto_deploy = false;

    public ?float $latitude = null;

    public ?float $longitude = null;

    // Step 3: Setup Options
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
            'slug' => 'required|string|max:255|unique:projects,slug,NULL,id,deleted_at,NULL',
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
            'build_command' => 'nullable|string',
            'start_command' => 'nullable|string',
        ]);
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
            'slug' => 'required|string|max:255|unique:projects,slug,NULL,id,deleted_at,NULL',
            'server_id' => 'required|exists:servers,id',
            'repository_url' => ['required', 'regex:/^(https?:\/\/|git@)[\w\-\.]+[\/:][\w\-\.]+\/[\w\-\.]+\.git$/'],
            'branch' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_\-\.\/]+$/'],
            'framework' => 'nullable|string|max:255',
            'deployment_method' => 'required|in:docker,standard',
            'php_version' => 'nullable|string|max:255',
            'node_version' => 'nullable|string|max:255',
            'root_directory' => 'required|string|max:255',
            'build_command' => 'nullable|string',
            'start_command' => 'nullable|string',
            'auto_deploy' => 'boolean',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ];
    }

    public function createProject(): void
    {
        $this->validate();

        $port = $this->findNextAvailablePort();
        $setupConfig = $this->buildSetupConfig();

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
            'build_command' => $this->build_command,
            'start_command' => $this->start_command,
            'auto_deploy' => $this->auto_deploy,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => 'stopped',
            'setup_status' => 'pending',
            'setup_config' => $setupConfig,
            'install_commands' => $this->install_commands,
            'build_commands' => $this->build_commands,
            'post_deploy_commands' => $this->post_deploy_commands,
        ]);

        $this->createDefaultDomains($project, $port);
        $this->initializeProjectSetup($project, $setupConfig);

        $this->createdProjectId = $project->id;
        $this->showProgressModal = true;

        $this->dispatch('project-created');
        session()->flash('message', 'Project created successfully on port '.$port.'!');
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

    protected function findNextAvailablePort(): int
    {
        $startPort = 8001;
        $maxPort = 9000;

        $usedPorts = Project::where('server_id', $this->server_id)
            ->whereNotNull('port')
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
        $baseDomain = config('app.base_domain', 'nilestack.duckdns.org');
        $subdomain = $project->slug.'.'.$baseDomain;

        Domain::create([
            'project_id' => $project->id,
            'domain' => $subdomain,
            'is_primary' => true,
            'ssl_enabled' => false,
            'dns_configured' => false,
            'status' => 'pending',
        ]);

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
     * @param array<string, bool> $setupConfig
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
        $this->redirect(route('projects.show', $this->createdProjectId), navigate: true);
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
