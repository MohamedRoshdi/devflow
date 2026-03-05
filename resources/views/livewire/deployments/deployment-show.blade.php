<div @if(in_array($deployment->status, ['pending', 'running'])) wire:poll.3s="refresh" @endif class="min-h-screen">
    {{-- Animated Background Orbs --}}
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-gradient-to-br from-emerald-500/5 via-teal-500/5 to-cyan-500/5 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-gradient-to-tr from-violet-500/5 via-purple-500/5 to-fuchsia-500/5 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-gradient-to-r from-blue-500/3 to-indigo-500/3 rounded-full blur-3xl"></div>
    </div>

    <x-breadcrumb :items="[
        ['label' => 'Deployments', 'url' => route('deployments.index')],
        ['label' => '#' . $deployment->id],
    ]" />

    <div class="mb-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
        <div>
            <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                Deployment #{{ $deployment->id }}
            </h1>
            <p class="text-gray-500 dark:text-slate-400 mt-2 text-lg">{{ $deployment->project->name }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @if(in_array($deployment->status, ['pending', 'running']))
                <div class="flex items-start space-x-2">
                    <button wire:click="refresh" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-slate-700/50 dark:hover:bg-slate-700/70 dark:text-white rounded-xl font-semibold transition-all duration-300 backdrop-blur-sm border border-slate-600/50 shadow-lg hover:shadow-xl">
                        <span wire:loading.remove wire:target="refresh">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </span>
                        <span wire:loading.remove wire:target="refresh">Refresh</span>
                        <span wire:loading wire:target="refresh">Refreshing...</span>
                    </button>
                    <livewire:components.inline-help help-key="deploy-button" />
                </div>
            @endif
            @if(in_array($deployment->status, ['pending', 'running']))
                <button wire:click="cancelDeployment"
                        wire:confirm="Cancel this deployment? It will be marked as failed."
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600/80 hover:bg-red-500 text-white rounded-xl font-semibold transition-all duration-300 backdrop-blur-sm border border-red-500/50 shadow-lg shadow-red-500/30 hover:shadow-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Cancel
                </button>
            @endif
            @if($deployment->status === 'failed')
                <button wire:click="retryDeployment"
                        wire:confirm="Retry this deployment? A new deployment will be created with the same settings."
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600/80 hover:bg-blue-500 text-white rounded-xl font-semibold transition-all duration-300 backdrop-blur-sm border border-blue-500/50 shadow-lg shadow-blue-500/30 hover:shadow-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Retry
                </button>
            @endif
            @if($this->canRollback())
                <button wire:click="initiateRollback"
                        wire:loading.attr="disabled"
                        wire:target="confirmRollback"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-600/80 hover:bg-amber-500 text-white rounded-xl font-semibold transition-all duration-300 backdrop-blur-sm border border-amber-500/50 shadow-lg shadow-amber-500/30 hover:shadow-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                    </svg>
                    Rollback to This Version
                </button>
            @endif
            <a href="{{ route('deployments.index') }}" class="inline-flex items-center gap-2 px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-white/10 dark:hover:bg-white/20 dark:text-white rounded-xl font-semibold transition-all duration-300 backdrop-blur-sm border border-gray-300 dark:border-white/20 hover:border-gray-400 dark:hover:border-white/30 shadow-lg hover:shadow-xl">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to List
            </a>
        </div>
    </div>

    <!-- Progress Bar (shown during deployment) -->
    @if(in_array($deployment->status, ['pending', 'running']))
        <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-2xl border border-gray-200 dark:border-slate-700/50 p-6 mb-8">
            <div class="mb-4">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        @if($deployment->status === 'running')
                            <svg class="w-6 h-6 text-blue-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Deployment in Progress
                        @else
                            <svg class="w-6 h-6 text-amber-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Deployment Pending
                        @endif
                    </h3>
                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300 bg-slate-200/80 dark:bg-slate-700/50 px-3 py-1 rounded-lg">{{ $progress }}%</span>
                </div>

                <!-- Progress Bar -->
                <div class="w-full bg-slate-200 dark:bg-slate-700/50 rounded-full h-3 overflow-hidden border border-slate-300 dark:border-slate-600/50">
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-500 h-3 rounded-full transition-all duration-500 ease-out relative overflow-hidden"
                         style="width: {{ $progress }}%">
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-30 animate-shimmer"></div>
                    </div>
                </div>

                @if($currentStep)
                    <p class="text-sm text-slate-600 dark:text-slate-300 mt-3 flex items-center">
                        <svg class="animate-spin h-4 w-4 mr-2 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="font-medium">{{ $currentStep }}</span>
                    </p>
                @endif
            </div>

            <!-- Estimated Time -->
            <div class="bg-gradient-to-r from-blue-600/20 to-cyan-600/20 border border-blue-500/30 rounded-xl p-4 mt-4 backdrop-blur-sm">
                <p class="text-sm text-blue-700 dark:text-blue-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <strong>Estimated time:</strong> 12-18 minutes
                    @if($deployment->started_at)
                        <span class="ml-2">• Running for {{ $deployment->started_at->diffForHumans(null, true) }}</span>
                    @endif
                </p>
                <p class="text-xs text-blue-600 dark:text-blue-300 mt-1">
                    Large builds with npm can take time. The page auto-refreshes every 3 seconds.
                </p>
            </div>
        </div>
    @endif

    <!-- Status Card -->
    <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-2xl border border-gray-200 dark:border-slate-700/50 p-8 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Status</p>
                <p class="mt-2">
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold shadow-lg
                        @if($deployment->status === 'success') bg-gradient-to-r from-emerald-500 to-green-500 text-white shadow-emerald-500/40
                        @elseif($deployment->status === 'failed') bg-gradient-to-r from-red-500 to-rose-500 text-white shadow-red-500/40
                        @elseif($deployment->status === 'running') bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-amber-500/40
                        @elseif($deployment->status === 'pending') bg-gradient-to-r from-blue-500 to-indigo-500 text-white shadow-blue-500/40
                        @else bg-gradient-to-r from-slate-500 to-slate-600 text-white shadow-slate-500/40
                        @endif">
                        @if($deployment->status === 'success')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                            </svg>
                        @elseif($deployment->status === 'failed')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        @elseif($deployment->status === 'running')
                            <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        @elseif($deployment->status === 'pending')
                            <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        @endif
                        {{ ucfirst($deployment->status) }}
                    </span>
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Branch</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white mt-2">{{ $deployment->branch }}</p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Duration</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white mt-2">
                    @if($deployment->duration_seconds)
                        {{ $deployment->duration_seconds }}s ({{ number_format($deployment->duration_seconds / 60, 1) }} min)
                    @elseif($deployment->started_at)
                        {{ $deployment->started_at->diffForHumans(null, true) }} (in progress)
                    @else
                        Pending...
                    @endif
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Triggered By</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white mt-2">{{ ucfirst($deployment->triggered_by) }}</p>
            </div>
        </div>
    </div>

    {{-- Post-Success Panel --}}
    @if($deployment->status === 'success')
        <div class="bg-gradient-to-r from-emerald-500/10 via-green-500/10 to-teal-500/10 backdrop-blur-sm rounded-2xl border border-emerald-500/30 shadow-lg shadow-emerald-500/10 p-6 mb-8">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 flex items-center justify-center shadow-lg shadow-emerald-500/30">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Deployment Successful</h3>
                        <p class="text-sm text-gray-500 dark:text-slate-400">
                            Completed in {{ $deployment->duration_seconds ? $deployment->duration_seconds . 's' : 'N/A' }}
                            @if($deployment->completed_at)
                                &mdash; {{ $deployment->completed_at->diffForHumans() }}
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    @php
                        $primaryDomain = $deployment->project?->domains?->where('is_primary', true)->first()
                            ?? $deployment->project?->domains?->first();
                        $siteUrl = null;
                        if ($primaryDomain) {
                            $domain = $primaryDomain->domain;
                            $siteUrl = str_starts_with($domain, 'http') ? $domain : ('http://' . $domain);
                        }
                    @endphp
                    @if($siteUrl)
                        <a href="{{ $siteUrl }}" target="_blank" rel="noopener"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-green-600 text-white font-semibold shadow-lg shadow-emerald-500/30 hover:shadow-xl transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            View Your Site
                        </a>
                    @endif
                    <a href="{{ route('projects.show', $deployment->project?->slug ?? $deployment->project_id) }}" wire:navigate
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-white/10 dark:hover:bg-white/20 dark:text-white font-medium border border-gray-300 dark:border-white/20 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Project
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- Post-Failure Panel --}}
    @if($deployment->status === 'failed')
        <div class="bg-gradient-to-r from-red-500/10 via-rose-500/10 to-pink-500/10 backdrop-blur-sm rounded-2xl border border-red-500/30 shadow-lg shadow-red-500/10 p-6 mb-8">
            <div class="flex items-start gap-4 mb-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center shadow-lg shadow-red-500/30 flex-shrink-0">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Deployment Failed</h3>
                    @if($deployment->error_log)
                        <p class="text-sm text-red-300 mt-1 font-mono truncate">{{ \Illuminate\Support\Str::limit($deployment->error_log, 200) }}</p>
                    @endif
                </div>
            </div>

            <div class="p-4 rounded-xl bg-gray-100 dark:bg-slate-800/50 border border-gray-200 dark:border-slate-700/50 mb-4">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-slate-300 mb-2">Suggested next steps:</h4>
                <ul class="text-sm text-gray-500 dark:text-slate-400 space-y-1.5">
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        Review the deployment logs below for error details
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        Verify the server is reachable via SSH
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        Check environment variables and secrets are configured
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        Ensure Docker is installed and running (if using Docker deployment)
                    </li>
                </ul>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button wire:click="retryDeployment"
                    wire:confirm="Retry this deployment? A new deployment will be created with the same settings."
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold shadow-lg shadow-blue-500/30 hover:shadow-xl transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Retry Deployment
                </button>
                <a href="{{ route('projects.show', $deployment->project?->slug ?? $deployment->project_id) }}" wire:navigate
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-white/10 dark:hover:bg-white/20 dark:text-white font-medium border border-gray-300 dark:border-white/20 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Project
                </a>
                @if($deployment->project?->server)
                    <a href="{{ route('servers.show', $deployment->project->server) }}" wire:navigate
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-white/10 dark:hover:bg-white/20 dark:text-white font-medium border border-gray-300 dark:border-white/20 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2"/>
                        </svg>
                        Check Server
                    </a>
                @endif
            </div>
        </div>
    @endif

    <style>
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        .animate-shimmer {
            animation: shimmer 2s infinite;
        }
    </style>

    <!-- Details -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-2xl border border-gray-200 dark:border-slate-700/50 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600/10 to-cyan-600/10 dark:from-blue-600/20 dark:to-cyan-600/20 p-6 border-b border-gray-200 dark:border-slate-700/50">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Deployment Details</h2>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex justify-between items-center py-2">
                    <span class="text-gray-500 dark:text-slate-400">Project:</span>
                    <a href="{{ route('projects.show', $deployment->project) }}"
                       class="font-medium text-blue-400 hover:text-blue-300 hover:underline transition-colors">
                        {{ $deployment->project->name }}
                    </a>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-gray-500 dark:text-slate-400">Server:</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $deployment->server->name ?? 'None' }}</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-gray-500 dark:text-slate-400">Commit Hash:</span>
                    <code class="font-medium font-mono text-sm bg-gray-100 dark:bg-slate-700/50 px-3 py-1 rounded-lg text-cyan-600 dark:text-cyan-300">{{ substr($deployment->commit_hash ?? 'N/A', 0, 8) }}</code>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-gray-500 dark:text-slate-400">Started At:</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $deployment->started_at ? $deployment->started_at->format('Y-m-d H:i:s') : '-' }}</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-gray-500 dark:text-slate-400">Completed At:</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $deployment->completed_at ? $deployment->completed_at->format('Y-m-d H:i:s') : 'In progress' }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-2xl border border-gray-200 dark:border-slate-700/50 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600/10 to-cyan-600/10 dark:from-blue-600/20 dark:to-cyan-600/20 p-6 border-b border-gray-200 dark:border-slate-700/50">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Commit Information</h2>
            </div>
            <div class="p-6">
                <p class="text-gray-900 dark:text-white leading-relaxed">{{ e($deployment->commit_message ?? 'No commit message available') }}</p>
            </div>
        </div>
    </div>

    <!-- Deployment Steps Progress -->
    @if(in_array($deployment->status, ['pending', 'running']))
        <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-2xl border border-gray-200 dark:border-slate-700/50 mb-8 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600/10 to-cyan-600/10 dark:from-blue-600/20 dark:to-cyan-600/20 p-6 border-b border-gray-200 dark:border-slate-700/50">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Deployment Steps</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @php
                        $logs = $deployment->output_log ?? '';
                        $steps = [
                            [
                                'name' => 'Setup Repository',
                                'markers' => ['=== Setting Up Repository ===', '=== Cloning Repository ==='],
                                'complete_markers' => ['✓ Repository cloned successfully', '✓ Repository updated successfully']
                            ],
                            [
                                'name' => 'Record Commit Info',
                                'markers' => ['=== Recording Commit Information ==='],
                                'complete_markers' => ['✓ Commit information recorded']
                            ],
                            [
                                'name' => 'Build Docker Image',
                                'markers' => ['=== Building Docker Container ==='],
                                'complete_markers' => ['✓ Build successful']
                            ],
                            [
                                'name' => 'Start Container',
                                'markers' => ['=== Starting Container ==='],
                                'complete_markers' => ['Container started successfully', 'Container started']
                            ],
                        ];
                    @endphp
                    
                    @foreach($steps as $step)
                        @php
                            $isActive = false;
                            $isComplete = false;
                            foreach ($step['markers'] as $marker) {
                                if (str_contains($logs, $marker)) {
                                    $isActive = true;
                                    break;
                                }
                            }
                            foreach ($step['complete_markers'] as $completeMarker) {
                                if (str_contains($logs, $completeMarker)) {
                                    $isComplete = true;
                                    break;
                                }
                            }
                        @endphp
                        
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                @if($isComplete)
                                    <div class="h-8 w-8 rounded-full bg-gradient-to-br from-emerald-500 to-green-500 flex items-center justify-center shadow-lg shadow-emerald-500/40">
                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                @elseif($isActive)
                                    <div class="h-8 w-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center shadow-lg shadow-blue-500/40">
                                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                @else
                                    <div class="h-8 w-8 rounded-full bg-slate-200 dark:bg-slate-700/50 flex items-center justify-center border border-slate-300 dark:border-slate-600/50">
                                        <span class="text-slate-500 dark:text-slate-400 text-sm font-medium">{{ $loop->iteration }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1">
                                <p class="font-medium {{ $isActive ? 'text-blue-600 dark:text-blue-400' : ($isComplete ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400') }}">
                                    {{ $step['name'] }}
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                @if($isComplete)
                                    <span class="text-xs text-emerald-400 font-medium flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Complete
                                    </span>
                                @elseif($isActive)
                                    <span class="text-xs text-blue-400 font-medium flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span>
                                        In Progress
                                    </span>
                                @else
                                    <span class="text-xs text-slate-500">Pending</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Logs -->
    <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-2xl border border-gray-200 dark:border-slate-700/50 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600/10 to-cyan-600/10 dark:from-blue-600/20 dark:to-cyan-600/20 p-6 border-b border-gray-200 dark:border-slate-700/50">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Deployment Logs</h2>
                <div class="flex items-center space-x-3">
                    @if(in_array($deployment->status, ['pending', 'running']))
                        <span class="flex items-center text-sm text-blue-400 bg-blue-500/20 px-3 py-1 rounded-lg border border-blue-500/30">
                            <span class="h-2 w-2 bg-blue-400 rounded-full mr-2 animate-pulse"></span>
                            Live Streaming
                        </span>
                    @endif
                    <button wire:click="exportLogs"
                            class="inline-flex items-center gap-2 px-3 py-1.5 text-sm bg-gray-100 hover:bg-gray-200 text-gray-600 dark:bg-slate-700/50 dark:hover:bg-slate-600/50 dark:text-slate-300 rounded-lg border border-gray-300 dark:border-slate-600/50 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Export Logs
                    </button>
                </div>
            </div>
            <div>
                <input wire:model.live.debounce.300ms="logSearch"
                       type="text"
                       placeholder="Search logs..."
                       class="w-full px-4 py-2 bg-gray-100 dark:bg-slate-800/50 border border-gray-300 dark:border-slate-600/50 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 text-sm focus:border-blue-500/50 focus:ring-1 focus:ring-blue-500/30 transition-all">
            </div>
        </div>
        <div class="p-6">
            @if(count($liveLogs) > 0 || $deployment->output_log)
                <div x-data="{
                    autoScroll: true,
                    isPaused: false,
                    lineCount: {{ count($liveLogs) }},
                    init() {
                        this.scrollToBottom();

                        // Watch for new log lines
                        this.$watch('lineCount', () => {
                            if (this.autoScroll && !this.isPaused) {
                                this.$nextTick(() => this.scrollToBottom());
                            }
                        });
                    },
                    scrollToBottom() {
                        const container = this.$refs.logContainer;
                        if (container) {
                            container.scrollTop = container.scrollHeight;
                        }
                    },
                    togglePause() {
                        this.isPaused = !this.isPaused;
                        if (!this.isPaused && this.autoScroll) {
                            this.scrollToBottom();
                        }
                    },
                    handleScroll() {
                        const container = this.$refs.logContainer;
                        const threshold = 50;
                        const isAtBottom = (container.scrollHeight - container.scrollTop - container.clientHeight) < threshold;
                        this.autoScroll = isAtBottom;
                    }
                }" class="relative">

                    <!-- Control Buttons -->
                    <div class="mb-3 flex justify-end space-x-2">
                        <button
                            @click="togglePause()"
                            class="px-3 py-2 text-xs rounded-lg font-medium transition-all duration-300 backdrop-blur-sm border"
                            :class="isPaused ? 'bg-emerald-600/80 hover:bg-emerald-500 text-white border-emerald-500/50 shadow-lg shadow-emerald-500/30' : 'bg-amber-600/80 hover:bg-amber-500 text-white border-amber-500/50 shadow-lg shadow-amber-500/30'">
                            <span x-show="!isPaused">Pause Auto-scroll</span>
                            <span x-show="isPaused">Resume Auto-scroll</span>
                        </button>
                        <button
                            @click="scrollToBottom()"
                            class="px-3 py-2 text-xs bg-blue-600/80 hover:bg-blue-500 text-white rounded-lg font-medium transition-all duration-300 backdrop-blur-sm border border-blue-500/50 shadow-lg shadow-blue-500/30">
                            Scroll to Bottom
                        </button>
                    </div>

                    <!-- Log Terminal -->
                    <div
                        x-ref="logContainer"
                        @scroll="handleScroll()"
                        wire:poll.3s
                        x-init="lineCount = {{ count($liveLogs) }}"
                        x-effect="lineCount = {{ count($liveLogs) }}"
                        class="bg-[#1a1a2e] text-gray-300 p-6 rounded-lg font-mono text-sm overflow-x-auto max-h-[600px] overflow-y-auto border border-gray-700 shadow-inner">

                        @if(count($liveLogs) > 0)
                            @foreach($liveLogs as $index => $log)
                                @if(!$logSearch || str_contains(strtolower($log['line']), strtolower($logSearch)))
                                <div class="flex hover:bg-gray-800/50 transition-colors py-0.5">
                                    <!-- Line Number -->
                                    <div class="flex-shrink-0 w-12 text-right pr-4 text-gray-600 select-none">
                                        {{ $index + 1 }}
                                    </div>

                                    <!-- Log Line -->
                                    <div class="flex-1 @if($log['level'] === 'error') text-red-400 font-semibold @elseif($log['level'] === 'warning') text-yellow-400 @else text-gray-300 @endif">
                                        {{ e($log['line']) }}
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        @elseif($deployment->output_log)
                            @foreach(explode("\n", $deployment->output_log) as $index => $line)
                                @php
                                    $level = 'info';
                                    $lowerLine = strtolower($line);
                                    if (preg_match('/^(error|fatal|failed)/i', $line) || str_contains($lowerLine, 'exception')) {
                                        $level = 'error';
                                    } elseif (preg_match('/^(warning|warn|notice)/i', $line) || str_contains($lowerLine, 'skipped')) {
                                        $level = 'warning';
                                    }
                                @endphp
                                @if(!$logSearch || str_contains($lowerLine, strtolower($logSearch)))
                                <div class="flex hover:bg-gray-800/50 transition-colors py-0.5">
                                    <!-- Line Number -->
                                    <div class="flex-shrink-0 w-12 text-right pr-4 text-gray-600 select-none">
                                        {{ $index + 1 }}
                                    </div>

                                    <!-- Log Line -->
                                    <div class="flex-1 @if($level === 'error') text-red-400 font-semibold @elseif($level === 'warning') text-yellow-400 @else text-gray-300 @endif">
                                        {{ e($line) }}
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        @endif
                    </div>

                    <!-- Status Indicator -->
                    <div class="mt-3 flex justify-between items-center">
                        <div class="text-xs text-slate-500 dark:text-slate-400">
                            @if(in_array($deployment->status, ['pending', 'running']))
                                <span class="flex items-center gap-2 bg-blue-500/20 px-3 py-1.5 rounded-lg border border-blue-500/30">
                                    <svg class="animate-spin h-3 w-3 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-blue-600 dark:text-blue-300">Real-time streaming via WebSocket</span>
                                </span>
                            @else
                                <span class="bg-slate-200 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600/50">{{ count($liveLogs) }} log lines</span>
                            @endif
                        </div>

                        <div class="text-xs" x-show="!autoScroll">
                            <span class="px-3 py-1.5 bg-amber-500/20 text-amber-400 rounded-lg border border-amber-500/30 font-medium">
                                Auto-scroll paused
                            </span>
                        </div>
                    </div>
                </div>
            @elseif(in_array($deployment->status, ['pending', 'running']))
                <div class="text-center py-12">
                    <svg class="animate-spin h-12 w-12 mx-auto text-blue-400 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-slate-700 dark:text-slate-300 font-medium">Starting deployment...</p>
                    <p class="text-xs text-slate-500 mt-2">Logs will stream in real-time shortly</p>
                </div>
            @else
                <p class="text-slate-500 dark:text-slate-400 text-center py-8">No logs available</p>
            @endif
        </div>
    </div>

    @if($deployment->error_log)
        <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-2xl border border-red-200 dark:border-red-500/50 mt-8 overflow-hidden">
            <div class="bg-gradient-to-r from-red-600/20 to-rose-600/20 p-6 border-b border-red-500/50">
                <h2 class="text-xl font-bold text-red-400 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Error Logs
                </h2>
            </div>
            <div class="p-6">
                <div class="bg-red-950/50 text-red-200 p-4 rounded-xl font-mono text-sm overflow-x-auto max-h-96 overflow-y-auto border border-red-500/30">
                    <pre>{{ e($deployment->error_log) }}</pre>
                </div>
            </div>
        </div>
    @endif

    {{-- Rollback Confirmation Modal --}}
    @if($showRollbackConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" wire:click.self="cancelRollback">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-slate-700 max-w-md w-full mx-4 overflow-hidden">
                <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-600/20 dark:to-orange-600/20 p-6 border-b border-gray-200 dark:border-slate-700">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.27 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                        Confirm Rollback
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-gray-600 dark:text-slate-300">
                        Are you sure you want to rollback to this deployment?
                    </p>
                    <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-4 text-sm text-gray-700 dark:text-slate-300 space-y-1">
                        <p><span class="text-gray-500 dark:text-slate-400">Commit:</span> <code class="text-cyan-600 dark:text-cyan-300">{{ substr($deployment->commit_hash ?? '', 0, 8) }}</code></p>
                        <p><span class="text-gray-500 dark:text-slate-400">Message:</span> {{ e(\Illuminate\Support\Str::limit($deployment->commit_message ?? 'N/A', 80)) }}</p>
                        <p><span class="text-gray-500 dark:text-slate-400">Branch:</span> {{ $deployment->branch }}</p>
                    </div>
                    <div class="bg-amber-50 border border-amber-200 dark:bg-amber-900/30 dark:border-amber-500/30 rounded-lg p-3 text-sm text-amber-700 dark:text-amber-200">
                        This will create a new deployment that reverts the application to this version.
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button wire:click="cancelRollback"
                                class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-white rounded-xl font-semibold transition-all border border-gray-200 dark:border-slate-600">
                            Cancel
                        </button>
                        <button wire:click="confirmRollback"
                                wire:loading.attr="disabled"
                                wire:target="confirmRollback"
                                class="px-5 py-2.5 bg-amber-600 hover:bg-amber-500 text-white rounded-xl font-semibold transition-all border border-amber-500 shadow-lg shadow-amber-500/30">
                            <span wire:loading.remove wire:target="confirmRollback">Confirm Rollback</span>
                            <span wire:loading wire:target="confirmRollback">Rolling back...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Rollback In Progress Overlay --}}
    @if($rollbackInProgress)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm">
            <div class="text-center">
                <svg class="animate-spin h-12 w-12 mx-auto text-amber-400 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-white text-lg font-semibold">Rollback in progress...</p>
                <p class="text-slate-300 dark:text-slate-400 text-sm mt-1">Please wait while we revert the deployment.</p>
            </div>
        </div>
    @endif
</div>

