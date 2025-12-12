<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\Server;
use App\Services\ServerConnectivityService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;

class ProjectEdit extends Component
{
    public Project $project;

    public string $name = '';

    public string $slug = '';

    public string|int|null $server_id = '';

    public ?string $repository_url = '';

    public string $branch = 'main';

    public ?string $framework = '';

    public ?string $php_version = '8.3';

    public ?string $node_version = '20';

    public string $root_directory = '/';

    public ?string $build_command = '';

    public ?string $start_command = '';

    public bool $auto_deploy = false;

    public float|string|null $latitude = null;

    public float|string|null $longitude = null;

    /** @var Collection<int, Server> */
    public Collection $servers;

    /** @var array<string, string> */
    public array $frameworks = [
        '' => '-- Select Framework --',
        'static' => 'Static Site (HTML/CSS/JS)',
        'laravel' => 'Laravel',
        'nodejs' => 'Node.js / Express',
        'react' => 'React',
        'vue' => 'Vue.js',
        'nextjs' => 'Next.js',
        'nuxt' => 'Nuxt.js',
    ];

    /** @var array<string, string> */
    public array $php_versions = [
        '8.4' => 'PHP 8.4 (Latest)',
        '8.3' => 'PHP 8.3',
        '8.2' => 'PHP 8.2',
        '8.1' => 'PHP 8.1',
        '8.0' => 'PHP 8.0',
        '7.4' => 'PHP 7.4 (Legacy)',
    ];

    public function mount(Project $project): void
    {
        // Authorization: Only project owner or team members can edit
        $user = auth()->user();

        if (! $user) {
            abort(401);
        }

        // Check if user owns the project
        if ($project->user_id !== $user->id) {
            // Check if user is a team member with access
            if ($project->team_id && $user->currentTeam && $user->currentTeam->id === $project->team_id) {
                // Team member has access
            } else {
                abort(403);
            }
        }

        $this->project = $project;

        // Load project data
        $this->name = $project->name;
        $this->slug = $project->slug;
        $this->server_id = $project->server_id;
        $this->repository_url = $project->repository_url;
        $this->branch = $project->branch;
        $this->framework = $project->framework;
        $this->php_version = $project->php_version;
        $this->node_version = $project->node_version;
        $this->root_directory = $project->root_directory;
        $this->build_command = $project->build_command;
        $this->start_command = $project->start_command;
        $this->auto_deploy = $project->auto_deploy;
        $this->latitude = $project->latitude;
        $this->longitude = $project->longitude;

        // All servers are shared
        // Use CASE for SQLite compatibility instead of MySQL's FIELD()
        $this->servers = Server::orderByRaw("CASE status WHEN 'online' THEN 1 WHEN 'maintenance' THEN 2 WHEN 'offline' THEN 3 WHEN 'error' THEN 4 ELSE 5 END")
            ->get();
    }

    public function updatedName(): void
    {
        $this->slug = Str::slug($this->name);
    }

    public function refreshServerStatus(int|string $serverId): void
    {
        $server = Server::find($serverId);

        if ($server) {
            $connectivityService = app(ServerConnectivityService::class);
            $connectivityService->pingAndUpdateStatus($server);

            // Reload servers list (all shared)
            // Use CASE for SQLite compatibility instead of MySQL's FIELD()
            $this->servers = Server::orderByRaw("CASE status WHEN 'online' THEN 1 WHEN 'maintenance' THEN 2 WHEN 'offline' THEN 3 WHEN 'error' THEN 4 ELSE 5 END")
                ->get();

            session()->flash('server_status_updated', 'Server status refreshed');
        }
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            // Ignore current project and soft-deleted projects
            'slug' => 'required|string|max:255|unique:projects,slug,'.$this->project->id.',id,deleted_at,NULL',
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

    /**
     * @return array<string, string>
     */
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
     * @return array<string, string>
     */
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
        return view('livewire.projects.project-edit');
    }
}
