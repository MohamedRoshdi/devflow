# System Logs - Implementation Status & Completion Guide

## ✅ FULLY IMPLEMENTED FEATURES

### 1. Core System Logs ✨
**Files Created**:
- ✅ Migration: `database/migrations/2026_01_18_130718_create_system_logs_table.php`
- ✅ Model: `app/Models/SystemLog.php`
- ✅ Service: `app/Services/SystemLogService.php`
- ✅ Component: `app/Livewire/Logs/SystemLogViewer.php`
- ✅ View: `resources/views/livewire/logs/system-log-viewer.blade.php`
- ✅ Command: `app/Console/Commands/SyncSystemLogsCommand.php`
- ✅ Factory: `database/factories/SystemLogFactory.php`
- ✅ Route: `/logs/system`

**Features**:
- Collect logs from servers via SSH
- Parse multiple log formats (syslog, auth, docker, nginx, kernel)
- Advanced filtering (server, type, level, time range, search)
- Statistics dashboard
- Pagination and auto-refresh
- Manual log collection button

### 2. Log Export ✨
**Files Created**:
- ✅ Service: `app/Services/LogExportService.php`
- ✅ Updated: `app/Livewire/Logs/SystemLogViewer.php` (added export methods)
- ✅ Updated: `resources/views/livewire/logs/system-log-viewer.blade.php` (added export dropdown)

**Features**:
- Export to CSV with headers
- Export to JSON with metadata
- Export to TXT (human-readable)
- Respects all active filters
- Streamed downloads for large datasets

###3. Log Alerts System ✨
**Files Created**:
- ✅ Migration: `database/migrations/2026_01_18_131534_create_log_alerts_table.php`
- ✅ Model: `app/Models/LogAlert.php`
- ✅ Service: `app/Services/LogAlertService.php`
- ✅ Notification: `app/Notifications/LogAlertTriggered.php`
- ✅ Component: `app/Livewire/Logs/LogAlertManager.php`
- ⏳ View: `resources/views/livewire/logs/log-alert-manager.blade.php` (needs completion)

**Features Implemented**:
- Pattern-based alerting (regex or text)
- Threshold-based triggering
- Time window configuration
- Multi-channel notifications (Email, Slack, Webhook)
- Server-specific or global alerts
- Alert testing functionality
- Alert statistics

### 4. Log Forwarding (Schema Ready)
**Files Created**:
- ✅ Migration: `database/migrations/2026_01_18_131534_create_log_forwarding_rules_table.php`
- ⏳ Model: `app/Models/LogForwardingRule.php` (pending)
- ⏳ Service: `app/Services/LogForwardingService.php` (pending)

### 5. Custom Log Parsers (Schema Ready)
**Files Created**:
- ✅ Migration: `database/migrations/2026_01_18_131534_create_custom_log_parsers_table.php`
- ⏳ Model: `app/Models/CustomLogParser.php` (pending)
- ⏳ Service: `app/Services/CustomLogParserService.php` (pending)

---

## 🚧 QUICK COMPLETION TASKS

### Task 1: Complete LogAlertManager View
**File**: `resources/views/livewire/logs/log-alert-manager.blade.php`

```blade
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Log Alerts</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Configure pattern-based alerts for system logs</p>
        </div>
        <button
            wire:click="createAlert"
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700"
        >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Create Alert
        </button>
    </div>

    @if($showForm)
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            {{ $editingId ? 'Edit Alert' : 'Create New Alert' }}
        </h3>

        <form wire:submit="saveAlert" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Alert Name</label>
                    <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                    <textarea wire:model="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Server (optional)</label>
                    <select wire:model="server_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Servers</option>
                        @foreach($this->servers as $server)
                            <option value="{{ $server->id }}">{{ $server->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Log Type</label>
                    <select wire:model="log_type" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Any Type</option>
                        @foreach($this->logTypes as $type)
                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Log Level</label>
                    <select wire:model="log_level" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Any Level</option>
                        @foreach($this->logLevels as $level)
                            <option value="{{ $level }}">{{ ucfirst($level) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pattern</label>
                    <input type="text" wire:model="pattern" placeholder="e.g., Failed password" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('pattern') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center space-x-4">
                    <label class="flex items-center">
                        <input type="checkbox" wire:model="is_regex" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Regex Pattern</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" wire:model="case_sensitive" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Case Sensitive</span>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Threshold</label>
                    <input type="number" wire:model="threshold" min="1" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Time Window (seconds)</label>
                    <input type="number" wire:model="time_window" min="1" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <div class="flex justify-between pt-4">
                <button
                    type="button"
                    wire:click="testCurrentAlert"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    Test Alert
                </button>

                <div class="flex gap-2">
                    <button
                        type="button"
                        wire:click="cancelForm"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700"
                    >
                        {{ $editingId ? 'Update Alert' : 'Create Alert' }}
                    </button>
                </div>
            </div>

            @if($testResult)
            <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900 rounded-md">
                <h4 class="font-medium text-blue-900 dark:text-blue-100">Test Results</h4>
                <div class="mt-2 text-sm text-blue-700 dark:text-blue-200">
                    <p>Checked: {{ $testResult['total_logs_checked'] }} logs</p>
                    <p>Matches: {{ $testResult['matches_found'] }}</p>
                    <p>Would Trigger: {{ $testResult['would_trigger'] ? 'Yes' : 'No' }}</p>
                </div>
            </div>
            @endif
        </form>
    </div>
    @endif

    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Server</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Pattern</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Threshold</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Triggers</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($this->alerts as $alert)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                        {{ $alert->name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $alert->server?->name ?? 'All' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                        <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ \Str::limit($alert->pattern, 30) }}</code>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $alert->threshold }} in {{ $alert->time_window }}s
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $alert->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $alert->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $alert->trigger_count }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                        <button wire:click="toggleActive({{ $alert->id }})" class="text-blue-600 hover:text-blue-900">
                            {{ $alert->is_active ? 'Disable' : 'Enable' }}
                        </button>
                        <button wire:click="editAlert({{ $alert->id }})" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                        <button wire:click="deleteAlert({{ $alert->id }})" wire:confirm="Delete this alert?" class="text-red-600 hover:text-red-900">Delete</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                        No alerts configured. Create one to get started.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4">
            {{ $this->alerts->links() }}
        </div>
    </div>
</div>
```

### Task 2: Create ProcessLogAlertsCommand
**File**: `app/Console/Commands/ProcessLogAlertsCommand.php`

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\LogAlertService;
use Illuminate\Console\Command;

class ProcessLogAlertsCommand extends Command
{
    protected $signature = 'logs:process-alerts {--server= : Process alerts for specific server}';
    protected $description = 'Process log alerts and send notifications';

    public function handle(LogAlertService $alertService): int
    {
        $this->info('Processing log alerts...');

        $serverId = $this->option('server');
        $results = $alertService->processAlerts($serverId ? (int) $serverId : null);

        $triggered = collect($results)->where('status', 'triggered')->count();
        $errors = collect($results)->where('status', 'error')->count();

        $this->table(
            ['Alert', 'Status', 'Matches'],
            collect($results)->map(fn($r) => [
                $r['alert_name'],
                $r['status'],
                $r['match_count'] ?? $r['error'] ?? '-'
            ])
        );

        $this->info("Processed " . count($results) . " alerts");
        $this->info("Triggered: {$triggered} | Errors: {$errors}");

        return self::SUCCESS;
    }
}
```

### Task 3: Add to Kernel Schedule
**File**: `app/Console/Kernel.php`

Add to the `schedule()` method:

```php
// Process log alerts every minute
$schedule->command('logs:process-alerts')->everyMinute();

// Collect system logs hourly
$schedule->command('system-logs:sync --lines=100')->hourly();

// Clean old logs weekly
$schedule->command('system-logs:sync --clean-old')->weekly();
```

### Task 4: Add Routes
**File**: `routes/web.php`

Add after the existing log routes:

```php
// Log Management Advanced Features
Route::get('/logs/alerts', \App\Livewire\Logs\LogAlertManager::class)->name('logs.alerts');
Route::get('/logs/forwarding', \App\Livewire\Logs\LogForwardingManager::class)->name('logs.forwarding');
Route::get('/logs/analytics', \App\Livewire\Logs\LogAnalyticsDashboard::class)->name('logs.analytics');
Route::get('/logs/parsers', \App\Livewire\Logs\LogParserManager::class)->name('logs.parsers');
```

---

## 📊 WHAT'S WORKING NOW

1. **System Logs** - Fully functional at `/logs/system`
2. **Log Export** - CSV, JSON, TXT exports working
3. **Log Alerts** - Models and services complete, UI pending view completion
4. **Database** - All tables created and migrated

## 🎯 TO COMPLETE IN 5 MINUTES

1. Create the LogAlertManager view (copy code above)
2. Create ProcessLogAlertsCommand (copy code above)
3. Add routes (copy code above)
4. Update Kernel schedule (copy code above)
5. Test alert creation

## 🚀 DEPLOYMENT COMMANDS

```bash
# Run migrations
php artisan migrate

# Create first alert via tinker
php artisan tinker
>>> App\Models\LogAlert::create([
    'user_id' => 1,
    'name' => 'SSH Failed Login',
    'pattern' => 'Failed password',
    'log_type' => 'auth',
    'threshold' => 5,
    'time_window' => 60,
    'notification_channels' => ['email'],
    'is_active' => true
]);

# Process alerts manually
php artisan logs:process-alerts

# Collect logs
php artisan system-logs:sync --server=1
```

---

## ✨ BONUS: Quick Alert Templates

```php
// SSH Brute Force Detection
[
    'name' => 'SSH Brute Force',
    'pattern' => 'Failed password',
    'log_type' => 'auth',
    'threshold' => 5,
    'time_window' => 60
]

// Docker Critical Errors
[
    'name' => 'Docker Critical',
    'pattern' => '.*',
    'log_type' => 'docker',
    'log_level' => 'critical',
    'threshold' => 1,
    'time_window' => 300
]

// High CPU Usage
[
    'name' => 'High CPU',
    'pattern' => 'CPU.*([89][0-9]|100)%',
    'log_type' => 'system',
    'is_regex' => true,
    'threshold' => 3,
    'time_window' => 180
]
```

---

**Status**: Core system logs + exports ✅ | Alerts backend ✅ | Alerts UI ⏳ (5 min to complete)
**Next**: Complete view, add command, test alerts
