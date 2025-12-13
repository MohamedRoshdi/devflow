<div class="min-h-screen">
    {{-- Animated Background --}}
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-gradient-to-br from-green-500/5 via-emerald-500/5 to-teal-500/5 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-gradient-to-tr from-blue-500/5 via-indigo-500/5 to-purple-500/5 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-gradient-to-r from-emerald-500/3 to-cyan-500/3 rounded-full blur-3xl"></div>
    </div>

    {{-- Hero Header --}}
    <div class="relative mb-8">
        <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 rounded-3xl overflow-hidden border border-slate-700/50 shadow-2xl">
            {{-- Grid Pattern --}}
            <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23fff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>

            <div class="relative p-6 lg:p-8">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                    <div class="flex items-start gap-4">
                        {{-- Animated Logo --}}
                        <div class="relative">
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-green-400 via-emerald-500 to-teal-600 flex items-center justify-center shadow-xl shadow-emerald-500/30 transform hover:scale-105 transition-transform">
                                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-label="Projects folder icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                </svg>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <h1 class="text-2xl lg:text-3xl font-bold text-white tracking-tight">Projects Management</h1>
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                    {{ $projects->total() }} Projects
                                </span>
                            </div>
                            <p class="text-slate-400 text-sm">Manage and deploy your applications with ease</p>

                            {{-- Quick Stats Pills --}}
                            <div class="flex flex-wrap items-center gap-2 mt-3">
                                @php
                                    $runningCount = $projects->where('status', 'running')->count();
                                    $buildingCount = $projects->where('status', 'building')->count();
                                    $stoppedCount = $projects->where('status', 'stopped')->count();
                                @endphp
                                @if($runningCount > 0)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-500/20 text-emerald-400 border border-emerald-500/30" role="status" aria-label="{{ $runningCount }} projects running">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" aria-hidden="true"></span>
                                    {{ $runningCount }} Running
                                </span>
                                @endif
                                @if($buildingCount > 0)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-amber-500/20 text-amber-400 border border-amber-500/30" role="status" aria-label="{{ $buildingCount }} projects building">
                                    <svg class="w-3 h-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    {{ $buildingCount }} Building
                                </span>
                                @endif
                                @if($stoppedCount > 0)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-700/50 text-slate-400 border border-slate-600/30" role="status" aria-label="{{ $stoppedCount }} projects stopped">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400" aria-hidden="true"></span>
                                    {{ $stoppedCount }} Stopped
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('projects.create') }}"
                           class="group relative inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm text-white overflow-hidden transition-all duration-300 hover:-translate-y-0.5"
                           style="background: linear-gradient(135deg, #10b981 0%, #14b8a6 50%, #06b6d4 100%);"
                           aria-label="Create new project">
                            <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/25 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700" aria-hidden="true"></div>
                            <svg class="w-4 h-4 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span class="relative z-10">New Project</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Section --}}
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 shadow-xl mb-8 overflow-hidden">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="search-input" class="block text-sm font-medium text-slate-300 mb-2 flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Search Projects
                    </label>
                    <input wire:model.live="search"
                           id="search-input"
                           type="text"
                           placeholder="Type to search..."
                           class="w-full px-4 py-2.5 bg-slate-900/50 border border-slate-600/50 rounded-xl text-white placeholder-slate-500 focus:border-emerald-500/50 focus:ring-2 focus:ring-emerald-500/20 transition-all"
                           aria-label="Search projects by name">
                </div>
                <div>
                    <label for="server-filter" class="block text-sm font-medium text-slate-300 mb-2 flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                        </svg>
                        Server
                    </label>
                    <select wire:model.live="serverFilter"
                            id="server-filter"
                            class="w-full px-4 py-2.5 bg-slate-900/50 border border-slate-600/50 rounded-xl text-white focus:border-emerald-500/50 focus:ring-2 focus:ring-emerald-500/20 transition-all"
                            aria-label="Filter by server">
                        <option value="">All Servers</option>
                        @foreach($this->servers as $server)
                            <option value="{{ $server->id }}">{{ $server->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="status-filter" class="block text-sm font-medium text-slate-300 mb-2 flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Status
                    </label>
                    <select wire:model.live="statusFilter"
                            id="status-filter"
                            class="w-full px-4 py-2.5 bg-slate-900/50 border border-slate-600/50 rounded-xl text-white focus:border-emerald-500/50 focus:ring-2 focus:ring-emerald-500/20 transition-all"
                            aria-label="Filter by status">
                        <option value="">All Statuses</option>
                        <option value="running">Running</option>
                        <option value="stopped">Stopped</option>
                        <option value="building">Building</option>
                        <option value="error">Error</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Loading State --}}
    <div wire:loading.delay class="mb-8" role="status" aria-live="polite" aria-label="Loading projects">
        <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-4 backdrop-blur-sm">
            <div class="flex items-center gap-3">
                <svg class="animate-spin h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-blue-400 font-medium">Loading projects...</p>
            </div>
        </div>
    </div>

    {{-- Projects Grid --}}
    @if($projects->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8" wire:loading.remove>
            {{-- DevFlow Pro Self-Management Card --}}
            <a href="{{ route('projects.devflow') }}"
               class="group relative bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 rounded-2xl overflow-hidden border border-white/10 shadow-2xl hover:shadow-indigo-500/25 transform hover:-translate-y-1 hover:scale-[1.02] transition-all duration-300"
               role="article"
               aria-label="DevFlow Pro self-management console">
                {{-- Shimmer Effect --}}
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000" aria-hidden="true"></div>

                <div class="relative p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-white mb-1" id="devflow-card-title">DevFlow Pro</h3>
                            <p class="text-white/80 text-sm">Self-Management Console</p>
                        </div>
                        <div class="p-2.5 rounded-xl bg-white/20 backdrop-blur-sm" aria-hidden="true">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-label="Settings icon">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                    </div>

                    <div class="space-y-2.5 mb-4">
                        <div class="flex items-center text-sm text-white/90">
                            <div class="p-1.5 bg-white/20 rounded-lg mr-2" aria-hidden="true">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-label="Server icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                                </svg>
                            </div>
                            This Server
                        </div>
                        <div class="flex items-center text-sm text-white/90">
                            <div class="p-1.5 bg-white/20 rounded-lg mr-2" aria-hidden="true">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-label="Framework: Laravel">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                            </div>
                            Laravel {{ app()->version() }}
                        </div>
                        <div class="flex items-center text-sm text-white/90">
                            <div class="p-1.5 bg-white/20 rounded-lg mr-2" aria-hidden="true">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-label="Domain icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                </svg>
                            </div>
                            admin.nilestack.duckdns.org
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-white/20">
                        <span class="px-3 py-1.5 bg-emerald-500 rounded-full text-xs font-semibold text-white flex items-center shadow-lg" role="status" aria-label="Status: Live">
                            <span class="w-2 h-2 bg-white rounded-full mr-2 animate-pulse" aria-hidden="true"></span>
                            Live
                        </span>
                        <span class="text-white/90 text-sm font-medium group-hover:text-white transition-colors flex items-center gap-1">
                            Manage
                            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                    </div>
                </div>
            </a>

            {{-- Project Cards --}}
            @foreach($projects as $project)
                <div wire:key="project-{{ $project->id }}"
                     class="group relative bg-slate-800/50 backdrop-blur-sm rounded-2xl overflow-hidden border border-slate-700/50 shadow-xl hover:shadow-2xl hover:border-slate-600/50 transform hover:-translate-y-1 hover:scale-[1.02] transition-all duration-300 cursor-pointer"
                     onclick="window.location='{{ route('projects.show', $project) }}'"
                     role="article"
                     aria-labelledby="project-{{ $project->id }}-title">

                    {{-- Status Indicator Bar --}}
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r
                        @if($project->status === 'running') from-emerald-500 to-teal-500
                        @elseif($project->status === 'stopped') from-slate-500 to-slate-600
                        @elseif($project->status === 'building') from-amber-500 to-orange-500
                        @else from-red-500 to-rose-600
                        @endif"
                        role="presentation"
                        aria-hidden="true">
                    </div>

                    <div class="relative p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <h3 class="text-lg font-bold text-white truncate group-hover:text-emerald-400 transition-colors" id="project-{{ $project->id }}-title">
                                        {{ $project->name }}
                                    </h3>
                                    {{-- Status Badge --}}
                                    <span class="flex-shrink-0 px-2.5 py-1 rounded-lg text-xs font-semibold flex items-center
                                        @if($project->status === 'running') bg-emerald-500/20 text-emerald-400 border border-emerald-500/30
                                        @elseif($project->status === 'building') bg-amber-500/20 text-amber-400 border border-amber-500/30
                                        @elseif($project->status === 'stopped') bg-slate-600/20 text-slate-400 border border-slate-600/30
                                        @else bg-red-500/20 text-red-400 border border-red-500/30
                                        @endif"
                                        role="status"
                                        aria-label="Status: @if($project->status === 'running')Running@elseif($project->status === 'building')Building@elseif($project->status === 'stopped')Stopped@else{{ ucfirst($project->status ?? 'Unknown') }}@endif">
                                        @if($project->status === 'running')
                                            <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full mr-1.5 animate-pulse" aria-hidden="true"></span>
                                            Live
                                        @elseif($project->status === 'building')
                                            <svg class="w-3 h-3 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                            Building
                                        @elseif($project->status === 'stopped')
                                            <span class="w-1.5 h-1.5 bg-slate-400 rounded-full mr-1.5" aria-hidden="true"></span>
                                            Stopped
                                        @else
                                            <span class="w-1.5 h-1.5 bg-red-400 rounded-full mr-1.5" aria-hidden="true"></span>
                                            {{ ucfirst($project->status ?? 'Unknown') }}
                                        @endif
                                    </span>
                                </div>
                                <p class="text-sm text-slate-400 font-mono">{{ $project->slug }}</p>
                            </div>
                        </div>

                        {{-- Project Info --}}
                        <div class="space-y-2.5 mb-4">
                            <div class="flex items-center text-sm text-slate-300">
                                <div class="p-1.5 bg-blue-500/20 rounded-lg mr-2 flex-shrink-0" aria-hidden="true">
                                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-label="Server icon">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                                    </svg>
                                </div>
                                <span class="truncate">{{ $project->server->name ?? 'No server' }}</span>
                            </div>
                            <div class="flex items-center text-sm text-slate-300">
                                <div class="p-1.5 bg-purple-500/20 rounded-lg mr-2 flex-shrink-0" aria-hidden="true">
                                    <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-label="Framework: {{ ucfirst($project->framework ?? 'Unknown') }}">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                </div>
                                <span class="truncate">{{ ucfirst($project->framework ?? 'Unknown') }}</span>
                            </div>
                            @if($project->domains->count() > 0)
                                <div class="flex items-center text-sm text-slate-300">
                                    <div class="p-1.5 bg-emerald-500/20 rounded-lg mr-2 flex-shrink-0" aria-hidden="true">
                                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-label="Domain icon">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                        </svg>
                                    </div>
                                    <span class="truncate">{{ $project->domains->first()->domain }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- Live URL Badge --}}
                        @if($project->status === 'running')
                            @php
                                $primaryDomain = $project->domains->where('is_primary', true)->first();
                                if ($primaryDomain) {
                                    $protocol = $primaryDomain->ssl_enabled ? 'https://' : 'http://';
                                    $url = $protocol . $primaryDomain->domain;
                                } elseif ($project->port && $project->server) {
                                    $url = 'http://' . $project->server->ip_address . ':' . $project->port;
                                } else {
                                    $url = null;
                                }
                            @endphp
                            @if($url)
                            <div class="mb-3 p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-xl backdrop-blur-sm">
                                <p class="text-xs text-emerald-400 font-medium mb-1 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    Live URL
                                </p>
                                <a href="{{ $url }}" target="_blank"
                                   onclick="event.stopPropagation()"
                                   class="text-sm text-emerald-300 hover:text-emerald-200 font-mono break-all flex items-center gap-1 transition-colors"
                                   aria-label="Open {{ $project->name }} in new tab">
                                    {{ $url }}
                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                </a>
                            </div>
                            @endif
                        @endif

                        {{-- Footer --}}
                        <div class="flex justify-between items-center pt-4 border-t border-slate-700/50">
                            <span class="text-xs text-slate-500">
                                {{ $project->last_deployed_at ? $project->last_deployed_at->diffForHumans() : 'Never deployed' }}
                            </span>
                            <div class="flex items-center gap-3" onclick="event.stopPropagation()">
                                <a href="{{ route('projects.show', $project) }}"
                                   class="text-emerald-400 hover:text-emerald-300 text-sm font-medium transition-colors"
                                   aria-label="View {{ $project->name }} details">
                                    View
                                </a>
                                <button wire:click="deleteProject({{ $project->id }})"
                                        wire:confirm="Are you sure you want to delete '{{ $project->name }}'? This action cannot be undone and will remove all associated deployments, logs, and configurations."
                                        wire:loading.attr="disabled"
                                        class="text-red-400 hover:text-red-300 text-sm font-medium transition-colors disabled:opacity-50"
                                        aria-label="Delete {{ $project->name }}">
                                    <span wire:loading.remove wire:target="deleteProject({{ $project->id }})">Delete</span>
                                    <span wire:loading wire:target="deleteProject({{ $project->id }})">
                                        <svg class="inline w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Deleting...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 shadow-xl p-4" wire:loading.remove>
            {{ $projects->links() }}
        </div>
    @elseif($search || $serverFilter || $statusFilter)
        {{-- No Results State (filters applied) --}}
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 shadow-xl text-center py-16" wire:loading.remove role="status" aria-live="polite">
            <div class="relative inline-flex items-center justify-center w-20 h-20 mb-6">
                <div class="absolute inset-0 bg-gradient-to-br from-amber-500/20 to-orange-500/20 rounded-full blur-xl" aria-hidden="true"></div>
                <div class="relative p-4 bg-slate-700/50 rounded-2xl">
                    <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-label="No results found icon">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">No Projects Found</h3>
            <p class="text-slate-400 mb-6 max-w-md mx-auto">No projects match your current filters. Try adjusting your search criteria or clear the filters.</p>
            <button wire:click="$set('search', ''); $set('serverFilter', ''); $set('statusFilter', '')"
               class="group inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-sm text-white overflow-hidden transition-all duration-300 hover:-translate-y-0.5 bg-slate-700/50 backdrop-blur-sm border border-slate-600/50 hover:border-slate-500/50"
               aria-label="Clear all filters">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span>Clear All Filters</span>
            </button>
        </div>
    @else
        {{-- Empty State (no projects at all) --}}
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 shadow-xl text-center py-16" wire:loading.remove role="status" aria-live="polite">
            <div class="relative inline-flex items-center justify-center w-20 h-20 mb-6">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/20 to-teal-500/20 rounded-full blur-xl" aria-hidden="true"></div>
                <div class="relative p-4 bg-slate-700/50 rounded-2xl">
                    <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-label="Empty projects folder icon">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">No Projects Yet</h3>
            <p class="text-slate-400 mb-6 max-w-md mx-auto">Get started by creating your first project and deploy your applications with ease.</p>
            <a href="{{ route('projects.create') }}"
               class="group inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-sm text-white overflow-hidden transition-all duration-300 hover:-translate-y-0.5"
               style="background: linear-gradient(135deg, #10b981 0%, #14b8a6 50%, #06b6d4 100%);"
               aria-label="Create your first project">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/25 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700" aria-hidden="true"></div>
                <svg class="w-4 h-4 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span class="relative z-10">Create Your First Project</span>
            </a>
        </div>
    @endif
</div>
