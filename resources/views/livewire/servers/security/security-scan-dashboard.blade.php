<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-purple-800 via-purple-900 to-purple-800 shadow-2xl">
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                            <div>
                                <h1 class="text-2xl font-bold text-white">Security Scans</h1>
                                <p class="text-white/80">{{ $server->name }} - Scan History</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button wire:click="runScan" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-white text-purple-800 font-medium rounded-lg hover:bg-gray-100 transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" wire:loading.class="animate-spin" wire:target="runScan" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span wire:loading.remove wire:target="runScan">Run New Scan</span>
                        <span wire:loading wire:target="runScan">Scanning...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Message -->
    @if($flashMessage)
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="rounded-lg p-4 {{ $flashType === 'success' ? 'bg-green-900/50 text-green-200 border border-green-700' : 'bg-red-900/50 text-red-200 border border-red-700' }}">
                {{ $flashMessage }}
            </div>
        </div>
    @endif

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Latest Scan Summary -->
        @if($this->latestScan)
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6 mb-6">
                <h3 class="text-lg font-semibold text-white mb-4">Latest Scan Results</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-gray-900/50 rounded-lg">
                        <div class="text-3xl font-bold text-{{ $this->latestScan->score_color }}-400">{{ $this->latestScan->score }}</div>
                        <div class="text-gray-400 text-sm">Security Score</div>
                    </div>
                    <div class="text-center p-4 bg-gray-900/50 rounded-lg">
                        <div class="text-3xl font-bold text-{{ $this->latestScan->risk_level_color }}-400">{{ ucfirst($this->latestScan->risk_level) }}</div>
                        <div class="text-gray-400 text-sm">Risk Level</div>
                    </div>
                    <div class="text-center p-4 bg-gray-900/50 rounded-lg">
                        <div class="text-3xl font-bold text-white">{{ count($this->latestScan->recommendations ?? []) }}</div>
                        <div class="text-gray-400 text-sm">Recommendations</div>
                    </div>
                    <div class="text-center p-4 bg-gray-900/50 rounded-lg">
                        <div class="text-lg font-medium text-white">{{ $this->latestScan->completed_at?->diffForHumans() }}</div>
                        <div class="text-gray-400 text-sm">Last Scanned</div>
                    </div>
                </div>

                @if(!empty($this->latestScan->recommendations))
                    <div class="mt-6">
                        <h4 class="text-white font-medium mb-3">Recommendations</h4>
                        <div class="space-y-2">
                            @foreach($this->latestScan->recommendations as $rec)
                                <div class="flex items-start gap-3 p-3 bg-gray-900/50 rounded-lg">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                        {{ $rec['priority'] === 'high' ? 'bg-red-500/20 text-red-400' : '' }}
                                        {{ $rec['priority'] === 'medium' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                                        {{ $rec['priority'] === 'low' ? 'bg-blue-500/20 text-blue-400' : '' }}">
                                        {{ strtoupper($rec['priority']) }}
                                    </span>
                                    <div>
                                        <h5 class="text-white font-medium">{{ $rec['title'] }}</h5>
                                        <p class="text-gray-400 text-sm">{{ $rec['description'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <!-- Scan History -->
        <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Scan History</h3>

            @if($this->scans->isEmpty())
                <div class="text-center py-8">
                    <svg class="w-12 h-12 text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="text-gray-400">No security scans have been run yet</p>
                    <button wire:click="runScan" class="mt-4 text-purple-400 hover:text-purple-300">
                        Run your first scan
                    </button>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-gray-400 text-sm border-b border-gray-700">
                                <th class="pb-3 font-medium">Date</th>
                                <th class="pb-3 font-medium">Score</th>
                                <th class="pb-3 font-medium">Risk Level</th>
                                <th class="pb-3 font-medium">Status</th>
                                <th class="pb-3 font-medium">Duration</th>
                                <th class="pb-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700/50">
                            @foreach($this->scans as $scan)
                                <tr class="text-gray-300">
                                    <td class="py-3">{{ $scan->created_at->format('M d, Y H:i') }}</td>
                                    <td class="py-3">
                                        @if($scan->score)
                                            <span class="font-bold text-{{ $scan->score_color }}-400">{{ $scan->score }}</span>/100
                                        @else
                                            --
                                        @endif
                                    </td>
                                    <td class="py-3">
                                        @if($scan->risk_level)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $scan->risk_level_color }}-500/20 text-{{ $scan->risk_level_color }}-400">
                                                {{ ucfirst($scan->risk_level) }}
                                            </span>
                                        @else
                                            --
                                        @endif
                                    </td>
                                    <td class="py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            {{ $scan->status === 'completed' ? 'bg-green-500/20 text-green-400' : '' }}
                                            {{ $scan->status === 'running' ? 'bg-blue-500/20 text-blue-400' : '' }}
                                            {{ $scan->status === 'failed' ? 'bg-red-500/20 text-red-400' : '' }}
                                            {{ $scan->status === 'pending' ? 'bg-gray-500/20 text-gray-400' : '' }}">
                                            {{ ucfirst($scan->status) }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-gray-400">
                                        {{ $scan->duration ? $scan->duration . 's' : '--' }}
                                    </td>
                                    <td class="py-3 text-right">
                                        @if($scan->status === 'completed')
                                            <button wire:click="viewScanDetails({{ $scan->id }})"
                                                class="text-purple-400 hover:text-purple-300 transition-colors">
                                                View Details
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $this->scans->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Scan Details Modal -->
    @if($showDetails && $selectedScan)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/70 transition-opacity" wire:click="closeDetails"></div>

                <div class="relative w-full max-w-3xl bg-gray-800 rounded-2xl shadow-xl border border-gray-700 max-h-[90vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-semibold text-white">Scan Details</h3>
                            <button wire:click="closeDetails" class="text-gray-400 hover:text-white">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div class="text-center p-4 bg-gray-900/50 rounded-lg">
                                <div class="text-2xl font-bold text-{{ $selectedScan->score_color }}-400">{{ $selectedScan->score }}</div>
                                <div class="text-gray-400 text-sm">Score</div>
                            </div>
                            <div class="text-center p-4 bg-gray-900/50 rounded-lg">
                                <div class="text-2xl font-bold text-{{ $selectedScan->risk_level_color }}-400">{{ ucfirst($selectedScan->risk_level) }}</div>
                                <div class="text-gray-400 text-sm">Risk Level</div>
                            </div>
                            <div class="text-center p-4 bg-gray-900/50 rounded-lg">
                                <div class="text-lg font-medium text-white">{{ $selectedScan->completed_at?->format('M d, Y H:i') }}</div>
                                <div class="text-gray-400 text-sm">Completed</div>
                            </div>
                        </div>

                        @if(!empty($selectedScan->findings))
                            <div class="mb-6">
                                <h4 class="text-white font-medium mb-3">Findings</h4>
                                <div class="bg-gray-900/50 rounded-lg p-4">
                                    <pre class="text-gray-300 text-sm overflow-x-auto">{{ json_encode($selectedScan->findings, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            </div>
                        @endif

                        @if(!empty($selectedScan->recommendations))
                            <div>
                                <h4 class="text-white font-medium mb-3">Recommendations</h4>
                                <div class="space-y-2">
                                    @foreach($selectedScan->recommendations as $rec)
                                        <div class="flex items-start gap-3 p-3 bg-gray-900/50 rounded-lg">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                {{ $rec['priority'] === 'high' ? 'bg-red-500/20 text-red-400' : '' }}
                                                {{ $rec['priority'] === 'medium' ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                                                {{ $rec['priority'] === 'low' ? 'bg-blue-500/20 text-blue-400' : '' }}">
                                                {{ strtoupper($rec['priority']) }}
                                            </span>
                                            <div>
                                                <h5 class="text-white font-medium">{{ $rec['title'] }}</h5>
                                                <p class="text-gray-400 text-sm">{{ $rec['description'] }}</p>
                                                @if(!empty($rec['command']))
                                                    <code class="mt-2 block text-xs bg-gray-800 text-green-400 p-2 rounded">{{ $rec['command'] }}</code>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
