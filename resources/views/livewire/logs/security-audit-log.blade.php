<div>
    {{-- Hero Section --}}
    <div class="mb-8 rounded-2xl bg-gradient-to-br from-red-600 via-orange-500 to-amber-500 p-8 text-white shadow-xl">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold">Security Audit Log</h1>
                <p class="mt-2 text-red-100">Track all security events, firewall changes, and IP bans across servers</p>
            </div>
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/20 backdrop-blur-sm">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="mb-8 grid gap-4 sm:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Events</p>
            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($this->stats['total']) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Events Today</p>
            <p class="mt-2 text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($this->stats['today']) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Firewall Events</p>
            <p class="mt-2 text-3xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($this->stats['firewall_events']) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">IP Bans</p>
            <p class="mt-2 text-3xl font-bold text-red-600 dark:text-red-400">{{ number_format($this->stats['ip_bans']) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by IP, details..."
                       class="w-full rounded-xl border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Server</label>
                <select wire:model.live="serverFilter"
                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">All Servers</option>
                    @foreach($this->servers as $server)
                        <option value="{{ $server->id }}">{{ $server->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Event Type</label>
                <select wire:model.live="eventTypeFilter"
                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">All Types</option>
                    @foreach($this->eventTypes as $type => $label)
                        <option value="{{ $type }}">{{ $label }}</option>
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

    {{-- Events Table --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Server</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Event Type</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Source IP</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Details</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">User</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Time</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                @forelse($events as $event)
                    <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $event->server?->name ?? 'N/A' }}</span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium
                                @if($event->event_type_color === 'green') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                @elseif($event->event_type_color === 'red') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                @elseif($event->event_type_color === 'orange') bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400
                                @elseif($event->event_type_color === 'yellow') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                                @elseif($event->event_type_color === 'blue') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                                @elseif($event->event_type_color === 'purple') bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400
                                @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                                {{ $event->getEventTypeLabel() }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="font-mono text-sm text-gray-600 dark:text-gray-300">{{ $event->source_ip ?? '-' }}</span>
                        </td>
                        <td class="max-w-xs truncate px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $event->details ?? '-' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $event->user?->name ?? 'System' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $event->created_at->diffForHumans() }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right">
                            <button wire:click="viewDetails({{ $event->id }})"
                                    class="text-orange-600 hover:text-orange-900 dark:text-orange-400 dark:hover:text-orange-300">
                                View Details
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            No security events found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($events->hasPages())
            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                {{ $events->links() }}
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
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Security Event Details</h3>
                    </div>
                    <div class="max-h-[60vh] overflow-y-auto p-6">
                        <dl class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Server</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedEvent['server'] ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Event Type</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedEvent['event_type_label'] ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Source IP</dt>
                                    <dd class="mt-1 font-mono text-sm text-gray-900 dark:text-white">{{ $selectedEvent['source_ip'] ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">User</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedEvent['user'] ?? 'System' }}</dd>
                                </div>
                                <div class="col-span-2">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Time</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedEvent['created_at'] ?? 'N/A' }}</dd>
                                </div>
                            </div>
                            @if(!empty($selectedEvent['details']))
                                <div>
                                    <dt class="mb-1 text-sm font-medium text-gray-500 dark:text-gray-400">Details</dt>
                                    <dd class="rounded-lg bg-gray-100 p-3 text-sm text-gray-700 dark:bg-gray-700 dark:text-gray-300">{{ $selectedEvent['details'] }}</dd>
                                </div>
                            @endif
                            @if(!empty($selectedEvent['metadata']))
                                <div>
                                    <dt class="mb-1 text-sm font-medium text-gray-500 dark:text-gray-400">Metadata</dt>
                                    <dd class="overflow-x-auto rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                                        <pre class="text-xs text-gray-800 dark:text-gray-200">{{ json_encode($selectedEvent['metadata'], JSON_PRETTY_PRINT) }}</pre>
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
