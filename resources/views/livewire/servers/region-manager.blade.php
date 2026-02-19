<div>
<div class="relative min-h-screen">
    {{-- Animated Background Orbs --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-gradient-to-br from-emerald-500/30 via-teal-500/30 to-cyan-500/30 rounded-full blur-3xl animate-float"></div>
        <div class="absolute bottom-1/4 right-1/3 w-80 h-80 bg-gradient-to-br from-teal-500/20 via-emerald-500/20 to-green-500/20 rounded-full blur-3xl animate-float-delayed"></div>
        <div class="absolute top-1/2 right-1/4 w-72 h-72 bg-gradient-to-br from-cyan-500/25 via-teal-500/25 to-emerald-500/25 rounded-full blur-3xl animate-float-slow"></div>
    </div>

    <div class="relative">
        {{-- Glassmorphism Card Container --}}
        <div class="bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-2xl shadow-slate-900/20 dark:shadow-slate-900/60 border border-slate-200 dark:border-slate-700/50 overflow-hidden">
            {{-- Premium Gradient Header --}}
            <div class="relative bg-gradient-to-br from-emerald-600 via-teal-600 to-cyan-600 px-8 py-10 overflow-hidden">
                {{-- Grid Pattern Overlay --}}
                <div class="absolute inset-0 bg-grid-pattern opacity-10"></div>
                <div class="absolute inset-0 bg-gradient-to-b from-transparent to-slate-900/20"></div>

                <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <h1 class="text-4xl font-bold text-white flex items-center gap-3 drop-shadow-lg">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Regions
                        </h1>
                        <p class="text-white/90 mt-3 max-w-2xl text-lg leading-relaxed">Manage geographic regions, monitor health scores, and control infrastructure distribution.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button wire:click="$set('showCreateModal', true)"
                                class="group inline-flex items-center gap-2 px-6 py-3 bg-white/20 hover:bg-white/30 text-white rounded-xl font-semibold transition-all duration-300 backdrop-blur-sm border border-white/30 hover:border-white/50 shadow-lg hover:shadow-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add Region
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

                {{-- Status Filter Tabs --}}
                <div class="flex flex-wrap items-center gap-2">
                    @php
                        $filters = [
                            'all' => ['label' => 'All', 'color' => 'slate'],
                            'active' => ['label' => 'Active', 'color' => 'emerald'],
                            'degraded' => ['label' => 'Degraded', 'color' => 'amber'],
                            'maintenance' => ['label' => 'Maintenance', 'color' => 'blue'],
                            'offline' => ['label' => 'Offline', 'color' => 'red'],
                        ];
                    @endphp
                    @foreach($filters as $value => $filter)
                        <button wire:click="$set('statusFilter', '{{ $value }}')"
                                class="px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-300 border
                                    {{ $statusFilter === $value
                                        ? 'bg-' . $filter['color'] . '-500/20 text-' . $filter['color'] . '-700 dark:text-' . $filter['color'] . '-300 border-' . $filter['color'] . '-500/40 shadow-sm'
                                        : 'bg-white/50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700' }}">
                            {{ $filter['label'] }}
                        </button>
                    @endforeach
                </div>

                {{-- Regions Grid --}}
                @if($this->regions->isNotEmpty())
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($this->regions as $region)
                            @php
                                $health = $this->getHealthScore($region->id);
                                $healthScore = $health['score'];
                                $healthColor = $healthScore > 80 ? 'emerald' : ($healthScore > 50 ? 'amber' : 'red');
                            @endphp
                            <div wire:key="region-{{ $region->id }}" class="bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm rounded-2xl border border-slate-200 dark:border-slate-700/50 overflow-hidden hover:shadow-lg transition-all duration-300 group">
                                {{-- Card Header --}}
                                <div class="p-5 border-b border-slate-100 dark:border-slate-700/50">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-emerald-900/40 dark:to-teal-900/40 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ $region->name }}</h3>
                                                <code class="text-xs px-2 py-0.5 rounded-lg font-mono bg-slate-100 dark:bg-slate-700 text-teal-600 dark:text-teal-300 border border-teal-500/20">{{ $region->code }}</code>
                                            </div>
                                        </div>
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

                                {{-- Card Body --}}
                                <div class="p-5 space-y-4">
                                    {{-- Continent & Server Count --}}
                                    <div class="space-y-2 text-sm">
                                        <div class="flex items-center justify-between">
                                            <span class="text-slate-500 dark:text-slate-400">Continent</span>
                                            <span class="font-medium text-slate-700 dark:text-slate-300">{{ $region->continent }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-slate-500 dark:text-slate-400">Servers</span>
                                            <span class="font-bold text-slate-900 dark:text-white">{{ $region->servers_count }}</span>
                                        </div>
                                    </div>

                                    {{-- Health Score Bar --}}
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-slate-500 dark:text-slate-400">Health Score</span>
                                            <span class="font-extrabold text-{{ $healthColor }}-600 dark:text-{{ $healthColor }}-400">{{ $healthScore }}%</span>
                                        </div>
                                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2 overflow-hidden">
                                            <div class="bg-gradient-to-r from-{{ $healthColor }}-500 to-{{ $healthColor }}-400 h-2 rounded-full transition-all duration-1000 ease-out"
                                                 style="width: {{ $healthScore }}%"></div>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                            <span>{{ $health['online_count'] }}/{{ $health['total_count'] }} servers online</span>
                                        </div>
                                    </div>

                                    {{-- Action Buttons --}}
                                    <div class="pt-3 border-t border-slate-100 dark:border-slate-700/50 flex items-center gap-2">
                                        <button wire:click="editRegion({{ $region->id }})"
                                                wire:loading.attr="disabled"
                                                wire:loading.class="opacity-50 cursor-not-allowed"
                                                wire:target="editRegion({{ $region->id }})"
                                                class="flex-1 px-3 py-2 text-xs font-medium rounded-lg border transition-all
                                                    border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300
                                                    hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center justify-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </button>
                                        <button wire:click="deleteRegion({{ $region->id }})"
                                                wire:confirm="Are you sure you want to delete this region? This action cannot be undone."
                                                wire:loading.attr="disabled"
                                                wire:loading.class="opacity-50 cursor-not-allowed"
                                                wire:target="deleteRegion({{ $region->id }})"
                                                class="flex-1 px-3 py-2 text-xs font-medium rounded-lg border transition-all
                                                    border-red-200 dark:border-red-800/50 text-red-600 dark:text-red-400
                                                    hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center justify-center gap-1.5">
                                            <svg wire:loading.remove wire:target="deleteRegion({{ $region->id }})" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <svg wire:loading wire:target="deleteRegion({{ $region->id }})" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-16 bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm rounded-2xl border border-slate-200 dark:border-slate-700/50">
                        <div class="relative inline-block">
                            <svg class="mx-auto h-20 w-20 text-slate-400 dark:text-slate-600 drop-shadow-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <div class="absolute inset-0 blur-2xl bg-emerald-500/20 rounded-full"></div>
                        </div>
                        <p class="mt-6 text-slate-700 dark:text-slate-300 text-xl font-semibold">No regions found</p>
                        <p class="text-sm text-slate-500 mt-3 max-w-md mx-auto">
                            @if($statusFilter !== 'all')
                                No regions match the selected filter. Try clearing the filter or adding new regions.
                            @else
                                Get started by adding your first region to organize and manage your infrastructure.
                            @endif
                        </p>
                        @if($statusFilter === 'all')
                            <button wire:click="$set('showCreateModal', true)"
                                    class="mt-6 px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl font-semibold transition-all transform hover:scale-105 shadow-lg inline-flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Add First Region
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Create Region Modal --}}
@if($showCreateModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" wire:click.self="$set('showCreateModal', false)">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-lg overflow-hidden">
            {{-- Modal Header --}}
            <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-5">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add New Region
                </h3>
                <p class="text-white/80 text-sm mt-1">Define a new geographic region for your infrastructure.</p>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="create-name" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Name</label>
                        <input type="text" id="create-name" wire:model="name" placeholder="US East"
                               class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600/50 bg-white dark:bg-slate-900/50 text-sm text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 transition-all" />
                        @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="create-code" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Code</label>
                        <input type="text" id="create-code" wire:model="code" placeholder="us-east-1"
                               class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600/50 bg-white dark:bg-slate-900/50 text-sm text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 transition-all font-mono" />
                        @error('code') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="create-continent" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Continent</label>
                    <select id="create-continent" wire:model="continent"
                            class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600/50 bg-white dark:bg-slate-900/50 text-sm text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 transition-all">
                        <option value="">Select continent...</option>
                        @foreach(['Africa', 'Asia', 'Europe', 'Middle East', 'North America', 'Oceania', 'South America'] as $cont)
                            <option value="{{ $cont }}">{{ $cont }}</option>
                        @endforeach
                    </select>
                    @error('continent') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="create-latitude" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Latitude</label>
                        <input type="number" id="create-latitude" wire:model="latitude" placeholder="40.7128" step="any" min="-90" max="90"
                               class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600/50 bg-white dark:bg-slate-900/50 text-sm text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 transition-all" />
                        @error('latitude') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="create-longitude" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Longitude</label>
                        <input type="number" id="create-longitude" wire:model="longitude" placeholder="-74.0060" step="any" min="-180" max="180"
                               class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600/50 bg-white dark:bg-slate-900/50 text-sm text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 transition-all" />
                        @error('longitude') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="create-dns-zone" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">DNS Zone</label>
                    <input type="text" id="create-dns-zone" wire:model="dns_zone" placeholder="us-east.example.com"
                           class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600/50 bg-white dark:bg-slate-900/50 text-sm text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 transition-all font-mono" />
                    @error('dns_zone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                <button wire:click="$set('showCreateModal', false)"
                        class="px-5 py-2.5 rounded-xl font-semibold text-sm text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                    Cancel
                </button>
                <button wire:click="createRegion"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        wire:target="createRegion"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-lg shadow-emerald-500/30 hover:shadow-xl transition-all duration-300">
                    <svg wire:loading.remove wire:target="createRegion" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <svg wire:loading wire:target="createRegion" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="createRegion">Create Region</span>
                    <span wire:loading wire:target="createRegion">Creating...</span>
                </button>
            </div>
        </div>
    </div>
@endif

{{-- Edit Region Modal --}}
@if($showEditModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" wire:click.self="$set('showEditModal', false)">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-lg overflow-hidden">
            {{-- Modal Header --}}
            <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-5">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit Region
                </h3>
                <p class="text-white/80 text-sm mt-1">Update region configuration and geographic details.</p>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edit-name" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Name</label>
                        <input type="text" id="edit-name" wire:model="name" placeholder="US East"
                               class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600/50 bg-white dark:bg-slate-900/50 text-sm text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 transition-all" />
                        @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="edit-code" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Code</label>
                        <input type="text" id="edit-code" wire:model="code" placeholder="us-east-1"
                               class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600/50 bg-white dark:bg-slate-900/50 text-sm text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 transition-all font-mono" />
                        @error('code') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="edit-continent" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Continent</label>
                    <select id="edit-continent" wire:model="continent"
                            class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600/50 bg-white dark:bg-slate-900/50 text-sm text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 transition-all">
                        <option value="">Select continent...</option>
                        @foreach(['Africa', 'Asia', 'Europe', 'Middle East', 'North America', 'Oceania', 'South America'] as $cont)
                            <option value="{{ $cont }}">{{ $cont }}</option>
                        @endforeach
                    </select>
                    @error('continent') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edit-latitude" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Latitude</label>
                        <input type="number" id="edit-latitude" wire:model="latitude" placeholder="40.7128" step="any" min="-90" max="90"
                               class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600/50 bg-white dark:bg-slate-900/50 text-sm text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 transition-all" />
                        @error('latitude') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="edit-longitude" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Longitude</label>
                        <input type="number" id="edit-longitude" wire:model="longitude" placeholder="-74.0060" step="any" min="-180" max="180"
                               class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600/50 bg-white dark:bg-slate-900/50 text-sm text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 transition-all" />
                        @error('longitude') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="edit-dns-zone" class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">DNS Zone</label>
                    <input type="text" id="edit-dns-zone" wire:model="dns_zone" placeholder="us-east.example.com"
                           class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600/50 bg-white dark:bg-slate-900/50 text-sm text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 transition-all font-mono" />
                    @error('dns_zone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                <button wire:click="$set('showEditModal', false)"
                        class="px-5 py-2.5 rounded-xl font-semibold text-sm text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                    Cancel
                </button>
                <button wire:click="updateRegion"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        wire:target="updateRegion"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-lg shadow-emerald-500/30 hover:shadow-xl transition-all duration-300">
                    <svg wire:loading.remove wire:target="updateRegion" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg wire:loading wire:target="updateRegion" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="updateRegion">Update Region</span>
                    <span wire:loading wire:target="updateRegion">Updating...</span>
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
