<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Audit Log Viewer</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            View and filter system audit logs
        </p>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search" id="search"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <!-- User Filter -->
            <div>
                <label for="userId" class="block text-sm font-medium text-gray-700 dark:text-gray-300">User</label>
                <select wire:model.live="userId" id="userId"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Users</option>
                    @foreach($this->users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Action Filter -->
            <div>
                <label for="action" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Action</label>
                <input type="text" wire:model.live.debounce.300ms="action" id="action"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <!-- Model Type Filter -->
            <div>
                <label for="modelType" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Model Type</label>
                <select wire:model.live="modelType" id="modelType"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Types</option>
                    @foreach($this->modelTypes as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            <!-- From Date -->
            <div>
                <label for="fromDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">From Date</label>
                <input type="date" wire:model.live="fromDate" id="fromDate"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <!-- To Date -->
            <div>
                <label for="toDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300">To Date</label>
                <input type="date" wire:model.live="toDate" id="toDate"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <!-- Clear Filters -->
            <div class="flex items-end">
                <button wire:click="clearFilters"
                    class="w-full px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white font-medium rounded-md">
                    Clear Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Stats -->
    @if($this->stats)
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">Total Events</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->stats['total'] ?? 0 }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">Unique Users</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->stats['unique_users'] ?? 0 }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">Actions Today</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->stats['today'] ?? 0 }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">Most Active</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->stats['most_active_action'] ?? 'N/A' }}</p>
        </div>
    </div>
    @endif

    <!-- Logs Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Timestamp
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        User
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Action
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Model
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        IP Address
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($this->logs as $log)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                        {{ $log->created_at->format('Y-m-d H:i:s') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                        {{ $log->user->name ?? 'System' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                        {{ $log->action }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                        {{ class_basename($log->auditable_type ?? '') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                        {{ $log->ip_address ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                        <button wire:click="toggleExpand({{ $log->id }})"
                            class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
                            {{ $expandedLogId === $log->id ? 'Hide' : 'Show' }} Details
                        </button>
                    </td>
                </tr>
                @if($expandedLogId === $log->id)
                <tr>
                    <td colspan="6" class="px-6 py-4 bg-gray-50 dark:bg-gray-700">
                        <pre class="text-xs text-gray-800 dark:text-gray-200 overflow-auto">{{ json_encode($log->properties ?? [], JSON_PRETTY_PRINT) }}</pre>
                    </td>
                </tr>
                @endif
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        No logs found
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
