<div>
<div class="relative min-h-screen">
    {{-- Animated Background Orbs --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-gradient-to-br from-teal-500/30 via-cyan-500/30 to-blue-500/30 rounded-full blur-3xl animate-float"></div>
        <div class="absolute bottom-1/4 right-1/3 w-80 h-80 bg-gradient-to-br from-cyan-500/20 via-teal-500/20 to-emerald-500/20 rounded-full blur-3xl animate-float-delayed"></div>
        <div class="absolute top-1/2 right-1/4 w-72 h-72 bg-gradient-to-br from-blue-500/25 via-cyan-500/25 to-teal-500/25 rounded-full blur-3xl animate-float-slow"></div>
    </div>

    <div class="relative">
        {{-- Glassmorphism Card Container --}}
        <div class="bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-2xl shadow-slate-900/20 dark:shadow-slate-900/60 border border-slate-200 dark:border-slate-700/50 overflow-hidden">
            {{-- Premium Gradient Header --}}
            <div class="relative bg-gradient-to-br from-teal-600 via-cyan-600 to-blue-600 px-8 py-10 overflow-hidden">
                {{-- Grid Pattern Overlay --}}
                <div class="absolute inset-0 bg-grid-pattern opacity-10"></div>
                <div class="absolute inset-0 bg-gradient-to-b from-transparent to-slate-900/20"></div>

                <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <a href="{{ route('regions.index') }}" class="group inline-flex items-center gap-1.5 text-white/70 hover:text-white text-sm font-medium transition-colors">
                                <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Back to Regions
                            </a>
                        </div>
                        <h1 class="text-4xl font-bold text-white flex items-center gap-3 drop-shadow-lg">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ $region->name }}
                        </h1>
                        <div class="flex flex-wrap items-center gap-3 mt-3">
                            <code class="px-3 py-1 rounded-lg font-mono text-sm bg-white/20 text-white backdrop-blur-sm border border-white/30">{{ $region->code }}</code>
                            <span class="text-white/80 text-sm">{{ $region->continent }}</span>
                            <span @class([
                                'inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold backdrop-blur-sm border',
                                'bg-emerald-500/20 text-emerald-100 border-emerald-400/30' => $region->status === \App\Enums\RegionStatus::Active,
                                'bg-amber-500/20 text-amber-100 border-amber-400/30' => $region->status === \App\Enums\RegionStatus::Degraded,
                                'bg-red-500/20 text-red-100 border-red-400/30' => $region->status === \App\Enums\RegionStatus::Offline,
                                'bg-blue-500/20 text-blue-100 border-blue-400/30' => $region->status === \App\Enums\RegionStatus::Maintenance,
                            ])>
                                @if($region->status === \App\Enums\RegionStatus::Active)
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 mr-1.5 animate-pulse"></span>
                                @endif
                                {{ $region->status->label() }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-8 space-y-8">
                {{-- Flash Messages --}}
                @if(session('message'))
                    <div class="p-4 rounded-xl border bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm font-medium">{{ session('message') }}</p>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="p-4 rounded-xl border bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm font-medium">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif

                {{-- Overview Stats Row --}}
                @php
                    $health = $this->healthScore;
                    $healthScore = $health['score'];
                    $healthColor = $healthScore > 80 ? 'emerald' : ($healthScore > 50 ? 'amber' : 'red');
                @endphp
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    {{-- Health Score --}}
                    <div class="bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm rounded-2xl border border-slate-200 dark:border-slate-700/50 p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Health Score</span>
                            <div class="w-8 h-8 rounded-lg bg-{{ $healthColor }}-100 dark:bg-{{ $healthColor }}-900/40 flex items-center justify-center">
                                <svg class="w-4 h-4 text-{{ $healthColor }}-600 dark:text-{{ $healthColor }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-3xl font-extrabold text-{{ $healthColor }}-600 dark:text-{{ $healthColor }}-400">{{ $healthScore }}%</p>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-1.5 mt-2 overflow-hidden">
                            <div class="bg-gradient-to-r from-{{ $healthColor }}-500 to-{{ $healthColor }}-400 h-1.5 rounded-full transition-all duration-1000"
                                 style="width: {{ $healthScore }}%"></div>
                        </div>
                    </div>

                    {{-- Total Servers --}}
                    <div class="bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm rounded-2xl border border-slate-200 dark:border-slate-700/50 p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Servers</span>
                            <div class="w-8 h-8 rounded-lg bg-cyan-100 dark:bg-cyan-900/40 flex items-center justify-center">
                                <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-3xl font-extrabold text-slate-900 dark:text-white">{{ $health['total_count'] }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">servers in region</p>
                    </div>

                    {{-- Online Servers --}}
                    <div class="bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm rounded-2xl border border-slate-200 dark:border-slate-700/50 p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Online Servers</span>
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center">
                                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400">{{ $health['online_count'] }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">responding to health checks</p>
                    </div>

                    {{-- Status --}}
                    <div class="bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm rounded-2xl border border-slate-200 dark:border-slate-700/50 p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Region Status</span>
                            <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                                <svg class="w-4 h-4 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <span @class([
                            'inline-flex items-center px-3 py-1.5 rounded-xl text-sm font-bold border',
                            $region->status->colorClass(),
                        ])>
                            @if($region->status === \App\Enums\RegionStatus::Active)
                                <span class="w-2 h-2 rounded-full bg-emerald-400 mr-2 animate-pulse"></span>
                            @endif
                            {{ $region->status->label() }}
                        </span>
                    </div>
                </div>

                {{-- Status Control --}}
                <div class="bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm rounded-2xl border border-slate-200 dark:border-slate-700/50 p-6">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Status Control
                    </h2>
                    <div class="flex flex-wrap items-center gap-3">
                        @foreach(\App\Enums\RegionStatus::cases() as $status)
                            @php
                                $isCurrent = $region->status === $status;
                                $isDestructive = in_array($status, [\App\Enums\RegionStatus::Offline, \App\Enums\RegionStatus::Maintenance]);
                            @endphp
                            <button wire:click="changeRegionStatus('{{ $status->value }}')"
                                    @if($isDestructive && !$isCurrent) wire:confirm="Are you sure you want to set this region to {{ $status->label() }}? This may affect active deployments." @endif
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-50 cursor-not-allowed"
                                    wire:target="changeRegionStatus('{{ $status->value }}')"
                                    @if($isCurrent) disabled @endif
                                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-300 border
                                        {{ $isCurrent
                                            ? 'bg-gradient-to-r from-teal-600 to-cyan-600 text-white border-teal-500 shadow-lg shadow-teal-500/20 cursor-default'
                                            : 'bg-white/50 dark:bg-slate-700/50 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700' }}">
                                @if($isCurrent)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                @endif
                                {{ $status->label() }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Servers Table --}}
                <div class="space-y-4">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-6 h-6 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                        </svg>
                        Servers
                    </h2>

                    @if($this->servers->isNotEmpty())
                        <div class="bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm border border-slate-200 dark:border-slate-700/50 rounded-2xl overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-200 dark:border-slate-700/50">
                                            <th class="text-left px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Name</th>
                                            <th class="text-left px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">IP Address</th>
                                            <th class="text-left px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</th>
                                            <th class="text-left px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">CPU</th>
                                            <th class="text-left px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">RAM</th>
                                            <th class="text-left px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Last Ping</th>
                                            <th class="text-left px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
                                        @foreach($this->servers as $server)
                                            <tr wire:key="server-{{ $server->id }}" class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                                                <td class="px-6 py-4">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-8 h-8 rounded-lg bg-cyan-100 dark:bg-cyan-900/40 flex items-center justify-center flex-shrink-0">
                                                            <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                                            </svg>
                                                        </div>
                                                        <span class="font-bold text-slate-900 dark:text-white">{{ $server->name }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <code class="px-2 py-1 bg-slate-100/80 dark:bg-slate-900/80 rounded-lg text-slate-600 dark:text-slate-300 font-mono text-xs border border-slate-200 dark:border-slate-700">{{ $server->ip_address }}</code>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                                        @if($server->status === 'online') bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300
                                                        @elseif($server->status === 'offline') bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300
                                                        @elseif($server->status === 'provisioning') bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300
                                                        @else bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300
                                                        @endif">
                                                        @if($server->status === 'online')
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                                                        @endif
                                                        {{ ucfirst($server->status) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-slate-700 dark:text-slate-300 font-medium">
                                                    {{ $server->cpu_cores }} cores
                                                </td>
                                                <td class="px-6 py-4 text-slate-700 dark:text-slate-300 font-medium">
                                                    {{ $server->memory_gb }} GB
                                                </td>
                                                <td class="px-6 py-4 text-slate-500 dark:text-slate-400 text-xs">
                                                    {{ $server->last_ping_at ? $server->last_ping_at->diffForHumans() : 'Never' }}
                                                </td>
                                                <td class="px-6 py-4">
                                                    <button wire:click="removeServer({{ $server->id }})"
                                                            wire:confirm="Are you sure you want to remove this server from the region?"
                                                            wire:loading.attr="disabled"
                                                            wire:loading.class="opacity-50 cursor-not-allowed"
                                                            wire:target="removeServer({{ $server->id }})"
                                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border transition-all
                                                                border-red-200 dark:border-red-800/50 text-red-600 dark:text-red-400
                                                                hover:bg-red-50 dark:hover:bg-red-900/20">
                                                        <svg wire:loading.remove wire:target="removeServer({{ $server->id }})" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                        <svg wire:loading wire:target="removeServer({{ $server->id }})" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Remove
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-12 bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm rounded-2xl border border-slate-200 dark:border-slate-700/50">
                            <div class="relative inline-block">
                                <svg class="mx-auto h-16 w-16 text-slate-400 dark:text-slate-600 drop-shadow-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                </svg>
                                <div class="absolute inset-0 blur-2xl bg-cyan-500/20 rounded-full"></div>
                            </div>
                            <p class="mt-4 text-slate-700 dark:text-slate-300 text-lg font-semibold">No servers in this region</p>
                            <p class="text-sm text-slate-500 mt-2">Add servers to this region from the server management page.</p>
                        </div>
                    @endif
                </div>

                {{-- Recent Deployments --}}
                <div class="space-y-4">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-6 h-6 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Recent Deployments
                    </h2>

                    @if($this->recentDeployments->isNotEmpty())
                        <div class="bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm border border-slate-200 dark:border-slate-700/50 rounded-2xl overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-200 dark:border-slate-700/50">
                                            <th class="text-left px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Date</th>
                                            <th class="text-left px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Project</th>
                                            <th class="text-left px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Strategy</th>
                                            <th class="text-left px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</th>
                                            <th class="text-left px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Initiator</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
                                        @foreach($this->recentDeployments as $deployment)
                                            <tr wire:key="deployment-{{ $deployment->id }}" class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                                                <td class="px-6 py-4 text-slate-500 dark:text-slate-400">
                                                    {{ $deployment->created_at->format('M d, H:i') }}
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="font-bold text-slate-900 dark:text-white">{{ $deployment->project?->name ?? 'Unknown' }}</span>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider bg-slate-100/50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600/50 text-slate-600 dark:text-slate-300">
                                                        {{ ucfirst($deployment->strategy) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                                        @if($deployment->status === 'completed') bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300
                                                        @elseif($deployment->status === 'failed') bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300
                                                        @elseif($deployment->status === 'running') bg-cyan-100 dark:bg-cyan-900/40 text-cyan-700 dark:text-cyan-300
                                                        @elseif($deployment->status === 'rolled_back') bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300
                                                        @else bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300
                                                        @endif">
                                                        {{ ucfirst(str_replace('_', ' ', $deployment->status)) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-slate-700 dark:text-slate-300">
                                                    {{ $deployment->initiator?->name ?? 'System' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-12 bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm rounded-2xl border border-slate-200 dark:border-slate-700/50">
                            <svg class="mx-auto h-12 w-12 text-slate-400 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="mt-4 text-slate-700 dark:text-slate-300 font-semibold">No deployments yet</p>
                            <p class="text-sm text-slate-500 mt-2">Deployments targeting this region will appear here.</p>
                        </div>
                    @endif
                </div>

                {{-- Region Info --}}
                <div class="bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm rounded-2xl border border-slate-200 dark:border-slate-700/50 p-6">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Region Details
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="space-y-1">
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Coordinates</p>
                            <p class="text-sm font-mono text-slate-900 dark:text-white">
                                @if($region->latitude && $region->longitude)
                                    {{ $region->latitude }}, {{ $region->longitude }}
                                @else
                                    <span class="text-slate-400 italic">Not set</span>
                                @endif
                            </p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">DNS Zone</p>
                            <p class="text-sm font-mono text-slate-900 dark:text-white">
                                @if($region->dns_zone)
                                    {{ $region->dns_zone }}
                                @else
                                    <span class="text-slate-400 italic">Not configured</span>
                                @endif
                            </p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Region Code</p>
                            <code class="text-sm px-2 py-1 rounded-lg font-mono bg-slate-100 dark:bg-slate-700 text-teal-600 dark:text-teal-300 border border-teal-500/20">{{ $region->code }}</code>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Created</p>
                            <p class="text-sm text-slate-900 dark:text-white">{{ $region->created_at->format('M d, Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes float {
        0%, 100% {
            transform: translate(0, 0) scale(1);
            opacity: 0.3;
        }
        50% {
            transform: translate(30px, -30px) scale(1.1);
            opacity: 0.4;
        }
    }

    @keyframes float-delayed {
        0%, 100% {
            transform: translate(0, 0) scale(1);
            opacity: 0.2;
        }
        50% {
            transform: translate(-40px, 40px) scale(1.15);
            opacity: 0.3;
        }
    }

    @keyframes float-slow {
        0%, 100% {
            transform: translate(0, 0) scale(1);
            opacity: 0.25;
        }
        50% {
            transform: translate(20px, 50px) scale(1.08);
            opacity: 0.35;
        }
    }

    .animate-float {
        animation: float 20s ease-in-out infinite;
    }

    .animate-float-delayed {
        animation: float-delayed 25s ease-in-out infinite;
    }

    .animate-float-slow {
        animation: float-slow 30s ease-in-out infinite;
    }

    .bg-grid-pattern {
        background-image:
            linear-gradient(to right, rgba(255, 255, 255, 0.1) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
        background-size: 24px 24px;
    }
</style>
</div>
