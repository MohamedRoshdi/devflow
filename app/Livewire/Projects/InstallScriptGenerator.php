<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Services\InstallScriptGenerator as InstallScriptGeneratorService;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class InstallScriptGenerator extends Component
{
    public Project $project;

    public bool $showModal = false;

    public bool $productionMode = false;

    public string $domain = '';

    public string $email = '';

    public string $dbDriver = 'pgsql';

    public bool $skipSsl = false;

    public bool $enableUfw = true;

    public bool $enableFail2ban = true;

    public bool $enableRedis = true;

    public bool $enableSupervisor = true;

    public int $queueWorkers = 2;

    public string $generatedScript = '';

    public bool $showScript = false;

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->domain = $project->domains()->where('is_primary', true)->first()?->domain ?? '';
        $this->email = config('mail.from.address', 'admin@example.com');
    }

    public function openModal(): void
    {
        $this->showModal = true;
        $this->resetScript();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetScript();
    }

    public function resetScript(): void
    {
        $this->generatedScript = '';
        $this->showScript = false;
    }

    public function generateScript(): void
    {
        $this->validate([
            'domain' => $this->productionMode ? 'required|string' : 'nullable|string',
            'email' => $this->productionMode ? 'required|email' : 'nullable|email',
            'dbDriver' => 'required|in:pgsql,mysql',
            'queueWorkers' => 'required|integer|min:1|max:10',
        ]);

        $generator = app(InstallScriptGeneratorService::class);

        $this->generatedScript = $generator->generate($this->project, [
            'production' => $this->productionMode,
            'domain' => $this->domain,
            'email' => $this->email,
            'db_driver' => $this->dbDriver,
            'skip_ssl' => $this->skipSsl,
            'enable_ufw' => $this->enableUfw,
            'enable_fail2ban' => $this->enableFail2ban,
            'enable_redis' => $this->enableRedis,
            'enable_supervisor' => $this->enableSupervisor,
            'queue_workers' => $this->queueWorkers,
        ]);

        $this->showScript = true;
    }

    public function downloadScript(): void
    {
        if (empty($this->generatedScript)) {
            $this->generateScript();
        }

        $filename = $this->project->slug . '-install.sh';
        $path = 'temp/' . $filename;

        Storage::disk('local')->put($path, $this->generatedScript);

        $this->dispatch('download-file', [
            'url' => route('download.temp', ['file' => $filename]),
            'filename' => $filename,
        ]);
    }

    public function copyToClipboard(): void
    {
        $this->dispatch('copy-to-clipboard', script: $this->generatedScript);
        $this->dispatch('notify', message: __('install_script.copied_to_clipboard'), type: 'success');
    }

    #[Computed]
    public function scriptLineCount(): int
    {
        if (empty($this->generatedScript)) {
            return 0;
        }

        return substr_count($this->generatedScript, "\n") + 1;
    }

    #[Computed]
    public function estimatedInstallTime(): string
    {
        $minutes = 5; // Base time

        if ($this->productionMode) {
            $minutes += 3; // Security setup
        }

        if ($this->enableRedis) {
            $minutes += 1;
        }

        if ($this->enableSupervisor) {
            $minutes += 1;
        }

        if (! $this->skipSsl && $this->productionMode) {
            $minutes += 2;
        }

        return $minutes . '-' . ($minutes + 5) . ' ' . __('install_script.minutes');
    }

    public function render(): View
    {
        return view('livewire.projects.install-script-generator');
    }
}
