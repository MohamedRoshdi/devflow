<div wire:init="loadSystemData" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Hero Section -->
    <div class="mb-10 relative">
        <div class="absolute inset-0 rounded-3xl bg-gradient-to-r from-purple-500 via-pink-500 to-red-500 opacity-80 blur-xl"></div>
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-purple-900/90 to-pink-900 text-white shadow-2xl">
            <div class="absolute inset-y-0 right-0 w-1/2 bg-[radial-gradient(circle_at_top,_rgba(255,255,255,0.12),_transparent_55%)]"></div>
            <div class="relative p-8 xl:p-10">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <span class="px-3 py-1 text-xs font-semibold tracking-wide uppercase bg-white/10 text-white/80 rounded-full">Production Tools</span>
                        </div>
                        <h1 class="text-4xl lg:text-5xl font-extrabold tracking-tight mb-3">System Administration</h1>
                        <p class="text-white/80 text-lg max-w-2xl">Monitor backups, optimize databases, view logs, and manage production systems</p>
                    </div>
                    <div class="hidden lg:block">
                        <svg class="w-32 h-32 text-white/20" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 3.5a1.5 1.5 0 013 0V4a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-.5a1.5 1.5 0 000 3h.5a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-.5a1.5 1.5 0 00-3 0v.5a1 1 0 01-1 1H6a1 1 0 01-1-1v-3a1 1 0 00-1-1h-.5a1.5 1.5 0 010-3H4a1 1 0 001-1V6a1 1 0 011-1h3a1 1 0 001-1v-.5z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="mb-6 bg-green-50 dark:bg-green-900/30 border-l-4 border-green-500 text-green-800 dark:text-green-400 px-6 py-4 rounded-r-lg shadow">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('message') }}
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 text-red-800 dark:text-red-400 px-6 py-4 rounded-r-lg shadow">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                {{ session('error') }}
            </div>
        </div>
    @endif

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Backup Status -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg dark:shadow-gray-900/50 p-6 hover:shadow-xl transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-full">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <span class="text-xs font-semibold text-green-600 dark:text-green-400 uppercase tracking-wide">Active</span>
            </div>
            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Database Backups</h3>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $backupStats['databases_backed_up'] ?? 3 }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Last: {{ $backupStats['last_backup'] ?? 'Today 02:00' }}</p>
        </div>

        <!-- System Monitoring -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg dark:shadow-gray-900/50 p-6 hover:shadow-xl transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <span class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wide">Every 5min</span>
            </div>
            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">System Monitoring</h3>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ count($recentAlerts) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Recent alerts</p>
        </div>

        <!-- Log Rotation -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg dark:shadow-gray-900/50 p-6 hover:shadow-xl transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-full">
                    <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <span class="text-xs font-semibold text-purple-600 dark:text-purple-400 uppercase tracking-wide">Daily</span>
            </div>
            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Log Rotation</h3>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">14</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Days retention</p>
        </div>

        <!-- DB Optimization -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg dark:shadow-gray-900/50 p-6 hover:shadow-xl transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-full">
                    <svg class="w-8 h-8 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                    </svg>
                </div>
                <span class="text-xs font-semibold text-orange-600 dark:text-orange-400 uppercase tracking-wide">Weekly</span>
            </div>
            <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">DB Optimization</h3>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">Sun 3AM</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Next run schedule</p>
        </div>
    </div>

    <!-- Tabbed Interface -->
    <div class="mb-6">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8 overflow-x-auto">
                <button wire:click="$set('activeTab', 'overview')"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'overview' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zM14 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1h-4a1 1 0 01-1-1v-3z"></path>
                        </svg>
                        <span>Overview</span>
                    </div>
                </button>

                <button wire:click="viewBackupLogs"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50"
                        wire:target="viewBackupLogs"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'backup-logs' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="viewBackupLogs">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                        </svg>
                        <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading wire:target="viewBackupLogs">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span wire:loading.remove wire:target="viewBackupLogs">Backup Logs</span>
                        <span wire:loading wire:target="viewBackupLogs">Loading...</span>
                    </div>
                </button>

                <button wire:click="viewMonitoringLogs"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50"
                        wire:target="viewMonitoringLogs"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'monitoring-logs' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="viewMonitoringLogs">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading wire:target="viewMonitoringLogs">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span wire:loading.remove wire:target="viewMonitoringLogs">Monitoring Logs</span>
                        <span wire:loading wire:target="viewMonitoringLogs">Loading...</span>
                    </div>
                </button>

                <button wire:click="viewOptimizationLogs"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50"
                        wire:target="viewOptimizationLogs"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === 'optimization-logs' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="viewOptimizationLogs">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading wire:target="viewOptimizationLogs">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span wire:loading.remove wire:target="viewOptimizationLogs">Optimization</span>
                        <span wire:loading wire:target="viewOptimizationLogs">Loading...</span>
                    </div>
                </button>
            </nav>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="space-y-8">
        <!-- Overview Tab -->
        @if($activeTab === 'overview')
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Backup Management Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg dark:shadow-gray-900/50 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-6">
                        <h2 class="text-2xl font-bold text-white flex items-center">
                            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                            </svg>
                            Database Backups
                        </h2>
                        <p class="text-white/80 text-sm mt-2">Automated daily backups at 2:00 AM</p>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Status</span>
                            <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full text-sm font-semibold">Active</span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Last Backup</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $backupStats['last_backup'] ?? 'Today 02:00' }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Databases</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $backupStats['databases_backed_up'] ?? 3 }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3">
                            <span class="text-gray-600 dark:text-gray-400">Total Size</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $backupStats['total_size'] ?? '144K' }}</span>
                        </div>
                        <button wire:click="runBackupNow"
                                wire:loading.attr="disabled"
                                class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-lg font-semibold transition-all transform hover:scale-105 shadow disabled:opacity-50">
                            <span wire:loading.remove wire:target="runBackupNow">🔄 Run Backup Now</span>
                            <span wire:loading wire:target="runBackupNow">⏳ Running Backup...</span>
                        </button>
                    </div>
                </div>

                <!-- Database Optimization Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg dark:shadow-gray-900/50 overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-500 to-red-600 p-6">
                        <h2 class="text-2xl font-bold text-white flex items-center">
                            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Database Optimization
                        </h2>
                        <p class="text-white/80 text-sm mt-2">Weekly optimization every Sunday at 3:00 AM</p>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Schedule</span>
                            <span class="font-semibold text-gray-900 dark:text-white">Weekly - Sun 3:00 AM</span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Operations</span>
                            <span class="font-semibold text-gray-900 dark:text-white">OPTIMIZE, ANALYZE</span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">Databases</span>
                            <span class="font-semibold text-gray-900 dark:text-white">MySQL, PostgreSQL</span>
                        </div>
                        <div class="flex items-center justify-between py-3">
                            <span class="text-gray-600 dark:text-gray-400">Status</span>
                            <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded-full text-sm font-semibold">Scheduled</span>
                        </div>
                        <button wire:click="runOptimizationNow"
                                wire:loading.attr="disabled"
                                class="w-full px-6 py-3 bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700 text-white rounded-lg font-semibold transition-all transform hover:scale-105 shadow disabled:opacity-50">
                            <span wire:loading.remove wire:target="runOptimizationNow">⚡ Optimize Now</span>
                            <span wire:loading wire:target="runOptimizationNow">⏳ Optimizing...</span>
                        </button>
                    </div>
                </div>

                <!-- Recent Alerts Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg dark:shadow-gray-900/50 overflow-hidden lg:col-span-2">
                    <div class="bg-gradient-to-r from-yellow-500 to-amber-600 p-6">
                        <h2 class="text-2xl font-bold text-white flex items-center">
                            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            Recent Alerts & Warnings
                        </h2>
                    </div>
                    <div class="p-6">
                        @if(count($recentAlerts) > 0)
                            <div class="space-y-3">
                                @foreach($recentAlerts as $alert)
                                    <div class="p-4 rounded-lg border
                                        @if($alert['level'] === 'ERROR' || $alert['level'] === 'CRITICAL')
                                            bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800
                                        @else
                                            bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800
                                        @endif">
                                        <div class="flex items-start">
                                            <span class="px-2 py-1 text-xs font-bold rounded
                                                @if($alert['level'] === 'ERROR' || $alert['level'] === 'CRITICAL')
                                                    bg-red-600 text-white
                                                @else
                                                    bg-yellow-600 text-white
                                                @endif">
                                                {{ $alert['level'] }}
                                            </span>
                                            <div class="ml-3 flex-1">
                                                <p class="text-sm text-gray-900 dark:text-white font-medium">{{ $alert['message'] }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $alert['timestamp'] }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="mt-2 text-gray-500 dark:text-gray-400">No recent alerts - All systems operational!</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Backup Logs Tab -->
        @if($activeTab === 'backup-logs')
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg dark:shadow-gray-900/50 overflow-hidden">
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-6">
                    <h2 class="text-2xl font-bold text-white">Database Backup Logs</h2>
                    <p class="text-white/80 text-sm mt-2">Latest backup operations and status</p>
                </div>
                <div class="p-6">
                    <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                        <pre class="text-green-400 text-sm font-mono">@foreach($backupLogs as $log){{ $log }}
@endforeach</pre>
                    </div>
                </div>
            </div>
        @endif

        <!-- Monitoring Logs Tab -->
        @if($activeTab === 'monitoring-logs')
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg dark:shadow-gray-900/50 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-6">
                    <h2 class="text-2xl font-bold text-white">System Monitoring Logs</h2>
                    <p class="text-white/80 text-sm mt-2">Real-time system health monitoring</p>
                </div>
                <div class="p-6">
                    <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto max-h-96 overflow-y-auto">
                        <pre class="text-blue-400 text-sm font-mono">@foreach($monitoringLogs as $log){{ $log }}
@endforeach</pre>
                    </div>
                </div>
            </div>
        @endif

        <!-- Optimization Logs Tab -->
        @if($activeTab === 'optimization-logs')
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg dark:shadow-gray-900/50 overflow-hidden">
                <div class="bg-gradient-to-r from-orange-500 to-red-600 p-6">
                    <h2 class="text-2xl font-bold text-white">Database Optimization Logs</h2>
                    <p class="text-white/80 text-sm mt-2">Weekly database maintenance and optimization</p>
                </div>
                <div class="p-6">
                    <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto max-h-96 overflow-y-auto">
                        <pre class="text-orange-400 text-sm font-mono">@foreach($optimizationLogs as $log){{ $log }}
@endforeach</pre>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Info Banner -->
    <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
        <div class="flex items-start">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 mt-1 mr-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div>
                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-200 mb-2">Automated Production Features</h3>
                <ul class="space-y-1 text-sm text-blue-800 dark:text-blue-300">
                    <li>✓ <strong>Backups:</strong> Daily at 2:00 AM - MySQL, PostgreSQL, Redis (7 daily / 4 weekly / 12 monthly retention)</li>
                    <li>✓ <strong>Monitoring:</strong> Every 5 minutes - CPU, Memory, Disk, Docker, SSL, Endpoints</li>
                    <li>✓ <strong>Log Rotation:</strong> Daily - 14 days retention with compression</li>
                    <li>✓ <strong>DB Optimization:</strong> Weekly Sunday 3:00 AM - OPTIMIZE, ANALYZE, VACUUM</li>
                    <li>✓ <strong>SSL Checks:</strong> Daily - 30-day expiration warnings</li>
                    <li>✓ <strong>Metrics Cleanup:</strong> Daily - Remove data older than 90 days</li>
                </ul>
            </div>
        </div>
    </div>
</div>
