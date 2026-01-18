<div>
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">SSH Terminal</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Execute commands on your servers via SSH
        </p>
    </div>

    @if(!$selectedServer)
        <!-- Server Selection -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Select a Server</h2>
            </div>

            @if($servers->isEmpty())
                <div class="p-6 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No servers available</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Add a server to use SSH Terminal
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('servers.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
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
                                class="flex flex-col p-4 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg border border-gray-200 dark:border-gray-600 transition text-left">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ $server->name }}</h3>
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded
                                        {{ $server->status === 'online' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400' : '' }}
                                        {{ $server->status === 'offline' ? 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-400' : '' }}
                                        {{ $server->status === 'maintenance' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-400' : '' }}">
                                        {{ ucfirst($server->status) }}
                                    </span>
                                </div>
                                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400 space-x-4">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                        </svg>
                                        {{ $server->ip_address }}
                                    </span>
                                </div>
                                @if($server->tags)
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        @foreach($server->tags as $tag)
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs rounded bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-400">
                                                {{ $tag }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @else
        <!-- SSH Terminal Interface -->
        <div class="space-y-6">
            <!-- Server Info Header -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                        </svg>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $selectedServer->name }}</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $selectedServer->ip_address }}</p>
                        </div>
                    </div>
                    <button
                        wire:click="$set('selectedServerId', null)"
                        class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition">
                        Change Server
                    </button>
                </div>
            </div>

            <!-- SSH Terminal Component -->
            <livewire:servers.s-s-h-terminal :server="$selectedServer" :key="'ssh-terminal-'.$selectedServer->id" />
        </div>
    @endif
</div>
