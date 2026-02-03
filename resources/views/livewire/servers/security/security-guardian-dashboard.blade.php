<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-indigo-800 via-purple-900 to-indigo-800 shadow-2xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex items-center gap-4">
                    <a href="{{ route('servers.security', $server) }}" class="p-2 bg-white/10 rounded-lg hover:bg-white/20 transition-colors">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-white">Security Guardian</h1>
                        <p class="text-white/80">{{ $server->name }} - Predict, Detect & Auto-Fix</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button wire:click="runGuardianScan" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-white text-indigo-800 font-medium rounded-lg hover:bg-gray-100 transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" wire:loading.class="animate-spin" wire:target="runGuardianScan" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span wire:loading.remove wire:target="runGuardianScan">Run Guardian Scan</span>
                        <span wire:loading wire:target="runGuardianScan">Scanning...</span>
                    </button>
                    <button wire:click="toggleGuardian"
                        class="px-4 py-2 {{ $server->guardian_enabled ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-600 hover:bg-gray-700' }} text-white font-medium rounded-lg transition-colors">
                        Guardian: {{ $server->guardian_enabled ? 'ON' : 'OFF' }}
                    </button>
                    <button wire:click="toggleAutoRemediation"
                        class="px-4 py-2 {{ $server->auto_remediation_enabled ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-600 hover:bg-gray-700' }} text-white font-medium rounded-lg transition-colors">
                        Auto-Fix: {{ $server->auto_remediation_enabled ? 'ON' : 'OFF' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Message -->
    @if($flashMessage)
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="rounded-lg p-4
                {{ $flashType === 'success' ? 'bg-green-900/50 text-green-200 border border-green-700' : '' }}
                {{ $flashType === 'warning' ? 'bg-yellow-900/50 text-yellow-200 border border-yellow-700' : '' }}
                {{ $flashType === 'error' ? 'bg-red-900/50 text-red-200 border border-red-700' : '' }}
                {{ $flashType === 'info' ? 'bg-blue-900/50 text-blue-200 border border-blue-700' : '' }}">
                {{ $flashMessage }}
            </div>
        </div>
    @endif

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Traffic Light Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            @php $overview = $this->overview; @endphp

            <!-- Overall Status -->
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-6 text-center">
                <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-3
                    {{ $overview['overall_status'] === 'secure' ? 'bg-green-500/20 border-2 border-green-500' : '' }}
                    {{ $overview['overall_status'] === 'warning' ? 'bg-yellow-500/20 border-2 border-yellow-500' : '' }}
                    {{ $overview['overall_status'] === 'critical' ? 'bg-red-500/20 border-2 border-red-500 animate-pulse' : '' }}
                    {{ $overview['overall_status'] === 'unknown' ? 'bg-gray-500/20 border-2 border-gray-500' : '' }}">
                    <svg class="w-8 h-8
                        {{ $overview['overall_status'] === 'secure' ? 'text-green-400' : '' }}
                        {{ $overview['overall_status'] === 'warning' ? 'text-yellow-400' : '' }}
                        {{ $overview['overall_status'] === 'critical' ? 'text-red-400' : '' }}
                        {{ $overview['overall_status'] === 'unknown' ? 'text-gray-400' : '' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <p class="text-sm text-gray-400">Overall Status</p>
                <p class="text-lg font-bold text-white capitalize">{{ $overview['overall_status'] }}</p>
            </div>

            <!-- Active Incidents -->
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-6 text-center">
                <p class="text-3xl font-bold {{ ($overview['active_incidents'] ?? 0) > 0 ? 'text-red-400' : 'text-green-400' }}">
                    {{ $overview['active_incidents'] ?? 0 }}
                </p>
                <p class="text-sm text-gray-400 mt-1">Active Incidents</p>
            </div>

            <!-- Active Predictions -->
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-6 text-center">
                <p class="text-3xl font-bold {{ ($overview['active_predictions'] ?? 0) > 0 ? 'text-yellow-400' : 'text-green-400' }}">
                    {{ $overview['active_predictions'] ?? 0 }}
                </p>
                <p class="text-sm text-gray-400 mt-1">Active Predictions</p>
            </div>

            <!-- Hardening Level -->
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-6 text-center">
                <p class="text-lg font-bold text-white capitalize">{{ $server->hardening_level ?? 'None' }}</p>
                <p class="text-sm text-gray-400 mt-1">Hardening Level</p>
                <a href="{{ route('servers.security.hardening', $server) }}" class="text-xs text-indigo-400 hover:text-indigo-300 mt-2 inline-block">
                    Configure &rarr;
                </a>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <a href="{{ route('servers.security.predictions', $server) }}" class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-4 hover:border-indigo-500/50 transition-colors flex items-center gap-4">
                <div class="p-3 bg-yellow-500/20 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-white">Predictive Analytics</p>
                    <p class="text-sm text-gray-400">Trends, predictions & baselines</p>
                </div>
            </a>
            <a href="{{ route('servers.security.hardening', $server) }}" class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-4 hover:border-indigo-500/50 transition-colors flex items-center gap-4">
                <div class="p-3 bg-emerald-500/20 rounded-lg">
                    <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-white">Server Hardening</p>
                    <p class="text-sm text-gray-400">SSH, Firewall, Fail2ban & more</p>
                </div>
            </a>
            <a href="{{ route('servers.security.threats', $server) }}" class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-4 hover:border-indigo-500/50 transition-colors flex items-center gap-4">
                <div class="p-3 bg-red-500/20 rounded-lg">
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-white">Threat Scanner</p>
                    <p class="text-sm text-gray-400">Detailed threat detection</p>
                </div>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Active Incidents -->
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50">
                <div class="p-4 border-b border-gray-700/50">
                    <h3 class="font-semibold text-white">Active Incidents</h3>
                </div>
                <div class="p-4 space-y-3">
                    @forelse($this->activeIncidents as $incident)
                        <div class="flex items-start gap-3 p-3 bg-gray-900/50 rounded-lg">
                            <span class="mt-1 w-2 h-2 rounded-full flex-shrink-0
                                {{ $incident->severity === 'critical' ? 'bg-red-500 animate-pulse' : '' }}
                                {{ $incident->severity === 'high' ? 'bg-orange-500' : '' }}
                                {{ $incident->severity === 'medium' ? 'bg-yellow-500' : '' }}
                                {{ $incident->severity === 'low' ? 'bg-blue-500' : '' }}"></span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-white truncate">{{ $incident->title }}</p>
                                <p class="text-xs text-gray-400">{{ $incident->detected_at->diffForHumans() }} &middot; {{ ucfirst($incident->severity) }}</p>
                            </div>
                            <button wire:click="acknowledgeIncident({{ $incident->id }})" class="text-xs text-indigo-400 hover:text-indigo-300 flex-shrink-0">
                                Investigate
                            </button>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 text-center py-4">No active incidents</p>
                    @endforelse
                </div>
            </div>

            <!-- Active Predictions -->
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50">
                <div class="p-4 border-b border-gray-700/50">
                    <h3 class="font-semibold text-white">Predictive Alerts</h3>
                </div>
                <div class="p-4 space-y-3">
                    @forelse($this->activePredictions as $prediction)
                        <div class="flex items-start gap-3 p-3 bg-gray-900/50 rounded-lg">
                            <span class="mt-1 w-2 h-2 rounded-full flex-shrink-0
                                {{ $prediction->severity === 'critical' ? 'bg-red-500 animate-pulse' : '' }}
                                {{ $prediction->severity === 'high' ? 'bg-orange-500' : '' }}
                                {{ $prediction->severity === 'medium' ? 'bg-yellow-500' : '' }}
                                {{ $prediction->severity === 'low' ? 'bg-blue-500' : '' }}"></span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-white truncate">{{ $prediction->title }}</p>
                                <p class="text-xs text-gray-400">
                                    {{ round($prediction->confidence_score * 100) }}% confidence
                                    @if($prediction->predicted_impact_at)
                                        &middot; Impact {{ $prediction->predicted_impact_at->diffForHumans() }}
                                    @endif
                                </p>
                            </div>
                            <button wire:click="acknowledgePrediction({{ $prediction->id }})" class="text-xs text-indigo-400 hover:text-indigo-300 flex-shrink-0">
                                Ack
                            </button>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 text-center py-4">No active predictions</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Recent Remediations -->
        <div class="mt-8 bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50">
            <div class="p-4 border-b border-gray-700/50">
                <h3 class="font-semibold text-white">Recent Remediation Actions</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-gray-400 border-b border-gray-700/50">
                            <th class="text-left p-3">Action</th>
                            <th class="text-left p-3">Target</th>
                            <th class="text-left p-3">Status</th>
                            <th class="text-left p-3">Type</th>
                            <th class="text-left p-3">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->recentRemediations as $log)
                            <tr class="border-b border-gray-700/30">
                                <td class="p-3 text-white font-medium">{{ str_replace('_', ' ', ucfirst($log->action)) }}</td>
                                <td class="p-3 text-gray-300 font-mono text-xs truncate max-w-[200px]">{{ $log->target }}</td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-xs {{ $log->success ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                                        {{ $log->success ? 'Success' : 'Failed' }}
                                    </span>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-xs {{ $log->auto_triggered ? 'bg-purple-500/20 text-purple-400' : 'bg-blue-500/20 text-blue-400' }}">
                                        {{ $log->auto_triggered ? 'Auto' : 'Manual' }}
                                    </span>
                                </td>
                                <td class="p-3 text-gray-400">{{ $log->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-gray-500">No remediation actions yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
