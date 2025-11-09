<?php

namespace App\Livewire\Projects;

use Livewire\Component;
use App\Models\Project;
use App\Models\Server;
use App\Services\ServerConnectivityService;
use Illuminate\Support\Str;

class ProjectEdit extends Component
{
    public Project $project;
    
    public $name = '';
    public $slug = '';
    public $server_id = '';
    public $repository_url = '';
    public $branch = 'main';
    public $framework = '';
    public $php_version = '8.3';
    public $node_version = '20';
    public $root_directory = '/';
    public $build_command = '';
    public $start_command = '';
    public $auto_deploy = false;
    public $latitude = null;
    public $longitude = null;

    public $servers = [];
    public $frameworks = [
        '' => '-- Select Framework --',
        'static' => 'Static Site (HTML/CSS/JS)',
        'laravel' => 'Laravel',
        'nodejs' => 'Node.js / Express',
        'react' => 'React',
        'vue' => 'Vue.js',
        'nextjs' => 'Next.js',
        'nuxt' => 'Nuxt.js',
    ];
    public $php_versions = [
        '8.4' => 'PHP 8.4 (Latest)',
        '8.3' => 'PHP 8.3',
        '8.2' => 'PHP 8.2',
        '8.1' => 'PHP 8.1',
        '8.0' => 'PHP 8.0',
        '7.4' => 'PHP 7.4 (Legacy)',
    ];

    public function mount(Project $project)
    {
        // Check authorization
        if ($project->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this project.');
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

        $this->servers = Server::where('user_id', auth()->id())
            ->orderByRaw("FIELD(status, 'online', 'maintenance', 'offline', 'error')")
            ->get();
    }

    public function updatedName()
    {
        $this->slug = Str::slug($this->name);
    }

    public function refreshServerStatus($serverId)
    {
        $server = Server::where('id', $serverId)
            ->where('user_id', auth()->id())
            ->first();

        if ($server) {
            $connectivityService = app(ServerConnectivityService::class);
            $connectivityService->pingAndUpdateStatus($server);
            
            // Reload servers list
            $this->servers = Server::where('user_id', auth()->id())
                ->orderByRaw("FIELD(status, 'online', 'maintenance', 'offline', 'error')")
                ->get();
            
            session()->flash('server_status_updated', 'Server status refreshed');
        }
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            // Ignore current project and soft-deleted projects
            'slug' => 'required|string|max:255|unique:projects,slug,' . $this->project->id . ',id,deleted_at,NULL',
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

    public function getFrameworksProperty()
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

    public function getPhpVersionsProperty()
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
        return view('livewire.projects.project-edit');
    }
}

