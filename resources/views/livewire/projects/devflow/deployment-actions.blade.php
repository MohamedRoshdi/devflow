<div>
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
