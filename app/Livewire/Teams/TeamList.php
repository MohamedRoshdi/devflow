<?php

declare(strict_types=1);

namespace App\Livewire\Teams;

use App\Models\Team;
use App\Rules\FileUploadRule;
use App\Services\TeamService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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

    public mixed $avatar = null;

    private function teamService(): TeamService
    {
        return app(TeamService::class);
    }

    #[Computed]
    public function teams()
    {
        $user = Auth::user();
        if ($user === null) {
            return collect();
        }

        return $user->teams()
            ->withCount('members')
            ->with(['owner', 'members'])
            ->latest()
            ->get()
            ->map(function (Team $team) use ($user) {
                // Get role from loaded members relationship to avoid N+1
                $member = $team->members->firstWhere('id', $user->id);
                $role = $member?->pivot?->role;

                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'description' => $team->description,
                    'avatar_url' => $team->avatar_url,
                    'members_count' => $team->members_count,
                    'role' => $role,
                    'is_owner' => $team->isOwner($user),
                    'is_current' => $user->current_team_id === $team->id,
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
        // Validate file upload separately with enhanced rules
        if ($this->avatar) {
            $this->validate([
                'avatar' => FileUploadRule::avatarRules(required: false),
            ], FileUploadRule::messages(), FileUploadRule::attributes());

            // Additional security check for suspicious filenames
            $originalName = $this->avatar->getClientOriginalName();
            if (FileUploadRule::isSuspiciousFilename($originalName)) {
                $this->dispatch('notification', [
                    'type' => 'error',
                    'message' => 'Invalid filename detected. Please rename the file.',
                ]);
                return;
            }
        }

        $this->validate();

        try {
            $data = [
                'name' => $this->name,
                'description' => $this->description,
            ];

            // Handle avatar upload with sanitized filename
            if ($this->avatar) {
                $sanitizedFilename = FileUploadRule::sanitizeFilename($this->avatar->getClientOriginalName());
                $data['avatar'] = $this->avatar->storeAs('teams', $sanitizedFilename, 'public');
            }

            $team = $this->teamService()->createTeam(Auth::user(), $data);

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
                'message' => 'Failed to create team: '.$e->getMessage(),
            ]);
        }
    }

    public function switchTeam(int $teamId)
    {
        $team = Team::findOrFail($teamId);

        $user = Auth::user();
        if ($user === null || ! $team->hasMember($user)) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'You do not have access to this team.',
            ]);

            return;
        }

        $user->update(['current_team_id' => $teamId]);

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

        if (! $team->isOwner(Auth::user())) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Only the team owner can delete the team.',
            ]);

            return;
        }

        try {
            $this->teamService()->deleteTeam($team);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Team deleted successfully.',
            ]);

            unset($this->teams);
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to delete team: '.$e->getMessage(),
            ]);
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.teams.team-list');
    }
}
