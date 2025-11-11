<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ProjectEnvironment extends Component
{
    #[Locked]
    public $projectId;

    public $environment;
    public $envVariables = [];
    public $showEnvModal = false;
    public $newEnvKey = '';
    public $newEnvValue = '';
    public $editingEnvKey = null;

    protected $rules = [
        'environment' => 'required|in:local,development,staging,production',
        'newEnvKey' => 'required|string|max:255',
        'newEnvValue' => 'string|max:1000',
    ];

    public function mount(Project $project)
    {
        $this->projectId = $project->id;
        $this->environment = $project->environment ?? 'production';
        $this->envVariables = $project->env_variables ? (array)$project->env_variables : [];
    }

    protected function getProject()
    {
        return Project::findOrFail($this->projectId);
    }

    public function updateEnvironment($newEnvironment = null)
    {
        if ($newEnvironment) {
            $this->environment = $newEnvironment;
        }

        $this->validate(['environment' => 'required|in:local,development,staging,production']);

        $project = $this->getProject();
        $project->update(['environment' => $this->environment]);

        session()->flash('message', 'Environment updated to ' . ucfirst($this->environment));
        $this->dispatch('environmentUpdated');
    }

    public function openEnvModal()
    {
        $this->showEnvModal = true;
        $this->newEnvKey = '';
        $this->newEnvValue = '';
        $this->editingEnvKey = null;
    }

    public function closeEnvModal()
    {
        $this->showEnvModal = false;
        $this->resetValidation();
    }

    public function addEnvVariable()
    {
        $this->validate([
            'newEnvKey' => 'required|string|max:255',
            'newEnvValue' => 'string|max:1000',
        ]);

        $this->envVariables[$this->newEnvKey] = $this->newEnvValue;
        $this->saveEnvVariables();

        $this->newEnvKey = '';
        $this->newEnvValue = '';
        session()->flash('message', 'Environment variable added successfully');
    }

    public function editEnvVariable($key)
    {
        $this->editingEnvKey = $key;
        $this->newEnvKey = $key;
        $this->newEnvValue = $this->envVariables[$key] ?? '';
        $this->showEnvModal = true;
    }

    public function updateEnvVariable()
    {
        if ($this->editingEnvKey && $this->editingEnvKey !== $this->newEnvKey) {
            unset($this->envVariables[$this->editingEnvKey]);
        }

        $this->envVariables[$this->newEnvKey] = $this->newEnvValue;
        $this->saveEnvVariables();

        $this->closeEnvModal();
        session()->flash('message', 'Environment variable updated successfully');
    }

    public function deleteEnvVariable($key)
    {
        unset($this->envVariables[$key]);
        $this->saveEnvVariables();
        session()->flash('message', 'Environment variable deleted successfully');
    }

    protected function saveEnvVariables()
    {
        $project = $this->getProject();
        $project->update(['env_variables' => $this->envVariables]);
    }

    public function render()
    {
        return view('livewire.projects.project-environment', [
            'project' => $this->getProject(),
        ]);
    }
}
