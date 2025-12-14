<?php

declare(strict_types=1);

namespace App\Livewire\Teams;

use App\Models\Team;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class TeamSettings extends Component
{
    public Team $team;

    public string $activeTab = 'general';

    // Transfer ownership
    public bool $showTransferModal = false;

    public ?int $newOwnerId = null;

    // Delete team
    public bool $showDeleteModal = false;

    public string $deleteConfirmation = '';

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
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
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

    #[On('team-updated')]
    public function onTeamUpdated(): void
    {
        $this->team->refresh();
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

    public function render(): View
    {
        return view('livewire.teams.team-settings');
    }
}
