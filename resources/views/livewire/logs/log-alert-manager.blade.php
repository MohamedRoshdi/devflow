<div class="space-y-6">
    {{-- Header --}}
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

    {{-- Alert Form --}}
    @if($showForm)
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            {{ $editingId ? 'Edit Alert' : 'Create New Alert' }}
        </h3>

        <form wire:submit="saveAlert" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                {{-- Alert Name --}}
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Alert Name</label>
                    <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                {{-- Description --}}
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                    <textarea wire:model="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>

                {{-- Server --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Server (optional)</label>
                    <select wire:model="server_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Servers</option>
                        @foreach($this->servers as $server)
                            <option value="{{ $server->id }}">{{ $server->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Log Type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Log Type</label>
                    <select wire:model="log_type" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Any Type</option>
                        @foreach($this->logTypes as $type)
                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Log Level --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Log Level</label>
                    <select wire:model="log_level" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Any Level</option>
                        @foreach($this->logLevels as $level)
                            <option value="{{ $level }}">{{ ucfirst($level) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Pattern --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pattern</label>
                    <input type="text" wire:model="pattern" placeholder="e.g., Failed password" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('pattern') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                {{-- Options --}}
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

                {{-- Threshold --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Threshold</label>
                    <input type="number" wire:model="threshold" min="1" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                {{-- Time Window --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Time Window (seconds)</label>
                    <input type="number" wire:model="time_window" min="1" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            {{-- Test Results --}}
            @if($testResult)
            <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900 rounded-md">
                <h4 class="font-medium text-blue-900 dark:text-blue-100">Test Results</h4>
                <div class="mt-2 text-sm text-blue-700 dark:text-blue-200">
                    <p>Checked: {{ $testResult['total_logs_checked'] }} logs</p>
                    <p>Matches: {{ $testResult['matches_found'] }}</p>
                    <p class="font-semibold">Would Trigger: {{ $testResult['would_trigger'] ? 'Yes ✓' : 'No ✗' }}</p>

                    @if(count($testResult['sample_matches']) > 0)
                    <div class="mt-2">
                        <p class="font-medium">Sample Matches:</p>
                        <ul class="mt-1 space-y-1">
                            @foreach($testResult['sample_matches'] as $match)
                            <li class="text-xs bg-blue-100 dark:bg-blue-800 p-2 rounded">
                                [{{ $match['logged_at'] }}] {{ \Str::limit($match['message'], 100) }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Actions --}}
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
        </form>
    </div>
    @endif

    {{-- Alerts Table --}}
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
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $alert->name }}</div>
                        @if($alert->description)
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ \Str::limit($alert->description, 40) }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $alert->server?->name ?? 'All Servers' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                        <code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-xs">
                            {{ \Str::limit($alert->pattern, 30) }}
                        </code>
                        @if($alert->is_regex)
                        <span class="ml-1 text-xs text-blue-600 dark:text-blue-400">(regex)</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $alert->threshold }} in {{ $alert->time_window }}s
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $alert->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ $alert->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900 dark:text-white">{{ $alert->trigger_count }}</div>
                        @if($alert->last_triggered_at)
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $alert->last_triggered_at->diffForHumans() }}
                        </div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                        <button
                            wire:click="toggleActive({{ $alert->id }})"
                            class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                        >
                            {{ $alert->is_active ? 'Disable' : 'Enable' }}
                        </button>
                        <button
                            wire:click="editAlert({{ $alert->id }})"
                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                        >
                            Edit
                        </button>
                        <button
                            wire:click="deleteAlert({{ $alert->id }})"
                            wire:confirm="Are you sure you want to delete this alert?"
                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                        >
                            Delete
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <p class="mt-2">No alerts configured</p>
                        <button
                            wire:click="createAlert"
                            class="mt-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700"
                        >
                            Create Your First Alert
                        </button>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        @if($this->alerts->hasPages())
        <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
            {{ $this->alerts->links() }}
        </div>
        @endif
    </div>
</div>
