<div>
    {{-- Service Management Component --}}
    <div class="space-y-6">
        {{-- Queue Workers --}}
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-white">Queue Workers</h3>
                <button wire:click="restartQueue" class="px-4 py-2 rounded-lg font-medium text-sm bg-emerald-600 text-white hover:bg-emerald-500 transition-colors">
                    Restart Queue
                </button>
            </div>
            @if(empty($queueStatus))
                <div class="text-slate-400 text-sm">No queue workers found via Supervisor</div>
            @else
                <div class="space-y-2">
                    @foreach($queueStatus as $worker)
                        <div class="bg-slate-900/50 rounded-lg p-3 flex items-center justify-between">
                            <div>
                                <div class="text-white font-medium">{{ $worker['name'] }}</div>
                                <div class="text-xs text-slate-400">{{ $worker['info'] }}</div>
                            </div>
                            <span class="px-3 py-1 rounded-lg text-xs font-medium {{ $worker['status'] === 'RUNNING' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400' }}">
                                {{ $worker['status'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Reverb WebSocket --}}
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Reverb WebSocket Server</h3>
            @if(isset($reverbStatus['error']))
                <div class="text-red-400">Error: {{ $reverbStatus['error'] }}</div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="bg-slate-900/50 rounded-lg p-4">
                        <div class="text-xs text-slate-400 mb-1">Status</div>
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full {{ $reverbRunning ? 'bg-emerald-500' : 'bg-slate-500' }}"></div>
                            <span class="text-white">{{ $reverbRunning ? 'Running' : 'Stopped' }}</span>
                        </div>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-4">
                        <div class="text-xs text-slate-400 mb-1">Host:Port</div>
                        <div class="text-white font-mono">{{ $reverbStatus['host'] ?? 'N/A' }}:{{ $reverbStatus['port'] ?? 'N/A' }}</div>
                    </div>
                </div>
                <div class="flex gap-2">
                    @if($reverbRunning)
                        <button wire:click="stopReverb" class="px-4 py-2 rounded-lg font-medium text-sm bg-red-600 text-white hover:bg-red-500 transition-colors">
                            Stop Reverb
                        </button>
                        <button wire:click="restartReverb" class="px-4 py-2 rounded-lg font-medium text-sm bg-amber-600 text-white hover:bg-amber-500 transition-colors">
                            Restart Reverb
                        </button>
                    @else
                        <button wire:click="startReverb" class="px-4 py-2 rounded-lg font-medium text-sm bg-emerald-600 text-white hover:bg-emerald-500 transition-colors">
                            Start Reverb
                        </button>
                    @endif
                </div>
                @if($reverbOutput)
                    <div class="mt-4 bg-slate-900/50 border border-slate-700 rounded-lg p-3">
                        <pre class="text-xs text-slate-300">{{ $reverbOutput }}</pre>
                    </div>
                @endif
            @endif
        </div>

        {{-- Supervisor Processes --}}
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-white">Supervisor Processes</h3>
                <button wire:click="refreshServices" class="text-sm text-emerald-400 hover:text-emerald-300">Refresh</button>
            </div>
            @if(empty($supervisorProcesses))
                <div class="text-slate-400 text-sm">No supervisor processes found or supervisor not available</div>
            @else
                <div class="space-y-2 mb-4">
                    @foreach($supervisorProcesses as $process)
                        <div class="bg-slate-900/50 rounded-lg p-3 flex items-center justify-between">
                            <div class="flex-1">
                                <div class="text-white font-medium">{{ $process['name'] }}</div>
                                <div class="text-xs text-slate-400">{{ $process['info'] }}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 rounded-lg text-xs font-medium {{ in_array($process['status'], ['RUNNING', 'STARTING']) ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400' }}">
                                    {{ $process['status'] }}
                                </span>
                                <div class="flex gap-1">
                                    <button wire:click="supervisorAction('start', '{{ $process['name'] }}')" class="px-2 py-1 rounded text-xs bg-emerald-600 text-white hover:bg-emerald-500">Start</button>
                                    <button wire:click="supervisorAction('stop', '{{ $process['name'] }}')" class="px-2 py-1 rounded text-xs bg-red-600 text-white hover:bg-red-500">Stop</button>
                                    <button wire:click="supervisorAction('restart', '{{ $process['name'] }}')" class="px-2 py-1 rounded text-xs bg-amber-600 text-white hover:bg-amber-500">Restart</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="flex gap-2">
                    <button wire:click="supervisorAction('restart', 'all')" class="px-4 py-2 rounded-lg font-medium text-sm bg-emerald-600 text-white hover:bg-emerald-500 transition-colors">
                        Restart All
                    </button>
                    <button wire:click="supervisorAction('reread', 'all')" class="px-4 py-2 rounded-lg font-medium text-sm bg-slate-700 text-white hover:bg-slate-600 transition-colors">
                        Reread Config
                    </button>
                    <button wire:click="supervisorAction('update', 'all')" class="px-4 py-2 rounded-lg font-medium text-sm bg-slate-700 text-white hover:bg-slate-600 transition-colors">
                        Update
                    </button>
                </div>
            @endif
        </div>

        {{-- Scheduler --}}
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Task Scheduler</h3>
            @if(isset($schedulerStatus['error']))
                <div class="text-red-400">Error: {{ $schedulerStatus['error'] }}</div>
            @else
                <div class="space-y-4">
                    <div class="bg-slate-900/50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-xs text-slate-400">Cron Configuration</div>
                            <span class="px-2 py-1 rounded text-xs {{ $schedulerStatus['cron_configured'] ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400' }}">
                                {{ $schedulerStatus['cron_configured'] ? 'Configured' : 'Not Configured' }}
                            </span>
                        </div>
                        <div class="text-xs text-slate-400">Last Run</div>
                        <div class="text-white font-mono text-sm">{{ $lastSchedulerRun }}</div>
                    </div>
                    @if(!empty($schedulerStatus['tasks']))
                        <div>
                            <div class="text-sm text-slate-400 mb-2">Scheduled Tasks</div>
                            <div class="bg-slate-900/50 border border-slate-700 rounded-lg p-3 max-h-64 overflow-y-auto">
                                <pre class="text-xs text-slate-300 whitespace-pre-wrap">{{ implode("\n", $schedulerStatus['tasks']) }}</pre>
                            </div>
                        </div>
                    @endif
                    <button wire:click="runScheduler" class="px-4 py-2 rounded-lg font-medium text-sm bg-emerald-600 text-white hover:bg-emerald-500 transition-colors">
                        Run Scheduler Now
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
