<div wire:init="loadServerData" @if($dockerInstalling) wire:poll.3s="checkDockerInstallProgress" @else wire:poll.30s @endif>
    <!-- Hero Section -->
    <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-slate-800 via-slate-900 to-slate-800 p-8 shadow-2xl overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="server-pattern" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                        <rect x="0" y="0" width="4" height="4" fill="currentColor" class="text-white"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#server-pattern)"/>
            </svg>
        </div>

        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex items-start gap-4">
                    <!-- Server Icon with Status -->
                    <div class="relative">
                        <div class="p-4 bg-white/10 backdrop-blur-md rounded-2xl">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                            </svg>
                        </div>
                        <!-- Status Indicator -->
                        <span class="absolute -bottom-1 -right-1 flex h-5 w-5">
                            @if($server->status === 'online')
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-5 w-5 bg-green-500 border-2 border-slate-800"></span>
                            @elseif($server->status === 'maintenance')
                                <span class="animate-pulse absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-5 w-5 bg-yellow-500 border-2 border-slate-800"></span>
                            @else
                                <span class="relative inline-flex rounded-full h-5 w-5 bg-red-500 border-2 border-slate-800"></span>
                            @endif
                        </span>
                    </div>

                    <div>
                        <h1 class="text-3xl font-bold text-white">{{ $server->name }}</h1>
                        <div class="flex flex-wrap items-center gap-3 mt-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/10 text-white/90">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                                </svg>
                                {{ $server->ip_address }}
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/10 text-white/90">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                {{ $server->username }}@{{ $server->ip_address }}:{{ $server->port }}
                            </span>
                            @if($server->os)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-500/20 text-blue-300">
                                    {{ $server->os }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <a href="{{ route('servers.provisioning', $server) }}" class="px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-xl transition-all duration-200 font-medium flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Provisioning
                    </a>
                    <a href="{{ route('servers.security', $server) }}" class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl transition-all duration-200 font-medium flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        Security
                    </a>
                    <a href="{{ route('servers.backups', $server) }}" class="px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl transition-all duration-200 font-medium flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                        </svg>
                        Backups
                    </a>
                    <a href="{{ route('servers.metrics', $server) }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-all duration-200 font-medium flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Metrics Dashboard
                    </a>
                    @can('update', $server)
                    <a href="{{ route('servers.edit', $server) }}" class="px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl transition-all duration-200 font-medium flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit
                    </a>
                    @endcan
                    <a href="{{ route('servers.index') }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-all duration-200 font-medium">
                        ← Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="mb-6 bg-gradient-to-r from-green-500/20 to-emerald-500/20 border border-green-500/30 text-green-400 px-5 py-4 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 bg-gradient-to-r from-red-500/20 to-red-600/20 border border-red-500/30 text-red-400 px-5 py-4 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    @if (session()->has('info'))
        <div class="mb-6 bg-gradient-to-r from-blue-500/20 to-indigo-500/20 border border-blue-500/30 text-blue-400 px-5 py-4 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            {{ session('info') }}
        </div>
    @endif

    <!-- Quick Actions Panel -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-8 transition-colors">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                Quick Actions
            </h3>
            <span class="text-sm text-gray-500 dark:text-gray-400">
                Last ping: {{ $server->last_ping_at ? $server->last_ping_at->diffForHumans() : 'Never' }}
            </span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <!-- Ping Server -->
            <button wire:click="pingServer"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    wire:target="pingServer"
                    class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                <svg wire:loading.remove wire:target="pingServer" class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                </svg>
                <svg wire:loading wire:target="pingServer" class="animate-spin w-8 h-8 mb-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-semibold" wire:loading.remove wire:target="pingServer">Ping</span>
                <span class="text-sm font-semibold" wire:loading wire:target="pingServer">Pinging...</span>
            </button>

            <!-- Reboot Server -->
            <button wire:click="rebootServer"
                    wire:confirm="⚠️ REBOOT SERVER?\n\nThis will restart the server and interrupt all running services.\nThe server may take 1-3 minutes to come back online."
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    wire:target="rebootServer"
                    class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                <svg wire:loading.remove wire:target="rebootServer" class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <svg wire:loading wire:target="rebootServer" class="animate-spin w-8 h-8 mb-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-semibold" wire:loading.remove wire:target="rebootServer">Reboot</span>
                <span class="text-sm font-semibold" wire:loading wire:target="rebootServer">Rebooting...</span>
            </button>

            <!-- Clear Cache -->
            <button wire:click="clearSystemCache"
                    wire:confirm="Clear system cache?\n\nThis will drop cached data to free up memory."
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    wire:target="clearSystemCache"
                    class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 text-white rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                <svg wire:loading.remove wire:target="clearSystemCache" class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                <svg wire:loading wire:target="clearSystemCache" class="animate-spin w-8 h-8 mb-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-semibold" wire:loading.remove wire:target="clearSystemCache">Clear Cache</span>
                <span class="text-sm font-semibold" wire:loading wire:target="clearSystemCache">Clearing...</span>
            </button>

            <!-- Check Docker -->
            <button wire:click="checkDockerStatus"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    wire:target="checkDockerStatus"
                    class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-blue-500 to-cyan-600 hover:from-blue-600 hover:to-cyan-700 text-white rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                <svg wire:loading.remove wire:target="checkDockerStatus" class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <svg wire:loading wire:target="checkDockerStatus" class="animate-spin w-8 h-8 mb-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-semibold" wire:loading.remove wire:target="checkDockerStatus">Check Docker</span>
                <span class="text-sm font-semibold" wire:loading wire:target="checkDockerStatus">Checking...</span>
            </button>

            <!-- Install Docker -->
            @if(!$server->docker_installed)
                @if($dockerInstalling)
                    <!-- Docker Installation In Progress -->
                    <div class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-blue-500 to-indigo-600 text-white rounded-xl shadow-lg">
                        <svg class="animate-spin w-8 h-8 mb-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm font-semibold">Installing Docker...</span>
                        @if($dockerInstallStatus)
                            <span class="text-xs mt-1 opacity-80">{{ $dockerInstallStatus['message'] ?? 'Please wait...' }}</span>
                        @endif
                    </div>
                @else
                    <button wire:click="installDocker"
                            wire:confirm="Install Docker?\n\nThis will install Docker Engine, Docker Compose, and required dependencies.\nThis runs in the background and may take 3-5 minutes."
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            wire:target="installDocker"
                            class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-sky-500 to-blue-600 hover:from-sky-600 hover:to-blue-700 text-white rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                        <svg wire:loading.remove wire:target="installDocker" class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        <svg wire:loading wire:target="installDocker" class="animate-spin w-8 h-8 mb-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm font-semibold" wire:loading.remove wire:target="installDocker">Install Docker</span>
                        <span class="text-sm font-semibold" wire:loading wire:target="installDocker">Starting...</span>
                    </button>
                @endif
            @else
                <!-- Docker Dashboard -->
                <a href="{{ route('docker.dashboard', $server) }}"
                   class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-sky-500 to-blue-600 hover:from-sky-600 hover:to-blue-700 text-white rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                    <svg class="w-8 h-8 mb-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M13.983 11.078h2.119a.186.186 0 00.186-.185V9.006a.186.186 0 00-.186-.186h-2.119a.185.185 0 00-.185.185v1.888c0 .102.083.185.185.185m-2.954-5.43h2.118a.186.186 0 00.186-.186V3.574a.186.186 0 00-.186-.185h-2.118a.185.185 0 00-.185.185v1.888c0 .102.082.185.185.186m0 2.716h2.118a.187.187 0 00.186-.186V6.29a.186.186 0 00-.186-.185h-2.118a.185.185 0 00-.185.185v1.887c0 .102.082.185.185.186m-2.93 0h2.12a.186.186 0 00.184-.186V6.29a.185.185 0 00-.185-.185H8.1a.185.185 0 00-.185.185v1.887c0 .102.083.185.185.186m-2.964 0h2.119a.186.186 0 00.185-.186V6.29a.185.185 0 00-.185-.185H5.136a.186.186 0 00-.186.185v1.887c0 .102.084.185.186.186m5.893 2.715h2.118a.186.186 0 00.186-.185V9.006a.186.186 0 00-.186-.186h-2.118a.185.185 0 00-.185.185v1.888c0 .102.082.185.185.185m-2.93 0h2.12a.185.185 0 00.184-.185V9.006a.185.185 0 00-.184-.186h-2.12a.185.185 0 00-.184.185v1.888c0 .102.083.185.185.185m-2.964 0h2.119a.185.185 0 00.185-.185V9.006a.185.185 0 00-.185-.186h-2.12a.186.186 0 00-.185.186v1.887c0 .102.084.185.186.185m-2.92 0h2.12a.185.185 0 00.184-.185V9.006a.185.185 0 00-.184-.186h-2.12a.185.185 0 00-.184.185v1.888c0 .102.082.185.185.185M23.763 9.89c-.065-.051-.672-.51-1.954-.51-.338.001-.676.03-1.01.087-.248-1.7-1.653-2.53-1.716-2.566l-.344-.199-.226.327c-.284.438-.49.922-.612 1.43-.23.97-.09 1.882.403 2.661-.595.332-1.55.413-1.744.42H.751a.751.751 0 00-.75.748 11.376 11.376 0 00.692 4.062c.545 1.428 1.355 2.48 2.41 3.124 1.18.723 3.1 1.137 5.275 1.137.983.003 1.963-.086 2.93-.266a12.248 12.248 0 003.823-1.389c.98-.567 1.86-1.288 2.61-2.136 1.252-1.418 1.998-2.997 2.553-4.4h.221c1.372 0 2.215-.549 2.68-1.009.309-.293.55-.65.707-1.046l.098-.288z"/>
                    </svg>
                    <span class="text-sm font-semibold">Docker Panel</span>
                </a>
            @endif

            <!-- SSL Certificates -->
            <a href="{{ route('servers.ssl', $server) }}"
               class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-green-500 to-teal-600 hover:from-green-600 hover:to-teal-700 text-white rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                <span class="text-sm font-semibold">SSL Certificates</span>
            </a>

            <!-- Restart Services Dropdown -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" type="button"
                        class="flex flex-col items-center justify-center p-4 w-full bg-gradient-to-br from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                    <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span class="text-sm font-semibold">Services</span>
                </button>
                <div x-show="open" @click.away="open = false"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute left-0 bottom-full mb-2 w-56 rounded-xl shadow-2xl bg-white dark:bg-gray-700 ring-1 ring-black ring-opacity-5 z-50 overflow-hidden">
                    <div class="py-2">
                        <div class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Restart Service</div>
                        @foreach(['nginx', 'apache2', 'mysql', 'redis', 'php8.4-fpm', 'docker', 'supervisor'] as $service)
                            <button wire:click="restartService('{{ $service }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="restartService('{{ $service }}')"
                                    @click="open = false"
                                    class="w-full text-left px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center justify-between transition-colors">
                                <span class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                    {{ $service }}
                                </span>
                                <svg wire:loading wire:target="restartService('{{ $service }}')" class="animate-spin w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Server Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <!-- Status -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 transition-colors">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</p>
                    <div class="mt-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold
                            @if($server->status === 'online') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                            @elseif($server->status === 'maintenance') bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400
                            @else bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                            @endif">
                            <span class="w-2 h-2 rounded-full mr-2
                                @if($server->status === 'online') bg-green-500 animate-pulse
                                @elseif($server->status === 'maintenance') bg-yellow-500
                                @else bg-red-500
                                @endif"></span>
                            {{ ucfirst($server->status) }}
                        </span>
                    </div>
                </div>
                <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded-xl">
                    <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- CPU -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 transition-colors">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">CPU Cores</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $server->cpu_cores ?? '-' }}</p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Memory -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 transition-colors">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Memory</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $server->memory_gb ?? '-' }} <span class="text-sm font-normal text-gray-500">GB</span></p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Docker -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 transition-colors">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Docker</p>
                    <p class="text-lg font-bold mt-1 @if($server->docker_installed) text-green-600 dark:text-green-400 @else text-red-600 dark:text-red-400 @endif">
                        @if($server->docker_installed)
                            v{{ $server->docker_version ?? 'Installed' }}
                        @else
                            Not Installed
                        @endif
                    </p>
                </div>
                <div class="p-3 @if($server->docker_installed) bg-green-100 dark:bg-green-900/30 @else bg-red-100 dark:bg-red-900/30 @endif rounded-xl">
                    <svg class="w-6 h-6 @if($server->docker_installed) text-green-600 dark:text-green-400 @else text-red-600 dark:text-red-400 @endif" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M13.983 11.078h2.119a.186.186 0 00.186-.185V9.006a.186.186 0 00-.186-.186h-2.119a.185.185 0 00-.185.185v1.888c0 .102.083.185.185.185m-2.954-5.43h2.118a.186.186 0 00.186-.186V3.574a.186.186 0 00-.186-.185h-2.118a.185.185 0 00-.185.185v1.888c0 .102.082.185.185.186m0 2.716h2.118a.187.187 0 00.186-.186V6.29a.186.186 0 00-.186-.185h-2.118a.185.185 0 00-.185.185v1.887c0 .102.082.185.185.186m-2.93 0h2.12a.186.186 0 00.184-.186V6.29a.185.185 0 00-.185-.185H8.1a.185.185 0 00-.185.185v1.887c0 .102.083.185.185.186m-2.964 0h2.119a.186.186 0 00.185-.186V6.29a.185.185 0 00-.185-.185H5.136a.186.186 0 00-.186.185v1.887c0 .102.084.185.186.186m5.893 2.715h2.118a.186.186 0 00.186-.185V9.006a.186.186 0 00-.186-.186h-2.118a.185.185 0 00-.185.185v1.888c0 .102.082.185.185.185m-2.93 0h2.12a.185.185 0 00.184-.185V9.006a.185.185 0 00-.184-.186h-2.12a.185.185 0 00-.184.185v1.888c0 .102.083.185.185.185m-2.964 0h2.119a.185.185 0 00.185-.185V9.006a.185.185 0 00-.185-.186h-2.12a.186.186 0 00-.185.186v1.887c0 .102.084.185.186.185m-2.92 0h2.12a.185.185 0 00.184-.185V9.006a.185.185 0 00-.184-.186h-2.12a.185.185 0 00-.184.185v1.888c0 .102.082.185.185.185"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Server Details -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl transition-colors overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-750">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Server Information
                </h2>
            </div>
            <div class="p-6">
                <dl class="space-y-4">
                    <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Hostname</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white font-mono">{{ $server->hostname }}</dd>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">IP Address</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white font-mono">{{ $server->ip_address }}</dd>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">SSH Port</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white">{{ $server->port }}</dd>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Username</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white font-mono">{{ $server->username }}</dd>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Operating System</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white">{{ $server->os ?? 'Unknown' }}</dd>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Disk Space</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white">{{ $server->disk_gb ?? '-' }} GB</dd>
                    </div>
                    @if($server->location_name)
                        <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Location</dt>
                            <dd class="text-sm font-semibold text-gray-900 dark:text-white">{{ $server->location_name }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between items-center py-3">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Added</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white">{{ $server->created_at->format('M d, Y') }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Recent Metrics -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl transition-colors overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-750">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Live Metrics
                    @if($isLoading)
                        <svg class="animate-spin h-4 w-4 text-gray-400 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    @endif
                </h2>
            </div>
            <div class="p-6">
                @if($isLoading)
                    {{-- Loading Skeleton --}}
                    <div class="space-y-6 animate-pulse">
                        @for($i = 0; $i < 3; $i++)
                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <div class="h-4 w-24 bg-gray-200 dark:bg-gray-700 rounded"></div>
                                    <div class="h-4 w-10 bg-gray-200 dark:bg-gray-700 rounded"></div>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3"></div>
                            </div>
                        @endfor
                    </div>
                @elseif($recentMetrics->count() > 0)
                    @php $latestMetric = $recentMetrics->first(); @endphp
                    <div class="space-y-6">
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">CPU Usage</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $latestMetric->cpu_usage }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                <div class="h-3 rounded-full transition-all duration-500
                                    @if($latestMetric->cpu_usage > 80) bg-gradient-to-r from-red-500 to-red-600
                                    @elseif($latestMetric->cpu_usage > 60) bg-gradient-to-r from-yellow-500 to-orange-500
                                    @else bg-gradient-to-r from-blue-500 to-indigo-600
                                    @endif" style="width: {{ $latestMetric->cpu_usage }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Memory Usage</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $latestMetric->memory_usage }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                <div class="h-3 rounded-full transition-all duration-500
                                    @if($latestMetric->memory_usage > 80) bg-gradient-to-r from-red-500 to-red-600
                                    @elseif($latestMetric->memory_usage > 60) bg-gradient-to-r from-yellow-500 to-orange-500
                                    @else bg-gradient-to-r from-green-500 to-emerald-600
                                    @endif" style="width: {{ $latestMetric->memory_usage }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Disk Usage</span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $latestMetric->disk_usage }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                <div class="h-3 rounded-full transition-all duration-500
                                    @if($latestMetric->disk_usage > 80) bg-gradient-to-r from-red-500 to-red-600
                                    @elseif($latestMetric->disk_usage > 60) bg-gradient-to-r from-yellow-500 to-orange-500
                                    @else bg-gradient-to-r from-purple-500 to-pink-600
                                    @endif" style="width: {{ $latestMetric->disk_usage }}%"></div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-4">
                            Last updated: {{ $latestMetric->recorded_at->diffForHumans() }}
                        </p>
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400">No metrics available yet</p>
                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Metrics will appear after the first monitoring run</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Projects -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl transition-colors overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-750 flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                    Projects
                </h2>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $projects->count() }} total</span>
            </div>
            <div class="p-6">
                @if($projects->count() > 0)
                    <div class="space-y-3">
                        @foreach($projects as $project)
                            <a href="{{ route('projects.show', $project) }}"
                               class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors group">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm">
                                        {{ strtoupper(substr($project->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ $project->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $project->domain ?? 'No domain' }}</p>
                                    </div>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    @if($project->status === 'running') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                                    @elseif($project->status === 'stopped') bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300
                                    @elseif($project->status === 'building') bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400
                                    @elseif($project->status === 'failed') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                    @else bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400
                                    @endif">
                                    {{ ucfirst($project->status) }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400">No projects on this server</p>
                        <a href="{{ route('projects.create') }}" class="inline-flex items-center mt-4 text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            + Create a project
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Deployments -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl transition-colors overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-750">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    Recent Deployments
                </h2>
            </div>
            <div class="p-6">
                @if($deployments->count() > 0)
                    <div class="space-y-3">
                        @foreach($deployments as $deployment)
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center
                                        @if($deployment->status === 'success') bg-green-100 dark:bg-green-900/30
                                        @elseif($deployment->status === 'failed') bg-red-100 dark:bg-red-900/30
                                        @else bg-yellow-100 dark:bg-yellow-900/30
                                        @endif">
                                        @if($deployment->status === 'success')
                                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        @elseif($deployment->status === 'failed')
                                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $deployment->project->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $deployment->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    @if($deployment->status === 'success') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                                    @elseif($deployment->status === 'failed') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                    @else bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400
                                    @endif">
                                    {{ ucfirst($deployment->status) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400">No deployments yet</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Server Tags Assignment -->
    <div class="mb-8">
        <livewire:servers.server-tag-assignment :server="$server" />
    </div>

    <!-- SSH Terminal Section -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl transition-colors overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-900 to-slate-800">
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                SSH Terminal
            </h2>
            <p class="text-sm text-gray-400 mt-1">Execute commands directly on {{ $server->name }}</p>
        </div>
        <div class="p-6">
            <livewire:servers.s-s-h-terminal :server="$server" />
        </div>
    </div>
</div>
