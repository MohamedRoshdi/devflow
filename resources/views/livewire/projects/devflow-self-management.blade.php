<div class="min-h-screen" x-data="{ activeTab: 'overview', showLogs: false }">
    {{-- Animated Background --}}
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-gradient-to-br from-emerald-500/5 via-teal-500/5 to-cyan-500/5 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-gradient-to-tr from-violet-500/5 via-purple-500/5 to-fuchsia-500/5 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-gradient-to-r from-blue-500/3 to-indigo-500/3 rounded-full blur-3xl"></div>
    </div>

    {{-- Hero Header --}}
    <div class="relative mb-8">
        <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 rounded-3xl overflow-hidden border border-slate-700/50 shadow-2xl">
            {{-- Grid Pattern --}}
            <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23fff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>

            <div class="relative p-6 lg:p-8">
                {{-- Top Row: Logo, Title, Actions --}}
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                    <div class="flex items-start gap-4">
                        {{-- Animated Logo --}}
                        <div class="relative">
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-400 via-teal-500 to-cyan-600 flex items-center justify-center shadow-xl shadow-emerald-500/30 transform hover:scale-105 transition-transform">
                                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                </svg>
                            </div>
                            <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full {{ $maintenanceMode ? 'bg-amber-500' : 'bg-emerald-500' }} border-2 border-slate-900 flex items-center justify-center">
                                @if(!$maintenanceMode)
                                    <span class="w-2 h-2 rounded-full bg-white animate-ping"></span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <h1 class="text-2xl lg:text-3xl font-bold text-white tracking-tight">DevFlow Pro</h1>
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider {{ $maintenanceMode ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' : 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' }}">
                                    {{ $maintenanceMode ? 'Maintenance' : 'Live' }}
                                </span>
                            </div>
                            <p class="text-slate-400 text-sm">Self-Management Console</p>

                            {{-- Version Pills --}}
                            <div class="flex flex-wrap items-center gap-2 mt-3">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-800/80 text-slate-300 border border-slate-700/50">
                                    <svg class="w-3.5 h-3.5 text-red-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm0 18c-4.418 0-8-3.582-8-8s3.582-8 8-8 8 3.582 8 8-3.582 8-8 8z"/><path d="M12 6c-3.309 0-6 2.691-6 6s2.691 6 6 6 6-2.691 6-6-2.691-6-6-6z"/></svg>
                                    Laravel {{ app()->version() }}
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-800/80 text-slate-300 border border-slate-700/50">
                                    <svg class="w-3.5 h-3.5 text-indigo-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2z"/></svg>
                                    PHP {{ PHP_VERSION }}
                                </span>
                                @if($debugMode)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-red-500/20 text-red-400 border border-red-500/30">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        Debug Mode
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Quick Actions --}}
                    <div class="flex flex-wrap gap-2">
                        <button wire:click="redeploy" wire:loading.attr="disabled" @disabled($isDeploying)
                            class="group relative inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold text-sm text-white overflow-hidden transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none hover:-translate-y-0.5"
                            style="background: linear-gradient(135deg, #10b981 0%, #14b8a6 50%, #06b6d4 100%);">
                            <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/25 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700"></div>
                            <div wire:loading.remove wire:target="redeploy" class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Deploy Now
                            </div>
                            <div wire:loading wire:target="redeploy" class="flex items-center gap-2">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Deploying...
                            </div>
                        </button>

                        <button wire:click="toggleMaintenanceMode"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm bg-slate-800/80 text-slate-300 border border-slate-700/50 hover:bg-slate-700/80 hover:text-white transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ $maintenanceMode ? 'Go Live' : 'Maintenance' }}
                        </button>

                        <button wire:click="clearCache('all')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm bg-slate-800/80 text-slate-300 border border-slate-700/50 hover:bg-slate-700/80 hover:text-white transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Clear Cache
                        </button>
                    </div>
                </div>

                {{-- Git Status Bar --}}
                @if($isGitRepo)
                    <div class="mt-6 flex items-center gap-4 p-4 rounded-2xl bg-slate-800/50 border border-slate-700/30 backdrop-blur-sm">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-700 to-slate-800 flex items-center justify-center border border-slate-600/50">
                            <svg class="w-5 h-5 text-slate-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3">
                                <span class="px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-400 font-mono text-xs border border-emerald-500/30">{{ $gitBranch }}</span>
                                <span class="text-slate-400 text-sm truncate font-mono" title="{{ $gitLastCommit }}">{{ Str::limit($gitLastCommit, 50) }}</span>
                            </div>
                        </div>
                        <button wire:click="toggleGitSetup" class="px-3 py-1.5 rounded-lg text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-700/50 transition-all border border-transparent hover:border-slate-600/50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </button>
                    </div>
                @else
                    <button wire:click="toggleGitSetup" class="mt-6 w-full flex items-center justify-center gap-3 p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-400 hover:bg-amber-500/20 transition-all group">
                        <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24">
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
        <div class="fixed inset-0 z-50 overflow-y-auto" x-transition>
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="toggleGitSetup"></div>
                <div class="relative bg-slate-900 rounded-2xl border border-slate-700/50 shadow-2xl w-full max-w-lg overflow-hidden">
                    <div class="p-6 border-b border-slate-700/50">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-slate-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                                Git Repository Setup
                            </h3>
                            <button wire:click="toggleGitSetup" class="text-slate-400 hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Repository URL</label>
                            <input type="text" wire:model="newRepoUrl" placeholder="https://github.com/user/repo.git"
                                class="w-full px-4 py-3 rounded-xl bg-slate-800 border border-slate-700 text-white placeholder-slate-500 focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Branch</label>
                            <input type="text" wire:model="newBranch" placeholder="master"
                                class="w-full px-4 py-3 rounded-xl bg-slate-800 border border-slate-700 text-white placeholder-slate-500 focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
                        </div>
                        <div class="flex gap-3 pt-2">
                            <button wire:click="initializeGit" wire:loading.attr="disabled"
                                class="flex-1 px-4 py-3 rounded-xl bg-emerald-500 text-white font-medium hover:bg-emerald-600 transition-colors disabled:opacity-50">
                                <span wire:loading.remove wire:target="initializeGit">Initialize Git</span>
                                <span wire:loading wire:target="initializeGit">Setting up...</span>
                            </button>
                            @if($isGitRepo)
                                <button wire:click="removeGit" wire:confirm="Remove .git directory?"
                                    class="px-4 py-3 rounded-xl bg-red-500/20 text-red-400 font-medium hover:bg-red-500/30 transition-colors border border-red-500/30">
                                    Remove
                                </button>
                            @endif
                        </div>
                        @if($gitSetupOutput)
                            <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 max-h-48 overflow-y-auto">
                                <pre class="text-xs text-emerald-400 font-mono whitespace-pre-wrap">{{ $gitSetupOutput }}</pre>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Deployment Progress (Full Screen Overlay when deploying) --}}
    @if($isDeploying || $deploymentStatus)
        <div class="mb-8 bg-slate-900/95 backdrop-blur-xl rounded-2xl border border-slate-700/50 shadow-2xl overflow-hidden"
             @if($isDeploying && $deploymentStatus === 'running') wire:poll.500ms="pollDeploymentStep" @endif>
            {{-- Header --}}
            <div class="p-5 border-b border-slate-700/50 flex items-center justify-between bg-gradient-to-r {{ $deploymentStatus === 'success' ? 'from-emerald-500/10 to-transparent' : ($deploymentStatus === 'failed' ? 'from-red-500/10 to-transparent' : 'from-blue-500/10 to-transparent') }}">
                <div class="flex items-center gap-3">
                    @if($isDeploying && $deploymentStatus === 'running')
                        <div class="w-10 h-10 rounded-xl bg-blue-500/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-400 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </div>
                    @elseif($deploymentStatus === 'success')
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    @elseif($deploymentStatus === 'failed')
                        <div class="w-10 h-10 rounded-xl bg-red-500/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                    @endif
                    <div>
                        <h3 class="text-lg font-semibold text-white">Deployment Progress</h3>
                        <p class="text-sm text-slate-400">
                            @if($deploymentStatus === 'success')
                                Completed successfully
                            @elseif($deploymentStatus === 'failed')
                                Failed - check logs below
                            @elseif($isDeploying)
                                Running step {{ $currentStep + 1 }} of {{ count($deploymentSteps) }}
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if(!$isDeploying || $deploymentStatus === 'success' || $deploymentStatus === 'failed')
                        <button wire:click="closeDeployment" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif
                </div>
            </div>

            <div class="p-5">
                {{-- Progress Bar --}}
                <div class="mb-6">
                    <div class="h-2 bg-slate-800 rounded-full overflow-hidden">
                        @php
                            $completedSteps = collect($deploymentSteps)->filter(fn($s) => $s['status'] === 'success')->count();
                            $progress = count($deploymentSteps) > 0 ? ($completedSteps / count($deploymentSteps)) * 100 : 0;
                        @endphp
                        <div class="h-full rounded-full transition-all duration-500 {{ $deploymentStatus === 'failed' ? 'bg-red-500' : 'bg-gradient-to-r from-emerald-500 to-cyan-500' }}" style="width: {{ $progress }}%"></div>
                    </div>
                </div>

                {{-- Steps Timeline --}}
                <div class="space-y-2 mb-6">
                    @foreach($deploymentSteps as $index => $step)
                        <div class="flex items-center gap-3 p-3 rounded-xl transition-all {{ $step['status'] === 'running' ? 'bg-blue-500/10 border border-blue-500/30' : ($step['status'] === 'success' ? 'bg-emerald-500/5' : ($step['status'] === 'failed' ? 'bg-red-500/10 border border-red-500/30' : '')) }}">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 {{ $step['status'] === 'success' ? 'bg-emerald-500/20' : ($step['status'] === 'running' ? 'bg-blue-500/20' : ($step['status'] === 'failed' ? 'bg-red-500/20' : 'bg-slate-800')) }}">
                                @if($step['status'] === 'success')
                                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                @elseif($step['status'] === 'running')
                                    <svg class="w-4 h-4 text-blue-400 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                @elseif($step['status'] === 'failed')
                                    <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                @else
                                    <span class="text-xs font-medium text-slate-500">{{ $index + 1 }}</span>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="text-sm font-medium {{ $step['status'] === 'success' ? 'text-emerald-400' : ($step['status'] === 'running' ? 'text-blue-400' : ($step['status'] === 'failed' ? 'text-red-400' : 'text-slate-400')) }}">{{ $step['name'] }}</span>
                            </div>
                            @if($step['status'] === 'success')
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Output Log --}}
                @if($deploymentOutput)
                    <div class="rounded-xl bg-slate-950 border border-slate-800 overflow-hidden">
                        <div class="px-4 py-2 border-b border-slate-800 flex items-center justify-between">
                            <span class="text-xs font-medium text-slate-400">Output Log</span>
                            <span class="text-xs text-slate-500 font-mono">{{ now()->format('H:i:s') }}</span>
                        </div>
                        <div class="p-4 max-h-64 overflow-y-auto" id="deployment-output">
                            <pre class="text-xs text-emerald-400 font-mono whitespace-pre-wrap leading-relaxed">{{ $deploymentOutput }}</pre>
                        </div>
                    </div>
                    <script>const output = document.getElementById('deployment-output'); if (output) output.scrollTop = output.scrollHeight;</script>
                @endif
            </div>
        </div>
    @endif

    {{-- Tab Navigation --}}
    <div class="mb-6">
        <div class="flex items-center gap-1 p-1 bg-slate-800/50 rounded-xl border border-slate-700/50 w-fit">
            <button @click="activeTab = 'overview'" :class="activeTab === 'overview' ? 'bg-slate-700 text-white shadow-lg' : 'text-slate-400 hover:text-white'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-all">
                Overview
            </button>
            <button @click="activeTab = 'services'" :class="activeTab === 'services' ? 'bg-slate-700 text-white shadow-lg' : 'text-slate-400 hover:text-white'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-all">
                Services
            </button>
            <button @click="activeTab = 'logs'" :class="activeTab === 'logs' ? 'bg-slate-700 text-white shadow-lg' : 'text-slate-400 hover:text-white'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-all">
                Logs
            </button>
            <button @click="activeTab = 'config'" :class="activeTab === 'config' ? 'bg-slate-700 text-white shadow-lg' : 'text-slate-400 hover:text-white'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-all">
                Config
            </button>
        </div>
    </div>

    {{-- Overview Tab --}}
    <div x-show="activeTab === 'overview'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {{-- System --}}
            <div class="group bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 p-5 hover:border-violet-500/50 transition-all hover:shadow-lg hover:shadow-violet-500/10">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="font-semibold text-white">System</h3>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm"><span class="text-slate-400">Memory</span><span class="font-mono text-white">{{ $systemInfo['memory_limit'] ?? 'N/A' }}</span></div>
                    <div class="flex justify-between text-sm"><span class="text-slate-400">Max Exec</span><span class="font-mono text-white">{{ $systemInfo['max_execution_time'] ?? 'N/A' }}</span></div>
                    <div class="flex justify-between text-sm"><span class="text-slate-400">Upload</span><span class="font-mono text-white">{{ $systemInfo['upload_max_filesize'] ?? 'N/A' }}</span></div>
                </div>
            </div>

            {{-- Database --}}
            <div class="group bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 p-5 hover:border-cyan-500/50 transition-all hover:shadow-lg hover:shadow-cyan-500/10">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
                    </div>
                    <h3 class="font-semibold text-white">Database</h3>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm"><span class="text-slate-400">Driver</span><span class="font-mono text-white">{{ $databaseInfo['connection'] ?? 'N/A' }}</span></div>
                    <div class="flex justify-between text-sm"><span class="text-slate-400">Tables</span><span class="font-mono text-white">{{ $databaseInfo['tables_count'] ?? 0 }}</span></div>
                    <div class="flex justify-between text-sm"><span class="text-slate-400">Status</span><span class="text-emerald-400 font-medium">Connected</span></div>
                </div>
            </div>

            {{-- Redis --}}
            <div class="group bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 p-5 hover:border-red-500/50 transition-all hover:shadow-lg hover:shadow-red-500/10">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                    </div>
                    <h3 class="font-semibold text-white">Redis</h3>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm"><span class="text-slate-400">Status</span><span class="{{ $redisConnected ?? false ? 'text-emerald-400' : 'text-red-400' }} font-medium">{{ $redisConnected ?? false ? 'Connected' : 'Disconnected' }}</span></div>
                    <div class="flex justify-between text-sm"><span class="text-slate-400">Memory</span><span class="font-mono text-white">{{ $redisInfo['used_memory_human'] ?? 'N/A' }}</span></div>
                    <div class="flex justify-between text-sm"><span class="text-slate-400">Keys</span><span class="font-mono text-white">{{ $redisInfo['keys'] ?? 0 }}</span></div>
                </div>
            </div>

            {{-- Queue --}}
            <div class="group bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 p-5 hover:border-amber-500/50 transition-all hover:shadow-lg hover:shadow-amber-500/10">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <h3 class="font-semibold text-white">Queue</h3>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm"><span class="text-slate-400">Driver</span><span class="font-mono text-white">{{ config('queue.default') }}</span></div>
                    <div class="flex justify-between text-sm"><span class="text-slate-400">Workers</span><span class="font-mono text-white">{{ count($queueStatus ?? []) }}</span></div>
                    <div class="flex justify-between text-sm"><span class="text-slate-400">Status</span><span class="text-emerald-400 font-medium">Running</span></div>
                </div>
            </div>
        </div>

        {{-- Storage Usage --}}
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 p-6 mb-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-600 to-slate-700 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white">Storage Usage</h3>
                        <p class="text-sm text-slate-400">{{ $storageInfo['used'] ?? '0 GB' }} of {{ $storageInfo['total'] ?? '0 GB' }} used</p>
                    </div>
                </div>
            </div>
            <div class="h-3 bg-slate-900 rounded-full overflow-hidden mb-4">
                @php $storagePercent = $storageInfo['percent'] ?? 0; @endphp
                <div class="h-full rounded-full transition-all duration-500 {{ $storagePercent > 90 ? 'bg-red-500' : ($storagePercent > 70 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $storagePercent }}%"></div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach(['logs' => 'Logs', 'cache' => 'Cache', 'sessions' => 'Sessions', 'views' => 'Views'] as $key => $label)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-900/50 border border-slate-800">
                        <span class="text-sm text-slate-400">{{ $label }}</span>
                        <button wire:click="cleanStorage('{{ $key }}')" class="text-xs text-slate-500 hover:text-red-400 transition-colors" title="Clean {{ $label }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Cache Management --}}
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </div>
                <div>
                    <h3 class="font-semibold text-white">Cache Management</h3>
                    <p class="text-sm text-slate-400">Clear and rebuild application caches</p>
                </div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                @foreach(['config' => 'Config', 'route' => 'Routes', 'view' => 'Views', 'event' => 'Events', 'all' => 'All'] as $type => $label)
                    <button wire:click="clearCache('{{ $type }}')" wire:loading.attr="disabled"
                        class="group p-4 rounded-xl bg-slate-900/50 border border-slate-700/50 hover:border-indigo-500/50 hover:bg-indigo-500/10 transition-all text-center">
                        <span class="block text-sm font-medium text-white group-hover:text-indigo-400 transition-colors">{{ $label }}</span>
                        <span class="block text-xs text-slate-500 mt-1">Clear</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Services Tab --}}
    <div x-show="activeTab === 'services'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="grid lg:grid-cols-2 gap-6">
            {{-- Supervisor Processes --}}
            <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                        </div>
                        <h3 class="font-semibold text-white">Supervisor</h3>
                    </div>
                    <button wire:click="loadSupervisorProcesses" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-700/50 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </button>
                </div>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @forelse($supervisorProcesses ?? [] as $process)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-900/50 border border-slate-800">
                            <div class="flex items-center gap-3">
                                <span class="w-2 h-2 rounded-full {{ str_contains($process['status'] ?? '', 'RUNNING') ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                <span class="text-sm text-white font-mono">{{ $process['name'] ?? 'unknown' }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <button wire:click="supervisorAction('restart', '{{ $process['name'] ?? '' }}')" class="p-1.5 rounded-lg text-slate-400 hover:text-amber-400 hover:bg-amber-500/10 transition-all" title="Restart">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-slate-500">
                            <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                            <p class="text-sm">No processes found</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Scheduler --}}
            <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="font-semibold text-white">Scheduler</h3>
                    </div>
                    <button wire:click="runScheduler" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-amber-500/20 text-amber-400 hover:bg-amber-500/30 transition-all border border-amber-500/30">
                        Run Now
                    </button>
                </div>
                <div class="p-4 rounded-xl bg-slate-900/50 border border-slate-800">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm text-slate-400">Cron Status</span>
                        <span class="text-sm {{ ($schedulerStatus['cron_configured'] ?? false) ? 'text-emerald-400' : 'text-red-400' }}">{{ ($schedulerStatus['cron_configured'] ?? false) ? 'Configured' : 'Not Configured' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-400">Last Run</span>
                        <span class="text-sm text-white font-mono">{{ $lastSchedulerRun ?? 'Never' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Logs Tab --}}
    <div x-show="activeTab === 'logs'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 overflow-hidden">
            <div class="p-5 border-b border-slate-700/50 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-500 to-pink-600 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white">Application Logs</h3>
                        <p class="text-sm text-slate-400">{{ count($logFiles ?? []) }} log files</p>
                    </div>
                </div>
                <button wire:click="refreshLogs" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-700/50 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
            </div>

            {{-- Log Files List --}}
            <div class="grid lg:grid-cols-4 divide-y lg:divide-y-0 lg:divide-x divide-slate-700/50">
                <div class="p-4 space-y-1 max-h-96 overflow-y-auto lg:col-span-1">
                    @forelse($logFiles ?? [] as $log)
                        <button wire:click="selectLogFile('{{ $log['name'] }}')"
                            class="w-full flex items-center justify-between p-3 rounded-xl text-left transition-all {{ ($selectedLogFile ?? '') === $log['name'] ? 'bg-rose-500/20 border border-rose-500/30' : 'hover:bg-slate-700/50' }}">
                            <div class="min-w-0">
                                <span class="block text-sm font-medium text-white truncate">{{ $log['name'] }}</span>
                                <span class="block text-xs text-slate-500">{{ $log['size'] ?? '0 KB' }}</span>
                            </div>
                        </button>
                    @empty
                        <p class="text-sm text-slate-500 text-center py-4">No log files</p>
                    @endforelse
                </div>

                {{-- Log Content --}}
                <div class="lg:col-span-3 p-4">
                    @if($recentLogs ?? false)
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-slate-400">{{ $selectedLogFile ?? 'Select a log file' }}</span>
                            <div class="flex items-center gap-2">
                                @if($selectedLogFile)
                                    <a href="{{ route('projects.devflow.logs.download', $selectedLogFile) }}" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-700/50 transition-all" title="Download">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    </a>
                                    <button wire:click="clearLogFile('{{ $selectedLogFile }}')" wire:confirm="Clear this log file?" class="p-1.5 rounded-lg text-slate-400 hover:text-red-400 hover:bg-red-500/10 transition-all" title="Clear">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="rounded-xl bg-slate-950 border border-slate-800 p-4 max-h-80 overflow-y-auto font-mono text-xs">
                            <pre class="text-slate-300 whitespace-pre-wrap leading-relaxed">{{ $recentLogs }}</pre>
                        </div>
                    @else
                        <div class="flex items-center justify-center h-64 text-slate-500">
                            <div class="text-center">
                                <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p>Select a log file to view</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Config Tab --}}
    <div x-show="activeTab === 'config'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
        <div class="grid lg:grid-cols-2 gap-6">
            {{-- Environment Variables --}}
            <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white">Environment</h3>
                        <p class="text-sm text-slate-400">Application configuration</p>
                    </div>
                </div>
                <div class="space-y-3">
                    @foreach($envVariables ?? [] as $key => $value)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-900/50 border border-slate-800">
                            <span class="text-sm text-slate-400 font-mono">{{ $key }}</span>
                            <span class="text-sm text-white font-mono truncate max-w-[200px]" title="{{ $value }}">{{ Str::limit($value, 30) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Domain Configuration --}}
            <div class="bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-700/50 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-white">Domain</h3>
                        <p class="text-sm text-slate-400">URL configuration</p>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="p-4 rounded-xl bg-slate-900/50 border border-slate-800">
                        <label class="block text-sm text-slate-400 mb-2">APP_URL</label>
                        <div class="flex items-center gap-2">
                            <input type="text" wire:model="currentAppUrl"
                                class="flex-1 px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-white text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <button wire:click="updateAppUrl" class="px-3 py-2 rounded-lg bg-blue-500 text-white text-sm font-medium hover:bg-blue-600 transition-colors">
                                Save
                            </button>
                        </div>
                    </div>
                    @if($nginxSites ?? false)
                        <div class="p-4 rounded-xl bg-slate-900/50 border border-slate-800">
                            <label class="block text-sm text-slate-400 mb-2">Nginx Sites</label>
                            <div class="space-y-1">
                                @foreach($nginxSites as $site)
                                    <div class="text-sm text-white font-mono">{{ $site }}</div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
