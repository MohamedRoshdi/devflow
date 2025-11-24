<div>
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
                <button wire:click="addCurrentServer" class="bg-white/20 hover:bg-white/30 backdrop-blur-md text-white font-semibold px-6 py-3 rounded-lg transition-all duration-300 hover:scale-105 shadow-lg">
                    ⚡ Add Current Server
                </button>
                <a href="{{ route('servers.create') }}" class="bg-white/20 hover:bg-white/30 backdrop-blur-md text-white font-semibold px-6 py-3 rounded-lg transition-all duration-300 hover:scale-105 shadow-lg">
                    + Add Server
                </a>
            </div>
        </div>
    </div>

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
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
    </div>

    <!-- Servers Grid -->
    @if($servers->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            @foreach($servers as $server)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 cursor-pointer relative group overflow-hidden"
                     onclick="window.location='{{ route('servers.show', $server) }}'">
                    <!-- Gradient Border Top -->
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r
                        @if($server->status === 'online') from-green-500 to-emerald-600
                        @elseif($server->status === 'offline') from-red-500 to-red-600
                        @elseif($server->status === 'maintenance') from-yellow-500 to-orange-500
                        @else from-gray-400 to-gray-500
                        @endif">
                    </div>

                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
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

                        <!-- Status Badge -->
                        <div class="mb-4">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($server->status === 'online') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                @elseif($server->status === 'offline') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                @elseif($server->status === 'maintenance') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                                @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                @endif">
                                {{ ucfirst($server->status) }}
                            </span>
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
                            <div class="flex space-x-2" onclick="event.stopPropagation()">
                                <a href="{{ route('servers.show', $server) }}"
                                   class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 text-sm font-medium transition-colors">
                                    View Details
                                </a>
                                <button wire:click="deleteServer({{ $server->id }})"
                                        wire:confirm="Are you sure you want to delete this server?"
                                        class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 text-sm font-medium transition-colors">
                                    Delete
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
</div>

