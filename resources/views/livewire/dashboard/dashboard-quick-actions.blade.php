<div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700/50 p-4">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <span class="text-sm font-medium text-slate-300">Quick Actions</span>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('servers.create') }}" class="group relative overflow-hidden inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-slate-300 bg-slate-700/50 hover:bg-slate-700 border border-slate-600/50 hover:border-slate-600 rounded-lg transition-all">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                <svg class="w-4 h-4 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/></svg>
                <span class="relative z-10">Add Server</span>
            </a>
            <button wire:click="deployAll" wire:confirm="Deploy all active projects?" class="group relative overflow-hidden inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-purple-300 bg-purple-500/20 hover:bg-purple-500/30 border border-purple-500/30 hover:border-purple-500/50 rounded-lg transition-all">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                <svg class="w-4 h-4 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                <span class="relative z-10">Deploy All</span>
            </button>
            <button wire:click="clearAllCaches" class="group relative overflow-hidden inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-orange-300 bg-orange-500/20 hover:bg-orange-500/30 border border-orange-500/30 hover:border-orange-500/50 rounded-lg transition-all">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                <svg class="w-4 h-4 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                <span class="relative z-10">Clear Caches</span>
            </button>
            <a href="{{ route('logs.index') }}" class="group relative overflow-hidden inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-slate-300 bg-slate-700/50 hover:bg-slate-700 border border-slate-600/50 hover:border-slate-600 rounded-lg transition-all">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                <svg class="w-4 h-4 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="relative z-10">View Logs</span>
            </a>
            <a href="{{ route('settings.health-checks') }}" class="group relative overflow-hidden inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-slate-300 bg-slate-700/50 hover:bg-slate-700 border border-slate-600/50 hover:border-slate-600 rounded-lg transition-all">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                <svg class="w-4 h-4 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="relative z-10">Health Checks</span>
            </a>
        </div>
    </div>
</div>
