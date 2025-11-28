<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Resource Alerts</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Configure alerts for {{ $server->name }} server resources
            </p>
        </div>
        <button wire:click="openCreateModal"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors duration-150">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Alert
        </button>
    </div>

    {{-- Current Metrics --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Current Metrics</h3>
            <button wire:click="refreshMetrics"
                    class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium">
                Refresh
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- CPU Usage --}}
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">CPU Usage</span>
                    </div>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($this->currentMetrics['cpu'], 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="h-2 rounded-full transition-all duration-300 {{ $this->currentMetrics['cpu'] > 80 ? 'bg-red-600' : ($this->currentMetrics['cpu'] > 60 ? 'bg-yellow-500' : 'bg-green-600') }}"
                         style="width: {{ min($this->currentMetrics['cpu'], 100) }}%"></div>
                </div>
            </div>

            {{-- Memory Usage --}}
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Memory</span>
                    </div>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($this->currentMetrics['memory'], 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="h-2 rounded-full transition-all duration-300 {{ $this->currentMetrics['memory'] > 80 ? 'bg-red-600' : ($this->currentMetrics['memory'] > 60 ? 'bg-yellow-500' : 'bg-green-600') }}"
                         style="width: {{ min($this->currentMetrics['memory'], 100) }}%"></div>
                </div>
            </div>

            {{-- Disk Usage --}}
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Disk</span>
                    </div>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($this->currentMetrics['disk'], 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="h-2 rounded-full transition-all duration-300 {{ $this->currentMetrics['disk'] > 80 ? 'bg-red-600' : ($this->currentMetrics['disk'] > 60 ? 'bg-yellow-500' : 'bg-green-600') }}"
                         style="width: {{ min($this->currentMetrics['disk'], 100) }}%"></div>
                </div>
            </div>

            {{-- Load Average --}}
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Load Avg</span>
                    </div>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($this->currentMetrics['load'], 2) }}</span>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    1-minute average
                </div>
            </div>
        </div>
    </div>

    {{-- Alerts List --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Configured Alerts</h3>
        </div>

        @if($this->alerts->isEmpty())
            <div class="px-6 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No alerts configured</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new alert.</p>
                <div class="mt-6">
                    <button wire:click="openCreateModal"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors duration-150">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Alert
                    </button>
                </div>
            </div>
        @else
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($this->alerts as $alert)
                    <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors duration-150">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4 flex-1">
                                {{-- Resource Icon --}}
                                <div class="flex-shrink-0">
                                    @if($alert->resource_type === 'cpu')
                                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                                            </svg>
                                        </div>
                                    @elseif($alert->resource_type === 'memory')
                                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                            </svg>
                                        </div>
                                    @elseif($alert->resource_type === 'disk')
                                        <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                                            <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                                            </svg>
                                        </div>
                                    @else
                                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                {{-- Alert Details --}}
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">{{ $alert->resource_type_label }}</h4>
                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $alert->threshold_display }}</span>
                                        @if($alert->isInCooldown())
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                                Cooldown: {{ $alert->cooldown_remaining_minutes }}m
                                            </span>
                                        @endif
                                    </div>
                                    <div class="mt-1 flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
                                        <span>Cooldown: {{ $alert->cooldown_minutes }} minutes</span>
                                        @if($alert->notification_channels)
                                            <span>•</span>
                                            <span>
                                                Channels:
                                                @foreach(array_keys($alert->notification_channels) as $channel)
                                                    <span class="capitalize">{{ $channel }}</span>@if(!$loop->last), @endif
                                                @endforeach
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Status Toggle --}}
                                <div class="flex items-center space-x-3">
                                    <button wire:click="toggleAlert({{ $alert->id }})"
                                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 {{ $alert->is_active ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700' }}"
                                            role="switch"
                                            aria-checked="{{ $alert->is_active ? 'true' : 'false' }}">
                                        <span class="sr-only">Toggle alert</span>
                                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $alert->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                    </button>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center space-x-2 ml-4">
                                <button wire:click="testAlert({{ $alert->id }})"
                                        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors duration-150"
                                        title="Test Alert">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </button>
                                <button wire:click="openEditModal({{ $alert->id }})"
                                        class="p-2 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors duration-150"
                                        title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button wire:click="deleteAlert({{ $alert->id }})"
                                        wire:confirm="Are you sure you want to delete this alert?"
                                        class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors duration-150"
                                        title="Delete">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Alert History --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Alert History</h3>
        </div>

        @if($this->alertHistory->isEmpty())
            <div class="px-6 py-12 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">No alert history available.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Timestamp</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Resource</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Value</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Threshold</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Message</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->alertHistory as $history)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $history->created_at->format('M d, Y H:i:s') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        @if($history->resource_type === 'cpu')
                                            <svg class="w-4 h-4 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                                            </svg>
                                        @elseif($history->resource_type === 'memory')
                                            <svg class="w-4 h-4 mr-2 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                            </svg>
                                        @elseif($history->resource_type === 'disk')
                                            <svg class="w-4 h-4 mr-2 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4 mr-2 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                            </svg>
                                        @endif
                                        <span class="text-sm text-gray-900 dark:text-white capitalize">{{ $history->resource_type }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ number_format($history->current_value, 2) }}{{ in_array($history->resource_type, ['cpu', 'memory', 'disk']) ? '%' : '' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ number_format($history->threshold_value, 2) }}{{ in_array($history->resource_type, ['cpu', 'memory', 'disk']) ? '%' : '' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($history->status === 'triggered')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                            Triggered
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                            Resolved
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ $history->message }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $this->alertHistory->links() }}
            </div>
        @endif
    </div>

    {{-- Create/Edit Modal --}}
    @if($showCreateModal || $showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 transition-opacity" wire:click="{{ $showCreateModal ? 'closeCreateModal' : 'closeEditModal' }}"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <form wire:submit.prevent="{{ $showCreateModal ? 'createAlert' : 'updateAlert' }}">
                        <div class="bg-white dark:bg-gray-800 px-6 pt-6 pb-4">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $showCreateModal ? 'Create New Alert' : 'Edit Alert' }}
                                </h3>
                                <button type="button"
                                        wire:click="{{ $showCreateModal ? 'closeCreateModal' : 'closeEditModal' }}"
                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="space-y-4">
                                {{-- Resource Type --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Resource Type</label>
                                    <select wire:model="resource_type"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                        <option value="cpu">CPU Usage (%)</option>
                                        <option value="memory">Memory Usage (%)</option>
                                        <option value="disk">Disk Usage (%)</option>
                                        <option value="load">Load Average</option>
                                    </select>
                                    @error('resource_type') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                                </div>

                                {{-- Threshold Type & Value --}}
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Condition</label>
                                        <select wire:model="threshold_type"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                            <option value="above">Above</option>
                                            <option value="below">Below</option>
                                        </select>
                                        @error('threshold_type') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Threshold Value</label>
                                        <input type="number"
                                               wire:model="threshold_value"
                                               step="0.01"
                                               min="0"
                                               max="100"
                                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                        @error('threshold_value') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Cooldown Minutes --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cooldown Period (minutes)</label>
                                    <input type="number"
                                           wire:model="cooldown_minutes"
                                           min="1"
                                           max="1440"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Minimum time between alerts</p>
                                    @error('cooldown_minutes') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                                </div>

                                {{-- Notification Channels --}}
                                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Notification Channels</h4>

                                    {{-- Email --}}
                                    <div class="mb-3">
                                        <label class="flex items-center">
                                            <input type="checkbox"
                                                   wire:model.live="enable_email"
                                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Email</span>
                                        </label>
                                        @if($enable_email)
                                            <input type="email"
                                                   wire:model="email_address"
                                                   placeholder="email@example.com"
                                                   class="mt-2 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                            @error('email_address') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                                        @endif
                                    </div>

                                    {{-- Slack --}}
                                    <div class="mb-3">
                                        <label class="flex items-center">
                                            <input type="checkbox"
                                                   wire:model.live="enable_slack"
                                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Slack</span>
                                        </label>
                                        @if($enable_slack)
                                            <input type="url"
                                                   wire:model="slack_webhook"
                                                   placeholder="https://hooks.slack.com/services/..."
                                                   class="mt-2 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                            @error('slack_webhook') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                                        @endif
                                    </div>

                                    {{-- Discord --}}
                                    <div>
                                        <label class="flex items-center">
                                            <input type="checkbox"
                                                   wire:model.live="enable_discord"
                                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Discord</span>
                                        </label>
                                        @if($enable_discord)
                                            <input type="url"
                                                   wire:model="discord_webhook"
                                                   placeholder="https://discord.com/api/webhooks/..."
                                                   class="mt-2 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                            @error('discord_webhook') <span class="text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
                                        @endif
                                    </div>
                                </div>

                                {{-- Is Active --}}
                                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                    <label class="flex items-center">
                                        <input type="checkbox"
                                               wire:model="is_active"
                                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Alert is active</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-900 px-6 py-3 flex justify-end space-x-3">
                            <button type="button"
                                    wire:click="{{ $showCreateModal ? 'closeCreateModal' : 'closeEditModal' }}"
                                    class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 font-medium transition-colors duration-150">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors duration-150">
                                {{ $showCreateModal ? 'Create Alert' : 'Update Alert' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
