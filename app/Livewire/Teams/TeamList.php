<?php

declare(strict_types=1);

namespace App\Livewire\Teams;

use App\Models\Team;
use App\Services\TeamService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class TeamList extends Component
{
    use WithFileUploads;

    public bool $showCreateModal = false;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:500')]
    public string $description = '';

    #[Validate('nullable|image|max:2048')]
    public $avatar = null;

    public function __construct(
        private readonly TeamService $teamService
    ) {}

    #[Computed]
    public function teams()
    {
        return Auth::user()->teams()
            ->withCount('members')
            ->with(['owner'])
            ->latest()
            ->get()
            ->map(function (Team $team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'description' => $team->description,
                    'avatar_url' => $team->avatar_url,
                    'members_count' => $team->members_count,
                    'role' => $team->getMemberRole(Auth::user()),
                    'is_owner' => $team->isOwner(Auth::user()),
                    'is_current' => Auth::user()->current_team_id === $team->id,
                ];
            });
    }

    public function openCreateModal(): void
    {
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->reset(['name', 'description', 'avatar']);
        $this->resetValidation();
    }

    public function createTeam()
    {
        $this->validate();

        try {
            $data = [
                'name' => $this->name,
                'description' => $this->description,
            ];

            // Handle avatar upload
            if ($this->avatar) {
                $data['avatar'] = $this->avatar->store('teams', 'public');
            }

            $team = $this->teamService->createTeam(Auth::user(), $data);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Team created successfully!',
            ]);

            $this->closeCreateModal();
            unset($this->teams);

            // Redirect to team settings
            return redirect()->route('teams.settings', $team);
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to create team: ' . $e->getMessage(),
            ]);
        }
    }

    public function switchTeam(int $teamId)
    {
        $team = Team::findOrFail($teamId);

        if (!$team->hasMember(Auth::user())) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'You do not have access to this team.',
            ]);
            return;
        }

        Auth::user()->update(['current_team_id' => $teamId]);

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => "Switched to {$team->name}",
        ]);

        unset($this->teams);
        $this->dispatch('team-switched');

        return redirect()->route('dashboard');
    }

    public function deleteTeam(int $teamId): void
    {
        $team = Team::findOrFail($teamId);

        if (!$team->isOwner(Auth::user())) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Only the team owner can delete the team.',
            ]);
            return;
        }

        try {
            $this->teamService->deleteTeam($team);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Team deleted successfully.',
            ]);

            unset($this->teams);
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to delete team: ' . $e->getMessage(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.teams.team-list');
    }
}
