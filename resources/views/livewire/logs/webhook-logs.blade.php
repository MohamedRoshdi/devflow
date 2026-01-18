<div>
    {{-- Hero Section --}}
    <div class="mb-8 rounded-2xl bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 p-8 text-white shadow-xl">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold">Webhook Delivery Logs</h1>
                <p class="mt-2 text-indigo-100">Track GitHub/GitLab webhook deliveries and auto-deployment triggers</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-xl bg-white/20 px-4 py-2 text-sm font-medium backdrop-blur-sm">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                    </svg>
                    GitHub
                </span>
                <span class="inline-flex items-center gap-2 rounded-xl bg-white/20 px-4 py-2 text-sm font-medium backdrop-blur-sm">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M22.65 14.39L12 22.13 1.35 14.39a.84.84 0 01-.3-.94l1.22-3.78 2.44-7.51A.42.42 0 014.82 2a.43.43 0 01.58 0 .42.42 0 01.11.18l2.44 7.49h8.1l2.44-7.51A.42.42 0 0118.6 2a.43.43 0 01.58 0 .42.42 0 01.11.18l2.44 7.51L23 13.45a.84.84 0 01-.35.94z"/>
                    </svg>
                    GitLab
                </span>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="mb-8 grid gap-4 sm:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Webhooks</p>
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
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Ignored</p>
            <p class="mt-2 text-3xl font-bold text-gray-600 dark:text-gray-400">{{ number_format($this->stats['ignored']) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-lg dark:border-gray-700 dark:bg-gray-800">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
            <div class="lg:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search webhooks..."
                       class="w-full rounded-xl border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                <select wire:model.live="statusFilter"
                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">All Statuses</option>
                    <option value="success">Success</option>
                    <option value="failed">Failed</option>
                    <option value="ignored">Ignored</option>
                    <option value="pending">Pending</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Provider</label>
                <select wire:model.live="providerFilter"
                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">All Providers</option>
                    <option value="github">GitHub</option>
                    <option value="gitlab">GitLab</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Project</label>
                <select wire:model.live="projectFilter"
                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">All Projects</option>
                    @foreach($this->projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }}</option>
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

    {{-- Deliveries Table --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Project</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Provider</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Event</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Deployment</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Time</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                @forelse($deliveries as $delivery)
                    <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $delivery->project?->name ?? 'N/A' }}</span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium
                                @if($delivery->provider === 'github') bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900
                                @else bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400 @endif">
                                {{ ucfirst($delivery->provider) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="text-sm text-gray-600 dark:text-gray-300">{{ $delivery->event_type }}</span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium
                                @if($delivery->status === 'success') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                @elseif($delivery->status === 'failed') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                @elseif($delivery->status === 'ignored') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 @endif">
                                <span class="h-1.5 w-1.5 rounded-full
                                    @if($delivery->status === 'success') bg-green-500
                                    @elseif($delivery->status === 'failed') bg-red-500
                                    @elseif($delivery->status === 'ignored') bg-gray-500
                                    @else bg-yellow-500 @endif"></span>
                                {{ ucfirst($delivery->status) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @if($delivery->deployment_id)
                                <a href="{{ route('deployments.show', $delivery->deployment_id) }}"
                                   class="text-sm text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300">
                                    #{{ $delivery->deployment_id }}
                                </a>
                            @else
                                <span class="text-sm text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $delivery->created_at?->diffForHumans() ?? 'N/A' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right">
                            <button wire:click="viewDetails({{ $delivery->id }})"
                                    class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300">
                                View Details
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            No webhook deliveries found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($deliveries->hasPages())
            <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                {{ $deliveries->links() }}
            </div>
        @endif
    </div>

    {{-- Details Modal --}}
    @if($showDetails)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeDetails"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
                <div class="inline-block transform overflow-hidden rounded-2xl bg-white text-left align-bottom shadow-xl transition-all dark:bg-gray-800 sm:my-8 sm:w-full sm:max-w-3xl sm:align-middle">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Webhook Delivery Details</h3>
                    </div>
                    <div class="max-h-[60vh] overflow-y-auto p-6">
                        <dl class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Project</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedDelivery['project'] ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Provider</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ ucfirst($selectedDelivery['provider'] ?? '') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Event Type</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedDelivery['event_type'] ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ ucfirst($selectedDelivery['status'] ?? '') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Deployment ID</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedDelivery['deployment_id'] ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Time</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $selectedDelivery['created_at'] ?? 'N/A' }}</dd>
                                </div>
                            </div>
                            @if(!empty($selectedDelivery['signature']))
                                <div>
                                    <dt class="mb-1 text-sm font-medium text-gray-500 dark:text-gray-400">Signature</dt>
                                    <dd class="rounded-lg bg-gray-100 p-3 font-mono text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-300">{{ $selectedDelivery['signature'] }}</dd>
                                </div>
                            @endif
                            @if(!empty($selectedDelivery['response']))
                                <div>
                                    <dt class="mb-1 text-sm font-medium text-gray-500 dark:text-gray-400">Response</dt>
                                    <dd class="rounded-lg bg-gray-100 p-3 text-sm text-gray-700 dark:bg-gray-700 dark:text-gray-300">{{ $selectedDelivery['response'] }}</dd>
                                </div>
                            @endif
                            @if(!empty($selectedDelivery['payload']))
                                <div>
                                    <dt class="mb-1 text-sm font-medium text-gray-500 dark:text-gray-400">Payload</dt>
                                    <dd class="overflow-x-auto rounded-lg bg-gray-100 p-3 dark:bg-gray-700">
                                        <pre class="text-xs text-gray-800 dark:text-gray-200">{{ json_encode($selectedDelivery['payload'], JSON_PRETTY_PRINT) }}</pre>
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
