<?php

declare(strict_types=1);

namespace App\Livewire\Teams;

use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TeamSwitcher extends Component
{
    public bool $showDropdown = false;

    #[Computed]
    public function currentTeam()
    {
        $user = Auth::user();
        if ($user === null) {
            return null;
        }

        $team = $user->currentTeam;

        if (! $team) {
            return null;
        }

        // Eager load members to avoid N+1
        $team->load('members');

        // Get role from loaded members relationship to avoid N+1
        $member = $team->members->firstWhere('id', $user->id);
        $role = $member?->pivot?->role;

        return [
            'id' => $team->id,
            'name' => $team->name,
            'avatar_url' => $team->avatar_url,
            'role' => $role,
        ];
    }

    #[Computed]
    public function teams()
    {
        $user = Auth::user();
        if ($user === null) {
            return collect();
        }

        return $user->teams()
            ->where('id', '!=', $user->current_team_id)
            ->with('members')
            ->get()
            ->map(function (Team $team) use ($user) {
                // Get role from loaded members relationship to avoid N+1
                $member = $team->members->firstWhere('id', $user->id);
                $role = $member?->pivot?->role;

                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'avatar_url' => $team->avatar_url,
                    'role' => $role,
                ];
            });
    }

    public function toggleDropdown(): void
    {
        $this->showDropdown = ! $this->showDropdown;
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

        $this->showDropdown = false;
        unset($this->currentTeam);
        unset($this->teams);

        $this->dispatch('team-switched');

        // Refresh the page to load team-specific data
        return $this->redirect(request()->url(), navigate: true);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.teams.team-switcher');
    }
}
