<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Logs</h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Centralized log aggregation and search
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <button
                        wire:click="syncNow"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg wire:loading.remove wire:target="syncNow" class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <svg wire:loading wire:target="syncNow" class="animate-spin mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Sync Now
                    </button>
                    <button
                        wire:click="exportLogs"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Export
                    </button>
                </div>
            </div>

            <!-- Statistics -->
            <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-4">
                <div class="bg-gray-50 dark:bg-gray-700 overflow-hidden rounded-lg px-4 py-5">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Logs</dt>
                    <dd class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">{{ number_format($this->statistics['total']) }}</dd>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 overflow-hidden rounded-lg px-4 py-5">
                    <dt class="text-sm font-medium text-red-600 dark:text-red-400 truncate">Errors</dt>
                    <dd class="mt-1 text-3xl font-semibold text-red-900 dark:text-red-300">{{ number_format($this->statistics['error']) }}</dd>
                </div>
                <div class="bg-yellow-50 dark:bg-yellow-900/20 overflow-hidden rounded-lg px-4 py-5">
                    <dt class="text-sm font-medium text-yellow-600 dark:text-yellow-400 truncate">Warnings</dt>
                    <dd class="mt-1 text-3xl font-semibold text-yellow-900 dark:text-yellow-300">{{ number_format($this->statistics['warning']) }}</dd>
                </div>
                <div class="bg-purple-50 dark:bg-purple-900/20 overflow-hidden rounded-lg px-4 py-5">
                    <dt class="text-sm font-medium text-purple-600 dark:text-purple-400 truncate">Critical</dt>
                    <dd class="mt-1 text-3xl font-semibold text-purple-900 dark:text-purple-300">{{ number_format($this->statistics['critical']) }}</dd>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Server Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Server</label>
                    <select wire:model.live="server_id" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="">All Servers</option>
                        @foreach($this->servers as $server)
                            <option value="{{ $server->id }}">{{ $server->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Project Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Project</label>
                    <select wire:model.live="project_id" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" @disabled(!$server_id)>
                        <option value="">All Projects</option>
                        @foreach($this->projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Source Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Source</label>
                    <select wire:model.live="source" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="all">All Sources</option>
                        <option value="nginx">Nginx</option>
                        <option value="php">PHP</option>
                        <option value="laravel">Laravel</option>
                        <option value="mysql">MySQL</option>
                        <option value="system">System</option>
                        <option value="docker">Docker</option>
                    </select>
                </div>

                <!-- Level Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Level</label>
                    <select wire:model.live="level" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="all">All Levels</option>
                        <option value="debug">Debug</option>
                        <option value="info">Info</option>
                        <option value="notice">Notice</option>
                        <option value="warning">Warning</option>
                        <option value="error">Error</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">From</label>
                    <input type="datetime-local" wire:model.live="dateFrom" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>

                <!-- Date To -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">To</label>
                    <input type="datetime-local" wire:model.live="dateTo" class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>
            </div>

            <!-- Search -->
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.500ms="search"
                        placeholder="Search in messages and file paths..."
                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm pl-10">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Clear Filters -->
            <div class="mt-4 flex justify-end">
                <button
                    wire:click="clearFilters"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Clear Filters
                </button>
            </div>
        </div>

        <!-- Log Entries -->
        <div class="mt-6 space-y-3">
            @forelse($this->logs as $log)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-750" wire:click="toggleExpand({{ $log->id }})">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-3 mb-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                        {{ $log->logged_at->format('Y-m-d H:i:s') }}
                                    </span>

                                    <!-- Level Badge -->
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($log->level_color === 'gray') bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300
                                        @elseif($log->level_color === 'blue') bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300
                                        @elseif($log->level_color === 'yellow') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300
                                        @elseif($log->level_color === 'red') bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300
                                        @elseif($log->level_color === 'purple') bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300
                                        @endif">
                                        {{ strtoupper($log->level) }}
                                    </span>

                                    <!-- Source Badge -->
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($log->source_badge_color === 'green') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300
                                        @elseif($log->source_badge_color === 'indigo') bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300
                                        @elseif($log->source_badge_color === 'red') bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300
                                        @elseif($log->source_badge_color === 'blue') bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300
                                        @elseif($log->source_badge_color === 'cyan') bg-cyan-100 dark:bg-cyan-900/30 text-cyan-800 dark:text-cyan-300
                                        @else bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-300
                                        @endif">
                                        {{ strtoupper($log->source) }}
                                    </span>

                                    <span class="text-xs text-gray-600 dark:text-gray-400">
                                        {{ $log->server->name }}
                                        @if($log->project)
                                            <span class="text-gray-400 dark:text-gray-500">→</span> {{ $log->project->name }}
                                        @endif
                                    </span>
                                </div>

                                <p class="text-sm text-gray-900 dark:text-gray-100 font-mono">
                                    {{ $expandedLogId === $log->id ? $log->message : $log->truncated_message }}
                                </p>

                                @if($log->location)
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 font-mono">
                                        {{ $log->location }}
                                    </p>
                                @endif
                            </div>

                            <div class="ml-4">
                                <svg class="h-5 w-5 text-gray-400 transform transition-transform {{ $expandedLogId === $log->id ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>

                        @if($expandedLogId === $log->id && $log->context)
                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Context:</h4>
                                <pre class="text-xs text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-900 rounded p-3 overflow-x-auto">{{ json_encode($log->context, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No logs found</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting your filters or sync logs from a server.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($this->logs->hasPages())
            <div class="mt-6">
                {{ $this->logs->links() }}
            </div>
        @endif
    </div>
</div>

@script
<script>
    $wire.on('download', (data) => {
        const link = document.createElement('a');
        link.href = 'data:text/csv;base64,' + data[0].content;
        link.download = data[0].filename;
        link.click();
    });
</script>
@endscript
