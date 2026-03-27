<div>
    {{-- Alert banner when any process is unhealthy --}}
    @if ($this->hasUnhealthy())
        <div class="mb-4 flex items-center gap-3 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400">
            <svg class="w-5 h-5 flex-shrink-0 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span class="text-sm font-medium">
                {{ collect($processes)->whereIn('status', \App\Services\Monitoring\SupervisorHealthService::UNHEALTHY_STATUSES)->count() }}
                process(es) in an unhealthy state — restart required.
            </span>
        </div>
    @endif

    {{-- Flash messages --}}
    @if (session()->has('message'))
        <div class="mb-4 flex items-center gap-3 px-4 py-3 rounded-xl bg-green-500/10 border border-green-500/30 text-green-400">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm">{{ session('message') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 flex items-center gap-3 px-4 py-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm">{{ session('error') }}</span>
        </div>
    @endif

    {{-- Card --}}
    <div class="bg-white dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-200 dark:border-slate-700/50 overflow-hidden">

        {{-- Card header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700/50
                    {{ $this->hasUnhealthy() ? 'bg-gradient-to-r from-red-600 to-rose-600' : 'bg-gradient-to-r from-slate-700 to-slate-800' }}">

            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-white/10">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-white text-sm">Supervisor Processes</h3>
                    @if ($lastCheckedAt)
                        <p class="text-white/60 text-xs">Last checked: {{ $lastCheckedAt }}</p>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2">
                {{-- Process count badges --}}
                @php
                    $runningCount = collect($processes)->where('status', 'RUNNING')->count();
                    $unhealthyCount = collect($processes)->whereIn('status', \App\Services\Monitoring\SupervisorHealthService::UNHEALTHY_STATUSES)->count();
                @endphp

                @if ($runningCount > 0)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-green-500/20 text-green-300">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
                        {{ $runningCount }} running
                    </span>
                @endif

                @if ($unhealthyCount > 0)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-500/30 text-red-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-300"></span>
                        {{ $unhealthyCount }} unhealthy
                    </span>
                @endif

                {{-- Refresh button --}}
                <button wire:click="refresh"
                        wire:loading.attr="disabled"
                        wire:target="refresh"
                        class="p-2 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors disabled:opacity-50"
                        title="Refresh status">
                    <svg wire:loading.remove wire:target="refresh" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <svg wire:loading wire:target="refresh" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </button>

                {{-- Link to full supervisor manager --}}
                <a href="{{ route('servers.supervisor', $server) }}"
                   class="px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-white text-xs font-medium transition-colors">
                    Manage
                </a>
            </div>
        </div>

        {{-- Process list --}}
        <div class="divide-y divide-slate-200 dark:divide-slate-700/50">
            @forelse ($processes as $process)
                @php
                    $status = $process['status'];
                    $isUnhealthy = in_array($status, \App\Services\Monitoring\SupervisorHealthService::UNHEALTHY_STATUSES);

                    $statusBg = match ($status) {
                        'RUNNING'  => 'bg-green-500/10 text-green-600 dark:text-green-400',
                        'FATAL'    => 'bg-red-500/15 text-red-700 dark:text-red-400 font-semibold',
                        'STOPPED', 'EXITED' => 'bg-red-500/10 text-red-600 dark:text-red-400',
                        'STARTING' => 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
                        'BACKOFF'  => 'bg-yellow-500/10 text-yellow-600 dark:text-yellow-400',
                        default    => 'bg-slate-500/10 text-slate-600 dark:text-slate-400',
                    };

                    $dot = match ($status) {
                        'RUNNING'  => 'bg-green-500 animate-pulse',
                        'FATAL', 'STOPPED', 'EXITED' => 'bg-red-500',
                        'STARTING' => 'bg-blue-500 animate-pulse',
                        'BACKOFF'  => 'bg-yellow-500',
                        default    => 'bg-slate-400',
                    };
                @endphp

                <div wire:key="hmon-{{ $process['name'] }}"
                     class="flex items-center justify-between px-6 py-3 gap-4 {{ $isUnhealthy ? 'bg-red-500/5 dark:bg-red-500/5' : '' }}">

                    {{-- Process name + status dot --}}
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $dot }}"></span>
                        <span class="font-mono text-sm text-slate-900 dark:text-slate-100 truncate">
                            {{ $process['name'] }}
                        </span>
                    </div>

                    {{-- Right side: status badge + uptime + restart button --}}
                    <div class="flex items-center gap-3 flex-shrink-0">
                        {{-- Uptime --}}
                        @if ($process['uptime'])
                            <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                                {{ $process['uptime'] }}
                            </span>
                        @endif

                        {{-- Status badge --}}
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusBg }}">
                            {{ $status }}
                        </span>

                        {{-- Restart button for unhealthy processes --}}
                        @if ($isUnhealthy)
                            @can('update', $server)
                                <button wire:click="restart('{{ $process['name'] }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="restart('{{ $process['name'] }}')"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium
                                               bg-indigo-600 hover:bg-indigo-700 text-white transition-colors disabled:opacity-50">
                                    <svg wire:loading.remove wire:target="restart('{{ $process['name'] }}')" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    <svg wire:loading wire:target="restart('{{ $process['name'] }}')" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Restart
                                </button>
                            @endcan
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                    <svg class="w-10 h-10 mx-auto mb-3 text-slate-400 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <p class="text-sm">No supervisor processes found.</p>
                    <p class="text-xs mt-1">Supervisor may not be installed or has no configured programs.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
