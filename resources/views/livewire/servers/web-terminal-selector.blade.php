<div class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        {{-- Header --}}
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Web Terminal
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Professional SSH terminal with full interactive capabilities
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('terminal') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                    </svg>
                    Simple Terminal
                </a>
            </div>
        </div>

        @if(!$selectedServer)
            {{-- Server Selection --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-750">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                        </svg>
                        Select a Server
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Choose a server to connect to via the web terminal</p>
                </div>

                @if($servers->isEmpty())
                    <div class="p-12 text-center">
                        <div class="mx-auto w-24 h-24 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">No servers available</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                            Add a server to start using the Web Terminal feature
                        </p>
                        <div class="mt-6">
                            <a href="{{ route('servers.create') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition shadow-lg shadow-blue-600/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Server
                            </a>
                        </div>
                    </div>
                @else
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($servers as $server)
                                <button
                                    wire:click="selectServer({{ $server->id }})"
                                    class="group relative flex flex-col p-5 bg-gray-50 dark:bg-gray-700/50 hover:bg-white dark:hover:bg-gray-700 rounded-xl border border-gray-200 dark:border-gray-600 hover:border-blue-500 dark:hover:border-blue-500 transition-all duration-200 text-left hover:shadow-lg hover:shadow-blue-500/10">

                                    {{-- Status Indicator --}}
                                    <div class="absolute top-4 right-4">
                                        <span class="relative flex h-3 w-3">
                                            @if($server->status === 'online')
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                                            @elseif($server->status === 'offline')
                                                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                                            @else
                                                <span class="relative inline-flex rounded-full h-3 w-3 bg-yellow-500"></span>
                                            @endif
                                        </span>
                                    </div>

                                    {{-- Server Icon --}}
                                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                        </svg>
                                    </div>

                                    {{-- Server Info --}}
                                    <h3 class="font-semibold text-gray-900 dark:text-white text-lg mb-1">{{ $server->name }}</h3>
                                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                        </svg>
                                        <span class="font-mono">{{ $server->ip_address }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <span>{{ $server->username . '@' . $server->port }}</span>
                                    </div>

                                    {{-- Tags --}}
                                    @php $tags = is_array($server->tags) ? $server->tags : (is_object($server->tags) ? $server->tags->toArray() : []); @endphp
                                    @if(!empty($tags))
                                        <div class="mt-3 flex flex-wrap gap-1">
                                            @foreach(array_slice($tags, 0, 3) as $tag)
                                                <span class="inline-flex items-center px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400">
                                                    {{ $tag }}
                                                </span>
                                            @endforeach
                                            @if(count($tags) > 3)
                                                <span class="inline-flex items-center px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-400">
                                                    +{{ count($tags) - 3 }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- Connect Hint --}}
                                    <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-600 flex items-center justify-between">
                                        <span class="text-xs text-gray-500 dark:text-gray-500 uppercase tracking-wider">Click to connect</span>
                                        <svg class="w-4 h-4 text-gray-400 group-hover:text-blue-500 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                        </svg>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @else
            {{-- Terminal Interface --}}
            <div class="space-y-4">
                {{-- Server Info Header --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $selectedServer->name }}</h2>
                                <p class="text-sm text-gray-600 dark:text-gray-400 font-mono">
                                    {{ $selectedServer->username . '@' . $selectedServer->ip_address . ':' . $selectedServer->port }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-full
                                {{ $selectedServer->status === 'online' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400' : '' }}
                                {{ $selectedServer->status === 'offline' ? 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-400' : '' }}
                                {{ $selectedServer->status === 'maintenance' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-400' : '' }}">
                                <span class="w-2 h-2 rounded-full {{ $selectedServer->status === 'online' ? 'bg-green-500' : ($selectedServer->status === 'offline' ? 'bg-red-500' : 'bg-yellow-500') }}"></span>
                                {{ ucfirst($selectedServer->status) }}
                            </span>
                            <button
                                wire:click="clearSelection"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                </svg>
                                Change Server
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Web Terminal Component --}}
                <livewire:servers.web-terminal :server="$selectedServer" :key="'web-terminal-'.$selectedServer->id" />
            </div>
        @endif
    </div>
</div>
