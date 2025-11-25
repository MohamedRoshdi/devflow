<?php

namespace App\Livewire\Deployments;

use App\Models\Deployment;
use App\Models\Project;
use App\Services\RollbackService;
use Livewire\Component;
use Livewire\Attributes\On;

class DeploymentRollback extends Component
{
    public Project $project;
    public $rollbackPoints = [];
    public $selectedDeployment = null;
    public $showRollbackModal = false;
    public $rollbackInProgress = false;
    public $comparisonData = null;

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->loadRollbackPoints();
    }

    public function loadRollbackPoints()
    {
        $rollbackService = app(RollbackService::class);
        $this->rollbackPoints = $rollbackService->getRollbackPoints($this->project, 20);
    }

    public function selectForRollback($deploymentId)
    {
        $this->selectedDeployment = collect($this->rollbackPoints)
            ->firstWhere('id', $deploymentId);

        if ($this->selectedDeployment) {
            $this->loadComparison();
            $this->showRollbackModal = true;
        }
    }

    public function loadComparison()
    {
        if (!$this->selectedDeployment) return;

        $currentDeployment = $this->project->deployments()
            ->where('status', 'success')
            ->latest()
            ->first();

        $targetDeployment = Deployment::find($this->selectedDeployment['id']);

        if ($currentDeployment && $targetDeployment) {
            // Get commit diff
            $projectPath = config('devflow.projects_path') . '/' . $this->project->slug;
            $diffCommand = "cd {$projectPath} && git log --oneline {$targetDeployment->commit_hash}..{$currentDeployment->commit_hash}";
            $result = \Illuminate\Support\Facades\Process::run($diffCommand);

            $this->comparisonData = [
                'current' => [
                    'commit' => substr($currentDeployment->commit_hash, 0, 7),
                    'message' => $currentDeployment->commit_message,
                    'date' => $currentDeployment->created_at->format('M d, Y H:i'),
                ],
                'target' => [
                    'commit' => substr($targetDeployment->commit_hash, 0, 7),
                    'message' => $targetDeployment->commit_message,
                    'date' => $targetDeployment->created_at->format('M d, Y H:i'),
                ],
                'commits_to_remove' => explode("\n", trim($result->output())),
                'files_changed' => $this->getFilesChanged($targetDeployment->commit_hash, $currentDeployment->commit_hash),
            ];
        }
    }

    private function getFilesChanged($fromCommit, $toCommit)
    {
        $projectPath = config('devflow.projects_path') . '/' . $this->project->slug;
        $command = "cd {$projectPath} && git diff --name-status {$fromCommit}..{$toCommit}";
        $result = \Illuminate\Support\Facades\Process::run($command);

        $files = [];
        foreach (explode("\n", trim($result->output())) as $line) {
            if (preg_match('/^([AMD])\s+(.+)$/', $line, $matches)) {
                $files[] = [
                    'status' => $matches[1] === 'A' ? 'added' : ($matches[1] === 'M' ? 'modified' : 'deleted'),
                    'path' => $matches[2],
                ];
            }
        }

        return array_slice($files, 0, 10); // Show first 10 files
    }

    public function confirmRollback()
    {
        if (!$this->selectedDeployment || !$this->selectedDeployment['can_rollback']) {
            return;
        }

        $this->rollbackInProgress = true;

        try {
            $deployment = Deployment::find($this->selectedDeployment['id']);
            $rollbackService = app(RollbackService::class);
            $result = $rollbackService->rollbackToDeployment($deployment);

            if ($result['success']) {
                $this->dispatch('notification',
                    type: 'success',
                    message: 'Rollback initiated successfully. Redirecting to deployment page...'
                );

                // Redirect to the rollback deployment page
                return redirect()->route('deployments.show', $result['deployment']);
            } else {
                $this->dispatch('notification',
                    type: 'error',
                    message: 'Rollback failed: ' . $result['error']
                );
            }
        } catch (\Exception $e) {
            $this->dispatch('notification',
                type: 'error',
                message: 'Rollback failed: ' . $e->getMessage()
            );
        } finally {
            $this->rollbackInProgress = false;
            $this->showRollbackModal = false;
        }
    }

    public function cancelRollback()
    {
        $this->showRollbackModal = false;
        $this->selectedDeployment = null;
        $this->comparisonData = null;
    }

    #[On('deployment-completed')]
    public function onDeploymentCompleted()
    {
        $this->loadRollbackPoints();
    }

    public function render()
    {
        return view('livewire.deployments.deployment-rollback');
    }
}