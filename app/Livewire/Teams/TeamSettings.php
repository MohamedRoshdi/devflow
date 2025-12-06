<?php

declare(strict_types=1);

namespace App\Livewire\Teams;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class TeamSettings extends Component
{
    use WithFileUploads;

    public Team $team;

    public string $activeTab = 'general';

    // General settings
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:500')]
    public string $description = '';

    #[Validate('nullable|image|max:2048')]
    public mixed $avatar = null;

    // Invitation
    public bool $showInviteModal = false;

    #[Validate('required|email')]
    public string $inviteEmail = '';

    #[Validate('required|in:admin,member,viewer')]
    public string $inviteRole = 'member';

    // Transfer ownership
    public bool $showTransferModal = false;

    public ?int $newOwnerId = null;

    // Delete team
    public bool $showDeleteModal = false;

    public string $deleteConfirmation = '';

    public function __construct(
        private readonly TeamService $teamService
    ) {}

    public function mount(Team $team): void
    {
        // Check access
        if (! $team->hasMember(Auth::user())) {
            abort(403, 'You do not have access to this team.');
        }

        $this->team = $team;
        $this->name = $team->name;
        $this->description = $team->description ?? '';
    }

    #[Computed]
    public function members()
    {
        return $this->team->teamMembers()
            ->with(['user', 'inviter'])
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'user_id' => $member->user->id,
                    'name' => $member->user->name,
                    'email' => $member->user->email,
                    'avatar' => $member->user->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($member->user->name),
                    'role' => $member->role,
                    'joined_at' => $member->joined_at?->format('M d, Y'),
                    'is_owner' => $member->role === 'owner',
                    'can_edit' => $this->canManageMembers() && $member->role !== 'owner',
                ];
            });
    }

    #[Computed]
    public function invitations()
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

    #[Computed]
    public function potentialOwners()
    {
        return $this->team->members()
            ->where('user_id', '!=', $this->team->owner_id)
            ->get();
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function updateTeam(): void
    {
        if (! $this->canManageTeam()) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'You do not have permission to update team settings.',
            ]);

            return;
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $data = [
                'name' => $this->name,
                'description' => $this->description,
            ];

            // Handle avatar upload
            if ($this->avatar) {
                $data['avatar'] = $this->avatar->store('teams', 'public');
            }

            $this->team->update($data);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Team updated successfully!',
            ]);

            $this->avatar = null;
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to update team: '.$e->getMessage(),
            ]);
        }
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

        try {
            $this->teamService->inviteMember(
                $this->team,
                $this->inviteEmail,
                $this->inviteRole,
                Auth::user()
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

    public function removeMember(int $userId): void
    {
        if (! $this->canManageMembers()) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'You do not have permission to remove members.',
            ]);

            return;
        }

        $user = User::findOrFail($userId);

        try {
            $this->teamService->removeMember($this->team, $user);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Member removed successfully.',
            ]);

            unset($this->members);
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function updateRole(int $userId, string $role): void
    {
        if (! $this->canManageMembers()) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'You do not have permission to update roles.',
            ]);

            return;
        }

        $user = User::findOrFail($userId);

        try {
            $this->teamService->updateRole($this->team, $user, $role);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Role updated successfully.',
            ]);

            unset($this->members);
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function openTransferModal(): void
    {
        if (! $this->team->isOwner(Auth::user())) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Only the team owner can transfer ownership.',
            ]);

            return;
        }

        $this->showTransferModal = true;
    }

    public function closeTransferModal(): void
    {
        $this->showTransferModal = false;
        $this->newOwnerId = null;
    }

    public function transferOwnership(): void
    {
        if (! $this->team->isOwner(Auth::user())) {
            return;
        }

        if (! $this->newOwnerId) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Please select a new owner.',
            ]);

            return;
        }

        $newOwner = User::findOrFail($this->newOwnerId);

        try {
            $this->teamService->transferOwnership($this->team, $newOwner);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Ownership transferred successfully.',
            ]);

            $this->closeTransferModal();
            $this->team->refresh();
            unset($this->members);
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function openDeleteModal(): void
    {
        if (! $this->team->isOwner(Auth::user())) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Only the team owner can delete the team.',
            ]);

            return;
        }

        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deleteConfirmation = '';
    }

    public function deleteTeam()
    {
        if (! $this->team->isOwner(Auth::user())) {
            return;
        }

        if ($this->deleteConfirmation !== $this->team->name) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Please type the team name to confirm deletion.',
            ]);

            return;
        }

        try {
            $this->teamService->deleteTeam($this->team);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Team deleted successfully.',
            ]);

            return redirect()->route('teams.index');
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function canManageTeam(): bool
    {
        $role = $this->team->getMemberRole(Auth::user());

        return in_array($role, ['owner', 'admin']);
    }

    private function canManageMembers(): bool
    {
        $role = $this->team->getMemberRole(Auth::user());

        return in_array($role, ['owner', 'admin']);
    }

    public function render()
    {
        return view('livewire.teams.team-settings');
    }
}
