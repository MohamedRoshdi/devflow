<div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 overflow-hidden">
    <div class="p-5 border-b border-slate-700/50 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-500 to-pink-600 flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <h3 class="font-semibold text-white">Application Logs</h3>
                <p class="text-sm text-slate-400">{{ count($logFiles ?? []) }} log files</p>
            </div>
        </div>
        <button wire:click="refreshLogs" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-700/50 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        </button>
    </div>

    {{-- Log Files List --}}
    <div class="grid lg:grid-cols-4 divide-y lg:divide-y-0 lg:divide-x divide-slate-700/50">
        <div class="p-4 space-y-1 max-h-96 overflow-y-auto lg:col-span-1">
            @forelse($logFiles ?? [] as $log)
                <button wire:click="selectLogFile('{{ $log['name'] }}')"
                    class="w-full flex items-center justify-between p-3 rounded-xl text-left transition-all {{ ($selectedLogFile ?? '') === $log['name'] ? 'bg-rose-500/20 border border-rose-500/30' : 'hover:bg-slate-700/50' }}">
                    <div class="min-w-0">
                        <span class="block text-sm font-medium text-white truncate">{{ $log['name'] }}</span>
                        <span class="block text-xs text-slate-500">{{ $log['size'] ?? '0 KB' }}</span>
                    </div>
                </button>
            @empty
                <p class="text-sm text-slate-500 text-center py-4">No log files</p>
            @endforelse
        </div>

        {{-- Log Content --}}
        <div class="lg:col-span-3 p-4">
            @if($recentLogs ?? false)
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-medium text-slate-400">{{ $selectedLogFile ?? 'Select a log file' }}</span>
                    <div class="flex items-center gap-2">
                        @if($selectedLogFile)
                            <a href="{{ route('projects.devflow.logs.download', $selectedLogFile) }}" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-700/50 transition-all" title="Download">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            </a>
                            <button wire:click="clearLogFile('{{ $selectedLogFile }}')" wire:confirm="Clear this log file?" class="p-1.5 rounded-lg text-slate-400 hover:text-red-400 hover:bg-red-500/10 transition-all" title="Clear">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                            @if($selectedLogFile !== 'laravel.log')
                                <button wire:click="deleteLogFile('{{ $selectedLogFile }}')" wire:confirm="Delete this log file permanently?" class="p-1.5 rounded-lg text-slate-400 hover:text-red-400 hover:bg-red-500/10 transition-all" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
                <div class="rounded-xl bg-slate-950 border border-slate-800 p-4 max-h-80 overflow-y-auto font-mono text-xs">
                    <pre class="text-slate-300 whitespace-pre-wrap leading-relaxed">{{ $recentLogs }}</pre>
                </div>
            @else
                <div class="flex items-center justify-center h-64 text-slate-500">
                    <div class="text-center">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <p>Select a log file to view</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
