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
        $team = Auth::user()->currentTeam;

        if (!$team) {
            return null;
        }

        return [
            'id' => $team->id,
            'name' => $team->name,
            'avatar_url' => $team->avatar_url,
            'role' => $team->getMemberRole(Auth::user()),
        ];
    }

    #[Computed]
    public function teams()
    {
        return Auth::user()->teams()
            ->where('id', '!=', Auth::user()->current_team_id)
            ->get()
            ->map(function (Team $team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'avatar_url' => $team->avatar_url,
                    'role' => $team->getMemberRole(Auth::user()),
                ];
            });
    }

    public function toggleDropdown(): void
    {
        $this->showDropdown = !$this->showDropdown;
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

        $this->showDropdown = false;
        unset($this->currentTeam);
        unset($this->teams);

        $this->dispatch('team-switched');

        // Refresh the page to load team-specific data
        return $this->redirect(request()->url(), navigate: true);
    }

    public function render()
    {
        return view('livewire.teams.team-switcher');
    }
}
