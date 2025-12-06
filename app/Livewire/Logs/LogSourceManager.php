<?php

declare(strict_types=1);

namespace App\Livewire\Logs;

use App\Models\LogSource;
use App\Models\Project;
use App\Models\Server;
use App\Services\LogAggregationService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class LogSourceManager extends Component
{
    public Server $server;

    public bool $showAddModal = false;

    public ?int $editingSourceId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|in:file,docker,journald')]
    public string $type = 'file';

    #[Validate('required|string|max:500')]
    public string $path = '';

    #[Validate('nullable|exists:projects,id')]
    public ?int $project_id = null;

    public string $selectedTemplate = '';

    public ?string $testResult = null;

    public function __construct(
        private readonly LogAggregationService $logService
    ) {}

    public function mount(Server $server): void
    {
        $this->server = $server;
    }

    #[Computed]
    public function sources()
    {
        return LogSource::forServer($this->server->id)
            ->with('project')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function projects()
    {
        return Project::where('server_id', $this->server->id)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function templates()
    {
        return LogSource::predefinedTemplates();
    }

    public function openAddModal(): void
    {
        $this->reset(['name', 'type', 'path', 'project_id', 'selectedTemplate', 'testResult', 'editingSourceId']);
        $this->showAddModal = true;
    }

    public function closeModal(): void
    {
        $this->showAddModal = false;
        $this->reset(['name', 'type', 'path', 'project_id', 'selectedTemplate', 'testResult', 'editingSourceId']);
    }

    public function selectTemplate(string $template): void
    {
        $this->selectedTemplate = $template;
        $templates = $this->templates;

        if (isset($templates[$template])) {
            $this->name = $templates[$template]['name'];
            $this->type = $templates[$template]['type'];
            $this->path = $templates[$template]['path'];
        }
    }

    public function addSource(): void
    {
        $this->validate();

        try {
            LogSource::create([
                'server_id' => $this->server->id,
                'project_id' => $this->project_id,
                'name' => $this->name,
                'type' => $this->type,
                'path' => $this->path,
                'is_active' => true,
            ]);

            $this->dispatch('notification', type: 'success', message: 'Log source added successfully');
            $this->closeModal();
            unset($this->sources);
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: "Failed to add log source: {$e->getMessage()}");
        }
    }

    public function editSource(int $sourceId): void
    {
        $source = LogSource::findOrFail($sourceId);

        $this->editingSourceId = $sourceId;
        $this->name = $source->name;
        $this->type = $source->type;
        $this->path = $source->path;
        $this->project_id = $source->project_id;
        $this->showAddModal = true;
    }

    public function updateSource(): void
    {
        $this->validate();

        try {
            $source = LogSource::findOrFail($this->editingSourceId);
            $source->update([
                'name' => $this->name,
                'type' => $this->type,
                'path' => $this->path,
                'project_id' => $this->project_id,
            ]);

            $this->dispatch('notification', type: 'success', message: 'Log source updated successfully');
            $this->closeModal();
            unset($this->sources);
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: "Failed to update log source: {$e->getMessage()}");
        }
    }

    public function toggleSource(int $sourceId): void
    {
        try {
            $source = LogSource::findOrFail($sourceId);
            $source->update(['is_active' => ! $source->is_active]);

            $status = $source->is_active ? 'enabled' : 'disabled';
            $this->dispatch('notification', type: 'success', message: "Log source {$status}");
            unset($this->sources);
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: "Failed to toggle log source: {$e->getMessage()}");
        }
    }

    public function removeSource(int $sourceId): void
    {
        try {
            LogSource::findOrFail($sourceId)->delete();
            $this->dispatch('notification', type: 'success', message: 'Log source removed successfully');
            unset($this->sources);
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: "Failed to remove log source: {$e->getMessage()}");
        }
    }

    public function testSource(): void
    {
        $this->validate();

        try {
            $content = match ($this->type) {
                'file' => $this->logService->fetchLogFile($this->server, $this->path, 10),
                'docker' => $this->logService->parseDockerLog($this->path, ''),
                default => throw new \InvalidArgumentException('Unsupported type'),
            };

            if (empty($content)) {
                $this->testResult = 'error';
                $this->dispatch('notification', type: 'error', message: 'No logs found or unable to access path');
            } else {
                $this->testResult = 'success';
                $this->dispatch('notification', type: 'success', message: 'Connection successful! Found log data.');
            }
        } catch (\Exception $e) {
            $this->testResult = 'error';
            $this->dispatch('notification', type: 'error', message: "Test failed: {$e->getMessage()}");
        }
    }

    public function syncSource(int $sourceId): void
    {
        try {
            $source = LogSource::findOrFail($sourceId);

            // Create a temporary sync for just this source
            $originalActive = $source->is_active;
            $source->update(['is_active' => true]);

            $results = $this->logService->syncLogs($this->server);

            if (! $originalActive) {
                $source->update(['is_active' => false]);
            }

            $this->dispatch('notification', type: 'success', message: "Synced {$results['total_entries']} log entries");
            unset($this->sources);
        } catch (\Exception $e) {
            $this->dispatch('notification', type: 'error', message: "Sync failed: {$e->getMessage()}");
        }
    }

    public function render()
    {
        return view('livewire.logs.log-source-manager')
            ->title("Log Sources - {$this->server->name} - DevFlow Pro");
    }
}
