<div>
    {{-- Default State - Show Deploy Actions --}}
    @if(!$isDeploying && !$deploymentStatus)
        <div class="space-y-6">
            {{-- Main Deploy Card --}}
            <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 rounded-2xl border border-slate-700/50 overflow-hidden shadow-xl">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-6">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-cyan-600 flex items-center justify-center shadow-lg shadow-emerald-500/20">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-white">Deploy DevFlow Pro</h2>
                                <p class="text-slate-400 text-sm mt-1">Pull latest changes, install dependencies, and rebuild caches</p>
                            </div>
                        </div>
                        @if($isGitRepo)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                                {{ $gitBranch }}
                            </span>
                        @endif
                    </div>

                    {{-- Deployment Steps Preview --}}
                    <div class="grid grid-cols-3 gap-3 mb-6">
                        @php
                            $previewSteps = [
                                ['icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', 'label' => 'Git Pull', 'color' => 'orange'],
                                ['icon' => 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'label' => 'Composer', 'color' => 'purple'],
                                ['icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'label' => 'NPM Build', 'color' => 'yellow'],
                                ['icon' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4', 'label' => 'Migrate', 'color' => 'blue'],
                                ['icon' => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16', 'label' => 'Clear Cache', 'color' => 'red'],
                                ['icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', 'label' => 'Rebuild', 'color' => 'emerald'],
                            ];
                            $colors = [
                                'orange' => 'from-orange-500/10 to-orange-600/5 text-orange-400 border-orange-500/20',
                                'purple' => 'from-purple-500/10 to-purple-600/5 text-purple-400 border-purple-500/20',
                                'yellow' => 'from-yellow-500/10 to-yellow-600/5 text-yellow-400 border-yellow-500/20',
                                'blue' => 'from-blue-500/10 to-blue-600/5 text-blue-400 border-blue-500/20',
                                'red' => 'from-red-500/10 to-red-600/5 text-red-400 border-red-500/20',
                                'emerald' => 'from-emerald-500/10 to-emerald-600/5 text-emerald-400 border-emerald-500/20',
                            ];
                        @endphp
                        @foreach($previewSteps as $step)
                            <div class="bg-gradient-to-br {{ $colors[$step['color']] }} rounded-xl p-3 border flex items-center gap-2">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $step['icon'] }}"/>
                                </svg>
                                <span class="text-xs font-medium truncate">{{ $step['label'] }}</span>
                            </div>
                        @endforeach
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex flex-wrap gap-3">
                        <button wire:click="redeploy"
                            class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-semibold text-white bg-gradient-to-r from-emerald-500 to-cyan-600 hover:from-emerald-600 hover:to-cyan-700 shadow-lg shadow-emerald-500/25 transition-all hover:shadow-emerald-500/40 hover:scale-[1.02]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            Deploy Now
                        </button>
                        <button wire:click="toggleDeployScript"
                            class="inline-flex items-center gap-2 px-4 py-3 rounded-xl font-medium text-slate-300 bg-slate-800/80 border border-slate-700/50 hover:bg-slate-700/80 hover:text-white transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                            </svg>
                            Edit Script
                        </button>
                    </div>
                </div>
            </div>

            {{-- Quick Info Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Git Status --}}
                <div class="bg-slate-800/50 rounded-xl border border-slate-700/50 p-4">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-lg bg-orange-500/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-orange-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-white">Git Repository</h4>
                            <p class="text-xs text-slate-400">{{ $isGitRepo ? 'Connected' : 'Not configured' }}</p>
                        </div>
                    </div>
                    @if($isGitRepo)
                        <div class="text-xs text-slate-500">Branch: <span class="text-orange-400 font-medium">{{ $gitBranch }}</span></div>
                    @endif
                </div>

                {{-- Deployment Script --}}
                <div class="bg-slate-800/50 rounded-xl border border-slate-700/50 p-4">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-lg bg-purple-500/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-white">Deploy Script</h4>
                            <p class="text-xs text-slate-400">{{ file_exists(base_path('deploy.sh')) ? 'Custom script' : 'Default script' }}</p>
                        </div>
                    </div>
                    <div class="text-xs text-slate-500">9 steps configured</div>
                </div>

                {{-- Server Info --}}
                <div class="bg-slate-800/50 rounded-xl border border-slate-700/50 p-4">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-lg bg-cyan-500/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-white">Server</h4>
                            <p class="text-xs text-slate-400">PHP {{ PHP_VERSION }}</p>
                        </div>
                    </div>
                    <div class="text-xs text-slate-500">Laravel {{ app()->version() }}</div>
                </div>
            </div>

            {{-- Deployment Notes --}}
            <div class="bg-amber-500/5 border border-amber-500/20 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-amber-400 mb-1">Before Deploying</h4>
                        <ul class="text-xs text-slate-400 space-y-1">
                            <li>• Make sure all changes are committed and pushed to the repository</li>
                            <li>• The site will briefly enter maintenance mode during deployment</li>
                            <li>• Database migrations will run automatically with --force flag</li>
                        </ul>
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

    {{-- Deploy Script Editor Modal --}}
    @if($showDeployScript)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-transition>
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" wire:click="toggleDeployScript"></div>
                <div class="relative bg-slate-900 rounded-2xl border border-slate-700/50 shadow-2xl w-full max-w-4xl overflow-hidden">
                    <div class="p-6 border-b border-slate-700/50">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-white">Deployment Script Editor</h3>
                            <button wire:click="toggleDeployScript" class="text-slate-400 hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="p-6">
                        <textarea wire:model="deployScript" rows="20"
                            class="w-full px-4 py-3 rounded-xl bg-slate-950 border border-slate-800 text-emerald-400 placeholder-slate-500 focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all font-mono text-sm"></textarea>
                        <div class="flex gap-3 mt-4">
                            <button wire:click="saveDeployScript"
                                class="px-4 py-2 rounded-xl bg-emerald-500 text-white font-medium hover:bg-emerald-600 transition-colors">
                                Save Script
                            </button>
                            <button wire:click="resetDeployScript"
                                class="px-4 py-2 rounded-xl bg-slate-700 text-white font-medium hover:bg-slate-600 transition-colors">
                                Reset to Default
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
