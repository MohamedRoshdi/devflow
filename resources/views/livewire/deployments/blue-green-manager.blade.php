<div>
    <!-- Blue-Green Deployment Section Header -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg dark:shadow-gray-900/50 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-600 to-green-500 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-white flex items-center">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        Blue-Green Deployment
                    </h2>
                    <p class="text-white/80 text-sm mt-2">Zero-downtime deployment with instant traffic switching and rollback</p>
                </div>
                <div class="flex items-center gap-3">
                    @if($isEnabled)
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-white/20 text-white backdrop-blur-sm">
                            <span class="w-2 h-2 rounded-full bg-green-300 mr-2 animate-pulse"></span>
                            Active
                        </span>
                        <button wire:click="disable"
                                wire:confirm="Are you sure you want to disable blue-green deployment? This will remove both environments."
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                wire:target="disable"
                                class="px-4 py-2 bg-white/20 hover:bg-red-500/80 text-white rounded-lg font-medium transition text-sm">
                            Disable
                        </button>
                    @else
                        <button wire:click="initialize"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                wire:target="initialize"
                                class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg font-medium transition flex items-center gap-2 text-sm">
                            <svg wire:loading.remove wire:target="initialize" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <svg wire:loading wire:target="initialize" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Enable Blue-Green
                        </button>
                    @endif
                </div>
            </div>
        </div>

        @if($isEnabled)
            <div class="p-6">
                <!-- Status Message -->
                @if($statusMessage)
                    <div class="mb-6 p-4 rounded-xl border
                        @if($statusType === 'success') bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200
                        @elseif($statusType === 'error') bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200
                        @else bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200
                        @endif">
                        <div class="flex items-center gap-3">
                            @if($statusType === 'success')
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @elseif($statusType === 'error')
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            @endif
                            <p class="text-sm font-medium">{{ $statusMessage }}</p>
                        </div>
                    </div>
                @endif

                <!-- Traffic Flow Indicator -->
                <div class="mb-6 flex items-center justify-center gap-4">
                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                        </svg>
                        <span>Traffic</span>
                    </div>
                    <svg class="w-6 h-6 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                    <div class="flex items-center gap-2">
                        @if($environmentStatus['active'] === 'blue')
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 border border-blue-300 dark:border-blue-700">
                                <span class="w-2 h-2 rounded-full bg-blue-500 mr-2 animate-pulse"></span>
                                Blue Environment
                            </span>
                        @elseif($environmentStatus['active'] === 'green')
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 border border-green-300 dark:border-green-700">
                                <span class="w-2 h-2 rounded-full bg-green-500 mr-2 animate-pulse"></span>
                                Green Environment
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                                No Active Environment
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Environment Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach(['blue', 'green'] as $envName)
                        @php
                            $env = $environmentStatus[$envName];
                            $isActive = $environmentStatus['active'] === $envName;
                            $colorClass = $envName === 'blue' ? 'blue' : 'green';
                        @endphp

                        <div class="relative rounded-2xl border-2 transition-all duration-300
                            @if($isActive)
                                border-{{ $colorClass }}-400 dark:border-{{ $colorClass }}-500 shadow-lg shadow-{{ $colorClass }}-100 dark:shadow-{{ $colorClass }}-900/20
                            @else
                                border-gray-200 dark:border-gray-700
                            @endif
                            bg-white dark:bg-gray-800 overflow-hidden">

                            <!-- LIVE Badge -->
                            @if($isActive)
                                <div class="absolute top-3 right-3 z-10">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-{{ $colorClass }}-500 text-white shadow-lg animate-pulse">
                                        LIVE
                                    </span>
                                </div>
                            @endif

                            <!-- Card Header -->
                            <div class="p-5 border-b border-gray-100 dark:border-gray-700
                                @if($isActive)
                                    bg-gradient-to-r from-{{ $colorClass }}-50 to-{{ $colorClass }}-100/50 dark:from-{{ $colorClass }}-900/20 dark:to-{{ $colorClass }}-800/10
                                @endif">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center
                                        @if($envName === 'blue')
                                            bg-blue-100 dark:bg-blue-900/40
                                        @else
                                            bg-green-100 dark:bg-green-900/40
                                        @endif">
                                        <div class="w-4 h-4 rounded-full
                                            @if($envName === 'blue')
                                                bg-blue-500
                                            @else
                                                bg-green-500
                                            @endif">
                                        </div>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white capitalize">{{ $envName }} Environment</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            @if($env)
                                                {{ $isActive ? 'Receiving traffic' : 'Standby' }}
                                            @else
                                                Not initialized
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Body -->
                            <div class="p-5 space-y-4">
                                @if($env)
                                    <!-- Status -->
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Status</span>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                            @if($env->status === 'active') bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300
                                            @elseif($env->status === 'deploying') bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300
                                            @elseif($env->status === 'failed') bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300
                                            @else bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300
                                            @endif">
                                            @if($env->status === 'deploying')
                                                <svg class="w-3 h-3 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            @endif
                                            {{ ucfirst($env->status) }}
                                        </span>
                                    </div>

                                    <!-- Commit Hash -->
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Commit</span>
                                        @if($env->commit_hash)
                                            <code class="text-xs px-2 py-1 rounded-lg font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                                {{ substr($env->commit_hash, 0, 7) }}
                                            </code>
                                        @else
                                            <span class="text-xs text-gray-400 dark:text-gray-500 italic">No deployment</span>
                                        @endif
                                    </div>

                                    <!-- Port -->
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Port</span>
                                        <span class="text-sm font-mono text-gray-700 dark:text-gray-300">
                                            {{ $env->port ?? 'Unassigned' }}
                                        </span>
                                    </div>

                                    <!-- Health Status -->
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Health</span>
                                        <span class="inline-flex items-center gap-1.5 text-sm font-medium
                                            @if($env->health_status === 'healthy') text-green-600 dark:text-green-400
                                            @elseif($env->health_status === 'unhealthy') text-red-600 dark:text-red-400
                                            @else text-gray-400 dark:text-gray-500
                                            @endif">
                                            @if($env->health_status === 'healthy')
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            @elseif($env->health_status === 'unhealthy')
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            @endif
                                            {{ ucfirst($env->health_status) }}
                                        </span>
                                    </div>

                                    <!-- Last Health Check -->
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Last Check</span>
                                        <span class="text-xs text-gray-400 dark:text-gray-500">
                                            {{ $env->last_health_check_at ? $env->last_health_check_at->diffForHumans() : 'Never' }}
                                        </span>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="pt-3 border-t border-gray-100 dark:border-gray-700 flex flex-wrap gap-2">
                                        <button wire:click="checkHealth('{{ $envName }}')"
                                                wire:loading.attr="disabled"
                                                wire:loading.class="opacity-50 cursor-not-allowed"
                                                wire:target="checkHealth('{{ $envName }}')"
                                                class="flex-1 px-3 py-2 text-xs font-medium rounded-lg border transition-all
                                                    border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300
                                                    hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center justify-center gap-1.5">
                                            <svg wire:loading.remove wire:target="checkHealth('{{ $envName }}')" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                            </svg>
                                            <svg wire:loading wire:target="checkHealth('{{ $envName }}')" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Health Check
                                        </button>
                                    </div>
                                @else
                                    <div class="text-center py-6">
                                        <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                        <p class="mt-2 text-sm text-gray-400 dark:text-gray-500">Not initialized</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Global Actions -->
                <div class="mt-6 flex flex-col sm:flex-row items-center gap-3">
                    <button wire:click="switchTraffic"
                            wire:confirm="Are you sure you want to switch traffic to the standby environment?"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-75 cursor-not-allowed"
                            wire:target="switchTraffic"
                            class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-blue-600 to-green-500 hover:from-blue-700 hover:to-green-600 text-white rounded-xl font-semibold transition-all transform hover:scale-105 shadow-lg flex items-center justify-center gap-2">
                        <svg wire:loading.remove wire:target="switchTraffic" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        <svg wire:loading wire:target="switchTraffic" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="switchTraffic">Switch Traffic</span>
                        <span wire:loading wire:target="switchTraffic">Switching...</span>
                    </button>

                    <button wire:click="rollback"
                            wire:confirm="Are you sure you want to rollback? This will switch traffic back to the previous environment."
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-75 cursor-not-allowed"
                            wire:target="rollback"
                            class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white rounded-xl font-semibold transition-all transform hover:scale-105 shadow-lg flex items-center justify-center gap-2">
                        <svg wire:loading.remove wire:target="rollback" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                        <svg wire:loading wire:target="rollback" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="rollback">Rollback</span>
                        <span wire:loading wire:target="rollback">Rolling Back...</span>
                    </button>
                </div>

                <!-- Info Box -->
                <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-sm text-blue-800 dark:text-blue-200">
                            <p class="font-medium">About Blue-Green Deployments</p>
                            <p class="mt-1 text-blue-700 dark:text-blue-300">Blue-green deployment maintains two identical environments. New code is deployed to the standby environment, and traffic is switched once the health check passes. Rollback is instant by switching traffic back.</p>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- Not Enabled State -->
            <div class="p-6">
                <div class="text-center py-12">
                    <div class="mx-auto w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-100 to-green-100 dark:from-blue-900/30 dark:to-green-900/30 flex items-center justify-center mb-4">
                        <svg class="w-10 h-10 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Blue-Green Deployment Not Enabled</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                        Enable blue-green deployment to achieve zero-downtime deployments with instant rollback capability. Two identical environments run in parallel with traffic switching between them.
                    </p>
                    <button wire:click="initialize"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            wire:target="initialize"
                            class="mt-6 px-6 py-3 bg-gradient-to-r from-blue-600 to-green-500 hover:from-blue-700 hover:to-green-600 text-white rounded-xl font-semibold transition-all transform hover:scale-105 shadow-lg inline-flex items-center gap-2">
                        <svg wire:loading.remove wire:target="initialize" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <svg wire:loading wire:target="initialize" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="initialize">Enable Blue-Green Deployment</span>
                        <span wire:loading wire:target="initialize">Initializing...</span>
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
