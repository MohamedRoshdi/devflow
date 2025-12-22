<div>
<div class="relative min-h-screen">
    {{-- Animated Background Orbs --}}
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-gradient-to-br from-indigo-500/30 via-purple-500/30 to-pink-500/30 rounded-full blur-3xl animate-float"></div>
        <div class="absolute bottom-1/4 right-1/3 w-80 h-80 bg-gradient-to-br from-blue-500/20 via-cyan-500/20 to-teal-500/20 rounded-full blur-3xl animate-float-delayed"></div>
        <div class="absolute top-1/2 right-1/4 w-72 h-72 bg-gradient-to-br from-violet-500/25 via-fuchsia-500/25 to-purple-500/25 rounded-full blur-3xl animate-float-slow"></div>
    </div>

    <div class="relative">
        {{-- Glassmorphism Card Container --}}
        <div class="bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-2xl shadow-slate-900/20 dark:shadow-slate-900/60 border border-slate-200 dark:border-slate-700/50 overflow-hidden">
            {{-- Premium Gradient Header with Grid Pattern --}}
            <div class="relative bg-gradient-to-br from-indigo-600 via-purple-600 to-blue-600 px-8 py-10 overflow-hidden">
                {{-- Grid Pattern Overlay --}}
                <div class="absolute inset-0 bg-grid-pattern opacity-10"></div>
                <div class="absolute inset-0 bg-gradient-to-b from-transparent to-slate-900/20"></div>

                <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <h1 class="text-4xl font-bold text-white flex items-center gap-3 drop-shadow-lg">
                            <svg class="w-10 h-10 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Deployment Activity
                        </h1>
                        <p class="text-white/90 mt-3 max-w-2xl text-lg leading-relaxed">Explore historical deployments, filter by status or project, and jump straight into detailed logs with a click.</p>
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
                {{-- Stats Cards with Glassmorphism --}}
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-5" role="group" aria-label="Deployment statistics summary">
                    <div class="group relative p-5 rounded-2xl border border-blue-400/30 bg-gradient-to-br from-blue-500/10 to-blue-600/5 backdrop-blur-sm hover:shadow-lg hover:shadow-blue-500/20 transition-all duration-300 hover:-translate-y-1" role="status" aria-label="Total deployments: {{ $stats['total'] }}" title="Total number of deployments">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-transparent rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity" aria-hidden="true"></div>
                        <p class="relative text-xs uppercase font-bold tracking-wider text-blue-400">Total Deployments</p>
                        <p class="relative mt-3 text-3xl font-extrabold text-blue-300 drop-shadow-lg">{{ $stats['total'] }}</p>
                    </div>
                    <div class="group relative p-5 rounded-2xl border border-emerald-400/30 bg-gradient-to-br from-emerald-500/10 to-emerald-600/5 backdrop-blur-sm hover:shadow-lg hover:shadow-emerald-500/20 transition-all duration-300 hover:-translate-y-1" role="status" aria-label="Successful deployments: {{ $stats['success'] }}" title="Number of successful deployments">
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity" aria-hidden="true"></div>
                        <p class="relative text-xs uppercase font-bold tracking-wider text-emerald-400">Successful</p>
                        <p class="relative mt-3 text-3xl font-extrabold text-emerald-300 drop-shadow-lg">{{ $stats['success'] }}</p>
                    </div>
                    <div class="group relative p-5 rounded-2xl border border-amber-400/30 bg-gradient-to-br from-amber-500/10 to-amber-600/5 backdrop-blur-sm hover:shadow-lg hover:shadow-amber-500/20 transition-all duration-300 hover:-translate-y-1" role="status" aria-label="Running deployments: {{ $stats['running'] }}" title="Number of currently running deployments">
                        <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity" aria-hidden="true"></div>
                        <p class="relative text-xs uppercase font-bold tracking-wider text-amber-400">Running</p>
                        <p class="relative mt-3 text-3xl font-extrabold text-amber-300 drop-shadow-lg">{{ $stats['running'] }}</p>
                    </div>
                    <div class="group relative p-5 rounded-2xl border border-rose-400/30 bg-gradient-to-br from-rose-500/10 to-rose-600/5 backdrop-blur-sm hover:shadow-lg hover:shadow-rose-500/20 transition-all duration-300 hover:-translate-y-1" role="status" aria-label="Failed deployments: {{ $stats['failed'] }}" title="Number of failed deployments">
                        <div class="absolute inset-0 bg-gradient-to-br from-rose-500/5 to-transparent rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity" aria-hidden="true"></div>
                        <p class="relative text-xs uppercase font-bold tracking-wider text-rose-400">Failed</p>
                        <p class="relative mt-3 text-3xl font-extrabold text-rose-300 drop-shadow-lg">{{ $stats['failed'] }}</p>
                    </div>
                </div>

                <div class="mb-4">
                    <livewire:components.inline-help help-key="rollback-button" :collapsible="true" />
                </div>

                {{-- Loading State --}}
                <div wire:loading.delay class="mb-8" role="status" aria-live="polite" aria-label="Loading deployments">
                    <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-4 backdrop-blur-sm">
                        <div class="flex items-center gap-3">
                            <svg class="animate-spin h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-blue-400 font-medium">Loading deployments...</p>
                        </div>
                    </div>
                </div>

                {{-- Premium Filter Section with Glassmorphism --}}
                <div class="bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm border border-slate-200 dark:border-slate-700/50 rounded-2xl p-6 shadow-lg" wire:loading.remove role="search" aria-label="Filter deployments">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
                        <div>
                            <label for="search-deployments" class="group flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2 cursor-help" title="Search across commit messages, branch names, and project names">
                                Search
                                <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-slate-200/50 dark:bg-slate-700/50 text-slate-500 dark:text-slate-400 text-[10px] font-normal group-hover:bg-blue-500/30 group-hover:text-blue-300 transition-colors">?</span>
                            </label>
                            <div class="relative" x-data="{ focused: false }" @focusin="focused = true" @focusout="focused = false">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 transition-colors" :class="{ 'text-blue-400': focused }" aria-hidden="true">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M9.5 17a7.5 7.5 0 117.5-7.5 7.5 7.5 0 01-7.5 7.5z" />
                                    </svg>
                                </span>
                                <input type="text" id="search-deployments" placeholder="Commit message, branch, or project" wire:model.live.debounce.500ms="search"
                                       class="w-full pl-11 pr-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600/50 bg-white dark:bg-slate-900/50 backdrop-blur-sm text-sm text-slate-900 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all"
                                       aria-label="Search deployments by commit message, branch, or project"
                                       title="Type to search through all deployments by commit message, branch name, or project name" />
                            </div>
                        </div>
                        <div>
                            <label for="filter-project" class="group flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2 cursor-help" title="Filter deployments to show only those belonging to a specific project">
                                Project
                                <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-slate-200/50 dark:bg-slate-700/50 text-slate-500 dark:text-slate-400 text-[10px] font-normal group-hover:bg-blue-500/30 group-hover:text-blue-300 transition-colors">?</span>
                            </label>
                            <select id="filter-project" wire:model.live="projectFilter"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600/50 bg-white dark:bg-slate-900/50 backdrop-blur-sm text-sm text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all"
                                    aria-label="Filter deployments by project"
                                    title="Select a project to filter deployments, or choose 'All projects' to see all">
                                <option value="">All projects</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="filter-status" class="group flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2 cursor-help" title="Filter by deployment status: Success (completed), Failed (error occurred), Running (in progress), or Pending (queued)">
                                Status
                                <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-slate-200/50 dark:bg-slate-700/50 text-slate-500 dark:text-slate-400 text-[10px] font-normal group-hover:bg-blue-500/30 group-hover:text-blue-300 transition-colors">?</span>
                            </label>
                            <select id="filter-status" wire:model.live="statusFilter"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600/50 bg-white dark:bg-slate-900/50 backdrop-blur-sm text-sm text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all"
                                    aria-label="Filter deployments by status"
                                    title="Filter deployments by their current status">
                                <option value="" title="Show deployments of all statuses">All statuses</option>
                                <option value="success" title="Completed deployments without errors">Success</option>
                                <option value="failed" title="Deployments that encountered an error">Failed</option>
                                <option value="running" title="Deployments currently in progress">Running</option>
                                <option value="pending" title="Deployments waiting to be executed">Pending</option>
                            </select>
                        </div>
                        <div>
                            <label for="per-page" class="group flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2 cursor-help" title="Change how many deployments are displayed per page">
                                Per Page
                                <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-slate-200/50 dark:bg-slate-700/50 text-slate-500 dark:text-slate-400 text-[10px] font-normal group-hover:bg-blue-500/30 group-hover:text-blue-300 transition-colors">?</span>
                            </label>
                            <select id="per-page" wire:model.live="perPage"
                                    class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-600/50 bg-white dark:bg-slate-900/50 backdrop-blur-sm text-sm text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all"
                                    aria-label="Number of deployments per page"
                                    title="Adjust how many deployment entries are shown on each page">
                                @foreach([10, 15, 20, 30, 50] as $size)
                                    <option value="{{ $size }}" title="Show {{ $size }} deployments per page">{{ $size }} results</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                @if($deployments->count())
                    {{-- Timeline with Enhanced Glassmorphism Cards --}}
                    <div class="relative pl-8" wire:loading.remove role="list" aria-label="Deployment timeline">
                        {{-- Premium Timeline Line with Gradient --}}
                        <span class="absolute left-3 top-0 bottom-0 w-0.5 bg-gradient-to-b from-indigo-400/40 via-purple-400/40 to-blue-400/40 shadow-lg" aria-hidden="true"></span>

                        <div class="space-y-6">
                            @foreach($deployments as $deployment)
                                <div class="relative pl-8 group" role="listitem">
                                    {{-- Enhanced Timeline Dot with Glow Effect --}}
                                    <span @class([
                                        'absolute left-0 top-4 w-5 h-5 rounded-full border-3 border-white dark:border-slate-800 shadow-2xl transition-all duration-300 group-hover:scale-125',
                                        'bg-gradient-to-br from-emerald-400 to-green-500 shadow-emerald-500/50' => $deployment->status === 'success',
                                        'bg-gradient-to-br from-amber-400 to-orange-500 shadow-amber-500/50 animate-pulse' => $deployment->status === 'running',
                                        'bg-gradient-to-br from-red-400 to-rose-500 shadow-red-500/50' => $deployment->status === 'failed',
                                        'bg-gradient-to-br from-blue-400 to-indigo-500 shadow-blue-500/50 animate-pulse' => $deployment->status === 'pending',
                                        'bg-gradient-to-br from-slate-400 to-slate-500 shadow-slate-500/50' => ! in_array($deployment->status, ['success', 'failed', 'running', 'pending']),
                                    ]) aria-hidden="true" @if($deployment->status === 'success') title="Deployment successful"
                                    @elseif($deployment->status === 'running') title="Deployment in progress"
                                    @elseif($deployment->status === 'failed') title="Deployment failed"
                                    @elseif($deployment->status === 'pending') title="Deployment pending"
                                    @else title="Deployment status: {{ $deployment->status }}"
                                    @endif>
                                        <span class="absolute inset-0 rounded-full animate-ping opacity-30" @class([
                                            'bg-emerald-400' => $deployment->status === 'success',
                                            'bg-amber-400' => $deployment->status === 'running',
                                            'bg-red-400' => $deployment->status === 'failed',
                                            'bg-blue-400' => $deployment->status === 'pending',
                                            'bg-slate-400' => ! in_array($deployment->status, ['success', 'failed', 'running', 'pending']),
                                        ]) aria-hidden="true"></span>
                                    </span>

                                    {{-- Glassmorphism Deployment Card with Glow on Hover --}}
                                    <div @class([
                                        'relative p-6 bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl border-2 transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl',
                                        'border-emerald-500/40 hover:border-emerald-400/60 hover:shadow-emerald-500/20' => $deployment->status === 'success',
                                        'border-amber-500/40 hover:border-amber-400/60 hover:shadow-amber-500/20' => $deployment->status === 'running',
                                        'border-red-500/40 hover:border-red-400/60 hover:shadow-red-500/20' => $deployment->status === 'failed',
                                        'border-blue-500/40 hover:border-blue-400/60 hover:shadow-blue-500/20' => $deployment->status === 'pending',
                                        'border-slate-600/40 hover:border-slate-500/60 hover:shadow-slate-500/20' => ! in_array($deployment->status, ['success', 'failed', 'running', 'pending']),
                                    ]) aria-label="Deployment {{ $deployment->id }} for {{ $deployment->project->name }} - Status: {{ ucfirst($deployment->status) }} - {{ $deployment->created_at->diffForHumans() }}">
                                        {{-- Subtle Glow Overlay --}}
                                        <div @class([
                                            'absolute inset-0 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300',
                                            'bg-gradient-to-br from-emerald-500/5 to-transparent' => $deployment->status === 'success',
                                            'bg-gradient-to-br from-amber-500/5 to-transparent' => $deployment->status === 'running',
                                            'bg-gradient-to-br from-red-500/5 to-transparent' => $deployment->status === 'failed',
                                            'bg-gradient-to-br from-blue-500/5 to-transparent' => $deployment->status === 'pending',
                                            'bg-gradient-to-br from-slate-500/5 to-transparent' => ! in_array($deployment->status, ['success', 'failed', 'running', 'pending']),
                                        ]) aria-hidden="true"></div>

                                        <div class="relative flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5">
                                            <div class="flex-1 space-y-4">
                                                {{-- Status Badge & Metadata --}}
                                                <div class="flex flex-wrap items-center gap-3">
                                                    <span @class([
                                                        'inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-extrabold uppercase tracking-wider shadow-xl border-2 transition-all duration-300 hover:scale-105',
                                                        'bg-emerald-600 text-white shadow-emerald-600/40 border-emerald-500' => $deployment->status === 'success',
                                                        'bg-amber-600 text-white shadow-amber-600/40 border-amber-500' => $deployment->status === 'running',
                                                        'bg-red-600 text-white shadow-red-600/40 border-red-500' => $deployment->status === 'failed',
                                                        'bg-blue-600 text-white shadow-blue-600/40 border-blue-500' => $deployment->status === 'pending',
                                                        'bg-slate-700 text-white shadow-slate-700/40 border-slate-600' => ! in_array($deployment->status, ['success', 'failed', 'running', 'pending']),
                                                    ]) role="status" aria-live="polite" aria-label="Deployment status: {{ ucfirst($deployment->status) }}" @if($deployment->status === 'success') title="Deployment completed successfully"
                                                    @elseif($deployment->status === 'running') title="Deployment currently in progress"
                                                    @elseif($deployment->status === 'failed') title="Deployment failed"
                                                    @elseif($deployment->status === 'pending') title="Deployment pending execution"
                                                    @else title="Deployment status: {{ ucfirst($deployment->status) }}"
                                                    @endif>
                                                        @if($deployment->status === 'success')
                                                            <svg class="w-4 h-4 drop-shadow-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                                            </svg>
                                                        @elseif($deployment->status === 'running')
                                                            <svg class="w-4 h-4 animate-spin drop-shadow-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                            </svg>
                                                        @elseif($deployment->status === 'failed')
                                                            <svg class="w-4 h-4 drop-shadow-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                                                            </svg>
                                                        @elseif($deployment->status === 'pending')
                                                            <svg class="w-4 h-4 animate-pulse drop-shadow-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                            </svg>
                                                        @else
                                                            <span class="w-2 h-2 rounded-full bg-white drop-shadow-lg" aria-hidden="true"></span>
                                                        @endif
                                                        {{ ucfirst($deployment->status) }}
                                                    </span>

                                                    @if($deployment->commit_hash)
                                                        <code class="text-xs font-mono bg-slate-100/80 dark:bg-slate-900/80 backdrop-blur-sm text-cyan-600 dark:text-cyan-300 px-4 py-2 rounded-lg border border-cyan-500/30 shadow-lg" aria-label="Commit hash: {{ $deployment->commit_hash }}" title="Full commit hash: {{ $deployment->commit_hash }}">{{ substr($deployment->commit_hash, 0, 7) }}</code>
                                                    @endif

                                                    <span class="inline-flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400" aria-label="Deployment time: {{ $deployment->created_at->format('M d, Y at H:i') }}" title="Deployed on {{ $deployment->created_at->format('M d, Y at H:i') }}">
                                                        <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        {{ $deployment->created_at->format('M d, Y • H:i') }}
                                                        <span class="text-xs text-slate-400 dark:text-slate-500">({{ $deployment->created_at->diffForHumans() }})</span>
                                                    </span>
                                                </div>

                                                {{-- Project & Commit Info --}}
                                                <div class="space-y-3">
                                                    <div class="flex items-center gap-2.5 text-sm">
                                                        <svg class="w-5 h-5 text-blue-500 dark:text-blue-400 drop-shadow-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v4a1 1 0 001 1h3v8l7-12h3a1 1 0 001-1V7a1 1 0 00-1-1H4a1 1 0 00-1 1z" />
                                                        </svg>
                                                        <span class="font-bold text-slate-900 dark:text-slate-100">{{ $deployment->project->name }}</span>
                                                    </div>
                                                    <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed pl-7">
                                                        {{ $deployment->commit_message ?? 'No commit message available for this deployment.' }}
                                                    </p>
                                                </div>

                                                {{-- Metadata Pills --}}
                                                <div class="flex flex-wrap items-center gap-3 text-xs pl-7">
                                                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-100/50 dark:bg-slate-700/50 backdrop-blur-sm border border-slate-200 dark:border-slate-600/50 text-slate-600 dark:text-slate-300" aria-label="Branch: {{ $deployment->branch }}" title="Git branch: {{ $deployment->branch }}">
                                                        <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10M7 12h4m1 8h5a2 2 0 002-2V7a2 2 0 00-2-2h-5M7 17h4m-5 4h5a2 2 0 002-2v-5" />
                                                        </svg>
                                                        Branch: <span class="font-bold text-slate-900 dark:text-white ml-1">{{ $deployment->branch }}</span>
                                                    </span>
                                                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-100/50 dark:bg-slate-700/50 backdrop-blur-sm border border-slate-200 dark:border-slate-600/50 text-slate-600 dark:text-slate-300" aria-label="Server: {{ optional($deployment->server)->name ?? 'Unknown' }}" title="Deployment server: {{ optional($deployment->server)->name ?? 'Unknown' }}">
                                                        <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2" />
                                                        </svg>
                                                        Server: <span class="font-bold text-slate-900 dark:text-white ml-1">{{ optional($deployment->server)->name ?? 'Unknown' }}</span>
                                                    </span>
                                                    @if($deployment->duration_seconds)
                                                        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-100/50 dark:bg-slate-700/50 backdrop-blur-sm border border-slate-200 dark:border-slate-600/50 text-slate-600 dark:text-slate-300" aria-label="Duration: {{ number_format($deployment->duration_seconds / 60, 1) }} minutes" title="Deployment took {{ number_format($deployment->duration_seconds / 60, 1) }} minutes">
                                                            <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                            Duration: <span class="font-bold text-slate-900 dark:text-white ml-1">{{ number_format($deployment->duration_seconds / 60, 1) }} min</span>
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- Action Button --}}
                                            <div class="flex items-end lg:items-center gap-2">
                                                <a href="{{ route('deployments.show', $deployment) }}" class="group inline-flex items-center gap-2.5 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-xl font-bold transition-all duration-300 shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40 hover:scale-105 backdrop-blur-sm border border-blue-400/30" aria-label="View deployment details for {{ $deployment->project->name }}" title="View full deployment logs and details">
                                                    View Details
                                                    <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H7" />
                                                    </svg>
                                                </a>
                                                <livewire:components.inline-help help-key="view-logs-button" :collapsible="true" :key="'help-logs-'.$deployment->id" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Pagination --}}
                    <div class="pt-8" wire:loading.remove>
                        {{ $deployments->onEachSide(1)->links() }}
                    </div>
                @elseif($search || $projectFilter || $statusFilter)
                    {{-- No Results State (filters applied) --}}
                    <div class="text-center py-24 bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm rounded-2xl border border-slate-200 dark:border-slate-700/50" wire:loading.remove role="status" aria-live="polite">
                        <div class="relative inline-block">
                            <svg class="mx-auto h-24 w-24 text-slate-400 dark:text-slate-600 drop-shadow-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <div class="absolute inset-0 blur-2xl bg-slate-500/20 rounded-full" aria-hidden="true"></div>
                        </div>
                        <p class="mt-6 text-slate-700 dark:text-slate-300 text-xl font-semibold">No deployments found for the selected filters.</p>
                        <p class="text-sm text-slate-500 mt-3 mb-6">Adjust your filters or trigger a new deployment to see it here.</p>
                        <button wire:click="$set('search', ''); $set('projectFilter', ''); $set('statusFilter', '')"
                           class="group inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-sm text-slate-900 dark:text-white overflow-hidden transition-all duration-300 hover:-translate-y-0.5 bg-slate-100/50 dark:bg-slate-700/50 backdrop-blur-sm border border-slate-300 dark:border-slate-600/50 hover:border-slate-400 dark:hover:border-slate-500/50"
                           aria-label="Clear all filters and show all deployments" title="Remove all active filters">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            <span>Clear All Filters</span>
                        </button>
                    </div>
                @else
                    {{-- Empty State (no deployments at all) --}}
                    <div class="text-center py-24 bg-white/30 dark:bg-slate-800/30 backdrop-blur-sm rounded-2xl border border-slate-200 dark:border-slate-700/50" wire:loading.remove role="status">
                        <div class="relative inline-block">
                            <svg class="mx-auto h-24 w-24 text-slate-400 dark:text-slate-600 drop-shadow-lg" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <div class="absolute inset-0 blur-2xl bg-slate-500/20 rounded-full" aria-hidden="true"></div>
                        </div>
                        <p class="mt-6 text-slate-700 dark:text-slate-300 text-xl font-semibold">No deployments yet</p>
                        <p class="text-sm text-slate-500 mt-3 mb-6">Start deploying your projects to see deployment history here.</p>
                        <a href="{{ route('projects.index') }}"
                           class="group inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-sm text-white overflow-hidden transition-all duration-300 hover:-translate-y-0.5"
                           style="background: linear-gradient(135deg, #3b82f6 0%, #6366f1 50%, #8b5cf6 100%);"
                           aria-label="Navigate to projects page to start deployments" title="Go to projects page">
                            <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/25 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700" aria-hidden="true"></div>
                            <svg class="w-4 h-4 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span class="relative z-10">View Projects</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

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
