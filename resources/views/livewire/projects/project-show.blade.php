@php
    use Illuminate\Support\Str;

    $isUpdatePending = $updateStatus && !(($updateStatus['up_to_date'] ?? true));
@endphp

<div wire:key="project-show-{{ $project->id }}"
     wire:init="preloadUpdateStatus"
     class="min-h-screen">

    {{-- Animated Background Orbs --}}
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-gradient-to-br from-emerald-500/5 via-teal-500/5 to-cyan-500/5 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-gradient-to-tr from-violet-500/5 via-purple-500/5 to-fuchsia-500/5 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-gradient-to-r from-blue-500/3 to-indigo-500/3 rounded-full blur-3xl"></div>
    </div>

    {{-- Active Deployment Banner --}}
    @if($project->activeDeployment)
        <div class="mb-6 animate-fade-in">
            <a href="{{ route('deployments.show', $project->activeDeployment) }}"
               wire:navigate
               class="block bg-gradient-to-r from-blue-500/20 via-indigo-500/20 to-purple-500/20 backdrop-blur-sm rounded-2xl border border-blue-500/30 shadow-lg shadow-blue-500/10 overflow-hidden hover:shadow-xl hover:shadow-blue-500/20 transition-all duration-300 group">
                <div class="p-6">
                    <div class="flex items-center justify-between gap-6">
                        <div class="flex items-center gap-4">
                            {{-- Animated Deployment Icon --}}
                            <div class="relative">
                                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/30">
                                    <svg class="w-7 h-7 text-white animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </div>
                                {{-- Pulsing Ring --}}
                                <div class="absolute inset-0 rounded-xl bg-blue-500 animate-ping opacity-20"></div>
                            </div>

                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-1">
                                    <h3 class="text-lg font-bold text-white">Deployment in Progress</h3>
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold uppercase tracking-wider
                                        @if($project->activeDeployment->status === 'running')
                                            bg-blue-500/20 text-blue-400 border border-blue-500/30 animate-pulse
                                        @else
                                            bg-amber-500/20 text-amber-400 border border-amber-500/30
                                        @endif">
                                        {{ ucfirst($project->activeDeployment->status) }}
                                    </span>
                                </div>
                                <p class="text-slate-300 text-sm">
                                    @if($project->activeDeployment->status === 'running')
                                        Deployment is currently running. Click to view live progress.
                                    @else
                                        Deployment is queued and will start shortly.
                                    @endif
                                </p>
                                <div class="flex items-center gap-4 mt-2 text-xs text-slate-400">
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Started {{ $project->activeDeployment->started_at?->diffForHumans() ?? 'just now' }}
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                                        </svg>
                                        Branch: {{ $project->activeDeployment->branch }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- View Button --}}
                        <div class="flex-shrink-0">
                            <div class="px-5 py-2.5 bg-white/5 hover:bg-white/10 backdrop-blur-sm rounded-xl border border-white/10 text-white font-medium transition-all duration-200 group-hover:border-white/20 flex items-center gap-2">
                                <span>View Progress</span>
                                <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    @endif

    {{-- Hero Section with Premium Styling --}}
    <div class="relative mb-8">
        <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 rounded-3xl overflow-hidden border border-slate-700/50 shadow-2xl">
            {{-- Grid Pattern Overlay --}}
            <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23fff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>

            <div class="relative p-6 lg:p-8">
                {{-- Top Row: Logo, Title, Actions --}}
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                    <div class="flex items-start gap-4">
                        {{-- Animated Project Icon --}}
                        <div class="relative">
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-400 via-indigo-500 to-purple-600 flex items-center justify-center shadow-xl shadow-blue-500/30 transform hover:scale-105 transition-transform">
                                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                </svg>
                            </div>
                            {{-- Status Indicator --}}
                            <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full border-2 border-slate-900 flex items-center justify-center
                                @if($project->status === 'running') bg-emerald-500
                                @elseif($project->status === 'building') bg-amber-500
                                @elseif($project->status === 'stopped') bg-slate-500
                                @elseif($project->status === 'failed' || $project->status === 'error') bg-red-500
                                @else bg-blue-500
                                @endif">
                                @if($project->status === 'running')
                                    <span class="w-2 h-2 rounded-full bg-white animate-ping"></span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <h1 class="text-2xl lg:text-3xl font-bold text-white tracking-tight">{{ $project->name }}</h1>
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider
                                    @if($project->status === 'running') bg-emerald-500/20 text-emerald-400 border border-emerald-500/30
                                    @elseif($project->status === 'building') bg-amber-500/20 text-amber-400 border border-amber-500/30
                                    @elseif($project->status === 'stopped') bg-slate-500/20 text-slate-400 border border-slate-500/30
                                    @elseif($project->status === 'failed' || $project->status === 'error') bg-red-500/20 text-red-400 border border-red-500/30
                                    @else bg-blue-500/20 text-blue-400 border border-blue-500/30
                                    @endif">
                                    @if($project->status === 'running') Live
                                    @elseif($project->status === 'building') Building
                                    @elseif($project->status === 'stopped') Stopped
                                    @elseif($project->status === 'failed' || $project->status === 'error') Failed
                                    @else {{ ucfirst($project->status) }}
                                    @endif
                                </span>
                            </div>
                            <p class="text-slate-400 text-sm">{{ $project->framework ?? 'Unknown Stack' }} Project</p>

                            {{-- Info Pills --}}
                            <div class="flex flex-wrap items-center gap-2 mt-3">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-800/80 text-slate-300 border border-slate-700/50">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                    {{ $project->slug }}
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-800/80 text-slate-300 border border-slate-700/50">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                                    </svg>
                                    {{ $project->server->name ?? 'No Server' }}
                                </span>
                                @if($project->php_version)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-800/80 text-slate-300 border border-slate-700/50">
                                        <svg class="w-3.5 h-3.5 text-indigo-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2z"/></svg>
                                        PHP {{ $project->php_version }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Quick Action Buttons --}}
                    <div class="flex flex-wrap gap-2">
                        @if($project->status === 'running')
                            {{-- View Live Button --}}
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
                                <a href="{{ $url }}" target="_blank"
                                    class="group relative inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm text-white overflow-hidden transition-all duration-300 hover:-translate-y-0.5"
                                    style="background: linear-gradient(135deg, #10b981 0%, #14b8a6 50%, #06b6d4 100%);">
                                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/25 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                                    <svg class="w-4 h-4 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                    <span class="relative z-10">View Live</span>
                                </a>
                            @endif

                            <button wire:click="stopProject" wire:confirm="Stop this project?"
                                wire:loading.attr="disabled"
                                wire:target="stopProject"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm bg-red-500/20 text-red-400 border border-red-500/30 hover:bg-red-500/30 transition-all disabled:opacity-50">
                                <div wire:loading.remove wire:target="stopProject" class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h6v6H9z"/>
                                    </svg>
                                    Stop
                                </div>
                                <div wire:loading wire:target="stopProject" class="flex items-center gap-2">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Stopping...
                                </div>
                            </button>
                        @else
                            <button wire:click="startProject"
                                wire:loading.attr="disabled"
                                wire:target="startProject"
                                class="group relative inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm text-white overflow-hidden transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed hover:-translate-y-0.5"
                                style="background: linear-gradient(135deg, #10b981 0%, #14b8a6 50%, #06b6d4 100%);">
                                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/25 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                                <div wire:loading.remove wire:target="startProject" class="flex items-center gap-2 relative z-10">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    </svg>
                                    Start Project
                                </div>
                                <div wire:loading wire:target="startProject" class="flex items-center gap-2 relative z-10">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Starting...
                                </div>
                            </button>
                        @endif

                        <button wire:click="$set('showDeployModal', true)"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm bg-slate-800/80 text-slate-300 border border-slate-700/50 hover:bg-slate-700/80 hover:text-white transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            Deploy
                        </button>

                        <a href="{{ route('projects.edit', $project) }}"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm bg-slate-800/80 text-slate-300 border border-slate-700/50 hover:bg-slate-700/80 hover:text-white transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Configure
                        </a>
                    </div>
                </div>

                {{-- Branch Info Bar with Switch Button --}}
                <div class="mt-6 flex items-center gap-4 p-4 rounded-2xl bg-slate-800/50 border border-slate-700/30 backdrop-blur-sm">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-700 to-slate-800 flex items-center justify-center border border-slate-600/50">
                        <svg class="w-5 h-5 text-slate-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-slate-500 font-medium">Branch:</span>
                            <span class="px-2.5 py-1 rounded-md bg-purple-500/20 text-purple-400 font-mono text-sm border border-purple-500/30">{{ $project->branch }}</span>
                            @if($isUpdatePending)
                                <span class="flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-amber-500/20 text-amber-400 text-xs border border-amber-500/30">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                    {{ $updateStatus['commits_behind'] }} updates
                                </span>
                            @endif
                        </div>
                    </div>
                    <button wire:click="$set('showBranchSelector', true)"
                        class="px-4 py-2 rounded-lg text-sm font-medium bg-purple-500/20 text-purple-400 border border-purple-500/30 hover:bg-purple-500/30 transition-all">
                        <svg class="w-4 h-4 inline-block mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v4a1 1 0 001 1h3v8l7-12h3a1 1 0 001-1V7a1 1 0 00-1-1H4a1 1 0 00-1 1z"/>
                        </svg>
                        Switch Branch
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Alerts --}}
    @if (session()->has('message'))
        <div class="mb-6 bg-emerald-50 dark:bg-emerald-900/30 border-l-4 border-emerald-500 text-emerald-800 dark:text-emerald-400 px-6 py-4 rounded-r-lg shadow">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('message') }}
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 text-red-800 dark:text-red-400 px-6 py-4 rounded-r-lg shadow">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                {{ session('error') }}
            </div>
        </div>
    @endif

    {{-- Git Update Alert --}}
    @if($checkingForUpdates && !$updateStatusLoaded)
        <div class="mb-8 rounded-2xl border border-blue-700/50 bg-slate-800/50 backdrop-blur-sm shadow-xl">
            <div class="flex items-center justify-between gap-6 p-6">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-blue-500/90 text-white flex items-center justify-center animate-pulse">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-white">Checking repository status...</h3>
                        <p class="text-sm text-slate-400">Fetching latest commits from origin/{{ $project->branch }}.</p>
                    </div>
                </div>
                <div class="text-xs text-blue-400 uppercase tracking-wide">Please wait</div>
            </div>
        </div>
    @elseif($updateStatus && !$updateStatus['up_to_date'])
        <div class="mb-8 rounded-2xl border border-amber-700/50 bg-gradient-to-r from-amber-900/20 to-orange-900/20 backdrop-blur-sm shadow-xl">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 p-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-full bg-amber-500 text-white flex items-center justify-center shadow-lg animate-bounce">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <h3 class="text-xl font-bold text-white">{{ $updateStatus['commits_behind'] }} new {{ Str::plural('commit', $updateStatus['commits_behind']) }} ready for deployment</h3>
                            <p class="text-sm text-slate-300">Pull in the latest changes from <span class="font-semibold text-amber-400">origin/{{ $project->branch }}</span> to keep production current.</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                            <div class="flex items-center gap-3 bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3">
                                <div class="text-xs font-semibold uppercase text-slate-400">Current</div>
                                <code class="font-mono text-sm text-white">{{ $updateStatus['local_commit'] }}</code>
                            </div>
                            <div class="flex items-center gap-3 bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3">
                                <div class="text-xs font-semibold uppercase text-emerald-400">Latest</div>
                                <code class="font-mono text-sm text-emerald-300">{{ $updateStatus['remote_commit'] }}</code>
                            </div>
                        </div>
                        @if(isset($updateStatus['remote_meta']['message']))
                            <p class="text-xs text-amber-300 italic">Latest change: "{{ Str::limit($updateStatus['remote_meta']['message'], 120) }}"</p>
                        @endif
                    </div>
                </div>
                <div class="flex flex-col items-stretch gap-3">
                    <button wire:click="$set('showDeployModal', true)"
                        class="group relative inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-semibold text-sm text-white overflow-hidden transition-all duration-300 hover:-translate-y-0.5"
                        style="background: linear-gradient(135deg, #f59e0b 0%, #f97316 50%, #ef4444 100%);">
                        <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/25 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                        <svg class="w-5 h-5 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span class="relative z-10">Deploy Latest Changes</span>
                    </button>
                    <div class="text-xs text-amber-300 text-center">Automated Laravel optimizations run post-deploy.</div>
                </div>
            </div>
        </div>
    @endif

    {{-- Quick Stats Cards with Glassmorphism --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="group bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 p-6 hover:border-blue-500/50 transition-all hover:shadow-lg hover:shadow-blue-500/10">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-white">Deployments</h3>
            </div>
            <p class="text-3xl font-bold text-white">{{ $project->deployments()->count() }}</p>
            <p class="text-xs text-slate-400 mt-1">Total deployments</p>
        </div>

        <div class="group bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 p-6 hover:border-purple-500/50 transition-all hover:shadow-lg hover:shadow-purple-500/10">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-white">Domains</h3>
            </div>
            <p class="text-3xl font-bold text-white">{{ $project->domains->count() }}</p>
            <p class="text-xs text-slate-400 mt-1">Configured domains</p>
        </div>

        <div class="group bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 p-6 hover:border-emerald-500/50 transition-all hover:shadow-lg hover:shadow-emerald-500/10">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-white">Storage</h3>
            </div>
            <p class="text-3xl font-bold text-white">{{ number_format($project->storage_used_mb / 1024, 1) }}GB</p>
            <p class="text-xs text-slate-400 mt-1">Disk usage</p>
        </div>

        <div class="group bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 p-6 hover:border-amber-500/50 transition-all hover:shadow-lg hover:shadow-amber-500/10">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-white">Last Deploy</h3>
            </div>
            <p class="text-lg font-bold text-white">{{ $project->last_deployed_at ? $project->last_deployed_at->diffForHumans() : 'Never' }}</p>
            <p class="text-xs text-slate-400 mt-1">Deployment time</p>
        </div>
    </div>

    {{-- Premium Tab Navigation with Unique Colors --}}
    <div class="mb-6">
        <div class="flex items-center gap-2 p-1.5 bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700/50 w-fit">
            <button wire:click="setActiveTab('overview')"
                wire:loading.class="opacity-50"
                class="px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-300 flex items-center gap-2 {{ $activeTab === 'overview' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-700/50' }}">
                <svg wire:loading.remove wire:target="setActiveTab('overview')" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <svg wire:loading wire:target="setActiveTab('overview')" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Overview
            </button>

            <button wire:click="setActiveTab('docker')"
                wire:loading.class="opacity-50"
                class="px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-300 flex items-center gap-2 {{ $activeTab === 'docker' ? 'bg-gradient-to-r from-cyan-600 to-blue-600 text-white shadow-lg shadow-cyan-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-700/50' }}">
                <svg wire:loading.remove wire:target="setActiveTab('docker')" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <svg wire:loading wire:target="setActiveTab('docker')" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Docker
            </button>

            <button wire:click="setActiveTab('environment')"
                wire:loading.class="opacity-50"
                class="px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-300 flex items-center gap-2 {{ $activeTab === 'environment' ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-lg shadow-emerald-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-700/50' }}">
                <svg wire:loading.remove wire:target="setActiveTab('environment')" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <svg wire:loading wire:target="setActiveTab('environment')" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Environment
            </button>

            <button wire:click="setActiveTab('git')"
                wire:loading.class="opacity-50"
                class="px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-300 flex items-center gap-2 {{ $activeTab === 'git' ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-lg shadow-purple-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-700/50' }}">
                <svg wire:loading.remove wire:target="setActiveTab('git')" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                </svg>
                <svg wire:loading wire:target="setActiveTab('git')" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Git & Commits
                @if($project->status === 'running' && $isUpdatePending)
                    <span class="w-2 h-2 bg-amber-400 rounded-full animate-pulse"></span>
                @endif
            </button>

            <button wire:click="setActiveTab('logs')"
                wire:loading.class="opacity-50"
                class="px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-300 flex items-center gap-2 {{ $activeTab === 'logs' ? 'bg-gradient-to-r from-amber-600 to-orange-600 text-white shadow-lg shadow-amber-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-700/50' }}">
                <svg wire:loading.remove wire:target="setActiveTab('logs')" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <svg wire:loading wire:target="setActiveTab('logs')" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Logs
            </button>

            <button wire:click="setActiveTab('deployments')"
                wire:loading.class="opacity-50"
                class="px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-300 flex items-center gap-2 {{ $activeTab === 'deployments' ? 'bg-gradient-to-r from-rose-600 to-red-600 text-white shadow-lg shadow-rose-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-700/50' }}">
                <svg wire:loading.remove wire:target="setActiveTab('deployments')" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <svg wire:loading wire:target="setActiveTab('deployments')" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Deployments
            </button>

            <button wire:click="setActiveTab('webhooks')"
                wire:loading.class="opacity-50"
                class="px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-300 flex items-center gap-2 {{ $activeTab === 'webhooks' ? 'bg-gradient-to-r from-violet-600 to-indigo-600 text-white shadow-lg shadow-violet-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-700/50' }}">
                <svg wire:loading.remove wire:target="setActiveTab('webhooks')" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <svg wire:loading wire:target="setActiveTab('webhooks')" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Webhooks
            </button>
        </div>
    </div>

    {{-- Tab Content --}}
    <div class="relative">
        {{-- Overview Tab --}}
        @if($activeTab === 'overview')
        <div class="space-y-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Project Details Card --}}
                <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-6 border-b border-blue-500/30">
                        <h2 class="text-xl font-bold text-white flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Project Details
                        </h2>
                    </div>
                    <div class="p-6 space-y-3">
                        <div class="flex items-center justify-between py-3 border-b border-slate-700/50">
                            <span class="text-slate-400 flex items-center gap-2 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                                </svg>
                                Server
                            </span>
                            <span class="font-semibold text-white">{{ $project->server->name ?? 'None' }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-slate-700/50">
                            <span class="text-slate-400 flex items-center gap-2 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                                </svg>
                                Framework
                            </span>
                            <span class="font-semibold text-white">{{ $project->framework ?? 'Unknown' }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-slate-700/50">
                            <span class="text-slate-400 flex items-center gap-2 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                PHP Version
                            </span>
                            <span class="font-semibold text-white">{{ $project->php_version ?? 'N/A' }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-slate-700/50">
                            <span class="text-slate-400 flex items-center gap-2 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                Node Version
                            </span>
                            <span class="font-semibold text-white">{{ $project->node_version ?? 'N/A' }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-slate-700/50">
                            <span class="text-slate-400 flex items-center gap-2 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Created
                            </span>
                            <span class="font-semibold text-white">{{ $project->created_at->format('M d, Y') }}</span>
                        </div>
                        @if($project->environment)
                            <div class="flex items-center justify-between py-3">
                                <span class="text-slate-400 flex items-center gap-2 text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 16v-2m8-6h2M4 12H2m15.364-7.364l1.414-1.414M6.343 17.657l-1.414 1.414m0-13.657L6.343 6.343m11.314 11.314l1.414 1.414"/>
                                    </svg>
                                    Environment
                                </span>
                                <span class="font-semibold text-white">{{ ucfirst($project->environment) }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Repository Info Card --}}
                @if($project->repository_url)
                <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-600 to-pink-600 p-6 border-b border-purple-500/30">
                        <h2 class="text-xl font-bold text-white flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                            </svg>
                            Repository
                        </h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-2">Repository URL</label>
                            <div class="p-3 rounded-xl bg-slate-900/50 border border-slate-700">
                                <p class="text-sm text-purple-400 font-mono break-all">{{ $project->repository_url }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-2">Current Branch</label>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-2 rounded-xl bg-purple-500/20 text-purple-400 font-mono text-sm border border-purple-500/30 flex-1">
                                    {{ $project->branch }}
                                </span>
                                <button wire:click="$set('showBranchSelector', true)"
                                    class="px-4 py-2 rounded-xl text-sm font-medium bg-purple-500/20 text-purple-400 border border-purple-500/30 hover:bg-purple-500/30 transition-all">
                                    Switch
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Domains Card --}}
            <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-600 to-pink-600 p-6 border-b border-purple-500/30">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-white flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                            </svg>
                            Domains
                        </h2>
                        <button wire:click="$dispatch('open-add-domain-modal')"
                            class="px-4 py-2 rounded-lg text-sm font-medium bg-white/20 hover:bg-white/30 text-white transition-all">
                            + Add
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    @if($project->domains && $project->domains->count() > 0)
                        <div class="space-y-3">
                            @foreach($project->domains as $domain)
                                @php
                                    $isIpPort = str_contains($domain->domain, ':');
                                    $protocol = $domain->ssl_enabled ? 'https' : 'http';
                                    $url = $protocol . '://' . $domain->domain;
                                @endphp
                                <div class="p-4 bg-slate-900/50 rounded-xl border border-slate-700/30 hover:bg-slate-900/70 hover:border-purple-500/30 transition-all group">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <a href="{{ $url }}" target="_blank"
                                                class="font-semibold text-purple-400 hover:text-purple-300 hover:underline flex items-center gap-2 group-hover:gap-3 transition-all">
                                                {{ $domain->domain }}
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                </svg>
                                            </a>
                                            <div class="flex items-center flex-wrap gap-2 mt-2">
                                                @if($isIpPort)
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-purple-500/20 text-purple-400 border border-purple-500/30">
                                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3.293 1.293a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414L7.586 10 5.293 7.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                        </svg>
                                                        Direct Access
                                                    </span>
                                                @elseif($domain->ssl_enabled)
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                                        </svg>
                                                        SSL Active
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-700/50 text-slate-400 border border-slate-600/50">
                                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd"/>
                                                        </svg>
                                                        No SSL
                                                    </span>
                                                @endif
                                                @if($domain->is_primary)
                                                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-500/20 text-blue-400 border border-blue-500/30">
                                                        PRIMARY
                                                    </span>
                                                @endif
                                                <span class="text-xs font-medium
                                                    @if($domain->status === 'active') text-emerald-400
                                                    @elseif($domain->status === 'pending') text-amber-400
                                                    @elseif($domain->status === 'failed' || $domain->status === 'expired') text-red-400
                                                    @else text-slate-400
                                                    @endif">
                                                    {{ ucfirst($domain->status) }}
                                                </span>
                                            </div>
                                        </div>
                                        <a href="{{ $url }}" target="_blank"
                                            class="p-2 text-slate-400 hover:text-purple-400 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-16 w-16 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                            </svg>
                            <p class="mt-4 text-slate-400">No domains configured</p>
                            <button wire:click="$dispatch('open-add-domain-modal')"
                                class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-purple-500/20 text-purple-400 border border-purple-500/30 hover:bg-purple-500/30 transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add First Domain
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Docker Tab --}}
        @if($activeTab === 'docker')
            @livewire('projects.project-docker-management', ['project' => $project], key('docker-' . $project->id))
        @endif

        {{-- Environment Tab --}}
        @if($activeTab === 'environment')
            @livewire('projects.project-environment', ['project' => $project], key('env-' . $project->id))
        @endif

        {{-- Git Tab --}}
        @if($activeTab === 'git')
            @if(strtolower($project->slug) === 'devflow-pro' || str_contains(strtolower($project->name), 'devflow'))
                {{-- DevFlow Pro Self-Management Console --}}
                @livewire('projects.dev-flow-self-management', ['project' => $project], key('git-' . $project->id))
            @else
                {{-- Standard Git & Commits View for Other Projects --}}
                @livewire('projects.project-git', ['project' => $project], key('git-' . $project->id))
            @endif
        @endif

        {{-- Logs Tab --}}
        @if($activeTab === 'logs')
            <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 p-8">
                <p class="text-slate-400 text-center">Logs viewer coming soon...</p>
            </div>
        @endif

        {{-- Deployments Tab --}}
        @if($activeTab === 'deployments')
            @livewire('deployments.deployment-list', ['project' => $project], key('deployments-' . $project->id))
        @endif

        {{-- Webhooks Tab --}}
        @if($activeTab === 'webhooks')
            @livewire('projects.project-webhook-settings', ['project' => $project], key('webhooks-' . $project->id))
        @endif
    </div>

    {{-- Branch Selector Modal with Enhanced Styling --}}
    @if($showBranchSelector)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-transition>
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="$set('showBranchSelector', false)"></div>
                <div class="relative bg-slate-900 rounded-2xl border border-slate-700/50 shadow-2xl w-full max-w-lg overflow-hidden">
                    {{-- Modal Header --}}
                    <div class="p-6 border-b border-slate-700/50 bg-gradient-to-r from-purple-900/30 to-pink-900/30">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v4a1 1 0 001 1h3v8l7-12h3a1 1 0 001-1V7a1 1 0 00-1-1H4a1 1 0 00-1 1z"/>
                                </svg>
                                Switch Git Branch
                            </h3>
                            <button wire:click="$set('showBranchSelector', false)" class="text-slate-400 hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <p class="text-sm text-slate-400 mt-2">Select a branch to deploy. This will update your project to use the selected branch.</p>
                    </div>

                    {{-- Modal Content --}}
                    <div class="p-6">
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            @forelse($availableBranches ?? [] as $branch)
                                <button wire:click="selectBranch('{{ $branch }}')"
                                    class="w-full flex items-center justify-between p-4 rounded-xl transition-all
                                        {{ $project->branch === $branch
                                            ? 'bg-purple-500/20 border-2 border-purple-500/50 text-purple-300'
                                            : 'bg-slate-800/50 border border-slate-700/50 text-slate-300 hover:bg-slate-700/50 hover:border-slate-600' }}">
                                    <div class="flex items-center gap-3">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v4a1 1 0 001 1h3v8l7-12h3a1 1 0 001-1V7a1 1 0 00-1-1H4a1 1 0 00-1 1z"/>
                                        </svg>
                                        <span class="font-mono font-medium">{{ $branch }}</span>
                                    </div>
                                    @if($project->branch === $branch)
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-purple-500/30 text-purple-300 border border-purple-500/50">
                                            CURRENT
                                        </span>
                                    @endif
                                </button>
                            @empty
                                <div class="text-center py-12 text-slate-500">
                                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v4a1 1 0 001 1h3v8l7-12h3a1 1 0 001-1V7a1 1 0 00-1-1H4a1 1 0 00-1 1z"/>
                                    </svg>
                                    <p class="text-sm">No branches available</p>
                                    <p class="text-xs text-slate-600 mt-1">Configure repository access to see branches</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Branch Switch Confirmation Modal --}}
    @if($showBranchConfirmModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-transition>
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="$set('showBranchConfirmModal', false)"></div>
                <div class="relative bg-slate-900 rounded-2xl border border-slate-700/50 shadow-2xl w-full max-w-md overflow-hidden">
                    <div class="p-6 border-b border-slate-700/50 bg-gradient-to-r from-amber-900/30 to-orange-900/30">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center border border-amber-500/30">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white">Confirm Branch Switch</h3>
                                <p class="text-sm text-slate-400">This action will update your deployment</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 space-y-4">
                        <p class="text-slate-300">
                            Are you sure you want to switch from <span class="font-mono px-2 py-1 bg-slate-800 rounded text-sm">{{ $project->branch }}</span> to <span class="font-mono px-2 py-1 bg-purple-500/20 text-purple-400 rounded text-sm">{{ $selectedBranchForConfirm }}</span>?
                        </p>
                        <div class="flex gap-3">
                            <button wire:click="confirmBranchSwitch"
                                class="flex-1 px-4 py-3 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 text-white font-medium hover:from-purple-700 hover:to-pink-700 transition-all">
                                Confirm Switch
                            </button>
                            <button wire:click="$set('showBranchConfirmModal', false)"
                                class="flex-1 px-4 py-3 rounded-xl bg-slate-800 text-slate-300 font-medium hover:bg-slate-700 transition-all">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Deploy Modal --}}
    @if($showDeployModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-transition>
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="$set('showDeployModal', false)"></div>
                <div class="relative bg-slate-900 rounded-2xl border border-slate-700/50 shadow-2xl w-full max-w-lg overflow-hidden">
                    <div class="p-6 border-b border-slate-700/50 bg-gradient-to-r from-blue-900/30 to-indigo-900/30">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                Deploy Project
                            </h3>
                            <button wire:click="$set('showDeployModal', false)" class="text-slate-400 hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <p class="text-sm text-slate-400 mt-2">This will deploy the latest changes from the <span class="font-mono text-blue-400">{{ $project->branch }}</span> branch.</p>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="p-4 rounded-xl bg-blue-500/10 border border-blue-500/20">
                            <h4 class="text-sm font-semibold text-blue-300 mb-2">Deployment will:</h4>
                            <ul class="text-sm text-blue-200 space-y-1">
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Pull latest code from repository
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Build Docker containers
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Apply environment variables
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Start the application
                                </li>
                            </ul>
                        </div>

                        <div class="flex gap-3">
                            <button wire:click="$set('showDeployModal', false)"
                                class="flex-1 px-4 py-3 rounded-xl bg-slate-800 text-slate-300 font-medium hover:bg-slate-700 transition-all">
                                Cancel
                            </button>
                            <button wire:click="deploy"
                                wire:loading.attr="disabled"
                                class="flex-1 px-4 py-3 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium hover:from-blue-700 hover:to-indigo-700 transition-all disabled:opacity-50">
                                <span wire:loading.remove wire:target="deploy">Deploy Now</span>
                                <span wire:loading wire:target="deploy" class="flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Deploying...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
