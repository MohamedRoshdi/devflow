<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-red-800 via-red-900 to-red-800 shadow-2xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex items-center gap-4">
                    <a href="{{ route('servers.security', $server) }}" class="p-2 bg-white/10 rounded-lg hover:bg-white/20 transition-colors">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <div class="flex items-center gap-3">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div>
                                <h1 class="text-2xl font-bold text-white">Threat Scanner</h1>
                                <p class="text-white/80">{{ $server->name }} - Security Threat Detection</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button wire:click="runThreatScan" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-white text-red-800 font-medium rounded-lg hover:bg-gray-100 transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" wire:loading.class="animate-spin" wire:target="runThreatScan" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span wire:loading.remove wire:target="runThreatScan">Scan for Threats</span>
                        <span wire:loading wire:target="runThreatScan">Scanning...</span>
                    </button>
                    <button wire:click="toggleAutoRemediation"
                        class="px-4 py-2 {{ $server->auto_remediation_enabled ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-600 hover:bg-gray-700' }} text-white font-medium rounded-lg transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        Auto-Remediation: {{ $server->auto_remediation_enabled ? 'ON' : 'OFF' }}
                    </button>
                    @if($server->lockdown_mode)
                        <button wire:click="disableLockdown"
                            wire:confirm="Disable lockdown mode and restore normal traffic?"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2 animate-pulse">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                            </svg>
                            Disable Lockdown
                        </button>
                    @else
                        <button wire:click="enableLockdown"
                            wire:confirm="Enable lockdown mode? This will block all traffic except SSH!"
                            class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Enable Lockdown
                        </button>
                    @endif
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
        <!-- Status Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-{{ $server->active_incidents_count > 0 ? 'red' : 'green' }}-500/20 rounded-lg">
                        <svg class="w-6 h-6 text-{{ $server->active_incidents_count > 0 ? 'red' : 'green' }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white">{{ $server->active_incidents_count }}</div>
                        <div class="text-gray-400 text-sm">Active Incidents</div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-purple-500/20 rounded-lg">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-white">{{ $server->last_threat_scan_at?->diffForHumans() ?? 'Never' }}</div>
                        <div class="text-gray-400 text-sm">Last Scan</div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-{{ $server->lockdown_mode ? 'red' : 'gray' }}-500/20 rounded-lg">
                        <svg class="w-6 h-6 text-{{ $server->lockdown_mode ? 'red' : 'gray' }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-white">{{ $server->lockdown_mode ? 'ACTIVE' : 'Inactive' }}</div>
                        <div class="text-gray-400 text-sm">Lockdown Mode</div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-{{ $server->auto_remediation_enabled ? 'green' : 'gray' }}-500/20 rounded-lg">
                        <svg class="w-6 h-6 text-{{ $server->auto_remediation_enabled ? 'green' : 'gray' }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-white">{{ $server->auto_remediation_enabled ? 'Enabled' : 'Disabled' }}</div>
                        <div class="text-gray-400 text-sm">Auto-Remediation</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scan Results (if just scanned) -->
        @if(!empty($threats))
            <div class="bg-red-900/20 backdrop-blur-sm rounded-2xl border border-red-700/50 p-6 mb-8">
                <h3 class="text-lg font-semibold text-red-400 mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Threats Detected ({{ count($threats) }})
                    @if($scanTime)
                        <span class="text-sm text-gray-400 ml-auto">Scan completed in {{ $scanTime }}s</span>
                    @endif
                </h3>
                <div class="space-y-4">
                    @foreach($threats as $index => $threat)
                        <div class="bg-gray-900/50 rounded-lg p-4 border-l-4
                            {{ $threat['severity'] === 'critical' ? 'border-red-500' : '' }}
                            {{ $threat['severity'] === 'high' ? 'border-orange-500' : '' }}
                            {{ $threat['severity'] === 'medium' ? 'border-yellow-500' : '' }}
                            {{ $threat['severity'] === 'low' ? 'border-blue-500' : '' }}">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded text-xs font-medium uppercase
                                            {{ $threat['severity'] === 'critical' ? 'bg-red-500/20 text-red-400' : '' }}
                                            {{ $threat['severity'] === 'high' ? 'bg-orange-500/20 text-orange-400' : '' }}
                                            {{ $threat['severity'] === 'medium' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                                            {{ $threat['severity'] === 'low' ? 'bg-blue-500/20 text-blue-400' : '' }}">
                                            {{ $threat['severity'] }}
                                        </span>
                                        <h4 class="text-white font-medium">{{ $threat['title'] }}</h4>
                                    </div>
                                    <p class="text-gray-400 text-sm mt-1">{{ $threat['description'] }}</p>
                                    @if(!empty($threat['affected_items']))
                                        <div class="mt-2 text-xs text-gray-500">
                                            <pre class="bg-gray-800/50 p-2 rounded overflow-x-auto">{{ json_encode($threat['affected_items'], JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Active Incidents -->
        <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-white">Active Security Incidents</h3>
                @if($this->activeIncidents->isNotEmpty())
                    <button wire:click="autoRemediateAll"
                        wire:confirm="Auto-remediate all critical and high severity incidents?"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Auto Remediate All
                    </button>
                @endif
            </div>

            @if($this->activeIncidents->isEmpty())
                <div class="text-center py-8">
                    <svg class="w-12 h-12 text-green-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-gray-400">No active security incidents</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($this->activeIncidents as $incident)
                        <div class="bg-gray-900/50 rounded-lg p-4 border-l-4
                            {{ $incident->severity === 'critical' ? 'border-red-500' : '' }}
                            {{ $incident->severity === 'high' ? 'border-orange-500' : '' }}
                            {{ $incident->severity === 'medium' ? 'border-yellow-500' : '' }}
                            {{ $incident->severity === 'low' ? 'border-blue-500' : '' }}">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="px-2 py-0.5 rounded text-xs font-medium uppercase
                                            {{ $incident->severity === 'critical' ? 'bg-red-500/20 text-red-400' : '' }}
                                            {{ $incident->severity === 'high' ? 'bg-orange-500/20 text-orange-400' : '' }}
                                            {{ $incident->severity === 'medium' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                                            {{ $incident->severity === 'low' ? 'bg-blue-500/20 text-blue-400' : '' }}">
                                            {{ $incident->severity }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-gray-600/50 text-gray-300">
                                            {{ $incident->status }}
                                        </span>
                                        <h4 class="text-white font-medium">{{ $incident->title }}</h4>
                                    </div>
                                    <p class="text-gray-400 text-sm">{{ $incident->description }}</p>
                                    <p class="text-gray-500 text-xs mt-1">Detected {{ $incident->detected_at->diffForHumans() }}</p>
                                </div>
                                <div class="flex items-center gap-2 ml-4">
                                    @if($incident->incident_type === 'suspicious_process' && isset($incident->affected_items['pid']))
                                        <button wire:click="remediate('kill_process', {{ $incident->id }})"
                                            class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-sm rounded transition-colors">
                                            Kill Process
                                        </button>
                                    @endif
                                    @if($incident->incident_type === 'malware' && isset($incident->affected_items['path']))
                                        <button wire:click="remediate('remove_directory', {{ $incident->id }})"
                                            class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-sm rounded transition-colors">
                                            Remove Dir
                                        </button>
                                    @endif
                                    @if($incident->incident_type === 'outbound_attack')
                                        <button wire:click="remediate('block_outbound_ssh', {{ $incident->id }})"
                                            class="px-3 py-1 bg-orange-600 hover:bg-orange-700 text-white text-sm rounded transition-colors">
                                            Block SSH
                                        </button>
                                    @endif
                                    <button wire:click="resolveIncident({{ $incident->id }})"
                                        class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-sm rounded transition-colors">
                                        Resolve
                                    </button>
                                    <button wire:click="markFalsePositive({{ $incident->id }})"
                                        class="px-3 py-1 bg-gray-600 hover:bg-gray-700 text-white text-sm rounded transition-colors">
                                        False Positive
                                    </button>
                                </div>
                            </div>

                            @if(!empty($incident->remediation_actions))
                                <div class="mt-3 pt-3 border-t border-gray-700">
                                    <p class="text-xs text-gray-500 mb-1">Remediation Actions:</p>
                                    @foreach($incident->remediation_actions as $action)
                                        <div class="flex items-center gap-2 text-xs">
                                            <span class="{{ $action['success'] ? 'text-green-400' : 'text-red-400' }}">
                                                {{ $action['success'] ? '✓' : '✗' }}
                                            </span>
                                            <span class="text-gray-400">{{ $action['action'] }}: {{ $action['details'] ?? $action['message'] ?? '' }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
