<div wire:poll.30s="refreshDashboard" class="space-y-6">

    {{-- System Status Banner (only show if issues) --}}
    @if(($healthCheckStats['down'] ?? 0) > 0 || ($queueStats['failed'] ?? 0) > 0)
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
        <div class="flex items-center gap-3">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-red-800 dark:text-red-200">
                    @if(($healthCheckStats['down'] ?? 0) > 0)
                        {{ $healthCheckStats['down'] }} service(s) down
                    @endif
                    @if(($healthCheckStats['down'] ?? 0) > 0 && ($queueStats['failed'] ?? 0) > 0) &bull; @endif
                    @if(($queueStats['failed'] ?? 0) > 0)
                        {{ $queueStats['failed'] }} failed job(s)
                    @endif
                </p>
            </div>
            <a href="{{ route('settings.health-checks') }}" class="text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">View →</a>
        </div>
    </div>
    @endif

    {{-- Welcome Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white">
                @if($isNewUser)
                    Welcome to DevFlow Pro
                @else
                    Dashboard
                @endif
            </h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">
                @if($isNewUser)
                    Your DevOps command center for servers, deployments & infrastructure
                @else
                    {{ now()->format('l, F j, Y') }}
                @endif
            </p>
        </div>
        <div class="flex items-center gap-3">
            @if($activeDeployments > 0)
            <div class="flex items-center gap-2 px-4 py-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-500 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-purple-600"></span>
                </span>
                <span class="text-sm font-medium text-purple-700 dark:text-purple-300">{{ $activeDeployments }} deploying</span>
            </div>
            @endif
            <a href="{{ route('projects.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-medium rounded-lg shadow-sm transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Project
            </a>
        </div>
    </div>

    {{-- Getting Started Section (for new/incomplete users) --}}
    @if(!$hasCompletedOnboarding)
    <div class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 rounded-2xl p-[2px]">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Setup</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Get your first deployment running in minutes</p>
                    </div>
                </div>
                <button wire:click="dismissGettingStarted" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Progress Steps --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Step 1 --}}
                <a href="{{ route('servers.create') }}" class="group relative flex items-center gap-4 p-4 rounded-xl border-2 transition-all {{ $onboardingSteps['add_server'] ? 'border-green-500 bg-green-50 dark:bg-green-900/10' : 'border-gray-200 dark:border-gray-700 hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/10' }}">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center {{ $onboardingSteps['add_server'] ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700 group-hover:bg-indigo-500' }} transition-colors">
                        @if($onboardingSteps['add_server'])
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <span class="text-sm font-bold text-gray-600 dark:text-gray-300 group-hover:text-white">1</span>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 dark:text-white">Add Server</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">Connect via SSH</p>
                    </div>
                    @if(!$onboardingSteps['add_server'])
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    @endif
                </a>

                {{-- Step 2 --}}
                <a href="{{ route('projects.create') }}" class="group relative flex items-center gap-4 p-4 rounded-xl border-2 transition-all {{ $onboardingSteps['create_project'] ? 'border-green-500 bg-green-50 dark:bg-green-900/10' : ($onboardingSteps['add_server'] ? 'border-gray-200 dark:border-gray-700 hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/10' : 'border-gray-100 dark:border-gray-800 opacity-50 pointer-events-none') }}">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center {{ $onboardingSteps['create_project'] ? 'bg-green-500' : ($onboardingSteps['add_server'] ? 'bg-gray-200 dark:bg-gray-700 group-hover:bg-indigo-500' : 'bg-gray-100 dark:bg-gray-800') }} transition-colors">
                        @if($onboardingSteps['create_project'])
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <span class="text-sm font-bold {{ $onboardingSteps['add_server'] ? 'text-gray-600 dark:text-gray-300 group-hover:text-white' : 'text-gray-400' }}">2</span>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 dark:text-white">Create Project</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">Link Git repository</p>
                    </div>
                </a>

                {{-- Step 3 --}}
                <div class="group relative flex items-center gap-4 p-4 rounded-xl border-2 {{ $onboardingSteps['first_deployment'] ? 'border-green-500 bg-green-50 dark:bg-green-900/10' : ($onboardingSteps['create_project'] ? 'border-gray-200 dark:border-gray-700' : 'border-gray-100 dark:border-gray-800 opacity-50') }}">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center {{ $onboardingSteps['first_deployment'] ? 'bg-green-500' : ($onboardingSteps['create_project'] ? 'bg-gray-200 dark:bg-gray-700' : 'bg-gray-100 dark:bg-gray-800') }}">
                        @if($onboardingSteps['first_deployment'])
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <span class="text-sm font-bold text-gray-600 dark:text-gray-300">3</span>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 dark:text-white">Deploy</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">First deployment</p>
                    </div>
                </div>

                {{-- Step 4 --}}
                <a href="{{ route('domains.index') }}" class="group relative flex items-center gap-4 p-4 rounded-xl border-2 transition-all {{ $onboardingSteps['setup_domain'] ? 'border-green-500 bg-green-50 dark:bg-green-900/10' : ($onboardingSteps['first_deployment'] ? 'border-gray-200 dark:border-gray-700 hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/10' : 'border-gray-100 dark:border-gray-800 opacity-50 pointer-events-none') }}">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center {{ $onboardingSteps['setup_domain'] ? 'bg-green-500' : ($onboardingSteps['first_deployment'] ? 'bg-gray-200 dark:bg-gray-700 group-hover:bg-indigo-500' : 'bg-gray-100 dark:bg-gray-800') }} transition-colors">
                        @if($onboardingSteps['setup_domain'])
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <span class="text-sm font-bold {{ $onboardingSteps['first_deployment'] ? 'text-gray-600 dark:text-gray-300 group-hover:text-white' : 'text-gray-400' }}">4</span>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 dark:text-white">Add Domain</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">SSL & DNS setup</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
    @endif

    {{-- Main Stats Grid --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Servers --}}
        <a href="{{ route('servers.index') }}" class="group bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-500 hover:shadow-lg transition-all">
            <div class="flex items-center justify-between">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg group-hover:bg-blue-500 transition-colors">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                    </svg>
                </div>
                <span class="text-xs font-medium px-2 py-1 rounded-full {{ ($stats['online_servers'] ?? 0) == ($stats['total_servers'] ?? 0) ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' }}">
                    {{ $stats['online_servers'] ?? 0 }}/{{ $stats['total_servers'] ?? 0 }} online
                </span>
            </div>
            <p class="mt-4 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total_servers'] ?? 0 }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Servers</p>
        </a>

        {{-- Projects --}}
        <a href="{{ route('projects.index') }}" class="group bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 hover:border-green-500 dark:hover:border-green-500 hover:shadow-lg transition-all">
            <div class="flex items-center justify-between">
                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg group-hover:bg-green-500 transition-colors">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium px-2 py-1 rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                    {{ $stats['running_projects'] ?? 0 }} running
                </span>
            </div>
            <p class="mt-4 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total_projects'] ?? 0 }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Projects</p>
        </a>

        {{-- Deployments Today --}}
        <a href="{{ route('deployments.index') }}" class="group bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 hover:border-purple-500 dark:hover:border-purple-500 hover:shadow-lg transition-all">
            <div class="flex items-center justify-between">
                <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg group-hover:bg-purple-500 transition-colors">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                </div>
                @if($activeDeployments > 0)
                <span class="text-xs font-medium px-2 py-1 rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 flex items-center gap-1">
                    <span class="w-1.5 h-1.5 bg-purple-500 rounded-full animate-pulse"></span>
                    {{ $activeDeployments }} active
                </span>
                @endif
            </div>
            <p class="mt-4 text-3xl font-bold text-gray-900 dark:text-white">{{ $deploymentsToday }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Deployments Today</p>
        </a>

        {{-- Security Score --}}
        <a href="{{ route('settings.security') }}" class="group bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700 hover:border-emerald-500 dark:hover:border-emerald-500 hover:shadow-lg transition-all">
            <div class="flex items-center justify-between">
                <div class="p-2 {{ $overallSecurityScore >= 80 ? 'bg-emerald-100 dark:bg-emerald-900/30' : ($overallSecurityScore >= 60 ? 'bg-amber-100 dark:bg-amber-900/30' : 'bg-red-100 dark:bg-red-900/30') }} rounded-lg group-hover:bg-emerald-500 transition-colors">
                    <svg class="w-5 h-5 {{ $overallSecurityScore >= 80 ? 'text-emerald-600 dark:text-emerald-400' : ($overallSecurityScore >= 60 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }} group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium px-2 py-1 rounded-full {{ $overallSecurityScore >= 80 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : ($overallSecurityScore >= 60 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400') }}">
                    {{ $overallSecurityScore >= 80 ? 'Excellent' : ($overallSecurityScore >= 60 ? 'Good' : 'Needs work') }}
                </span>
            </div>
            <p class="mt-4 text-3xl font-bold text-gray-900 dark:text-white">{{ $overallSecurityScore }}%</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Security Score</p>
        </a>
    </div>

    {{-- Quick Actions Bar --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Quick Actions</span>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('servers.create') }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/></svg>
                    Add Server
                </a>
                <button wire:click="deployAll" wire:confirm="Deploy all active projects?" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-purple-700 dark:text-purple-300 bg-purple-100 dark:bg-purple-900/30 hover:bg-purple-200 dark:hover:bg-purple-900/50 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    Deploy All
                </button>
                <button wire:click="clearAllCaches" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-orange-700 dark:text-orange-300 bg-orange-100 dark:bg-orange-900/30 hover:bg-orange-200 dark:hover:bg-orange-900/50 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Clear Caches
                </button>
                <a href="{{ route('logs.index') }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    View Logs
                </a>
                <a href="{{ route('settings.health-checks') }}" class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Health Checks
                </a>
            </div>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Recent Activity (2/3) --}}
        <div class="xl:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h2 class="font-semibold text-gray-900 dark:text-white">Recent Activity</h2>
                <a href="{{ route('deployments.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">View all →</a>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($recentActivity as $activity)
                <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 mt-0.5">
                            <span class="w-8 h-8 rounded-full flex items-center justify-center
                                @if($activity['type'] === 'deployment')
                                    @if($activity['status'] === 'success') bg-green-100 dark:bg-green-900/30
                                    @elseif($activity['status'] === 'failed') bg-red-100 dark:bg-red-900/30
                                    @elseif($activity['status'] === 'running') bg-amber-100 dark:bg-amber-900/30
                                    @else bg-gray-100 dark:bg-gray-700
                                    @endif
                                @else bg-blue-100 dark:bg-blue-900/30
                                @endif">
                                @if($activity['type'] === 'deployment')
                                    <svg class="w-4 h-4 @if($activity['status'] === 'success') text-green-600 dark:text-green-400 @elseif($activity['status'] === 'failed') text-red-600 dark:text-red-400 @elseif($activity['status'] === 'running') text-amber-600 dark:text-amber-400 @else text-gray-600 dark:text-gray-400 @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                @endif
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $activity['title'] }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $activity['description'] }}</p>
                            <div class="mt-1 flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500">
                                <span>{{ $activity['user'] }}</span>
                                <span>&bull;</span>
                                <span>{{ $activity['timestamp']->diffForHumans() }}</span>
                                @if($activity['type'] === 'deployment')
                                <span>&bull;</span>
                                <span class="px-1.5 py-0.5 rounded text-xs font-medium
                                    @if($activity['status'] === 'success') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                                    @elseif($activity['status'] === 'failed') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                    @elseif($activity['status'] === 'running') bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400
                                    @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300
                                    @endif">{{ ucfirst($activity['status']) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No recent activity</p>
                    <a href="{{ route('projects.create') }}" class="mt-3 inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800">Create your first project →</a>
                </div>
                @endforelse
            </div>
            @if(count($recentActivity) > 0)
            <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700">
                <button wire:click="loadMoreActivity" wire:loading.attr="disabled" class="w-full text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium disabled:opacity-50">
                    <span wire:loading.remove wire:target="loadMoreActivity">Load more</span>
                    <span wire:loading wire:target="loadMoreActivity">Loading...</span>
                </button>
            </div>
            @endif
        </div>

        {{-- Server Health (1/3) --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h2 class="font-semibold text-gray-900 dark:text-white">Server Health</h2>
                <a href="{{ route('servers.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">View all →</a>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-[400px] overflow-y-auto">
                @forelse($serverHealth as $server)
                <a href="{{ route('servers.show', $server['server_id']) }}" class="block px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-medium text-gray-900 dark:text-white text-sm">{{ $server['server_name'] }}</span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full {{ ($server['health_status'] ?? 'unknown') === 'healthy' ? 'bg-green-500' : (($server['health_status'] ?? 'unknown') === 'warning' ? 'bg-amber-500' : 'bg-red-500') }}"></span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ ucfirst($server['health_status'] ?? 'unknown') }}</span>
                        </span>
                    </div>
                    @if($server['cpu_usage'] !== null)
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500 dark:text-gray-400 w-12">CPU</span>
                            <div class="flex-1 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full rounded-full {{ $server['cpu_usage'] < 60 ? 'bg-green-500' : ($server['cpu_usage'] < 80 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ min($server['cpu_usage'], 100) }}%"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300 w-10 text-right">{{ number_format($server['cpu_usage'], 0) }}%</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500 dark:text-gray-400 w-12">RAM</span>
                            <div class="flex-1 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full rounded-full {{ $server['memory_usage'] < 60 ? 'bg-green-500' : ($server['memory_usage'] < 80 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ min($server['memory_usage'], 100) }}%"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300 w-10 text-right">{{ number_format($server['memory_usage'], 0) }}%</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500 dark:text-gray-400 w-12">Disk</span>
                            <div class="flex-1 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full rounded-full {{ $server['disk_usage'] < 60 ? 'bg-green-500' : ($server['disk_usage'] < 80 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ min($server['disk_usage'], 100) }}%"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300 w-10 text-right">{{ number_format($server['disk_usage'], 0) }}%</span>
                        </div>
                    </div>
                    @else
                    <p class="text-xs text-gray-400 dark:text-gray-500">No metrics available</p>
                    @endif
                </a>
                @empty
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No servers online</p>
                    <a href="{{ route('servers.create') }}" class="mt-3 inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800">Add your first server →</a>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Deployment Timeline --}}
    @if(count($deploymentTimeline) > 0 && collect($deploymentTimeline)->sum('total') > 0)
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900 dark:text-white">Deployment Activity (7 Days)</h2>
            <div class="flex items-center gap-4 text-xs">
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-green-500"></span> Success</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-500"></span> Failed</span>
            </div>
        </div>
        <div class="p-6">
            <div class="flex items-end gap-2 h-32">
                @foreach($deploymentTimeline as $day)
                <div class="flex-1 flex flex-col items-center gap-1">
                    <div class="w-full flex flex-col-reverse gap-0.5" style="height: 100px;">
                        @if($day['total'] > 0)
                            @php $maxTotal = max(collect($deploymentTimeline)->max('total'), 1); @endphp
                            @if($day['successful'] > 0)
                            <div class="w-full bg-green-500 rounded-t transition-all hover:bg-green-600" style="height: {{ ($day['successful'] / $maxTotal) * 100 }}%" title="{{ $day['successful'] }} successful"></div>
                            @endif
                            @if($day['failed'] > 0)
                            <div class="w-full bg-red-500 {{ $day['successful'] == 0 ? 'rounded-t' : '' }} transition-all hover:bg-red-600" style="height: {{ ($day['failed'] / $maxTotal) * 100 }}%" title="{{ $day['failed'] }} failed"></div>
                            @endif
                        @endif
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $day['date'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Secondary Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-teal-100 dark:bg-teal-900/30 rounded-lg">
                    <svg class="w-5 h-5 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $sslStats['active_certificates'] ?? 0 }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">SSL Certificates</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $healthCheckStats['healthy'] ?? 0 }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Health Checks</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $queueStats['pending'] ?? 0 }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Queue Jobs</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-cyan-100 dark:bg-cyan-900/30 rounded-lg">
                    <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_deployments'] ?? 0 }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total Deployments</p>
                </div>
            </div>
        </div>
    </div>

</div>
