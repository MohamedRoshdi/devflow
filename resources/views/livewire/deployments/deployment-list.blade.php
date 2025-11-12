<div>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow dark:shadow-gray-900/60 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-blue-600 px-6 py-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Deployment Activity
                    </h1>
                    <p class="text-white/80 mt-2 max-w-2xl">Explore historical deployments, filter by status or project, and jump straight into detailed logs with a click.</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white/20 hover:bg-white/30 text-white rounded-full font-semibold transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v4a1 1 0 001 1h3v8l7-12h3a1 1 0 001-1V7a1 1 0 00-1-1H4a1 1 0 00-1 1z" />
                        </svg>
                        Back to Projects
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                <div class="p-4 rounded-xl border border-blue-100 bg-blue-50 dark:border-blue-900/60 dark:bg-blue-900/20">
                    <p class="text-xs uppercase font-semibold tracking-wide text-blue-700 dark:text-blue-300">Total Deployments</p>
                    <p class="mt-2 text-2xl font-bold text-blue-900 dark:text-blue-100">{{ $stats['total'] }}</p>
                </div>
                <div class="p-4 rounded-xl border border-emerald-100 bg-emerald-50 dark:border-emerald-900/60 dark:bg-emerald-900/20">
                    <p class="text-xs uppercase font-semibold tracking-wide text-emerald-700 dark:text-emerald-300">Successful</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-900 dark:text-emerald-100">{{ $stats['success'] }}</p>
                </div>
                <div class="p-4 rounded-xl border border-amber-100 bg-amber-50 dark:border-amber-900/60 dark:bg-amber-900/20">
                    <p class="text-xs uppercase font-semibold tracking-wide text-amber-700 dark:text-amber-300">Running</p>
                    <p class="mt-2 text-2xl font-bold text-amber-900 dark:text-amber-100">{{ $stats['running'] }}</p>
                </div>
                <div class="p-4 rounded-xl border border-rose-100 bg-rose-50 dark:border-rose-900/60 dark:bg-rose-900/20">
                    <p class="text-xs uppercase font-semibold tracking-wide text-rose-700 dark:text-rose-300">Failed</p>
                    <p class="mt-2 text-2xl font-bold text-rose-900 dark:text-rose-100">{{ $stats['failed'] }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Search</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M9.5 17a7.5 7.5 0 117.5-7.5 7.5 7.5 0 01-7.5 7.5z" />
                            </svg>
                        </span>
                        <input type="text" placeholder="Commit message, branch, or project" wire:model.debounce.500ms="search"
                               class="w-full pl-9 pr-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-gray-200 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Project</label>
                    <select wire:model="projectFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-gray-200 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All projects</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Status</label>
                    <select wire:model="statusFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-gray-200 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All statuses</option>
                        <option value="success">Success</option>
                        <option value="failed">Failed</option>
                        <option value="running">Running</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Per Page</label>
                    <select wire:model="perPage" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-gray-200 focus:ring-blue-500 focus:border-blue-500">
                        @foreach([10, 15, 20, 30, 50] as $size)
                            <option value="{{ $size }}">{{ $size }} results</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if($deployments->count())
                <div class="relative pl-6">
                    <span class="absolute left-2 top-0 bottom-0 w-px bg-gradient-to-b from-indigo-200 via-purple-200 to-blue-200 dark:from-indigo-700/50 dark:via-purple-700/50 dark:to-blue-700/50"></span>
                    <div class="space-y-5">
                        @foreach($deployments as $deployment)
                            <div class="relative pl-6">
                                <span class="absolute left-0 top-3 w-3 h-3 rounded-full border-2 border-white dark:border-gray-900 bg-gradient-to-r from-indigo-500 via-purple-500 to-blue-500 shadow"></span>
                                <div class="p-6 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 hover:-translate-y-0.5 transition-transform">
                                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                        <div class="flex-1 space-y-3">
                                            <div class="flex flex-wrap items-center gap-3">
                                                <span class="px-4 py-1.5 rounded-full text-xs font-semibold uppercase tracking-wide shadow-sm
                                                    @class([
                                                        'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' => $deployment->status === 'success',
                                                        'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' => $deployment->status === 'running',
                                                        'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' => $deployment->status === 'failed',
                                                        'bg-gray-100 text-gray-700 dark:bg-gray-800/60 dark:text-gray-300' => ! in_array($deployment->status, ['success', 'failed', 'running']),
                                                    ])">
                                                    {{ ucfirst($deployment->status) }}
                                                </span>
                                                @if($deployment->commit_hash)
                                                    <code class="text-xs font-mono bg-gray-900 text-white px-3 py-1.5 rounded-lg">{{ substr($deployment->commit_hash, 0, 7) }}</code>
                                                @endif
                                                <span class="inline-flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    {{ $deployment->created_at->format('M d, Y • H:i') }}
                                                    <span class="text-[11px] text-gray-400">({{ $deployment->created_at->diffForHumans() }})</span>
                                                </span>
                                            </div>

                                            <div class="space-y-2">
                                                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-3-3v6m8-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span class="font-semibold text-gray-900 dark:text-white">{{ $deployment->project->name }}</span>
                                                </div>
                                                <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                                                    {{ $deployment->commit_message ?? 'No commit message available for this deployment.' }}
                                                </p>
                                            </div>

                                            <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-800/60">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10M7 12h4m1 8h5a2 2 0 002-2V7a2 2 0 00-2-2h-5M7 17h4m-5 4h5a2 2 0 002-2v-5" />
                                                    </svg>
                                                    Branch: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $deployment->branch }}</span>
                                                </span>
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-800/60">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2" />
                                                    </svg>
                                                    Server: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ optional($deployment->server)->name ?? 'Unknown' }}</span>
                                                </span>
                                                @if($deployment->duration_seconds)
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-800/60">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        Duration: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ number_format($deployment->duration_seconds / 60, 1) }} min</span>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex items-end lg:items-center">
                                            <a href="{{ route('deployments.show', $deployment) }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400 text-white rounded-lg font-semibold transition">
                                                View Details
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H7" />
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="pt-6">
                    {{ $deployments->onEachSide(1)->links() }}
                </div>
            @else
                <div class="text-center py-20">
                    <svg class="mx-auto h-20 w-20 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="mt-4 text-gray-500 dark:text-gray-400 text-lg">No deployments found for the selected filters.</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">Adjust your filters or trigger a new deployment to see it here.</p>
                </div>
            @endif
        </div>
    </div>
</div>
