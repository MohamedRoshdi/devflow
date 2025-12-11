<?php

namespace App\Livewire\Deployments;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\Server;
use App\Services\RollbackService;
use Illuminate\Support\Facades\Process;
use Livewire\Attributes\On;
use Livewire\Component;

class DeploymentRollback extends Component
{
    public Project $project;

    /** @var array<int, array<string, mixed>> */
    public array $rollbackPoints = [];

    /** @var array<string, mixed>|null */
    public ?array $selectedDeployment = null;

    public bool $showRollbackModal = false;

    public bool $rollbackInProgress = false;

    /** @var array<string, mixed>|null */
    public ?array $comparisonData = null;

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->loadRollbackPoints();
    }

    /**
     * Build SSH command for remote execution
     */
    protected function buildSSHCommand(Server $server, string $remoteCommand): string
    {
        $sshOptions = [
            '-o StrictHostKeyChecking=no',
            '-o UserKnownHostsFile=/dev/null',
            '-p '.$server->port,
        ];

        $escapedCommand = str_replace("'", "'\\''", $remoteCommand);

        return sprintf(
            "ssh %s %s@%s '%s'",
            implode(' ', $sshOptions),
            $server->username,
            $server->ip_address,
            $escapedCommand
        );
    }

    /**
     * Execute command on server via SSH
     */
    protected function executeOnServer(string $command): string
    {
        $server = $this->project->server;
        $sshCommand = $this->buildSSHCommand($server, $command);
        $result = Process::timeout(30)->run($sshCommand);

        return $result->output();
    }

    public function loadRollbackPoints(): void
    {
        $rollbackService = app(RollbackService::class);
        $this->rollbackPoints = $rollbackService->getRollbackPoints($this->project, 20);
    }

    public function selectForRollback(int $deploymentId): void
    {
        /** @var array<string, mixed>|null $selected */
        $selected = collect($this->rollbackPoints)
            ->firstWhere('id', $deploymentId);

        $this->selectedDeployment = is_array($selected) ? $selected : null;

        if ($this->selectedDeployment) {
            $this->loadComparison();
            $this->showRollbackModal = true;
        }
    }

    public function loadComparison(): void
    {
        if (! $this->selectedDeployment) {
            return;
        }

        $currentDeployment = $this->project->deployments()
            ->where('status', 'success')
            ->latest()
            ->first();

        $targetDeployment = Deployment::find($this->selectedDeployment['id']);

        if ($currentDeployment && $targetDeployment) {
            $projectPath = "/var/www/{$this->project->slug}";

            // Get commit diff via SSH
            $diffCommand = "cd {$projectPath} && git log --oneline {$targetDeployment->commit_hash}..{$currentDeployment->commit_hash} 2>/dev/null || echo ''";
            $diffOutput = $this->executeOnServer($diffCommand);

            $this->comparisonData = [
                'current' => [
                    'commit' => $currentDeployment->commit_hash ? substr($currentDeployment->commit_hash, 0, 7) : 'N/A',
                    'message' => $currentDeployment->commit_message ?? 'No message',
                    'date' => $currentDeployment->created_at->format('M d, Y H:i'),
                ],
                'target' => [
                    'commit' => $targetDeployment->commit_hash ? substr($targetDeployment->commit_hash, 0, 7) : 'N/A',
                    'message' => $targetDeployment->commit_message ?? 'No message',
                    'date' => $targetDeployment->created_at?->format('M d, Y H:i') ?? 'Unknown',
                ],
                'commits_to_remove' => array_filter(explode("\n", trim($diffOutput))),
                'files_changed' => $this->getFilesChanged($targetDeployment->commit_hash, $currentDeployment->commit_hash),
            ];
        }
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getFilesChanged(?string $fromCommit, ?string $toCommit): array
    {
        if (! $fromCommit || ! $toCommit) {
            return [];
        }

        $projectPath = "/var/www/{$this->project->slug}";
        $command = "cd {$projectPath} && git diff --name-status {$fromCommit}..{$toCommit} 2>/dev/null | head -20";
        $output = $this->executeOnServer($command);

        /** @var array<int, array<string, string>> $files */
        $files = [];
        foreach (explode("\n", trim($output)) as $line) {
            if (preg_match('/^([AMD])\s+(.+)$/', $line, $matches)) {
                $files[] = [
                    'status' => $matches[1] === 'A' ? 'added' : ($matches[1] === 'M' ? 'modified' : 'deleted'),
                    'path' => $matches[2],
                ];
            }
        }

        return array_slice($files, 0, 10);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|void
     */
    public function confirmRollback()
    {
        if (! $this->selectedDeployment || ! isset($this->selectedDeployment['can_rollback']) || ! $this->selectedDeployment['can_rollback']) {
            return;
        }

        $this->rollbackInProgress = true;

        try {
            $deployment = Deployment::find($this->selectedDeployment['id']);

            if (! $deployment) {
                $this->dispatch('notification',
                    type: 'error',
                    message: 'Deployment not found.'
                );

                return;
            }

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
                    message: 'Rollback failed: '.($result['error'] ?? 'Unknown error')
                );
            }
        } catch (\Exception $e) {
            $this->dispatch('notification',
                type: 'error',
                message: 'Rollback failed: '.$e->getMessage()
            );
        } finally {
            $this->rollbackInProgress = false;
            $this->showRollbackModal = false;
        }
    }

    public function cancelRollback(): void
    {
        $this->showRollbackModal = false;
        $this->selectedDeployment = null;
        $this->comparisonData = null;
    }

    #[On('deployment-completed')]
    public function onDeploymentCompleted(): void
    {
        $this->loadRollbackPoints();
    }

    /**
     * @return \Illuminate\View\View
     */
    public function render(): \Illuminate\View\View
    {
        return view('livewire.deployments.deployment-rollback');
    }
}
