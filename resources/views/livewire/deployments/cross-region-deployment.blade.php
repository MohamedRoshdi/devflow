<div>
<div class="relative min-h-screen">
    {{-- Animated Background Orbs --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-gradient-to-br from-purple-500/30 via-indigo-500/30 to-violet-500/30 rounded-full blur-3xl animate-float"></div>
        <div class="absolute bottom-1/4 right-1/3 w-80 h-80 bg-gradient-to-br from-indigo-500/20 via-purple-500/20 to-fuchsia-500/20 rounded-full blur-3xl animate-float-delayed"></div>
        <div class="absolute top-1/2 right-1/4 w-72 h-72 bg-gradient-to-br from-violet-500/25 via-indigo-500/25 to-purple-500/25 rounded-full blur-3xl animate-float-slow"></div>
    </div>

    <div class="relative">
        {{-- Glassmorphism Card Container --}}
        <div class="bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-2xl shadow-slate-900/20 dark:shadow-slate-900/60 border border-slate-200 dark:border-slate-700/50 overflow-hidden">
            {{-- Premium Gradient Header --}}
            <div class="relative bg-gradient-to-br from-purple-600 via-indigo-600 to-violet-600 px-8 py-10 overflow-hidden">
                {{-- Grid Pattern Overlay --}}
                <div class="absolute inset-0 bg-grid-pattern opacity-10"></div>
                <div class="absolute inset-0 bg-gradient-to-b from-transparent to-slate-900/20"></div>

                <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <h1 class="text-4xl font-bold text-white flex items-center gap-3 drop-shadow-lg">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Cross-Region Deployment
                        </h1>
                        <p class="text-white/90 mt-3 max-w-2xl text-lg leading-relaxed">Deploy {{ $project->name }} across multiple geographic regions with sequential or parallel strategies.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button wire:click="$set('showDeployModal', true)"
                                class="group inline-flex items-center gap-2 px-6 py-3 bg-white/20 hover:bg-white/30 text-white rounded-xl font-semibold transition-all duration-300 backdrop-blur-sm border border-white/30 hover:border-white/50 shadow-lg hover:shadow-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            New Deployment
                        </button>
                    </div>
                </div>
            </div>

            <div class="p-8 space-y-8">
                {{-- Flash Messages --}}
                @if(session('message'))
                    <div class="p-4 rounded-xl border bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm font-medium">{{ session('message') }}</p>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="p-4 rounded-xl border bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm font-medium">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif

                {{-- Active Deployment Progress --}}
                @if($activeDeploymentId)
                    @php
                        $progress = $this->getDeploymentProgress($activeDeploymentId);
                    @endphp
                    <div wire:poll.3s class="space-y-4">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-purple-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-purple-500"></span>
                            </span>
                            Active Deployment
                        </h2>

                        <div class="bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm border-2 border-purple-500/40 rounded-2xl p-6 space-y-5">
                            {{-- Progress Summary --}}
                            <div class="flex flex-wrap items-center gap-4">
                                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-extrabold uppercase tracking-wider border-2
                                    @if($progress['status'] === 'completed') bg-emerald-500/20 text-emerald-400 border-emerald-500/30
                                    @elseif($progress['status'] === 'failed') bg-red-500/20 text-red-400 border-red-500/30
                                    @elseif($progress['status'] === 'running') bg-purple-500/20 text-purple-400 border-purple-500/30
                                    @else bg-slate-500/20 text-slate-400 border-slate-500/30
                                    @endif">
                                    @if($progress['status'] === 'running')
                                        <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    @endif
                                    {{ ucfirst($progress['status']) }}
                                </span>
                                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-100/50 dark:bg-slate-700/50 backdrop-blur-sm border border-slate-200 dark:border-slate-600/50 text-slate-600 dark:text-slate-300 text-xs">
                                    Strategy: <span class="font-bold text-slate-900 dark:text-white capitalize">{{ $progress['strategy'] }}</span>
                                </span>
                            </div>

                            {{-- Progress Bar --}}
                            @if($progress['total_regions'] > 0)
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="font-bold text-slate-700 dark:text-slate-300">Region Progress</span>
                                        <span class="text-purple-600 dark:text-purple-400 font-extrabold text-lg">{{ $progress['completed'] }}/{{ $progress['total_regions'] }}</span>
                                    </div>
                                    <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-3 overflow-hidden">
                                        <div class="bg-gradient-to-r from-purple-500 to-indigo-500 h-3 rounded-full transition-all duration-1000 ease-out shadow-lg shadow-purple-500/30"
                                             style="width: {{ $progress['total_regions'] > 0 ? round(($progress['completed'] / $progress['total_regions']) * 100) : 0 }}%"></div>
                                    </div>
                                </div>

                                {{-- Region Status Grid --}}
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl p-3 text-center">
                                        <p class="text-2xl font-extrabold text-emerald-500">{{ $progress['completed'] }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Completed</p>
                                    </div>
                                    <div class="bg-purple-500/10 border border-purple-500/30 rounded-xl p-3 text-center">
                                        <p class="text-2xl font-extrabold text-purple-500">{{ $progress['running'] }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Running</p>
                                    </div>
                                    <div class="bg-slate-500/10 border border-slate-500/30 rounded-xl p-3 text-center">
                                        <p class="text-2xl font-extrabold text-slate-500">{{ $progress['pending'] }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Pending</p>
                                    </div>
                                    <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-3 text-center">
                                        <p class="text-2xl font-extrabold text-red-500">{{ $progress['failed'] }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Failed</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Available Regions --}}
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <svg class="w-6 h-6 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Available Regions
                        </h2>
                        <div class="flex items-center gap-2">
                            <button wire:click="selectAllRegions"
                                    class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                                Select All
                            </button>
                            <button wire:click="deselectAllRegions"
                                    class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                                Deselect All
                            </button>
                        </div>
                    </div>

                    @if($this->regions->isNotEmpty())
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($this->regions as $region)
                                @php
                                    $isSelected = in_array($region->id, $selectedRegions);
                                @endphp
                                <div wire:key="region-{{ $region->id }}"
                                     wire:click="toggleRegion({{ $region->id }})"
                                     class="relative cursor-pointer rounded-2xl border-2 transition-all duration-300 p-5 space-y-3
                                         {{ $isSelected
                                             ? 'border-purple-400 dark:border-purple-500 shadow-lg shadow-purple-100 dark:shadow-purple-900/20 bg-purple-50/50 dark:bg-purple-900/10'
                                             : 'border-slate-200 dark:border-slate-700/50 hover:border-slate-300 dark:hover:border-slate-600 bg-white/30 dark:bg-slate-800/30' }} backdrop-blur-sm">

                                    {{-- Selection Checkbox --}}
                                    <div class="absolute top-4 right-4">
                                        <div class="w-6 h-6 rounded-lg border-2 flex items-center justify-center transition-all
                                            {{ $isSelected
                                                ? 'bg-gradient-to-br from-purple-500 to-indigo-500 border-purple-400'
                                                : 'border-slate-300 dark:border-slate-600' }}">
                                            @if($isSelected)
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Region Name & Code --}}
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-100 to-indigo-100 dark:from-purple-900/40 dark:to-indigo-900/40 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ $region->name }}</h3>
                                            <code class="text-xs px-2 py-0.5 rounded-lg font-mono bg-slate-100 dark:bg-slate-700 text-purple-600 dark:text-purple-300 border border-purple-500/20">{{ $region->code }}</code>
                                        </div>
                                    </div>

                                    {{-- Region Details --}}
                                    <div class="space-y-2 text-sm">
                                        <div class="flex items-center justify-between">
                                            <span class="text-slate-500 dark:text-slate-400">Continent</span>
                                            <span class="font-medium text-slate-700 dark:text-slate-300">{{ $region->continent }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-slate-500 dark:text-slate-400">Servers</span>
                                            <span class="font-bold text-slate-900 dark:text-white">{{ $region->servers_count }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-slate-500 dark:text-slate-400">Status</span>
                                            <span @class([
                                                'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border',
                                                $region->status->colorClass(),
                                            ])>
                                                @if($region->status === \App\Enums\RegionStatus::Active)
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-1.5 animate-pulse"></span>
                                                @endif
                                                {{ $region->status->label() }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-16 bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm rounded-2xl border border-slate-200 dark:border-slate-700/50">
                            <div class="relative inline-block">
                                <svg class="mx-auto h-20 w-20 text-slate-400 dark:text-slate-600 drop-shadow-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="absolute inset-0 blur-2xl bg-purple-500/20 rounded-full"></div>
                            </div>
                            <p class="mt-6 text-slate-700 dark:text-slate-300 text-xl font-semibold">No regions available</p>
                            <p class="text-sm text-slate-500 mt-3 max-w-md mx-auto">Add regions in the Region Manager to enable cross-region deployments.</p>
                        </div>
                    @endif
                </div>

                {{-- Deployment History --}}
                @if($this->deploymentHistory->isNotEmpty())
                    <div class="space-y-4">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <svg class="w-6 h-6 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Deployment History
                        </h2>

                        <div class="bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm border border-slate-200 dark:border-slate-700/50 rounded-2xl overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-200 dark:border-slate-700/50">
                                            <th class="text-left px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Date</th>
                                            <th class="text-left px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Strategy</th>
                                            <th class="text-left px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Regions</th>
                                            <th class="text-left px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Initiator</th>
                                            <th class="text-left px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Status</th>
                                            <th class="text-left px-6 py-4 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
                                        @foreach($this->deploymentHistory as $deployment)
                                            <tr wire:key="history-{{ $deployment->id }}" class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                                                <td class="px-6 py-4 text-slate-500 dark:text-slate-400">
                                                    {{ $deployment->created_at->format('M d, H:i') }}
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider bg-slate-100/50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600/50 text-slate-600 dark:text-slate-300">
                                                        @if($deployment->strategy === 'parallel')
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                                            </svg>
                                                        @else
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                            </svg>
                                                        @endif
                                                        {{ ucfirst($deployment->strategy) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="font-bold text-slate-900 dark:text-white">{{ is_array($deployment->region_order) ? count($deployment->region_order) : 0 }}</span>
                                                    <span class="text-slate-500 dark:text-slate-400"> regions</span>
                                                </td>
                                                <td class="px-6 py-4 text-slate-700 dark:text-slate-300">
                                                    {{ $deployment->initiator?->name ?? 'System' }}
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                                        @if($deployment->status === 'completed') bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300
                                                        @elseif($deployment->status === 'failed') bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300
                                                        @elseif($deployment->status === 'running') bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300
                                                        @elseif($deployment->status === 'rolled_back') bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300
                                                        @else bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300
                                                        @endif">
                                                        {{ ucfirst(str_replace('_', ' ', $deployment->status)) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4">
                                                    @if(in_array($deployment->status, ['completed', 'running']))
                                                        <button wire:click="rollbackDeployment({{ $deployment->id }})"
                                                                wire:confirm="Are you sure you want to rollback this deployment? This will revert all affected regions."
                                                                wire:loading.attr="disabled"
                                                                wire:loading.class="opacity-50 cursor-not-allowed"
                                                                wire:target="rollbackDeployment({{ $deployment->id }})"
                                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white shadow-lg shadow-amber-500/20 hover:shadow-xl transition-all duration-300">
                                                            <svg wire:loading.remove wire:target="rollbackDeployment({{ $deployment->id }})" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                                            </svg>
                                                            <svg wire:loading wire:target="rollbackDeployment({{ $deployment->id }})" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                            </svg>
                                                            Rollback
                                                        </button>
                                                    @else
                                                        <span class="text-xs text-slate-400 dark:text-slate-500 italic">No actions</span>
                                                    @endif
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

{{-- Deploy Modal --}}
@if($showDeployModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" wire:click.self="$set('showDeployModal', false)">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-lg overflow-hidden">
            {{-- Modal Header --}}
            <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-5">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Initiate Cross-Region Deployment
                </h3>
                <p class="text-white/80 text-sm mt-1">Configure deployment strategy and confirm region selection.</p>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 space-y-5">
                {{-- Strategy Selection --}}
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-3">Deployment Strategy</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="relative cursor-pointer">
                            <input type="radio" wire:model="strategy" value="sequential" class="peer sr-only" />
                            <div class="p-4 rounded-xl border-2 transition-all peer-checked:border-purple-500 peer-checked:bg-purple-50 dark:peer-checked:bg-purple-900/20 border-slate-200 dark:border-slate-600 hover:border-slate-300 dark:hover:border-slate-500">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                    <span class="font-bold text-sm text-slate-900 dark:text-white">Sequential</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Deploy to regions one at a time, validating each before proceeding.</p>
                            </div>
                        </label>
                        <label class="relative cursor-pointer">
                            <input type="radio" wire:model="strategy" value="parallel" class="peer sr-only" />
                            <div class="p-4 rounded-xl border-2 transition-all peer-checked:border-purple-500 peer-checked:bg-purple-50 dark:peer-checked:bg-purple-900/20 border-slate-200 dark:border-slate-600 hover:border-slate-300 dark:hover:border-slate-500">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                    <span class="font-bold text-sm text-slate-900 dark:text-white">Parallel</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Deploy to all selected regions simultaneously for faster rollout.</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Selected Regions Summary --}}
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Selected Regions</label>
                    @if(count($selectedRegions) > 0)
                        <div class="flex flex-wrap gap-2">
                            @foreach($this->regions->filter(fn($r) => in_array($r->id, $selectedRegions)) as $region)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 border border-purple-300 dark:border-purple-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                    {{ $region->name }}
                                    <code class="ml-1 text-purple-500 dark:text-purple-400">({{ $region->code }})</code>
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-slate-400 dark:text-slate-500 italic">No regions selected. Select regions from the grid above before deploying.</p>
                    @endif
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                <button wire:click="$set('showDeployModal', false)"
                        class="px-5 py-2.5 rounded-xl font-semibold text-sm text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                    Cancel
                </button>
                <button wire:click="initiateDeploy"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        wire:target="initiateDeploy"
                        @if(count($selectedRegions) === 0) disabled @endif
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 shadow-lg shadow-purple-500/30 hover:shadow-xl transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg wire:loading.remove wire:target="initiateDeploy" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <svg wire:loading wire:target="initiateDeploy" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="initiateDeploy">Deploy to {{ count($selectedRegions) }} Region{{ count($selectedRegions) !== 1 ? 's' : '' }}</span>
                    <span wire:loading wire:target="initiateDeploy">Deploying...</span>
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
