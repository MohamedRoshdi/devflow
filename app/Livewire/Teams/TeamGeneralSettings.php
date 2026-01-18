<?php

declare(strict_types=1);

namespace App\Livewire\Teams;

use App\Models\Team;
use App\Rules\DescriptionRule;
use App\Rules\FileUploadRule;
use App\Rules\NameRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class TeamGeneralSettings extends Component
{
    use WithFileUploads;

    public Team $team;

    public string $name = '';

    public string $description = '';

    public mixed $avatar = null;

    public function mount(Team $team): void
    {
        $this->team = $team;
        $this->name = $team->name;
        $this->description = $team->description ?? '';
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

        $this->validate([
            'name' => NameRule::rules(required: true, maxLength: 255),
            'description' => DescriptionRule::rules(required: false, maxLength: 500),
        ]);

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

            $this->team->update($data);

            $this->dispatch('notification', [
                'type' => 'success',
                'message' => 'Team updated successfully!',
            ]);

            $this->dispatch('team-updated');

            $this->avatar = null;
        } catch (\Exception $e) {
            $this->dispatch('notification', [
                'type' => 'error',
                'message' => 'Failed to update team: '.$e->getMessage(),
            ]);
        }
    }

    private function canManageTeam(): bool
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
        return view('livewire.teams.team-general-settings');
    }
}
