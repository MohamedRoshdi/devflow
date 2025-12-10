<div class="space-y-6">
    {{-- Floating Background Elements --}}
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-emerald-500/10 to-teal-500/10 rounded-full blur-3xl transform translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-gradient-to-tr from-blue-500/10 to-indigo-500/10 rounded-full blur-3xl transform -translate-x-1/2 translate-y-1/2"></div>
    </div>

    {{-- Header Section --}}
    <div class="relative">
        <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 rounded-3xl overflow-hidden border border-slate-700/50 shadow-2xl">
            {{-- Animated Grid Background --}}
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: linear-gradient(rgba(255,255,255,0.1) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.1) 1px, transparent 1px); background-size: 50px 50px;"></div>
            </div>

            <div class="relative p-8">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    {{-- Left: Title & Status --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/25">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-white">DevFlow Pro</h1>
                                <p class="text-slate-400 text-sm">Self-Management Console</p>
                            </div>
                        </div>

                        {{-- Status Pills --}}
                        <div class="flex flex-wrap items-center gap-2">
                            @if($maintenanceMode)
                                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                                    Maintenance Mode
                                </span>
                            @else
                                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                    Live
                                </span>
                            @endif
                            <span class="px-3 py-1.5 rounded-full text-xs font-medium bg-slate-700/50 text-slate-300 border border-slate-600/50">
                                Laravel {{ app()->version() }}
                            </span>
                            <span class="px-3 py-1.5 rounded-full text-xs font-medium bg-slate-700/50 text-slate-300 border border-slate-600/50">
                                PHP {{ PHP_VERSION }}
                            </span>
                            @if($debugMode)
                                <span class="px-3 py-1.5 rounded-full text-xs font-medium bg-red-500/20 text-red-400 border border-red-500/30">
                                    Debug ON
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Right: Quick Actions --}}
                    <div class="flex flex-wrap gap-3">
                        <button wire:click="redeploy" wire:loading.attr="disabled" @disabled($isDeploying)
                            class="group relative px-6 py-3 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-semibold shadow-lg shadow-emerald-500/25 hover:shadow-xl hover:shadow-emerald-500/40 transition-all duration-300 hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed">
                            <div wire:loading.remove wire:target="redeploy" class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Deploy
                            </div>
                            <div wire:loading wire:target="redeploy" class="flex items-center gap-2">
                                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Deploying...
                            </div>
                        </button>

                        <button wire:click="toggleMaintenanceMode"
                            class="px-6 py-3 rounded-xl bg-slate-700/50 text-white font-medium border border-slate-600/50 hover:bg-slate-700 transition-all duration-300">
                            {{ $maintenanceMode ? 'Go Live' : 'Maintenance' }}
                        </button>

                        <button wire:click="clearCache('all')"
                            class="px-6 py-3 rounded-xl bg-slate-700/50 text-white font-medium border border-slate-600/50 hover:bg-slate-700 transition-all duration-300">
                            Clear Cache
                        </button>
                    </div>
                </div>

                {{-- Git Info Bar --}}
                @if($isGitRepo)
                    <div class="mt-6 flex items-center gap-4 p-4 rounded-2xl bg-slate-800/50 border border-slate-700/50">
                        <div class="w-10 h-10 rounded-xl bg-slate-700/50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-slate-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 text-sm">
                                <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-mono text-xs">{{ $gitBranch }}</span>
                                <span class="text-slate-400 truncate" title="{{ $gitLastCommit }}">{{ Str::limit($gitLastCommit, 60) }}</span>
                            </div>
                        </div>
                        <button wire:click="toggleGitSetup" class="px-3 py-1.5 rounded-lg text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-700/50 transition-colors">
                            Configure
                        </button>
                    </div>
                @else
                    <button wire:click="toggleGitSetup" class="mt-6 w-full flex items-center justify-center gap-3 p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-400 hover:bg-amber-500/20 transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                        </svg>
                        <span class="font-medium">No Git Repository - Click to Setup</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Git Setup Modal --}}
    @if($showGitSetup)
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xl overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Git Repository Setup</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Repository URL</label>
                    <input type="text" wire:model="newRepoUrl" placeholder="https://github.com/user/repo.git or git@github.com:user/repo.git"
                        class="w-full px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Branch</label>
                    <input type="text" wire:model="newBranch" placeholder="master"
                        class="w-full px-4 py-3 rounded-xl bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
                </div>
                <div class="flex gap-3">
                    <button wire:click="initializeGit" wire:loading.attr="disabled"
                        class="flex-1 px-4 py-3 rounded-xl bg-emerald-500 text-white font-medium hover:bg-emerald-600 transition-colors disabled:opacity-50">
                        <span wire:loading.remove wire:target="initializeGit">Initialize Git</span>
                        <span wire:loading wire:target="initializeGit">Setting up...</span>
                    </button>
                    @if($isGitRepo)
                        <button wire:click="removeGit" wire:confirm="Remove .git directory?"
                            class="px-4 py-3 rounded-xl bg-red-500 text-white font-medium hover:bg-red-600 transition-colors">
                            Remove Git
                        </button>
                    @endif
                    <button wire:click="toggleGitSetup"
                        class="px-4 py-3 rounded-xl bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-300 dark:hover:bg-slate-600 transition-colors">
                        Cancel
                    </button>
                </div>
                @if($gitSetupOutput)
                    <div class="p-4 rounded-xl bg-slate-900 text-slate-300 font-mono text-sm overflow-x-auto">
                        <pre>{{ $gitSetupOutput }}</pre>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Deployment Progress --}}
    @if($isDeploying || $deploymentStatus)
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xl overflow-hidden"
             @if($isDeploying && $deploymentStatus === 'running') wire:poll.500ms="pollDeploymentStep" @endif>
            <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    @if($isDeploying && $deploymentStatus === 'running')
                        <svg class="w-5 h-5 text-emerald-500 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    @elseif($deploymentStatus === 'success')
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    @elseif($deploymentStatus === 'failed')
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    @endif
                    Deployment Progress
                    @if($deploymentStatus === 'success')
                        <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400">Complete</span>
                    @elseif($deploymentStatus === 'failed')
                        <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-500/20 text-red-600 dark:text-red-400">Failed</span>
                    @elseif($isDeploying)
                        <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400">Running Step {{ $currentStep + 1 }}/10</span>
                    @endif
                </h3>
                <button wire:click="toggleDeployScript" class="text-sm text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                    {{ $showDeployScript ? 'Hide Script' : 'Show Script' }}
                </button>
            </div>
            <div class="p-6">
                {{-- Deployment Steps Grid --}}
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
                    @foreach($deploymentSteps as $index => $step)
                        <div class="p-3 rounded-xl border transition-all duration-300
                            {{ $step['status'] === 'success' ? 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/30' : '' }}
                            {{ $step['status'] === 'running' ? 'bg-blue-50 dark:bg-blue-500/10 border-blue-200 dark:border-blue-500/30 animate-pulse' : '' }}
                            {{ $step['status'] === 'failed' ? 'bg-red-50 dark:bg-red-500/10 border-red-200 dark:border-red-500/30' : '' }}
                            {{ $step['status'] === 'pending' ? 'bg-slate-50 dark:bg-slate-700/50 border-slate-200 dark:border-slate-600' : '' }}">
                            <div class="flex items-center gap-2 mb-1">
                                @if($step['status'] === 'success')
                                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @elseif($step['status'] === 'running')
                                    <svg class="w-4 h-4 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                @elseif($step['status'] === 'failed')
                                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                @else
                                    <span class="w-4 h-4 rounded-full border-2 border-slate-300 dark:border-slate-500"></span>
                                @endif
                                <span class="text-xs font-medium text-slate-600 dark:text-slate-400">Step {{ $index + 1 }}</span>
                            </div>
                            <p class="text-xs text-slate-700 dark:text-slate-300 truncate" title="{{ $step['name'] }}">{{ $step['name'] }}</p>
                        </div>
                    @endforeach
                </div>

                {{-- Deployment Output --}}
                @if($deploymentOutput)
                    <div class="p-4 rounded-xl bg-slate-900 max-h-64 overflow-y-auto" id="deployment-output">
                        <pre class="text-xs text-emerald-400 font-mono whitespace-pre-wrap">{{ $deploymentOutput }}</pre>
                    </div>
                    <script>
                        // Auto-scroll to bottom of deployment output
                        const output = document.getElementById('deployment-output');
                        if (output) output.scrollTop = output.scrollHeight;
                    </script>
                @endif
            </div>
        </div>
    @endif

    {{-- Stats Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        {{-- System Info --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-lg shadow-violet-500/25">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-slate-900 dark:text-white">System</h3>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">Memory</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ $systemInfo['memory_limit'] ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Max Exec</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ $systemInfo['max_execution_time'] ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Upload</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ $systemInfo['upload_max_filesize'] ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        {{-- Database --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center shadow-lg shadow-cyan-500/25">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-slate-900 dark:text-white">Database</h3>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">Driver</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ $databaseInfo['connection'] ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Tables</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ $databaseInfo['tables_count'] ?? 0 }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Pending</span>
                    <span class="font-medium {{ count($pendingMigrations) > 0 ? 'text-amber-500' : 'text-emerald-500' }}">{{ count($pendingMigrations) }} migrations</span>
                </div>
            </div>
        </div>

        {{-- Redis --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center shadow-lg shadow-red-500/25">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-slate-900 dark:text-white">Redis</h3>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">Status</span>
                    <span class="font-medium {{ $redisConnected ? 'text-emerald-500' : 'text-red-500' }}">{{ $redisConnected ? 'Connected' : 'Offline' }}</span>
                </div>
                @if($redisConnected)
                    <div class="flex justify-between">
                        <span class="text-slate-500">Memory</span>
                        <span class="font-medium text-slate-900 dark:text-white">{{ $redisInfo['used_memory'] ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Keys</span>
                        <span class="font-medium text-slate-900 dark:text-white">{{ number_format($redisInfo['total_keys'] ?? 0) }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Reverb WebSocket --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 hover:shadow-lg transition-shadow">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-purple-500/25">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-slate-900 dark:text-white">Reverb</h3>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">Status</span>
                    <span class="font-medium {{ $reverbRunning ? 'text-emerald-500' : 'text-slate-400' }}">{{ $reverbRunning ? 'Running' : 'Stopped' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Port</span>
                    <span class="font-medium text-slate-900 dark:text-white">{{ $reverbStatus['port'] ?? 'N/A' }}</span>
                </div>
                <div class="pt-2">
                    @if($reverbRunning)
                        <button wire:click="stopReverb" class="w-full px-3 py-1.5 rounded-lg text-xs font-medium bg-red-100 dark:bg-red-500/20 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-500/30 transition-colors">
                            Stop
                        </button>
                    @else
                        <button wire:click="startReverb" class="w-full px-3 py-1.5 rounded-lg text-xs font-medium bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-200 dark:hover:bg-emerald-500/30 transition-colors">
                            Start
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Storage Overview --}}
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xl overflow-hidden">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                    </svg>
                    Storage Overview
                </h3>
                <span class="text-sm text-slate-500">{{ $storageInfo['disk_percent'] ?? 0 }}% used</span>
            </div>
        </div>
        <div class="p-6">
            {{-- Disk Usage Bar --}}
            <div class="mb-6">
                <div class="h-3 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500 {{ ($storageInfo['disk_percent'] ?? 0) > 90 ? 'bg-gradient-to-r from-red-500 to-rose-600' : (($storageInfo['disk_percent'] ?? 0) > 70 ? 'bg-gradient-to-r from-amber-500 to-orange-600' : 'bg-gradient-to-r from-emerald-500 to-teal-600') }}"
                         style="width: {{ min($storageInfo['disk_percent'] ?? 0, 100) }}%"></div>
                </div>
                <div class="flex justify-between mt-2 text-xs text-slate-500">
                    <span>Used: {{ $this->formatBytes($storageInfo['disk_used'] ?? 0) }}</span>
                    <span>Free: {{ $this->formatBytes($storageInfo['disk_free'] ?? 0) }}</span>
                    <span>Total: {{ $this->formatBytes($storageInfo['disk_total'] ?? 0) }}</span>
                </div>
            </div>

            {{-- Storage Breakdown --}}
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                @php
                    $storageItems = [
                        ['name' => 'Logs', 'key' => 'storage_logs', 'type' => 'logs', 'color' => 'amber'],
                        ['name' => 'Cache', 'key' => 'storage_cache', 'type' => 'cache', 'color' => 'blue'],
                        ['name' => 'Sessions', 'key' => 'storage_sessions', 'type' => 'sessions', 'color' => 'purple'],
                        ['name' => 'Views', 'key' => 'storage_views', 'type' => 'views', 'color' => 'pink'],
                        ['name' => 'Vendor', 'key' => 'vendor', 'type' => null, 'color' => 'slate'],
                        ['name' => 'Node', 'key' => 'node_modules', 'type' => null, 'color' => 'green'],
                        ['name' => 'Build', 'key' => 'public_build', 'type' => null, 'color' => 'cyan'],
                    ];
                @endphp
                @foreach($storageItems as $item)
                    <div class="relative group p-4 rounded-xl bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 hover:shadow-md transition-all">
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">{{ $item['name'] }}</p>
                        <p class="text-lg font-bold text-slate-900 dark:text-white">{{ $this->formatBytes($storageInfo[$item['key']] ?? 0) }}</p>
                        @if($item['type'])
                            <button wire:click="cleanStorage('{{ $item['type'] }}')" wire:confirm="Clear {{ strtolower($item['name']) }}?"
                                class="absolute top-2 right-2 p-1.5 rounded-lg opacity-0 group-hover:opacity-100 bg-red-100 dark:bg-red-500/20 text-red-500 hover:bg-red-200 dark:hover:bg-red-500/30 transition-all">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Two Column Layout: Processes & Cache --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Supervisor Processes --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xl overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                        </svg>
                        Supervisor
                    </h3>
                    <div class="flex gap-2">
                        <button wire:click="supervisorAction('reread')" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                            Reread
                        </button>
                        <button wire:click="supervisorAction('update')" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                            Update
                        </button>
                    </div>
                </div>
            </div>
            <div class="p-6">
                @if(count($supervisorProcesses) > 0)
                    <div class="space-y-3">
                        @foreach($supervisorProcesses as $process)
                            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600">
                                <div class="flex items-center gap-3">
                                    <span class="w-2.5 h-2.5 rounded-full {{ $process['status'] === 'RUNNING' ? 'bg-emerald-500 animate-pulse' : ($process['status'] === 'STOPPED' ? 'bg-slate-400' : 'bg-red-500') }}"></span>
                                    <div>
                                        <p class="font-medium text-sm text-slate-900 dark:text-white">{{ $process['name'] }}</p>
                                        <p class="text-xs text-slate-500">{{ $process['info'] }}</p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    @if($process['status'] === 'RUNNING')
                                        <button wire:click="supervisorAction('stop', '{{ $process['name'] }}')" class="px-2.5 py-1 rounded-lg text-xs font-medium bg-red-100 dark:bg-red-500/20 text-red-600 dark:text-red-400 hover:bg-red-200 transition-colors">
                                            Stop
                                        </button>
                                    @else
                                        <button wire:click="supervisorAction('start', '{{ $process['name'] }}')" class="px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-200 transition-colors">
                                            Start
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-slate-500">
                        <svg class="w-12 h-12 mx-auto mb-3 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                        </svg>
                        <p>No processes found</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Cache Management --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xl overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Cache Management
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 gap-3">
                    @php
                        $cacheTypes = [
                            ['type' => 'config', 'label' => 'Config', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
                            ['type' => 'route', 'label' => 'Routes', 'icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7'],
                            ['type' => 'view', 'label' => 'Views', 'icon' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'],
                            ['type' => 'event', 'label' => 'Events', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                            ['type' => 'all', 'label' => 'All Caches', 'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
                            ['type' => 'optimize', 'label' => 'Rebuild All', 'icon' => 'M5 13l4 4L19 7'],
                        ];
                    @endphp
                    @foreach($cacheTypes as $cache)
                        <button wire:click="{{ $cache['type'] === 'optimize' ? 'rebuildCache' : "clearCache('{$cache['type']}')" }}"
                            class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 dark:border-slate-600 {{ $cache['type'] === 'optimize' ? 'bg-emerald-50 dark:bg-emerald-500/10 hover:bg-emerald-100 dark:hover:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400' : 'bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300' }} transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $cache['icon'] }}"/>
                            </svg>
                            <span class="font-medium text-sm">{{ $cache['label'] }}</span>
                        </button>
                    @endforeach
                </div>

                @if($redisConnected)
                    <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                        <button wire:click="flushRedis" wire:confirm="Flush all Redis cache?"
                            class="w-full flex items-center justify-center gap-2 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-500/20 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <span class="font-medium">Flush Redis</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Scheduler & Logs --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Task Scheduler --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xl overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Task Scheduler
                    </h3>
                    <span class="px-3 py-1 rounded-full text-xs font-medium {{ ($schedulerStatus['cron_configured'] ?? false) ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400' : 'bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400' }}">
                        {{ ($schedulerStatus['cron_configured'] ?? false) ? 'Cron Active' : 'Cron Missing' }}
                    </span>
                </div>
            </div>
            <div class="p-6">
                <p class="text-sm text-slate-500 mb-4">Last Run: <span class="font-medium text-slate-900 dark:text-white">{{ $lastSchedulerRun }}</span></p>

                @if(!empty($schedulerStatus['tasks']))
                    <div class="mb-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-700/50 max-h-40 overflow-y-auto">
                        @foreach($schedulerStatus['tasks'] as $task)
                            @if(!empty(trim($task)))
                                <p class="text-xs font-mono text-slate-600 dark:text-slate-400 py-1 border-b border-slate-200 dark:border-slate-600 last:border-0">{{ $task }}</p>
                            @endif
                        @endforeach
                    </div>
                @endif

                <button wire:click="runScheduler" wire:loading.attr="disabled"
                    class="w-full px-4 py-3 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 text-white font-medium shadow-lg shadow-amber-500/25 hover:shadow-xl hover:shadow-amber-500/40 transition-all">
                    <span wire:loading.remove wire:target="runScheduler">Run Scheduler Now</span>
                    <span wire:loading wire:target="runScheduler">Running...</span>
                </button>
            </div>
        </div>

        {{-- Log Files --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xl overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Log Files
                    </h3>
                    <button wire:click="refreshLogs" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-500 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="p-6">
                @if(count($logFiles) > 0)
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        @foreach($logFiles as $log)
                            <div class="flex items-center justify-between p-3 rounded-xl {{ $selectedLogFile === $log['name'] ? 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/30' : 'bg-slate-50 dark:bg-slate-700/50' }} border border-slate-200 dark:border-slate-600 cursor-pointer hover:shadow-md transition-all"
                                 wire:click="selectLogFile('{{ $log['name'] }}')">
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-sm text-slate-900 dark:text-white truncate">{{ $log['name'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $this->formatBytes($log['size']) }} • {{ $log['modified'] }}</p>
                                </div>
                                <div class="flex gap-1 ml-2">
                                    <a href="{{ route('projects.devflow.logs.download', $log['name']) }}" class="p-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-500 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                    </a>
                                    @if($log['name'] !== 'laravel.log')
                                        <button wire:click.stop="deleteLogFile('{{ $log['name'] }}')" wire:confirm="Delete this log file?" class="p-1.5 rounded-lg hover:bg-red-100 dark:hover:bg-red-500/20 text-red-500 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($selectedLogFile && $recentLogs)
                        <div class="mt-4 p-4 rounded-xl bg-slate-900 max-h-48 overflow-y-auto">
                            <pre class="text-xs text-emerald-400 font-mono whitespace-pre-wrap">{{ $recentLogs }}</pre>
                        </div>
                    @endif
                @else
                    <div class="text-center py-8 text-slate-500">
                        <svg class="w-12 h-12 mx-auto mb-3 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p>No log files found</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Environment & Domain Config --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Environment Variables --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xl overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Environment
                    </h3>
                    <button wire:click="toggleEnvEditor" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                        {{ $showEnvEditor ? 'Close' : 'Edit' }}
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="space-y-3 max-h-72 overflow-y-auto">
                    @foreach($envVariables as $key => $value)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600">
                            <span class="font-mono text-xs text-slate-600 dark:text-slate-400">{{ $key }}</span>
                            @if($showEnvEditor && in_array($key, $editableEnvKeys))
                                <input type="text" wire:model.lazy="envVariables.{{ $key }}" wire:change="updateEnvVariable('{{ $key }}', $event.target.value)"
                                    class="w-48 px-3 py-1.5 rounded-lg text-xs font-mono bg-white dark:bg-slate-600 border border-slate-200 dark:border-slate-500 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 transition-all">
                            @else
                                <span class="font-mono text-xs text-slate-900 dark:text-white truncate max-w-[200px]" title="{{ $value }}">{{ Str::limit($value, 30) }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Domain Configuration --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xl overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                        </svg>
                        Domain
                    </h3>
                    <button wire:click="toggleDomainEditor" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                        {{ $showDomainEditor ? 'Close' : 'Edit' }}
                    </button>
                </div>
            </div>
            <div class="p-6 space-y-4">
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600">
                    <p class="text-xs text-slate-500 mb-1">Current APP_URL</p>
                    @if($showDomainEditor)
                        <div class="flex gap-2">
                            <input type="text" wire:model="currentAppUrl"
                                class="flex-1 px-3 py-2 rounded-lg text-sm font-mono bg-white dark:bg-slate-600 border border-slate-200 dark:border-slate-500 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 transition-all">
                            <button wire:click="updateAppUrl($wire.currentAppUrl)" class="px-4 py-2 rounded-lg bg-emerald-500 text-white font-medium hover:bg-emerald-600 transition-colors">
                                Save
                            </button>
                        </div>
                    @else
                        <p class="font-mono text-sm text-slate-900 dark:text-white">{{ $currentAppUrl }}</p>
                    @endif
                </div>

                @if(count($nginxSites) > 0)
                    <div>
                        <p class="text-xs font-medium text-slate-500 mb-2">Nginx Sites</p>
                        <div class="space-y-2">
                            @foreach($nginxSites as $site)
                                <div class="flex items-center gap-2 p-2 rounded-lg bg-slate-50 dark:bg-slate-700/50">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span class="font-mono text-xs text-slate-700 dark:text-slate-300">{{ $site }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Queue Workers --}}
    @if(count($queueStatus) > 0)
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xl overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        Queue Workers
                    </h3>
                    <button wire:click="restartQueue" class="px-4 py-2 rounded-lg text-sm font-medium bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 hover:bg-amber-200 dark:hover:bg-amber-500/30 transition-colors">
                        Restart Queue
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($queueStatus as $worker)
                        <div class="flex items-center gap-3 p-4 rounded-xl bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600">
                            <span class="w-3 h-3 rounded-full {{ $worker['status'] === 'RUNNING' ? 'bg-emerald-500 animate-pulse' : ($worker['status'] === 'STOPPED' ? 'bg-slate-400' : 'bg-red-500') }}"></span>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-sm text-slate-900 dark:text-white truncate">{{ $worker['name'] }}</p>
                                <p class="text-xs text-slate-500">{{ $worker['status'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
