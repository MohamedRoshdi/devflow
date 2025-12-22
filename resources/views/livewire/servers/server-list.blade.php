<div x-data="{ bulkDropdownOpen: false, servicesDropdownOpen: false }" class="min-h-screen">
    {{-- Animated Background Orbs --}}
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-gradient-to-br from-blue-500/5 via-indigo-500/5 to-purple-500/5 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-gradient-to-tr from-cyan-500/5 via-blue-500/5 to-indigo-500/5 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-gradient-to-r from-blue-500/3 to-purple-500/3 rounded-full blur-3xl"></div>
    </div>

    {{-- Hero Header --}}
    <div class="relative mb-8">
        <div class="bg-gradient-to-br from-slate-100 via-white to-slate-100 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 rounded-3xl overflow-hidden border border-slate-200 dark:border-slate-700/50 shadow-2xl">
            {{-- Grid Pattern Overlay --}}
            <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23fff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>

            <div class="relative p-6 lg:p-8">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                    <div class="flex items-start gap-4">
                        {{-- Animated Logo --}}
                        <div class="relative">
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-400 via-indigo-500 to-purple-600 flex items-center justify-center shadow-xl shadow-indigo-500/30 transform hover:scale-105 transition-transform" aria-hidden="true">
                                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-label="Server icon">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"></path>
                                </svg>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <h1 class="text-2xl lg:text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Server Management</h1>
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-blue-500/20 text-blue-400 border border-blue-500/30">
                                    {{ $servers->total() }} Servers
                                </span>
                            </div>
                            <p class="text-slate-600 dark:text-slate-400 text-sm">Manage your server infrastructure</p>

                            {{-- Quick Stats Pills --}}
                            <div class="flex flex-wrap items-center gap-2 mt-3">
                                @php
                                    $onlineCount = $servers->where('status', 'online')->count();
                                    $offlineCount = $servers->where('status', 'offline')->count();
                                    $maintenanceCount = $servers->where('status', 'maintenance')->count();
                                @endphp
                                @if($onlineCount > 0)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-500/20 text-emerald-400 border border-emerald-500/30" role="status" aria-label="{{ $onlineCount }} servers online">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" aria-hidden="true"></span>
                                    {{ $onlineCount }} Online
                                </span>
                                @endif
                                @if($offlineCount > 0)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-red-500/20 text-red-400 border border-red-500/30" role="status" aria-label="{{ $offlineCount }} servers offline">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-400" aria-hidden="true"></span>
                                    {{ $offlineCount }} Offline
                                </span>
                                @endif
                                @if($maintenanceCount > 0)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-amber-500/20 text-amber-400 border border-amber-500/30" role="status" aria-label="{{ $maintenanceCount }} servers in maintenance mode">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    {{ $maintenanceCount }} Maintenance
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex flex-wrap gap-2">
                        <button wire:click="pingAllServers"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                wire:target="pingAllServers"
                                aria-label="Ping all servers to check connectivity"
                                class="group relative inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm text-white overflow-hidden transition-all duration-300 hover:-translate-y-0.5 disabled:hover:translate-y-0"
                                style="background: linear-gradient(135deg, #10b981 0%, #14b8a6 50%, #06b6d4 100%);">
                            <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/25 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700" aria-hidden="true"></div>
                            <span wire:loading.remove wire:target="pingAllServers" class="relative z-10 inline-flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M5.63 18.37A9 9 0 1118.37 5.63L19 6M5 19l.63-.63"></path>
                                </svg>
                                Ping All
                            </span>
                            <span wire:loading wire:target="pingAllServers" class="relative z-10 inline-flex items-center gap-2" role="status" aria-live="polite">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24" role="img" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Pinging...
                            </span>
                        </button>
                        <button wire:click="addCurrentServer"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                wire:target="addCurrentServer"
                                aria-label="Add current server to the management system"
                                class="group relative inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm text-slate-700 dark:text-white overflow-hidden transition-all duration-300 hover:-translate-y-0.5 disabled:hover:translate-y-0 bg-slate-200/50 dark:bg-slate-700/50 backdrop-blur-sm border border-slate-300/50 dark:border-slate-600/50 hover:border-slate-400/50 dark:hover:border-slate-500/50">
                            <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700" aria-hidden="true"></div>
                            <span wire:loading.remove wire:target="addCurrentServer" class="relative z-10">+ Add Current Server</span>
                            <span wire:loading wire:target="addCurrentServer" class="relative z-10 inline-flex items-center gap-2" role="status" aria-live="polite">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24" role="img" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Adding...
                            </span>
                        </button>
                        <a href="{{ route('servers.create') }}"
                           aria-label="Add a new server"
                           class="group relative inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm text-white overflow-hidden transition-all duration-300 hover:-translate-y-0.5"
                           style="background: linear-gradient(135deg, #3b82f6 0%, #6366f1 50%, #8b5cf6 100%);">
                            <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/25 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700" aria-hidden="true"></div>
                            <svg class="w-4 h-4 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span class="relative z-10">Add Server</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Actions Bar (Sticky when servers selected) -->
    @if(count($selectedServers) > 0)
        <div class="sticky top-0 z-40 mb-6 bg-white/80 dark:bg-slate-800/80 backdrop-blur-md rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700/50 p-4">
            {{-- Mobile: Stack vertically --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-slate-900 dark:text-white font-semibold whitespace-nowrap">{{ count($selectedServers) }} server(s) selected</span>
                    </div>
                    <button wire:click="clearSelection"
                            aria-label="Clear all selected servers"
                            class="text-sm text-slate-400 hover:text-white transition-colors underline whitespace-nowrap">
                        Clear
                    </button>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Loading Indicator -->
                    @if($bulkActionInProgress)
                        <div class="flex items-center space-x-2 text-amber-400" role="status" aria-live="polite" aria-label="Bulk action in progress">
                            <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24" role="img" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm font-medium">Processing...</span>
                        </div>
                    @endif

                    <!-- Bulk Actions Dropdown -->
                    <div class="relative flex-shrink-0" x-data="{ open: false }">
                        <button @click="open = !open"
                                @click.away="open = false"
                                :disabled="$wire.bulkActionInProgress"
                                aria-label="Bulk actions menu"
                                aria-haspopup="true"
                                :aria-expanded="open"
                                class="group relative inline-flex items-center gap-2 px-4 sm:px-5 py-2.5 rounded-xl font-semibold text-sm text-white overflow-hidden transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed w-full sm:w-auto justify-center"
                                style="background: linear-gradient(135deg, #3b82f6 0%, #6366f1 100%);">
                            <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/25 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700" aria-hidden="true"></div>
                            <svg class="w-5 h-5 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <span class="relative z-10">Bulk Actions</span>
                            <svg class="w-4 h-4 relative z-10 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <!-- Dropdown Menu - Mobile-friendly positioning -->
                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             role="menu"
                             aria-label="Bulk actions menu"
                             class="absolute right-0 sm:right-0 left-0 sm:left-auto mt-2 w-full sm:w-64 bg-slate-800/95 backdrop-blur-sm rounded-xl shadow-2xl border border-slate-700/50 overflow-hidden z-50 max-h-[60vh] overflow-y-auto">

                            <!-- Ping Selected -->
                            <button wire:click="bulkPing"
                                    @click="open = false"
                                    role="menuitem"
                                    aria-label="Ping all selected servers"
                                    class="w-full text-left px-4 py-3 hover:bg-slate-700/50 transition-colors flex items-center space-x-3">
                                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                                </svg>
                                <span class="text-white font-medium">Ping Selected</span>
                            </button>

                            <!-- Reboot Selected -->
                            <button wire:click="bulkReboot"
                                    wire:confirm="Are you sure you want to reboot {{ count($selectedServers) }} server(s)? All running services will be interrupted."
                                    @click="open = false"
                                    role="menuitem"
                                    aria-label="Reboot all selected servers"
                                    class="w-full text-left px-4 py-3 hover:bg-slate-700/50 transition-colors flex items-center space-x-3 border-t border-slate-700/50">
                                <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span class="text-white font-medium">Reboot Selected</span>
                            </button>

                            <!-- Install Docker -->
                            <button wire:click="bulkInstallDocker"
                                    @click="open = false"
                                    role="menuitem"
                                    aria-label="Install Docker on all selected servers"
                                    class="w-full text-left px-4 py-3 hover:bg-slate-700/50 transition-colors flex items-center space-x-3 border-t border-slate-700/50">
                                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                <span class="text-white font-medium">Install Docker</span>
                            </button>

                            <!-- Restart Service Submenu -->
                            <div class="border-t border-slate-700/50">
                                <div class="relative" x-data="{ servicesOpen: false }">
                                    <button @click="servicesOpen = !servicesOpen"
                                            class="w-full text-left px-4 py-3 hover:bg-slate-700/50 transition-colors flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                            <span class="text-white font-medium">Restart Service</span>
                                        </div>
                                        <svg class="w-4 h-4 text-slate-400" :class="{'rotate-90': servicesOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </button>

                                    <!-- Services Submenu -->
                                    <div x-show="servicesOpen"
                                         x-transition
                                         class="bg-slate-900/50">
                                        <button wire:click="bulkRestartService('nginx')"
                                                @click="open = false; servicesOpen = false"
                                                class="w-full text-left px-8 py-2.5 hover:bg-slate-700/50 transition-colors text-slate-300 text-sm">
                                            Nginx
                                        </button>
                                        <button wire:click="bulkRestartService('mysql')"
                                                @click="open = false; servicesOpen = false"
                                                class="w-full text-left px-8 py-2.5 hover:bg-slate-700/50 transition-colors text-slate-300 text-sm">
                                            MySQL
                                        </button>
                                        <button wire:click="bulkRestartService('redis')"
                                                @click="open = false; servicesOpen = false"
                                                class="w-full text-left px-8 py-2.5 hover:bg-slate-700/50 transition-colors text-slate-300 text-sm">
                                            Redis
                                        </button>
                                        <button wire:click="bulkRestartService('php-fpm')"
                                                @click="open = false; servicesOpen = false"
                                                class="w-full text-left px-8 py-2.5 hover:bg-slate-700/50 transition-colors text-slate-300 text-sm">
                                            PHP-FPM
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (session()->has('message'))
        <div class="mb-6 bg-emerald-500/10 border border-emerald-500/30 rounded-xl p-4 backdrop-blur-sm">
            <p class="text-emerald-400 font-medium">{{ session('message') }}</p>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 bg-red-500/10 border border-red-500/30 rounded-xl p-4 backdrop-blur-sm">
            <p class="text-red-400 font-medium">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-200 dark:border-slate-700/50 shadow-xl mb-8 overflow-hidden">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2 flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Search Servers
                    </label>
                    <input wire:model.live="search"
                           type="text"
                           placeholder="Type to search..."
                           class="w-full px-4 py-2.5 bg-white dark:bg-slate-900/50 border border-slate-300 dark:border-slate-600/50 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:border-blue-500/50 focus:ring-2 focus:ring-blue-500/20 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2 flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Status
                    </label>
                    <select wire:model.live="statusFilter"
                            class="w-full px-4 py-2.5 bg-white dark:bg-slate-900/50 border border-slate-300 dark:border-slate-600/50 rounded-xl text-slate-900 dark:text-white focus:border-blue-500/50 focus:ring-2 focus:ring-blue-500/20 transition-all">
                        <option value="">All Statuses</option>
                        <option value="online">Online</option>
                        <option value="offline">Offline</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="error">Error</option>
                    </select>
                </div>
            </div>

            <!-- Tag Filter -->
            @if($allTags->count() > 0)
                <div class="border-t border-slate-200 dark:border-slate-700/50 pt-4">
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Filter by Tags</label>
                        <a href="{{ route('servers.tags') }}" class="text-xs text-blue-400 hover:text-blue-300 transition-colors">
                            Manage Tags
                        </a>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($allTags as $tag)
                            <button wire:click="toggleTagFilter({{ $tag->id }})"
                                    class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium transition-all duration-200
                                        @if(in_array($tag->id, $tagFilter))
                                            ring-2 ring-offset-2 ring-offset-slate-900 shadow-lg scale-105
                                        @else
                                            hover:scale-105 opacity-75 hover:opacity-100
                                        @endif"
                                    style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}; @if(in_array($tag->id, $tagFilter)) ring-color: {{ $tag->color }}; @endif">
                                <span class="w-2 h-2 rounded-full mr-2" style="background-color: {{ $tag->color }}"></span>
                                {{ $tag->name }}
                                <span class="ml-1.5 text-xs opacity-75">({{ $tag->servers_count }})</span>
                            </button>
                        @endforeach
                    </div>
                    @if(!empty($tagFilter))
                        <button wire:click="$set('tagFilter', [])"
                                class="mt-3 text-xs text-slate-400 hover:text-slate-200 transition-colors">
                            Clear tag filters
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Loading State --}}
    <div wire:loading.delay class="mb-8">
        <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-4 backdrop-blur-sm" role="status" aria-live="polite" aria-label="Loading servers">
            <div class="flex items-center gap-3">
                <svg class="animate-spin h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" role="img" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-blue-400 font-medium">Loading servers...</p>
            </div>
        </div>
    </div>

    <!-- Select All Checkbox -->
    @if($servers->count() > 0)
        <div class="bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-200 dark:border-slate-700/50 shadow-xl p-4 mb-4" wire:loading.remove>
            <label class="flex items-center cursor-pointer">
                <input type="checkbox"
                       wire:model.live="selectAll"
                       wire:change="toggleSelectAll"
                       aria-label="Select all servers on this page"
                       class="w-5 h-5 text-blue-500 bg-white dark:bg-slate-700 border-slate-300 dark:border-slate-600 rounded focus:ring-blue-500/50 focus:ring-2 focus:ring-offset-0">
                <span class="ml-3 text-slate-900 dark:text-white font-medium">Select All Servers</span>
            </label>
        </div>
    @endif

    <!-- Servers Grid -->
    @if($servers->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6" wire:loading.remove>
            @foreach($servers as $server)
                <div class="group relative bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700/50 shadow-xl hover:shadow-2xl hover:border-slate-300 dark:hover:border-slate-600/50 transform hover:-translate-y-1 hover:scale-[1.02] transition-all duration-300"
                     :class="{ 'ring-4 ring-blue-500/50': {{ in_array($server->id, $selectedServers) ? 'true' : 'false' }} }">

                    {{-- Status Indicator Bar --}}
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r
                        @if($server->status === 'online') from-emerald-500 to-teal-500
                        @elseif($server->status === 'offline') from-red-500 to-rose-600
                        @elseif($server->status === 'maintenance') from-amber-500 to-orange-500
                        @else from-slate-500 to-slate-600
                        @endif">
                    </div>

                    <!-- Selection Checkbox -->
                    <div class="absolute top-4 left-4 z-10" onclick="event.stopPropagation()">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox"
                                   wire:model.live="selectedServers"
                                   value="{{ $server->id }}"
                                   wire:change="toggleServerSelection({{ $server->id }})"
                                   aria-label="Select {{ $server->name }}"
                                   class="w-5 h-5 text-blue-500 bg-white dark:bg-slate-700 border-slate-300 dark:border-slate-600 rounded focus:ring-blue-500/50 focus:ring-2 focus:ring-offset-0">
                        </label>
                    </div>

                    <div class="p-6 cursor-pointer" onclick="window.location='{{ route('servers.show', $server) }}'">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1 ml-8 min-w-0">
                                <a href="{{ route('servers.show', $server) }}" class="text-xl font-bold text-slate-900 dark:text-white hover:text-blue-400 transition-colors truncate block">
                                    {{ $server->name }}
                                </a>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-mono truncate">{{ $server->ip_address }}</p>
                            </div>
                            <div class="flex-shrink-0 p-2 rounded-lg
                                @if($server->status === 'online') bg-gradient-to-br from-emerald-500 to-teal-600
                                @elseif($server->status === 'offline') bg-gradient-to-br from-red-500 to-rose-600
                                @elseif($server->status === 'maintenance') bg-gradient-to-br from-amber-500 to-orange-500
                                @else bg-gradient-to-br from-slate-500 to-slate-600
                                @endif"
                                role="status"
                                aria-label="Server status: {{ ucfirst($server->status) }}"
                                title="Server status: {{ ucfirst($server->status) }}">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-hidden="true">
                                    @if($server->status === 'online')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    @elseif($server->status === 'maintenance')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    @endif
                                </svg>
                            </div>
                        </div>

                        <!-- Status Badge & Tags -->
                        <div class="mb-4 flex flex-wrap items-center gap-2">
                            <span class="flex-shrink-0 px-2.5 py-1 rounded-lg text-xs font-semibold flex items-center
                                @if($server->status === 'online') bg-emerald-500/20 text-emerald-400 border border-emerald-500/30
                                @elseif($server->status === 'offline') bg-red-500/20 text-red-400 border border-red-500/30
                                @elseif($server->status === 'maintenance') bg-amber-500/20 text-amber-400 border border-amber-500/30
                                @else bg-slate-600/20 text-slate-400 border border-slate-600/30
                                @endif"
                                role="status"
                                aria-live="polite"
                                aria-label="Server status: {{ ucfirst($server->status) }}">
                                @if($server->status === 'online')
                                    <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full mr-1.5 animate-pulse" aria-hidden="true"></span>
                                @else
                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5
                                        @if($server->status === 'offline') bg-red-400
                                        @elseif($server->status === 'maintenance') bg-amber-400
                                        @else bg-slate-400
                                        @endif" aria-hidden="true"></span>
                                @endif
                                {{ ucfirst($server->status) }}
                            </span>

                            @if($server->tags->count() > 0)
                                @foreach($server->tags as $tag)
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full"
                                          style="background-color: {{ $tag->color }}20; color: {{ $tag->color }};">
                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5" style="background-color: {{ $tag->color }}"></span>
                                        {{ $tag->name }}
                                    </span>
                                @endforeach
                            @endif
                        </div>

                        <!-- Resources -->
                        <div class="space-y-2.5 mb-4">
                            <div class="flex items-center justify-between p-3 bg-blue-500/10 border border-blue-500/30 rounded-xl backdrop-blur-sm"
                                 aria-label="CPU: {{ $server->cpu_cores ?? 0 }} cores"
                                 title="CPU: {{ $server->cpu_cores ?? 0 }} cores">
                                <div class="flex items-center">
                                    <div class="p-1.5 bg-blue-500/20 rounded-lg mr-2" aria-hidden="true">
                                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-medium text-slate-300">CPU</span>
                                </div>
                                <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $server->cpu_cores ?? 0 }} Cores</span>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-purple-500/10 border border-purple-500/30 rounded-xl backdrop-blur-sm"
                                 aria-label="RAM: {{ $server->memory_gb ?? 0 }} gigabytes"
                                 title="RAM: {{ $server->memory_gb ?? 0 }} GB">
                                <div class="flex items-center">
                                    <div class="p-1.5 bg-purple-500/20 rounded-lg mr-2" aria-hidden="true">
                                        <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-medium text-slate-300">RAM</span>
                                </div>
                                <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $server->memory_gb ?? 0 }} GB</span>
                            </div>
                        </div>

                        <!-- Location & Last Ping -->
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-sm text-slate-300"
                                 aria-label="Server location: {{ $server->location_name ?? 'Unknown' }}"
                                 title="Server location: {{ $server->location_name ?? 'Unknown' }}">
                                <div class="p-1.5 bg-emerald-500/20 rounded-lg mr-2" aria-hidden="true">
                                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                                {{ $server->location_name ?? 'Unknown' }}
                            </div>
                            <div class="flex items-center text-sm text-slate-300"
                                 aria-label="Last ping: {{ $server->last_ping_at ? $server->last_ping_at->diffForHumans() : 'Never' }}"
                                 title="Last ping: {{ $server->last_ping_at ? $server->last_ping_at->format('Y-m-d H:i:s') : 'Never' }}">
                                <div class="p-1.5 bg-orange-500/20 rounded-lg mr-2" aria-hidden="true">
                                    <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                Last ping: {{ $server->last_ping_at ? $server->last_ping_at->diffForHumans() : 'Never' }}
                            </div>
                        </div>

                        <div class="flex justify-between items-center pt-4 border-t border-slate-200 dark:border-slate-700/50">
                            <div class="flex space-x-3" onclick="event.stopPropagation()">
                                <button wire:click="pingServer({{ $server->id }})"
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-50"
                                        wire:target="pingServer({{ $server->id }})"
                                        aria-label="Ping {{ $server->name }}"
                                        class="text-emerald-400 hover:text-emerald-300 text-sm font-medium transition-colors inline-flex items-center">
                                    <svg wire:loading.remove wire:target="pingServer({{ $server->id }})" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                                    </svg>
                                    <svg wire:loading wire:target="pingServer({{ $server->id }})" class="animate-spin w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" role="img" aria-hidden="true">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span wire:loading.remove wire:target="pingServer({{ $server->id }})">Ping</span>
                                    <span wire:loading wire:target="pingServer({{ $server->id }})" role="status" aria-live="polite">Pinging...</span>
                                </button>
                                <button wire:click="rebootServer({{ $server->id }})"
                                        wire:confirm="Are you sure you want to reboot '{{ $server->name }}'? All running services will be temporarily interrupted during the reboot process."
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-50"
                                        wire:target="rebootServer({{ $server->id }})"
                                        aria-label="Reboot {{ $server->name }}"
                                        class="text-orange-400 hover:text-orange-300 text-sm font-medium transition-colors inline-flex items-center">
                                    <svg wire:loading.remove wire:target="rebootServer({{ $server->id }})" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" role="img" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    <svg wire:loading wire:target="rebootServer({{ $server->id }})" class="animate-spin w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" role="img" aria-hidden="true">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span wire:loading.remove wire:target="rebootServer({{ $server->id }})">Reboot</span>
                                    <span wire:loading wire:target="rebootServer({{ $server->id }})" role="status" aria-live="polite">Rebooting...</span>
                                </button>
                                <a href="{{ route('servers.show', $server) }}"
                                   aria-label="View details for {{ $server->name }}"
                                   class="text-blue-400 hover:text-blue-300 text-sm font-medium transition-colors">
                                    View
                                </a>
                                @can('update', $server)
                                <a href="{{ route('servers.edit', $server) }}"
                                   aria-label="Edit {{ $server->name }}"
                                   class="text-indigo-400 hover:text-indigo-300 text-sm font-medium transition-colors">
                                    Edit
                                </a>
                                @endcan
                                @can('delete', $server)
                                <button wire:click="deleteServer({{ $server->id }})"
                                        wire:confirm="Are you sure you want to delete '{{ $server->name }}'? This action cannot be undone and will remove all associated projects, configurations, and metrics."
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-50"
                                        wire:target="deleteServer({{ $server->id }})"
                                        aria-label="Delete {{ $server->name }}"
                                        class="text-red-400 hover:text-red-300 text-sm font-medium transition-colors inline-flex items-center">
                                    <span wire:loading.remove wire:target="deleteServer({{ $server->id }})">Delete</span>
                                    <span wire:loading wire:target="deleteServer({{ $server->id }})" class="inline-flex items-center gap-1" role="status" aria-live="polite">
                                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24" role="img" aria-hidden="true">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Deleting...
                                    </span>
                                </button>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-200 dark:border-slate-700/50 shadow-xl p-4" wire:loading.remove>
            {{ $servers->links() }}
        </div>
    @elseif($search || $statusFilter || !empty($tagFilter))
        {{-- No Results State (filters applied) --}}
        <div class="bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-200 dark:border-slate-700/50 shadow-xl text-center py-16" wire:loading.remove>
            <div class="relative inline-flex items-center justify-center w-20 h-20 mb-6">
                <div class="absolute inset-0 bg-gradient-to-br from-amber-500/20 to-orange-500/20 rounded-full blur-xl"></div>
                <div class="relative p-4 bg-slate-200/50 dark:bg-slate-700/50 rounded-2xl">
                    <svg class="w-10 h-10 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">No Servers Found</h3>
            <p class="text-slate-600 dark:text-slate-400 mb-6 max-w-md mx-auto">No servers match your current filters. Try adjusting your search criteria or clear the filters.</p>
            <button wire:click="$set('search', ''); $set('statusFilter', ''); $set('tagFilter', [])"
               class="group inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-sm text-slate-700 dark:text-white overflow-hidden transition-all duration-300 hover:-translate-y-0.5 bg-slate-200/50 dark:bg-slate-700/50 backdrop-blur-sm border border-slate-300/50 dark:border-slate-600/50 hover:border-slate-400/50 dark:hover:border-slate-500/50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span>Clear All Filters</span>
            </button>
        </div>
    @else
        {{-- Empty State (no servers at all) --}}
        <div class="bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-200 dark:border-slate-700/50 shadow-xl text-center py-16" wire:loading.remove>
            <div class="relative inline-flex items-center justify-center w-20 h-20 mb-6">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-500/20 to-indigo-500/20 rounded-full blur-xl"></div>
                <div class="relative p-4 bg-slate-200/50 dark:bg-slate-700/50 rounded-2xl">
                    <svg class="w-10 h-10 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"></path>
                    </svg>
                </div>
            </div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">No Servers Yet</h3>
            <p class="text-slate-600 dark:text-slate-400 mb-6 max-w-md mx-auto">Get started by adding your first server to manage your infrastructure.</p>
            <a href="{{ route('servers.create') }}"
               class="group inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-sm text-white overflow-hidden transition-all duration-300 hover:-translate-y-0.5"
               style="background: linear-gradient(135deg, #3b82f6 0%, #6366f1 50%, #8b5cf6 100%);">
                <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/25 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                <svg class="w-4 h-4 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span class="relative z-10">Add Your First Server</span>
            </a>
        </div>
    @endif

    <!-- Bulk Action Results Modal -->
    @if($showResultsModal && !empty($bulkActionResults))
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: true }">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 transition-opacity bg-slate-900/80 backdrop-blur-sm"
                     @click="$wire.closeResultsModal()"></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-slate-800/95 backdrop-blur-sm rounded-2xl text-left overflow-hidden shadow-2xl border border-slate-700/50 transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold text-white flex items-center">
                                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                Bulk Action Results
                            </h3>
                            <button @click="$wire.closeResultsModal()"
                                    class="text-white hover:text-slate-200 transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Results Content -->
                    <div class="px-6 py-4 max-h-96 overflow-y-auto">
                        <div class="space-y-3">
                            @foreach($bulkActionResults as $serverId => $result)
                                <div class="flex items-start p-4 rounded-xl border backdrop-blur-sm
                                    @if($result['success'])
                                        bg-emerald-500/10 border-emerald-500/30
                                    @else
                                        bg-red-500/10 border-red-500/30
                                    @endif">
                                    <!-- Icon -->
                                    <div class="flex-shrink-0">
                                        @if($result['success'])
                                            <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        @else
                                            <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        @endif
                                    </div>

                                    <!-- Content -->
                                    <div class="ml-3 flex-1">
                                        <h4 class="font-semibold
                                            @if($result['success'])
                                                text-emerald-300
                                            @else
                                                text-red-300
                                            @endif">
                                            {{ $result['server_name'] }}
                                        </h4>
                                        <p class="text-sm mt-1
                                            @if($result['success'])
                                                text-emerald-400
                                            @else
                                                text-red-400
                                            @endif">
                                            {{ $result['message'] }}
                                        </p>
                                        @if(isset($result['latency_ms']))
                                            <p class="text-xs mt-1 text-slate-400">
                                                Latency: {{ $result['latency_ms'] }}ms
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="bg-slate-900/50 px-6 py-4 flex justify-end space-x-3">
                        <button @click="$wire.closeResultsModal()"
                                class="bg-slate-700 hover:bg-slate-600 text-white font-semibold px-6 py-2 rounded-lg transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
