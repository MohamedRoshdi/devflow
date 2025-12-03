<div>
    {{-- Hero Section --}}
    <div class="mb-8 rounded-2xl bg-gradient-to-br from-rose-500 via-pink-500 to-fuchsia-600 p-8 text-white shadow-xl">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold">Notification Logs</h1>
                <p class="mt-2 text-rose-100">Track all notification delivery attempts across channels</p>
            </div>
            <a href="{{ route('notifications.index') }}"
               class="flex items-center gap-2 rounded-xl bg-white/20 px-4 py-2 font-medium backdrop-blur-sm transition hover:bg-white/30">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Manage Channels
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="mb-8 grid gap-4 sm:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Notifications</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($this->stats['total']) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Successful</p>
            <p class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($this->stats['success']) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Failed</p>
            <p class="mt-2 text-3xl font-bold text-red-600 dark:text-red-400">{{ number_format($this->stats['failed']) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending</p>
            <p class="mt-2 text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($this->stats['pending']) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <div class="lg:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search logs..."
                       class="w-full rounded-xl border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                <select wire:model.live="statusFilter"
                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">All Statuses</option>
                    <option value="success">Success</option>
                    <option value="failed">Failed</option>
                    <option value="pending">Pending</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Channel</label>
                <select wire:model.live="channelFilter"
                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">All Channels</option>
                    @foreach($this->channels as $channel)
                        <option value="{{ $channel->id }}">{{ $channel->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Event Type</label>
                <select wire:model.live="eventTypeFilter"
                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-pink-500 focus:ring-pink-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">All Events</option>
                    @foreach($this->eventTypes as $type)
                        <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button wire:click="clearFilters" class="w-full rounded-xl bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                    Clear Filters
                </button>
            </div>
        </div>
    </div>

    {{-- Logs Table --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Channel</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Event Type</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Error</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Time</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                @forelse($logs as $log)
                    <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="flex items-center">
                                <span class="inline-flex items-center rounded-lg bg-gray-100 px-2 py-1 text-sm font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                    {{ $log->channel?->name ?? 'Unknown' }}
                                </span>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="text-sm text-gray-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $log->event_type)) }}</span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium
                                @if($log->status === 'success') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                @elseif($log->status === 'failed') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 @endif">
                                <span class="h-1.5 w-1.5 rounded-full @if($log->status === 'success') bg-green-500 @elseif($log->status === 'failed') bg-red-500 @else bg-yellow-500 @endif"></span>
                                {{ ucfirst($log->status) }}
                            </span>
                        </td>
                        <td class="max-w-xs truncate px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $log->error_message ?? '-' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $log->created_at->diffForHumans() }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right">
                            <button wire:click="viewDetails({{ $log->id }})"
                                    class="text-pink-600 hover:text-pink-900 dark:text-pink-400 dark:hover:text-pink-300">
                                View Details
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            No notification logs found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($logs->hasPages())
            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    {{-- Details Modal --}}
    @if($showDetails)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeDetails"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
                <div class="inline-block transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all dark:bg-gray-800 sm:my-8 sm:w-full sm:max-w-2xl sm:align-middle">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Notification Details</h3>
                    </div>
                    <div class="max-h-[60vh] overflow-y-auto p-6">
                        <dl class="space-y-4">
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Channel</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedLog['channel'] ?? 'N/A' }} ({{ $selectedLog['channel_type'] ?? 'N/A' }})</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Event Type</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $selectedLog['event_type'] ?? '')) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">{{ ucfirst($selectedLog['status'] ?? '') }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Time</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">{{ $selectedLog['created_at'] ?? 'N/A' }}</dd>
                            </div>
                            @if(!empty($selectedLog['error_message']))
                                <div>
                                    <dt class="mb-1 text-sm font-medium text-gray-500 dark:text-gray-400">Error Message</dt>
                                    <dd class="rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400">{{ $selectedLog['error_message'] }}</dd>
                                </div>
                            @endif
                            @if(!empty($selectedLog['payload']))
                                <div>
                                    <dt class="mb-1 text-sm font-medium text-gray-500 dark:text-gray-400">Payload</dt>
                                    <dd class="overflow-x-auto rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                                        <pre class="text-xs text-gray-800 dark:text-gray-200">{{ json_encode($selectedLog['payload'], JSON_PRETTY_PRINT) }}</pre>
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                    <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                        <button wire:click="closeDetails" class="w-full rounded-xl bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
