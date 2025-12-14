<div wire:init="loadStats">
    {{-- Main Stats Grid --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @if($isLoading)
        {{-- Skeleton Loading --}}
        @for($i = 0; $i < 4; $i++)
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl p-5 border border-slate-700/50 animate-pulse">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 bg-slate-700 rounded-lg"></div>
                <div class="w-16 h-5 bg-slate-700 rounded-full"></div>
            </div>
            <div class="mt-4 w-12 h-8 bg-slate-700 rounded"></div>
            <div class="mt-1 w-20 h-4 bg-slate-700 rounded"></div>
        </div>
        @endfor
        @elseif($hasError)
        {{-- Error State --}}
        <div class="col-span-full">
            <div class="bg-red-500/10 backdrop-blur-sm rounded-xl p-6 border border-red-500/30">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-red-500/20 rounded-lg">
                            <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-red-400 font-medium">{{ $errorMessage }}</p>
                            <p class="text-red-400/70 text-sm">There was a problem loading dashboard data.</p>
                        </div>
                    </div>
                    <button wire:click="retryLoad"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50"
                            class="px-4 py-2 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-lg font-medium transition-all flex items-center gap-2">
                        <svg wire:loading.remove wire:target="retryLoad" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <svg wire:loading wire:target="retryLoad" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="retryLoad">Retry</span>
                        <span wire:loading wire:target="retryLoad">Retrying...</span>
                    </button>
                </div>
            </div>
        </div>
        @else
        {{-- Servers --}}
        <a href="{{ route('servers.index') }}" class="group relative overflow-hidden bg-slate-800/50 backdrop-blur-sm rounded-xl p-5 border border-slate-700/50 hover:border-blue-500/50 hover:shadow-lg hover:shadow-blue-500/10 transition-all">
            <div class="relative z-10 flex items-center justify-between">
                <div class="relative p-2 bg-gradient-to-br from-blue-500/20 to-blue-600/20 rounded-lg group-hover:from-blue-500 group-hover:to-blue-600 transition-all shadow-lg shadow-blue-500/0 group-hover:shadow-blue-500/50">
                    <svg class="w-5 h-5 text-blue-400 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                    </svg>
                </div>
                <span class="text-xs font-medium px-2 py-1 rounded-full {{ ($stats['online_servers'] ?? 0) == ($stats['total_servers'] ?? 0) ? 'bg-green-500/20 text-green-400 border border-green-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30' }}">
                    {{ $stats['online_servers'] ?? 0 }}/{{ $stats['total_servers'] ?? 0 }} online
                </span>
            </div>
            <p class="mt-4 text-3xl font-bold text-white relative z-10">{{ $stats['total_servers'] ?? 0 }}</p>
            <p class="text-sm text-slate-400 relative z-10">Servers</p>
        </a>

        {{-- Projects --}}
        <a href="{{ route('projects.index') }}" class="group relative overflow-hidden bg-slate-800/50 backdrop-blur-sm rounded-xl p-5 border border-slate-700/50 hover:border-green-500/50 hover:shadow-lg hover:shadow-green-500/10 transition-all">
            <div class="relative z-10 flex items-center justify-between">
                <div class="relative p-2 bg-gradient-to-br from-green-500/20 to-green-600/20 rounded-lg group-hover:from-green-500 group-hover:to-green-600 transition-all shadow-lg shadow-green-500/0 group-hover:shadow-green-500/50">
                    <svg class="w-5 h-5 text-green-400 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium px-2 py-1 rounded-full bg-green-500/20 text-green-400 border border-green-500/30">
                    {{ $stats['running_projects'] ?? 0 }} running
                </span>
            </div>
            <p class="mt-4 text-3xl font-bold text-white relative z-10">{{ $stats['total_projects'] ?? 0 }}</p>
            <p class="text-sm text-slate-400 relative z-10">Projects</p>
        </a>

        {{-- Deployments Today --}}
        <a href="{{ route('deployments.index') }}" class="group relative overflow-hidden bg-slate-800/50 backdrop-blur-sm rounded-xl p-5 border border-slate-700/50 hover:border-purple-500/50 hover:shadow-lg hover:shadow-purple-500/10 transition-all">
            <div class="relative z-10 flex items-center justify-between">
                <div class="relative p-2 bg-gradient-to-br from-purple-500/20 to-purple-600/20 rounded-lg group-hover:from-purple-500 group-hover:to-purple-600 transition-all shadow-lg shadow-purple-500/0 group-hover:shadow-purple-500/50">
                    <svg class="w-5 h-5 text-purple-400 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                </div>
                @if($activeDeployments > 0)
                <span class="text-xs font-medium px-2 py-1 rounded-full bg-purple-500/20 text-purple-400 border border-purple-500/30 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 bg-purple-400 rounded-full animate-pulse"></span>
                    {{ $activeDeployments }} active
                </span>
                @endif
            </div>
            <p class="mt-4 text-3xl font-bold text-white relative z-10">{{ $deploymentsToday }}</p>
            <p class="text-sm text-slate-400 relative z-10">Deployments Today</p>
        </a>

        {{-- Security Score --}}
        <a href="{{ route('settings.system') }}" class="group relative overflow-hidden bg-slate-800/50 backdrop-blur-sm rounded-xl p-5 border border-slate-700/50 hover:border-emerald-500/50 hover:shadow-lg hover:shadow-emerald-500/10 transition-all">
            <div class="relative z-10 flex items-center justify-between">
                <div class="relative p-2 {{ $overallSecurityScore >= 80 ? 'bg-gradient-to-br from-emerald-500/20 to-emerald-600/20' : ($overallSecurityScore >= 60 ? 'bg-gradient-to-br from-amber-500/20 to-amber-600/20' : 'bg-gradient-to-br from-red-500/20 to-red-600/20') }} rounded-lg group-hover:from-emerald-500 group-hover:to-emerald-600 transition-all shadow-lg shadow-emerald-500/0 group-hover:shadow-emerald-500/50">
                    <svg class="w-5 h-5 {{ $overallSecurityScore >= 80 ? 'text-emerald-400' : ($overallSecurityScore >= 60 ? 'text-amber-400' : 'text-red-400') }} group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium px-2 py-1 rounded-full {{ $overallSecurityScore >= 80 ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : ($overallSecurityScore >= 60 ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' : 'bg-red-500/20 text-red-400 border border-red-500/30') }}">
                    {{ $overallSecurityScore >= 80 ? 'Excellent' : ($overallSecurityScore >= 60 ? 'Good' : 'Needs work') }}
                </span>
            </div>
            <p class="mt-4 text-3xl font-bold text-white relative z-10">{{ $overallSecurityScore }}%</p>
            <p class="text-sm text-slate-400 relative z-10">Security Score</p>
        </a>
        @endif
    </div>

    {{-- Secondary Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700/50 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gradient-to-br from-teal-500/20 to-teal-600/20 rounded-lg shadow-lg shadow-teal-500/0">
                    <svg class="w-5 h-5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ $sslStats['active_certificates'] ?? 0 }}</p>
                    <p class="text-xs text-slate-400">SSL Certificates</p>
                </div>
            </div>
        </div>
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700/50 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gradient-to-br from-emerald-500/20 to-emerald-600/20 rounded-lg shadow-lg shadow-emerald-500/0">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ $healthCheckStats['healthy'] ?? 0 }}</p>
                    <p class="text-xs text-slate-400">Health Checks</p>
                </div>
            </div>
        </div>
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700/50 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gradient-to-br from-indigo-500/20 to-indigo-600/20 rounded-lg shadow-lg shadow-indigo-500/0">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ $queueStats['pending'] ?? 0 }}</p>
                    <p class="text-xs text-slate-400">Queue Jobs</p>
                </div>
            </div>
        </div>
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700/50 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gradient-to-br from-cyan-500/20 to-cyan-600/20 rounded-lg shadow-lg shadow-cyan-500/0">
                    <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ $stats['total_deployments'] ?? 0 }}</p>
                    <p class="text-xs text-slate-400">Total Deployments</p>
                </div>
            </div>
        </div>
    </div>
</div>
