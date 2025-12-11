<div>
    {{-- Cache Management Section --}}
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 p-6 mb-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </div>
            <div>
                <h3 class="font-semibold text-white">Cache Management</h3>
                <p class="text-sm text-slate-400">Clear and rebuild application caches</p>
            </div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            @foreach(['config' => 'Config', 'route' => 'Routes', 'view' => 'Views', 'event' => 'Events', 'all' => 'All'] as $type => $label)
                <button wire:click="clearCache('{{ $type }}')" wire:loading.attr="disabled"
                    class="group p-4 rounded-xl bg-slate-900/50 border border-slate-700/50 hover:border-indigo-500/50 hover:bg-indigo-500/10 transition-all text-center">
                    <span class="block text-sm font-medium text-white group-hover:text-indigo-400 transition-colors">{{ $label }}</span>
                    <span class="block text-xs text-slate-500 mt-1">Clear</span>
                </button>
            @endforeach
        </div>
        <div class="mt-4">
            <button wire:click="rebuildCache" wire:loading.attr="disabled"
                class="w-full p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/20 transition-all text-center font-medium">
                Rebuild All Caches
            </button>
        </div>
    </div>

    {{-- Storage Usage --}}
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 p-6 mb-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-600 to-slate-700 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                </div>
                <div>
                    <h3 class="font-semibold text-white">Storage Usage</h3>
                    <p class="text-sm text-slate-400">
                        @php
                            $diskUsed = $storageInfo['disk_used'] ?? 0;
                            $diskTotal = $storageInfo['disk_total'] ?? 1;
                            $usedGB = round($diskUsed / (1024 ** 3), 2);
                            $totalGB = round($diskTotal / (1024 ** 3), 2);
                        @endphp
                        {{ $usedGB }} GB of {{ $totalGB }} GB used
                    </p>
                </div>
            </div>
        </div>
        <div class="h-3 bg-slate-900 rounded-full overflow-hidden mb-4">
            @php $storagePercent = $storageInfo['disk_percent'] ?? 0; @endphp
            <div class="h-full rounded-full transition-all duration-500 {{ $storagePercent > 90 ? 'bg-red-500' : ($storagePercent > 70 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $storagePercent }}%"></div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach(['logs' => ['label' => 'Logs', 'size' => $storageInfo['storage_logs'] ?? 0], 'cache' => ['label' => 'Cache', 'size' => $storageInfo['storage_cache'] ?? 0], 'sessions' => ['label' => 'Sessions', 'size' => $storageInfo['storage_sessions'] ?? 0], 'views' => ['label' => 'Views', 'size' => $storageInfo['storage_views'] ?? 0]] as $key => $data)
                <div class="flex flex-col justify-between p-3 rounded-xl bg-slate-900/50 border border-slate-800">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-slate-400">{{ $data['label'] }}</span>
                        <button wire:click="cleanStorage('{{ $key }}')" class="text-xs text-slate-500 hover:text-red-400 transition-colors" title="Clean {{ $data['label'] }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                    <span class="text-xs text-slate-500 font-mono">
                        @php
                            $sizeBytes = $data['size'];
                            $units = ['B', 'KB', 'MB', 'GB'];
                            $bytes = max($sizeBytes, 0);
                            $pow = $bytes > 0 ? floor(log($bytes) / log(1024)) : 0;
                            $pow = min($pow, count($units) - 1);
                            $size = round($bytes / (1024 ** $pow), 2);
                        @endphp
                        {{ $size }} {{ $units[$pow] }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Redis Info (if connected) --}}
    @if($redisConnected)
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white">Redis Cache</h3>
                        <p class="text-sm text-slate-400">{{ $redisInfo['total_keys'] ?? 0 }} keys stored</p>
                    </div>
                </div>
                <button wire:click="flushRedis" wire:confirm="Are you sure you want to flush Redis? This will clear all cached data."
                    class="px-4 py-2 rounded-xl bg-red-500/20 text-red-400 hover:bg-red-500/30 transition-all border border-red-500/30 text-sm font-medium">
                    Flush Redis
                </button>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="p-3 rounded-xl bg-slate-900/50 border border-slate-800">
                    <span class="block text-xs text-slate-400 mb-1">Version</span>
                    <span class="block text-sm text-white font-mono">{{ $redisInfo['version'] ?? 'N/A' }}</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-900/50 border border-slate-800">
                    <span class="block text-xs text-slate-400 mb-1">Memory Used</span>
                    <span class="block text-sm text-white font-mono">{{ $redisInfo['used_memory_human'] ?? 'N/A' }}</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-900/50 border border-slate-800">
                    <span class="block text-xs text-slate-400 mb-1">Clients</span>
                    <span class="block text-sm text-white font-mono">{{ $redisInfo['connected_clients'] ?? 0 }}</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-900/50 border border-slate-800">
                    <span class="block text-xs text-slate-400 mb-1">Uptime</span>
                    <span class="block text-sm text-white font-mono">{{ $redisInfo['uptime_days'] ?? 0 }} days</span>
                </div>
            </div>
        </div>
    @endif
</div>
