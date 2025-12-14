<div wire:init="loadDashboardData" wire:poll.30s="refreshDashboard" class="space-y-6">

    {{-- Animated Background Orbs --}}
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-gradient-to-br from-emerald-500/5 via-teal-500/5 to-cyan-500/5 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-gradient-to-tr from-violet-500/5 via-purple-500/5 to-fuchsia-500/5 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-gradient-to-r from-blue-500/3 to-indigo-500/3 rounded-full blur-3xl"></div>
    </div>

    {{-- System Status Banner (only show if issues) --}}
    @if($healthCheckDown > 0 || $queueFailed > 0)
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
        <div class="flex items-center gap-3">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-red-800 dark:text-red-200">
                    @if($healthCheckDown > 0)
                        {{ $healthCheckDown }} service(s) down
                    @endif
                    @if($healthCheckDown > 0 && $queueFailed > 0) &bull; @endif
                    @if($queueFailed > 0)
                        {{ $queueFailed }} failed job(s)
                    @endif
                </p>
            </div>
            <a href="{{ route('settings.health-checks') }}" class="text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">View &rarr;</a>
        </div>
    </div>
    @endif

    {{-- Welcome Header with Grid Pattern --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900/95 via-slate-800/95 to-slate-900/95 backdrop-blur-sm border border-slate-700/50">
        {{-- Grid Pattern Overlay --}}
        <div class="absolute inset-0 bg-[linear-gradient(to_right,#4f46e520_1px,transparent_1px),linear-gradient(to_bottom,#4f46e520_1px,transparent_1px)] bg-[size:4rem_4rem]"></div>

        <div class="relative px-6 py-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold text-white">
                    @if($isNewUser)
                        Welcome to DevFlow Pro
                    @else
                        Dashboard
                    @endif
                </h1>
                <p class="text-slate-400 mt-1">
                    @if($isNewUser)
                        Your DevOps command center for servers, deployments & infrastructure
                    @else
                        {{ now()->format('l, F j, Y') }}
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-3">
                @if($activeDeployments > 0)
                <div class="flex items-center gap-2 px-4 py-2 bg-purple-500/10 backdrop-blur-sm border border-purple-500/20 rounded-lg">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-500 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-purple-500"></span>
                    </span>
                    <span class="text-sm font-medium text-purple-300">{{ $activeDeployments }} deploying</span>
                </div>
                @endif
                <a href="{{ route('docs.show') }}" class="group relative overflow-hidden inline-flex items-center gap-2 px-4 py-2 bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 hover:border-slate-600 text-slate-300 font-medium rounded-lg transition-all">
                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                    <svg class="w-4 h-4 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    <span class="relative z-10">Docs</span>
                </a>
                <a href="{{ route('projects.create') }}" class="group relative overflow-hidden inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-medium rounded-lg shadow-lg shadow-blue-500/25 transition-all">
                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/25 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                    <svg class="w-4 h-4 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span class="relative z-10">New Project</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Getting Started Section (for new/incomplete users) --}}
    @if(!$hasCompletedOnboarding)
    <div class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 rounded-2xl p-[2px]">
        <div class="bg-slate-900/95 backdrop-blur-sm rounded-2xl p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg shadow-lg shadow-indigo-500/50">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-white">Quick Setup</h2>
                        <p class="text-sm text-slate-400">Get your first deployment running in minutes</p>
                    </div>
                </div>
                <button wire:click="dismissGettingStarted" class="p-1 text-slate-400 hover:text-slate-300 rounded">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Progress Steps --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Step 1 --}}
                <a href="{{ route('servers.create') }}" class="group relative flex items-center gap-4 p-4 rounded-xl border-2 transition-all {{ $onboardingSteps['add_server'] ? 'border-green-500 bg-green-500/10' : 'border-slate-700 hover:border-indigo-500 hover:bg-indigo-500/10' }}">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center {{ $onboardingSteps['add_server'] ? 'bg-green-500 shadow-lg shadow-green-500/50' : 'bg-slate-700 group-hover:bg-indigo-500 group-hover:shadow-lg group-hover:shadow-indigo-500/50' }} transition-all">
                        @if($onboardingSteps['add_server'])
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <span class="text-sm font-bold text-slate-300 group-hover:text-white">1</span>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-white">Add Server</p>
                        <p class="text-xs text-slate-400 truncate">Connect via SSH</p>
                    </div>
                    @if(!$onboardingSteps['add_server'])
                    <svg class="w-5 h-5 text-slate-400 group-hover:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    @endif
                </a>

                {{-- Step 2 --}}
                <a href="{{ route('projects.create') }}" class="group relative flex items-center gap-4 p-4 rounded-xl border-2 transition-all {{ $onboardingSteps['create_project'] ? 'border-green-500 bg-green-500/10' : ($onboardingSteps['add_server'] ? 'border-slate-700 hover:border-indigo-500 hover:bg-indigo-500/10' : 'border-slate-800 opacity-50 pointer-events-none') }}">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center {{ $onboardingSteps['create_project'] ? 'bg-green-500 shadow-lg shadow-green-500/50' : ($onboardingSteps['add_server'] ? 'bg-slate-700 group-hover:bg-indigo-500 group-hover:shadow-lg group-hover:shadow-indigo-500/50' : 'bg-slate-800') }} transition-all">
                        @if($onboardingSteps['create_project'])
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <span class="text-sm font-bold {{ $onboardingSteps['add_server'] ? 'text-slate-300 group-hover:text-white' : 'text-slate-600' }}">2</span>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-white">Create Project</p>
                        <p class="text-xs text-slate-400 truncate">Link Git repository</p>
                    </div>
                </a>

                {{-- Step 3 --}}
                <div class="group relative flex items-center gap-4 p-4 rounded-xl border-2 {{ $onboardingSteps['first_deployment'] ? 'border-green-500 bg-green-500/10' : ($onboardingSteps['create_project'] ? 'border-slate-700' : 'border-slate-800 opacity-50') }}">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center {{ $onboardingSteps['first_deployment'] ? 'bg-green-500 shadow-lg shadow-green-500/50' : ($onboardingSteps['create_project'] ? 'bg-slate-700' : 'bg-slate-800') }}">
                        @if($onboardingSteps['first_deployment'])
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <span class="text-sm font-bold text-slate-300">3</span>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-white">Deploy</p>
                        <p class="text-xs text-slate-400 truncate">First deployment</p>
                    </div>
                </div>

                {{-- Step 4 --}}
                <a href="{{ route('projects.index') }}" class="group relative flex items-center gap-4 p-4 rounded-xl border-2 transition-all {{ $onboardingSteps['setup_domain'] ? 'border-green-500 bg-green-500/10' : ($onboardingSteps['first_deployment'] ? 'border-slate-700 hover:border-indigo-500 hover:bg-indigo-500/10' : 'border-slate-800 opacity-50 pointer-events-none') }}">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center {{ $onboardingSteps['setup_domain'] ? 'bg-green-500 shadow-lg shadow-green-500/50' : ($onboardingSteps['first_deployment'] ? 'bg-slate-700 group-hover:bg-indigo-500 group-hover:shadow-lg group-hover:shadow-indigo-500/50' : 'bg-slate-800') }} transition-all">
                        @if($onboardingSteps['setup_domain'])
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <span class="text-sm font-bold {{ $onboardingSteps['first_deployment'] ? 'text-slate-300 group-hover:text-white' : 'text-slate-600' }}">4</span>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-white">Add Domain</p>
                        <p class="text-xs text-slate-400 truncate">SSL & DNS setup</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
    @endif

    {{-- Stats Component --}}
    <livewire:dashboard.dashboard-stats />

    {{-- Quick Actions Component --}}
    <livewire:dashboard.dashboard-quick-actions />

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Recent Activity (2/3) --}}
        <div class="xl:col-span-2">
            <livewire:dashboard.dashboard-recent-activity />
        </div>

        {{-- Server Health (1/3) --}}
        <livewire:dashboard.dashboard-server-health />
    </div>

    {{-- Deployment Timeline --}}
    @if(count($deploymentTimeline) > 0 && collect($deploymentTimeline)->sum('total') > 0)
    <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700/50 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-700/50 flex items-center justify-between">
            <h2 class="font-semibold text-white">Deployment Activity (7 Days)</h2>
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
                    <span class="text-xs text-slate-400">{{ $day['date'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

</div>
