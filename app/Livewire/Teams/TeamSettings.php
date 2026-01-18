<?php

declare(strict_types=1);

namespace App\Livewire\Teams;

use App\Mail\TeamInvitation as TeamInvitationMail;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class TeamSettings extends Component
{
    use WithFileUploads;

    public Team $team;

    public string $activeTab = 'general';

    // Team settings
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:500')]
    public string $description = '';

    /** @var UploadedFile|null */
    public $avatar = null;

    // Transfer ownership
    public bool $showTransferModal = false;

    public ?int $newOwnerId = null;

    // Delete team
    public bool $showDeleteModal = false;

    public string $deleteConfirmation = '';

    // Invite member
    public bool $showInviteModal = false;

    #[Validate('required|email')]
    public string $inviteEmail = '';

    #[Validate('required|in:admin,member,viewer')]
    public string $inviteRole = 'member';

    private TeamService $teamService;

    public function boot(TeamService $teamService): void
    {
        $this->teamService = $teamService;
    }

    public function mount(Team $team): void
    {
        $user = Auth::user();

        // Check access
        if (! $user || ! $team->hasMember($user)) {
            abort(403, 'You do not have access to this team.');
        }

        $this->team = $team;
        $this->name = $team->name;
        $this->description = $team->description ?? '';
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function members()
    {
        $user = Auth::user();
        $canManage = $user && $this->canManageTeam($user);

        return $this->team->teamMembers()
            ->with('user')
            ->get()
            ->map(function ($member) use ($canManage) {
                $isOwner = $member->user_id === $this->team->owner_id;

                return [
                    'id' => $member->id,
                    'user_id' => $member->user_id,
                    'user' => $member->user,
                    'role' => $member->role,
                    'joined_at' => $member->joined_at,
                    'is_owner' => $isOwner,
                    'can_edit' => $canManage && ! $isOwner,
                ];
            });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, TeamInvitation>
     */
    #[Computed]
    public function invitations()
    {
        return TeamInvitation::where('team_id', $this->team->id)
            ->whereNull('accepted_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, TeamMember>
     */
    #[Computed]
    public function potentialOwners()
    {
        return $this->team->teamMembers()
            ->where('user_id', '!=', $this->team->owner_id)
            ->get();
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    #[On('team-updated')]
    public function onTeamUpdated(): void
    {
        $this->team->refresh();
        $this->name = $this->team->name;
        $this->description = $this->team->description ?? '';
    }

    public function updateTeam(): void
    {
        $user = Auth::user();

        // Check if user has permission to update
        if (! $user || ! $this->canManageTeam($user)) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'You do not have permission to update team settings.',
            ]);

            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        try {
            $data = [
                'name' => $this->name,
                'description' => $this->description,
            ];

            if ($this->avatar) {
                $path = $this->avatar->store('team-avatars', 'public');
                $data['avatar'] = $path;
            }

            $this->team->update($data);
            $this->team->refresh();
            $this->avatar = null;

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Team updated successfully!',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to update team: ' . $e->getMessage(),
            ]);
        }
    }

    public function openInviteModal(): void
    {
        $user = Auth::user();
        if (! $user || ! $this->canManageTeam($user)) {
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
        $this->inviteEmail = '';
        $this->inviteRole = 'member';
    }

    public function inviteMember(): void
    {
        $user = Auth::user();
        if (! $user || ! $this->canManageTeam($user)) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'You do not have permission to invite members.',
            ]);

            return;
        }

        $this->validate([
            'inviteEmail' => ['required', 'email'],
            'inviteRole' => ['required', 'in:admin,member,viewer'],
        ]);

        // Check if already a member
        $existingUser = User::where('email', $this->inviteEmail)->first();
        if ($existingUser && $this->team->hasMember($existingUser)) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'This user is already a team member.',
            ]);

            return;
        }

        try {
            $invitation = TeamInvitation::create([
                'team_id' => $this->team->id,
                'email' => $this->inviteEmail,
                'role' => $this->inviteRole,
                'invited_by' => $user->id,
                'token' => \Illuminate\Support\Str::random(64),
                'expires_at' => now()->addDays(7),
            ]);

            // Send invitation email
            Mail::to($this->inviteEmail)->send(new TeamInvitationMail($invitation));

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Invitation sent successfully!',
            ]);

            $this->closeInviteModal();
            unset($this->invitations);
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to send invitation: ' . $e->getMessage(),
            ]);
        }
    }

    public function cancelInvitation(int $invitationId): void
    {
        $user = Auth::user();
        if (! $user || ! $this->canManageTeam($user)) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'You do not have permission to cancel invitations.',
            ]);

            return;
        }

        $invitation = TeamInvitation::where('id', $invitationId)
            ->where('team_id', $this->team->id)
            ->first();

        if (! $invitation) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Invitation not found.',
            ]);

            return;
        }

        $invitation->delete();
        unset($this->invitations);

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Invitation cancelled.',
        ]);
    }

    public function resendInvitation(int $invitationId): void
    {
        $user = Auth::user();
        if (! $user || ! $this->canManageTeam($user)) {
            return;
        }

        $invitation = TeamInvitation::where('id', $invitationId)
            ->where('team_id', $this->team->id)
            ->first();

        if (! $invitation) {
            return;
        }

        try {
            $invitation->update([
                'expires_at' => now()->addDays(7),
                'token' => \Illuminate\Support\Str::random(64),
            ]);

            Mail::to($invitation->email)->send(new TeamInvitationMail($invitation));

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Invitation resent successfully!',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to resend invitation.',
            ]);
        }
    }

    public function removeMember(int $userId): void
    {
        $user = Auth::user();
        if (! $user || ! $this->canManageTeam($user)) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'You do not have permission to remove members.',
            ]);

            return;
        }

        // Cannot remove owner
        if ($userId === $this->team->owner_id) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Cannot remove the team owner.',
            ]);

            return;
        }

        TeamMember::where('team_id', $this->team->id)
            ->where('user_id', $userId)
            ->delete();

        unset($this->members);

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Member removed successfully.',
        ]);
    }

    public function updateMemberRole(int $userId, string $role): void
    {
        $user = Auth::user();
        if (! $user || ! $this->canManageTeam($user)) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'You do not have permission to update member roles.',
            ]);

            return;
        }

        // Cannot change owner role
        if ($userId === $this->team->owner_id) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Cannot change the owner\'s role.',
            ]);

            return;
        }

        if (! in_array($role, ['admin', 'member', 'viewer'])) {
            return;
        }

        TeamMember::where('team_id', $this->team->id)
            ->where('user_id', $userId)
            ->update(['role' => $role]);

        unset($this->members);

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => 'Member role updated.',
        ]);
    }

    public function openTransferModal(): void
    {
        $user = Auth::user();
        if (! $user || ! $this->team->isOwner($user)) {
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
        $user = Auth::user();
        if (! $user || ! $this->team->isOwner($user)) {
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
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function openDeleteModal(): void
    {
        $user = Auth::user();
        if (! $user || ! $this->team->isOwner($user)) {
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

    public function deleteTeam(): RedirectResponse|Redirector|null
    {
        $user = Auth::user();
        if (! $user || ! $this->team->isOwner($user)) {
            return null;
        }

        if ($this->deleteConfirmation !== $this->team->name) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Please type the team name to confirm deletion.',
            ]);

            return null;
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

            return null;
        }
    }

    /**
     * Check if user can manage team (owner or admin)
     */
    private function canManageTeam(User $user): bool
    {
        if ($this->team->isOwner($user)) {
            return true;
        }

        $member = TeamMember::where('team_id', $this->team->id)
            ->where('user_id', $user->id)
            ->first();

        return $member && $member->role === 'admin';
    }

    public function render(): View
    {
        return view('livewire.teams.team-settings');
    }
}
