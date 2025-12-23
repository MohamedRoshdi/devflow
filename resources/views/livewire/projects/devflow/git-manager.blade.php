<div>
    {{-- Git Management Component --}}
    <div class="space-y-6">
        @if(!$isGitRepo)
            {{-- No Git Repository Setup --}}
            <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-8 text-center">
                <div class="w-16 h-16 rounded-full bg-slate-700/50 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-white mb-2">No Git Repository</h3>
                <p class="text-slate-400 mb-6">This DevFlow instance is not connected to a Git repository.</p>
                <button wire:click="toggleGitSetup" class="px-6 py-2.5 rounded-xl font-medium text-sm bg-emerald-600 text-white hover:bg-emerald-500 transition-colors">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Initialize Git Repository
                    </span>
                </button>
            </div>

            {{-- Git Setup Modal --}}
            @if($showGitSetup)
                <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Initialize Git Repository</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Repository URL</label>
                            <input type="text" wire:model="newRepoUrl" class="w-full px-4 py-2 rounded-lg bg-slate-900/50 border border-slate-700 text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="https://github.com/user/repo.git">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Branch</label>
                            <input type="text" wire:model="newBranch" class="w-full px-4 py-2 rounded-lg bg-slate-900/50 border border-slate-700 text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="master">
                        </div>
                        @if($gitSetupOutput)
                            <div class="bg-slate-900/50 border border-slate-700 rounded-lg p-4">
                                <pre class="text-xs text-slate-300 whitespace-pre-wrap">{{ $gitSetupOutput }}</pre>
                            </div>
                        @endif
                        <div class="flex gap-3">
                            <button wire:click="initializeGit" wire:loading.attr="disabled" :disabled="$isSettingUpGit" class="px-6 py-2.5 rounded-xl font-medium text-sm bg-emerald-600 text-white hover:bg-emerald-500 transition-colors disabled:opacity-50">
                                <span wire:loading.remove wire:target="initializeGit">Initialize</span>
                                <span wire:loading wire:target="initializeGit">Initializing...</span>
                            </button>
                            <button wire:click="toggleGitSetup" class="px-6 py-2.5 rounded-xl font-medium text-sm bg-slate-700 text-white hover:bg-slate-600 transition-colors">Cancel</button>
                        </div>
                    </div>
                </div>
            @endif
        @else
            {{-- Git Repository Info --}}
            <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-white">Repository Information</h3>
                    <button wire:click="refreshGitTab" class="text-sm text-emerald-400 hover:text-emerald-300">Refresh</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-slate-900/50 rounded-lg p-4">
                        <div class="text-xs text-slate-400 mb-1">Current Branch</div>
                        <div class="text-white font-mono">{{ $gitBranch }}</div>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-4">
                        <div class="text-xs text-slate-400 mb-1">Last Commit</div>
                        <div class="text-white text-sm font-mono">{{ $gitLastCommit }}</div>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-4">
                        <div class="text-xs text-slate-400 mb-1">Remote URL</div>
                        <div class="text-white text-sm font-mono truncate">{{ $gitRemoteUrl }}</div>
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <button wire:click="pullLatestChanges" wire:loading.attr="disabled" class="px-4 py-2 rounded-lg font-medium text-sm bg-emerald-600 text-white hover:bg-emerald-500 transition-colors disabled:opacity-50">
                        <span wire:loading.remove wire:target="pullLatestChanges">Pull Changes</span>
                        <span wire:loading wire:target="pullLatestChanges">Pulling...</span>
                    </button>
                    <button wire:click="removeGit" onclick="return confirm('Are you sure you want to remove Git?')" class="px-4 py-2 rounded-lg font-medium text-sm bg-red-600 text-white hover:bg-red-500 transition-colors">Remove Git</button>
                </div>
            </div>

            {{-- Git Status --}}
            <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Git Status</h3>
                <div wire:loading.class="opacity-50" wire:target="loadGitTab,refreshGitTab">
                    @if(isset($gitStatus['clean']) && $gitStatus['clean'])
                        <div class="text-emerald-400">Working directory clean</div>
                    @else
                        <div class="space-y-2">
                            @if(!empty($gitStatus['modified']))
                                <div class="text-amber-400">Modified: {{ count($gitStatus['modified']) }} files</div>
                            @endif
                            @if(!empty($gitStatus['staged']))
                                <div class="text-emerald-400">Staged: {{ count($gitStatus['staged']) }} files</div>
                            @endif
                            @if(!empty($gitStatus['untracked']))
                                <div class="text-slate-400">Untracked: {{ count($gitStatus['untracked']) }} files</div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Branches --}}
            @if(!empty($branches))
                <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Branches</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($branches as $branch)
                            <button wire:click="switchBranch('{{ $branch }}')" class="px-3 py-1.5 rounded-lg text-sm {{ $branch === $gitBranch ? 'bg-emerald-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600' }} transition-colors">
                                {{ $branch }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Commits --}}
            @if(!empty($commits))
                <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-white">Recent Commits</h3>
                        <div class="flex items-center gap-2">
                            <button wire:click="previousCommitPage" :disabled="$commitPage <= 1" class="px-3 py-1 rounded-lg bg-slate-700 text-white disabled:opacity-50">Previous</button>
                            <span class="text-sm text-slate-400">Page {{ $commitPage }} of {{ $this->commitPages }}</span>
                            <button wire:click="nextCommitPage" :disabled="$commitPage >= $this->commitPages" class="px-3 py-1 rounded-lg bg-slate-700 text-white disabled:opacity-50">Next</button>
                        </div>
                    </div>
                    <div class="space-y-2">
                        @foreach($commits as $commit)
                            <div class="bg-slate-900/50 rounded-lg p-3">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="text-white font-medium">{{ $commit['message'] }}</div>
                                        <div class="text-xs text-slate-400 mt-1">
                                            <span class="font-mono">{{ $commit['short_hash'] }}</span> by {{ $commit['author'] }} • {{ $commit['date'] }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
