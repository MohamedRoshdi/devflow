<?php

declare(strict_types=1);

namespace App\Livewire\Teams;

use App\Models\Team;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TeamMemberManager extends Component
{
    public Team $team;

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
     * @return Collection<int, array{id: int, user_id: int, name: string, email: string, avatar: string, role: string, joined_at: string|null, is_owner: bool, can_edit: bool}>
     */
    #[Computed]
    public function members(): Collection
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
        return view('livewire.teams.team-member-manager');
    }
}
