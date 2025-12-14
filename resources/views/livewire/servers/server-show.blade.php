<div wire:init="loadServerData"
     @if($dockerInstalling) wire:poll.3s="checkDockerInstallProgress" @else wire:poll.30s @endif
     class="min-h-screen">

    {{-- Animated Background Orbs --}}
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-gradient-to-br from-blue-500/5 via-cyan-500/5 to-indigo-500/5 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-gradient-to-tr from-purple-500/5 via-pink-500/5 to-fuchsia-500/5 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-gradient-to-r from-emerald-500/3 to-teal-500/3 rounded-full blur-3xl"></div>
    </div>

    {{-- Hero Section with Premium Styling --}}
    <div class="relative mb-8">
        <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 rounded-3xl overflow-hidden border border-slate-700/50 shadow-2xl">
            {{-- Grid Pattern Overlay --}}
            <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23fff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>

            <div class="relative p-6 lg:p-8">
                {{-- Top Row: Logo, Title, Actions --}}
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                    <div class="flex items-start gap-4">
                        {{-- Animated Server Icon --}}
                        <div class="relative">
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-cyan-400 via-blue-500 to-indigo-600 flex items-center justify-center shadow-xl shadow-cyan-500/30 transform hover:scale-105 transition-transform">
                                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                </svg>
                            </div>
                            {{-- Status Indicator --}}
                            <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full border-2 border-slate-900 flex items-center justify-center
                                @if($server->status === 'online') bg-emerald-500
                                @elseif($server->status === 'maintenance') bg-amber-500
                                @else bg-red-500
                                @endif">
                                @if($server->status === 'online')
                                    <span class="w-2 h-2 rounded-full bg-white animate-ping"></span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <h1 class="text-2xl lg:text-3xl font-bold text-white tracking-tight">{{ $server->name }}</h1>
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider
                                    @if($server->status === 'online') bg-emerald-500/20 text-emerald-400 border border-emerald-500/30
                                    @elseif($server->status === 'maintenance') bg-amber-500/20 text-amber-400 border border-amber-500/30
                                    @else bg-red-500/20 text-red-400 border border-red-500/30
                                    @endif">
                                    {{ ucfirst($server->status) }}
                                </span>
                            </div>
                            <p class="text-slate-400 text-sm">{{ $server->os ?? 'Server Infrastructure' }}</p>

                            {{-- Info Pills --}}
                            <div class="flex flex-wrap items-center gap-2 mt-3">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-800/80 text-slate-300 border border-slate-700/50">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                    </svg>
                                    {{ $server->ip_address }}
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-800/80 text-slate-300 border border-slate-700/50">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    {{ $server->username . '@' . $server->hostname }}
                                </span>
                                @if($server->docker_installed)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-800/80 text-slate-300 border border-slate-700/50">
                                        <svg class="w-3.5 h-3.5 text-cyan-400" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M13.983 11.078h2.119a.186.186 0 00.186-.185V9.006a.186.186 0 00-.186-.186h-2.119a.185.185 0 00-.185.185v1.888c0 .102.083.185.185.185m-2.954-5.43h2.118a.186.186 0 00.186-.186V3.574a.186.186 0 00-.186-.185h-2.118a.185.185 0 00-.185.185v1.888c0 .102.082.185.185.186m0 2.716h2.118a.187.187 0 00.186-.186V6.29a.186.186 0 00-.186-.185h-2.118a.185.185 0 00-.185.185v1.887c0 .102.082.185.185.186"/>
                                        </svg>
                                        Docker {{ $server->docker_version ?? 'Installed' }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Quick Action Buttons --}}
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('servers.provisioning', $server) }}"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm bg-slate-800/80 text-slate-300 border border-slate-700/50 hover:bg-slate-700/80 hover:text-white transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Provisioning
                        </a>

                        <a href="{{ route('servers.security', $server) }}"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm bg-slate-800/80 text-slate-300 border border-slate-700/50 hover:bg-slate-700/80 hover:text-white transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            Security
                        </a>

                        <a href="{{ route('servers.log-sources', $server) }}"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm bg-slate-800/80 text-slate-300 border border-slate-700/50 hover:bg-slate-700/80 hover:text-white transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Log Sources
                        </a>

                        @can('update', $server)
                        <a href="{{ route('servers.edit', $server) }}"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm bg-slate-800/80 text-slate-300 border border-slate-700/50 hover:bg-slate-700/80 hover:text-white transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Configure
                        </a>
                        @endcan
                    </div>
                </div>

                {{-- Server Connection Info Bar --}}
                <div class="mt-6 flex items-center gap-4 p-4 rounded-2xl bg-slate-800/50 border border-slate-700/30 backdrop-blur-sm">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-700 to-slate-800 flex items-center justify-center border border-slate-600/50">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-slate-500 font-medium">SSH:</span>
                            <span class="px-2.5 py-1 rounded-md bg-cyan-500/20 text-cyan-400 font-mono text-sm border border-cyan-500/30">{{ $server->username . '@' . $server->ip_address . ':' . $server->port }}</span>
                        </div>
                    </div>
                    <button wire:click="pingServer"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 rounded-lg text-sm font-medium bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/30 transition-all disabled:opacity-50">
                        <span wire:loading.remove wire:target="pingServer" class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                            </svg>
                            Ping Server
                        </span>
                        <span wire:loading wire:target="pingServer" class="flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Pinging...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
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

    @if (session()->has('info'))
        <div class="mb-6 bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500 text-blue-800 dark:text-blue-400 px-6 py-4 rounded-r-lg shadow">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                {{ session('info') }}
            </div>
        </div>
    @endif

    {{-- Quick Stats Cards with Glassmorphism --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="group bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 p-6 hover:border-emerald-500/50 transition-all hover:shadow-lg hover:shadow-emerald-500/10">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-white">Status</h3>
            </div>
            <p class="text-2xl font-bold
                @if($server->status === 'online') text-emerald-400
                @elseif($server->status === 'maintenance') text-amber-400
                @else text-red-400
                @endif">
                {{ ucfirst($server->status) }}
            </p>
            <p class="text-xs text-slate-400 mt-1">Server health</p>
        </div>

        <div class="group bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 p-6 hover:border-blue-500/50 transition-all hover:shadow-lg hover:shadow-blue-500/10">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-white">CPU</h3>
            </div>
            <p class="text-3xl font-bold text-white">{{ $server->cpu_cores ?? '-' }}</p>
            <p class="text-xs text-slate-400 mt-1">Core count</p>
        </div>

        <div class="group bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 p-6 hover:border-purple-500/50 transition-all hover:shadow-lg hover:shadow-purple-500/10">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-white">Memory</h3>
            </div>
            <p class="text-3xl font-bold text-white">{{ $server->memory_gb ?? '-' }}<span class="text-lg">GB</span></p>
            <p class="text-xs text-slate-400 mt-1">Total RAM</p>
        </div>

        <div class="group bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 p-6 hover:border-cyan-500/50 transition-all hover:shadow-lg hover:shadow-cyan-500/10">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-white">Projects</h3>
            </div>
            <p class="text-3xl font-bold text-white">{{ $projects->count() }}</p>
            <p class="text-xs text-slate-400 mt-1">Active projects</p>
        </div>
    </div>

    {{-- Premium Tab Navigation with Unique Colors --}}
    <div class="mb-6">
        <div class="flex items-center gap-2 p-1.5 bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700/50 overflow-x-auto">
            <button wire:click="$set('activeTab', 'overview')"
                class="px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-300 flex items-center gap-2 whitespace-nowrap {{ !isset($activeTab) || $activeTab === 'overview' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-700/50' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Overview
            </button>

            <button wire:click="$set('activeTab', 'actions')"
                class="px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-300 flex items-center gap-2 whitespace-nowrap {{ isset($activeTab) && $activeTab === 'actions' ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-lg shadow-emerald-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-700/50' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Quick Actions
            </button>

            <button wire:click="$set('activeTab', 'metrics')"
                class="px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-300 flex items-center gap-2 whitespace-nowrap {{ isset($activeTab) && $activeTab === 'metrics' ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-lg shadow-purple-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-700/50' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                </svg>
                Live Metrics
                @if($isLoading)
                    <span class="w-2 h-2 bg-purple-400 rounded-full animate-pulse"></span>
                @endif
            </button>

            <button wire:click="$set('activeTab', 'projects')"
                class="px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-300 flex items-center gap-2 whitespace-nowrap {{ isset($activeTab) && $activeTab === 'projects' ? 'bg-gradient-to-r from-cyan-600 to-blue-600 text-white shadow-lg shadow-cyan-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-700/50' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
                Projects
                @if($projects->count() > 0)
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-cyan-500/30">{{ $projects->count() }}</span>
                @endif
            </button>

            <button wire:click="$set('activeTab', 'terminal')"
                class="px-4 py-2.5 rounded-lg text-sm font-medium transition-all duration-300 flex items-center gap-2 whitespace-nowrap {{ isset($activeTab) && $activeTab === 'terminal' ? 'bg-gradient-to-r from-amber-600 to-orange-600 text-white shadow-lg shadow-amber-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-700/50' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                SSH Terminal
            </button>
        </div>
    </div>

    {{-- Tab Content --}}
    <div class="relative">
        {{-- Overview Tab --}}
        @if(!isset($activeTab) || $activeTab === 'overview')
        <div class="space-y-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Server Details Card --}}
                <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-6 border-b border-blue-500/30">
                        <h2 class="text-xl font-bold text-white flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Server Information
                        </h2>
                    </div>
                    <div class="p-6 space-y-3">
                        <div class="flex items-center justify-between py-3 border-b border-slate-700/50">
                            <span class="text-slate-400 flex items-center gap-2 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                                </svg>
                                Hostname
                            </span>
                            <span class="font-semibold text-white font-mono">{{ $server->hostname }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-slate-700/50">
                            <span class="text-slate-400 flex items-center gap-2 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                </svg>
                                IP Address
                            </span>
                            <span class="font-semibold text-white font-mono">{{ $server->ip_address }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-slate-700/50">
                            <span class="text-slate-400 flex items-center gap-2 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                SSH Port
                            </span>
                            <span class="font-semibold text-white">{{ $server->port }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-slate-700/50">
                            <span class="text-slate-400 flex items-center gap-2 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Username
                            </span>
                            <span class="font-semibold text-white font-mono">{{ $server->username }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-slate-700/50">
                            <span class="text-slate-400 flex items-center gap-2 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                                </svg>
                                Operating System
                            </span>
                            <span class="font-semibold text-white">{{ $server->os ?? 'Unknown' }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-slate-700/50">
                            <span class="text-slate-400 flex items-center gap-2 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                                </svg>
                                Disk Space
                            </span>
                            <span class="font-semibold text-white">{{ $server->disk_gb ?? '-' }} GB</span>
                        </div>
                        @if($server->location_name)
                            <div class="flex items-center justify-between py-3 border-b border-slate-700/50">
                                <span class="text-slate-400 flex items-center gap-2 text-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    Location
                                </span>
                                <span class="font-semibold text-white">{{ $server->location_name }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between py-3">
                            <span class="text-slate-400 flex items-center gap-2 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Added
                            </span>
                            <span class="font-semibold text-white">{{ $server->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Docker Status Card --}}
                <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 overflow-hidden">
                    <div class="bg-gradient-to-r from-cyan-600 to-blue-600 p-6 border-b border-cyan-500/30">
                        <h2 class="text-xl font-bold text-white flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M13.983 11.078h2.119a.186.186 0 00.186-.185V9.006a.186.186 0 00-.186-.186h-2.119a.185.185 0 00-.185.185v1.888c0 .102.083.185.185.185m-2.954-5.43h2.118a.186.186 0 00.186-.186V3.574a.186.186 0 00-.186-.185h-2.118a.185.185 0 00-.185.185v1.888c0 .102.082.185.185.186"/>
                            </svg>
                            Docker Status
                        </h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between p-4 rounded-xl bg-slate-900/50 border border-slate-700">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center {{ $server->docker_installed ? 'bg-emerald-500/20' : 'bg-red-500/20' }}">
                                    @if($server->docker_installed)
                                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    @endif
                                </div>
                                <div>
                                    <p class="font-semibold text-white">Docker Engine</p>
                                    <p class="text-xs text-slate-400">
                                        @if($server->docker_installed)
                                            v{{ $server->docker_version ?? 'Installed' }}
                                        @else
                                            Not Installed
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-lg text-xs font-bold {{ $server->docker_installed ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-red-500/20 text-red-400 border border-red-500/30' }}">
                                {{ $server->docker_installed ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        @if(!$server->docker_installed)
                            @if($dockerInstalling)
                                <div class="p-4 rounded-xl bg-blue-500/10 border border-blue-500/30">
                                    <div class="flex items-center gap-3 mb-2">
                                        <svg class="w-5 h-5 text-blue-400 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        <p class="text-sm font-semibold text-blue-300">Installing Docker...</p>
                                    </div>
                                    @if($dockerInstallStatus)
                                        <p class="text-xs text-blue-200">{{ $dockerInstallStatus['message'] ?? 'Please wait...' }}</p>
                                    @endif
                                </div>
                            @else
                                <button wire:click="installDocker"
                                    wire:confirm="Install Docker?\n\nThis will install Docker Engine, Docker Compose, and required dependencies.\nThis runs in the background and may take 3-5 minutes."
                                    wire:loading.attr="disabled"
                                    class="w-full p-4 rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 text-white font-medium transition-all disabled:opacity-50 flex items-center justify-center gap-2">
                                    <svg wire:loading.remove wire:target="installDocker" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    <svg wire:loading wire:target="installDocker" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <span wire:loading.remove wire:target="installDocker">Install Docker Now</span>
                                    <span wire:loading wire:target="installDocker">Starting Installation...</span>
                                </button>
                            @endif
                        @else
                            <a href="{{ route('docker.dashboard', $server) }}"
                                class="w-full p-4 rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 text-white font-medium transition-all flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M13.983 11.078h2.119a.186.186 0 00.186-.185V9.006a.186.186 0 00-.186-.186h-2.119a.185.185 0 00-.185.185v1.888c0 .102.083.185.185.185m-2.954-5.43h2.118a.186.186 0 00.186-.186V3.574a.186.186 0 00-.186-.185h-2.118a.185.185 0 00-.185.185v1.888c0 .102.082.185.185.186"/>
                                </svg>
                                Open Docker Dashboard
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Quick Actions Tab --}}
        @if(isset($activeTab) && $activeTab === 'actions')
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 overflow-hidden">
            <div class="bg-gradient-to-r from-emerald-600 to-teal-600 p-6 border-b border-emerald-500/30">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Quick Actions
                </h2>
                <p class="text-sm text-emerald-100 mt-1">Last ping: {{ $server->last_ping_at ? $server->last_ping_at->diffForHumans() : 'Never' }}</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    {{-- Reboot Server --}}
                    <button wire:click="rebootServer"
                        wire:confirm="⚠️ REBOOT SERVER?\n\nThis will restart the server and interrupt all running services.\nThe server may take 1-3 minutes to come back online."
                        wire:loading.attr="disabled"
                        class="flex flex-col items-center justify-center p-6 bg-gradient-to-br from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl hover:-translate-y-0.5 disabled:opacity-50">
                        <svg wire:loading.remove wire:target="rebootServer" class="w-10 h-10 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <svg wire:loading wire:target="rebootServer" class="animate-spin w-10 h-10 mb-3" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span class="text-sm font-semibold" wire:loading.remove wire:target="rebootServer">Reboot</span>
                        <span class="text-sm font-semibold" wire:loading wire:target="rebootServer">Rebooting...</span>
                    </button>

                    {{-- Clear Cache --}}
                    <button wire:click="clearSystemCache"
                        wire:confirm="Clear system cache?\n\nThis will drop cached data to free up memory."
                        wire:loading.attr="disabled"
                        class="flex flex-col items-center justify-center p-6 bg-gradient-to-br from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 text-white rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl hover:-translate-y-0.5 disabled:opacity-50">
                        <svg wire:loading.remove wire:target="clearSystemCache" class="w-10 h-10 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <svg wire:loading wire:target="clearSystemCache" class="animate-spin w-10 h-10 mb-3" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span class="text-sm font-semibold" wire:loading.remove wire:target="clearSystemCache">Clear Cache</span>
                        <span class="text-sm font-semibold" wire:loading wire:target="clearSystemCache">Clearing...</span>
                    </button>

                    {{-- Check Docker --}}
                    <button wire:click="checkDockerStatus"
                        wire:loading.attr="disabled"
                        class="flex flex-col items-center justify-center p-6 bg-gradient-to-br from-blue-500 to-cyan-600 hover:from-blue-600 hover:to-cyan-700 text-white rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl hover:-translate-y-0.5 disabled:opacity-50">
                        <svg wire:loading.remove wire:target="checkDockerStatus" class="w-10 h-10 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <svg wire:loading wire:target="checkDockerStatus" class="animate-spin w-10 h-10 mb-3" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span class="text-sm font-semibold" wire:loading.remove wire:target="checkDockerStatus">Check Docker</span>
                        <span class="text-sm font-semibold" wire:loading wire:target="checkDockerStatus">Checking...</span>
                    </button>

                    {{-- SSL Certificates --}}
                    <a href="{{ route('servers.ssl', $server) }}"
                        class="flex flex-col items-center justify-center p-6 bg-gradient-to-br from-green-500 to-teal-600 hover:from-green-600 hover:to-teal-700 text-white rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                        <svg class="w-10 h-10 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span class="text-sm font-semibold">SSL Certificates</span>
                    </a>
                </div>

                {{-- Service Management --}}
                <div class="mt-6">
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Restart Services
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                        @foreach(['nginx', 'apache2', 'mysql', 'redis', 'php8.4-fpm', 'docker', 'supervisor'] as $service)
                            <button wire:click="restartService('{{ $service }}')"
                                wire:loading.attr="disabled"
                                class="flex items-center justify-between p-3 bg-slate-900/50 hover:bg-slate-900/70 border border-slate-700 hover:border-amber-500/50 rounded-xl text-sm font-medium text-slate-300 hover:text-white transition-all disabled:opacity-50">
                                <span class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    {{ $service }}
                                </span>
                                <svg wire:loading wire:target="restartService('{{ $service }}')" class="animate-spin w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Metrics Tab --}}
        @if(isset($activeTab) && $activeTab === 'metrics')
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 overflow-hidden">
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 p-6 border-b border-purple-500/30">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-white flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            Live Metrics
                            @if($isLoading)
                                <svg class="animate-spin h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            @endif
                        </h2>
                        <p class="text-sm text-purple-100 mt-1">Real-time server performance monitoring</p>
                    </div>
                    <a href="{{ route('servers.metrics', $server) }}"
                        class="px-4 py-2 rounded-lg text-sm font-medium bg-white/20 hover:bg-white/30 text-white transition-all">
                        Full Dashboard
                    </a>
                </div>
            </div>
            <div class="p-6">
                @if($isLoading)
                    {{-- Loading Skeleton --}}
                    <div class="space-y-6 animate-pulse">
                        @for($i = 0; $i < 3; $i++)
                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <div class="h-4 w-24 bg-slate-700 rounded"></div>
                                    <div class="h-4 w-10 bg-slate-700 rounded"></div>
                                </div>
                                <div class="w-full bg-slate-700 rounded-full h-4"></div>
                            </div>
                        @endfor
                    </div>
                @elseif($recentMetrics->count() > 0)
                    @php $latestMetric = $recentMetrics->first(); @endphp
                    <div class="space-y-6">
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-slate-400 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                                    </svg>
                                    CPU Usage
                                </span>
                                <span class="text-sm font-bold text-white">{{ $latestMetric->cpu_usage }}%</span>
                            </div>
                            <div class="w-full bg-slate-700 rounded-full h-4 overflow-hidden">
                                <div class="h-4 rounded-full transition-all duration-500 shadow-lg
                                    @if($latestMetric->cpu_usage > 80) bg-gradient-to-r from-red-500 to-red-600
                                    @elseif($latestMetric->cpu_usage > 60) bg-gradient-to-r from-yellow-500 to-orange-500
                                    @else bg-gradient-to-r from-blue-500 to-indigo-600
                                    @endif" style="width: {{ $latestMetric->cpu_usage }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-slate-400 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                    Memory Usage
                                </span>
                                <span class="text-sm font-bold text-white">{{ $latestMetric->memory_usage }}%</span>
                            </div>
                            <div class="w-full bg-slate-700 rounded-full h-4 overflow-hidden">
                                <div class="h-4 rounded-full transition-all duration-500 shadow-lg
                                    @if($latestMetric->memory_usage > 80) bg-gradient-to-r from-red-500 to-red-600
                                    @elseif($latestMetric->memory_usage > 60) bg-gradient-to-r from-yellow-500 to-orange-500
                                    @else bg-gradient-to-r from-green-500 to-emerald-600
                                    @endif" style="width: {{ $latestMetric->memory_usage }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-slate-400 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                                    </svg>
                                    Disk Usage
                                </span>
                                <span class="text-sm font-bold text-white">{{ $latestMetric->disk_usage }}%</span>
                            </div>
                            <div class="w-full bg-slate-700 rounded-full h-4 overflow-hidden">
                                <div class="h-4 rounded-full transition-all duration-500 shadow-lg
                                    @if($latestMetric->disk_usage > 80) bg-gradient-to-r from-red-500 to-red-600
                                    @elseif($latestMetric->disk_usage > 60) bg-gradient-to-r from-yellow-500 to-orange-500
                                    @else bg-gradient-to-r from-purple-500 to-pink-600
                                    @endif" style="width: {{ $latestMetric->disk_usage }}%"></div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between p-4 rounded-xl bg-purple-500/10 border border-purple-500/30 mt-4">
                            <span class="text-xs text-purple-300">Last updated: {{ $latestMetric->recorded_at->diffForHumans() }}</span>
                            <a href="{{ route('servers.metrics', $server) }}"
                                class="text-xs font-medium text-purple-400 hover:text-purple-300 hover:underline">
                                View detailed metrics
                            </a>
                        </div>
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <p class="text-slate-400">No metrics available yet</p>
                        <p class="text-sm text-slate-500 mt-1">Metrics will appear after the first monitoring run</p>
                    </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Projects Tab --}}
        @if(isset($activeTab) && $activeTab === 'projects')
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 overflow-hidden">
            <div class="bg-gradient-to-r from-cyan-600 to-blue-600 p-6 border-b border-cyan-500/30">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        Projects
                    </h2>
                    <span class="text-sm text-cyan-100">{{ $projects->count() }} total</span>
                </div>
            </div>
            <div class="p-6">
                @if($projects->count() > 0)
                    <div class="space-y-3">
                        @foreach($projects as $project)
                            <a href="{{ route('projects.show', $project) }}"
                                class="flex items-center justify-between p-4 bg-slate-900/50 hover:bg-slate-900/70 rounded-xl border border-slate-700/30 hover:border-cyan-500/30 transition-all group">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white font-bold shadow-lg group-hover:scale-110 transition-transform">
                                        {{ strtoupper(substr($project->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-white group-hover:text-cyan-400 transition-colors">{{ $project->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $project->domain ?? 'No domain' }}</p>
                                    </div>
                                </div>
                                <span class="px-3 py-1 rounded-lg text-xs font-semibold
                                    @if($project->status === 'running') bg-emerald-500/20 text-emerald-400 border border-emerald-500/30
                                    @elseif($project->status === 'stopped') bg-slate-500/20 text-slate-400 border border-slate-500/30
                                    @elseif($project->status === 'building') bg-amber-500/20 text-amber-400 border border-amber-500/30
                                    @elseif($project->status === 'failed') bg-red-500/20 text-red-400 border border-red-500/30
                                    @else bg-slate-500/20 text-slate-400 border border-slate-500/30
                                    @endif">
                                    {{ ucfirst($project->status) }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        <p class="text-slate-400">No projects on this server</p>
                        <a href="{{ route('projects.create') }}"
                            class="inline-flex items-center gap-2 mt-4 px-4 py-2 rounded-lg bg-cyan-500/20 text-cyan-400 border border-cyan-500/30 hover:bg-cyan-500/30 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Create a project
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Recent Deployments --}}
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 overflow-hidden mt-6">
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 p-6 border-b border-purple-500/30">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    Recent Deployments
                </h2>
            </div>
            <div class="p-6">
                @if($deployments->count() > 0)
                    <div class="space-y-3">
                        @foreach($deployments as $deployment)
                            <div class="flex items-center justify-between p-4 bg-slate-900/50 rounded-xl border border-slate-700/30">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center
                                        @if($deployment->status === 'success') bg-emerald-500/20
                                        @elseif($deployment->status === 'failed') bg-red-500/20
                                        @else bg-amber-500/20
                                        @endif">
                                        @if($deployment->status === 'success')
                                            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        @elseif($deployment->status === 'failed')
                                            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5 text-amber-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-semibold text-white">{{ $deployment->project->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $deployment->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                                <span class="px-3 py-1 rounded-lg text-xs font-semibold
                                    @if($deployment->status === 'success') bg-emerald-500/20 text-emerald-400 border border-emerald-500/30
                                    @elseif($deployment->status === 'failed') bg-red-500/20 text-red-400 border border-red-500/30
                                    @else bg-amber-500/20 text-amber-400 border border-amber-500/30
                                    @endif">
                                    {{ ucfirst($deployment->status) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <p class="text-slate-400">No deployments yet</p>
                    </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Terminal Tab --}}
        @if(isset($activeTab) && $activeTab === 'terminal')
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 overflow-hidden">
            <div class="bg-gradient-to-r from-slate-900 to-slate-800 p-6 border-b border-slate-700/50">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    SSH Terminal
                </h2>
                <p class="text-sm text-slate-400 mt-1">Execute commands directly on {{ $server->name }}</p>
            </div>
            <div class="p-6">
                <livewire:servers.s-s-h-terminal :server="$server" />
            </div>
        </div>
        @endif
    </div>

    {{-- Server Tags Assignment --}}
    <div class="mt-8">
        <livewire:servers.server-tag-assignment :server="$server" />
    </div>
</div>
