<div>
<div class="relative min-h-screen">
    {{-- Animated Background Orbs --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-gradient-to-br from-cyan-500/30 via-teal-500/30 to-emerald-500/30 rounded-full blur-3xl animate-float"></div>
        <div class="absolute bottom-1/4 right-1/3 w-80 h-80 bg-gradient-to-br from-teal-500/20 via-cyan-500/20 to-blue-500/20 rounded-full blur-3xl animate-float-delayed"></div>
        <div class="absolute top-1/2 right-1/4 w-72 h-72 bg-gradient-to-br from-emerald-500/25 via-teal-500/25 to-cyan-500/25 rounded-full blur-3xl animate-float-slow"></div>
    </div>

    <div class="relative">
        {{-- Glassmorphism Card Container --}}
        <div class="bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-2xl shadow-slate-900/20 dark:shadow-slate-900/60 border border-slate-200 dark:border-slate-700/50 overflow-hidden">
            {{-- Premium Gradient Header --}}
            <div class="relative bg-gradient-to-br from-cyan-600 via-teal-600 to-emerald-600 px-8 py-10 overflow-hidden">
                {{-- Grid Pattern Overlay --}}
                <div class="absolute inset-0 bg-grid-pattern opacity-10"></div>
                <div class="absolute inset-0 bg-gradient-to-b from-transparent to-slate-900/20"></div>

                <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <h1 class="text-4xl font-bold text-white flex items-center gap-3 drop-shadow-lg">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            Canary Releases
                        </h1>
                        <p class="text-white/90 mt-3 max-w-2xl text-lg leading-relaxed">Gradually roll out deployments with traffic splitting, real-time health monitoring, and automatic promotion or rollback for {{ $project->name }}.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('projects.index') }}" class="group inline-flex items-center gap-2 px-6 py-3 bg-white/20 hover:bg-white/30 text-white rounded-xl font-semibold transition-all duration-300 backdrop-blur-sm border border-white/30 hover:border-white/50 shadow-lg hover:shadow-xl">
                            <svg class="w-5 h-5 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Back to Projects
                        </a>
                    </div>
                </div>
            </div>

            <div class="p-8 space-y-8">
                {{-- Active Canary Releases --}}
                @if($activeReleases->isNotEmpty())
                    <div class="space-y-6">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-cyan-500"></span>
                            </span>
                            Active Canary Releases
                        </h2>

                        @foreach($activeReleases as $release)
                            <div wire:key="canary-{{ $release->id }}" class="bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm border-2 border-cyan-500/40 rounded-2xl p-6 space-y-6">
                                {{-- Release Header --}}
                                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                    <div class="flex items-center gap-4">
                                        <span @class([
                                            'inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-extrabold uppercase tracking-wider border-2',
                                            $release->status->colorClass(),
                                        ])>
                                            {{ $release->status->label() }}
                                        </span>
                                        <div class="text-sm text-slate-600 dark:text-slate-300">
                                            <span class="font-bold">Canary:</span>
                                            <code class="ml-1 px-2 py-1 bg-slate-100/80 dark:bg-slate-900/80 rounded-lg text-cyan-600 dark:text-cyan-300 font-mono text-xs border border-cyan-500/30">{{ substr($release->canary_version ?? '', 0, 7) ?: 'N/A' }}</code>
                                            <span class="mx-2 text-slate-400">vs</span>
                                            <span class="font-bold">Stable:</span>
                                            <code class="ml-1 px-2 py-1 bg-slate-100/80 dark:bg-slate-900/80 rounded-lg text-emerald-600 dark:text-emerald-300 font-mono text-xs border border-emerald-500/30">{{ substr($release->stable_version ?? '', 0, 7) ?: 'N/A' }}</code>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 text-sm text-slate-500 dark:text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Started {{ $release->started_at?->diffForHumans() ?? 'Not started' }}
                                    </div>
                                </div>

                                {{-- Weight Progress Stepper --}}
                                @php
                                    $schedule = $release->weight_schedule ?? [];
                                    $currentStep = $release->current_step;
                                @endphp
                                @if(count($schedule) > 0)
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="font-bold text-slate-700 dark:text-slate-300">Traffic Weight Progress</span>
                                            <span class="text-cyan-600 dark:text-cyan-400 font-extrabold text-lg">{{ $release->current_weight }}%</span>
                                        </div>

                                        {{-- Progress Bar --}}
                                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-3 overflow-hidden">
                                            <div class="bg-gradient-to-r from-cyan-500 to-teal-500 h-3 rounded-full transition-all duration-1000 ease-out shadow-lg shadow-cyan-500/30" style="width: {{ $release->current_weight }}%"></div>
                                        </div>

                                        {{-- Step Indicators --}}
                                        <div class="flex items-center justify-between">
                                            @foreach($schedule as $stepIndex => $step)
                                                <div class="flex flex-col items-center gap-1">
                                                    <div @class([
                                                        'w-10 h-10 rounded-full flex items-center justify-center text-xs font-bold border-2 transition-all duration-300',
                                                        'bg-gradient-to-br from-cyan-500 to-teal-500 text-white border-cyan-400 shadow-lg shadow-cyan-500/30' => $stepIndex <= $currentStep,
                                                        'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 border-slate-300 dark:border-slate-600' => $stepIndex > $currentStep,
                                                    ])>
                                                        @if($stepIndex < $currentStep)
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                        @else
                                                            {{ $step['weight'] }}%
                                                        @endif
                                                    </div>
                                                    <span class="text-xs text-slate-500 dark:text-slate-400">
                                                        @if($step['duration_minutes'] > 0)
                                                            {{ $step['duration_minutes'] }}m
                                                        @else
                                                            Final
                                                        @endif
                                                    </span>
                                                </div>
                                                @if(!$loop->last)
                                                    <div @class([
                                                        'flex-1 h-0.5 mx-2',
                                                        'bg-gradient-to-r from-cyan-500 to-teal-500' => $stepIndex < $currentStep,
                                                        'bg-slate-200 dark:bg-slate-700' => $stepIndex >= $currentStep,
                                                    ])></div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Metrics Comparison --}}
                                @if($metricsComparison !== null)
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                        {{-- Stable Metrics --}}
                                        <div class="bg-emerald-500/5 border border-emerald-500/30 rounded-2xl p-5 space-y-4">
                                            <h4 class="text-sm font-bold uppercase tracking-wider text-emerald-500 dark:text-emerald-400 flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                Stable Version
                                            </h4>
                                            <div class="grid grid-cols-2 gap-3">
                                                <div>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400">Error Rate</p>
                                                    <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($metricsComparison['stable']['avg_error_rate'], 2) }}%</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400">Avg Response</p>
                                                    <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ $metricsComparison['stable']['avg_response_time'] }}ms</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400">P95 Response</p>
                                                    <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ $metricsComparison['stable']['p95_response_time'] }}ms</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400">P99 Response</p>
                                                    <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ $metricsComparison['stable']['p99_response_time'] }}ms</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400">Requests</p>
                                                    <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($metricsComparison['stable']['total_requests']) }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400">Errors</p>
                                                    <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($metricsComparison['stable']['total_errors']) }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Canary Metrics --}}
                                        <div class="bg-cyan-500/5 border border-cyan-500/30 rounded-2xl p-5 space-y-4">
                                            <h4 class="text-sm font-bold uppercase tracking-wider text-cyan-500 dark:text-cyan-400 flex items-center gap-2">
                                                <span class="relative flex h-2 w-2">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-cyan-500"></span>
                                                </span>
                                                Canary Version
                                            </h4>
                                            <div class="grid grid-cols-2 gap-3">
                                                <div>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400">Error Rate</p>
                                                    <p @class([
                                                        'text-lg font-bold',
                                                        'text-red-500' => $metricsComparison['canary']['avg_error_rate'] > $metricsComparison['stable']['avg_error_rate'] * 1.5,
                                                        'text-cyan-600 dark:text-cyan-400' => $metricsComparison['canary']['avg_error_rate'] <= $metricsComparison['stable']['avg_error_rate'] * 1.5,
                                                    ])>{{ number_format($metricsComparison['canary']['avg_error_rate'], 2) }}%</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400">Avg Response</p>
                                                    <p @class([
                                                        'text-lg font-bold',
                                                        'text-red-500' => $metricsComparison['canary']['avg_response_time'] > $metricsComparison['stable']['avg_response_time'] * 1.5,
                                                        'text-cyan-600 dark:text-cyan-400' => $metricsComparison['canary']['avg_response_time'] <= $metricsComparison['stable']['avg_response_time'] * 1.5,
                                                    ])>{{ $metricsComparison['canary']['avg_response_time'] }}ms</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400">P95 Response</p>
                                                    <p class="text-lg font-bold text-cyan-600 dark:text-cyan-400">{{ $metricsComparison['canary']['p95_response_time'] }}ms</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400">P99 Response</p>
                                                    <p class="text-lg font-bold text-cyan-600 dark:text-cyan-400">{{ $metricsComparison['canary']['p99_response_time'] }}ms</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400">Requests</p>
                                                    <p class="text-lg font-bold text-cyan-600 dark:text-cyan-400">{{ number_format($metricsComparison['canary']['total_requests']) }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400">Errors</p>
                                                    <p class="text-lg font-bold text-cyan-600 dark:text-cyan-400">{{ number_format($metricsComparison['canary']['total_errors']) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Action Buttons --}}
                                <div class="flex flex-wrap items-center gap-3 pt-2">
                                    @if($release->canAdvance())
                                        <button wire:click="advanceWeight({{ $release->id }})" wire:confirm="Are you sure you want to advance the canary weight to the next step?" class="group inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-cyan-600 to-teal-600 hover:from-cyan-500 hover:to-teal-500 text-white rounded-xl font-bold transition-all duration-300 shadow-lg shadow-cyan-500/30 hover:shadow-xl hover:shadow-cyan-500/40 hover:scale-105 text-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                            </svg>
                                            Advance Weight
                                        </button>
                                    @endif

                                    @if($release->isMonitoring())
                                        <button wire:click="promote({{ $release->id }})" wire:confirm="Are you sure you want to promote this canary release to 100% traffic?" class="group inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-500 hover:to-green-500 text-white rounded-xl font-bold transition-all duration-300 shadow-lg shadow-emerald-500/30 hover:shadow-xl hover:shadow-emerald-500/40 hover:scale-105 text-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Promote to Stable
                                        </button>

                                        <button wire:click="rollback({{ $release->id }})" wire:confirm="Are you sure you want to rollback this canary release? All traffic will be routed back to the stable version." class="group inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 text-white rounded-xl font-bold transition-all duration-300 shadow-lg shadow-red-500/30 hover:shadow-xl hover:shadow-red-500/40 hover:scale-105 text-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                            </svg>
                                            Rollback
                                        </button>
                                    @endif
                                </div>

                                {{-- Release Metadata --}}
                                <div class="flex flex-wrap items-center gap-3 text-xs">
                                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-100/50 dark:bg-slate-700/50 backdrop-blur-sm border border-slate-200 dark:border-slate-600/50 text-slate-600 dark:text-slate-300">
                                        Error Threshold: <span class="font-bold text-slate-900 dark:text-white">{{ $release->error_rate_threshold }}%</span>
                                    </span>
                                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-100/50 dark:bg-slate-700/50 backdrop-blur-sm border border-slate-200 dark:border-slate-600/50 text-slate-600 dark:text-slate-300">
                                        Response Threshold: <span class="font-bold text-slate-900 dark:text-white">{{ $release->response_time_threshold }}ms</span>
                                    </span>
                                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-100/50 dark:bg-slate-700/50 backdrop-blur-sm border border-slate-200 dark:border-slate-600/50 text-slate-600 dark:text-slate-300">
                                        Auto Promote: <span class="font-bold {{ $release->auto_promote ? 'text-emerald-500' : 'text-red-500' }}">{{ $release->auto_promote ? 'On' : 'Off' }}</span>
                                    </span>
                                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-100/50 dark:bg-slate-700/50 backdrop-blur-sm border border-slate-200 dark:border-slate-600/50 text-slate-600 dark:text-slate-300">
                                        Auto Rollback: <span class="font-bold {{ $release->auto_rollback ? 'text-emerald-500' : 'text-red-500' }}">{{ $release->auto_rollback ? 'On' : 'Off' }}</span>
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- No Active Releases --}}
                    <div class="text-center py-16 bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm rounded-2xl border border-slate-200 dark:border-slate-700/50">
                        <div class="relative inline-block">
                            <svg class="mx-auto h-20 w-20 text-slate-400 dark:text-slate-600 drop-shadow-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <div class="absolute inset-0 blur-2xl bg-cyan-500/20 rounded-full"></div>
                        </div>
                        <p class="mt-6 text-slate-700 dark:text-slate-300 text-xl font-semibold">No active canary releases</p>
                        <p class="text-sm text-slate-500 mt-3 max-w-md mx-auto">Canary releases allow you to gradually roll out new deployments to a percentage of traffic before fully promoting them.</p>
                    </div>
                @endif

                {{-- Recent Releases History --}}
                @if($recentReleases->isNotEmpty())
                    <div class="space-y-4">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <svg class="w-6 h-6 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Recent Releases
                        </h2>

                        <div class="bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm border border-slate-200 dark:border-slate-700/50 rounded-2xl overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-200 dark:border-slate-700/50">
                                            <th class="text-left px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</th>
                                            <th class="text-left px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Canary Version</th>
                                            <th class="text-left px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Stable Version</th>
                                            <th class="text-left px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Final Weight</th>
                                            <th class="text-left px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Started</th>
                                            <th class="text-left px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Completed</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
                                        @foreach($recentReleases as $release)
                                            <tr wire:key="recent-{{ $release->id }}" class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                                                <td class="px-6 py-4">
                                                    <span @class([
                                                        'inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider border',
                                                        $release->status->colorClass(),
                                                    ])>
                                                        {{ $release->status->label() }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <code class="px-2 py-1 bg-slate-100/80 dark:bg-slate-900/80 rounded text-cyan-600 dark:text-cyan-300 font-mono text-xs">{{ substr($release->canary_version ?? '', 0, 7) ?: 'N/A' }}</code>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <code class="px-2 py-1 bg-slate-100/80 dark:bg-slate-900/80 rounded text-emerald-600 dark:text-emerald-300 font-mono text-xs">{{ substr($release->stable_version ?? '', 0, 7) ?: 'N/A' }}</code>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="font-bold text-slate-900 dark:text-white">{{ $release->current_weight }}%</span>
                                                </td>
                                                <td class="px-6 py-4 text-slate-500 dark:text-slate-400">
                                                    {{ $release->started_at?->format('M d, H:i') ?? '-' }}
                                                </td>
                                                <td class="px-6 py-4 text-slate-500 dark:text-slate-400">
                                                    {{ $release->completed_at?->format('M d, H:i') ?? '-' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Initiate Canary Release Modal --}}
@if($showInitiateModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" wire:click.self="$set('showInitiateModal', false)">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-lg overflow-hidden">
            {{-- Modal Header --}}
            <div class="bg-gradient-to-r from-cyan-600 to-teal-600 px-6 py-5">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Initiate Canary Release
                </h3>
                <p class="text-white/80 text-sm mt-1">Configure traffic splitting parameters for the new release.</p>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 space-y-5">
                <div>
                    <label for="initialWeight" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Initial Traffic Weight (%)</label>
                    <input type="number" id="initialWeight" wire:model="initialWeight" min="1" max="100"
                           class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600/50 bg-white dark:bg-slate-900/50 text-sm text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500/50 transition-all" />
                </div>
                <div>
                    <label for="stepDuration" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Step Duration (minutes)</label>
                    <input type="number" id="stepDuration" wire:model="stepDuration" min="1" max="120"
                           class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600/50 bg-white dark:bg-slate-900/50 text-sm text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500/50 transition-all" />
                </div>
                <div>
                    <label for="errorThreshold" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Error Rate Threshold (%)</label>
                    <input type="number" id="errorThreshold" wire:model="errorThreshold" min="0.1" max="100" step="0.1"
                           class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600/50 bg-white dark:bg-slate-900/50 text-sm text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500/50 transition-all" />
                </div>
                <div>
                    <label for="responseTimeThreshold" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Response Time Threshold (ms)</label>
                    <input type="number" id="responseTimeThreshold" wire:model="responseTimeThreshold" min="100" max="60000"
                           class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600/50 bg-white dark:bg-slate-900/50 text-sm text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500/50 transition-all" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="autoPromote" class="w-5 h-5 rounded-md border-slate-300 dark:border-slate-600 text-cyan-600 focus:ring-cyan-500/50" />
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Auto Promote</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="autoRollback" class="w-5 h-5 rounded-md border-slate-300 dark:border-slate-600 text-cyan-600 focus:ring-cyan-500/50" />
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Auto Rollback</span>
                    </label>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                <button wire:click="$set('showInitiateModal', false)" class="px-5 py-2.5 rounded-xl font-semibold text-sm text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                    Cancel
                </button>
                <button class="px-5 py-2.5 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-cyan-600 to-teal-600 hover:from-cyan-500 hover:to-teal-500 shadow-lg shadow-cyan-500/30 hover:shadow-xl transition-all duration-300">
                    Start Canary Release
                </button>
            </div>
        </div>
    </div>
@endif

<style>
    @keyframes float {
        0%, 100% {
            transform: translate(0, 0) scale(1);
            opacity: 0.3;
        }
        50% {
            transform: translate(30px, -30px) scale(1.1);
            opacity: 0.4;
        }
    }

    @keyframes float-delayed {
        0%, 100% {
            transform: translate(0, 0) scale(1);
            opacity: 0.2;
        }
        50% {
            transform: translate(-40px, 40px) scale(1.15);
            opacity: 0.3;
        }
    }

    @keyframes float-slow {
        0%, 100% {
            transform: translate(0, 0) scale(1);
            opacity: 0.25;
        }
        50% {
            transform: translate(20px, 50px) scale(1.08);
            opacity: 0.35;
        }
    }

    .animate-float {
        animation: float 20s ease-in-out infinite;
    }

    .animate-float-delayed {
        animation: float-delayed 25s ease-in-out infinite;
    }

    .animate-float-slow {
        animation: float-slow 30s ease-in-out infinite;
    }

    .bg-grid-pattern {
        background-image:
            linear-gradient(to right, rgba(255, 255, 255, 0.1) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
        background-size: 24px 24px;
    }
</style>
</div>
