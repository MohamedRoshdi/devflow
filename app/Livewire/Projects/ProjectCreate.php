<?php

namespace App\Livewire\Projects;

use App\Jobs\ProcessProjectSetupJob;
use App\Models\Domain;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\Server;
use App\Services\ProjectSetupService;
use App\Services\ServerConnectivityService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Component;

class ProjectCreate extends Component
{
    // Wizard state
    public int $currentStep = 1;

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

    public string $php_version = '8.3';

    public string $node_version = '20';

    public string $root_directory = '/';

    public string $build_command = '';

    public string $start_command = '';

    public bool $auto_deploy = false;

    public ?float $latitude = null;

    public ?float $longitude = null;

    // Step 3: Setup Options (feature toggles)
    public bool $enableSsl = true;

    public bool $enableWebhooks = true;

    public bool $enableHealthChecks = true;

    public bool $enableBackups = true;

    public bool $enableNotifications = true;

    public bool $enableAutoDeploy = false;

    // Template fields
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
        // All servers are shared
        // Use CASE for SQLite compatibility instead of MySQL's FIELD()
        $this->servers = Server::orderByRaw("CASE status WHEN 'online' THEN 1 WHEN 'maintenance' THEN 2 WHEN 'offline' THEN 3 WHEN 'error' THEN 4 ELSE 5 END")
            ->get();

        $this->templates = ProjectTemplate::active()->get();

        // Load user's default settings for setup options
        $user = auth()->user();
        if ($user !== null) {
            $settings = $user->getSettings();
            $this->enableSsl = $settings->default_enable_ssl;
            $this->enableWebhooks = $settings->default_enable_webhooks;
            $this->enableHealthChecks = $settings->default_enable_health_checks;
            $this->enableBackups = $settings->default_enable_backups;
            $this->enableNotifications = $settings->default_enable_notifications;
            $this->enableAutoDeploy = $settings->default_enable_auto_deploy;
        }
    }

    // Wizard navigation methods
    public function nextStep(): void
    {
        $this->validateCurrentStep();

        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step <= $this->totalSteps && $step <= $this->currentStep) {
            $this->currentStep = $step;
        }
    }

    protected function validateCurrentStep(): void
    {
        match ($this->currentStep) {
            1 => $this->validateStep1(),
            2 => $this->validateStep2(),
            3 => true, // Step 3 has no required fields
            default => true,
        };
    }

    protected function validateStep1(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:projects,slug,NULL,id,deleted_at,NULL',
            'server_id' => 'required|exists:servers,id',
            'repository_url' => ['required', 'regex:/^(https?:\/\/|git@)[\w\-\.]+[\/:][\w\-\.]+\/[\w\-\.]+\.git$/'],
            'branch' => 'required|string|max:255',
        ]);
    }

    protected function validateStep2(): void
    {
        $this->validate([
            'framework' => 'nullable|string|max:255',
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

        if ($templateId) {
            $template = ProjectTemplate::find($templateId);
            if ($template) {
                $this->framework = $template->framework;
                $this->branch = $template->default_branch;
                $this->php_version = $template->php_version ?? '8.3';
                $this->node_version = $template->node_version ?? '20';
                $this->install_commands = $template->install_commands ?? [];
                $this->build_commands = $template->build_commands ?? [];
                $this->post_deploy_commands = $template->post_deploy_commands ?? [];

                // Set build_command as a string (first command if available)
                $this->build_command = $template->build_commands[0] ?? '';
            }
        }
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

        if ($server) {
            $connectivityService = app(ServerConnectivityService::class);
            $connectivityService->pingAndUpdateStatus($server);

            // Reload servers list
            $this->mount();

            session()->flash('server_status_updated', 'Server status refreshed');
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            // Ignore soft-deleted projects when checking slug uniqueness
            'slug' => 'required|string|max:255|unique:projects,slug,NULL,id,deleted_at,NULL',
            'server_id' => 'required|exists:servers,id',
            // Support both HTTPS and SSH URLs
            'repository_url' => ['required', 'regex:/^(https?:\/\/|git@)[\w\-\.]+[\/:][\w\-\.]+\/[\w\-\.]+\.git$/'],
            'branch' => 'required|string|max:255',
            'framework' => 'nullable|string|max:255',
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

        // Find next available port
        $port = $this->getNextAvailablePort();

        // Build setup config
        $setupConfig = [
            'ssl' => $this->enableSsl,
            'webhook' => $this->enableWebhooks,
            'health_check' => $this->enableHealthChecks,
            'backup' => $this->enableBackups,
            'notifications' => $this->enableNotifications,
            'deployment' => $this->enableAutoDeploy,
        ];

        $project = Project::create([
            'user_id' => auth()->id(),
            'server_id' => $this->server_id,
            'template_id' => $this->selectedTemplateId,
            'name' => $this->name,
            'slug' => $this->slug,
            'repository_url' => $this->repository_url,
            'branch' => $this->branch,
            'framework' => $this->framework,
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

        // Auto-create subdomain for the project
        $this->createDefaultDomain($project, $port);

        // Initialize and run project setup
        $hasAnySetup = array_filter($setupConfig);
        if (! empty($hasAnySetup)) {
            try {
                app(ProjectSetupService::class)->initializeSetup($project, $setupConfig);
                ProcessProjectSetupJob::dispatch($project)->afterCommit();
            } catch (\Exception $e) {
                \Log::error('Failed to initialize project setup', [
                    'project_id' => $project->id,
                    'error' => $e->getMessage(),
                ]);
                // Continue even if setup initialization fails
            }
        }

        $this->createdProjectId = $project->id;
        $this->showProgressModal = true;

        $this->dispatch('project-created');

        // Don't redirect immediately - show progress modal
        session()->flash('message', 'Project created successfully on port '.$port.'!');
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
            \Log::error('Failed to get setup progress', [
                'project_id' => $this->createdProjectId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Find the next available port for the project
     */
    protected function getNextAvailablePort(): int
    {
        // Start from port 8001
        $startPort = 8001;
        $maxPort = 9000;

        // Get all used ports on the selected server
        $usedPorts = Project::where('server_id', $this->server_id)
            ->whereNotNull('port')
            ->pluck('port')
            ->toArray();

        // Find the first available port
        for ($port = $startPort; $port <= $maxPort; $port++) {
            if (! in_array($port, $usedPorts)) {
                return $port;
            }
        }

        // If all ports are used, return a high port number
        return $maxPort + count($usedPorts) + 1;
    }

    /**
     * Create a default domain for the project
     */
    protected function createDefaultDomain(Project $project, int $port): void
    {
        $server = Server::find($this->server_id);
        $baseDomain = config('app.base_domain', 'nilestack.duckdns.org');

        // Create subdomain based on project slug
        $subdomain = $project->slug.'.'.$baseDomain;

        // Create IP:port domain as well
        $ipDomain = $server ? $server->ip_address.':'.$port : null;

        // Create primary subdomain
        Domain::create([
            'project_id' => $project->id,
            'domain' => $subdomain,
            'is_primary' => true,
            'ssl_enabled' => false,
            'dns_configured' => false,
            'status' => 'pending',
        ]);

        // Create IP:port access domain if server has IP
        if ($ipDomain) {
            Domain::create([
                'project_id' => $project->id,
                'domain' => $ipDomain,
                'is_primary' => false,
                'ssl_enabled' => false,
                'dns_configured' => true,
                'status' => 'active',
                'metadata' => ['type' => 'ip_port'],
            ]);
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

    public function render()
    {
        return view('livewire.projects.project-create');
    }
}
