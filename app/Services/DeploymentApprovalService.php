<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AuditServiceInterface;
use App\Contracts\NotificationServiceInterface;
use App\Models\Deployment;
use App\Models\DeploymentApproval;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DeploymentApprovalService
{
    public function __construct(
        private readonly AuditServiceInterface $auditService,
        private readonly NotificationServiceInterface $notificationService
    ) {}

    /**
     * Check if a deployment requires approval based on project settings
     */
    public function requiresApproval(Deployment $deployment): bool
    {
        $project = $deployment->project;

        if (! $project || ! $project->requires_approval) {
            return false;
        }

        $settings = $project->approval_settings ?? [];

        // Check environment-specific requirements
        if (isset($settings['environments']) && is_array($settings['environments'])) {
            $requiresForEnv = in_array($deployment->project?->environment ?? 'production', $settings['environments']);
            if (! $requiresForEnv) {
                return false;
            }
        }

        // Check branch-specific requirements
        if (isset($settings['branches']) && is_array($settings['branches'])) {
            $requiresForBranch = in_array($deployment->branch, $settings['branches']);
            if (! $requiresForBranch) {
                return false;
            }
        }

        return true;
    }

    /**
     * Request approval for a deployment
     */
    public function requestApproval(Deployment $deployment, User $requestedBy): DeploymentApproval
    {
        $approval = DB::transaction(function () use ($deployment, $requestedBy) {
            $approval = DeploymentApproval::create([
                'deployment_id' => $deployment->id,
                'requested_by' => $requestedBy->id,
                'status' => 'pending',
                'requested_at' => now(),
            ]);

            // Log the request
            $this->auditService->log(
                'deployment.approval_requested',
                $deployment,
                null,
                ['approval_id' => $approval->id, 'requested_by' => $requestedBy->name]
            );

            // Update deployment status
            $deployment->update(['status' => 'pending_approval']);

            return $approval;
        });

        // Notify approvers
        $this->notifyApprovers($approval);

        return $approval;
    }

    /**
     * Approve a deployment
     */
    public function approve(DeploymentApproval $approval, User $approver, ?string $notes = null): void
    {
        if (! $this->canApprove($approver, $approval->deployment)) {
            throw new \Exception('You do not have permission to approve this deployment');
        }

        if (! $approval->isPending()) {
            throw new \Exception('This approval has already been processed');
        }

        DB::transaction(function () use ($approval, $approver, $notes) {
            $approval->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'notes' => $notes,
                'responded_at' => now(),
            ]);

            // Log the approval
            $this->auditService->log(
                'deployment.approved',
                $approval->deployment,
                ['status' => 'pending_approval'],
                ['status' => 'approved', 'approved_by' => $approver->name]
            );

            // Update deployment status
            $approval->deployment->update(['status' => 'pending']);
        });

        // Notify the requester
        $this->notificationService->notifyDeploymentEvent(
            $approval->deployment,
            'deployment.approved'
        );
    }

    /**
     * Reject a deployment
     */
    public function reject(DeploymentApproval $approval, User $rejector, string $reason): void
    {
        if (! $this->canApprove($rejector, $approval->deployment)) {
            throw new \Exception('You do not have permission to reject this deployment');
        }

        if (! $approval->isPending()) {
            throw new \Exception('This approval has already been processed');
        }

        DB::transaction(function () use ($approval, $rejector, $reason) {
            $approval->update([
                'status' => 'rejected',
                'approved_by' => $rejector->id,
                'notes' => $reason,
                'responded_at' => now(),
            ]);

            // Log the rejection
            $this->auditService->log(
                'deployment.rejected',
                $approval->deployment,
                ['status' => 'pending_approval'],
                ['status' => 'rejected', 'rejected_by' => $rejector->name]
            );

            // Update deployment status
            $approval->deployment->update([
                'status' => 'failed',
                'error_message' => 'Deployment rejected: '.$reason,
            ]);
        });

        // Notify the requester
        $this->notificationService->notifyDeploymentEvent(
            $approval->deployment,
            'deployment.rejected'
        );
    }

    /**
     * Get pending approvals for a user
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\DeploymentApproval>
     */
    public function getPendingApprovals(User $user): Collection
    {
        // Get approvals user can approve based on permissions
        $query = DeploymentApproval::with(['deployment.project', 'requester'])
            ->where('status', 'pending');

        // If user has specific approval permission, show all
        if ($user->can('approve_all_deployments')) {
            return $query->latest('requested_at')->get();
        }

        // Otherwise, show only for projects user has access to
        if ($user->can('approve_deployments')) {
            $projectIds = $user->projects()->pluck('id');

            return $query->whereHas('deployment', function ($q) use ($projectIds) {
                $q->whereIn('project_id', $projectIds);
            })->latest('requested_at')->get();
        }

        return collect();
    }

    /**
     * Get approval statistics
     */
    public function getApprovalStats(?User $user = null): array
    {
        $query = DeploymentApproval::query();

        if ($user) {
            $projectIds = $user->projects()->pluck('id');
            $query->whereHas('deployment', function ($q) use ($projectIds) {
                $q->whereIn('project_id', $projectIds);
            });
        }

        return [
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
            'expired' => (clone $query)->where('status', 'expired')->count(),
            'total' => $query->count(),
        ];
    }

    /**
     * Check if user can approve a deployment
     */
    private function canApprove(User $user, Deployment $deployment): bool
    {
        // Users can't approve their own deployments
        if ($deployment->user_id === $user->id) {
            return false;
        }

        // Check for global approval permission
        if ($user->can('approve_all_deployments')) {
            return true;
        }

        // Check if user has permission for this project
        if ($user->can('approve_deployments')) {
            return $user->projects()->where('id', $deployment->project_id)->exists();
        }

        return false;
    }

    /**
     * Notify users who can approve this deployment
     */
    private function notifyApprovers(DeploymentApproval $approval): void
    {
        // Find users with approval permissions for this project
        $approvers = User::permission('approve_deployments')
            ->whereHas('projects', function ($q) use ($approval) {
                $q->where('id', $approval->deployment->project_id);
            })
            ->get();

        // Also include global approvers
        $globalApprovers = User::permission('approve_all_deployments')->get();
        $allApprovers = $approvers->merge($globalApprovers)->unique('id');

        foreach ($allApprovers as $approver) {
            // Don't notify the requester
            if ($approver->id === $approval->requested_by) {
                continue;
            }

            // Send notification (email, in-app, etc.)
            $approver->notify(new \App\Notifications\DeploymentApprovalRequested($approval));
        }
    }
}
