<div wire:init="loadAllStats">
    {{-- Hero Section --}}
    <div class="mb-8 rounded-2xl bg-gradient-to-br from-slate-600 via-gray-600 to-zinc-700 p-8 text-white shadow-xl">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold">System Status</h1>
                <p class="mt-2 text-slate-200">Monitor all system services, WebSocket connections, queues, and infrastructure</p>
            </div>
            <div class="flex items-center gap-3">
                <button wire:click="testBroadcast"
                        class="flex items-center gap-2 rounded-xl bg-white/20 px-4 py-2 font-medium backdrop-blur-sm transition hover:bg-white/30">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Test Broadcast
                </button>
                <button wire:click="refreshStats"
                        class="flex items-center gap-2 rounded-xl bg-white/20 px-4 py-2 font-medium backdrop-blur-sm transition hover:bg-white/30">
                    <svg class="h-5 w-5" wire:loading.class="animate-spin" wire:target="refreshStats" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Refresh
                </button>
            </div>
        </div>
    </div>

    {{-- Services Overview --}}
    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @foreach($services as $service)
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-lg transition hover:shadow-xl dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $service['name'] }}</p>
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $service['details'] }}</p>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium
                        @if($service['status'] === 'running') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                        @elseif($service['status'] === 'stopped') bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400
                        @else bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 @endif">
                        <span class="h-2 w-2 rounded-full @if($service['status'] === 'running') bg-green-500 animate-pulse @elseif($service['status'] === 'stopped') bg-yellow-500 @else bg-red-500 @endif"></span>
                        {{ ucfirst($service['status']) }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-8 lg:grid-cols-2">
        {{-- WebSocket (Reverb) Status --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">WebSocket Server (Reverb)</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Real-time communication status</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between rounded-xl bg-gray-50 p-4 dark:bg-gray-700/50">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Status</span>
                        <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-medium
                            @if($reverbStatus['running'] ?? false) bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                            @else bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 @endif">
                            <span class="h-2 w-2 rounded-full @if($reverbStatus['running'] ?? false) bg-green-500 animate-pulse @else bg-red-500 @endif"></span>
                            {{ ($reverbStatus['running'] ?? false) ? 'Running' : 'Stopped' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl bg-gray-50 p-4 dark:bg-gray-700/50">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Host</span>
                        <span class="font-mono text-sm text-gray-900 dark:text-white">{{ $reverbStatus['host'] ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl bg-gray-50 p-4 dark:bg-gray-700/50">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Port</span>
                        <span class="font-mono text-sm text-gray-900 dark:text-white">{{ $reverbStatus['port'] ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl bg-gray-50 p-4 dark:bg-gray-700/50">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">App ID</span>
                        <span class="font-mono text-sm text-gray-900 dark:text-white">{{ $reverbStatus['app_id'] ?? 'N/A' }}</span>
                    </div>
                    @if(!empty($reverbStatus['error']))
                        <div class="rounded-xl bg-red-50 p-4 dark:bg-red-900/20">
                            <p class="text-sm text-red-700 dark:text-red-400">{{ $reverbStatus['error'] }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Queue Status --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Queue Workers</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Background job processing</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="mb-6 grid grid-cols-3 gap-4">
                    <div class="rounded-xl bg-blue-50 p-4 text-center dark:bg-blue-900/20">
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $queueStats['pending_jobs'] ?? 0 }}</p>
                        <p class="text-xs text-blue-600/70 dark:text-blue-400/70">Pending</p>
                    </div>
                    <div class="rounded-xl bg-yellow-50 p-4 text-center dark:bg-yellow-900/20">
                        <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $queueStats['processing_jobs'] ?? 0 }}</p>
                        <p class="text-xs text-yellow-600/70 dark:text-yellow-400/70">Processing</p>
                    </div>
                    <div class="rounded-xl bg-red-50 p-4 text-center dark:bg-red-900/20">
                        <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $queueStats['failed_jobs'] ?? 0 }}</p>
                        <p class="text-xs text-red-600/70 dark:text-red-400/70">Failed</p>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="flex items-center justify-between rounded-xl bg-gray-50 p-4 dark:bg-gray-700/50">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Workers</span>
                        <span class="font-mono text-sm text-gray-900 dark:text-white">{{ $queueStats['worker_status']['worker_count'] ?? 0 }} active</span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl bg-gray-50 p-4 dark:bg-gray-700/50">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Jobs/Hour</span>
                        <span class="font-mono text-sm text-gray-900 dark:text-white">{{ $queueStats['jobs_per_hour'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cache Status --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Cache ({{ ucfirst($cacheStats['driver'] ?? 'unknown') }})</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Application caching layer</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between rounded-xl bg-gray-50 p-4 dark:bg-gray-700/50">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Status</span>
                        <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-medium
                            @if($cacheStats['working'] ?? false) bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                            @else bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 @endif">
                            {{ ($cacheStats['working'] ?? false) ? 'Connected' : 'Error' }}
                        </span>
                    </div>
                    @if(!empty($cacheStats['redis_info']))
                        <div class="flex items-center justify-between rounded-xl bg-gray-50 p-4 dark:bg-gray-700/50">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Redis Version</span>
                            <span class="font-mono text-sm text-gray-900 dark:text-white">{{ $cacheStats['redis_info']['version'] ?? 'N/A' }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl bg-gray-50 p-4 dark:bg-gray-700/50">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Memory Used</span>
                            <span class="font-mono text-sm text-gray-900 dark:text-white">{{ $cacheStats['redis_info']['used_memory'] ?? 'N/A' }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-xl bg-gray-50 p-4 dark:bg-gray-700/50">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Connected Clients</span>
                            <span class="font-mono text-sm text-gray-900 dark:text-white">{{ $cacheStats['redis_info']['connected_clients'] ?? 0 }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Database Status --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-amber-600">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Database ({{ ucfirst($databaseStats['driver'] ?? 'unknown') }})</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Primary data storage</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between rounded-xl bg-gray-50 p-4 dark:bg-gray-700/50">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Status</span>
                        <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-medium
                            @if($databaseStats['connected'] ?? false) bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                            @else bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 @endif">
                            {{ ($databaseStats['connected'] ?? false) ? 'Connected' : 'Error' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl bg-gray-50 p-4 dark:bg-gray-700/50">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Database</span>
                        <span class="font-mono text-sm text-gray-900 dark:text-white">{{ $databaseStats['database'] ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl bg-gray-50 p-4 dark:bg-gray-700/50">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Version</span>
                        <span class="font-mono text-sm text-gray-900 dark:text-white">{{ $databaseStats['version'] ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
