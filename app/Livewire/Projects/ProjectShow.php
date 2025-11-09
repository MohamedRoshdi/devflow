<?php

namespace App\Livewire\Projects;

use Livewire\Component;
use App\Models\Project;
use App\Models\Deployment;
use App\Services\DockerService;
use App\Services\GitService;
use Livewire\Attributes\On;

class ProjectShow extends Component
{
    public Project $project;
    public $showDeployModal = false;
    public $commits = [];
    public $updateStatus = null;
    public $checkingForUpdates = false;

    public function mount(Project $project)
    {
        // Check if project belongs to current user
        if ($project->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this project.');
        }
        
        $this->project = $project;
        $this->loadCommits();
    }

    public function loadCommits()
    {
        try {
            $gitService = app(GitService::class);
            $result = $gitService->getLatestCommits($this->project, 10);
            
            if ($result['success']) {
                $this->commits = $result['commits'];
            } else {
                $this->commits = [];
            }
        } catch (\Exception $e) {
            $this->commits = [];
        }
    }

    public function checkForUpdates()
    {
        try {
            $this->checkingForUpdates = true;
            
            $gitService = app(GitService::class);
            $result = $gitService->checkForUpdates($this->project);
            
            if ($result['success']) {
                $this->updateStatus = $result;
                
                if ($result['up_to_date']) {
                    session()->flash('message', 'Project is up-to-date with the latest commit!');
                } else {
                    session()->flash('message', "New updates available! {$result['commits_behind']} commit(s) behind.");
                }
            } else {
                session()->flash('error', 'Failed to check for updates: ' . $result['error']);
            }
            
            $this->checkingForUpdates = false;
        } catch (\Exception $e) {
            $this->checkingForUpdates = false;
            session()->flash('error', 'Failed to check for updates: ' . $e->getMessage());
        }
    }

    public function deploy()
    {
        try {
            $deployment = Deployment::create([
                'user_id' => auth()->id(),
                'project_id' => $this->project->id,
                'server_id' => $this->project->server_id,
                'branch' => $this->project->branch,
                'status' => 'pending',
                'triggered_by' => 'manual',
                'started_at' => now(),
            ]);

            // Dispatch deployment job
            \App\Jobs\DeployProjectJob::dispatch($deployment);

            session()->flash('message', 'Deployment started successfully!');
            
            $this->showDeployModal = false;
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to start deployment: ' . $e->getMessage());
        }
    }

    public function startProject()
    {
        try {
            $dockerService = app(DockerService::class);
            $result = $dockerService->startContainer($this->project);

            if ($result['success']) {
                $this->project->update(['status' => 'running']);
                session()->flash('message', 'Project started successfully');
            } else {
                session()->flash('error', 'Failed to start project: ' . $result['error']);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to start project: ' . $e->getMessage());
        }
    }

    public function stopProject()
    {
        try {
            $dockerService = app(DockerService::class);
            $result = $dockerService->stopContainer($this->project);

            if ($result['success']) {
                $this->project->update(['status' => 'stopped']);
                session()->flash('message', 'Project stopped successfully');
            } else {
                session()->flash('error', 'Failed to stop project: ' . $result['error']);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to stop project: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $deployments = $this->project->deployments()->latest()->take(10)->get();
        $domains = $this->project->domains;

        return view('livewire.projects.project-show', [
            'deployments' => $deployments,
            'domains' => $domains,
            'commits' => $this->commits,
            'updateStatus' => $this->updateStatus,
        ]);
    }
}

