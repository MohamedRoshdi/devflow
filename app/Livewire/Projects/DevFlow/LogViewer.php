<?php

namespace App\Livewire\Projects\DevFlow;

use Livewire\Component;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class LogViewer extends Component
{
    // Log state
    public string $recentLogs = '';
    public array $logFiles = [];
    public string $selectedLogFile = '';

    public function mount(): void
    {
        $this->loadRecentLogs();
    }

    public function loadRecentLogs(): void
    {
        try {
            $logsPath = storage_path('logs');

            // Get all log files
            $this->logFiles = [];
            $files = glob($logsPath . '/laravel*.log');

            if ($files) {
                // Sort by modification time (newest first)
                usort($files, fn($a, $b) => filemtime($b) - filemtime($a));

                foreach ($files as $file) {
                    $filename = basename($file);
                    $this->logFiles[] = [
                        'name' => $filename,
                        'path' => $file,
                        'size' => $this->formatBytes(filesize($file)),
                        'modified' => date('Y-m-d H:i:s', filemtime($file)),
                    ];
                }
            }

            // Select first log file by default
            if (empty($this->selectedLogFile) && !empty($this->logFiles)) {
                $this->selectedLogFile = $this->logFiles[0]['name'];
            }

            // Load selected log content
            $this->loadLogContent();

        } catch (\Exception $e) {
            $this->recentLogs = 'Unable to load logs: ' . $e->getMessage();
        }
    }

    private function loadLogContent(): void
    {
        if (empty($this->selectedLogFile)) {
            $this->recentLogs = 'No log file selected';
            return;
        }

        $logPath = storage_path('logs/' . $this->selectedLogFile);
        if (file_exists($logPath)) {
            $result = Process::run("tail -100 " . escapeshellarg($logPath));
            $this->recentLogs = $result->output();
        } else {
            $this->recentLogs = 'Log file not found';
        }
    }

    public function selectLogFile(string $filename): void
    {
        $this->selectedLogFile = $filename;
        $this->loadLogContent();
    }

    public function refreshLogs(): void
    {
        $this->loadRecentLogs();
    }

    public function clearLogFile(string $filename): void
    {
        $logPath = storage_path('logs/' . basename($filename));

        if (file_exists($logPath) && str_starts_with(basename($filename), 'laravel')) {
            try {
                file_put_contents($logPath, '');
                session()->flash('message', "Log file {$filename} cleared successfully!");
                Log::info('DevFlow log file cleared', ['file' => $filename, 'user_id' => auth()->id()]);
                $this->loadRecentLogs();
            } catch (\Exception $e) {
                session()->flash('error', 'Failed to clear log: ' . $e->getMessage());
            }
        } else {
            session()->flash('error', 'Invalid log file');
        }
    }

    public function deleteLogFile(string $filename): void
    {
        $logPath = storage_path('logs/' . basename($filename));

        // Don't allow deleting the main laravel.log
        if ($filename === 'laravel.log') {
            session()->flash('error', 'Cannot delete the main log file. Use clear instead.');
            return;
        }

        if (file_exists($logPath) && str_starts_with(basename($filename), 'laravel')) {
            try {
                unlink($logPath);
                session()->flash('message', "Log file {$filename} deleted successfully!");
                Log::info('DevFlow log file deleted', ['file' => $filename, 'user_id' => auth()->id()]);

                // Reset selection if deleted file was selected
                if ($this->selectedLogFile === $filename) {
                    $this->selectedLogFile = '';
                }
                $this->loadRecentLogs();
            } catch (\Exception $e) {
                session()->flash('error', 'Failed to delete log: ' . $e->getMessage());
            }
        } else {
            session()->flash('error', 'Invalid log file');
        }
    }

    public function downloadLogFile(string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $logPath = storage_path('logs/' . basename($filename));

        if (file_exists($logPath) && str_starts_with(basename($filename), 'laravel')) {
            return response()->streamDownload(function () use ($logPath) {
                echo file_get_contents($logPath);
            }, $filename, [
                'Content-Type' => 'text/plain',
            ]);
        }

        abort(404, 'Log file not found');
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = (float) max((float) $bytes, 0);
        if ($bytes == 0) {
            return '0 B';
        }
        $pow = floor(log($bytes) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.projects.devflow.log-viewer');
    }
}
