<div>
    <!-- Hero Section -->
    <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-indigo-800 via-purple-900 to-indigo-800 p-8 shadow-2xl overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="provisioning-pattern" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                        <rect x="0" y="0" width="4" height="4" fill="currentColor" class="text-white"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#provisioning-pattern)"/>
            </svg>
        </div>

        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex items-start gap-4">
                    <!-- Icon -->
                    <div class="p-4 bg-white/10 backdrop-blur-md rounded-2xl">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>

                    <div>
                        <h1 class="text-3xl font-bold text-white">Provisioning Logs</h1>
                        <p class="text-white/80 mt-2">{{ $server->name }} - Provisioning History</p>
                        <div class="flex flex-wrap items-center gap-3 mt-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/10 text-white/90">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                                </svg>
                                {{ $server->ip_address }}
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/10 text-white/90">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $this->stats['total'] }} Total Logs
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('servers.show', $server) }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-all duration-200 font-medium flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Server
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <!-- Total -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 transition-colors">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Logs</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $this->stats['total'] }}</p>
                </div>
                <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded-xl">
                    <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Completed -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 transition-colors">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Completed</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ $this->stats['completed'] }}</p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-xl">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Failed -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 transition-colors">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Failed</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ $this->stats['failed'] }}</p>
                </div>
                <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-xl">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Running -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 transition-colors">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Running</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $this->stats['running'] }}</p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Avg Duration -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 transition-colors">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Avg Duration</p>
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1">
                        @if($this->stats['avg_duration'])
                            {{ number_format($this->stats['avg_duration'], 0) }}s
                        @else
                            -
                        @endif
                    </p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Logs -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl transition-colors overflow-hidden">
        <!-- Header with Filters -->
        <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-750">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Provisioning Timeline
                </h2>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Status Filter -->
                    <select wire:model.live="statusFilter"
                            class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="running">Running</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                    </select>

                    <!-- Date Range Filter -->
                    <select wire:model.live="dateRange"
                            class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        <option value="all">All Time</option>
                        <option value="today">Today</option>
                        <option value="week">Last Week</option>
                        <option value="month">Last Month</option>
                    </select>

                    <!-- Reset Button -->
                    @if($statusFilter !== 'all' || $dateRange !== 'all')
                        <button wire:click="resetFilters"
                                class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Reset
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Logs Timeline -->
        <div class="p-6">
            @if($this->logs->count() > 0)
                <div class="space-y-4">
                    @foreach($this->logs as $log)
                        <div class="relative">
                            <!-- Timeline Line (except for last item) -->
                            @if(!$loop->last)
                                <div class="absolute left-6 top-14 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>
                            @endif

                            <!-- Log Entry -->
                            <div class="relative flex gap-4 group">
                                <!-- Status Icon -->
                                <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center z-10 shadow-lg
                                    {{ $log->getStatusBadgeClass() }} border">
                                    @if($log->isCompleted())
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    @elseif($log->isFailed())
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    @elseif($log->isRunning())
                                        <svg class="w-6 h-6 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                    @else
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    @endif
                                </div>

                                <!-- Log Content -->
                                <div class="flex-1 bg-gradient-to-br from-gray-50 to-white dark:from-gray-700/50 dark:to-gray-800/50 rounded-xl p-5 border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-all duration-200">
                                    <div class="flex items-start justify-between gap-4 mb-3">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                                                {{ str_replace('_', ' ', ucfirst($log->task)) }}
                                            </h3>
                                            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                    {{ $log->created_at->format('M d, Y') }}
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    {{ $log->created_at->format('h:i A') }}
                                                </span>
                                                @if($log->duration_seconds)
                                                    <span class="flex items-center gap-1 font-semibold text-purple-600 dark:text-purple-400">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                        </svg>
                                                        {{ $log->duration_seconds }}s
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <!-- Status Badge -->
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border
                                                {{ $log->getStatusBadgeClass() }}">
                                                {{ ucfirst($log->status) }}
                                            </span>

                                            <!-- Expand Button -->
                                            @if($log->output || $log->error_message)
                                                <button wire:click="toggleLogExpansion({{ $log->id }})"
                                                        class="p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-all">
                                                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 transition-transform duration-200
                                                        {{ $expandedLogId === $log->id ? 'rotate-180' : '' }}"
                                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Error Message Preview (if failed) -->
                                    @if($log->error_message && $expandedLogId !== $log->id)
                                        <div class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                            <p class="text-sm text-red-700 dark:text-red-400 font-mono">
                                                {{ Str::limit($log->error_message, 150) }}
                                            </p>
                                        </div>
                                    @endif

                                    <!-- Expanded Output -->
                                    @if($expandedLogId === $log->id)
                                        <div class="mt-4 space-y-3">
                                            @if($log->error_message)
                                                <div>
                                                    <h4 class="text-sm font-semibold text-red-600 dark:text-red-400 mb-2 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                        </svg>
                                                        Error Message
                                                    </h4>
                                                    <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                                        <pre class="text-sm text-red-700 dark:text-red-400 font-mono whitespace-pre-wrap break-words">{{ $log->error_message }}</pre>
                                                    </div>
                                                </div>
                                            @endif

                                            @if($log->output)
                                                <div>
                                                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                        </svg>
                                                        Command Output
                                                    </h4>
                                                    <div class="p-4 bg-gray-900 dark:bg-gray-950 border border-gray-700 rounded-lg max-h-96 overflow-y-auto">
                                                        <pre class="text-sm text-green-400 font-mono whitespace-pre-wrap break-words">{{ $log->output }}</pre>
                                                    </div>
                                                </div>
                                            @endif

                                            <!-- Execution Details -->
                                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                                                @if($log->started_at)
                                                    <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                                        <p class="text-blue-600 dark:text-blue-400 font-medium mb-1">Started At</p>
                                                        <p class="text-gray-900 dark:text-white font-mono">{{ $log->started_at->format('Y-m-d H:i:s') }}</p>
                                                    </div>
                                                @endif

                                                @if($log->completed_at)
                                                    <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                                        <p class="text-green-600 dark:text-green-400 font-medium mb-1">Completed At</p>
                                                        <p class="text-gray-900 dark:text-white font-mono">{{ $log->completed_at->format('Y-m-d H:i:s') }}</p>
                                                    </div>
                                                @endif

                                                @if($log->duration_seconds)
                                                    <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                                        <p class="text-purple-600 dark:text-purple-400 font-medium mb-1">Duration</p>
                                                        <p class="text-gray-900 dark:text-white font-mono">{{ $log->duration_seconds }} seconds</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $this->logs->links() }}
                </div>
            @else
                <!-- Empty State -->
                <div class="text-center py-16">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gray-100 dark:bg-gray-700 mb-4">
                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No Provisioning Logs Found</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">
                        @if($statusFilter !== 'all' || $dateRange !== 'all')
                            No logs match your current filters. Try adjusting your filters.
                        @else
                            This server doesn't have any provisioning logs yet.
                        @endif
                    </p>
                    @if($statusFilter !== 'all' || $dateRange !== 'all')
                        <button wire:click="resetFilters"
                                class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium transition-all inline-flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Reset Filters
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
