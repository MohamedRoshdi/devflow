<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Models\Deployment;
use App\Models\Project;
use App\Services\DockerService;
use App\Services\GitService;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectShow extends Component
{
    use WithPagination;

    public Project $project;

    public bool $showDeployModal = false;

    public string $activeTab = 'overview';

    public int $deploymentsPerPage = 5;

    protected string $paginationTheme = 'tailwind';

    // Update status for overview banner
    /** @var array<string, mixed>|null */
    public ?array $updateStatus = null;

    public bool $checkingForUpdates = false;

    public bool $updateStatusLoaded = false;

    protected DockerService $dockerService;

    protected GitService $gitService;

    public function boot(DockerService $dockerService, GitService $gitService): void
    {
        $this->dockerService = $dockerService;
        $this->gitService = $gitService;
    }

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);

        $this->project = $project->load([
            'domains:id,project_id,domain,subdomain,ssl_enabled,is_primary',
            'activeDeployment' => fn ($q) => $q->select('deployments.id', 'deployments.project_id', 'deployments.status', 'deployments.created_at')
        ]);

        $tab = request()->query('tab', 'overview');
        $this->activeTab = is_string($tab) ? $tab : 'overview';

        $this->preloadUpdateStatus();
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    /**
     * Preload update status for the overview banner
     */
    public function preloadUpdateStatus(): void
    {
        if ($this->updateStatusLoaded) {
            return;
        }

        $this->checkForUpdates();
    }

    /**
     * Check for pending updates (used by overview banner)
     */
    public function checkForUpdates(): void
    {
        try {
            $this->checkingForUpdates = true;

            $result = $this->gitService->checkForUpdates($this->project);

            if ($result['success']) {
                $this->updateStatus = $result;
                $this->updateStatusLoaded = true;
            }

        } catch (\Exception $e) {
            \Log::warning('ProjectShow: Failed to check for updates', [
                'project_id' => $this->project->id,
                'error' => $e->getMessage(),
            ]);
        } finally {
            $this->checkingForUpdates = false;
        }
    }

    #[On('deployment-completed')]
    public function onDeploymentCompleted(): void
    {
        $this->project->refresh();
        $this->updateStatusLoaded = false;
        $this->checkForUpdates();
    }

    public function openDeployModal(): void
    {
        $this->showDeployModal = true;
    }

    public function closeDeployModal(): void
    {
        $this->showDeployModal = false;
    }

    public function deploy(): mixed
    {
        try {
            $activeDeployment = $this->project->deployments()
                ->whereIn('status', ['pending', 'running'])
                ->first();

            if ($activeDeployment) {
                session()->flash('error', 'A deployment is already in progress. Please wait for it to complete or cancel it first.');
                return $this->redirect(route('deployments.show', $activeDeployment), navigate: true);
            }

            $deployment = Deployment::create([
                'user_id' => auth()->id(),
                'project_id' => $this->project->id,
                'server_id' => $this->project->server_id,
                'branch' => $this->project->branch,
                'status' => 'pending',
                'triggered_by' => 'manual',
                'started_at' => now(),
            ]);

            \App\Jobs\DeployProjectJob::dispatch($deployment);

            session()->flash('message', 'Deployment started successfully!');

            return $this->redirect(route('deployments.show', $deployment), navigate: true);

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to start deployment: '.$e->getMessage());
            return null;
        }
    }

    public function startProject(): void
    {
        try {
            $result = $this->dockerService->startContainer($this->project);

            if ($result['success']) {
                $this->project->update(['status' => 'running']);
                $this->project->refresh();
                session()->flash('message', 'Project started successfully');
            } else {
                session()->flash('error', 'Failed to start project: '.($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to start project: '.$e->getMessage());
        }
    }

    public function stopProject(): void
    {
        try {
            $result = $this->dockerService->stopContainer($this->project);

            if ($result['success']) {
                $this->project->update(['status' => 'stopped']);
                $this->project->refresh();
                session()->flash('message', 'Project stopped successfully');
            } else {
                session()->flash('error', 'Failed to stop project: '.($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to stop project: '.$e->getMessage());
        }
    }

    public function render(): View
    {
        $deployments = $this->project->deployments()
            ->select(['id', 'project_id', 'user_id', 'server_id', 'status', 'branch', 'commit_hash', 'commit_message', 'created_at', 'started_at', 'completed_at', 'triggered_by'])
            ->with([
                'user:id,name',
                'server:id,name'
            ])
            ->latest()
            ->paginate($this->deploymentsPerPage, ['*'], 'deploymentsPage');

        return view('livewire.projects.project-show', [
            'deployments' => $deployments,
            'domains' => $this->project->domains,
        ]);
    }
}
