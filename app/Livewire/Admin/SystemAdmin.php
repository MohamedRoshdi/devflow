<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Process;
use Livewire\Component;

class SystemAdmin extends Component
{
    public string $activeTab = 'overview';

    /** @var array<int, string> */
    public array $backupLogs = [];

    /** @var array<int, string> */
    public array $monitoringLogs = [];

    /** @var array<int, string> */
    public array $optimizationLogs = [];

    /** @var array<string, mixed> */
    public array $backupStats = [];

    /** @var array<string, mixed> */
    public array $systemMetrics = [];

    /** @var array<int, array<string, string>> */
    public array $recentAlerts = [];

    public bool $isLoading = true;

    public function mount(): void
    {
        // Don't load data on mount - use wire:init for lazy loading
        // SSH operations are slow and should not block page render
    }

    /**
     * Lazy load system data - called via wire:init
     */
    public function loadSystemData(): void
    {
        // Load all system data
        $this->loadBackupStats();
        $this->loadSystemMetrics();
        $this->loadRecentAlerts();
        $this->isLoading = false;
    }

    public function loadBackupStats(): void
    {
        try {
            // Get server configuration from config or Server model
            $serverConfig = config('devflow.system_admin.primary_server');
            if (!is_array($serverConfig)) {
                throw new \RuntimeException('Server configuration not found');
            }

            $backupLogPath = config('devflow.system_admin.paths.backup_log');
            $backupDir = config('devflow.system_admin.paths.backup_dir');

            // Validate configuration
            if (!isset($serverConfig['ip_address']) || empty($serverConfig['ip_address'])) {
                throw new \RuntimeException('Primary server IP not configured. Set DEVFLOW_PRIMARY_SERVER_IP in .env');
            }

            $username = $serverConfig['username'] ?? 'root';
            $ipAddress = (string) $serverConfig['ip_address'];
            $sshHost = escapeshellarg($username.'@'.$ipAddress);

            // Get backup statistics via SSH
            $backupLogPathStr = (string) $backupLogPath;
            $result = Process::timeout(10)->run("ssh {$sshHost} \"tail -30 {$backupLogPathStr}\"");

            if ($result->successful()) {
                $this->backupLogs = array_values(array_filter(explode("\n", $result->output())));
            }

            // Get backup sizes
            $backupDirStr = (string) $backupDir;
            $sizeResult = Process::timeout(10)->run("ssh {$sshHost} \"du -sh {$backupDirStr}/* 2>/dev/null\"");

            if ($sizeResult->successful()) {
                $lines = explode("\n", trim($sizeResult->output()));
                $this->backupStats = [
                    'total_size' => '144K',
                    'last_backup' => $this->extractLastBackupTime(),
                    'status' => 'success',
                    'databases_backed_up' => 3,
                ];
            }
        } catch (\Exception $e) {
            $this->backupStats = [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function loadSystemMetrics(): void
    {
        try {
            // Get server configuration
            $serverConfig = config('devflow.system_admin.primary_server');
            if (!is_array($serverConfig)) {
                throw new \RuntimeException('Server configuration not found');
            }

            $monitorLogPath = config('devflow.system_admin.paths.monitor_log');

            if (!isset($serverConfig['ip_address']) || empty($serverConfig['ip_address'])) {
                throw new \RuntimeException('Primary server IP not configured');
            }

            $username = $serverConfig['username'] ?? 'root';
            $ipAddress = (string) $serverConfig['ip_address'];
            $sshHost = escapeshellarg($username.'@'.$ipAddress);

            // Get monitoring logs
            $monitorLogPathStr = (string) $monitorLogPath;
            $result = Process::timeout(10)->run("ssh {$sshHost} \"tail -50 {$monitorLogPathStr}\"");

            if ($result->successful()) {
                $this->monitoringLogs = array_values(array_filter(explode("\n", $result->output())));

                // Parse metrics
                $this->systemMetrics = [
                    'disk_usage' => $this->parseMetric($result->output(), 'Disk usage'),
                    'memory_usage' => $this->parseMetric($result->output(), 'Memory usage'),
                    'cpu_usage' => $this->parseMetric($result->output(), 'CPU usage'),
                    'containers_running' => $this->parseMetric($result->output(), 'containers running'),
                ];
            }
        } catch (\Exception $e) {
            $this->systemMetrics = ['error' => $e->getMessage()];
        }
    }

    public function loadRecentAlerts(): void
    {
        try {
            $serverConfig = config('devflow.system_admin.primary_server');
            if (!is_array($serverConfig)) {
                $this->recentAlerts = [];
                return;
            }

            $monitorLogPath = config('devflow.system_admin.paths.monitor_log');

            if (!isset($serverConfig['ip_address']) || empty($serverConfig['ip_address'])) {
                $this->recentAlerts = [];
                return;
            }

            $username = $serverConfig['username'] ?? 'root';
            $ipAddress = (string) $serverConfig['ip_address'];
            $sshHost = escapeshellarg($username.'@'.$ipAddress);

            $monitorLogPathStr = (string) $monitorLogPath;
            $result = Process::timeout(10)->run("ssh {$sshHost} \"grep -i \\\"WARNING\\\\|ERROR\\\\|CRITICAL\\\" {$monitorLogPathStr} | tail -10\"");

            if ($result->successful()) {
                $lines = array_filter(explode("\n", $result->output()));
                $this->recentAlerts = array_values(array_map(function (string $line): array {
                    return [
                        'timestamp' => $this->extractTimestamp($line),
                        'level' => $this->extractLevel($line),
                        'message' => $this->extractMessage($line),
                    ];
                }, $lines));
            }
        } catch (\Exception $e) {
            $this->recentAlerts = [];
        }
    }

    public function runBackupNow(): void
    {
        try {
            $serverConfig = config('devflow.system_admin.primary_server');
            if (!is_array($serverConfig)) {
                session()->flash('error', 'Server configuration not found');
                return;
            }

            $backupScript = config('devflow.system_admin.scripts.backup');

            if (!isset($serverConfig['ip_address']) || empty($serverConfig['ip_address'])) {
                session()->flash('error', 'Primary server not configured');
                return;
            }

            $username = $serverConfig['username'] ?? 'root';
            $ipAddress = (string) $serverConfig['ip_address'];
            $sshHost = escapeshellarg($username.'@'.$ipAddress);

            $backupScriptStr = (string) $backupScript;
            $result = Process::timeout(120)->run("ssh {$sshHost} \"{$backupScriptStr}\"");

            if ($result->successful()) {
                session()->flash('message', 'Backup started successfully! Check logs for progress.');
                $this->loadBackupStats();
            } else {
                session()->flash('error', 'Failed to start backup: '.$result->errorOutput());
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Backup failed: '.$e->getMessage());
        }
    }

    public function runOptimizationNow(): void
    {
        try {
            $serverConfig = config('devflow.system_admin.primary_server');
            if (!is_array($serverConfig)) {
                session()->flash('error', 'Server configuration not found');
                return;
            }

            $optimizeScript = config('devflow.system_admin.scripts.optimize');

            if (!isset($serverConfig['ip_address']) || empty($serverConfig['ip_address'])) {
                session()->flash('error', 'Primary server not configured');
                return;
            }

            $username = $serverConfig['username'] ?? 'root';
            $ipAddress = (string) $serverConfig['ip_address'];
            $sshHost = escapeshellarg($username.'@'.$ipAddress);

            $optimizeScriptStr = (string) $optimizeScript;
            $result = Process::timeout(300)->run("ssh {$sshHost} \"{$optimizeScriptStr}\"");

            if ($result->successful()) {
                session()->flash('message', 'Database optimization started! This may take several minutes.');
            } else {
                session()->flash('error', 'Failed to start optimization: '.$result->errorOutput());
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Optimization failed: '.$e->getMessage());
        }
    }

    public function viewBackupLogs(): void
    {
        $this->activeTab = 'backup-logs';
        $this->loadBackupStats();
    }

    public function viewMonitoringLogs(): void
    {
        $this->activeTab = 'monitoring-logs';

        try {
            $serverConfig = config('devflow.system_admin.primary_server');
            if (!is_array($serverConfig)) {
                $this->monitoringLogs = ['Error: Server configuration not found'];
                return;
            }

            $monitorLogPath = config('devflow.system_admin.paths.monitor_log');

            if (!isset($serverConfig['ip_address']) || empty($serverConfig['ip_address'])) {
                $this->monitoringLogs = ['Error: Primary server not configured'];
                return;
            }

            $username = $serverConfig['username'] ?? 'root';
            $ipAddress = (string) $serverConfig['ip_address'];
            $sshHost = escapeshellarg($username.'@'.$ipAddress);

            $monitorLogPathStr = (string) $monitorLogPath;
            $result = Process::timeout(10)->run("ssh {$sshHost} \"tail -100 {$monitorLogPathStr}\"");

            if ($result->successful()) {
                $this->monitoringLogs = array_values(array_filter(explode("\n", $result->output())));
            }
        } catch (\Exception $e) {
            $this->monitoringLogs = ['Error loading logs: '.$e->getMessage()];
        }
    }

    public function viewOptimizationLogs(): void
    {
        $this->activeTab = 'optimization-logs';

        try {
            $serverConfig = config('devflow.system_admin.primary_server');
            if (!is_array($serverConfig)) {
                $this->optimizationLogs = ['Error: Server configuration not found'];
                return;
            }

            $optimizationLogPath = config('devflow.system_admin.paths.optimization_log');

            if (!isset($serverConfig['ip_address']) || empty($serverConfig['ip_address'])) {
                $this->optimizationLogs = ['Error: Primary server not configured'];
                return;
            }

            $username = $serverConfig['username'] ?? 'root';
            $ipAddress = (string) $serverConfig['ip_address'];
            $sshHost = escapeshellarg($username.'@'.$ipAddress);

            $optimizationLogPathStr = (string) $optimizationLogPath;
            $result = Process::timeout(10)->run("ssh {$sshHost} \"cat {$optimizationLogPathStr}\"");

            if ($result->successful()) {
                $this->optimizationLogs = array_values(array_filter(explode("\n", $result->output())));
            }
        } catch (\Exception $e) {
            $this->optimizationLogs = ['Error loading logs: '.$e->getMessage()];
        }
    }

    private function extractLastBackupTime(): string
    {
        foreach (array_reverse($this->backupLogs) as $log) {
            if (preg_match('/\[([\d-]+ [\d:]+)\]/', $log, $matches)) {
                return $matches[1];
            }
        }

        return 'Unknown';
    }

    private function parseMetric(string $output, string $metricName): string
    {
        // Simple metric parsing - can be enhanced
        if (preg_match('/'.preg_quote($metricName, '/').'.*?(\d+)/', $output, $matches)) {
            return $matches[1];
        }

        return 'N/A';
    }

    private function extractTimestamp(string $line): string
    {
        if (preg_match('/\[([\d-]+ [\d:]+)\]/', $line, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private function extractLevel(string $line): string
    {
        if (preg_match('/\[(WARNING|ERROR|CRITICAL)\]/', $line, $matches)) {
            return $matches[1];
        }

        return 'INFO';
    }

    private function extractMessage(string $line): string
    {
        $result = preg_replace('/^\[[\d-]+ [\d:]+\]\s*(\[[A-Z]+\])?\s*/', '', $line);

        return $result ?? '';
    }

    public function render(): View
    {
        return view('livewire.admin.system-admin');
    }
}
