<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Livewire\Concerns\HasProjectFormFields;
use App\Models\Project;
use App\Models\Server;
use App\Services\ServerConnectivityService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

class ProjectEdit extends Component
{
    use HasProjectFormFields;

    public Project $project;

    /** @var Collection<int, Server> */
    public Collection $servers;

    public function mount(Project $project): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(401);
        }

        // Check if user owns the project or is a team member
        if ($project->user_id !== $user->id) {
            if ($project->team_id && $user->currentTeam && $user->currentTeam->id === $project->team_id) {
                // Team member has access
            } else {
                abort(403);
            }
        }

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

    /**
     * @return array<string, string|array<int, string>>
     */
    public function rules(): array
    {
        return array_merge(
            $this->baseProjectRules(),
            ['slug' => $this->uniqueSlugRule($this->project->id)]
        );
    }

    public function updateProject()
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
        ]);

        session()->flash('message', 'Project updated successfully!');

        return redirect()->route('projects.show', $this->project);
    }

    public function render(): View
    {
        return view('livewire.projects.project-edit');
    }
}
