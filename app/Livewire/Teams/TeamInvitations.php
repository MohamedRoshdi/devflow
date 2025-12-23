<?php

declare(strict_types=1);

namespace App\Livewire\Teams;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Services\TeamService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TeamInvitations extends Component
{
    public Team $team;

    public bool $showInviteModal = false;

    public string $inviteEmail = '';

    public string $inviteRole = 'member';

    private TeamService $teamService;

    public function boot(TeamService $teamService): void
    {
        $this->teamService = $teamService;
    }

    public function mount(Team $team): void
    {
        $this->team = $team;
    }

    /**
     * @return Collection<int, array{id: int, email: string, role: string, invited_by: string, created_at: string, expires_at: string}>
     */
    #[Computed]
    public function invitations(): Collection
    {
        return $this->team->invitations()
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->with('inviter')
            ->latest()
            ->get()
            ->map(function (TeamInvitation $invitation) {
                return [
                    'id' => $invitation->id,
                    'email' => $invitation->email,
                    'role' => ucfirst($invitation->role),
                    'invited_by' => $invitation->inviter->name,
                    'created_at' => $invitation->created_at->format('M d, Y'),
                    'expires_at' => $invitation->expires_at->diffForHumans(),
                ];
            });
    }

    public function openInviteModal(): void
    {
        if (! $this->canManageMembers()) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'You do not have permission to invite members.',
            ]);

            return;
        }

        $this->showInviteModal = true;
    }

    public function closeInviteModal(): void
    {
        $this->showInviteModal = false;
        $this->reset(['inviteEmail', 'inviteRole']);
        $this->resetValidation();
    }

    public function inviteMember(): void
    {
        $this->validate([
            'inviteEmail' => 'required|email',
            'inviteRole' => 'required|in:admin,member,viewer',
        ]);

        $user = Auth::user();
        if (! $user) {
            return;
        }

        try {
            $this->teamService->inviteMember(
                $this->team,
                $this->inviteEmail,
                $this->inviteRole,
                $user
            );

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Invitation sent successfully!',
            ]);

            $this->closeInviteModal();
            unset($this->invitations);
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function cancelInvitation(int $invitationId): void
    {
        if (! $this->canManageMembers()) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'You do not have permission to cancel invitations.',
            ]);

            return;
        }

        $invitation = TeamInvitation::findOrFail($invitationId);

        if ($invitation->team_id !== $this->team->id) {
            abort(403);
        }

        try {
            $this->teamService->cancelInvitation($invitation);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Invitation cancelled.',
            ]);

            unset($this->invitations);
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function resendInvitation(int $invitationId): void
    {
        if (! $this->canManageMembers()) {
            return;
        }

        $invitation = TeamInvitation::findOrFail($invitationId);

        if ($invitation->team_id !== $this->team->id) {
            abort(403);
        }

        try {
            $this->teamService->resendInvitation($invitation);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Invitation resent!',
            ]);

            unset($this->invitations);
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function canManageMembers(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        $role = $this->team->getMemberRole($user);

        return in_array($role, ['owner', 'admin']);
    }

    public function render(): View
    {
        return view('livewire.teams.team-invitations');
    }
}
