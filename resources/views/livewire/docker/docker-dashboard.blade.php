<div wire:init="loadInitialData" class="space-y-6">
    {{-- Header with Gradient --}}
    <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-cyan-500 via-blue-500 to-indigo-500 dark:from-cyan-600 dark:via-blue-600 dark:to-indigo-600 p-8 shadow-xl overflow-hidden">
        <div class="absolute inset-0 bg-black/10 dark:bg-black/20 backdrop-blur-sm"></div>
        <div class="relative z-10 flex justify-between items-center">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <div class="p-2 bg-white/20 dark:bg-white/10 backdrop-blur-md rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <h1 class="text-4xl font-bold text-white">Docker Management</h1>
                </div>
                <p class="text-white/90 text-lg">Server: {{ $server->name }}</p>
            </div>
            <button wire:click="loadDockerInfo"
                    class="bg-white/20 hover:bg-white/30 backdrop-blur-md text-white font-semibold px-6 py-3 rounded-lg transition-all duration-300 hover:scale-105 shadow-lg flex items-center space-x-2"
                    wire:loading.attr="disabled"
                    wire:target="loadDockerInfo">
                <span wire:loading.remove wire:target="loadDockerInfo" class="flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Refresh</span>
                </span>
                <span wire:loading wire:target="loadDockerInfo" class="flex items-center space-x-2">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Refreshing...</span>
                </span>
            </button>
        </div>
    </div>

    {{-- Success Message --}}
    @if (session()->has('message'))
        <div class="mb-6 bg-gradient-to-r from-green-500/20 to-emerald-500/20 dark:from-green-500/30 dark:to-emerald-500/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-400 px-4 py-3 rounded-lg backdrop-blur-sm">
            {{ session('message') }}
        </div>
    @endif

    {{-- Enhanced Error Message with User-Friendly Context --}}
    @if ($error)
        @php
            // Parse error message and provide user-friendly context
            $errorLower = strtolower($error);
            $errorType = 'general';
            $errorTitle = 'Operation Failed';
            $suggestion = '';
            $icon = 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z';

            // Connection/SSH errors
            if (str_contains($errorLower, 'connection') || str_contains($errorLower, 'ssh') || str_contains($errorLower, 'refused') || str_contains($errorLower, 'timed out')) {
                $errorType = 'connection';
                $errorTitle = 'Connection Failed';
                $suggestion = 'The server may be offline or unreachable. Check the server status and network connectivity, then try again.';
                $icon = 'M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414';
            }
            // Docker not running
            elseif (str_contains($errorLower, 'docker daemon') || str_contains($errorLower, 'not running') || str_contains($errorLower, 'socket')) {
                $errorType = 'docker-down';
                $errorTitle = 'Docker Not Running';
                $suggestion = 'Docker service is not running on this server. Please start Docker with "sudo systemctl start docker" or contact your server administrator.';
                $icon = 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636';
            }
            // Permission denied
            elseif (str_contains($errorLower, 'permission') || str_contains($errorLower, 'access denied') || str_contains($errorLower, 'forbidden')) {
                $errorType = 'permission';
                $errorTitle = 'Permission Denied';
                $suggestion = 'You may not have sufficient permissions for this operation. Ensure the SSH user has Docker access (is in the docker group).';
                $icon = 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z';
            }
            // Resource in use
            elseif (str_contains($errorLower, 'in use') || str_contains($errorLower, 'being used') || str_contains($errorLower, 'conflict')) {
                $errorType = 'in-use';
                $errorTitle = 'Resource In Use';
                $suggestion = 'This resource is currently being used by a running container. Stop the container first, then try again.';
                $icon = 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z';
            }
            // Image not found
            elseif (str_contains($errorLower, 'no such image') || str_contains($errorLower, 'image not found')) {
                $errorType = 'not-found';
                $errorTitle = 'Image Not Found';
                $suggestion = 'The image was not found. It may have already been deleted or the reference is incorrect. Refresh to update the list.';
                $icon = 'M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
            }
            // Volume/Network not found
            elseif (str_contains($errorLower, 'no such volume') || str_contains($errorLower, 'no such network')) {
                $errorType = 'not-found';
                $errorTitle = 'Resource Not Found';
                $suggestion = 'The resource was not found. It may have already been deleted. Refresh to update the list.';
                $icon = 'M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
            }
            // Default suggestion for general errors
            else {
                $suggestion = 'An unexpected error occurred. Please check the server logs for more details or try refreshing the data.';
            }
        @endphp
        <div class="mb-6 bg-gradient-to-r from-red-500/10 to-red-600/10 dark:from-red-500/20 dark:to-red-600/20 border border-red-200 dark:border-red-700 rounded-xl overflow-hidden">
            <div class="p-4">
                <div class="flex items-start gap-4">
                    {{-- Error Icon --}}
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
                            </svg>
                        </div>
                    </div>

                    {{-- Error Content --}}
                    <div class="flex-1 min-w-0">
                        <h4 class="text-lg font-semibold text-red-800 dark:text-red-300 flex items-center gap-2">
                            {{ $errorTitle }}
                            <span class="text-xs font-normal px-2 py-0.5 bg-red-200 dark:bg-red-800 text-red-700 dark:text-red-300 rounded-full">{{ $errorType }}</span>
                        </h4>

                        {{-- Technical Error (Collapsible) --}}
                        <div x-data="{ showDetails: false }" class="mt-2">
                            <button @click="showDetails = !showDetails" class="text-sm text-red-600 dark:text-red-400 hover:underline flex items-center gap-1">
                                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-90': showDetails }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                <span x-text="showDetails ? 'Hide technical details' : 'Show technical details'"></span>
                            </button>
                            <div x-show="showDetails" x-collapse class="mt-2 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                <code class="text-xs text-red-700 dark:text-red-300 break-all font-mono">{{ $error }}</code>
                            </div>
                        </div>

                        {{-- User-Friendly Suggestion --}}
                        @if($suggestion)
                            <div class="mt-3 flex items-start gap-2 text-sm text-red-700 dark:text-red-300">
                                <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                                <span>{{ $suggestion }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex-shrink-0 flex flex-col gap-2">
                        <button wire:click="loadDockerInfo"
                                wire:loading.attr="disabled"
                                wire:target="loadDockerInfo"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                            <svg wire:loading.remove wire:target="loadDockerInfo" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <svg wire:loading wire:target="loadDockerInfo" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="loadDockerInfo">Retry</span>
                            <span wire:loading wire:target="loadDockerInfo">Retrying...</span>
                        </button>
                        <button wire:click="$set('error', null)" class="px-4 py-2 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 text-sm font-medium rounded-lg transition-colors">
                            Dismiss
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Initial Loading Skeleton --}}
    @if($isLoading)
        <div class="space-y-6 animate-pulse">
            {{-- Tabs Skeleton --}}
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex space-x-8">
                    @for($i = 0; $i < 5; $i++)
                        <div class="h-4 w-24 bg-gray-200 dark:bg-gray-700 rounded my-4"></div>
                    @endfor
                </nav>
            </div>
            {{-- Content Skeleton --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @for($i = 0; $i < 4; $i++)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                        <div class="h-4 w-24 bg-gray-200 dark:bg-gray-700 rounded mb-4"></div>
                        <div class="h-8 w-16 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    </div>
                @endfor
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                <div class="h-5 w-32 bg-gray-200 dark:bg-gray-700 rounded mb-4"></div>
                <div class="space-y-3">
                    @for($i = 0; $i < 3; $i++)
                        <div class="h-4 w-full bg-gray-200 dark:bg-gray-700 rounded"></div>
                    @endfor
                </div>
            </div>
        </div>
    @else
    {{-- Tabs --}}
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8">
            <button wire:click="switchTab('overview')"
                    class="@if($activeTab === 'overview') border-blue-500 text-blue-600 @else border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                📊 Overview
            </button>
            <button wire:click="switchTab('images')"
                    class="@if($activeTab === 'images') border-blue-500 text-blue-600 @else border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                🖼️ Images ({{ count($images) }})
            </button>
            <button wire:click="switchTab('volumes')"
                    class="@if($activeTab === 'volumes') border-blue-500 text-blue-600 @else border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                💾 Volumes ({{ count($volumes) }})
            </button>
            <button wire:click="switchTab('networks')"
                    class="@if($activeTab === 'networks') border-blue-500 text-blue-600 @else border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                🌐 Networks ({{ count($networks) }})
            </button>
            <button wire:click="switchTab('cleanup')"
                    class="@if($activeTab === 'cleanup') border-blue-500 text-blue-600 @else border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                🧹 Cleanup
            </button>
        </nav>
    </div>

    {{-- Loading Overlay for Content --}}
    <div wire:loading wire:target="loadDockerInfo,switchTab" class="fixed inset-0 z-40 flex items-center justify-center">
        <div class="absolute inset-0 backdrop-blur-md bg-slate-900/70"></div>
        <div class="relative">
            <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-blue-500/60 via-purple-500/60 to-indigo-500/60 blur-xl opacity-75 animate-pulse"></div>
            <div class="relative bg-slate-900/95 border border-white/10 rounded-3xl px-10 py-8 shadow-2xl text-center space-y-5">
                <div class="flex items-center justify-center space-x-3 text-blue-300">
                    <svg class="w-6 h-6 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm font-semibold tracking-wide uppercase">Docker telemetry incoming…</span>
                </div>
                <div class="text-white text-lg font-semibold">Syncing server metrics</div>
                <p class="text-sm text-slate-400 max-w-sm">
                    Fetching container stats, images, volumes, networks and disk usage directly from <span class="font-semibold">{{ $server->name }}</span>.
                </p>
                <div class="space-y-2 text-left text-xs text-slate-400 font-medium">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Container information
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse delay-150"></span>
                        Resource metrics
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-purple-400 animate-pulse delay-300"></span>
                        Disk usage snapshots
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Overview Tab --}}
    @if ($activeTab === 'overview')
        <div class="space-y-6">
            {{-- Docker Info --}}
            @if ($dockerInfo)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Docker System Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 p-4 rounded-lg transition-all hover:-translate-y-1 shadow-md">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Docker Version</div>
                            <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $dockerInfo['ServerVersion'] ?? 'N/A' }}</div>
                        </div>
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-900/40 p-4 rounded-lg transition-all hover:-translate-y-1 shadow-md">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Containers</div>
                            <div class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $dockerInfo['Containers'] ?? 0 }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Running: {{ $dockerInfo['ContainersRunning'] ?? 0 }} |
                                Stopped: {{ $dockerInfo['ContainersStopped'] ?? 0 }}
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-900/40 p-4 rounded-lg transition-all hover:-translate-y-1 shadow-md">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Images</div>
                            <div class="text-xl font-bold text-green-600 dark:text-green-400">{{ $dockerInfo['Images'] ?? 0 }}</div>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-900/40 p-4 rounded-lg transition-all hover:-translate-y-1 shadow-md">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Storage Driver</div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ $dockerInfo['Driver'] ?? 'N/A' }}</div>
                        </div>
                        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-900/40 p-4 rounded-lg transition-all hover:-translate-y-1 shadow-md">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Operating System</div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ $dockerInfo['OperatingSystem'] ?? 'N/A' }}</div>
                        </div>
                        <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/20 dark:to-indigo-900/40 p-4 rounded-lg transition-all hover:-translate-y-1 shadow-md">
                            <div class="text-sm text-gray-600 dark:text-gray-400">CPU Cores</div>
                            <div class="text-xl font-bold text-indigo-600 dark:text-indigo-400">{{ $dockerInfo['NCPU'] ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Disk Usage --}}
            @if ($diskUsage)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Disk Usage</h3>
                    <div class="space-y-4">
                        @foreach ($diskUsage as $item)
                            <div class="flex items-center justify-between p-4 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 rounded-lg hover:-translate-y-1 transition-all shadow-md">
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $item['Type'] ?? 'Unknown' }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        Total: {{ $item['TotalCount'] ?? 0 }} |
                                        Active: {{ $item['Active'] ?? 0 }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $item['Size'] ?? 'N/A' }}</div>
                                    <div class="text-sm text-orange-600 dark:text-orange-400">
                                        Reclaimable: {{ $item['Reclaimable'] ?? '0' }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Images Tab --}}
    @if ($activeTab === 'images')
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Docker Images</h3>
                <button wire:click="pruneImages"
                        class="px-4 py-2 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white rounded-lg transition-all hover:scale-105 shadow-lg"
                        wire:loading.attr="disabled">
                    🧹 Prune Unused Images
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Repository</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tag</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Image ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Size</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($images as $image)
                            <tr class="dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white dark:text-white">
                                    {{ $image['Repository'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    {{ $image['Tag'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono">
                                    {{ Str::limit($image['ID'] ?? 'N/A', 12) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    {{ $image['Size'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    {{ $image['CreatedAt'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    <button wire:click="deleteImage('{{ $image['ID'] }}')"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50"
                                            wire:target="deleteImage('{{ $image['ID'] }}')"
                                            class="text-red-600 hover:text-red-900"
                                            onclick="return confirm('Are you sure you want to delete this image?')">
                                        <span wire:loading.remove wire:target="deleteImage('{{ $image['ID'] }}')">🗑️ Delete</span>
                                        <span wire:loading wire:target="deleteImage('{{ $image['ID'] }}')">⏳ Deleting...</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400 dark:text-gray-400">No images found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Volumes Tab --}}
    @if ($activeTab === 'volumes')
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Docker Volumes</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Driver</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Mountpoint</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($volumes as $volume)
                            <tr class="dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white dark:text-white">
                                    {{ $volume['Name'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    {{ $volume['Driver'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 font-mono text-xs">
                                    {{ Str::limit($volume['Mountpoint'] ?? 'N/A', 50) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    <button wire:click="deleteVolume('{{ $volume['Name'] }}')"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50"
                                            wire:target="deleteVolume('{{ $volume['Name'] }}')"
                                            class="text-red-600 hover:text-red-900"
                                            onclick="return confirm('Are you sure? This will permanently delete all data in this volume!')">
                                        <span wire:loading.remove wire:target="deleteVolume('{{ $volume['Name'] }}')">🗑️ Delete</span>
                                        <span wire:loading wire:target="deleteVolume('{{ $volume['Name'] }}')">⏳ Deleting...</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400 dark:text-gray-400">No volumes found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Networks Tab --}}
    @if ($activeTab === 'networks')
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Docker Networks</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Driver</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Scope</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($networks as $network)
                            <tr class="dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white dark:text-white">
                                    {{ $network['Name'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono">
                                    {{ Str::limit($network['ID'] ?? 'N/A', 12) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    {{ $network['Driver'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    {{ $network['Scope'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    @if (!in_array($network['Name'], ['bridge', 'host', 'none']))
                                        <button wire:click="deleteNetwork('{{ $network['Name'] }}')"
                                                wire:loading.attr="disabled"
                                                wire:loading.class="opacity-50"
                                                wire:target="deleteNetwork('{{ $network['Name'] }}')"
                                                class="text-red-600 hover:text-red-900"
                                                onclick="return confirm('Are you sure you want to delete this network?')">
                                            <span wire:loading.remove wire:target="deleteNetwork('{{ $network['Name'] }}')">🗑️ Delete</span>
                                            <span wire:loading wire:target="deleteNetwork('{{ $network['Name'] }}')">⏳ Deleting...</span>
                                        </button>
                                    @else
                                        <span class="text-gray-400">System Network</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400 dark:text-gray-400">No networks found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Cleanup Tab --}}
    @if ($activeTab === 'cleanup')
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">System Cleanup</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">Free up disk space by removing unused Docker resources.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-900/40 rounded-lg p-6 hover:-translate-y-1 transition-all shadow-md">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">🖼️ Prune Images</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Remove dangling and unused images</p>
                        <button wire:click="pruneImages"
                                class="w-full px-4 py-2 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white rounded-lg transition-all hover:scale-105 shadow-lg"
                                wire:loading.attr="disabled">
                            Prune Images
                        </button>
                    </div>

                    <div class="border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-900/40 rounded-lg p-6 hover:-translate-y-1 transition-all shadow-md">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">🧹 System Prune</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Remove all unused containers, networks, and images</p>
                        <button wire:click="systemPrune"
                                class="w-full px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-lg transition-all hover:scale-105 shadow-lg"
                                wire:loading.attr="disabled"
                                onclick="return confirm('This will remove all unused Docker resources. Continue?')">
                            System Prune
                        </button>
                    </div>
                </div>

                @if ($diskUsage)
                    <div class="mt-6 p-4 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-900/40 rounded-lg backdrop-blur-sm border border-blue-200 dark:border-blue-700">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">💡 Disk Space Summary</h4>
                        <div class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                            @foreach ($diskUsage as $item)
                                <div class="flex justify-between">
                                    <span>{{ $item['Type'] ?? 'Unknown' }}:</span>
                                    <span class="font-semibold">{{ $item['Size'] ?? 'N/A' }} <span class="text-orange-600 dark:text-orange-400">({{ $item['Reclaimable'] ?? '0' }} reclaimable)</span></span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
    @endif {{-- End of isLoading else block --}}
</div>

