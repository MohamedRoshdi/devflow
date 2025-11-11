<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-white">Analytics Dashboard</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Monitor performance and deployment metrics</p>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Time Period</label>
                <select wire:model.live="selectedPeriod" class="input">
                    <option value="24hours">Last 24 Hours</option>
                    <option value="7days">Last 7 Days</option>
                    <option value="30days">Last 30 Days</option>
                    <option value="90days">Last 90 Days</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Project</label>
                <select wire:model.live="selectedProject" class="input">
                    <option value="">All Projects</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Deployment Statistics -->
    <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Deployment Statistics</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400">Total Deployments</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $deploymentStats['total'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400">Successful</p>
                <p class="text-3xl font-bold text-green-600 mt-2">{{ $deploymentStats['successful'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400">Failed</p>
                <p class="text-3xl font-bold text-red-600 mt-2">{{ $deploymentStats['failed'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400">Avg Duration</p>
                <p class="text-3xl font-bold text-blue-600 mt-2">{{ $deploymentStats['avg_duration'] ?? 0 }}s</p>
            </div>
        </div>
    </div>

    <!-- Server Performance -->
    <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Server Performance</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-4">Average CPU Usage</p>
                <div class="flex items-center">
                    <div class="flex-1">
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                            <div class="bg-blue-600 h-4 rounded-full" style="width: {{ $serverMetrics->avg_cpu ?? 0 }}%"></div>
                        </div>
                    </div>
                    <span class="ml-4 text-2xl font-bold text-gray-900 dark:text-white dark:text-white">{{ round($serverMetrics->avg_cpu ?? 0, 1) }}%</span>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-4">Average Memory Usage</p>
                <div class="flex items-center">
                    <div class="flex-1">
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                            <div class="bg-green-600 h-4 rounded-full" style="width: {{ $serverMetrics->avg_memory ?? 0 }}%"></div>
                        </div>
                    </div>
                    <span class="ml-4 text-2xl font-bold text-gray-900 dark:text-white dark:text-white">{{ round($serverMetrics->avg_memory ?? 0, 1) }}%</span>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-4">Average Disk Usage</p>
                <div class="flex items-center">
                    <div class="flex-1">
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                            <div class="bg-yellow-600 h-4 rounded-full" style="width: {{ $serverMetrics->avg_disk ?? 0 }}%"></div>
                        </div>
                    </div>
                    <span class="ml-4 text-2xl font-bold text-gray-900 dark:text-white dark:text-white">{{ round($serverMetrics->avg_disk ?? 0, 1) }}%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Analytics -->
    <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Project Analytics</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400">Total Projects</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $projectAnalytics['total_projects'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400">Running</p>
                <p class="text-3xl font-bold text-green-600 mt-2">{{ $projectAnalytics['running'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400">Stopped</p>
                <p class="text-3xl font-bold text-gray-600 dark:text-gray-400 mt-2">{{ $projectAnalytics['stopped'] }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400">Total Storage</p>
                <p class="text-3xl font-bold text-blue-600 mt-2">{{ round($projectAnalytics['total_storage'] / 1024, 2) }} GB</p>
            </div>
        </div>
    </div>
</div>

