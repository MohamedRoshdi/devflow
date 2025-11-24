<div>
    <!-- Hero Section with Gradient -->
    <div class="mb-10 relative">
        <div class="absolute inset-0 rounded-3xl bg-gradient-to-r from-purple-500 via-pink-500 to-red-500 opacity-80 blur-xl"></div>
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-purple-900 via-pink-900/90 to-red-900 text-white shadow-2xl">
            <div class="absolute inset-y-0 right-0 w-1/2 bg-[radial-gradient(circle_at_top,_rgba(255,255,255,0.12),_transparent_55%)]"></div>
            <div class="relative p-8 xl:p-10">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-4">
                            <svg class="w-10 h-10 text-white/90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <span class="px-3 py-1 text-xs font-semibold tracking-wide uppercase bg-white/10 text-white/80 rounded-full">Analytics Dashboard</span>
                        </div>
                        <h1 class="text-4xl lg:text-5xl font-extrabold tracking-tight mb-3">Performance Insights</h1>
                        <p class="text-lg text-white/80">Real-time monitoring of deployments, servers, and project metrics</p>
                    </div>
                    <div class="flex flex-wrap gap-4">
                        <div class="bg-white/10 backdrop-blur-sm rounded-2xl px-6 py-4 border border-white/20">
                            <p class="text-sm text-white/70 font-medium">Total Deployments</p>
                            <p class="text-3xl font-bold mt-1">{{ $deploymentStats['total'] }}</p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-sm rounded-2xl px-6 py-4 border border-white/20">
                            <p class="text-sm text-white/70 font-medium">Success Rate</p>
                            <p class="text-3xl font-bold mt-1">{{ $deploymentStats['total'] > 0 ? round(($deploymentStats['successful'] / $deploymentStats['total']) * 100, 1) : 0 }}%</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section with Enhanced Design -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-8 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center gap-3 mb-6">
            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
            </svg>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Filters</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Time Period</label>
                <select wire:model.live="selectedPeriod" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition text-gray-900 dark:text-white">
                    <option value="24hours">Last 24 Hours</option>
                    <option value="7days">Last 7 Days</option>
                    <option value="30days">Last 30 Days</option>
                    <option value="90days">Last 90 Days</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Project Filter</label>
                <select wire:model.live="selectedProject" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition text-gray-900 dark:text-white">
                    <option value="">All Projects</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Deployment Statistics with Enhanced Cards -->
    <div class="mb-10">
        <div class="flex items-center gap-3 mb-6">
            <svg class="w-7 h-7 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
            </svg>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Deployment Statistics</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Deployments -->
            <div class="group relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
                <div class="relative p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-white/20 rounded-xl">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                        </div>
                        <span class="text-4xl font-bold opacity-20">01</span>
                    </div>
                    <p class="text-sm font-medium text-white/80 mb-2">Total Deployments</p>
                    <p class="text-4xl font-extrabold">{{ $deploymentStats['total'] }}</p>
                    <div class="mt-4 pt-4 border-t border-white/20">
                        <p class="text-xs text-white/70">All time deployments</p>
                    </div>
                </div>
            </div>

            <!-- Successful Deployments -->
            <div class="group relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
                <div class="relative p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-white/20 rounded-xl">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <span class="text-4xl font-bold opacity-20">02</span>
                    </div>
                    <p class="text-sm font-medium text-white/80 mb-2">Successful</p>
                    <p class="text-4xl font-extrabold">{{ $deploymentStats['successful'] }}</p>
                    <div class="mt-4 pt-4 border-t border-white/20">
                        <p class="text-xs text-white/70">
                            {{ $deploymentStats['total'] > 0 ? round(($deploymentStats['successful'] / $deploymentStats['total']) * 100, 1) : 0 }}% success rate
                        </p>
                    </div>
                </div>
            </div>

            <!-- Failed Deployments -->
            <div class="group relative overflow-hidden bg-gradient-to-br from-red-500 to-pink-600 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
                <div class="relative p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-white/20 rounded-xl">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <span class="text-4xl font-bold opacity-20">03</span>
                    </div>
                    <p class="text-sm font-medium text-white/80 mb-2">Failed</p>
                    <p class="text-4xl font-extrabold">{{ $deploymentStats['failed'] }}</p>
                    <div class="mt-4 pt-4 border-t border-white/20">
                        <p class="text-xs text-white/70">
                            {{ $deploymentStats['total'] > 0 ? round(($deploymentStats['failed'] / $deploymentStats['total']) * 100, 1) : 0 }}% failure rate
                        </p>
                    </div>
                </div>
            </div>

            <!-- Average Duration -->
            <div class="group relative overflow-hidden bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
                <div class="relative p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-white/20 rounded-xl">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <span class="text-4xl font-bold opacity-20">04</span>
                    </div>
                    <p class="text-sm font-medium text-white/80 mb-2">Avg Duration</p>
                    <p class="text-4xl font-extrabold">{{ $deploymentStats['avg_duration'] ?? 0 }}<span class="text-xl">s</span></p>
                    <div class="mt-4 pt-4 border-t border-white/20">
                        <p class="text-xs text-white/70">Per deployment</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Server Performance with Modern Progress Bars -->
    <div class="mb-10">
        <div class="flex items-center gap-3 mb-6">
            <svg class="w-7 h-7 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"></path>
            </svg>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Server Performance</h2>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- CPU Usage -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">CPU Usage</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Average processor load</p>
                        </div>
                    </div>
                    <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ round($serverMetrics->avg_cpu ?? 0, 1) }}%</span>
                </div>
                <div class="relative">
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-6 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-6 rounded-full transition-all duration-1000 ease-out flex items-center justify-end px-3"
                             style="width: {{ min($serverMetrics->avg_cpu ?? 0, 100) }}%">
                            @if(($serverMetrics->avg_cpu ?? 0) > 10)
                                <span class="text-xs font-bold text-white">{{ round($serverMetrics->avg_cpu ?? 0, 1) }}%</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
                        <span>0%</span>
                        <span>50%</span>
                        <span>100%</span>
                    </div>
                </div>
                @php
                    $cpuUsage = $serverMetrics->avg_cpu ?? 0;
                    $cpuStatus = $cpuUsage < 50 ? ['text' => 'Normal', 'color' => 'text-green-600 dark:text-green-400'] :
                                 ($cpuUsage < 80 ? ['text' => 'Warning', 'color' => 'text-yellow-600 dark:text-yellow-400'] :
                                 ['text' => 'Critical', 'color' => 'text-red-600 dark:text-red-400']);
                @endphp
                <p class="mt-4 text-sm font-medium {{ $cpuStatus['color'] }}">
                    Status: {{ $cpuStatus['text'] }}
                </p>
            </div>

            <!-- Memory Usage -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Memory Usage</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">RAM utilization</p>
                        </div>
                    </div>
                    <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ round($serverMetrics->avg_memory ?? 0, 1) }}%</span>
                </div>
                <div class="relative">
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-6 overflow-hidden">
                        <div class="bg-gradient-to-r from-green-500 to-emerald-600 h-6 rounded-full transition-all duration-1000 ease-out flex items-center justify-end px-3"
                             style="width: {{ min($serverMetrics->avg_memory ?? 0, 100) }}%">
                            @if(($serverMetrics->avg_memory ?? 0) > 10)
                                <span class="text-xs font-bold text-white">{{ round($serverMetrics->avg_memory ?? 0, 1) }}%</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
                        <span>0%</span>
                        <span>50%</span>
                        <span>100%</span>
                    </div>
                </div>
                @php
                    $memUsage = $serverMetrics->avg_memory ?? 0;
                    $memStatus = $memUsage < 60 ? ['text' => 'Normal', 'color' => 'text-green-600 dark:text-green-400'] :
                                 ($memUsage < 85 ? ['text' => 'Warning', 'color' => 'text-yellow-600 dark:text-yellow-400'] :
                                 ['text' => 'Critical', 'color' => 'text-red-600 dark:text-red-400']);
                @endphp
                <p class="mt-4 text-sm font-medium {{ $memStatus['color'] }}">
                    Status: {{ $memStatus['text'] }}
                </p>
            </div>

            <!-- Disk Usage -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Disk Usage</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Storage utilization</p>
                        </div>
                    </div>
                    <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ round($serverMetrics->avg_disk ?? 0, 1) }}%</span>
                </div>
                <div class="relative">
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-6 overflow-hidden">
                        <div class="bg-gradient-to-r from-yellow-500 to-orange-600 h-6 rounded-full transition-all duration-1000 ease-out flex items-center justify-end px-3"
                             style="width: {{ min($serverMetrics->avg_disk ?? 0, 100) }}%">
                            @if(($serverMetrics->avg_disk ?? 0) > 10)
                                <span class="text-xs font-bold text-white">{{ round($serverMetrics->avg_disk ?? 0, 1) }}%</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
                        <span>0%</span>
                        <span>50%</span>
                        <span>100%</span>
                    </div>
                </div>
                @php
                    $diskUsage = $serverMetrics->avg_disk ?? 0;
                    $diskStatus = $diskUsage < 70 ? ['text' => 'Normal', 'color' => 'text-green-600 dark:text-green-400'] :
                                  ($diskUsage < 90 ? ['text' => 'Warning', 'color' => 'text-yellow-600 dark:text-yellow-400'] :
                                  ['text' => 'Critical', 'color' => 'text-red-600 dark:text-red-400']);
                @endphp
                <p class="mt-4 text-sm font-medium {{ $diskStatus['color'] }}">
                    Status: {{ $diskStatus['text'] }}
                </p>
            </div>
        </div>
    </div>

    <!-- Project Analytics with Enhanced Grid -->
    <div>
        <div class="flex items-center gap-3 mb-6">
            <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
            </svg>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Project Analytics</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Projects -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl">
                        <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">Total Projects</p>
                <p class="text-4xl font-extrabold text-gray-900 dark:text-white">{{ $projectAnalytics['total_projects'] }}</p>
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400">All managed projects</p>
                </div>
            </div>

            <!-- Running Projects -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-xl">
                        <svg class="w-7 h-7 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">Running</p>
                <p class="text-4xl font-extrabold text-green-600 dark:text-green-400">{{ $projectAnalytics['running'] }}</p>
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Active and operational</p>
                </div>
            </div>

            <!-- Stopped Projects -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded-xl">
                        <svg class="w-7 h-7 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">Stopped</p>
                <p class="text-4xl font-extrabold text-gray-600 dark:text-gray-400">{{ $projectAnalytics['stopped'] }}</p>
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Currently inactive</p>
                </div>
            </div>

            <!-- Total Storage -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700 hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl">
                        <svg class="w-7 h-7 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">Total Storage</p>
                <p class="text-4xl font-extrabold text-blue-600 dark:text-blue-400">{{ round($projectAnalytics['total_storage'] / 1024, 2) }}<span class="text-xl"> GB</span></p>
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Across all projects</p>
                </div>
            </div>
        </div>
    </div>
</div>
