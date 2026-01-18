<div>
    {{-- Cache Performance Card --}}
    <div class="bg-white dark:bg-slate-800/50 backdrop-blur-sm rounded-xl border border-gray-200 dark:border-slate-700/50 shadow-sm dark:shadow-none overflow-hidden">
        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-slate-700/50">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-purple-100 dark:bg-purple-500/20 rounded-lg">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Cache Performance</h3>
                    <p class="text-xs text-gray-500 dark:text-slate-400">Hit rate & statistics</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="toggleDetails"
                        class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-slate-300 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700/50 transition-colors">
                    <svg class="w-4 h-4 transition-transform {{ $showDetails ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <button wire:click="refreshStats"
                        wire:loading.attr="disabled"
                        class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-slate-300 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700/50 transition-colors">
                    <svg wire:loading.remove wire:target="refreshStats" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <svg wire:loading wire:target="refreshStats" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </div>

        @if($isLoading)
        {{-- Loading State --}}
        <div class="p-5 space-y-4">
            <div class="animate-pulse flex items-center justify-between">
                <div class="w-24 h-8 bg-gray-200 dark:bg-slate-700 rounded"></div>
                <div class="w-16 h-6 bg-gray-200 dark:bg-slate-700 rounded-full"></div>
            </div>
            <div class="animate-pulse grid grid-cols-3 gap-3">
                @for($i = 0; $i < 3; $i++)
                <div class="h-16 bg-gray-200 dark:bg-slate-700 rounded-lg"></div>
                @endfor
            </div>
        </div>
        @elseif($hasError)
        {{-- Error State --}}
        <div class="p-5">
            <div class="flex items-center gap-2 text-red-500 dark:text-red-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span class="text-sm">{{ $errorMessage }}</span>
            </div>
        </div>
        @else
        {{-- Content --}}
        <div class="p-5">
            {{-- Hit Rate Display --}}
            <div class="flex items-center justify-between mb-4">
                <div>
                    <span class="text-3xl font-bold {{ $this->getHitRateStatus() }}">{{ number_format($stats['hit_rate'], 1) }}%</span>
                    <span class="text-sm text-gray-500 dark:text-slate-400 ml-1">hit rate</span>
                </div>
                <span class="px-2.5 py-1 text-xs font-medium rounded-full {{ $this->getHitRateBadgeClass() }}">
                    @if($stats['hit_rate'] >= 80)
                        Excellent
                    @elseif($stats['hit_rate'] >= 50)
                        Good
                    @else
                        Needs Attention
                    @endif
                </span>
            </div>

            {{-- Quick Stats --}}
            <div class="grid grid-cols-3 gap-3 mb-4">
                <div class="bg-gray-50 dark:bg-slate-900/50 rounded-lg p-3 text-center">
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($stats['hits']) }}</p>
                    <p class="text-xs text-gray-500 dark:text-slate-400">Hits</p>
                </div>
                <div class="bg-gray-50 dark:bg-slate-900/50 rounded-lg p-3 text-center">
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($stats['misses']) }}</p>
                    <p class="text-xs text-gray-500 dark:text-slate-400">Misses</p>
                </div>
                <div class="bg-gray-50 dark:bg-slate-900/50 rounded-lg p-3 text-center">
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($stats['avg_latency_ms'], 2) }}ms</p>
                    <p class="text-xs text-gray-500 dark:text-slate-400">Avg Latency</p>
                </div>
            </div>

            {{-- Progress Bar --}}
            <div class="mb-4">
                <div class="flex justify-between text-xs text-gray-500 dark:text-slate-400 mb-1">
                    <span>{{ number_format($stats['total_requests']) }} total requests</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-2 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500
                        @if($stats['hit_rate'] >= 80) bg-green-500
                        @elseif($stats['hit_rate'] >= 50) bg-yellow-500
                        @else bg-red-500
                        @endif"
                        style="width: {{ min($stats['hit_rate'], 100) }}%">
                    </div>
                </div>
            </div>

            {{-- Expanded Details --}}
            @if($showDetails)
            <div class="pt-4 border-t border-gray-100 dark:border-slate-700/50 space-y-4" x-show="true" x-transition>
                {{-- Top Keys --}}
                @if(count($topKeys) > 0)
                <div>
                    <h4 class="text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-2">Top Keys</h4>
                    <div class="space-y-2">
                        @foreach($topKeys as $key)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 dark:text-slate-300 truncate max-w-[60%]" title="{{ $key['key'] }}">{{ Str::limit($key['key'], 30) }}</span>
                            <span class="text-gray-500 dark:text-slate-400">{{ $key['hits'] }} hits</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Recommendations --}}
                @if(count($recommendations) > 0)
                <div>
                    <h4 class="text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-2">Recommendations</h4>
                    <div class="space-y-2">
                        @foreach($recommendations as $recommendation)
                        <div class="flex items-start gap-2 text-sm">
                            <svg class="w-4 h-4 text-blue-500 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-gray-600 dark:text-slate-300">{{ $recommendation }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Redis Stats --}}
                @if($redisStats)
                <div>
                    <h4 class="text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider mb-2">Redis Stats</h4>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-slate-400">Memory:</span>
                            <span class="text-gray-900 dark:text-white">{{ $redisStats['used_memory'] ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-slate-400">Keys:</span>
                            <span class="text-gray-900 dark:text-white">{{ number_format($redisStats['db_size'] ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-slate-400">Uptime:</span>
                            <span class="text-gray-900 dark:text-white">{{ $redisStats['uptime_days'] ?? 0 }} days</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-slate-400">Clients:</span>
                            <span class="text-gray-900 dark:text-white">{{ $redisStats['connected_clients'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Reset Button --}}
                <div class="pt-2">
                    <button wire:click="resetStats"
                            wire:loading.attr="disabled"
                            wire:confirm="Are you sure you want to reset all cache statistics? This action cannot be undone."
                            class="w-full px-3 py-2 text-sm text-gray-600 dark:text-slate-300 hover:text-gray-900 dark:hover:text-white bg-gray-100 dark:bg-slate-700/50 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-lg transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span>Reset Statistics</span>
                    </button>
                </div>
            </div>
            @endif
        </div>
        @endif
    </div>
</div>
