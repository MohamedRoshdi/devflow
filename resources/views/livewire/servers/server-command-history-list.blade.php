<div>
    <!-- Header with Stats -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Command History</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    All commands executed on this server
                    @if($server->is_current_server)
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                            Local Execution
                        </span>
                    @endif
                </p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ array_sum($this->statusCounts) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Total Commands</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ $this->statusCounts['success'] ?? 0 }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Successful</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700">
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                    {{ $this->statusCounts['failed'] ?? 0 }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Failed</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700">
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                    {{ count($this->actionCounts) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Action Types</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3 items-center">
            <select wire:model.live="statusFilter"
                    class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="">All Statuses</option>
                <option value="success">Success</option>
                <option value="failed">Failed</option>
                <option value="running">Running</option>
                <option value="pending">Pending</option>
            </select>

            <select wire:model.live="actionFilter"
                    class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="">All Actions</option>
                @foreach($this->actionCounts as $action => $count)
                    <option value="{{ $action }}">{{ ucfirst(str_replace('_', ' ', $action)) }} ({{ $count }})</option>
                @endforeach
            </select>

            <select wire:model.live="executionTypeFilter"
                    class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="">All Types</option>
                <option value="local">Local</option>
                <option value="ssh">SSH</option>
            </select>

            @if($statusFilter || $actionFilter || $executionTypeFilter)
                <button wire:click="clearFilters"
                        class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    Clear filters
                </button>
            @endif
        </div>
    </div>

    <!-- Command History List -->
    <div class="space-y-2">
        @forelse($this->commandHistory as $command)
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <!-- Command Header (clickable) -->
                <button wire:click="toggleExpand({{ $command->id }})"
                        class="w-full px-4 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="flex items-center gap-3">
                        <!-- Status Icon -->
                        <div class="flex-shrink-0">
                            @if($command->status === 'success')
                                <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            @elseif($command->status === 'failed')
                                <div class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </div>
                            @elseif($command->status === 'running')
                                <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </div>
                            @else
                                <div class="w-8 h-8 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <!-- Action & Details -->
                        <div class="text-left">
                            <div class="font-medium text-gray-900 dark:text-white">
                                {{ $command->action_name }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                <span>{{ $command->created_at->diffForHumans() }}</span>
                                <span class="text-gray-300 dark:text-gray-600">|</span>
                                <span>{{ $command->user?->name ?? 'System' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right side: badges and chevron -->
                    <div class="flex items-center gap-2">
                        <!-- Execution Type Badge -->
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                            {{ $command->execution_type === 'local'
                                ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300'
                                : 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-300' }}">
                            {{ $command->execution_type === 'local' ? 'Local' : 'SSH' }}
                        </span>

                        <!-- Duration -->
                        @if($command->duration_ms)
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $command->formatted_duration }}
                            </span>
                        @endif

                        <!-- Expand Chevron -->
                        <svg class="w-5 h-5 text-gray-400 transition-transform {{ $expandedId === $command->id ? 'rotate-180' : '' }}"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </button>

                <!-- Expanded Details -->
                @if($expandedId === $command->id)
                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
                        <!-- Command -->
                        @if($command->command)
                            <div class="mb-3">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Command</div>
                                <code class="block text-sm bg-gray-900 text-green-400 p-2 rounded overflow-x-auto">
                                    {{ $command->command }}
                                </code>
                            </div>
                        @endif

                        <!-- Output -->
                        @if($command->output)
                            <div class="mb-3">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Output</div>
                                <pre class="text-xs bg-gray-900 text-gray-300 p-2 rounded overflow-x-auto max-h-48 overflow-y-auto">{{ $command->output }}</pre>
                            </div>
                        @endif

                        <!-- Error Output -->
                        @if($command->error_output)
                            <div class="mb-3">
                                <div class="text-xs font-medium text-red-500 dark:text-red-400 mb-1">Error Output</div>
                                <pre class="text-xs bg-red-950 text-red-300 p-2 rounded overflow-x-auto max-h-48 overflow-y-auto">{{ $command->error_output }}</pre>
                            </div>
                        @endif

                        <!-- Metadata -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Exit Code:</span>
                                <span class="ml-1 font-medium text-gray-900 dark:text-white">
                                    {{ $command->exit_code ?? 'N/A' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Started:</span>
                                <span class="ml-1 font-medium text-gray-900 dark:text-white">
                                    {{ $command->started_at?->format('H:i:s') ?? 'N/A' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Completed:</span>
                                <span class="ml-1 font-medium text-gray-900 dark:text-white">
                                    {{ $command->completed_at?->format('H:i:s') ?? 'N/A' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Duration:</span>
                                <span class="ml-1 font-medium text-gray-900 dark:text-white">
                                    {{ $command->formatted_duration }}
                                </span>
                            </div>
                        </div>

                        @if($command->metadata)
                            <div class="mt-3">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Additional Info</div>
                                <pre class="text-xs bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 p-2 rounded overflow-x-auto">{{ json_encode($command->metadata, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No command history</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Commands executed on this server will appear here.
                </p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($this->commandHistory->hasPages())
        <div class="mt-4">
            {{ $this->commandHistory->links() }}
        </div>
    @endif
</div>
