<?php

declare(strict_types=1);

namespace App\Livewire\Deployments;

use App\Models\DeploymentApproval;
use App\Models\Project;
use App\Services\DeploymentApprovalService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class DeploymentApprovals extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'pending';

    public ?int $projectFilter = null;

    public ?int $selectedApprovalId = null;

    public string $approvalNotes = '';

    public string $rejectionReason = '';

    public bool $showApproveModal = false;

    public bool $showRejectModal = false;

    #[Computed]
    public function pendingApprovals()
    {
        return app(DeploymentApprovalService::class)->getPendingApprovals(auth()->user());
    }

    #[Computed]
    public function approvals()
    {
        $query = DeploymentApproval::with([
            'deployment.project',
            'deployment.user',
            'requester',
            'approver',
        ])->latest('requested_at');

        // Filter by status
        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        // Filter by project
        if ($this->projectFilter) {
            $query->whereHas('deployment', function ($q) {
                $q->where('project_id', $this->projectFilter);
            });
        }

        // Search
        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('deployment.project', function ($sq) {
                    $sq->where('name', 'like', "%{$this->search}%");
                })
                    ->orWhereHas('requester', function ($sq) {
                        $sq->where('name', 'like', "%{$this->search}%");
                    })
                    ->orWhereHas('deployment', function ($sq) {
                        $sq->where('branch', 'like', "%{$this->search}%");
                    });
            });
        }

        return $query->paginate(20);
    }

    #[Computed]
    public function projects()
    {
        $user = auth()->user();
        if ($user === null) {
            return collect();
        }

        // Get projects user has access to
        if ($user->can('approve_all_deployments')) {
            return Project::orderBy('name')->get(['id', 'name']);
        }

        return $user->projects()->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function stats()
    {
        return app(DeploymentApprovalService::class)->getApprovalStats(auth()->user());
    }

    public function openApproveModal(int $approvalId): void
    {
        $this->selectedApprovalId = $approvalId;
        $this->approvalNotes = '';
        $this->showApproveModal = true;
    }

    public function openRejectModal(int $approvalId): void
    {
        $this->selectedApprovalId = $approvalId;
        $this->rejectionReason = '';
        $this->showRejectModal = true;
    }

    public function approve(): void
    {
        $this->validate([
            'approvalNotes' => 'nullable|string|max:1000',
        ]);

        try {
            $approval = DeploymentApproval::findOrFail($this->selectedApprovalId);
            app(DeploymentApprovalService::class)->approve(
                $approval,
                auth()->user(),
                $this->approvalNotes ?: null
            );

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Deployment approved successfully',
            ]);

            $this->showApproveModal = false;
            $this->reset('selectedApprovalId', 'approvalNotes');
            unset($this->approvals, $this->pendingApprovals, $this->stats);
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function reject(): void
    {
        $this->validate([
            'rejectionReason' => 'required|string|max:1000',
        ]);

        try {
            $approval = DeploymentApproval::findOrFail($this->selectedApprovalId);
            app(DeploymentApprovalService::class)->reject(
                $approval,
                auth()->user(),
                $this->rejectionReason
            );

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Deployment rejected',
            ]);

            $this->showRejectModal = false;
            $this->reset('selectedApprovalId', 'rejectionReason');
            unset($this->approvals, $this->pendingApprovals, $this->stats);
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingProjectFilter(): void
    {
        $this->resetPage();
    }

    #[On('approval-requested')]
    public function onApprovalRequested(): void
    {
        unset($this->approvals, $this->pendingApprovals, $this->stats);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.deployments.deployment-approvals');
    }
}
