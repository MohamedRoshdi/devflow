<div>
    <!-- Rollback Section Header -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg dark:shadow-gray-900/50 overflow-hidden">
        <div class="bg-gradient-to-r from-amber-500 to-orange-500 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-white flex items-center">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                        Rollback Points
                    </h2>
                    <p class="text-white/80 text-sm mt-2">Restore your project to a previous successful deployment</p>
                </div>
                <button wire:click="loadRollbackPoints"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        wire:target="loadRollbackPoints"
                        class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg font-medium transition flex items-center gap-2">
                    <svg wire:loading.remove wire:target="loadRollbackPoints" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <svg wire:loading wire:target="loadRollbackPoints" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Refresh
                </button>
            </div>
        </div>

        <div class="p-6">
            @if(count($rollbackPoints) > 0)
                <div class="space-y-3">
                    @foreach($rollbackPoints as $index => $point)
                        @php
                            // Determine if this is a merged/deployed commit (current) or available for rollback
                            $isCurrent = $index === 0;
                            $isDeployed = !$point['can_rollback'] || $isCurrent;
                        @endphp
                        <div class="group p-4 rounded-xl border-2 transition-all duration-200
                            @if($isCurrent)
                                {{-- Current deployment - green background --}}
                                bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-green-300 dark:border-green-700
                            @elseif($point['can_rollback'])
                                {{-- Available for rollback - amber/orange background on hover --}}
                                bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-amber-400 dark:hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:shadow-md cursor-pointer
                            @else
                                {{-- Old deployed commits - subtle blue/gray background --}}
                                bg-gradient-to-r from-blue-50/50 to-indigo-50/50 dark:from-blue-900/10 dark:to-indigo-900/10 border-blue-200 dark:border-blue-800/50 opacity-70
                            @endif"
                            @if($point['can_rollback']) wire:click="selectForRollback({{ $point['id'] }})" @endif>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <!-- Index/Current Badge -->
                                    <div class="flex-shrink-0">
                                        @if($isCurrent)
                                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-green-500 dark:bg-green-600 text-white font-bold text-sm shadow-lg">
                                                NOW
                                            </span>
                                        @elseif($point['can_rollback'])
                                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-400 font-mono text-sm border-2 border-amber-300 dark:border-amber-700">
                                                -{{ $index }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-mono text-sm">
                                                -{{ $index }}
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Commit Info -->
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <code class="text-sm px-2.5 py-1 rounded-lg font-mono font-bold
                                                @if($isCurrent)
                                                    bg-green-600 text-white
                                                @elseif($point['can_rollback'])
                                                    bg-slate-800 text-white
                                                @else
                                                    bg-blue-600/80 text-white
                                                @endif">
                                                {{ $point['commit_hash'] ? substr($point['commit_hash'], 0, 7) : 'N/A' }}
                                            </code>
                                            @if($isCurrent)
                                                <span class="text-xs px-2 py-0.5 rounded-full bg-green-500 text-white font-medium shadow-sm">
                                                    Current
                                                </span>
                                            @elseif($point['can_rollback'])
                                                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 font-medium border border-amber-300 dark:border-amber-700">
                                                    Available
                                                </span>
                                            @else
                                                <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 font-medium">
                                                    Deployed
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white line-clamp-1">
                                            {{ $point['commit_message'] ?? 'No commit message' }}
                                        </p>
                                        <div class="flex items-center gap-3 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            <span class="flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                </svg>
                                                {{ $point['deployed_by'] }}
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                {{ \Carbon\Carbon::parse($point['deployed_at'])->diffForHumans() }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Rollback Button -->
                                @if($point['can_rollback'])
                                    <button class="flex-shrink-0 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-medium text-sm transition-all transform group-hover:scale-105 flex items-center gap-2 shadow-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                        </svg>
                                        Rollback
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="text-sm text-amber-800 dark:text-amber-200">
                            <p class="font-medium">About Rollbacks</p>
                            <p class="mt-1 text-amber-700 dark:text-amber-300">Rolling back will checkout the selected commit, rebuild containers, and run post-deployment commands. A backup of the current state will be created automatically.</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-16 w-16 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                    <p class="mt-4 text-gray-500 dark:text-gray-400 text-lg">No rollback points available</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">Successful deployments will appear here as rollback options.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Rollback Confirmation Modal -->
    @if($showRollbackModal && $selectedDeployment)
        <div class="fixed inset-0 bg-black/50 dark:bg-black/70 backdrop-blur-sm overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4"
             x-data
             @click.self="$wire.cancelRollback()">
            <div class="relative w-full max-w-2xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">

                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-amber-500 to-orange-500 p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-white flex items-center gap-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            Confirm Rollback
                        </h3>
                        <button wire:click="cancelRollback" class="text-white/80 hover:text-white transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Comparison View -->
                    @if($comparisonData)
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Current State -->
                            <div class="p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                                <h4 class="text-xs font-semibold uppercase tracking-wide text-red-700 dark:text-red-400 mb-2 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Current (Will Be Replaced)
                                </h4>
                                <code class="text-sm bg-red-600 text-white px-2 py-1 rounded font-mono">{{ $comparisonData['current']['commit'] }}</code>
                                <p class="mt-2 text-sm text-red-800 dark:text-red-200 line-clamp-2">{{ $comparisonData['current']['message'] }}</p>
                                <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $comparisonData['current']['date'] }}</p>
                            </div>

                            <!-- Target State -->
                            <div class="p-4 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                                <h4 class="text-xs font-semibold uppercase tracking-wide text-green-700 dark:text-green-400 mb-2 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Target (Will Be Restored)
                                </h4>
                                <code class="text-sm bg-green-600 text-white px-2 py-1 rounded font-mono">{{ $comparisonData['target']['commit'] }}</code>
                                <p class="mt-2 text-sm text-green-800 dark:text-green-200 line-clamp-2">{{ $comparisonData['target']['message'] }}</p>
                                <p class="text-xs text-green-600 dark:text-green-400 mt-1">{{ $comparisonData['target']['date'] }}</p>
                            </div>
                        </div>

                        <!-- Commits to Remove -->
                        @if(!empty($comparisonData['commits_to_remove']) && $comparisonData['commits_to_remove'][0] !== '')
                            <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700">
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    Commits That Will Be Undone ({{ count($comparisonData['commits_to_remove']) }})
                                </h4>
                                <div class="max-h-32 overflow-y-auto space-y-1">
                                    @foreach($comparisonData['commits_to_remove'] as $commit)
                                        @if($commit)
                                            <div class="text-xs font-mono text-gray-600 dark:text-gray-400 py-1 px-2 bg-white dark:bg-gray-800 rounded">
                                                {{ $commit }}
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Files Changed -->
                        @if(!empty($comparisonData['files_changed']))
                            <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700">
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Files That Will Change</h4>
                                <div class="max-h-32 overflow-y-auto space-y-1">
                                    @foreach($comparisonData['files_changed'] as $file)
                                        <div class="flex items-center gap-2 text-xs py-1">
                                            @if($file['status'] === 'added')
                                                <span class="w-5 h-5 flex items-center justify-center rounded bg-green-100 dark:bg-green-900/40 text-green-600 dark:text-green-400 font-bold">+</span>
                                            @elseif($file['status'] === 'deleted')
                                                <span class="w-5 h-5 flex items-center justify-center rounded bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 font-bold">-</span>
                                            @else
                                                <span class="w-5 h-5 flex items-center justify-center rounded bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 font-bold">M</span>
                                            @endif
                                            <code class="text-gray-600 dark:text-gray-400">{{ $file['path'] }}</code>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif

                    <!-- Warning -->
                    <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div class="text-sm">
                                <p class="font-medium text-amber-800 dark:text-amber-200">This action will:</p>
                                <ul class="mt-1 text-amber-700 dark:text-amber-300 list-disc list-inside space-y-1">
                                    <li>Create a backup of the current state</li>
                                    <li>Checkout commit <code class="font-mono bg-amber-200/50 dark:bg-amber-900/50 px-1 rounded">{{ $selectedDeployment['commit_hash'] ? substr($selectedDeployment['commit_hash'], 0, 7) : 'N/A' }}</code></li>
                                    <li>Rebuild and restart Docker containers</li>
                                    <li>Run Laravel optimization commands</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button wire:click="cancelRollback"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50"
                                wire:target="confirmRollback"
                                class="px-5 py-2.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg font-medium transition">
                            Cancel
                        </button>
                        <button wire:click="confirmRollback"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-75 cursor-not-allowed"
                                class="px-6 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white rounded-lg font-semibold transition-all transform hover:scale-105 shadow-lg flex items-center gap-2">
                            <svg wire:loading.remove wire:target="confirmRollback" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                            </svg>
                            <svg wire:loading wire:target="confirmRollback" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="confirmRollback">Confirm Rollback</span>
                            <span wire:loading wire:target="confirmRollback">Rolling Back...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
