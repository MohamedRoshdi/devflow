<div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700/50 overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-700/50 flex items-center justify-between">
        <h2 class="font-semibold text-white">Recent Activity</h2>
        <a href="{{ route('deployments.index') }}" class="text-sm text-blue-400 hover:text-blue-300">View all &rarr;</a>
    </div>

    @if($isLoading)
    {{-- Loading Skeleton --}}
    <div class="divide-y divide-slate-700/50">
        @for($i = 0; $i < 3; $i++)
        <div class="px-6 py-4 animate-pulse">
            <div class="flex items-start gap-4">
                <div class="w-8 h-8 bg-slate-700 rounded-full"></div>
                <div class="flex-1 space-y-2">
                    <div class="h-4 bg-slate-700 rounded w-3/4"></div>
                    <div class="h-3 bg-slate-700 rounded w-1/2"></div>
                    <div class="h-3 bg-slate-700 rounded w-1/4"></div>
                </div>
            </div>
        </div>
        @endfor
    </div>
    @elseif($hasError)
    {{-- Error State --}}
    <div class="px-6 py-8">
        <div class="bg-red-500/10 rounded-lg p-4 border border-red-500/30">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-red-500/20 rounded-lg">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <p class="text-red-400 text-sm">{{ $errorMessage }}</p>
                </div>
                <button wire:click="retryLoad"
                        wire:loading.attr="disabled"
                        class="px-3 py-1.5 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                    <svg wire:loading.remove wire:target="retryLoad" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <svg wire:loading wire:target="retryLoad" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Retry
                </button>
            </div>
        </div>
    </div>
    @else
    <div class="divide-y divide-slate-700/50">
        @forelse($recentActivity as $activity)
        <div class="px-6 py-4 hover:bg-slate-700/30 transition-colors">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 mt-0.5">
                    <span class="w-8 h-8 rounded-full flex items-center justify-center
                        @if($activity['type'] === 'deployment')
                            @if($activity['status'] === 'success') bg-green-500/20 border border-green-500/30
                            @elseif($activity['status'] === 'failed') bg-red-500/20 border border-red-500/30
                            @elseif($activity['status'] === 'running') bg-amber-500/20 border border-amber-500/30
                            @else bg-slate-700
                            @endif
                        @else bg-blue-500/20 border border-blue-500/30
                        @endif">
                        @if($activity['type'] === 'deployment')
                            <svg class="w-4 h-4 @if($activity['status'] === 'success') text-green-400 @elseif($activity['status'] === 'failed') text-red-400 @elseif($activity['status'] === 'running') text-amber-400 @else text-slate-400 @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        @endif
                    </span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white">{{ $activity['title'] }}</p>
                    <p class="text-sm text-slate-400">{{ $activity['description'] }}</p>
                    <div class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                        <span>{{ $activity['user'] }}</span>
                        <span>&bull;</span>
                        <span>{{ $activity['timestamp']->diffForHumans() }}</span>
                        @if($activity['type'] === 'deployment')
                        <span>&bull;</span>
                        <span class="px-1.5 py-0.5 rounded text-xs font-medium
                            @if($activity['status'] === 'success') bg-green-500/20 text-green-400 border border-green-500/30
                            @elseif($activity['status'] === 'failed') bg-red-500/20 text-red-400 border border-red-500/30
                            @elseif($activity['status'] === 'running') bg-amber-500/20 text-amber-400 border border-amber-500/30
                            @else bg-slate-700 text-slate-300
                            @endif">{{ ucfirst($activity['status']) }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="px-6 py-12 text-center">
            <svg class="mx-auto h-12 w-12 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
            <p class="mt-2 text-sm text-slate-400">No recent activity</p>
            <a href="{{ route('projects.create') }}" class="mt-3 inline-flex items-center text-sm text-blue-400 hover:text-blue-300">Create your first project &rarr;</a>
        </div>
        @endforelse
    </div>
    @if(count($recentActivity) > 0)
    <div class="px-6 py-3 bg-slate-700/30 border-t border-slate-700/50">
        <button wire:click="loadMoreActivity" wire:loading.attr="disabled" class="w-full text-sm text-slate-400 hover:text-white font-medium disabled:opacity-50">
            <span wire:loading.remove wire:target="loadMoreActivity">Load more</span>
            <span wire:loading wire:target="loadMoreActivity">Loading...</span>
        </button>
    </div>
    @endif
    @endif
</div>
