<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-yellow-800 via-amber-900 to-yellow-800 shadow-2xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex items-center gap-4">
                    <a href="{{ route('servers.security.guardian', $server) }}" class="p-2 bg-white/10 rounded-lg hover:bg-white/20 transition-colors">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-white">Predictive Analytics</h1>
                        <p class="text-white/80">{{ $server->name }} - Trends, Predictions & Baseline Drifts</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button wire:click="runAnalysis" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-white text-amber-800 font-medium rounded-lg hover:bg-gray-100 transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" wire:loading.class="animate-spin" wire:target="runAnalysis" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        <span wire:loading.remove wire:target="runAnalysis">Run Analysis</span>
                        <span wire:loading wire:target="runAnalysis">Analyzing...</span>
                    </button>
                    <button wire:click="captureBaseline" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white font-medium rounded-lg transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" wire:loading.class="animate-spin" wire:target="captureBaseline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span wire:loading.remove wire:target="captureBaseline">Capture Baseline</span>
                        <span wire:loading wire:target="captureBaseline">Capturing...</span>
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
        <!-- Baseline Info -->
        @php $baseline = $this->baseline; @endphp
        <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-6 mb-8">
            <h3 class="font-semibold text-white mb-4">Latest Baseline</h3>
            @if($baseline)
                <div class="grid grid-cols-2 md:grid-cols-6 gap-4 text-center">
                    <div>
                        <p class="text-2xl font-bold text-white">{{ count($baseline->running_services) }}</p>
                        <p class="text-xs text-gray-400">Services</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-white">{{ count($baseline->listening_ports) }}</p>
                        <p class="text-xs text-gray-400">Ports</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-white">{{ count($baseline->system_users) }}</p>
                        <p class="text-xs text-gray-400">Users</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-white">{{ count($baseline->crontab_entries) }}</p>
                        <p class="text-xs text-gray-400">Crontabs</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-white">{{ round($baseline->avg_cpu_usage, 1) }}%</p>
                        <p class="text-xs text-gray-400">Avg CPU</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-white">{{ round($baseline->avg_memory_usage, 1) }}%</p>
                        <p class="text-xs text-gray-400">Avg Memory</p>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-4">Captured {{ $baseline->created_at->diffForHumans() }}</p>
            @else
                <p class="text-gray-500 text-sm">No baseline captured yet. Click "Capture Baseline" to create one.</p>
            @endif
        </div>

        <!-- Baseline Drift -->
        @php $drifts = $this->baselineDrift; @endphp
        @if(!empty($drifts))
            <div class="bg-yellow-900/20 border border-yellow-700/50 rounded-xl p-6 mb-8">
                <h3 class="font-semibold text-yellow-300 mb-4">Baseline Drift Detected</h3>
                <div class="space-y-3">
                    @foreach($drifts as $category => $drift)
                        <div class="flex items-start gap-3">
                            <span class="text-yellow-400 font-medium text-sm min-w-[120px]">{{ ucfirst(str_replace('_', ' ', $category)) }}:</span>
                            <div class="text-sm">
                                @if(!empty($drift['added'] ?? []))
                                    <p class="text-green-400">+ Added: {{ implode(', ', array_slice($drift['added'], 0, 5)) }}
                                        @if(count($drift['added']) > 5) <span class="text-gray-500">and {{ count($drift['added']) - 5 }} more</span> @endif
                                    </p>
                                @endif
                                @if(!empty($drift['removed'] ?? []))
                                    <p class="text-red-400">- Removed: {{ implode(', ', array_slice($drift['removed'], 0, 5)) }}
                                        @if(count($drift['removed']) > 5) <span class="text-gray-500">and {{ count($drift['removed']) - 5 }} more</span> @endif
                                    </p>
                                @endif
                                @if(isset($drift['delta']))
                                    <p class="{{ $drift['delta'] > 0 ? 'text-red-400' : 'text-green-400' }}">
                                        Delta: {{ $drift['delta'] > 0 ? '+' : '' }}{{ $drift['delta'] }}
                                        (Baseline: {{ $drift['baseline'] ?? '?' }}, Current: {{ $drift['current'] ?? '?' }})
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Filter -->
        <div class="flex gap-2 mb-6">
            @foreach(['active' => 'Active', 'acknowledged' => 'Acknowledged', 'resolved' => 'Resolved', 'all' => 'All'] as $value => $label)
                <button wire:click="$set('statusFilter', '{{ $value }}')"
                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors
                        {{ $statusFilter === $value ? 'bg-amber-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <!-- Predictions Table -->
        <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-gray-400 border-b border-gray-700/50">
                        <th class="text-left p-3">Severity</th>
                        <th class="text-left p-3">Prediction</th>
                        <th class="text-left p-3">Type</th>
                        <th class="text-left p-3">Confidence</th>
                        <th class="text-left p-3">Impact</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->predictions as $prediction)
                        <tr class="border-b border-gray-700/30 hover:bg-gray-700/20">
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-xs
                                    {{ $prediction->severity === 'critical' ? 'bg-red-500/20 text-red-400' : '' }}
                                    {{ $prediction->severity === 'high' ? 'bg-orange-500/20 text-orange-400' : '' }}
                                    {{ $prediction->severity === 'medium' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                                    {{ $prediction->severity === 'low' ? 'bg-blue-500/20 text-blue-400' : '' }}">
                                    {{ ucfirst($prediction->severity) }}
                                </span>
                            </td>
                            <td class="p-3 text-white">{{ $prediction->title }}</td>
                            <td class="p-3 text-gray-400 text-xs">{{ str_replace('_', ' ', $prediction->prediction_type) }}</td>
                            <td class="p-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-16 bg-gray-700 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full {{ $prediction->confidence_score > 0.8 ? 'bg-red-500' : ($prediction->confidence_score > 0.6 ? 'bg-yellow-500' : 'bg-blue-500') }}"
                                             style="width: {{ $prediction->confidence_score * 100 }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-400">{{ round($prediction->confidence_score * 100) }}%</span>
                                </div>
                            </td>
                            <td class="p-3 text-gray-400 text-xs">
                                {{ $prediction->predicted_impact_at ? $prediction->predicted_impact_at->diffForHumans() : '-' }}
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded text-xs bg-{{ $prediction->status_color }}-500/20 text-{{ $prediction->status_color }}-400">
                                    {{ ucfirst($prediction->status) }}
                                </span>
                            </td>
                            <td class="p-3">
                                @if($prediction->isActive())
                                    <div class="flex gap-1">
                                        <button wire:click="acknowledgePrediction({{ $prediction->id }})" class="text-xs text-blue-400 hover:text-blue-300">Ack</button>
                                        <span class="text-gray-600">|</span>
                                        <button wire:click="resolvePrediction({{ $prediction->id }})" class="text-xs text-green-400 hover:text-green-300">Resolve</button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-gray-500">No predictions found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if($this->predictions->hasPages())
                <div class="p-4 border-t border-gray-700/50">
                    {{ $this->predictions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
