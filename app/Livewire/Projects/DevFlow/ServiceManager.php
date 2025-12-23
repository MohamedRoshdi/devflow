<?php

declare(strict_types=1);

namespace App\Livewire\Projects\DevFlow;

use Livewire\Component;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ServiceManager extends Component
{
    // Queue Status
    /** @var array<int, array{name: string, status: string, info: string}> */
    public array $queueStatus = [];

    // Reverb WebSocket
    /** @var array<string, mixed> */
    public array $reverbStatus = [];
    public bool $reverbRunning = false;
    public string $reverbOutput = '';

    // Supervisor Processes
    /** @var array<int, array{name: string, status: string, info: string}> */
    public array $supervisorProcesses = [];

    // Scheduler
    /** @var array<string, mixed> */
    public array $schedulerStatus = [];
    public string $lastSchedulerRun = '';

    public function mount(): void
    {
        $this->loadQueueStatus();
        $this->loadReverbStatus();
        $this->loadSupervisorProcesses();
        $this->loadSchedulerStatus();
    }

    // ===== QUEUE METHODS =====

    private function loadQueueStatus(): void
    {
        try {
            $result = Process::run("supervisorctl status devflow-worker:* 2>/dev/null");
            $output = trim($result->output());

            $this->queueStatus = [];
            if (!empty($output)) {
                $lines = explode("\n", $output);
                foreach ($lines as $line) {
                    if (preg_match('/(\S+)\s+(RUNNING|STOPPED|FATAL)\s+(.*)/', $line, $matches)) {
                        $this->queueStatus[] = [
                            'name' => $matches[1],
                            'status' => $matches[2],
                            'info' => $matches[3] ?? '',
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Supervisor might not be available
        }
    }

    public function restartQueue(): void
    {
        try {
            Artisan::call('queue:restart');
            $this->loadQueueStatus();
            session()->flash('message', 'Queue workers will restart on their next cycle.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to restart queue: ' . $e->getMessage());
        }
    }

    // ===== REVERB WEBSOCKET METHODS =====

    private function loadReverbStatus(): void
    {
        try {
            // Check if reverb process is running
            $result = Process::run("pgrep -f 'reverb:start' 2>/dev/null");
            $this->reverbRunning = !empty(trim($result->output()));

            // Get reverb config
            $this->reverbStatus = [
                'enabled' => config('broadcasting.default') === 'reverb',
                'host' => config('reverb.servers.reverb.host', config('reverb.host', 'localhost')),
                'port' => config('reverb.servers.reverb.port', config('reverb.port', 8080)),
                'app_id' => config('reverb.apps.0.app_id', 'Not configured'),
                'running' => $this->reverbRunning,
            ];
        } catch (\Exception $e) {
            $this->reverbStatus = ['error' => $e->getMessage()];
        }
    }

    public function startReverb(): void
    {
        try {
            // Start reverb in background
            Process::timeout(5)->run("cd " . base_path() . " && nohup php artisan reverb:start --host=0.0.0.0 --port=8080 > storage/logs/reverb.log 2>&1 &");
            sleep(2);
            $this->loadReverbStatus();
            $this->reverbOutput = 'Reverb WebSocket server started successfully';
            session()->flash('message', 'Reverb WebSocket server started');
        } catch (\Exception $e) {
            $this->reverbOutput = 'Error: ' . $e->getMessage();
            session()->flash('error', 'Failed to start Reverb: ' . $e->getMessage());
        }
    }

    public function stopReverb(): void
    {
        try {
            Process::run("pkill -f 'reverb:start' 2>/dev/null");
            sleep(1);
            $this->loadReverbStatus();
            $this->reverbOutput = 'Reverb WebSocket server stopped';
            session()->flash('message', 'Reverb WebSocket server stopped');
        } catch (\Exception $e) {
            $this->reverbOutput = 'Error: ' . $e->getMessage();
            session()->flash('error', 'Failed to stop Reverb: ' . $e->getMessage());
        }
    }

    public function restartReverb(): void
    {
        $this->stopReverb();
        sleep(1);
        $this->startReverb();
    }

    // ===== SUPERVISOR METHODS =====

    private function loadSupervisorProcesses(): void
    {
        try {
            $result = Process::run("supervisorctl status 2>/dev/null");
            $output = trim($result->output());

            $this->supervisorProcesses = [];
            if (!empty($output) && !str_contains($output, 'error') && !str_contains($output, 'refused')) {
                $lines = explode("\n", $output);
                foreach ($lines as $line) {
                    if (preg_match('/^(\S+)\s+(RUNNING|STOPPED|FATAL|STARTING|BACKOFF|STOPPING|EXITED|UNKNOWN)\s*(.*)$/', trim($line), $matches)) {
                        $this->supervisorProcesses[] = [
                            'name' => $matches[1],
                            'status' => $matches[2],
                            'info' => trim($matches[3] ?? ''),
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            $this->supervisorProcesses = [];
        }
    }

    public function supervisorAction(string $action, string $process = 'all'): void
    {
        try {
            $validActions = ['start', 'stop', 'restart', 'reread', 'update'];
            if (!in_array($action, $validActions)) {
                throw new \Exception('Invalid action');
            }

            $result = Process::run("supervisorctl {$action} {$process} 2>&1");
            $this->loadSupervisorProcesses();

            session()->flash('message', "Supervisor: {$action} {$process} - " . trim($result->output()));
        } catch (\Exception $e) {
            session()->flash('error', 'Supervisor error: ' . $e->getMessage());
        }
    }

    // ===== SCHEDULER METHODS =====

    private function loadSchedulerStatus(): void
    {
        try {
            // Check crontab for Laravel scheduler
            $result = Process::run("crontab -l 2>/dev/null | grep -c 'schedule:run'");
            $cronConfigured = (int) trim($result->output()) > 0;

            // Get scheduled tasks
            $result = Process::run("cd " . base_path() . " && php artisan schedule:list 2>/dev/null");
            $scheduleList = trim($result->output());

            // Check last run
            $cacheFile = storage_path('framework/schedule-*');
            $lastRun = 'Unknown';
            $files = glob($cacheFile);
            if ($files !== false && !empty($files)) {
                $lastFile = end($files);
                if (file_exists($lastFile)) {
                    $lastRun = date('Y-m-d H:i:s', filemtime($lastFile));
                }
            }

            $this->schedulerStatus = [
                'cron_configured' => $cronConfigured,
                'tasks' => $scheduleList ? explode("\n", $scheduleList) : [],
            ];
            $this->lastSchedulerRun = $lastRun;
        } catch (\Exception $e) {
            $this->schedulerStatus = ['error' => $e->getMessage()];
        }
    }

    public function runScheduler(): void
    {
        try {
            Artisan::call('schedule:run');
            $output = Artisan::output();
            $this->loadSchedulerStatus();
            session()->flash('message', 'Scheduler executed: ' . ($output ?: 'No tasks due'));
        } catch (\Exception $e) {
            session()->flash('error', 'Scheduler error: ' . $e->getMessage());
        }
    }

    public function refreshServices(): void
    {
        $this->loadQueueStatus();
        $this->loadReverbStatus();
        $this->loadSupervisorProcesses();
        $this->loadSchedulerStatus();
        session()->flash('message', 'Service information refreshed successfully!');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.projects.devflow.service-manager');
    }
}
