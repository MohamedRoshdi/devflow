<?php

namespace App\Livewire\Deployments;

use App\Models\Project;
use App\Models\ScheduledDeployment;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class ScheduledDeployments extends Component
{
    public Project $project;

    /** @var array<int, string> */
    public array $branches = [];

    public bool $showScheduleModal = false;

    // Form fields
    public string $selectedBranch = 'main';

    public string $scheduledDate = '';

    public string $scheduledTime = '';

    public string $timezone = 'UTC';

    public string $notes = '';

    public bool $notifyBefore = true;

    public int $notifyMinutes = 15;

    /** @var array<string, string> */
    protected array $timezones = [
        'UTC' => 'UTC',
        'America/New_York' => 'Eastern Time (ET)',
        'America/Chicago' => 'Central Time (CT)',
        'America/Denver' => 'Mountain Time (MT)',
        'America/Los_Angeles' => 'Pacific Time (PT)',
        'Europe/London' => 'London (GMT)',
        'Europe/Paris' => 'Paris (CET)',
        'Europe/Berlin' => 'Berlin (CET)',
        'Asia/Tokyo' => 'Tokyo (JST)',
        'Asia/Shanghai' => 'Shanghai (CST)',
        'Asia/Dubai' => 'Dubai (GST)',
        'Australia/Sydney' => 'Sydney (AEST)',
        'Africa/Cairo' => 'Cairo (EET)',
    ];

    public function mount(Project $project)
    {
        $this->project = $project;
        $this->selectedBranch = $project->branch ?? 'main';
        $this->timezone = auth()->user()->timezone ?? 'UTC';
        $this->scheduledDate = now()->addDay()->format('Y-m-d');
        $this->scheduledTime = '03:00'; // Default to 3 AM for off-peak
        $this->loadBranches();
    }

    protected function loadBranches()
    {
        // Use existing branches from project or default
        $this->branches = $this->project->available_branches ?? [
            $this->project->branch ?? 'main',
        ];
    }

    public function openScheduleModal()
    {
        $this->showScheduleModal = true;
    }

    public function closeScheduleModal()
    {
        $this->showScheduleModal = false;
        $this->resetForm();
    }

    protected function resetForm()
    {
        $this->selectedBranch = $this->project->branch ?? 'main';
        $this->scheduledDate = now()->addDay()->format('Y-m-d');
        $this->scheduledTime = '03:00';
        $this->notes = '';
        $this->notifyBefore = true;
        $this->notifyMinutes = 15;
    }

    public function scheduleDeployment()
    {
        $this->validate([
            'selectedBranch' => 'required|string',
            'scheduledDate' => 'required|date|after_or_equal:today',
            'scheduledTime' => 'required|date_format:H:i',
            'timezone' => 'required|string',
            'notes' => 'nullable|string|max:500',
            'notifyMinutes' => 'integer|min:5|max:60',
        ]);

        // Combine date and time in the selected timezone
        $parsedDate = Carbon::createFromFormat(
            'Y-m-d H:i',
            "{$this->scheduledDate} {$this->scheduledTime}",
            $this->timezone
        );

        if (! $parsedDate instanceof \Carbon\Carbon) {
            $this->addError('scheduledTime', 'Invalid date/time format.');

            return;
        }

        $scheduledAt = $parsedDate->utc();

        // Ensure the scheduled time is in the future
        if ($scheduledAt->isPast()) {
            $this->addError('scheduledTime', 'Scheduled time must be in the future.');

            return;
        }

        ScheduledDeployment::create([
            'project_id' => $this->project->id,
            'user_id' => auth()->id(),
            'branch' => $this->selectedBranch,
            'scheduled_at' => $scheduledAt,
            'timezone' => $this->timezone,
            'notes' => $this->notes,
            'notify_before' => $this->notifyBefore,
            'notify_minutes' => $this->notifyMinutes,
            'status' => 'pending',
        ]);

        $this->dispatch('notification',
            type: 'success',
            message: 'Deployment scheduled successfully for '.$scheduledAt->timezone($this->timezone)->format('M d, Y H:i').' '.$this->timezone
        );

        $this->closeScheduleModal();
    }

    public function cancelScheduledDeployment(int $id)
    {
        $scheduled = ScheduledDeployment::where('id', $id)
            ->where('project_id', $this->project->id)
            ->first();

        if ($scheduled && $scheduled->canCancel()) {
            $scheduled->update(['status' => 'cancelled']);

            $this->dispatch('notification',
                type: 'success',
                message: 'Scheduled deployment cancelled.'
            );
        }
    }

    public function getScheduledDeploymentsProperty()
    {
        return ScheduledDeployment::where('project_id', $this->project->id)
            ->with('user')
            ->orderBy('scheduled_at', 'asc')
            ->get();
    }

    public function getTimezoneOptionsProperty()
    {
        return $this->timezones;
    }

    #[On('deployment-completed')]
    public function refreshList()
    {
        // Refresh the list when a deployment completes
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.deployments.scheduled-deployments');
    }
}
