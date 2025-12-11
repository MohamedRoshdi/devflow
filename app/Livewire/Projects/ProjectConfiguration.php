<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ProjectConfiguration extends Component
{
    #[Locked]
    public int $projectId;

    public Project $project;

    public string $name = '';

    public string $slug = '';

    public string $repository_url = '';

    public string $branch = 'main';

    public string $framework = '';

    public string $php_version = '8.3';

    public string $node_version = '20';

    public string $root_directory = '/';

    public string $health_check_url = '';

    public bool $auto_deploy = false;

    /** @var array<string, string> */
    public array $frameworks = [
        '' => '-- Select Framework --',
        'laravel' => 'Laravel',
        'nodejs' => 'Node.js / Express',
        'react' => 'React',
        'vue' => 'Vue.js',
        'nextjs' => 'Next.js',
        'nuxt' => 'Nuxt.js',
        'static' => 'Static Site (HTML/CSS/JS)',
    ];

    /** @var array<string, string> */
    public array $phpVersions = [
        '8.4' => 'PHP 8.4 (Latest)',
        '8.3' => 'PHP 8.3',
        '8.2' => 'PHP 8.2',
        '8.1' => 'PHP 8.1',
        '8.0' => 'PHP 8.0',
        '7.4' => 'PHP 7.4 (Legacy)',
    ];

    /** @var array<int|string, string> */
    public array $nodeVersions = [
        '22' => 'Node.js 22 (Latest)',
        '20' => 'Node.js 20 (LTS)',
        '18' => 'Node.js 18 (LTS)',
        '16' => 'Node.js 16',
    ];

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->projectId = $project->id;

        // Load project configuration
        $this->name = $project->name;
        $this->slug = $project->slug;
        $this->repository_url = $project->repository_url ?? '';
        $this->branch = $project->branch;
        $this->framework = $project->framework ?? '';
        $this->php_version = $project->php_version ?? '8.3';
        $this->node_version = $project->node_version ?? '20';
        $this->root_directory = $project->root_directory;
        $this->health_check_url = $project->health_check_url ?? '';
        $this->auto_deploy = $project->auto_deploy;
    }

    public function updatedName(): void
    {
        $this->slug = Str::slug($this->name);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|regex:/^[a-z0-9-]+$/|unique:projects,slug,'.$this->projectId.',id,deleted_at,NULL',
            'repository_url' => ['nullable', 'regex:/^(https?:\/\/|git@)[\w\-\.]+[\/:][\w\-\.]+\/[\w\-\.]+\.git$/'],
            'branch' => 'required|string|max:255',
            'framework' => 'nullable|string|max:255',
            'php_version' => 'nullable|string|max:255',
            'node_version' => 'nullable|string|max:255',
            'root_directory' => 'required|string|max:255',
            'health_check_url' => 'nullable|url|max:500',
            'auto_deploy' => 'boolean',
        ];
    }

    public function saveConfiguration()
    {
        $this->validate();

        try {
            $this->project->update([
                'name' => $this->name,
                'slug' => $this->slug,
                'repository_url' => $this->repository_url ?: null,
                'branch' => $this->branch,
                'framework' => $this->framework ?: null,
                'php_version' => $this->php_version,
                'node_version' => $this->node_version,
                'root_directory' => $this->root_directory,
                'health_check_url' => $this->health_check_url ?: null,
                'auto_deploy' => $this->auto_deploy,
            ]);

            session()->flash('message', 'Project configuration updated successfully!');

            return redirect()->route('projects.show', $this->project);

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update configuration: '.$e->getMessage());
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.projects.project-configuration');
    }
}
