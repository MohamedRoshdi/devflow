<?php

declare(strict_types=1);

namespace App\Livewire\Pipelines;

use App\Models\PipelineRun;
use App\Models\PipelineStageRun;
use App\Services\CICD\PipelineExecutionService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class PipelineRunShow extends Component
{
    public PipelineRun $pipelineRun;

    public ?int $expandedStageId = null;

    public bool $autoScroll = true;

    /**
     * Mount component
     */
    public function mount(PipelineRun $pipelineRun): void
    {
        $this->pipelineRun = $pipelineRun->load([
            'project',
            'deployment',
            'stageRuns.pipelineStage',
        ]);
    }

    /**
     * Get stage runs with details
     */
    #[Computed]
    public function stageRuns()
    {
        return $this->pipelineRun->stageRuns()
            ->with('pipelineStage')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get overall progress percentage
     */
    #[Computed]
    public function progressPercent()
    {
        $totalStages = $this->stageRuns->count();

        if ($totalStages === 0) {
            return 0;
        }

        $completedStages = $this->stageRuns
            ->whereIn('status', ['success', 'failed', 'skipped'])
            ->count();

        return (int) (($completedStages / $totalStages) * 100);
    }

    /**
     * Get summary statistics
     */
    #[Computed]
    public function statistics()
    {
        return [
            'total' => $this->stageRuns->count(),
            'success' => $this->stageRuns->where('status', 'success')->count(),
            'failed' => $this->stageRuns->where('status', 'failed')->count(),
            'running' => $this->stageRuns->where('status', 'running')->count(),
            'pending' => $this->stageRuns->where('status', 'pending')->count(),
            'skipped' => $this->stageRuns->where('status', 'skipped')->count(),
        ];
    }

    /**
     * Toggle stage expansion
     */
    public function toggleStage(int $stageRunId): void
    {
        if ($this->expandedStageId === $stageRunId) {
            $this->expandedStageId = null;
        } else {
            $this->expandedStageId = $stageRunId;
        }
    }

    /**
     * Expand all stages
     */
    public function expandAll(): void
    {
        // Will expand all in the view
        $this->expandedStageId = -1;
    }

    /**
     * Collapse all stages
     */
    public function collapseAll(): void
    {
        $this->expandedStageId = null;
    }

    /**
     * Toggle auto-scroll
     */
    public function toggleAutoScroll(): void
    {
        $this->autoScroll = ! $this->autoScroll;
    }

    /**
     * Cancel running pipeline
     */
    public function cancelPipeline(): void
    {
        if (! $this->pipelineRun->isRunning()) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Pipeline is not running',
            ]);

            return;
        }

        $pipelineService = app(PipelineExecutionService::class);
        $pipelineService->cancelPipeline($this->pipelineRun);

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Pipeline cancelled',
        ]);

        $this->refreshPipeline();
    }

    /**
     * Retry failed pipeline
     */
    public function retryPipeline(): void
    {
        if (! $this->pipelineRun->isComplete()) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Cannot retry a running pipeline',
            ]);

            return;
        }

        // Create new deployment
        $deployment = \App\Models\Deployment::create([
            'project_id' => $this->pipelineRun->project_id,
            'server_id' => $this->pipelineRun->project->server_id,
            'user_id' => auth()->id(),
            'status' => 'pending',
            'triggered_by' => 'manual',
            'branch' => $this->pipelineRun->project->branch,
        ]);

        \App\Jobs\DeployProjectJob::dispatch($deployment);

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Pipeline retry started',
        ]);

        $this->redirect(route('projects.show', $this->pipelineRun->project->slug));
    }

    /**
     * Listen for pipeline stage updates
     */
    #[On('echo:pipeline.{pipelineRun.id},pipeline.stage.updated')]
    public function onStageUpdated($data): void
    {
        $this->refreshPipeline();

        // Auto-expand running stage
        if (isset($data['stage_run_id']) && $data['status'] === 'running') {
            $this->expandedStageId = $data['stage_run_id'];
        }
    }

    /**
     * Refresh pipeline data
     */
    public function refreshPipeline(): void
    {
        $this->pipelineRun->refresh();
        unset($this->stageRuns);
        unset($this->progressPercent);
        unset($this->statistics);
        $this->dispatch('$refresh');
    }

    /**
     * Download stage output
     */
    public function downloadStageOutput(int $stageRunId): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $stageRun = PipelineStageRun::findOrFail($stageRunId);

        if ($stageRun->pipeline_run_id !== $this->pipelineRun->id) {
            abort(403);
        }

        $filename = sprintf(
            'stage-%s-%s.log',
            $stageRun->pipelineStage->name,
            $stageRun->id
        );

        return response()->streamDownload(function () use ($stageRun) {
            echo $stageRun->output ?? 'No output available';
        }, $filename, [
            'Content-Type' => 'text/plain',
        ]);
    }

    public function render()
    {
        return view('livewire.pipelines.pipeline-run-show');
    }
}
