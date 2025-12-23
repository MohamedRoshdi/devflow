<div>
    @if($loading)
        <div class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>
    @elseif($error)
        <div class="bg-red-900/20 border border-red-500/50 rounded-xl p-6 text-center">
            <svg class="w-12 h-12 mx-auto text-red-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="text-lg font-semibold text-white mb-2">Error Loading Git Data</h3>
            <p class="text-red-400">{{ $error }}</p>
            <button wire:click="loadGitData" class="mt-4 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                Retry
            </button>
        </div>
    @else
        <div class="space-y-6">
            {{-- Update Status Banner --}}
            @if(isset($updateStatus['success']) && $updateStatus['success'])
                <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border {{ $updateStatus['up_to_date'] ? 'border-green-500/50' : 'border-yellow-500/50' }} p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-4">
                            @if($updateStatus['up_to_date'])
                                <div class="p-3 bg-green-500/20 rounded-lg">
                                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            @else
                                <div class="p-3 bg-yellow-500/20 rounded-lg">
                                    <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                            @endif
                            <div>
                                @if($updateStatus['up_to_date'])
                                    <h3 class="text-lg font-semibold text-green-400 mb-1">Up to Date</h3>
                                    <p class="text-slate-400">Your deployment is synced with the latest commit</p>
                                @else
                                    <h3 class="text-lg font-semibold text-yellow-400 mb-1">Updates Available</h3>
                                    <p class="text-slate-400">{{ $updateStatus['commits_behind'] }} new commit(s) available</p>
                                @endif

                                @if(isset($updateStatus['local_meta']))
                                    <div class="mt-3 space-y-2 text-sm">
                                        <div>
                                            <span class="text-slate-500">Current:</span>
                                            <span class="text-slate-300 font-mono ml-2">{{ $updateStatus['local_meta']['short_hash'] }}</span>
                                            <span class="text-slate-400 ml-2">{{ $updateStatus['local_meta']['message'] }}</span>
                                        </div>
                                        @if(!$updateStatus['up_to_date'] && isset($updateStatus['remote_meta']))
                                            <div>
                                                <span class="text-slate-500">Latest:</span>
                                                <span class="text-blue-400 font-mono ml-2">{{ $updateStatus['remote_meta']['short_hash'] }}</span>
                                                <span class="text-slate-400 ml-2">{{ $updateStatus['remote_meta']['message'] }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if(!$updateStatus['up_to_date'])
                            <button wire:click="deployProject" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-800 text-white rounded-lg transition-colors flex items-center gap-2">
                                <svg wire:loading.remove class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                <svg wire:loading class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span wire:loading.remove>Deploy Update</span>
                                <span wire:loading>Deploying...</span>
                            </button>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Branches Section --}}
            @if(count($branches) > 0)
                <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700/50 overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-600/20 to-pink-600/20 px-6 py-4 border-b border-slate-700/50">
                        <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                            </svg>
                            Branches
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($branches as $branch)
                                <button
                                    wire:click="switchBranch('{{ $branch['name'] }}')"
                                    @if($branch['is_current']) disabled @endif
                                    class="p-4 rounded-lg border {{ $branch['is_current'] ? 'bg-blue-500/20 border-blue-500/50' : 'bg-slate-700/30 border-slate-700/50 hover:border-slate-600 hover:bg-slate-700/50' }} transition-all text-left"
                                >
                                    <div class="flex items-start justify-between mb-2">
                                        <span class="font-semibold {{ $branch['is_current'] ? 'text-blue-400' : 'text-white' }}">
                                            {{ $branch['name'] }}
                                        </span>
                                        @if($branch['is_current'])
                                            <span class="px-2 py-1 text-xs bg-blue-500 text-white rounded">Current</span>
                                        @elseif($branch['is_main'])
                                            <span class="px-2 py-1 text-xs bg-purple-500/20 text-purple-400 rounded border border-purple-500/50">Main</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-slate-400">{{ $branch['last_commit_date'] }}</p>
                                    <p class="text-xs text-slate-500 mt-1">by {{ $branch['last_committer'] }}</p>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Commits Section --}}
            <div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700/50 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600/20 to-cyan-600/20 px-6 py-4 border-b border-slate-700/50 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                        </svg>
                        Recent Commits
                    </h3>
                    <button wire:click="loadGitData" class="px-3 py-1 text-sm bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Refresh
                    </button>
                </div>

                <div class="divide-y divide-slate-700/50">
                    @forelse($commits as $commit)
                        <div class="p-6 hover:bg-slate-700/30 transition-colors">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-20">
                                    <span class="px-3 py-1 text-xs font-mono bg-slate-700 text-blue-400 rounded border border-slate-600">
                                        {{ $commit['short_hash'] }}
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-white font-medium mb-2">{{ $commit['message'] }}</p>
                                    <div class="flex items-center gap-4 text-sm text-slate-400">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            <span>{{ $commit['author'] }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span>{{ \Carbon\Carbon::createFromTimestamp($commit['timestamp'])->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-12 text-center">
                            <svg class="w-16 h-16 mx-auto text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="text-slate-400 text-lg">No commits found</p>
                            <p class="text-slate-500 text-sm mt-2">This project may not be deployed yet</p>
                        </div>
                    @endforelse
                </div>

                {{-- Pagination --}}
                @if($totalCommits > $perPage)
                    <div class="px-6 py-4 bg-slate-800/30 border-t border-slate-700/50 flex items-center justify-between">
                        <button
                            wire:click="previousPage"
                            @if($currentPage === 1) disabled @endif
                            class="px-4 py-2 bg-slate-700 hover:bg-slate-600 disabled:bg-slate-800 disabled:text-slate-600 disabled:cursor-not-allowed text-white rounded-lg transition-colors flex items-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Previous
                        </button>

                        <span class="text-slate-400">
                            Page {{ $currentPage }} of {{ ceil($totalCommits / $perPage) }}
                        </span>

                        <button
                            wire:click="nextPage"
                            @if($currentPage >= ceil($totalCommits / $perPage)) disabled @endif
                            class="px-4 py-2 bg-slate-700 hover:bg-slate-600 disabled:bg-slate-800 disabled:text-slate-600 disabled:cursor-not-allowed text-white rounded-lg transition-colors flex items-center gap-2"
                        >
                            Next
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
