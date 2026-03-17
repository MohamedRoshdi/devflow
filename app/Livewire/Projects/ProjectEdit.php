<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Livewire\Concerns\HasProjectFormFields;
use App\Livewire\Concerns\WithPasswordConfirmation;
use App\Models\Project;
use App\Models\Server;
use App\Services\ServerConnectivityService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Component;

class ProjectEdit extends Component
{
    use AuthorizesRequests;
    use HasProjectFormFields;
    use WithPasswordConfirmation;

    public Project $project;

    /** @var Collection<int, Server> */
    public Collection $servers;

    /**
     * Override the trait's auto-slug generation.
     * In edit mode the slug is locked and must not change when the name is edited.
     */
    public function updatedName(): void
    {
        // intentionally empty — slug is read-only in edit
    }

    // Deployment fields
    public string $deployment_method = 'docker';

    public ?string $deploy_path = null;

    public bool $use_octane = false;

    public ?string $octane_server = 'frankenphp';

    /** @var array<int, string> */
    public array $install_commands = [];

    /** @var array<int, string> */
    public array $build_commands = [];

    /** @var array<int, string> */
    public array $post_deploy_commands = [];

    public function mount(Project $project): void
    {
        $this->authorize('update', $project);

        $this->project = $project;
        $this->loadProjectData();
        $this->loadServers();
    }

    protected function loadProjectData(): void
    {
        $this->name = $this->project->name;
        $this->slug = $this->project->slug;
        $this->server_id = $this->project->server_id;
        $this->repository_url = $this->project->repository_url;
        $this->branch = $this->project->branch;
        $this->framework = $this->project->framework;
        $this->php_version = $this->project->php_version;
        $this->node_version = $this->project->node_version;
        $this->root_directory = $this->project->root_directory;
        $this->build_command = $this->project->build_command;
        $this->start_command = $this->project->start_command;
        $this->auto_deploy = $this->project->auto_deploy;
        $this->latitude = $this->project->latitude;
        $this->longitude = $this->project->longitude;
        $this->notes = $this->project->notes;

        // Deployment fields
        $this->deployment_method = $this->project->deployment_method ?? 'docker';
        $this->deploy_path = $this->project->deploy_path;
        $this->use_octane = (bool) $this->project->use_octane;
        $this->octane_server = $this->project->octane_server ?? 'frankenphp';
        $this->install_commands = $this->project->install_commands ?? [];
        $this->build_commands = $this->project->build_commands ?? [];
        $this->post_deploy_commands = $this->project->post_deploy_commands ?? [];
    }

    protected function loadServers(): void
    {
        $this->servers = Server::orderByRaw("CASE status WHEN 'online' THEN 1 WHEN 'maintenance' THEN 2 WHEN 'offline' THEN 3 WHEN 'error' THEN 4 ELSE 5 END")
            ->get();
    }

    public function refreshServerStatus(int|string $serverId): void
    {
        $server = Server::find($serverId);

        if ($server) {
            $connectivityService = app(ServerConnectivityService::class);
            $connectivityService->pingAndUpdateStatus($server);
            $this->loadServers();
            session()->flash('server_status_updated', 'Server status refreshed');
        }
    }

    // Pipeline command list helpers

    public function addInstallCommand(): void
    {
        $this->install_commands[] = '';
    }

    public function removeInstallCommand(int $index): void
    {
        array_splice($this->install_commands, $index, 1);
        $this->install_commands = array_values($this->install_commands);
    }

    public function addBuildCommand(): void
    {
        $this->build_commands[] = '';
    }

    public function removeBuildCommand(int $index): void
    {
        array_splice($this->build_commands, $index, 1);
        $this->build_commands = array_values($this->build_commands);
    }

    public function addPostDeployCommand(): void
    {
        $this->post_deploy_commands[] = '';
    }

    public function removePostDeployCommand(int $index): void
    {
        array_splice($this->post_deploy_commands, $index, 1);
        $this->post_deploy_commands = array_values($this->post_deploy_commands);
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    public function rules(): array
    {
        return array_merge(
            $this->baseProjectRules(),
            [
                'slug' => $this->uniqueSlugRule($this->project->id),
                'deployment_method' => 'required|in:docker,standard',
                'deploy_path' => 'nullable|string|max:500',
                'use_octane' => 'boolean',
                'octane_server' => 'nullable|string|in:frankenphp,swoole,roadrunner',
                'install_commands' => 'nullable|array',
                'install_commands.*' => 'nullable|string|max:1000',
                'build_commands' => 'nullable|array',
                'build_commands.*' => 'nullable|string|max:1000',
                'post_deploy_commands' => 'nullable|array',
                'post_deploy_commands.*' => 'nullable|string|max:1000',
            ]
        );
    }

    public function updateProject(): mixed
    {
        $this->validate();

        $this->project->update([
            'name' => $this->name,
            'slug' => $this->slug,
            'server_id' => $this->server_id,
            'repository_url' => $this->repository_url,
            'branch' => $this->branch,
            'framework' => $this->framework,
            'php_version' => $this->php_version,
            'node_version' => $this->node_version,
            'root_directory' => $this->root_directory,
            'build_command' => $this->build_command,
            'start_command' => $this->start_command,
            'auto_deploy' => $this->auto_deploy,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'notes' => $this->notes ?: null,
            // Deployment
            'deployment_method' => $this->deployment_method,
            'deploy_path' => $this->deployment_method === 'standard' ? ($this->deploy_path ?: null) : null,
            'use_octane' => $this->use_octane,
            'octane_server' => $this->use_octane ? $this->octane_server : null,
            // Pipeline
            'install_commands' => array_values(array_filter($this->install_commands, fn ($v) => $v !== '' && $v !== null)),
            'build_commands' => array_values(array_filter($this->build_commands, fn ($v) => $v !== '' && $v !== null)),
            'post_deploy_commands' => array_values(array_filter($this->post_deploy_commands, fn ($v) => $v !== '' && $v !== null)),
        ]);

        session()->flash('message', 'Project updated successfully!');

        return redirect()->route('projects.show', $this->project);
    }

    public function deleteProject(string $param = ''): mixed
    {
        $this->authorize('delete', $this->project);

        $this->project->delete();

        session()->flash('message', 'Project deleted successfully.');

        return redirect()->route('projects.index');
    }

    public function render(): View
    {
        return view('livewire.projects.project-edit');
    }
}
