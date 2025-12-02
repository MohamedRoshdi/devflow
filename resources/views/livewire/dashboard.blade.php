<div wire:poll.30s="refreshDashboard">
    <!-- Hero Section with Gradient -->
    <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-blue-500 via-purple-500 to-pink-500 dark:from-blue-600 dark:via-purple-600 dark:to-pink-600 p-8 shadow-xl overflow-hidden">
        <div class="absolute inset-0 bg-black/10 dark:bg-black/20"></div>
        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-3xl lg:text-4xl font-bold text-white">Welcome Back!</h1>
                <p class="text-white/90 text-lg mt-2">Here's your infrastructure overview for today</p>
            </div>
            <!-- Quick stats in hero -->
            <div class="mt-6 lg:mt-0 flex flex-wrap gap-4">
                <div class="bg-white/20 backdrop-blur-md rounded-xl px-4 py-3">
                    <div class="text-2xl font-bold text-white">{{ $stats['online_servers'] }}/{{ $stats['total_servers'] }}</div>
                    <div class="text-sm text-white/80">Servers Online</div>
                </div>
                <div class="bg-white/20 backdrop-blur-md rounded-xl px-4 py-3">
                    <div class="text-2xl font-bold text-white">{{ $stats['running_projects'] }}</div>
                    <div class="text-sm text-white/80">Running Projects</div>
                </div>
                <div class="bg-white/20 backdrop-blur-md rounded-xl px-4 py-3">
                    <div class="text-2xl font-bold text-white">{{ $deploymentsToday }}</div>
                    <div class="text-sm text-white/80">Deployments Today</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 8 Stats Cards Grid (2x4) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- 1. Total Servers Card -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-100">Total Servers</p>
                    <p class="text-4xl font-bold text-white mt-2">{{ $stats['total_servers'] }}</p>
                    <p class="text-sm text-blue-100 mt-2">
                        <span class="font-semibold">{{ $stats['online_servers'] }}</span> online,
                        <span class="font-semibold">{{ $stats['total_servers'] - $stats['online_servers'] }}</span> offline
                    </p>
                </div>
                <div class="p-3 bg-white/20 backdrop-blur-md rounded-xl">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- 2. Total Projects Card -->
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 dark:from-green-600 dark:to-emerald-700 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-green-100">Total Projects</p>
                    <p class="text-4xl font-bold text-white mt-2">{{ $stats['total_projects'] }}</p>
                    <p class="text-sm text-green-100 mt-2">
                        <span class="font-semibold">{{ $stats['running_projects'] }}</span> running
                    </p>
                </div>
                <div class="p-3 bg-white/20 backdrop-blur-md rounded-xl">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- 3. Active Deployments Card -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 dark:from-purple-600 dark:to-purple-700 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-purple-100">Active Deployments</p>
                    <div class="flex items-center mt-2">
                        <p class="text-4xl font-bold text-white">{{ $activeDeployments }}</p>
                        @if($activeDeployments > 0)
                            <span class="ml-3 flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-3 w-3 rounded-full bg-white opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-white"></span>
                            </span>
                        @endif
                    </div>
                    <p class="text-sm text-purple-100 mt-2">
                        <span class="font-semibold">{{ $stats['successful_deployments'] }}</span> successful total
                    </p>
                </div>
                <div class="p-3 bg-white/20 backdrop-blur-md rounded-xl">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- 4. SSL Certificates Card -->
        <div class="bg-gradient-to-br from-{{ $sslStats['expiring_soon'] > 0 ? 'amber-500 to-amber-600 dark:from-amber-600 dark:to-amber-700' : 'teal-500 to-teal-600 dark:from-teal-600 dark:to-teal-700' }} rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium {{ $sslStats['expiring_soon'] > 0 ? 'text-amber-100' : 'text-teal-100' }}">SSL Certificates</p>
                    <p class="text-4xl font-bold text-white mt-2">{{ $sslStats['active_certificates'] }}</p>
                    <p class="text-sm {{ $sslStats['expiring_soon'] > 0 ? 'text-amber-100' : 'text-teal-100' }} mt-2">
                        @if($sslStats['expiring_soon'] > 0)
                            <span class="font-semibold">{{ $sslStats['expiring_soon'] }}</span> expiring soon
                        @else
                            All certificates valid
                        @endif
                    </p>
                </div>
                <div class="p-3 bg-white/20 backdrop-blur-md rounded-xl">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- 5. Health Checks Card -->
        <div class="bg-gradient-to-br from-{{ $healthCheckStats['down'] > 0 ? 'red-500 to-red-600 dark:from-red-600 dark:to-red-700' : 'emerald-500 to-emerald-600 dark:from-emerald-600 dark:to-emerald-700' }} rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium {{ $healthCheckStats['down'] > 0 ? 'text-red-100' : 'text-emerald-100' }}">Health Checks</p>
                    <p class="text-4xl font-bold text-white mt-2">{{ $healthCheckStats['healthy'] }}</p>
                    <p class="text-sm {{ $healthCheckStats['down'] > 0 ? 'text-red-100' : 'text-emerald-100' }} mt-2">
                        @if($healthCheckStats['down'] > 0)
                            <span class="font-semibold">{{ $healthCheckStats['down'] }}</span> services down
                        @else
                            All systems operational
                        @endif
                    </p>
                </div>
                <div class="p-3 bg-white/20 backdrop-blur-md rounded-xl">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- 6. Queue Jobs Card -->
        <div class="bg-gradient-to-br from-{{ $queueStats['failed'] > 0 ? 'orange-500 to-orange-600 dark:from-orange-600 dark:to-orange-700' : 'indigo-500 to-indigo-600 dark:from-indigo-600 dark:to-indigo-700' }} rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium {{ $queueStats['failed'] > 0 ? 'text-orange-100' : 'text-indigo-100' }}">Queue Jobs</p>
                    <p class="text-4xl font-bold text-white mt-2">{{ $queueStats['pending'] }}</p>
                    <p class="text-sm {{ $queueStats['failed'] > 0 ? 'text-orange-100' : 'text-indigo-100' }} mt-2">
                        @if($queueStats['failed'] > 0)
                            <span class="font-semibold">{{ $queueStats['failed'] }}</span> failed jobs
                        @else
                            No failed jobs
                        @endif
                    </p>
                </div>
                <div class="p-3 bg-white/20 backdrop-blur-md rounded-xl">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- 7. Deployments Today Card -->
        <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 dark:from-cyan-600 dark:to-cyan-700 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-cyan-100">Deployments Today</p>
                    <p class="text-4xl font-bold text-white mt-2">{{ $deploymentsToday }}</p>
                    <p class="text-sm text-cyan-100 mt-2">
                        <span class="font-semibold">{{ $stats['total_deployments'] }}</span> all time
                    </p>
                </div>
                <div class="p-3 bg-white/20 backdrop-blur-md rounded-xl">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- 8. Security Score Card -->
        <div class="bg-gradient-to-br from-{{ $overallSecurityScore >= 80 ? 'emerald-500 to-emerald-600 dark:from-emerald-600 dark:to-emerald-700' : ($overallSecurityScore >= 60 ? 'yellow-500 to-yellow-600 dark:from-yellow-600 dark:to-yellow-700' : 'red-500 to-red-600 dark:from-red-600 dark:to-red-700') }} rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium {{ $overallSecurityScore >= 80 ? 'text-emerald-100' : ($overallSecurityScore >= 60 ? 'text-yellow-100' : 'text-red-100') }}">Security Score</p>
                    <p class="text-4xl font-bold text-white mt-2">{{ $overallSecurityScore }}%</p>
                    <p class="text-sm {{ $overallSecurityScore >= 80 ? 'text-emerald-100' : ($overallSecurityScore >= 60 ? 'text-yellow-100' : 'text-red-100') }} mt-2">
                        @if($overallSecurityScore >= 80)
                            Excellent security
                        @elseif($overallSecurityScore >= 60)
                            Good security
                        @else
                            Needs attention
                        @endif
                    </p>
                </div>
                <div class="p-3 bg-white/20 backdrop-blur-md rounded-xl">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Panel -->
    @if($showQuickActions)
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Quick Actions</h2>
            <button wire:click="toggleSection('quickActions')" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
        </div>

        @if(!in_array('quickActions', $collapsedSections))
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
            <!-- New Project -->
            <a href="{{ route('projects.create') }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 text-center hover:shadow-lg hover:scale-105 transform transition-all duration-300 border-2 border-transparent hover:border-blue-500">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg inline-block mb-2">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">New Project</p>
            </a>

            <!-- Add Server -->
            <a href="{{ route('servers.create') }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 text-center hover:shadow-lg hover:scale-105 transform transition-all duration-300 border-2 border-transparent hover:border-green-500">
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg inline-block mb-2">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"></path>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">Add Server</p>
            </a>

            <!-- Deploy All -->
            <button onclick="confirm('Deploy all projects?') || event.stopImmediatePropagation()" class="bg-white dark:bg-gray-800 rounded-xl p-4 text-center hover:shadow-lg hover:scale-105 transform transition-all duration-300 border-2 border-transparent hover:border-purple-500">
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg inline-block mb-2">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">Deploy All</p>
            </button>

            <!-- Clear Caches -->
            <button wire:click="clearAllCaches" class="bg-white dark:bg-gray-800 rounded-xl p-4 text-center hover:shadow-lg hover:scale-105 transform transition-all duration-300 border-2 border-transparent hover:border-orange-500">
                <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-lg inline-block mb-2">
                    <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">Clear Caches</p>
            </button>

            <!-- View Logs -->
            <a href="{{ route('logs.index') }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 text-center hover:shadow-lg hover:scale-105 transform transition-all duration-300 border-2 border-transparent hover:border-red-500">
                <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-lg inline-block mb-2">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">View Logs</p>
            </a>

            <!-- Health Checks -->
            <a href="{{ route('settings.health-checks') }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 text-center hover:shadow-lg hover:scale-105 transform transition-all duration-300 border-2 border-transparent hover:border-teal-500">
                <div class="p-3 bg-teal-100 dark:bg-teal-900/30 rounded-lg inline-block mb-2">
                    <svg class="w-6 h-6 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">Health Checks</p>
            </a>

            <!-- Settings -->
            <a href="{{ route('settings.preferences') }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 text-center hover:shadow-lg hover:scale-105 transform transition-all duration-300 border-2 border-transparent hover:border-gray-500">
                <div class="p-3 bg-gray-100 dark:bg-gray-900/30 rounded-lg inline-block mb-2">
                    <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">Settings</p>
            </a>
        </div>
        @endif
    </div>
    @endif

    <!-- Activity Feed and Server Health Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Activity Feed (2/3 width) -->
        @if($showActivityFeed)
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Recent Activity</h2>
                        </div>
                        <span class="text-sm text-gray-500 dark:text-gray-400" wire:poll.15s>
                            Auto-refresh
                        </span>
                    </div>
                </div>
                <div class="p-6">
                    <div class="flow-root">
                        <ul class="-mb-8">
                            @forelse($recentActivity as $index => $activity)
                                <li>
                                    <div class="relative pb-8">
                                        @if($index < count($recentActivity) - 1)
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-gray-800
                                                    @if($activity['type'] === 'deployment')
                                                        @if($activity['status'] === 'success') bg-green-500
                                                        @elseif($activity['status'] === 'failed') bg-red-500
                                                        @elseif($activity['status'] === 'running') bg-yellow-500
                                                        @else bg-gray-500
                                                        @endif
                                                    @else
                                                        bg-blue-500
                                                    @endif">
                                                    @if($activity['type'] === 'deployment')
                                                        <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                                        </svg>
                                                    @else
                                                        <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                        </svg>
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="flex-1 min-w-0 pt-1.5">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ $activity['title'] }}
                                                    </p>
                                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                        {{ $activity['description'] }}
                                                    </p>
                                                    <div class="mt-2 flex items-center space-x-3">
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                                            by {{ $activity['user'] }}
                                                        </span>
                                                        <span class="text-xs text-gray-400 dark:text-gray-500">
                                                            {{ $activity['timestamp']->diffForHumans() }}
                                                        </span>
                                                        @if($activity['type'] === 'deployment')
                                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                                                @if($activity['status'] === 'success') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                                                @elseif($activity['status'] === 'failed') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                                                @elseif($activity['status'] === 'running') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                                                                @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                                                @endif">
                                                                {{ ucfirst($activity['status']) }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li class="text-center py-12">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No recent activity</p>
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Server Health Summary (1/3 width) -->
        @if($showServerHealth)
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Server Health</h2>
                    </div>
                </div>
                <div class="p-6 space-y-4 max-h-[600px] overflow-y-auto">
                    @forelse($serverHealth as $server)
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="font-medium text-gray-900 dark:text-white">{{ $server['server_name'] }}</h3>
                                <span class="flex items-center">
                                    <span class="h-2 w-2 rounded-full mr-2
                                        @if($server['health_status'] === 'healthy') bg-green-500
                                        @elseif($server['health_status'] === 'warning') bg-yellow-500
                                        @else bg-red-500
                                        @endif">
                                    </span>
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ ucfirst($server['health_status']) }}</span>
                                </span>
                            </div>

                            @if($server['cpu_usage'] !== null)
                                <!-- CPU Usage -->
                                <div class="mb-3">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-xs text-gray-600 dark:text-gray-400">CPU</span>
                                        <span class="text-xs font-medium text-gray-900 dark:text-white">{{ number_format($server['cpu_usage'], 1) }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                        <div class="h-2 rounded-full transition-all duration-500
                                            @if($server['cpu_usage'] < 60) bg-green-500
                                            @elseif($server['cpu_usage'] < 80) bg-yellow-500
                                            @else bg-red-500
                                            @endif"
                                            style="width: {{ min($server['cpu_usage'], 100) }}%">
                                        </div>
                                    </div>
                                </div>

                                <!-- Memory Usage -->
                                <div class="mb-3">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-xs text-gray-600 dark:text-gray-400">Memory</span>
                                        <span class="text-xs font-medium text-gray-900 dark:text-white">{{ number_format($server['memory_usage'], 1) }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                        <div class="h-2 rounded-full transition-all duration-500
                                            @if($server['memory_usage'] < 60) bg-green-500
                                            @elseif($server['memory_usage'] < 80) bg-yellow-500
                                            @else bg-red-500
                                            @endif"
                                            style="width: {{ min($server['memory_usage'], 100) }}%">
                                        </div>
                                    </div>
                                </div>

                                <!-- Disk Usage -->
                                <div class="mb-2">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-xs text-gray-600 dark:text-gray-400">Disk</span>
                                        <span class="text-xs font-medium text-gray-900 dark:text-white">{{ number_format($server['disk_usage'], 1) }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                        <div class="h-2 rounded-full transition-all duration-500
                                            @if($server['disk_usage'] < 60) bg-green-500
                                            @elseif($server['disk_usage'] < 80) bg-yellow-500
                                            @else bg-red-500
                                            @endif"
                                            style="width: {{ min($server['disk_usage'], 100) }}%">
                                        </div>
                                    </div>
                                </div>
                            @else
                                <p class="text-xs text-gray-500 dark:text-gray-400">No metrics available</p>
                            @endif

                            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                                <a href="{{ route('servers.show', $server['server_id']) }}" class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium">
                                    View Details →
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"></path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No servers online</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
