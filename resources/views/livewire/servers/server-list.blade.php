<div wire:poll.60s x-data="{ bulkDropdownOpen: false, servicesDropdownOpen: false }">
    <!-- Hero Section with Gradient -->
    <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-500 dark:from-blue-600 dark:via-indigo-600 dark:to-purple-600 p-8 shadow-xl overflow-hidden">
        <div class="absolute inset-0 bg-black/10 dark:bg-black/20 backdrop-blur-sm"></div>
        <div class="relative z-10 flex justify-between items-center">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <div class="p-2 bg-white/20 dark:bg-white/10 backdrop-blur-md rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"></path>
                        </svg>
                    </div>
                    <h1 class="text-4xl font-bold text-white">Server Management</h1>
                </div>
                <p class="text-white/90 text-lg">Manage your server infrastructure</p>
            </div>
            <div class="flex space-x-3">
                <button wire:click="pingAllServers"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        wire:target="pingAllServers"
                        class="bg-green-500/30 hover:bg-green-500/40 backdrop-blur-md text-white font-semibold px-6 py-3 rounded-lg transition-all duration-300 hover:scale-105 shadow-lg disabled:hover:scale-100">
                    <span wire:loading.remove wire:target="pingAllServers" class="inline-flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M5.63 18.37A9 9 0 1118.37 5.63L19 6M5 19l.63-.63"></path>
                        </svg>
                        Ping All
                    </span>
                    <span wire:loading wire:target="pingAllServers" class="inline-flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
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
                        class="bg-white/20 hover:bg-white/30 backdrop-blur-md text-white font-semibold px-6 py-3 rounded-lg transition-all duration-300 hover:scale-105 shadow-lg disabled:hover:scale-100">
                    <span wire:loading.remove wire:target="addCurrentServer">+ Add Current Server</span>
                    <span wire:loading wire:target="addCurrentServer" class="inline-flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Adding...
                    </span>
                </button>
                <a href="{{ route('servers.create') }}" class="bg-white/20 hover:bg-white/30 backdrop-blur-md text-white font-semibold px-6 py-3 rounded-lg transition-all duration-300 hover:scale-105 shadow-lg">
                    + Add Server
                </a>
            </div>
        </div>
    </div>

    <!-- Bulk Actions Bar (Sticky when servers selected) -->
    @if(count($selectedServers) > 0)
        <div class="sticky top-0 z-40 mb-6 bg-gradient-to-r from-gray-800 via-gray-900 to-gray-800 dark:from-gray-900 dark:via-black dark:to-gray-900 rounded-2xl shadow-2xl p-4 border border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-white font-semibold">{{ count($selectedServers) }} server(s) selected</span>
                    </div>
                    <button wire:click="clearSelection"
                            class="text-sm text-gray-400 hover:text-white transition-colors underline">
                        Clear Selection
                    </button>
                </div>

                <div class="flex items-center space-x-3">
                    <!-- Loading Indicator -->
                    @if($bulkActionInProgress)
                        <div class="flex items-center space-x-2 text-yellow-400">
                            <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm font-medium">Processing...</span>
                        </div>
                    @endif

                    <!-- Bulk Actions Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                @click.away="open = false"
                                :disabled="$wire.bulkActionInProgress"
                                class="bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-semibold px-6 py-2.5 rounded-lg transition-all duration-300 shadow-lg inline-flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Bulk Actions
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden z-50">

                            <!-- Ping Selected -->
                            <button wire:click="bulkPing"
                                    @click="open = false"
                                    class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center space-x-3">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                                </svg>
                                <span class="text-gray-900 dark:text-white font-medium">Ping Selected</span>
                            </button>

                            <!-- Reboot Selected -->
                            <button wire:click="bulkReboot"
                                    wire:confirm="Are you sure you want to reboot {{ count($selectedServers) }} server(s)? All running services will be interrupted."
                                    @click="open = false"
                                    class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center space-x-3 border-t border-gray-100 dark:border-gray-700">
                                <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span class="text-gray-900 dark:text-white font-medium">Reboot Selected</span>
                            </button>

                            <!-- Install Docker -->
                            <button wire:click="bulkInstallDocker"
                                    @click="open = false"
                                    class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center space-x-3 border-t border-gray-100 dark:border-gray-700">
                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                <span class="text-gray-900 dark:text-white font-medium">Install Docker</span>
                            </button>

                            <!-- Restart Service Submenu -->
                            <div class="border-t border-gray-100 dark:border-gray-700">
                                <div class="relative" x-data="{ servicesOpen: false }">
                                    <button @click="servicesOpen = !servicesOpen"
                                            class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                            <span class="text-gray-900 dark:text-white font-medium">Restart Service</span>
                                        </div>
                                        <svg class="w-4 h-4 text-gray-400" :class="{'rotate-90': servicesOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </button>

                                    <!-- Services Submenu -->
                                    <div x-show="servicesOpen"
                                         x-transition
                                         class="bg-gray-50 dark:bg-gray-900/50">
                                        <button wire:click="bulkRestartService('nginx')"
                                                @click="open = false; servicesOpen = false"
                                                class="w-full text-left px-8 py-2.5 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-gray-700 dark:text-gray-300 text-sm">
                                            Nginx
                                        </button>
                                        <button wire:click="bulkRestartService('mysql')"
                                                @click="open = false; servicesOpen = false"
                                                class="w-full text-left px-8 py-2.5 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-gray-700 dark:text-gray-300 text-sm">
                                            MySQL
                                        </button>
                                        <button wire:click="bulkRestartService('redis')"
                                                @click="open = false; servicesOpen = false"
                                                class="w-full text-left px-8 py-2.5 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-gray-700 dark:text-gray-300 text-sm">
                                            Redis
                                        </button>
                                        <button wire:click="bulkRestartService('php-fpm')"
                                                @click="open = false; servicesOpen = false"
                                                class="w-full text-left px-8 py-2.5 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-gray-700 dark:text-gray-300 text-sm">
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
        <div class="mb-6 bg-gradient-to-r from-green-500/20 to-emerald-500/20 dark:from-green-500/30 dark:to-emerald-500/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-400 px-4 py-3 rounded-lg backdrop-blur-sm">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 bg-gradient-to-r from-red-500/20 to-red-600/20 dark:from-red-500/30 dark:to-red-600/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-400 px-4 py-3 rounded-lg backdrop-blur-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                <input wire:model.live="search"
                       type="text"
                       placeholder="Search servers..."
                       class="input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                <select wire:model.live="statusFilter" class="input">
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
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <div class="flex items-center justify-between mb-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Filter by Tags</label>
                    <a href="{{ route('servers.tags') }}" class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300">
                        Manage Tags
                    </a>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach($allTags as $tag)
                        <button wire:click="toggleTagFilter({{ $tag->id }})"
                                class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium transition-all duration-200
                                    @if(in_array($tag->id, $tagFilter))
                                        ring-2 ring-offset-2 dark:ring-offset-gray-800 shadow-lg scale-105
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
                            class="mt-3 text-xs text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                        Clear tag filters
                    </button>
                @endif
            </div>
        @endif
    </div>

    <!-- Select All Checkbox -->
    @if($servers->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-4 mb-4">
            <label class="flex items-center cursor-pointer">
                <input type="checkbox"
                       wire:model.live="selectAll"
                       wire:change="toggleSelectAll"
                       class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                <span class="ml-3 text-gray-900 dark:text-white font-medium">Select All Servers</span>
            </label>
        </div>
    @endif

    <!-- Servers Grid -->
    @if($servers->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            @foreach($servers as $server)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 relative group overflow-hidden"
                     :class="{ 'ring-4 ring-blue-500': {{ in_array($server->id, $selectedServers) ? 'true' : 'false' }} }">
                    <!-- Gradient Border Top -->
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r
                        @if($server->status === 'online') from-green-500 to-emerald-600
                        @elseif($server->status === 'offline') from-red-500 to-red-600
                        @elseif($server->status === 'maintenance') from-yellow-500 to-orange-500
                        @else from-gray-400 to-gray-500
                        @endif">
                    </div>

                    <!-- Selection Checkbox -->
                    <div class="absolute top-4 left-4 z-10" onclick="event.stopPropagation()">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox"
                                   wire:model.live="selectedServers"
                                   value="{{ $server->id }}"
                                   wire:change="toggleServerSelection({{ $server->id }})"
                                   class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        </label>
                    </div>

                    <div class="p-6 cursor-pointer" onclick="window.location='{{ route('servers.show', $server) }}'">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1 ml-8">
                                <a href="{{ route('servers.show', $server) }}" class="text-xl font-bold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                    {{ $server->name }}
                                </a>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 font-mono">{{ $server->ip_address }}</p>
                            </div>
                            <div class="p-2 rounded-lg
                                @if($server->status === 'online') bg-gradient-to-br from-green-500 to-emerald-600
                                @elseif($server->status === 'offline') bg-gradient-to-br from-red-500 to-red-600
                                @elseif($server->status === 'maintenance') bg-gradient-to-br from-yellow-500 to-orange-500
                                @else bg-gradient-to-br from-gray-400 to-gray-500
                                @endif">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($server->status === 'online') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                @elseif($server->status === 'offline') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                @elseif($server->status === 'maintenance') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                                @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                @endif">
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
                        <div class="space-y-3 mb-4">
                            <div class="flex items-center justify-between p-3 bg-gradient-to-br from-blue-500/10 to-indigo-500/10 dark:from-blue-500/20 dark:to-indigo-500/20 rounded-lg">
                                <div class="flex items-center">
                                    <div class="p-1 bg-blue-100 dark:bg-blue-900/30 rounded mr-2">
                                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">CPU</span>
                                </div>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $server->cpu_cores ?? 0 }} Cores</span>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-gradient-to-br from-purple-500/10 to-pink-500/10 dark:from-purple-500/20 dark:to-pink-500/20 rounded-lg">
                                <div class="flex items-center">
                                    <div class="p-1 bg-purple-100 dark:bg-purple-900/30 rounded mr-2">
                                        <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">RAM</span>
                                </div>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $server->memory_gb ?? 0 }} GB</span>
                            </div>
                        </div>

                        <!-- Location & Last Ping -->
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                <div class="p-1 bg-green-100 dark:bg-green-900/30 rounded mr-2">
                                    <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                                {{ $server->location_name ?? 'Unknown' }}
                            </div>
                            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                <div class="p-1 bg-orange-100 dark:bg-orange-900/30 rounded mr-2">
                                    <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                Last ping: {{ $server->last_ping_at ? $server->last_ping_at->diffForHumans() : 'Never' }}
                            </div>
                        </div>

                        <div class="flex justify-between items-center pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex space-x-3" onclick="event.stopPropagation()">
                                <button wire:click="pingServer({{ $server->id }})"
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-50"
                                        wire:target="pingServer({{ $server->id }})"
                                        class="text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 text-sm font-medium transition-colors inline-flex items-center">
                                    <svg wire:loading.remove wire:target="pingServer({{ $server->id }})" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                                    </svg>
                                    <svg wire:loading wire:target="pingServer({{ $server->id }})" class="animate-spin w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span wire:loading.remove wire:target="pingServer({{ $server->id }})">Ping</span>
                                    <span wire:loading wire:target="pingServer({{ $server->id }})">Pinging...</span>
                                </button>
                                <button wire:click="rebootServer({{ $server->id }})"
                                        wire:confirm="Are you sure you want to reboot this server? All running services will be interrupted."
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-50"
                                        wire:target="rebootServer({{ $server->id }})"
                                        class="text-orange-600 dark:text-orange-400 hover:text-orange-700 dark:hover:text-orange-300 text-sm font-medium transition-colors inline-flex items-center">
                                    <svg wire:loading.remove wire:target="rebootServer({{ $server->id }})" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    <svg wire:loading wire:target="rebootServer({{ $server->id }})" class="animate-spin w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span wire:loading.remove wire:target="rebootServer({{ $server->id }})">Reboot</span>
                                    <span wire:loading wire:target="rebootServer({{ $server->id }})">Rebooting...</span>
                                </button>
                                <a href="{{ route('servers.show', $server) }}"
                                   class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 text-sm font-medium transition-colors">
                                    View Details
                                </a>
                                <button wire:click="deleteServer({{ $server->id }})"
                                        wire:confirm="Are you sure you want to delete this server?"
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-50"
                                        wire:target="deleteServer({{ $server->id }})"
                                        class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 text-sm font-medium transition-colors">
                                    <span wire:loading.remove wire:target="deleteServer({{ $server->id }})">Delete</span>
                                    <span wire:loading wire:target="deleteServer({{ $server->id }})">Deleting...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-4 transition-colors">
            {{ $servers->links() }}
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl text-center py-12 transition-colors">
            <div class="p-4 bg-gradient-to-br from-blue-100 to-indigo-200 dark:from-blue-700 dark:to-indigo-600 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                <svg class="h-10 w-10 text-blue-600 dark:text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No servers</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Get started by adding a new server.</p>
            <a href="{{ route('servers.create') }}" class="inline-block bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-semibold px-6 py-3 rounded-lg transition-all duration-300 hover:scale-105 shadow-lg">
                + Add Server
            </a>
        </div>
    @endif

    <!-- Bulk Action Results Modal -->
    @if($showResultsModal && !empty($bulkActionResults))
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: true }">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"
                     @click="$wire.closeResultsModal()"></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
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
                                    class="text-white hover:text-gray-200 transition-colors">
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
                                <div class="flex items-start p-4 rounded-lg border
                                    @if($result['success'])
                                        bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800
                                    @else
                                        bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800
                                    @endif">
                                    <!-- Icon -->
                                    <div class="flex-shrink-0">
                                        @if($result['success'])
                                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        @else
                                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        @endif
                                    </div>

                                    <!-- Content -->
                                    <div class="ml-3 flex-1">
                                        <h4 class="font-semibold
                                            @if($result['success'])
                                                text-green-900 dark:text-green-300
                                            @else
                                                text-red-900 dark:text-red-300
                                            @endif">
                                            {{ $result['server_name'] }}
                                        </h4>
                                        <p class="text-sm mt-1
                                            @if($result['success'])
                                                text-green-700 dark:text-green-400
                                            @else
                                                text-red-700 dark:text-red-400
                                            @endif">
                                            {{ $result['message'] }}
                                        </p>
                                        @if(isset($result['latency_ms']))
                                            <p class="text-xs mt-1 text-gray-600 dark:text-gray-400">
                                                Latency: {{ $result['latency_ms'] }}ms
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="bg-gray-50 dark:bg-gray-900 px-6 py-4 flex justify-end space-x-3">
                        <button @click="$wire.closeResultsModal()"
                                class="bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-semibold px-6 py-2 rounded-lg transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

