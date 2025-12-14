<div wire:init="loadServerHealth" class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700/50 overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-700/50 flex items-center justify-between">
        <h2 class="font-semibold text-white">Server Health</h2>
        <a href="{{ route('servers.index') }}" class="text-sm text-blue-400 hover:text-blue-300">View all &rarr;</a>
    </div>
    <div class="divide-y divide-slate-700/50 max-h-[400px] overflow-y-auto">
        @forelse($serverHealth as $server)
        <a href="{{ route('servers.show', $server['server_id']) }}" class="block px-6 py-4 hover:bg-slate-700/30 transition-colors">
            <div class="flex items-center justify-between mb-2">
                <span class="font-medium text-white text-sm">{{ $server['server_name'] }}</span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full {{ ($server['health_status'] ?? 'unknown') === 'healthy' ? 'bg-green-500' : (($server['health_status'] ?? 'unknown') === 'warning' ? 'bg-amber-500' : 'bg-red-500') }}"></span>
                    <span class="text-xs text-slate-400">{{ ucfirst($server['health_status'] ?? 'unknown') }}</span>
                </span>
            </div>
            @if($server['cpu_usage'] !== null)
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-400 w-12">CPU</span>
                    <div class="flex-1 h-1.5 bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full rounded-full {{ $server['cpu_usage'] < 60 ? 'bg-green-500' : ($server['cpu_usage'] < 80 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ min($server['cpu_usage'], 100) }}%"></div>
                    </div>
                    <span class="text-xs font-medium text-slate-300 w-10 text-right">{{ number_format($server['cpu_usage'], 0) }}%</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-400 w-12">RAM</span>
                    <div class="flex-1 h-1.5 bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full rounded-full {{ $server['memory_usage'] < 60 ? 'bg-green-500' : ($server['memory_usage'] < 80 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ min($server['memory_usage'], 100) }}%"></div>
                    </div>
                    <span class="text-xs font-medium text-slate-300 w-10 text-right">{{ number_format($server['memory_usage'], 0) }}%</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-400 w-12">Disk</span>
                    <div class="flex-1 h-1.5 bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full rounded-full {{ $server['disk_usage'] < 60 ? 'bg-green-500' : ($server['disk_usage'] < 80 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ min($server['disk_usage'], 100) }}%"></div>
                    </div>
                    <span class="text-xs font-medium text-slate-300 w-10 text-right">{{ number_format($server['disk_usage'], 0) }}%</span>
                </div>
            </div>
            @else
            <p class="text-xs text-slate-500">No metrics available</p>
            @endif
        </a>
        @empty
        <div class="px-6 py-12 text-center">
            <svg class="mx-auto h-12 w-12 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
            </svg>
            <p class="mt-2 text-sm text-slate-400">No servers online</p>
            <a href="{{ route('servers.create') }}" class="mt-3 inline-flex items-center text-sm text-blue-400 hover:text-blue-300">Add your first server &rarr;</a>
        </div>
        @endforelse
    </div>
</div>
