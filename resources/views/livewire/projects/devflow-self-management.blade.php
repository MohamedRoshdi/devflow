<div>
    <!-- Hero Section - Matching Project Show Style -->
    <div class="mb-10 relative">
        <div class="absolute inset-0 rounded-3xl bg-gradient-to-r from-purple-500 via-indigo-500 to-blue-500 opacity-80 blur-xl"></div>
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-purple-900/90 to-indigo-900 text-white shadow-2xl">
            <div class="absolute inset-y-0 right-0 w-1/2 bg-[radial-gradient(circle_at_top,_rgba(255,255,255,0.12),_transparent_55%)]"></div>
            <div class="relative p-8 xl:p-10 space-y-6">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                    <div class="flex-1 space-y-4">
                        <!-- Badges -->
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="px-3 py-1 text-xs font-semibold tracking-wide uppercase bg-white/10 text-white/80 rounded-full">System</span>
                            <span class="px-3 py-1 text-xs font-semibold tracking-wide uppercase bg-white/10 text-white/60 rounded-full">Laravel {{ app()->version() }}</span>
                            <span class="px-3 py-1 text-xs font-semibold tracking-wide uppercase bg-white/10 text-white/60 rounded-full">PHP {{ PHP_VERSION }}</span>
                        </div>

                        <!-- Title & Status -->
                        <div class="flex flex-wrap items-center gap-4">
                            <h1 class="text-4xl lg:text-5xl font-extrabold tracking-tight">DevFlow Pro</h1>
                            @if($maintenanceMode)
                                <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-semibold shadow-lg backdrop-blur-sm bg-gradient-to-r from-yellow-500 to-amber-500 ring-2 ring-yellow-400/50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    Maintenance
                                </span>
                            @else
                                <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-semibold shadow-lg backdrop-blur-sm bg-gradient-to-r from-green-500 to-emerald-500 ring-2 ring-green-400/50">
                                    <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                                    Live
                                </span>
                            @endif
                        </div>

                        <!-- Info Pills -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm text-indigo-100">
                            <div class="flex items-center gap-2 bg-white/5 rounded-xl px-3 py-2 border border-white/10">
                                <svg class="w-4 h-4 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 16v-2m8-6h2M4 12H2m15.364-7.364l1.414-1.414M6.343 17.657l-1.414 1.414m0-13.657L6.343 6.343m11.314 11.314l1.414 1.414"></path>
                                </svg>
                                <span class="text-white/90">{{ $appEnv }}</span>
                            </div>
                            <div class="flex items-center gap-2 bg-white/5 rounded-xl px-3 py-2 border border-white/10">
                                <svg class="w-4 h-4 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                                </svg>
                                <span class="text-white/90">{{ $cacheDriver }}</span>
                            </div>
                            <div class="flex items-center gap-2 bg-white/5 rounded-xl px-3 py-2 border border-white/10">
                                <svg class="w-4 h-4 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                                <span class="text-white/90">{{ $queueDriver }}</span>
                            </div>
                            <div class="flex items-center gap-2 bg-white/5 rounded-xl px-3 py-2 border border-white/10">
                                <svg class="w-4 h-4 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                                <span class="text-white/90">{{ $debugMode ? 'Debug ON' : 'Debug OFF' }}</span>
                            </div>
                        </div>

                        <!-- Git Info (Compact) -->
                        @if($isGitRepo)
                            <div class="flex items-center gap-4 bg-white/10 border border-white/10 rounded-2xl px-4 py-3">
                                <svg class="w-5 h-5 text-white/70" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                                </svg>
                                <div class="flex-1 flex flex-wrap items-center gap-4 text-sm">
                                    <span class="font-mono text-white/90">{{ $gitBranch }}</span>
                                    <span class="text-white/50">|</span>
                                    <span class="text-white/70 truncate max-w-xs" title="{{ $gitLastCommit }}">{{ Str::limit($gitLastCommit, 50) }}</span>
                                </div>
                                <button wire:click="toggleGitSetup" class="text-xs text-white/60 hover:text-white transition">Configure</button>
                            </div>
                        @else
                            <button wire:click="toggleGitSetup" class="w-full flex items-center justify-center gap-3 bg-white/10 border border-white/10 hover:bg-white/15 rounded-2xl px-4 py-3 transition">
                                <svg class="w-5 h-5 text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                                </svg>
                                <span class="text-white/80 text-sm">No Git Repository - Click to Setup</span>
                            </button>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row lg:flex-col items-stretch gap-3">
                        <button wire:click="redeploy" wire:loading.attr="disabled" @disabled($isDeploying)
                            class="group px-6 py-3 rounded-xl bg-white text-indigo-700 font-semibold shadow-lg hover:shadow-xl transition-transform transform hover:-translate-y-0.5 disabled:opacity-50">
                            <div wire:loading.remove wire:target="redeploy" class="flex items-center justify-center gap-2">
                                <span class="text-lg">🚀</span>
                                Deploy
                            </div>
                            <div wire:loading wire:target="redeploy" class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Deploying...
                            </div>
                        </button>

                        <button wire:click="toggleMaintenanceMode"
                            class="px-6 py-3 rounded-xl {{ $maintenanceMode ? 'bg-gradient-to-r from-emerald-500 to-green-600' : 'bg-gradient-to-r from-amber-500 to-orange-600' }} text-white font-semibold shadow-lg transition-transform transform hover:-translate-y-0.5">
                            @if($maintenanceMode)
                                <div class="flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Go Live
                                </div>
                            @else
                                <div class="flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    Maintenance
                                </div>
                            @endif
                        </button>

                        <a href="{{ route('projects.index') }}"
                            class="px-6 py-3 rounded-xl bg-white/10 border border-white/20 text-white font-semibold hover:bg-white/15 transition text-center">
                            <div class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
                                </svg>
                                Back to Projects
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session()->has('message'))
        <div class="mb-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-400 p-4 rounded-r-lg">
            <p class="text-green-700 dark:text-green-300">{{ session('message') }}</p>
        </div>
    @endif
    @if(session()->has('error'))
        <div class="mb-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 p-4 rounded-r-lg">
            <p class="text-red-700 dark:text-red-300">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Git Setup Modal -->
    @if($showGitSetup)
        <div class="mb-8 bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                </svg>
                Git Repository Setup
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Repository URL</label>
                    <input type="text" wire:model="newRepoUrl" placeholder="git@github.com:user/repo.git"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Branch</label>
                    <input type="text" wire:model="newBranch" placeholder="master"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
            </div>
            @if($gitSetupOutput)
                <div class="bg-gray-900 rounded-lg p-4 mb-4 max-h-40 overflow-y-auto">
                    <pre class="text-xs text-green-400 font-mono whitespace-pre-wrap">{{ $gitSetupOutput }}</pre>
                </div>
            @endif
            <div class="flex gap-3">
                <button wire:click="initializeGit" wire:loading.attr="disabled" @disabled($isSettingUpGit)
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition disabled:opacity-50">
                    <span wire:loading.remove wire:target="initializeGit">Initialize Git</span>
                    <span wire:loading wire:target="initializeGit">Setting up...</span>
                </button>
                @if($isGitRepo)
                    <button wire:click="removeGit" wire:confirm="Remove Git connection?"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition">
                        Remove Git
                    </button>
                @endif
                <button wire:click="toggleGitSetup" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition">
                    Cancel
                </button>
            </div>
        </div>
    @endif

    <!-- Deployment Progress (When Running) -->
    @if(count($deploymentSteps) > 0)
        <div class="mb-8 bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-white flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Deployment Progress
                </h3>
                @if($deploymentStatus === 'success')
                    <span class="px-3 py-1 bg-green-500/20 text-green-100 text-sm font-semibold rounded-full">Success</span>
                @elseif($deploymentStatus === 'failed')
                    <span class="px-3 py-1 bg-red-500/20 text-red-100 text-sm font-semibold rounded-full">Failed</span>
                @elseif($deploymentStatus === 'running')
                    <span class="px-3 py-1 bg-white/20 text-white text-sm font-semibold rounded-full flex items-center">
                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Running
                    </span>
                @endif
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
                    @foreach($deploymentSteps as $index => $step)
                        <div class="p-3 rounded-lg border
                            @if($step['status'] === 'success') bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800
                            @elseif($step['status'] === 'running') bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800
                            @elseif($step['status'] === 'failed') bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800
                            @else bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600
                            @endif">
                            <div class="flex items-center gap-2">
                                @if($step['status'] === 'success')
                                    <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                @elseif($step['status'] === 'running')
                                    <svg class="w-4 h-4 text-blue-500 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                @elseif($step['status'] === 'failed')
                                    <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                @else
                                    <span class="w-4 h-4 flex items-center justify-center text-[10px] font-bold text-gray-400 bg-gray-200 dark:bg-gray-600 rounded-full flex-shrink-0">{{ $index + 1 }}</span>
                                @endif
                                <span class="text-xs font-medium truncate
                                    @if($step['status'] === 'success') text-green-700 dark:text-green-400
                                    @elseif($step['status'] === 'running') text-blue-700 dark:text-blue-400
                                    @elseif($step['status'] === 'failed') text-red-700 dark:text-red-400
                                    @else text-gray-500 dark:text-gray-400
                                    @endif">
                                    {{ $step['name'] }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($deploymentOutput)
                    <div class="bg-gray-900 rounded-lg p-4 max-h-64 overflow-y-auto">
                        <pre class="text-xs text-green-400 font-mono whitespace-pre-wrap">{{ $deploymentOutput }}</pre>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-8">
        <button wire:click="clearCache('all')" class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow hover:shadow-lg transition text-center group">
            <div class="w-10 h-10 mx-auto mb-2 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center group-hover:scale-110 transition">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </div>
            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Clear Cache</span>
        </button>

        <button wire:click="rebuildCache" class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow hover:shadow-lg transition text-center group">
            <div class="w-10 h-10 mx-auto mb-2 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center group-hover:scale-110 transition">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                </svg>
            </div>
            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Rebuild Cache</span>
        </button>

        <button wire:click="restartQueue" class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow hover:shadow-lg transition text-center group">
            <div class="w-10 h-10 mx-auto mb-2 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center group-hover:scale-110 transition">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
            </div>
            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Restart Queue</span>
        </button>

        <button wire:click="runMigrations" class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow hover:shadow-lg transition text-center group">
            <div class="w-10 h-10 mx-auto mb-2 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center group-hover:scale-110 transition">
                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                </svg>
            </div>
            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Run Migrations</span>
        </button>

        <button wire:click="toggleDeployScript" class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow hover:shadow-lg transition text-center group">
            <div class="w-10 h-10 mx-auto mb-2 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center group-hover:scale-110 transition">
                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                </svg>
            </div>
            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Deploy Script</span>
        </button>

        <button wire:click="toggleEnvEditor" class="p-4 bg-white dark:bg-gray-800 rounded-xl shadow hover:shadow-lg transition text-center group">
            <div class="w-10 h-10 mx-auto mb-2 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center group-hover:scale-110 transition">
                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                </svg>
            </div>
            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Environment</span>
        </button>
    </div>

    <!-- Deploy Script Editor -->
    @if($showDeployScript)
        <div class="mb-8 bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">deploy.sh</h3>
                <div class="flex gap-2">
                    <button wire:click="resetDeployScript" class="px-3 py-1 text-sm bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg">Reset</button>
                    <button wire:click="saveDeployScript" class="px-3 py-1 text-sm bg-green-600 text-white rounded-lg">Save</button>
                </div>
            </div>
            <textarea wire:model="deployScript" rows="15" class="w-full px-4 py-3 font-mono text-sm bg-gray-900 text-green-400 rounded-lg border-0"></textarea>
            <p class="mt-2 text-xs text-gray-500">Run manually: <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">./deploy.sh</code></p>
        </div>
    @endif

    <!-- Environment Variables Editor -->
    @if($showEnvEditor)
        <div class="mb-8 bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Environment Variables</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($envVariables as $key => $value)
                    <div class="flex items-center gap-2">
                        <label class="w-40 text-sm font-mono text-gray-600 dark:text-gray-400 truncate">{{ $key }}</label>
                        <input type="text" wire:model.blur="envVariables.{{ $key }}" wire:change="updateEnvVariable('{{ $key }}', $event.target.value)"
                            class="flex-1 px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                @endforeach
            </div>
            <p class="mt-4 text-xs text-gray-500">Changes automatically clear config cache.</p>
        </div>
    @endif

    <!-- Info Cards Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- System Info -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                </svg>
                System
            </h3>
            <div class="space-y-3 text-sm">
                @foreach(['disk_free' => 'Disk Free', 'disk_total' => 'Disk Total', 'memory_limit' => 'Memory Limit', 'upload_max_filesize' => 'Max Upload'] as $key => $label)
                    @if(isset($systemInfo[$key]))
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">{{ $label }}</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $systemInfo[$key] }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <!-- Database -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                </svg>
                Database
            </h3>
            <div class="space-y-3 text-sm">
                @foreach($databaseInfo as $key => $value)
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400 capitalize">{{ str_replace('_', ' ', $key) }}</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
            @if(count($pendingMigrations) > 0)
                <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                    <p class="text-xs text-yellow-700 dark:text-yellow-400 font-medium">{{ count($pendingMigrations) }} pending migrations</p>
                </div>
            @endif
        </div>

        <!-- Domain -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                </svg>
                Domain
            </h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">APP_URL</span>
                    <span class="font-mono text-gray-900 dark:text-white text-xs">{{ $currentAppDomain }}</span>
                </div>
                @if(count($nginxSites) > 0)
                    <div class="pt-3 border-t border-gray-100 dark:border-gray-700">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Nginx Sites</p>
                        @foreach(array_slice($nginxSites, 0, 3) as $site)
                            <div class="flex items-center gap-2 text-xs">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                <span class="font-mono text-gray-700 dark:text-gray-300">{{ $site }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Log Files -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-red-600 to-rose-600 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-white flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Log Files
            </h3>
            <button wire:click="refreshLogs" class="px-3 py-1 text-sm bg-white/20 hover:bg-white/30 text-white rounded-lg transition">Refresh</button>
        </div>

        @if(count($logFiles) > 0)
            <div class="p-6">
                <div class="overflow-x-auto mb-4">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 dark:text-gray-400">
                                <th class="pb-3 font-medium">File</th>
                                <th class="pb-3 font-medium">Size</th>
                                <th class="pb-3 font-medium">Modified</th>
                                <th class="pb-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($logFiles as $log)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ $selectedLogFile === $log['name'] ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}">
                                    <td class="py-3">
                                        <button wire:click="selectLogFile('{{ $log['name'] }}')" class="font-mono text-gray-900 dark:text-white hover:text-blue-600">
                                            {{ $log['name'] }}
                                        </button>
                                    </td>
                                    <td class="py-3 text-gray-500 dark:text-gray-400">{{ $log['size'] }}</td>
                                    <td class="py-3 text-gray-500 dark:text-gray-400">{{ $log['modified'] }}</td>
                                    <td class="py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('projects.devflow.logs.download', $log['name']) }}" class="p-1.5 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg" title="Download">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                                </svg>
                                            </a>
                                            <button wire:click="clearLogFile('{{ $log['name'] }}')" wire:confirm="Clear {{ $log['name'] }}?" class="p-1.5 text-amber-600 hover:bg-amber-100 dark:hover:bg-amber-900/30 rounded-lg" title="Clear">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                            @if($log['name'] !== 'laravel.log')
                                                <button wire:click="deleteLogFile('{{ $log['name'] }}')" wire:confirm="Delete {{ $log['name'] }}?" class="p-1.5 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg" title="Delete">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($selectedLogFile && $recentLogs)
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                            Viewing: <span class="font-mono text-blue-600 dark:text-blue-400">{{ $selectedLogFile }}</span> (last 100 lines)
                        </p>
                        <div class="bg-gray-900 rounded-lg p-4 max-h-64 overflow-y-auto">
                            <pre class="text-xs text-green-400 font-mono whitespace-pre-wrap">{{ $recentLogs }}</pre>
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="p-8 text-center">
                <p class="text-gray-500 dark:text-gray-400">No log files found</p>
            </div>
        @endif
    </div>
</div>
