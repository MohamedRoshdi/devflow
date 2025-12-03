<?php

declare(strict_types=1);

namespace App\Livewire\Pipelines;

use App\Models\Project;
use App\Models\PipelineRun;
use Livewire\Component;
use Livewire\Attributes\{Computed, On};
use Livewire\WithPagination;

class PipelineRunHistory extends Component
{
    use WithPagination;

    public Project $project;
    public string $statusFilter = 'all';
    public int $perPage = 10;

    /**
     * Get pipeline runs for the project
     */
    #[Computed]
    public function pipelineRuns()
    {
        return PipelineRun::where('project_id', $this->project->id)
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status', $this->statusFilter))
            ->with(['stageRuns.pipelineStage', 'deployment'])
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    /**
     * Get status counts for filters
     */
    #[Computed]
    public function statusCounts()
    {
        $counts = PipelineRun::where('project_id', $this->project->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'all' => array_sum($counts),
            'success' => $counts['success'] ?? 0,
            'failed' => $counts['failed'] ?? 0,
            'running' => $counts['running'] ?? 0,
            'pending' => $counts['pending'] ?? 0,
            'cancelled' => $counts['cancelled'] ?? 0,
        ];
    }

    /**
     * Set status filter
     */
    public function setStatusFilter(string $status): void
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    /**
     * Refresh pipeline runs list
     */
    #[On('pipeline-run-updated')]
    public function refreshRuns(): void
    {
        unset($this->pipelineRuns);
        unset($this->statusCounts);
        $this->dispatch('$refresh');
    }

    /**
     * View pipeline run details
     */
    public function viewRun(int $runId): void
    {
        $this->redirect(route('projects.pipelines.show', [
            'project' => $this->project->slug,
            'run' => $runId,
        ]));
    }

    /**
     * Retry failed pipeline run
     */
    public function retryRun(int $runId): void
    {
        $run = PipelineRun::findOrFail($runId);

        if ($run->project_id !== $this->project->id) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Unauthorized action',
            ]);
            return;
        }

        // Create new deployment and trigger pipeline
        $deployment = \App\Models\Deployment::create([
            'project_id' => $this->project->id,
            'server_id' => $this->project->server_id,
            'user_id' => auth()->id(),
            'status' => 'pending',
            'triggered_by' => 'manual',
            'branch' => $this->project->branch,
        ]);

        \App\Jobs\DeployProjectJob::dispatch($deployment);

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Pipeline retry started',
        ]);

        $this->refreshRuns();
    }

    public function render()
    {
        return view('livewire.pipelines.pipeline-run-history');
    }
}
