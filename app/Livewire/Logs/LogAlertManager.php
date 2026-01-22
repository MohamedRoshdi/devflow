<?php

declare(strict_types=1);

namespace App\Livewire\Logs;

use App\Models\LogAlert;
use App\Models\Server;
use App\Models\SystemLog;
use App\Services\LogAlertService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class LogAlertManager extends Component
{
    use WithPagination;

    // Form properties
    public ?int $editingId = null;
    public bool $showForm = false;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string')]
    public string $description = '';

    #[Validate('nullable|exists:servers,id')]
    public ?int $server_id = null;

    #[Validate('required|string')]
    public string $pattern = '';

    #[Validate('nullable|string')]
    public ?string $log_type = null;

    #[Validate('nullable|string')]
    public ?string $log_level = null;

    #[Validate('boolean')]
    public bool $is_regex = false;

    #[Validate('boolean')]
    public bool $case_sensitive = false;

    #[Validate('required|integer|min:1')]
    public int $threshold = 1;

    #[Validate('required|integer|min:1')]
    public int $time_window = 60;

    /** @var array<int, string> */
    public array $notification_channels = [];

    /** @var array<string, mixed> */
    public array $notification_config = [];

    #[Validate('boolean')]
    public bool $is_active = true;

    /** @var array<string, mixed>|null */
    public ?array $testResult = null;

    #[Computed]
    public function alerts()
    {
        return LogAlert::with(['server', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    #[Computed]
    public function servers()
    {
        return Server::orderBy('name')->get();
    }

    #[Computed]
    public function logTypes()
    {
        return SystemLog::getLogTypes();
    }

    #[Computed]
    public function logLevels()
    {
        return SystemLog::getLogLevels();
    }

    public function createAlert(): void
    {
        $this->reset([
            'editingId',
            'name',
            'description',
            'server_id',
            'pattern',
            'log_type',
            'log_level',
            'is_regex',
            'case_sensitive',
            'threshold',
            'time_window',
            'notification_channels',
            'notification_config',
            'is_active',
            'testResult',
        ]);

        $this->threshold = 1;
        $this->time_window = 60;
        $this->is_active = true;
        $this->showForm = true;
    }

    public function editAlert(int $id): void
    {
        $alert = LogAlert::findOrFail($id);

        $this->editingId = $alert->id;
        $this->name = $alert->name;
        $this->description = $alert->description ?? '';
        $this->server_id = $alert->server_id;
        $this->pattern = $alert->pattern;
        $this->log_type = $alert->log_type;
        $this->log_level = $alert->log_level;
        $this->is_regex = $alert->is_regex;
        $this->case_sensitive = $alert->case_sensitive;
        $this->threshold = $alert->threshold;
        $this->time_window = $alert->time_window;
        $this->notification_channels = $alert->notification_channels ?? [];
        $this->notification_config = $alert->notification_config ?? [];
        $this->is_active = $alert->is_active;
        $this->testResult = null;

        $this->showForm = true;
    }

    public function saveAlert(): void
    {
        $this->validate();

        $data = [
            'user_id' => auth()->id(),
            'server_id' => $this->server_id,
            'name' => $this->name,
            'description' => $this->description,
            'pattern' => $this->pattern,
            'log_type' => $this->log_type,
            'log_level' => $this->log_level,
            'is_regex' => $this->is_regex,
            'case_sensitive' => $this->case_sensitive,
            'threshold' => $this->threshold,
            'time_window' => $this->time_window,
            'notification_channels' => $this->notification_channels,
            'notification_config' => $this->notification_config,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            LogAlert::findOrFail($this->editingId)->update($data);
            $message = 'Alert updated successfully';
        } else {
            LogAlert::create($data);
            $message = 'Alert created successfully';
        }

        $this->showForm = false;
        $this->reset([
            'editingId',
            'name',
            'description',
            'server_id',
            'pattern',
            'log_type',
            'log_level',
            'is_regex',
            'case_sensitive',
            'threshold',
            'time_window',
            'notification_channels',
            'notification_config',
            'testResult',
        ]);

        unset($this->alerts);
        $this->dispatch('notification', type: 'success', message: $message);
    }

    public function deleteAlert(int $id): void
    {
        LogAlert::findOrFail($id)->delete();
        unset($this->alerts);
        $this->dispatch('notification', type: 'success', message: 'Alert deleted successfully');
    }

    public function toggleActive(int $id): void
    {
        $alert = LogAlert::findOrFail($id);
        $alert->update(['is_active' => !$alert->is_active]);
        unset($this->alerts);
        $this->dispatch('notification', type: 'success', message: 'Alert status updated');
    }

    public function testCurrentAlert(): void
    {
        $this->validate([
            'pattern' => 'required|string',
        ]);

        // Create temporary alert for testing
        $tempAlert = new LogAlert([
            'server_id' => $this->server_id,
            'pattern' => $this->pattern,
            'log_type' => $this->log_type,
            'log_level' => $this->log_level,
            'is_regex' => $this->is_regex,
            'case_sensitive' => $this->case_sensitive,
            'threshold' => $this->threshold,
            'time_window' => $this->time_window,
        ]);

        $alertService = app(LogAlertService::class);
        $this->testResult = $alertService->testAlert($tempAlert);

        $this->dispatch('notification', type: 'info', message: 'Test completed. See results below.');
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->reset([
            'editingId',
            'name',
            'description',
            'server_id',
            'pattern',
            'log_type',
            'log_level',
            'is_regex',
            'case_sensitive',
            'threshold',
            'time_window',
            'notification_channels',
            'notification_config',
            'testResult',
        ]);
    }

    public function render(): View
    {
        return view('livewire.logs.log-alert-manager');
    }
}
