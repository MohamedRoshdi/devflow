<div class="space-y-6">
    {{-- Flash Message --}}
    @if ($flashMessage)
        <div @class([
            'p-4 rounded-lg flex items-center justify-between',
            'bg-green-500/10 border border-green-500/20 text-green-400' => $flashType === 'success',
            'bg-red-500/10 border border-red-500/20 text-red-400' => $flashType === 'error',
            'bg-yellow-500/10 border border-yellow-500/20 text-yellow-400' => $flashType === 'warning',
            'bg-blue-500/10 border border-blue-500/20 text-blue-400' => $flashType === 'info',
        ])>
            <span>{{ $flashMessage }}</span>
            <button wire:click="$set('flashMessage', null)" class="text-current hover:opacity-70">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    {{-- Stats Overview --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
            <div class="text-2xl font-bold text-white">{{ $this->stats['total'] }}</div>
            <div class="text-sm text-gray-400">Total Incidents</div>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
            <div class="text-2xl font-bold text-yellow-400">{{ $this->stats['active'] }}</div>
            <div class="text-sm text-gray-400">Active</div>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 border border-red-500/30">
            <div class="text-2xl font-bold text-red-400">{{ $this->stats['critical'] }}</div>
            <div class="text-sm text-gray-400">Critical</div>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 border border-orange-500/30">
            <div class="text-2xl font-bold text-orange-400">{{ $this->stats['high'] }}</div>
            <div class="text-sm text-gray-400">High</div>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 border border-green-500/30">
            <div class="text-2xl font-bold text-green-400">{{ $this->stats['resolved_today'] }}</div>
            <div class="text-sm text-gray-400">Resolved Today</div>
        </div>
        <div class="bg-gray-800 rounded-lg p-4 border border-blue-500/30">
            <div class="text-2xl font-bold text-blue-400">{{ $this->stats['auto_remediated'] }}</div>
            <div class="text-sm text-gray-400">Auto-Remediated</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
            {{-- Search --}}
            <div class="lg:col-span-2">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search incidents..."
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                >
            </div>

            {{-- Severity Filter --}}
            <div>
                <select
                    wire:model.live="severityFilter"
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2 text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                >
                    <option value="">All Severities</option>
                    <option value="critical">Critical</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
            </div>

            {{-- Status Filter --}}
            <div>
                <select
                    wire:model.live="statusFilter"
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2 text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                >
                    <option value="">All Statuses</option>
                    <option value="detected">Detected</option>
                    <option value="investigating">Investigating</option>
                    <option value="mitigating">Mitigating</option>
                    <option value="resolved">Resolved</option>
                    <option value="false_positive">False Positive</option>
                </select>
            </div>

            {{-- Server Filter --}}
            <div>
                <select
                    wire:model.live="serverFilter"
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2 text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                >
                    <option value="">All Servers</option>
                    @foreach ($this->servers as $server)
                        <option value="{{ $server->id }}">{{ $server->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Type Filter --}}
            <div>
                <select
                    wire:model.live="typeFilter"
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-2 text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                >
                    <option value="">All Types</option>
                    @foreach ($this->getIncidentTypes() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Filter Actions --}}
        <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-700">
            <button
                wire:click="clearFilters"
                class="text-sm text-gray-400 hover:text-white transition-colors"
            >
                Clear All Filters
            </button>
            <button
                wire:click="bulkResolve"
                wire:confirm="This will resolve all non-critical active incidents. Continue?"
                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors"
            >
                Bulk Resolve Non-Critical
            </button>
        </div>
    </div>

    {{-- Incidents Table --}}
    <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                            <button wire:click="sortBy('severity')" class="flex items-center gap-1 hover:text-white">
                                Severity
                                @if ($sortField === 'severity')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}" />
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                            <button wire:click="sortBy('title')" class="flex items-center gap-1 hover:text-white">
                                Incident
                                @if ($sortField === 'title')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}" />
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Server</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                            <button wire:click="sortBy('status')" class="flex items-center gap-1 hover:text-white">
                                Status
                                @if ($sortField === 'status')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}" />
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                            <button wire:click="sortBy('detected_at')" class="flex items-center gap-1 hover:text-white">
                                Detected
                                @if ($sortField === 'detected_at')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}" />
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse ($this->incidents as $incident)
                        <tr class="hover:bg-gray-700/50 transition-colors">
                            {{-- Severity --}}
                            <td class="px-4 py-3">
                                <span @class([
                                    'px-2 py-1 text-xs font-medium rounded-full',
                                    'bg-red-500/20 text-red-400 border border-red-500/30' => $incident->severity === 'critical',
                                    'bg-orange-500/20 text-orange-400 border border-orange-500/30' => $incident->severity === 'high',
                                    'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30' => $incident->severity === 'medium',
                                    'bg-blue-500/20 text-blue-400 border border-blue-500/30' => $incident->severity === 'low',
                                ])>
                                    {{ ucfirst($incident->severity) }}
                                </span>
                            </td>

                            {{-- Incident Title --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    @if ($incident->auto_remediated)
                                        <span class="text-green-400" title="Auto-remediated">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                        </span>
                                    @endif
                                    <div>
                                        <div class="text-white font-medium">{{ $incident->title }}</div>
                                        @if ($incident->description)
                                            <div class="text-sm text-gray-400 truncate max-w-xs">{{ Str::limit($incident->description, 50) }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Server --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-green-400"></div>
                                    <span class="text-gray-300">{{ $incident->server->name ?? 'Unknown' }}</span>
                                </div>
                            </td>

                            {{-- Type --}}
                            <td class="px-4 py-3">
                                <span class="text-gray-300 text-sm">
                                    {{ $this->getIncidentTypes()[$incident->incident_type] ?? $incident->incident_type }}
                                </span>
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-3">
                                <span @class([
                                    'px-2 py-1 text-xs font-medium rounded-full',
                                    'bg-red-500/20 text-red-400' => $incident->status === 'detected',
                                    'bg-yellow-500/20 text-yellow-400' => $incident->status === 'investigating',
                                    'bg-blue-500/20 text-blue-400' => $incident->status === 'mitigating',
                                    'bg-green-500/20 text-green-400' => $incident->status === 'resolved',
                                    'bg-gray-500/20 text-gray-400' => $incident->status === 'false_positive',
                                ])>
                                    {{ ucfirst(str_replace('_', ' ', $incident->status)) }}
                                </span>
                            </td>

                            {{-- Detected At --}}
                            <td class="px-4 py-3 text-gray-400 text-sm">
                                <div>{{ $incident->detected_at->format('M d, Y') }}</div>
                                <div class="text-xs">{{ $incident->detected_at->format('H:i:s') }}</div>
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        wire:click="viewIncident({{ $incident->id }})"
                                        class="p-1.5 text-gray-400 hover:text-white transition-colors"
                                        title="View Details"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>

                                    @if (in_array($incident->status, ['detected', 'investigating']))
                                        <button
                                            wire:click="autoRemediate({{ $incident->id }})"
                                            wire:confirm="Run auto-remediation for this incident?"
                                            class="p-1.5 text-blue-400 hover:text-blue-300 transition-colors"
                                            title="Auto-Remediate"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        </button>
                                    @endif

                                    @if ($incident->status !== 'resolved' && $incident->status !== 'false_positive')
                                        <button
                                            wire:click="resolveIncident({{ $incident->id }})"
                                            class="p-1.5 text-green-400 hover:text-green-300 transition-colors"
                                            title="Mark Resolved"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    @endif

                                    <button
                                        wire:click="generateReport({{ $incident->id }})"
                                        class="p-1.5 text-purple-400 hover:text-purple-300 transition-colors"
                                        title="Generate Report"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="w-12 h-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <div class="text-gray-400">No security incidents found</div>
                                    <div class="text-sm text-gray-500">All systems appear secure</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($this->incidents->hasPages())
            <div class="px-4 py-3 border-t border-gray-700">
                {{ $this->incidents->links() }}
            </div>
        @endif
    </div>

    {{-- Incident Detail Modal --}}
    @if ($showIncidentModal && $selectedIncidentId)
        @php
            $selectedIncident = \App\Models\SecurityIncident::with(['server', 'user'])->find($selectedIncidentId);
        @endphp
        @if ($selectedIncident)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/70" wire:click="closeIncidentModal"></div>

                    <div class="relative bg-gray-800 rounded-xl shadow-xl max-w-3xl w-full max-h-[90vh] overflow-hidden">
                        {{-- Header --}}
                        <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-white">Incident #{{ $selectedIncident->id }}</h3>
                                <p class="text-sm text-gray-400">{{ $selectedIncident->detected_at->format('M d, Y H:i:s') }}</p>
                            </div>
                            <button wire:click="closeIncidentModal" class="text-gray-400 hover:text-white">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        {{-- Content --}}
                        <div class="p-6 overflow-y-auto max-h-[60vh] space-y-6">
                            {{-- Title & Status --}}
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="text-xl font-semibold text-white">{{ $selectedIncident->title }}</h4>
                                    @if ($selectedIncident->description)
                                        <p class="text-gray-400 mt-1">{{ $selectedIncident->description }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    <span @class([
                                        'px-3 py-1 text-sm font-medium rounded-full',
                                        'bg-red-500/20 text-red-400' => $selectedIncident->severity === 'critical',
                                        'bg-orange-500/20 text-orange-400' => $selectedIncident->severity === 'high',
                                        'bg-yellow-500/20 text-yellow-400' => $selectedIncident->severity === 'medium',
                                        'bg-blue-500/20 text-blue-400' => $selectedIncident->severity === 'low',
                                    ])>
                                        {{ ucfirst($selectedIncident->severity) }}
                                    </span>
                                    <span @class([
                                        'px-3 py-1 text-sm font-medium rounded-full',
                                        'bg-red-500/20 text-red-400' => $selectedIncident->status === 'detected',
                                        'bg-yellow-500/20 text-yellow-400' => $selectedIncident->status === 'investigating',
                                        'bg-blue-500/20 text-blue-400' => $selectedIncident->status === 'mitigating',
                                        'bg-green-500/20 text-green-400' => $selectedIncident->status === 'resolved',
                                        'bg-gray-500/20 text-gray-400' => $selectedIncident->status === 'false_positive',
                                    ])>
                                        {{ ucfirst(str_replace('_', ' ', $selectedIncident->status)) }}
                                    </span>
                                </div>
                            </div>

                            {{-- Details Grid --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-gray-900 rounded-lg p-4">
                                    <div class="text-sm text-gray-400">Server</div>
                                    <div class="text-white font-medium">{{ $selectedIncident->server->name ?? 'Unknown' }}</div>
                                    <div class="text-sm text-gray-500">{{ $selectedIncident->server->ip_address ?? '' }}</div>
                                </div>
                                <div class="bg-gray-900 rounded-lg p-4">
                                    <div class="text-sm text-gray-400">Type</div>
                                    <div class="text-white font-medium">{{ $this->getIncidentTypes()[$selectedIncident->incident_type] ?? $selectedIncident->incident_type }}</div>
                                </div>
                                <div class="bg-gray-900 rounded-lg p-4">
                                    <div class="text-sm text-gray-400">Assigned To</div>
                                    <div class="text-white font-medium">{{ $selectedIncident->user->name ?? 'Unassigned' }}</div>
                                </div>
                                <div class="bg-gray-900 rounded-lg p-4">
                                    <div class="text-sm text-gray-400">Auto-Remediated</div>
                                    <div class="text-white font-medium">{{ $selectedIncident->auto_remediated ? 'Yes' : 'No' }}</div>
                                </div>
                            </div>

                            {{-- Findings --}}
                            @if ($selectedIncident->findings)
                                <div>
                                    <h5 class="text-sm font-medium text-gray-400 mb-2">Findings</h5>
                                    <div class="bg-gray-900 rounded-lg p-4">
                                        <pre class="text-sm text-gray-300 whitespace-pre-wrap">{{ json_encode($selectedIncident->findings, JSON_PRETTY_PRINT) }}</pre>
                                    </div>
                                </div>
                            @endif

                            {{-- Affected Items --}}
                            @if ($selectedIncident->affected_items)
                                <div>
                                    <h5 class="text-sm font-medium text-gray-400 mb-2">Affected Items</h5>
                                    <div class="bg-gray-900 rounded-lg p-4">
                                        <pre class="text-sm text-gray-300 whitespace-pre-wrap">{{ json_encode($selectedIncident->affected_items, JSON_PRETTY_PRINT) }}</pre>
                                    </div>
                                </div>
                            @endif

                            {{-- Remediation Actions --}}
                            @if ($selectedIncident->remediation_actions)
                                <div>
                                    <h5 class="text-sm font-medium text-gray-400 mb-2">Remediation Actions</h5>
                                    <div class="space-y-2">
                                        @foreach ($selectedIncident->remediation_actions as $action)
                                            <div class="bg-gray-900 rounded-lg p-3 flex items-center justify-between">
                                                <div>
                                                    <span class="text-white">{{ $action['action'] ?? 'Unknown' }}</span>
                                                    <span class="text-sm text-gray-400 ml-2">{{ $action['message'] ?? '' }}</span>
                                                </div>
                                                @if (isset($action['success']))
                                                    <span @class([
                                                        'px-2 py-1 text-xs rounded',
                                                        'bg-green-500/20 text-green-400' => $action['success'],
                                                        'bg-red-500/20 text-red-400' => !$action['success'],
                                                    ])>
                                                        {{ $action['success'] ? 'Success' : 'Failed' }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Timeline --}}
                            <div>
                                <h5 class="text-sm font-medium text-gray-400 mb-2">Timeline</h5>
                                <div class="space-y-2">
                                    <div class="flex items-center gap-3 text-sm">
                                        <div class="w-2 h-2 rounded-full bg-red-400"></div>
                                        <span class="text-gray-400">Detected:</span>
                                        <span class="text-white">{{ $selectedIncident->detected_at->format('M d, Y H:i:s') }}</span>
                                    </div>
                                    @if ($selectedIncident->resolved_at)
                                        <div class="flex items-center gap-3 text-sm">
                                            <div class="w-2 h-2 rounded-full bg-green-400"></div>
                                            <span class="text-gray-400">Resolved:</span>
                                            <span class="text-white">{{ $selectedIncident->resolved_at->format('M d, Y H:i:s') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Footer Actions --}}
                        <div class="px-6 py-4 border-t border-gray-700 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                @if ($selectedIncident->status === 'detected')
                                    <button
                                        wire:click="startInvestigation({{ $selectedIncident->id }})"
                                        class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-lg transition-colors"
                                    >
                                        Start Investigation
                                    </button>
                                @endif
                                @if (in_array($selectedIncident->status, ['detected', 'investigating']))
                                    <button
                                        wire:click="autoRemediate({{ $selectedIncident->id }})"
                                        wire:confirm="Run auto-remediation?"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
                                    >
                                        Auto-Remediate
                                    </button>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($selectedIncident->status !== 'false_positive')
                                    <button
                                        wire:click="markFalsePositive({{ $selectedIncident->id }})"
                                        class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white text-sm font-medium rounded-lg transition-colors"
                                    >
                                        False Positive
                                    </button>
                                @endif
                                @if ($selectedIncident->status !== 'resolved')
                                    <button
                                        wire:click="resolveIncident({{ $selectedIncident->id }})"
                                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors"
                                    >
                                        Mark Resolved
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- Report Modal --}}
    @if ($showReportModal && session('incident_report'))
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/70" wire:click="closeReportModal"></div>

                <div class="relative bg-gray-800 rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
                    {{-- Header --}}
                    <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-white">Incident Report</h3>
                        <div class="flex items-center gap-2">
                            <button
                                onclick="navigator.clipboard.writeText(document.getElementById('report-content').innerText)"
                                class="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 text-white text-sm rounded-lg transition-colors"
                            >
                                Copy to Clipboard
                            </button>
                            <button wire:click="closeReportModal" class="text-gray-400 hover:text-white">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Report Content --}}
                    <div class="p-6 overflow-y-auto max-h-[70vh]">
                        <div id="report-content" class="bg-gray-900 rounded-lg p-6">
                            <pre class="text-sm text-gray-300 whitespace-pre-wrap font-mono">{{ session('incident_report') }}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
