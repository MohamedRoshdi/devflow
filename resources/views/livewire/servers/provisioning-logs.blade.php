<div>
    <!-- Hero Section -->
    <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-indigo-800 via-purple-900 to-indigo-800 p-8 shadow-2xl overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="provisioning-pattern" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                        <rect x="0" y="0" width="4" height="4" fill="currentColor" class="text-white"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#provisioning-pattern)"/>
            </svg>
        </div>

        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex items-start gap-4">
                    <!-- Icon -->
                    <div class="p-4 bg-white/10 backdrop-blur-md rounded-2xl">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>

                    <div>
                        <h1 class="text-3xl font-bold text-white">Server Provisioning</h1>
                        <p class="text-white/80 mt-2">{{ $server->name }} — Configure &amp; Deploy</p>
                        <div class="flex flex-wrap items-center gap-3 mt-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/10 text-white/90">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                                </svg>
                                {{ $server->ip_address }}
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/10 text-white/90">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $this->stats['total'] }} Total Logs
                            </span>
                            @if($server->provision_status)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                    {{ match($server->provision_status) {
                                        'completed' => 'bg-green-500/20 text-green-300 border border-green-500/30',
                                        'provisioning' => 'bg-blue-500/20 text-blue-300 border border-blue-500/30',
                                        'failed' => 'bg-red-500/20 text-red-300 border border-red-500/30',
                                        default => 'bg-white/10 text-white/80',
                                    } }}">
                                    {{ ucfirst($server->provision_status) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    @if(!$showProvisioningForm)
                        <button wire:click="$set('showProvisioningForm', true)"
                                class="px-4 py-2.5 bg-purple-600 hover:bg-purple-500 text-white rounded-xl transition-all duration-200 font-medium flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            {{ $server->isProvisioned() ? 'Re-provision' : 'Start Provisioning' }}
                        </button>
                    @endif
                    <a href="{{ route('servers.show', $server) }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-all duration-200 font-medium flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Server
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <!-- Total -->
        <div class="bg-gray-800 rounded-xl shadow-lg p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-400">Total Logs</p>
                    <p class="text-2xl font-bold text-white mt-1">{{ $this->stats['total'] }}</p>
                </div>
                <div class="p-3 bg-gray-700 rounded-xl">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Completed -->
        <div class="bg-gray-800 rounded-xl shadow-lg p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-400">Completed</p>
                    <p class="text-2xl font-bold text-green-400 mt-1">{{ $this->stats['completed'] }}</p>
                </div>
                <div class="p-3 bg-green-900/30 rounded-xl">
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Failed -->
        <div class="bg-gray-800 rounded-xl shadow-lg p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-400">Failed</p>
                    <p class="text-2xl font-bold text-red-400 mt-1">{{ $this->stats['failed'] }}</p>
                </div>
                <div class="p-3 bg-red-900/30 rounded-xl">
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Running -->
        <div class="bg-gray-800 rounded-xl shadow-lg p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-400">Running</p>
                    <p class="text-2xl font-bold text-blue-400 mt-1">{{ $this->stats['running'] }}</p>
                </div>
                <div class="p-3 bg-blue-900/30 rounded-xl">
                    <svg class="w-6 h-6 text-blue-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Avg Duration -->
        <div class="bg-gray-800 rounded-xl shadow-lg p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-400">Avg Duration</p>
                    <p class="text-2xl font-bold text-purple-400 mt-1">
                        @if($this->stats['avg_duration'])
                            {{ number_format($this->stats['avg_duration'], 0) }}s
                        @else
                            -
                        @endif
                    </p>
                </div>
                <div class="p-3 bg-purple-900/30 rounded-xl">
                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- ====================================================
         Provisioning Wizard Form
    ===================================================== --}}
    @if($showProvisioningForm)
        <div class="bg-gray-800 border border-gray-700 rounded-2xl shadow-xl mb-8 overflow-hidden">
            <!-- Form Header -->
            <div class="px-6 py-5 border-b border-gray-700 bg-gradient-to-r from-purple-900/40 to-indigo-900/40 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-purple-500/20 rounded-lg">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-white">Provision Server</h2>
                        <p class="text-sm text-gray-400">Install packages and configure your server</p>
                    </div>
                </div>
                @if($this->stats['total'] > 0)
                    <button wire:click="$set('showProvisioningForm', false)"
                            class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                @endif
            </div>

            <div class="px-6 py-6 space-y-8">
                {{-- Package Selection --}}
                <div>
                    <h3 class="text-sm font-semibold text-gray-300 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        Packages to Install
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        {{-- PHP --}}
                        <label class="flex items-center gap-3 p-4 bg-gray-900 border border-gray-700 rounded-xl cursor-pointer hover:border-purple-500/50 hover:bg-gray-900/80 transition-all group">
                            <input type="checkbox" wire:model.live="installPHP"
                                   class="w-4 h-4 rounded text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 focus:ring-offset-gray-900">
                            <div class="flex items-center gap-2 flex-1">
                                <span class="text-lg">🐘</span>
                                <div>
                                    <span class="text-sm font-medium text-white">PHP</span>
                                    <span class="text-xs text-gray-400 ml-1">(version below)</span>
                                </div>
                            </div>
                        </label>

                        {{-- Nginx --}}
                        <label class="flex items-center gap-3 p-4 bg-gray-900 border border-gray-700 rounded-xl cursor-pointer hover:border-purple-500/50 hover:bg-gray-900/80 transition-all group">
                            <input type="checkbox" wire:model.live="installNginx"
                                   class="w-4 h-4 rounded text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 focus:ring-offset-gray-900">
                            <div class="flex items-center gap-2 flex-1">
                                <span class="text-lg">🌐</span>
                                <div>
                                    <span class="text-sm font-medium text-white">Nginx</span>
                                    <span class="text-xs text-gray-500 block">Web server</span>
                                </div>
                            </div>
                        </label>

                        {{-- PostgreSQL --}}
                        <label class="flex items-center gap-3 p-4 bg-gray-900 border border-gray-700 rounded-xl cursor-pointer hover:border-purple-500/50 hover:bg-gray-900/80 transition-all group">
                            <input type="checkbox" wire:model.live="installPostgreSQL"
                                   class="w-4 h-4 rounded text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 focus:ring-offset-gray-900">
                            <div class="flex items-center gap-2 flex-1">
                                <span class="text-lg">🐘</span>
                                <div>
                                    <span class="text-sm font-medium text-white">PostgreSQL 16</span>
                                    <span class="text-xs text-gray-500 block">Relational database</span>
                                </div>
                            </div>
                        </label>

                        {{-- MySQL --}}
                        <label class="flex items-center gap-3 p-4 bg-gray-900 border border-gray-700 rounded-xl cursor-pointer hover:border-purple-500/50 hover:bg-gray-900/80 transition-all group">
                            <input type="checkbox" wire:model.live="installMySQL"
                                   class="w-4 h-4 rounded text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 focus:ring-offset-gray-900">
                            <div class="flex items-center gap-2 flex-1">
                                <span class="text-lg">🗄️</span>
                                <div>
                                    <span class="text-sm font-medium text-white">MySQL</span>
                                    <span class="text-xs text-gray-500 block">Relational database</span>
                                </div>
                            </div>
                        </label>

                        {{-- Redis --}}
                        <label class="flex items-center gap-3 p-4 bg-gray-900 border border-gray-700 rounded-xl cursor-pointer hover:border-purple-500/50 hover:bg-gray-900/80 transition-all group">
                            <input type="checkbox" wire:model.live="installRedis"
                                   class="w-4 h-4 rounded text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 focus:ring-offset-gray-900">
                            <div class="flex items-center gap-2 flex-1">
                                <span class="text-lg">⚡</span>
                                <div>
                                    <span class="text-sm font-medium text-white">Redis</span>
                                    <span class="text-xs text-gray-500 block">In-memory cache</span>
                                </div>
                            </div>
                        </label>

                        {{-- Composer --}}
                        <label class="flex items-center gap-3 p-4 bg-gray-900 border border-gray-700 rounded-xl cursor-pointer hover:border-purple-500/50 hover:bg-gray-900/80 transition-all group">
                            <input type="checkbox" wire:model.live="installComposer"
                                   class="w-4 h-4 rounded text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 focus:ring-offset-gray-900">
                            <div class="flex items-center gap-2 flex-1">
                                <span class="text-lg">🎼</span>
                                <div>
                                    <span class="text-sm font-medium text-white">Composer</span>
                                    <span class="text-xs text-gray-500 block">PHP dependency manager</span>
                                </div>
                            </div>
                        </label>

                        {{-- Node.js --}}
                        <label class="flex items-center gap-3 p-4 bg-gray-900 border border-gray-700 rounded-xl cursor-pointer hover:border-purple-500/50 hover:bg-gray-900/80 transition-all group">
                            <input type="checkbox" wire:model.live="installNodeJS"
                                   class="w-4 h-4 rounded text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 focus:ring-offset-gray-900">
                            <div class="flex items-center gap-2 flex-1">
                                <span class="text-lg">⬢</span>
                                <div>
                                    <span class="text-sm font-medium text-white">Node.js</span>
                                    <span class="text-xs text-gray-400 ml-1">(version below)</span>
                                </div>
                            </div>
                        </label>

                        {{-- Firewall --}}
                        <label class="flex items-center gap-3 p-4 bg-gray-900 border border-gray-700 rounded-xl cursor-pointer hover:border-purple-500/50 hover:bg-gray-900/80 transition-all group">
                            <input type="checkbox" wire:model.live="configureFirewall"
                                   class="w-4 h-4 rounded text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 focus:ring-offset-gray-900">
                            <div class="flex items-center gap-2 flex-1">
                                <span class="text-lg">🔥</span>
                                <div>
                                    <span class="text-sm font-medium text-white">UFW Firewall</span>
                                    <span class="text-xs text-gray-500 block">Opens ports 22, 80, 443</span>
                                </div>
                            </div>
                        </label>

                        {{-- Swap --}}
                        <label class="flex items-center gap-3 p-4 bg-gray-900 border border-gray-700 rounded-xl cursor-pointer hover:border-purple-500/50 hover:bg-gray-900/80 transition-all group">
                            <input type="checkbox" wire:model.live="setupSwap"
                                   class="w-4 h-4 rounded text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 focus:ring-offset-gray-900">
                            <div class="flex items-center gap-2 flex-1">
                                <span class="text-lg">💾</span>
                                <div>
                                    <span class="text-sm font-medium text-white">Swap File</span>
                                    <span class="text-xs text-gray-400 ml-1">(size below)</span>
                                </div>
                            </div>
                        </label>

                        {{-- SSH Hardening --}}
                        <label class="flex items-center gap-3 p-4 bg-gray-900 border border-gray-700 rounded-xl cursor-pointer hover:border-purple-500/50 hover:bg-gray-900/80 transition-all group">
                            <input type="checkbox" wire:model.live="secureSSH"
                                   class="w-4 h-4 rounded text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 focus:ring-offset-gray-900">
                            <div class="flex items-center gap-2 flex-1">
                                <span class="text-lg">🔐</span>
                                <div>
                                    <span class="text-sm font-medium text-white">SSH Hardening</span>
                                    <span class="text-xs text-gray-500 block">Disable password auth</span>
                                </div>
                            </div>
                        </label>

                        {{-- Supervisor --}}
                        <label class="flex items-center gap-3 p-4 bg-gray-900 border border-gray-700 rounded-xl cursor-pointer hover:border-purple-500/50 hover:bg-gray-900/80 transition-all group">
                            <input type="checkbox" wire:model.live="installSupervisor"
                                   class="w-4 h-4 rounded text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 focus:ring-offset-gray-900">
                            <div class="flex items-center gap-2 flex-1">
                                <span class="text-lg">⚙️</span>
                                <div>
                                    <span class="text-sm font-medium text-white">Supervisor</span>
                                    <span class="text-xs text-gray-500 block">Process manager</span>
                                </div>
                            </div>
                        </label>

                        {{-- FrankenPHP / Octane --}}
                        <label class="flex items-center gap-3 p-4 bg-gray-900 border border-gray-700 rounded-xl cursor-pointer hover:border-purple-500/50 hover:bg-gray-900/80 transition-all group">
                            <input type="checkbox" wire:model.live="installFrankenphp"
                                   class="w-4 h-4 rounded text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 focus:ring-offset-gray-900">
                            <div class="flex items-center gap-2 flex-1">
                                <span class="text-lg">🚀</span>
                                <div>
                                    <span class="text-sm font-medium text-white">FrankenPHP</span>
                                    <span class="text-xs text-gray-500 block">Laravel Octane</span>
                                </div>
                            </div>
                        </label>

                        {{-- Fail2ban --}}
                        <label class="flex items-center gap-3 p-4 bg-gray-900 border border-gray-700 rounded-xl cursor-pointer hover:border-purple-500/50 hover:bg-gray-900/80 transition-all group">
                            <input type="checkbox" wire:model.live="installFail2ban"
                                   class="w-4 h-4 rounded text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 focus:ring-offset-gray-900">
                            <div class="flex items-center gap-2 flex-1">
                                <span class="text-lg">🛡️</span>
                                <div>
                                    <span class="text-sm font-medium text-white">Fail2ban</span>
                                    <span class="text-xs text-gray-500 block">Intrusion prevention</span>
                                </div>
                            </div>
                        </label>

                        {{-- Wildcard Subdomain Routing --}}
                        <label class="flex items-center gap-3 p-4 bg-gray-900 border border-gray-700 rounded-xl cursor-pointer hover:border-purple-500/50 hover:bg-gray-900/80 transition-all group col-span-2">
                            <input type="checkbox" wire:model.live="configureWildcardNginx"
                                   class="w-4 h-4 rounded text-purple-600 bg-gray-700 border-gray-600 focus:ring-purple-500 focus:ring-offset-gray-900">
                            <div class="flex items-center gap-2 flex-1">
                                <span class="text-lg">🌐</span>
                                <div>
                                    <span class="text-sm font-medium text-white">Wildcard Subdomain Routing</span>
                                    <span class="text-xs text-gray-500 block">*.domain.com → Octane reverse proxy</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Conditional Config Panels --}}
                @if($installSupervisor)
                <div class="p-4 border border-purple-500/30 rounded-xl bg-purple-500/5">
                    <h4 class="text-sm font-medium text-purple-300 mb-3">Queue Worker Configuration</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Worker Count</label>
                            <input type="number" wire:model="queueWorkerCount" min="1" max="16"
                                   class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Queue Names</label>
                            <input type="text" wire:model="queueNames" placeholder="default,emails"
                                   class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white">
                        </div>
                    </div>
                </div>
                @endif

                @if($installFrankenphp)
                <div class="p-4 border border-orange-500/30 rounded-xl bg-orange-500/5">
                    <h4 class="text-sm font-medium text-orange-300 mb-3">Octane / FrankenPHP Settings</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Workers</label>
                            <input type="number" wire:model="octaneWorkers" min="1" max="64"
                                   class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Port</label>
                            <input type="number" wire:model="octanePort" min="1024" max="65535"
                                   class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white">
                        </div>
                    </div>
                </div>
                @endif

                @if($configureWildcardNginx)
                <div class="p-4 border border-cyan-500/30 rounded-xl bg-cyan-500/5">
                    <h4 class="text-sm font-medium text-cyan-300 mb-3">Wildcard Subdomain Configuration</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Base Domain</label>
                            <input type="text" wire:model="wildcardDomain" placeholder="store-eg.com"
                                   class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Project Path</label>
                            <input type="text" wire:model="wildcardProjectPath" placeholder="/var/www/e-store"
                                   class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Octane Port</label>
                            <input type="number" wire:model="octanePort" min="1024" max="65535"
                                   class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white">
                        </div>
                    </div>
                </div>
                @endif

                @if($installPostgreSQL && count($additionalDatabases) > 0 || $installPostgreSQL)
                <div class="p-4 border border-blue-500/30 rounded-xl bg-blue-500/5">
                    <h4 class="text-sm font-medium text-blue-300 mb-3">Additional Databases</h4>
                    @if(count($additionalDatabases) > 0)
                    <div class="flex flex-wrap gap-2 mb-3">
                        @foreach($additionalDatabases as $index => $db)
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-500/20 border border-blue-500/30 rounded-full text-sm text-blue-300">
                            {{ $db }}
                            <button type="button" wire:click="removeAdditionalDatabase({{ $index }})" class="text-blue-400 hover:text-red-400">&times;</button>
                        </span>
                        @endforeach
                    </div>
                    @endif
                    <div class="flex gap-2">
                        <input type="text" wire:model="newAdditionalDatabase" wire:keydown.enter.prevent="addAdditionalDatabase"
                               placeholder="e.g. lebsa, general" class="flex-1 bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white">
                        <button type="button" wire:click="addAdditionalDatabase"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors">Add</button>
                    </div>
                </div>
                @endif

                {{-- Version Configuration --}}
                <div>
                    <h3 class="text-sm font-semibold text-gray-300 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Version &amp; Size Options
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        {{-- PHP Version --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                PHP Version
                                @if(!$installPHP)
                                    <span class="text-xs text-gray-500 font-normal">(PHP not selected)</span>
                                @endif
                            </label>
                            <select wire:model.blur="phpVersion"
                                    @disabled(!$installPHP)
                                    class="w-full px-3 py-2.5 bg-gray-900 border border-gray-700 rounded-lg text-white text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                <option value="8.1">PHP 8.1</option>
                                <option value="8.2">PHP 8.2</option>
                                <option value="8.3">PHP 8.3</option>
                                <option value="8.4">PHP 8.4</option>
                            </select>
                            @error('phpVersion')
                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Node.js Version --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                Node.js Version
                                @if(!$installNodeJS)
                                    <span class="text-xs text-gray-500 font-normal">(Node.js not selected)</span>
                                @endif
                            </label>
                            <select wire:model.blur="nodeVersion"
                                    @disabled(!$installNodeJS)
                                    class="w-full px-3 py-2.5 bg-gray-900 border border-gray-700 rounded-lg text-white text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                <option value="18">Node.js 18 LTS</option>
                                <option value="20">Node.js 20 LTS</option>
                                <option value="22">Node.js 22 Current</option>
                            </select>
                            @error('nodeVersion')
                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Swap Size --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                Swap Size (GB)
                                @if(!$setupSwap)
                                    <span class="text-xs text-gray-500 font-normal">(Swap not selected)</span>
                                @endif
                            </label>
                            <input type="number" wire:model.blur="swapSizeGB" min="1" max="32"
                                   @disabled(!$setupSwap)
                                   class="w-full px-3 py-2.5 bg-gray-900 border border-gray-700 rounded-lg text-white text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            @error('swapSizeGB')
                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Database & Service Credentials --}}
                @if($installPostgreSQL || $installMySQL || $installRedis)
                    <div>
                        <h3 class="text-sm font-semibold text-gray-300 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                            </svg>
                            Credentials &amp; Configuration
                        </h3>
                        <div class="space-y-5">
                            {{-- PostgreSQL --}}
                            @if($installPostgreSQL)
                                <div class="p-5 bg-gray-900/60 border border-gray-700 rounded-xl space-y-4">
                                    <h4 class="text-sm font-semibold text-blue-400 flex items-center gap-2">
                                        <span>🐘</span> PostgreSQL Configuration
                                    </h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                                            <input type="password" wire:model.blur="postgresqlPassword"
                                                   class="w-full px-3 py-2.5 bg-gray-800 border border-gray-600 rounded-lg text-white text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                                                   placeholder="PostgreSQL password">
                                            @error('postgresqlPassword')
                                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-300 mb-2">Databases</label>
                                            <input type="text" wire:model.blur="postgresqlDatabases"
                                                   class="w-full px-3 py-2.5 bg-gray-800 border border-gray-600 rounded-lg text-white text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                                                   placeholder="app_db, admin_db (comma-separated)">
                                            <p class="mt-1 text-xs text-gray-500">Leave empty to create databases later</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- MySQL --}}
                            @if($installMySQL)
                                <div class="p-5 bg-gray-900/60 border border-gray-700 rounded-xl">
                                    <h4 class="text-sm font-semibold text-orange-400 flex items-center gap-2 mb-4">
                                        <span>🗄️</span> MySQL Configuration
                                    </h4>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">Root Password</label>
                                        <input type="password" wire:model.blur="mysqlPassword"
                                               class="w-full px-3 py-2.5 bg-gray-800 border border-gray-600 rounded-lg text-white text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                                               placeholder="MySQL root password">
                                        @error('mysqlPassword')
                                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            @endif

                            {{-- Redis --}}
                            @if($installRedis)
                                <div class="p-5 bg-gray-900/60 border border-gray-700 rounded-xl space-y-4">
                                    <h4 class="text-sm font-semibold text-red-400 flex items-center gap-2">
                                        <span>⚡</span> Redis Configuration
                                    </h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-300 mb-2">Password <span class="text-gray-500 font-normal">(optional)</span></label>
                                            <input type="password" wire:model.blur="redisPassword"
                                                   class="w-full px-3 py-2.5 bg-gray-800 border border-gray-600 rounded-lg text-white text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                                                   placeholder="Leave empty for no auth">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-300 mb-2">Max Memory (MB)</label>
                                            <input type="number" wire:model.blur="redisMaxMemoryMB" min="64" max="8192"
                                                   class="w-full px-3 py-2.5 bg-gray-800 border border-gray-600 rounded-lg text-white text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                                            @error('redisMaxMemoryMB')
                                                <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Warning notice for SSH hardening --}}
                @if($secureSSH)
                    <div class="flex items-start gap-3 p-4 bg-amber-500/10 border border-amber-500/30 rounded-xl">
                        <svg class="w-5 h-5 text-amber-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <p class="text-sm text-amber-300">
                            <strong>SSH Hardening</strong> will disable password authentication and restrict root login.
                            Ensure your SSH key is already added to the server before proceeding.
                        </p>
                    </div>
                @endif

                {{-- Submit Button --}}
                <div class="pt-2">
                    <button wire:click="startProvisioning"
                            wire:loading.attr="disabled"
                            class="w-full py-3.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-semibold rounded-xl transition-all duration-200 shadow-lg shadow-purple-900/30 disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-3 text-base">
                        <svg wire:loading.remove wire:target="startProvisioning" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z"></path>
                        </svg>
                        <svg wire:loading wire:target="startProvisioning" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="startProvisioning">Start Provisioning</span>
                        <span wire:loading wire:target="startProvisioning">Starting provisioning...</span>
                    </button>
                    <p class="mt-2 text-center text-xs text-gray-500">
                        Provisioning runs in the background and may take 5–15 minutes depending on selected packages.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- ====================================================
         Provisioning Timeline / Logs
    ===================================================== --}}
    <div class="bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
        <!-- Header with Filters -->
        <div class="p-6 border-b border-gray-700 bg-gray-800">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Provisioning Timeline
                </h2>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Status Filter -->
                    <select wire:model.live="statusFilter"
                            class="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-sm text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="running">Running</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                    </select>

                    <!-- Date Range Filter -->
                    <select wire:model.live="dateRange"
                            class="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-sm text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all">
                        <option value="all">All Time</option>
                        <option value="today">Today</option>
                        <option value="week">Last Week</option>
                        <option value="month">Last Month</option>
                    </select>

                    <!-- Reset Button -->
                    @if($statusFilter !== 'all' || $dateRange !== 'all')
                        <button wire:click="resetFilters"
                                class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Reset
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Logs Timeline -->
        <div class="p-6">
            @if($this->logs->count() > 0)
                <div class="space-y-4">
                    @foreach($this->logs as $log)
                        <div class="relative">
                            <!-- Timeline Line (except for last item) -->
                            @if(!$loop->last)
                                <div class="absolute left-6 top-14 bottom-0 w-0.5 bg-gray-700"></div>
                            @endif

                            <!-- Log Entry -->
                            <div class="relative flex gap-4 group">
                                <!-- Status Icon -->
                                <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center z-10 shadow-lg
                                    {{ $log->getStatusBadgeClass() }} border">
                                    @if($log->isCompleted())
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    @elseif($log->isFailed())
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    @elseif($log->isRunning())
                                        <svg class="w-6 h-6 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                    @else
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    @endif
                                </div>

                                <!-- Log Content -->
                                <div class="flex-1 bg-gray-700/50 rounded-xl p-5 border border-gray-700 hover:shadow-lg transition-all duration-200">
                                    <div class="flex items-start justify-between gap-4 mb-3">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-white mb-1">
                                                {{ str_replace('_', ' ', ucfirst($log->task)) }}
                                            </h3>
                                            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-400">
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                    {{ $log->created_at->format('M d, Y') }}
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    {{ $log->created_at->format('h:i A') }}
                                                </span>
                                                @if($log->duration_seconds)
                                                    <span class="flex items-center gap-1 font-semibold text-purple-400">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                        </svg>
                                                        {{ $log->duration_seconds }}s
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <!-- Status Badge -->
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border
                                                {{ $log->getStatusBadgeClass() }}">
                                                {{ ucfirst($log->status) }}
                                            </span>

                                            <!-- Expand Button -->
                                            @if($log->output || $log->error_message)
                                                <button wire:click="toggleLogExpansion({{ $log->id }})"
                                                        class="p-2 rounded-lg hover:bg-gray-600 transition-all">
                                                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-200
                                                        {{ $expandedLogId === $log->id ? 'rotate-180' : '' }}"
                                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Error Message Preview (if failed) -->
                                    @if($log->error_message && $expandedLogId !== $log->id)
                                        <div class="mt-3 p-3 bg-red-900/20 border border-red-800 rounded-lg">
                                            <p class="text-sm text-red-400 font-mono">
                                                {{ Str::limit($log->error_message, 150) }}
                                            </p>
                                        </div>
                                    @endif

                                    <!-- Expanded Output -->
                                    @if($expandedLogId === $log->id)
                                        <div class="mt-4 space-y-3">
                                            @if($log->error_message)
                                                <div>
                                                    <h4 class="text-sm font-semibold text-red-400 mb-2 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                        </svg>
                                                        Error Message
                                                    </h4>
                                                    <div class="p-4 bg-red-900/20 border border-red-800 rounded-lg">
                                                        <pre class="text-sm text-red-400 font-mono whitespace-pre-wrap break-words">{{ $log->error_message }}</pre>
                                                    </div>
                                                </div>
                                            @endif

                                            @if($log->output)
                                                <div>
                                                    <h4 class="text-sm font-semibold text-gray-300 mb-2 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                        </svg>
                                                        Command Output
                                                    </h4>
                                                    <div class="p-4 bg-gray-950 border border-gray-700 rounded-lg max-h-96 overflow-y-auto">
                                                        <pre class="text-sm text-green-400 font-mono whitespace-pre-wrap break-words">{{ $log->output }}</pre>
                                                    </div>
                                                </div>
                                            @endif

                                            <!-- Execution Details -->
                                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                                                @if($log->started_at)
                                                    <div class="p-3 bg-blue-900/20 rounded-lg">
                                                        <p class="text-blue-400 font-medium mb-1">Started At</p>
                                                        <p class="text-white font-mono">{{ $log->started_at->format('Y-m-d H:i:s') }}</p>
                                                    </div>
                                                @endif

                                                @if($log->completed_at)
                                                    <div class="p-3 bg-green-900/20 rounded-lg">
                                                        <p class="text-green-400 font-medium mb-1">Completed At</p>
                                                        <p class="text-white font-mono">{{ $log->completed_at->format('Y-m-d H:i:s') }}</p>
                                                    </div>
                                                @endif

                                                @if($log->duration_seconds)
                                                    <div class="p-3 bg-purple-900/20 rounded-lg">
                                                        <p class="text-purple-400 font-medium mb-1">Duration</p>
                                                        <p class="text-white font-mono">{{ $log->duration_seconds }} seconds</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $this->logs->links() }}
                </div>
            @else
                <!-- Empty State -->
                <div class="text-center py-16">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gray-700 mb-4">
                        <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2">No Provisioning Logs Found</h3>
                    <p class="text-gray-400 mb-6">
                        @if($statusFilter !== 'all' || $dateRange !== 'all')
                            No logs match your current filters. Try adjusting your filters.
                        @else
                            This server hasn't been provisioned yet. Use the form above to get started.
                        @endif
                    </p>
                    @if($statusFilter !== 'all' || $dateRange !== 'all')
                        <button wire:click="resetFilters"
                                class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium transition-all inline-flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Reset Filters
                        </button>
                    @elseif(!$showProvisioningForm)
                        <button wire:click="$set('showProvisioningForm', true)"
                                class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-medium transition-all inline-flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z"></path>
                            </svg>
                            Start Provisioning
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
