<div x-data="serverMetrics()" x-init="initCharts()" wire:ignore.self>
    <!-- Hero Section -->
    <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-indigo-800 via-purple-900 to-indigo-800 p-8 shadow-2xl overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="metrics-pattern" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                        <rect x="0" y="0" width="4" height="4" fill="currentColor" class="text-white"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#metrics-pattern)"/>
            </svg>
        </div>

        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex items-start gap-4">
                    <!-- Metrics Icon -->
                    <div class="p-4 bg-white/10 backdrop-blur-md rounded-2xl">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>

                    <div>
                        <h1 class="text-3xl font-bold text-white">Real-time Server Metrics</h1>
                        <div class="flex flex-wrap items-center gap-3 mt-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/10 text-white/90">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                                </svg>
                                {{ $server->name }}
                            </span>
                            <!-- Live Indicator -->
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-500/20 text-green-300 border border-green-500/30">
                                <span class="relative flex h-2 w-2 mr-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                                </span>
                                Live Updates
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button wire:click="refreshMetrics"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            wire:target="refreshMetrics"
                            class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-all duration-200 font-medium flex items-center gap-2">
                        <svg wire:loading.remove wire:target="refreshMetrics" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <svg wire:loading wire:target="refreshMetrics" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="refreshMetrics">Collect Now</span>
                        <span wire:loading wire:target="refreshMetrics">Collecting...</span>
                    </button>
                    <a href="{{ route('servers.show', $server) }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-all duration-200 font-medium">
                        Back to Server
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Banner -->
    @if($this->alertStatus['status'] !== 'healthy' && $this->alertStatus['status'] !== 'unknown')
        <div class="mb-6 rounded-xl p-4 {{ $this->alertStatus['status'] === 'critical' ? 'bg-red-500/10 border border-red-500/30' : 'bg-yellow-500/10 border border-yellow-500/30' }}">
            <div class="flex items-start gap-3">
                <div class="p-2 rounded-lg {{ $this->alertStatus['status'] === 'critical' ? 'bg-red-500/20' : 'bg-yellow-500/20' }}">
                    <svg class="w-5 h-5 {{ $this->alertStatus['status'] === 'critical' ? 'text-red-400' : 'text-yellow-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold {{ $this->alertStatus['status'] === 'critical' ? 'text-red-400' : 'text-yellow-400' }}">
                        {{ $this->alertStatus['status'] === 'critical' ? 'Critical Alert' : 'Warning' }}
                    </h3>
                    <div class="mt-1 space-y-1">
                        @foreach($this->alertStatus['alerts'] as $alert)
                            <p class="text-sm {{ $alert['type'] === 'critical' ? 'text-red-300' : 'text-yellow-300' }}">
                                {{ $alert['metric'] }} usage is at {{ number_format($alert['value'], 1) }}%
                            </p>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Time Range Selector -->
    <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 transition-colors">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Time Range:</span>
                <div class="flex gap-2">
                    @foreach(['1h' => '1 Hour', '6h' => '6 Hours', '24h' => '24 Hours', '7d' => '7 Days'] as $key => $label)
                        <button wire:click="setPeriod('{{ $key }}')"
                                class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                                @if($period === $key) bg-indigo-600 text-white shadow-md @else bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 @endif">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Last updated: <span x-text="lastUpdate">{{ $latestMetric?->recorded_at?->diffForHumans() ?? 'Never' }}</span>
            </div>
        </div>
    </div>

    @if($latestMetric)
        <!-- Current Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- CPU Usage Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 transition-colors">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">CPU Usage</h3>
                    <div class="p-3 rounded-xl
                        @if($latestMetric->cpu_usage > 80) bg-red-100 dark:bg-red-900/30
                        @elseif($latestMetric->cpu_usage > 60) bg-yellow-100 dark:bg-yellow-900/30
                        @else bg-blue-100 dark:bg-blue-900/30
                        @endif">
                        <svg class="w-6 h-6
                            @if($latestMetric->cpu_usage > 80) text-red-600 dark:text-red-400
                            @elseif($latestMetric->cpu_usage > 60) text-yellow-600 dark:text-yellow-400
                            @else text-blue-600 dark:text-blue-400
                            @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mb-3">
                    <span class="text-4xl font-bold text-gray-900 dark:text-white" x-text="currentMetrics.cpu">{{ number_format($latestMetric->cpu_usage, 1) }}</span>
                    <span class="text-xl text-gray-500 dark:text-gray-400">%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                    <div class="h-3 rounded-full transition-all duration-500
                        @if($latestMetric->cpu_usage > 80) bg-gradient-to-r from-red-500 to-red-600
                        @elseif($latestMetric->cpu_usage > 60) bg-gradient-to-r from-yellow-500 to-orange-500
                        @else bg-gradient-to-r from-blue-500 to-indigo-600
                        @endif" :style="'width: ' + Math.min(currentMetrics.cpu, 100) + '%'"></div>
                </div>
                <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    Load Avg: {{ $latestMetric->load_average_1 }}, {{ $latestMetric->load_average_5 }}, {{ $latestMetric->load_average_15 }}
                </div>
            </div>

            <!-- Memory Usage Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 transition-colors">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Memory Usage</h3>
                    <div class="p-3 rounded-xl
                        @if($latestMetric->memory_usage > 80) bg-red-100 dark:bg-red-900/30
                        @elseif($latestMetric->memory_usage > 60) bg-yellow-100 dark:bg-yellow-900/30
                        @else bg-green-100 dark:bg-green-900/30
                        @endif">
                        <svg class="w-6 h-6
                            @if($latestMetric->memory_usage > 80) text-red-600 dark:text-red-400
                            @elseif($latestMetric->memory_usage > 60) text-yellow-600 dark:text-yellow-400
                            @else text-green-600 dark:text-green-400
                            @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                </div>
                <div class="mb-3">
                    <span class="text-4xl font-bold text-gray-900 dark:text-white" x-text="currentMetrics.memory">{{ number_format($latestMetric->memory_usage, 1) }}</span>
                    <span class="text-xl text-gray-500 dark:text-gray-400">%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                    <div class="h-3 rounded-full transition-all duration-500
                        @if($latestMetric->memory_usage > 80) bg-gradient-to-r from-red-500 to-red-600
                        @elseif($latestMetric->memory_usage > 60) bg-gradient-to-r from-yellow-500 to-orange-500
                        @else bg-gradient-to-r from-green-500 to-emerald-600
                        @endif" :style="'width: ' + Math.min(currentMetrics.memory, 100) + '%'"></div>
                </div>
                <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    {{ number_format($latestMetric->memory_used_mb / 1024, 1) }} GB / {{ number_format($latestMetric->memory_total_mb / 1024, 1) }} GB
                </div>
            </div>

            <!-- Disk Usage Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 transition-colors">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Disk Usage</h3>
                    <div class="p-3 rounded-xl
                        @if($latestMetric->disk_usage > 80) bg-red-100 dark:bg-red-900/30
                        @elseif($latestMetric->disk_usage > 60) bg-yellow-100 dark:bg-yellow-900/30
                        @else bg-purple-100 dark:bg-purple-900/30
                        @endif">
                        <svg class="w-6 h-6
                            @if($latestMetric->disk_usage > 80) text-red-600 dark:text-red-400
                            @elseif($latestMetric->disk_usage > 60) text-yellow-600 dark:text-yellow-400
                            @else text-purple-600 dark:text-purple-400
                            @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                        </svg>
                    </div>
                </div>
                <div class="mb-3">
                    <span class="text-4xl font-bold text-gray-900 dark:text-white" x-text="currentMetrics.disk">{{ number_format($latestMetric->disk_usage, 1) }}</span>
                    <span class="text-xl text-gray-500 dark:text-gray-400">%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                    <div class="h-3 rounded-full transition-all duration-500
                        @if($latestMetric->disk_usage > 80) bg-gradient-to-r from-red-500 to-red-600
                        @elseif($latestMetric->disk_usage > 60) bg-gradient-to-r from-yellow-500 to-orange-500
                        @else bg-gradient-to-r from-purple-500 to-pink-600
                        @endif" :style="'width: ' + Math.min(currentMetrics.disk, 100) + '%'"></div>
                </div>
                <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    {{ $latestMetric->disk_used_gb }} GB / {{ $latestMetric->disk_total_gb }} GB
                </div>
            </div>
        </div>

        <!-- Real-time Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- CPU & Memory Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 transition-colors">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                    CPU & Memory Trend
                </h3>
                <div class="h-64">
                    <canvas id="cpuMemoryChart"></canvas>
                </div>
            </div>

            <!-- Disk & Load Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 transition-colors">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Disk Usage & Load Average
                </h3>
                <div class="h-64">
                    <canvas id="diskLoadChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Network Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Network In -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transition-colors">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-xl">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Network In (Total)</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ number_format($latestMetric->network_in_bytes / 1024 / 1024 / 1024, 2) }} GB
                        </p>
                    </div>
                </div>
            </div>

            <!-- Network Out -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transition-colors">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl">
                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Network Out (Total)</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ number_format($latestMetric->network_out_bytes / 1024 / 1024 / 1024, 2) }} GB
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Metrics History Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl transition-colors overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-750">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Metrics History
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Showing data for the last {{ $period }}</p>
        </div>
        <div class="p-6">
            @if($metrics->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Time</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">CPU %</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Memory %</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Memory Used</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Disk %</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Disk Used</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Load Avg</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($metrics->take(20) as $metric)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white whitespace-nowrap">
                                        {{ $metric->recorded_at->format('M d, H:i') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm font-semibold whitespace-nowrap
                                        @if($metric->cpu_usage > 80) text-red-600 dark:text-red-400
                                        @elseif($metric->cpu_usage > 60) text-yellow-600 dark:text-yellow-400
                                        @else text-green-600 dark:text-green-400
                                        @endif">
                                        {{ number_format($metric->cpu_usage, 1) }}%
                                    </td>
                                    <td class="px-4 py-3 text-sm font-semibold whitespace-nowrap
                                        @if($metric->memory_usage > 80) text-red-600 dark:text-red-400
                                        @elseif($metric->memory_usage > 60) text-yellow-600 dark:text-yellow-400
                                        @else text-green-600 dark:text-green-400
                                        @endif">
                                        {{ number_format($metric->memory_usage, 1) }}%
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                        {{ number_format($metric->memory_used_mb / 1024, 1) }} / {{ number_format($metric->memory_total_mb / 1024, 1) }} GB
                                    </td>
                                    <td class="px-4 py-3 text-sm font-semibold whitespace-nowrap
                                        @if($metric->disk_usage > 80) text-red-600 dark:text-red-400
                                        @elseif($metric->disk_usage > 60) text-yellow-600 dark:text-yellow-400
                                        @else text-green-600 dark:text-green-400
                                        @endif">
                                        {{ number_format($metric->disk_usage, 1) }}%
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                        {{ $metric->disk_used_gb }} / {{ $metric->disk_total_gb }} GB
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                        {{ $metric->load_average_1 }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">No metrics available for this period</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">Click "Collect Now" to gather server metrics</p>
                    <button wire:click="refreshMetrics"
                            class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors font-medium">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Collect Metrics Now
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

@script
<script>
    Alpine.data('serverMetrics', () => ({
        cpuMemoryChart: null,
        diskLoadChart: null,
        lastUpdate: '{{ $latestMetric?->recorded_at?->diffForHumans() ?? "Never" }}',
        currentMetrics: {
            cpu: {{ $latestMetric?->cpu_usage ?? 0 }},
            memory: {{ $latestMetric?->memory_usage ?? 0 }},
            disk: {{ $latestMetric?->disk_usage ?? 0 }}
        },

        initCharts() {
            const chartData = @json($this->chartData);
            this.createCpuMemoryChart(chartData);
            this.createDiskLoadChart(chartData);

            // Listen for chart updates from Livewire
            Livewire.on('metrics-chart-update', (event) => {
                this.updateCharts(event.data);
                this.lastUpdate = 'Just now';
            });

            // Listen for real-time WebSocket updates
            if (window.Echo) {
                window.Echo.channel('server-metrics.{{ $server->id }}')
                    .listen('ServerMetricsUpdated', (e) => {
                        console.log('Real-time metrics received:', e);
                        this.currentMetrics = {
                            cpu: e.metrics.cpu_usage,
                            memory: e.metrics.memory_usage,
                            disk: e.metrics.disk_usage
                        };
                        this.lastUpdate = 'Just now';

                        // Show toast for alerts
                        if (e.alerts && e.alerts.length > 0) {
                            e.alerts.forEach(alert => {
                                window.showToast(alert.message, alert.type === 'critical' ? 'error' : 'warning');
                            });
                        }
                    });
            }
        },

        createCpuMemoryChart(data) {
            const ctx = document.getElementById('cpuMemoryChart');
            if (!ctx || !window.Chart) return;

            const isDark = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
            const textColor = isDark ? '#9CA3AF' : '#6B7280';

            this.cpuMemoryChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'CPU %',
                            data: data.cpu,
                            borderColor: 'rgb(99, 102, 241)',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 2,
                            pointHoverRadius: 5
                        },
                        {
                            label: 'Memory %',
                            data: data.memory,
                            borderColor: 'rgb(16, 185, 129)',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 2,
                            pointHoverRadius: 5
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { color: textColor }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            grid: { color: gridColor },
                            ticks: { color: textColor }
                        },
                        x: {
                            grid: { color: gridColor },
                            ticks: { color: textColor, maxRotation: 0 }
                        }
                    }
                }
            });
        },

        createDiskLoadChart(data) {
            const ctx = document.getElementById('diskLoadChart');
            if (!ctx || !window.Chart) return;

            const isDark = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
            const textColor = isDark ? '#9CA3AF' : '#6B7280';

            this.diskLoadChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Disk %',
                            data: data.disk,
                            borderColor: 'rgb(168, 85, 247)',
                            backgroundColor: 'rgba(168, 85, 247, 0.1)',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 2,
                            pointHoverRadius: 5,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Load Avg',
                            data: data.load,
                            borderColor: 'rgb(245, 158, 11)',
                            backgroundColor: 'rgba(245, 158, 11, 0.1)',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 2,
                            pointHoverRadius: 5,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { color: textColor }
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true,
                            max: 100,
                            grid: { color: gridColor },
                            ticks: { color: textColor }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            beginAtZero: true,
                            grid: { drawOnChartArea: false },
                            ticks: { color: textColor }
                        },
                        x: {
                            grid: { color: gridColor },
                            ticks: { color: textColor, maxRotation: 0 }
                        }
                    }
                }
            });
        },

        updateCharts(data) {
            if (this.cpuMemoryChart) {
                this.cpuMemoryChart.data.labels = data.labels;
                this.cpuMemoryChart.data.datasets[0].data = data.cpu;
                this.cpuMemoryChart.data.datasets[1].data = data.memory;
                this.cpuMemoryChart.update('none');
            }

            if (this.diskLoadChart) {
                this.diskLoadChart.data.labels = data.labels;
                this.diskLoadChart.data.datasets[0].data = data.disk;
                this.diskLoadChart.data.datasets[1].data = data.load;
                this.diskLoadChart.update('none');
            }

            // Update current metrics
            if (data.cpu.length > 0) {
                this.currentMetrics.cpu = data.cpu[data.cpu.length - 1];
            }
            if (data.memory.length > 0) {
                this.currentMetrics.memory = data.memory[data.memory.length - 1];
            }
            if (data.disk.length > 0) {
                this.currentMetrics.disk = data.disk[data.disk.length - 1];
            }
        }
    }));
</script>
@endscript
