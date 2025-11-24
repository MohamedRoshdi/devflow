@php
    use Illuminate\Support\Str;

    $isUpdatePending = $updateStatus && !(($updateStatus['up_to_date'] ?? true));
    $initialTab = $firstTab ?? 'overview';
@endphp

<div wire:init="preloadUpdateStatus" x-data="{
        projectId: {{ $project->id }},
        activeTab: '{{ $initialTab }}',
        dockerReady: false,
        gitPrimed: false,
        tabLoading: false,
        init() {
            const stored = localStorage.getItem(`project-${this.projectId}-tab`);
            if (stored) {
                this.activeTab = stored;
            }

            if (this.activeTab === 'docker') {
                this.prepareDocker();
            }

            if (this.activeTab === 'git') {
                this.prepareGit();
            }
        },
        setTab(value) {
            if (this.activeTab === value) {
                return;
            }

            this.tabLoading = true;
            this.activeTab = value;
            localStorage.setItem(`project-${this.projectId}-tab`, value);

            setTimeout(() => {
                this.tabLoading = false;
            }, 300);

            if (value === 'docker') {
                this.prepareDocker();
            }

            if (value === 'git') {
                this.prepareGit();
            }
        },
        prepareDocker() {
            if (!this.dockerReady) {
                Livewire.dispatch('init-docker', { projectId: this.projectId });
                this.dockerReady = true;
            }
        },
        prepareGit() {
            if (!this.gitPrimed) {
                $wire.prepareGitTab();
                this.gitPrimed = true;
            }
        }
    }">
    <!-- Hero Section with Project Status -->
    <div class="mb-10 relative">
        <div class="absolute inset-0 rounded-3xl bg-gradient-to-r from-indigo-500 via-purple-500 to-sky-500 opacity-80 blur-xl"></div>
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-900/90 to-blue-900 text-white shadow-2xl">
            <div class="absolute inset-y-0 right-0 w-1/2 bg-[radial-gradient(circle_at_top,_rgba(255,255,255,0.12),_transparent_55%)]"></div>
            <div class="relative p-8 xl:p-10 space-y-8">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-8">
                    <div class="flex-1 space-y-5">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="px-3 py-1 text-xs font-semibold tracking-wide uppercase bg-white/10 text-white/80 rounded-full">Project</span>
                            <span class="px-3 py-1 text-xs font-semibold tracking-wide uppercase bg-white/10 text-white/60 rounded-full">{{ $project->framework ?? 'Unknown Stack' }}</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-4">
                            <h1 class="text-4xl lg:text-5xl font-extrabold tracking-tight">{{ $project->name }}</h1>
                            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-semibold shadow-lg
                                @class([
                                    'bg-green-500/90' => $project->status === 'running',
                                    'bg-yellow-500/90 animate-pulse' => $project->status === 'building',
                                    'bg-slate-500/90' => $project->status === 'stopped',
                                    'bg-red-500/90' => !in_array($project->status, ['running','building','stopped'])
                                ])">
                                <span class="w-2.5 h-2.5 rounded-full bg-white"></span>
                                {{ ucfirst($project->status) }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 text-sm text-indigo-100">
                            <div class="flex items-center gap-2 bg-white/5 rounded-xl px-3 py-2 border border-white/10">
                                <svg class="w-4 h-4 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                                <span class="font-medium">Slug</span>
                                <span class="font-mono text-white/90">{{ $project->slug }}</span>
                            </div>
                            <div class="flex items-center gap-2 bg-white/5 rounded-xl px-3 py-2 border border-white/10">
                                <svg class="w-4 h-4 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"></path>
                                </svg>
                                <span class="font-medium">Server</span>
                                <span class="text-white/90">{{ $project->server->name ?? 'No Server' }}</span>
                            </div>
                            <div class="flex items-center gap-2 bg-white/5 rounded-xl px-3 py-2 border border-white/10">
                                <svg class="w-4 h-4 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h7"></path>
                                </svg>
                                <span class="font-medium">Branch</span>
                                <span class="font-mono text-white/90">{{ $project->branch }}</span>
                            </div>
                            @if($project->environment)
                                <div class="flex items-center gap-2 bg-white/5 rounded-xl px-3 py-2 border border-white/10">
                                    <svg class="w-4 h-4 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 16v-2m8-6h2M4 12H2m15.364-7.364l1.414-1.414M6.343 17.657l-1.414 1.414m0-13.657L6.343 6.343m11.314 11.314l1.414 1.414" />
                                    </svg>
                                    <span class="font-medium">Environment</span>
                                    <span class="inline-flex items-center gap-1 text-white/90">
                                        @if($project->environment === 'production')
                                            <span class="text-lg">🚀</span>
                                        @elseif($project->environment === 'staging')
                                            <span class="text-lg">🔧</span>
                                        @elseif($project->environment === 'development')
                                            <span class="text-lg">💻</span>
                                        @else
                                            <span class="text-lg">🏠</span>
                                        @endif
                                        {{ ucfirst($project->environment) }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        @if($project->status === 'running' && $project->port && $project->server)
                            @php
                                $url = 'http://' . $project->server->ip_address . ':' . $project->port;
                            @endphp
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white/10 border border-white/10 rounded-2xl px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-300 animate-pulse"></span>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-white/70">Live URL</div>
                                    <a href="{{ $url }}" target="_blank" class="font-mono text-sm text-white hover:text-blue-100 transition flex items-center gap-2">
                                        {{ $url }}
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                </div>
                                <button onclick="navigator.clipboard.writeText('{{ $url }}'); window.dispatchEvent(new CustomEvent('devflow-toast',{detail:{message:'Live URL copied!',type:'success'}}));"
                                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold uppercase tracking-wide bg-white/20 hover:bg-white/30 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16h8M8 12h8m-1-8H9a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V6a2 2 0 00-2-2z" />
                                    </svg>
                                    Copy URL
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-col sm:flex-row lg:flex-col items-stretch gap-3">
                        @if($project->status === 'running')
                            <button wire:click="stopProject" wire:confirm="Stop this project?"
                                    class="group px-6 py-3 rounded-xl bg-gradient-to-r from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-700 text-white font-semibold shadow-lg transition-transform transform hover:-translate-y-0.5">
                                <div class="flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h6v6H9z" />
                                    </svg>
                                    Stop Project
                                </div>
                                <p class="text-[11px] text-white/70 tracking-wide">Gracefully shuts down container</p>
                            </button>
                        @else
                            <button wire:click="startProject"
                                    class="group px-6 py-3 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white font-semibold shadow-lg transition-transform transform hover:-translate-y-0.5">
                                <div class="flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    </svg>
                                    Start Project
                                </div>
                                <p class="text-[11px] text-white/70 tracking-wide">Boots container and services</p>
                            </button>
                        @endif

                        <button wire:click="$set('showDeployModal', true)"
                                class="group px-6 py-3 rounded-xl bg-white text-indigo-700 font-semibold shadow-lg hover:shadow-xl transition-transform transform hover:-translate-y-0.5">
                            <div class="flex items-center justify-center gap-2">
                                <span class="text-lg">🚀</span>
                                Deploy Update
                            </div>
                            <p class="text-[11px] text-indigo-500 tracking-wide">Guided deployment workflow</p>
                        </button>

                        <a href="{{ route('projects.edit', $project) }}"
                           class="px-6 py-3 rounded-xl bg-white/10 border border-white/20 text-white font-semibold hover:bg-white/15 transition">
                            <div class="flex items-center justify-center gap-2">
                                <span>✏️</span>
                                Configure Project
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    @if (session()->has('message'))
        <div class="mb-6 bg-green-50 dark:bg-green-900/30 border-l-4 border-green-500 text-green-800 dark:text-green-400 px-6 py-4 rounded-r-lg shadow">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('message') }}
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 text-red-800 dark:text-red-400 px-6 py-4 rounded-r-lg shadow">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                {{ session('error') }}
            </div>
        </div>
    @endif

    <!-- Git Update Alert -->
    @if($checkingForUpdates && !$updateStatusLoaded)
        <div class="mb-8 rounded-3xl border border-blue-200/70 bg-gradient-to-br from-blue-50 via-slate-50 to-white dark:from-blue-900/10 dark:via-slate-900/10 dark:to-slate-900 shadow-xl">
            <div class="flex items-center justify-between gap-6 p-6">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-blue-500/90 text-white flex items-center justify-center animate-pulse">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-200">Checking repository status…</h3>
                        <p class="text-sm text-blue-600 dark:text-blue-300">Fetching latest commits from origin/{{ $project->branch }}.</p>
                    </div>
                </div>
                <div class="text-xs text-blue-500 dark:text-blue-300 uppercase tracking-wide">Please wait</div>
            </div>
        </div>
    @elseif($updateStatus && !$updateStatus['up_to_date'])
        <div class="mb-8 rounded-3xl border border-amber-200/70 bg-gradient-to-br from-amber-50 via-orange-50 to-white dark:from-amber-900/10 dark:to-slate-900 shadow-xl">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 p-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 rounded-full bg-amber-400 text-white flex items-center justify-center shadow animate-bounce">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <h3 class="text-xl font-bold text-amber-800 dark:text-amber-200">{{ $updateStatus['commits_behind'] }} new {{ Str::plural('commit', $updateStatus['commits_behind']) }} ready for deployment</h3>
                            <p class="text-sm text-amber-600 dark:text-amber-300">Pull in the latest changes from <span class="font-semibold">origin/{{ $project->branch }}</span> to keep production current.</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                            <div class="flex items-center gap-3 bg-white/70 dark:bg-amber-900/30 border border-white/80 dark:border-amber-800 rounded-xl px-4 py-3">
                                <div class="text-xs font-semibold uppercase text-amber-600 dark:text-amber-200">Current</div>
                                <code class="font-mono text-sm text-amber-900 dark:text-amber-100">{{ $updateStatus['local_commit'] }}</code>
                            </div>
                            <div class="flex items-center gap-3 bg-white/70 dark:bg-amber-900/30 border border-white/80 dark:border-amber-800 rounded-xl px-4 py-3">
                                <div class="text-xs font-semibold uppercase text-emerald-600 dark:text-emerald-200">Latest</div>
                                <code class="font-mono text-sm text-emerald-700 dark:text-emerald-100">{{ $updateStatus['remote_commit'] }}</code>
                            </div>
                        </div>
                        @if(isset($updateStatus['remote_meta']['message']))
                            <p class="text-xs text-amber-500 dark:text-amber-200 italic">Latest change: "{{ Str::limit($updateStatus['remote_meta']['message'], 120) }}"</p>
                        @endif
                    </div>
                </div>
                <div class="flex flex-col items-stretch gap-3">
                    <button wire:click="$set('showDeployModal', true)"
                            class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 text-white font-semibold shadow-lg hover:shadow-xl transition-transform transform hover:-translate-y-0.5">
                        <span class="text-lg">🚀</span>
                        Deploy Latest Changes
                    </button>
                    <div class="text-xs text-amber-500 dark:text-amber-300 text-center">Automated Laravel optimizations run post-deploy.</div>
                </div>
            </div>
        </div>
    @endif

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg dark:shadow-gray-900/50 p-6 transition-all hover:shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Deployments</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $project->deployments()->count() }}</p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg dark:shadow-gray-900/50 p-6 transition-all hover:shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Domains</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $project->domains->count() }}</p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-full">
                    <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg dark:shadow-gray-900/50 p-6 transition-all hover:shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Storage</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($project->storage_used_mb / 1024, 1) }}GB</p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-full">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg dark:shadow-gray-900/50 p-6 transition-all hover:shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Last Deploy</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white mt-2">{{ $project->last_deployed_at ? $project->last_deployed_at->diffForHumans() : 'Never' }}</p>
                </div>
                <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-full">
                    <svg class="w-8 h-8 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabbed Navigation -->
    <div class="mb-6">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8 overflow-x-auto">
                <button @click="setTab('overview')" 
                        :class="activeTab === 'overview' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span>Overview</span>
                </button>

                <button @click="setTab('docker')" 
                        :class="activeTab === 'docker' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <span>Docker</span>
                </button>

                <button @click="setTab('environment')" 
                        :class="activeTab === 'environment' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span>Environment</span>
                </button>

                <button @click="setTab('git'); $wire.prepareGitTab()" 
                        class="flex items-center space-x-2 px-4 py-2 rounded-lg transition-colors
                        @if($project->status === 'running' && $isUpdatePending) text-yellow-500 @endif
                        " :class="activeTab === 'git' ? 'bg-white/10 text-white' : 'text-white/70 hover:text-white'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12A4 4 0 108 12a4 4 0 108 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v2m6.364.636l-1.414 1.414M22 12h-2m-.636 6.364l-1.414-1.414M12 20v-2m-6.364.636l1.414-1.414M4 12H2m.636-6.364l1.414 1.414" />
                    </svg>
                    <span>Git & Commits</span>
                </button>

                <button @click="setTab('logs')"
                        class="flex items-center space-x-2 px-4 py-2 rounded-lg transition-colors"
                        :class="activeTab === 'logs' ? 'bg-white/10 text-white' : 'text-white/70 hover:text-white'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 17l4 4 4-4m0-5V3m-4 14V3m-4 9v4m8-4v4" />
                    </svg>
                    <span>Logs</span>
                </button>

                <button @click="setTab('deployments')" 
                        class="flex items-center space-x-2 px-4 py-2 rounded-lg transition-colors" 
                        :class="activeTab === 'deployments' ? 'bg-white/10 text-white' : 'text-white/70 hover:text-white'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Deployments</span>
                </button>
            </nav>
        </div>
    </div>

    <!-- Tab Loading Overlay -->
    <x-tab-loading-overlay />

    <!-- Tab Content -->
    <div class="min-h-screen relative">
        <!-- Overview Tab -->
        <div x-show="activeTab === 'overview'" x-transition class="space-y-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Project Details Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg dark:shadow-gray-900/50 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-6">
                        <h2 class="text-2xl font-bold text-white flex items-center">
                            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Project Details
                        </h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"></path>
                                </svg>
                                Server
                            </span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $project->server->name ?? 'None' }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                </svg>
                                Framework
                            </span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $project->framework ?? 'Unknown' }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                PHP Version
                            </span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $project->php_version ?? 'N/A' }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                Node Version
                            </span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $project->node_version ?? 'N/A' }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                                Branch
                            </span>
                            <span class="font-semibold text-gray-900 dark:text-white font-mono">{{ $project->branch }}</span>
                        </div>
                        <div class="flex items-center justify-between py-3">
                            <span class="text-gray-600 dark:text-gray-400 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Auto Deploy
                            </span>
                            <span class="font-semibold {{ $project->auto_deploy ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white' }}">
                                {{ $project->auto_deploy ? '✅ Enabled' : 'Disabled' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Domains Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg dark:shadow-gray-900/50 overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-500 to-purple-600 p-6">
                        <div class="flex items-center justify-between">
                            <h2 class="text-2xl font-bold text-white flex items-center">
                                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                                </svg>
                                Domains
                            </h2>
                            <button class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg text-sm font-medium transition">
                                + Add
                            </button>
                        </div>
                    </div>
                    <div class="p-6">
                        @if($domains->count() > 0)
                            <div class="space-y-3">
                                @foreach($domains as $domain)
                                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <div class="font-semibold text-gray-900 dark:text-white">{{ $domain->domain }}</div>
                                                <div class="flex items-center space-x-3 mt-2">
                                                    @if($domain->ssl_enabled)
                                                        <span class="inline-flex items-center text-xs text-green-600 dark:text-green-400 font-medium">
                                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                                            </svg>
                                                            SSL Active
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center text-xs text-gray-500 dark:text-gray-400">
                                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd"/>
                                                            </svg>
                                                            No SSL
                                                        </span>
                                                    @endif
                                                    @if($domain->is_primary)
                                                        <span class="px-2 py-1 bg-blue-500 text-white text-xs rounded-full font-medium">Primary</span>
                                                    @endif
                                                    <span class="text-xs text-gray-600 dark:text-gray-400">{{ ucfirst($domain->status) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-12">
                                <svg class="mx-auto h-16 w-16 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                                </svg>
                                <p class="mt-4 text-gray-500 dark:text-gray-400">No domains configured</p>
                                <button class="mt-4 btn btn-primary">+ Add First Domain</button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Docker Tab -->
        <div x-show="activeTab === 'docker'" 
             x-transition 
             class="space-y-8" 
             wire:ignore.self>
            @livewire('projects.project-docker-management', ['project' => $project], key('docker-' . $project->id))
        </div>

        <!-- Environment Tab -->
        <div x-show="activeTab === 'environment'" 
             x-transition 
             class="space-y-8" 
             wire:ignore.self>
            @livewire('projects.project-environment', ['project' => $project], key('env-' . $project->id))
        </div>

        <!-- Git & Commits Tab -->
        <div x-show="activeTab === 'git'" x-transition class="space-y-8" wire:ignore.self>
            <!-- Loading State for Git Tab -->
            <div x-show="!gitPrimed || $wire.commitsLoading"
                 class="bg-white dark:bg-gray-800 rounded-xl shadow-lg dark:shadow-gray-900/50 overflow-hidden p-12">
                <div class="flex flex-col items-center justify-center space-y-6">
                    <div class="relative w-20 h-20">
                        <div class="absolute inset-0 border-4 border-blue-200 dark:border-blue-800 rounded-full"></div>
                        <div class="absolute inset-0 border-4 border-blue-600 dark:border-blue-400 rounded-full border-t-transparent animate-spin"></div>
                    </div>
                    <div class="text-center space-y-2">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Loading Git Data...</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Fetching commits and repository information</p>
                    </div>
                </div>
            </div>

            <div x-show="gitPrimed && !$wire.commitsLoading"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-y-4"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 class="bg-white dark:bg-gray-800 rounded-xl shadow-lg dark:shadow-gray-900/50 overflow-hidden">
                <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-blue-900 p-6 sm:p-8">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                        <div>
                            <h2 class="text-2xl font-bold text-white flex items-center">
                                <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12A4 4 0 108 12a4 4 0 108 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v2m6.364.636l-1.414 1.414M22 12h-2m-.636 6.364l-1.414-1.414M12 20v-2m-6.364.636l1.414-1.414M4 12H2m.636-6.364l1.414 1.414" />
                                </svg>
                                Repository & Git Activity
                            </h2>
                            <p class="text-white/70 text-sm mt-2 max-w-2xl">
                                Keep your deployment in sync with <span class="font-semibold">{{ $project->branch }}</span>. Review the current deployment commit, compare remote changes, and explore recent history – all without leaving DevFlow Pro.
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <button wire:click="checkForUpdates(true)"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-full font-semibold transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M5.63 18.37A9 9 0 1118.37 5.63L19 6M5 19l.63-.63" />
                                </svg>
                                <span wire:loading.remove>Check for Updates</span>
                                <span wire:loading>Checking…</span>
                            </button>
                            <a href="{{ route('deployments.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-slate-900 rounded-full font-semibold hover:bg-slate-100 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h4a1 1 0 011 1v16l-3-2-3 2V4zM14 4h7" />
                                </svg>
                                View All Deployments
                            </a>
                        </div>
                    </div>
                </div>

                <div class="p-6 sm:p-8 space-y-8">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="p-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-4">Repository Overview</h4>
                            <dl class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
                                <div class="flex items-start gap-2">
                                    <dt class="mt-0.5">
                                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M6.343 6.343a4 4 0 115.657 5.657M9 19a3 3 0 006 0" />
                                        </svg>
                                    </dt>
                                    <dd class="flex-1">
                                        <span class="font-semibold text-gray-900 dark:text-white block">Remote</span>
                                        <a href="{{ $project->repository_url }}" target="_blank" class="text-xs text-blue-600 dark:text-blue-400 hover:underline break-all">
                                            {{ $project->repository_url }}
                                        </a>
                                    </dd>
                                </div>
                                <div class="flex items-start gap-2">
                                    <dt class="mt-0.5">
                                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v4a1 1 0 001 1h3v8l7-12h3a1 1 0 001-1V7a1 1 0 00-1-1H4a1 1 0 00-1 1z" />
                                        </svg>
                                    </dt>
                                    <dd class="flex-1">
                                        <span class="font-semibold text-gray-900 dark:text-white block">Branch</span>
                                        <span class="inline-flex items-center gap-2 text-xs font-mono px-2 py-1 bg-purple-100 dark:bg-purple-900/40 rounded-full text-purple-700 dark:text-purple-200">
                                            {{ $project->branch }}
                                        </span>
                                    </dd>
                                </div>
                                <div class="flex items-start gap-2">
                                    <dt class="mt-0.5">
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18" />
                                        </svg>
                                    </dt>
                                    <dd class="flex-1">
                                        <span class="font-semibold text-gray-900 dark:text-white block">Root Directory</span>
                                        <code class="text-xs bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-200 px-2 py-1 rounded-lg">{{ $project->root_directory ?? '/' }}</code>
                                    </dd>
                                </div>
                                <div class="flex items-start gap-2">
                                    <dt class="mt-0.5">
                                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h8l4 4v13a2 2 0 01-2 2z" />
                                        </svg>
                                    </dt>
                                    <dd class="flex-1">
                                        <span class="font-semibold text-gray-900 dark:text-white block">Repository Notes</span>
                                        <span class="text-xs leading-relaxed text-gray-600 dark:text-gray-400">
                                            Auto deploy is {{ $project->auto_deploy ? 'enabled – new commits trigger deployments.' : 'disabled – deployments run manually.' }}
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div class="lg:col-span-2 space-y-6">
                            @if($updateStatus)
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="p-5 rounded-xl border border-blue-200 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800">
                                        <h5 class="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300 mb-2">Local (Deployed)</h5>
                                        <code class="text-sm font-mono bg-blue-600 text-white px-2.5 py-1 rounded-lg shadow">{{ $updateStatus['local_meta']['short_hash'] ?? substr($updateStatus['local_commit'] ?? 'unknown', 0, 7) }}</code>
                                        <p class="mt-3 text-sm font-medium text-blue-900 dark:text-blue-100 leading-snug">
                                            {{ $updateStatus['local_meta']['message'] ?? 'Local commit metadata unavailable.' }}
                                        </p>
                                        @if(isset($updateStatus['local_meta']['author']))
                                            <p class="mt-2 text-xs text-blue-700 dark:text-blue-300">
                                                {{ $updateStatus['local_meta']['author'] }} • {{ \Carbon\Carbon::parse($updateStatus['local_meta']['date'])->diffForHumans() }}
                                            </p>
                                        @endif
                                    </div>
                                    <div class="p-5 rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-900/20 dark:border-emerald-700">
                                        <h5 class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300 mb-2">Remote (origin/{{ $project->branch }})</h5>
                                        <code class="text-sm font-mono bg-emerald-600 text-white px-2.5 py-1 rounded-lg shadow">{{ $updateStatus['remote_meta']['short_hash'] ?? substr($updateStatus['remote_commit'] ?? 'unknown', 0, 7) }}</code>
                                        <p class="mt-3 text-sm font-medium text-emerald-900 dark:text-emerald-100 leading-snug">
                                            {{ $updateStatus['remote_meta']['message'] ?? 'Remote commit metadata unavailable.' }}
                                        </p>
                                        @if(isset($updateStatus['remote_meta']['author']))
                                            <p class="mt-2 text-xs text-emerald-700 dark:text-emerald-300">
                                                {{ $updateStatus['remote_meta']['author'] }} • {{ \Carbon\Carbon::parse($updateStatus['remote_meta']['date'])->diffForHumans() }}
                                            </p>
                                        @endif
                                    </div>
                                    <div class="p-5 rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-700">
                                        <h5 class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300 mb-2">Sync Status</h5>
                                        <p class="text-2xl font-bold text-amber-700 dark:text-amber-300">
                                            @if($updateStatus['up_to_date'])
                                                Up-to-date ✅
                                            @else
                                                {{ $updateStatus['commits_behind'] }} commit{{ $updateStatus['commits_behind'] === 1 ? '' : 's' }} behind
                                            @endif
                                        </p>
                                        <p class="mt-3 text-xs text-amber-700 dark:text-amber-300 leading-relaxed">
                                            Local: <code class="bg-amber-200/70 dark:bg-amber-900/40 px-1.5 py-0.5 rounded font-mono">{{ $updateStatus['local_commit'] }}</code><br>
                                            Remote: <code class="bg-amber-200/70 dark:bg-amber-900/40 px-1.5 py-0.5 rounded font-mono">{{ $updateStatus['remote_commit'] }}</code>
                                        </p>
                                        @unless($updateStatus['up_to_date'])
                                            <p class="mt-3 text-xs text-amber-600 dark:text-amber-200">Trigger a deployment to bring this project in sync with origin/{{ $project->branch }}.</p>
                                        @endunless
                                    </div>
                                </div>
                            @endif

                            @if($project->current_commit_hash)
                                <div class="p-6 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border border-blue-200 dark:border-blue-700 rounded-xl">
                                    <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-3 flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                        </svg>
                                        Currently Deployed Commit
                                    </h4>
                                    <div class="flex flex-wrap items-start gap-4">
                                        <code class="text-sm bg-blue-600 text-white px-3 py-1.5 rounded-lg font-mono font-bold">{{ substr($project->current_commit_hash, 0, 7) }}</code>
                                        <div class="flex-1 min-w-[12rem]">
                                            <p class="text-base font-medium text-blue-900 dark:text-blue-200">{{ $project->current_commit_message }}</p>
                                            <p class="text-sm text-blue-700 dark:text-blue-400 mt-2 flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ $project->last_commit_at ? $project->last_commit_at->diffForHumans() : 'Unknown time' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if(count($commits) > 0)
                        @php
                            $commitPages = max(1, (int) ceil(max(0, $commitTotal) / $commitPerPage));
                        @endphp
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                Showing <span class="font-semibold text-gray-900 dark:text-white">{{ $this->commitRange['start'] }}</span>
                                to <span class="font-semibold text-gray-900 dark:text-white">{{ $this->commitRange['end'] }}</span>
                                of <span class="font-semibold text-gray-900 dark:text-white">{{ $commitTotal }}</span> commits.
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Per page</label>
                                <select wire:change="setCommitPerPage($event.target.value)" class="border border-gray-300 dark:border-gray-600 rounded-lg px-2 py-1.5 text-sm bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 focus:ring-blue-500 focus:border-blue-500">
                                    @foreach([5, 8, 10, 15] as $size)
                                        <option value="{{ $size }}" @selected($commitPerPage === $size)>{{ $size }}</option>
                                    @endforeach
                                </select>
                                <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                    <button wire:click="firstCommitPage" wire:loading.attr="disabled" @disabled($commitPage <= 1)
                                            class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition disabled:opacity-40 disabled:cursor-not-allowed">« First</button>
                                    <button wire:click="previousCommitPage" wire:loading.attr="disabled" @disabled($commitPage <= 1)
                                            class="px-2.5 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition disabled:opacity-40 disabled:cursor-not-allowed">‹ Prev</button>
                                    <span>Page <span class="font-semibold text-gray-900 dark:text-white">{{ $commitPage }}</span> of <span class="font-semibold text-gray-900 dark:text-white">{{ $commitPages }}</span></span>
                                    <button wire:click="nextCommitPage" wire:loading.attr="disabled" @disabled($commitPage >= $commitPages)
                                            class="px-2.5 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition disabled:opacity-40 disabled:cursor-not-allowed">Next ›</button>
                                    <button wire:click="lastCommitPage" wire:loading.attr="disabled" @disabled($commitPage >= $commitPages)
                                            class="px-2 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition disabled:opacity-40 disabled:cursor-not-allowed">Last »</button>
                                </div>
                            </div>
                        </div>

                        <h4 class="text-lg font-bold text-gray-900 dark:text-white mt-2 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Recent Commits on <span class="font-mono ml-2 text-green-600 dark:text-green-400">{{ $project->branch }}</span>
                        </h4>

                        <div class="relative pl-6">
                            <span class="absolute left-2 top-0 bottom-0 w-px bg-gradient-to-b from-green-200 via-blue-200 to-purple-200 dark:from-green-700/60 dark:via-blue-700/60 dark:to-purple-700/60"></span>
                            <div class="space-y-4">
                                @foreach($commits as $commit)
                                    <div class="relative pl-6"
                                         wire:key="commit-{{ $commit['hash'] }}">
                                        <span class="absolute left-0 top-2 w-3 h-3 rounded-full border-2 border-white dark:border-gray-900 bg-gradient-to-r from-green-500 via-blue-500 to-purple-500 shadow"></span>
                                        <div class="p-5 bg-gray-50 dark:bg-gray-700/40 rounded-xl border border-gray-200 dark:border-gray-600 hover:-translate-y-0.5 transition-transform">
                                            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                                <div class="flex items-center gap-3">
                                                    <code class="text-xs bg-slate-900 text-white px-3 py-1.5 rounded-lg font-mono">{{ $commit['short_hash'] }}</code>
                                                    <button type="button"
                                                            onclick="navigator.clipboard.writeText('{{ $commit['hash'] }}')"
                                                            class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">
                                                        Copy full hash
                                                    </button>
                                                </div>
                                                <span class="inline-flex items-center gap-2 text-xs font-semibold text-gray-500 dark:text-gray-400">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    {{ \Carbon\Carbon::createFromTimestamp($commit['timestamp'])->format('M d, Y • H:i') }}
                                                    <span class="text-[11px] text-gray-400">({{ \Carbon\Carbon::createFromTimestamp($commit['timestamp'])->diffForHumans() }})</span>
                                                </span>
                                            </div>
                                            <p class="mt-3 text-sm font-medium text-gray-900 dark:text-white leading-relaxed">{{ $commit['message'] }}</p>
                                            <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-gray-600 dark:text-gray-400">
                                                <span class="inline-flex items-center gap-2 px-2 py-1 bg-gray-200/70 dark:bg-gray-700/60 rounded-lg">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                                    </svg>
                                                    {{ $commit['author'] }}
                                                </span>
                                                <span class="inline-flex items-center gap-2 px-2 py-1 bg-gray-200/40 dark:bg-gray-700/40 rounded-lg">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.88 3.549l3.536 3.536-9.193 9.193a4 4 0 01-1.414.943l-3.086.924.924-3.086a4 4 0 01.943-1.414l9.29-9.29z" />
                                                    </svg>
                                                    {{ $commit['email'] }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-gray-500 dark:text-gray-400">
                            <span>Page {{ $commitPage }} of {{ $commitPages }}</span>
                            <div class="flex items-center gap-2">
                                <button wire:click="firstCommitPage" wire:loading.attr="disabled" @disabled($commitPage <= 1)
                                        class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition disabled:opacity-40 disabled:cursor-not-allowed">First</button>
                                <button wire:click="previousCommitPage" wire:loading.attr="disabled" @disabled($commitPage <= 1)
                                        class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition disabled:opacity-40 disabled:cursor-not-allowed">Previous</button>
                                <button wire:click="nextCommitPage" wire:loading.attr="disabled" @disabled($commitPage >= $commitPages)
                                        class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition disabled:opacity-40 disabled:cursor-not-allowed">Next</button>
                                <button wire:click="lastCommitPage" wire:loading.attr="disabled" @disabled($commitPage >= $commitPages)
                                        class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition disabled:opacity-40 disabled:cursor-not-allowed">Last</button>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-16">
                            <svg class="mx-auto h-20 w-20 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                            </svg>
                            <p class="mt-4 text-gray-500 dark:text-gray-400 text-lg">No commit history available</p>
                            <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">Deploy the project first to start tracking commits.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Logs Tab -->
        <div x-show="activeTab === 'logs'" x-transition class="space-y-8" wire:ignore.self>
            @livewire('projects.project-logs', ['project' => $project], key('logs-' . $project->id))
        </div>

        <!-- Deployments Tab -->
        <div x-show="activeTab === 'deployments'" x-transition class="space-y-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg dark:shadow-gray-900/50 overflow-hidden">
                <div class="bg-gradient-to-r from-orange-500 to-red-500 p-6">
                    <h2 class="text-2xl font-bold text-white flex items-center">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Deployment History
                    </h2>
                    <p class="text-white/80 text-sm mt-2">Track all deployments with detailed status and logs</p>
                </div>
                <div class="p-6">
                    @if($deployments->count() > 0)
                        <div class="space-y-4">
                            @foreach($deployments as $deployment)
                                <div class="p-5 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-all border border-gray-200 dark:border-gray-600 hover:shadow-md">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-4 mb-3">
                                                <span class="px-4 py-1.5 rounded-full text-sm font-bold shadow-sm
                                                    @if($deployment->status === 'success') bg-gradient-to-r from-green-400 to-green-500 text-white
                                                    @elseif($deployment->status === 'failed') bg-gradient-to-r from-red-400 to-red-500 text-white
                                                    @elseif($deployment->status === 'running') bg-gradient-to-r from-yellow-400 to-yellow-500 text-white animate-pulse
                                                    @else bg-gradient-to-r from-gray-400 to-gray-500 text-white
                                                    @endif">
                                                    {{ ucfirst($deployment->status) }}
                                                </span>
                                                @if($deployment->commit_hash)
                                                    <code class="text-xs bg-gray-700 dark:bg-gray-600 text-white px-3 py-1.5 rounded-lg font-mono font-bold">
                                                        {{ substr($deployment->commit_hash, 0, 7) }}
                                                    </code>
                                                @endif
                                            </div>
                                            <p class="text-base font-medium text-gray-900 dark:text-white mb-2">
                                                {{ $deployment->commit_message ?? 'No commit message' }}
                                            </p>
                                            <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                                                <span class="flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                                    </svg>
                                                    {{ $deployment->created_at->diffForHumans() }}
                                                </span>
                                                @if($deployment->duration_seconds)
                                                    <span class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                                        </svg>
                                                        Duration: {{ number_format($deployment->duration_seconds / 60, 1) }} min
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <a href="{{ route('deployments.show', $deployment) }}" 
                                           class="px-6 py-3 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg font-semibold transition-all transform hover:scale-105 shadow">
                                            View Details →
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-16">
                            <svg class="mx-auto h-20 w-20 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="mt-4 text-gray-500 dark:text-gray-400 text-lg">No deployments yet</p>
                            <button wire:click="$set('showDeployModal', true)" class="mt-6 btn btn-primary btn-lg">
                                🚀 Start First Deployment
                            </button>
                        </div>
                    @endif
                </div>
            </div>
            <div class="mt-6">
                {{ $deployments->onEachSide(1)->links() }}
            </div>
        </div>
    </div>

    <!-- Deploy Modal -->
    @if($showDeployModal)
        <div class="fixed inset-0 bg-black/50 dark:bg-black/70 backdrop-blur-sm overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4" 
             wire:click="$set('showDeployModal', false)">
            <div class="relative mx-auto border border-gray-200 dark:border-gray-700 w-full max-w-lg shadow-2xl rounded-2xl bg-white dark:bg-gray-800 transform transition-all" 
                 @click.stop>
                <div class="p-8">
                    <div class="flex items-center justify-center w-16 h-16 mx-auto bg-gradient-to-br from-blue-500 to-purple-600 rounded-full mb-6">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3 text-center">Deploy Project</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6 text-center">
                        This will deploy the latest changes from the <span class="font-mono font-semibold text-blue-600 dark:text-blue-400">{{ $project->branch }}</span> branch.
                    </p>

                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-6">
                        <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-2">Deployment will:</h4>
                        <ul class="text-sm text-blue-800 dark:text-blue-400 space-y-1">
                            <li>✓ Pull latest code from GitHub</li>
                            <li>✓ Build Docker container (12-18 min)</li>
                            <li>✓ Inject environment variables</li>
                            <li>✓ Start the application</li>
                        </ul>
                    </div>

                    <div class="flex space-x-3">
                        <button wire:click="$set('showDeployModal', false)" 
                                wire:loading.attr="disabled"
                                wire:target="deploy"
                                class="flex-1 px-6 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            Cancel
                        </button>
                        <button wire:click="deploy" 
                                wire:loading.attr="disabled"
                                wire:loading.class="scale-100 cursor-wait"
                                class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg font-bold transition-all transform hover:scale-105 shadow-lg disabled:opacity-75 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="deploy" class="flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                Deploy Now
                            </span>
                            <span wire:loading wire:target="deploy" class="flex items-center justify-center">
                                <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="animate-pulse">Starting deployment...</span>
                            </span>
                        </button>
                    </div>
                    
                    <!-- Loading Overlay -->
                    <div wire:loading wire:target="deploy" class="absolute inset-0 bg-white dark:bg-gray-800 bg-opacity-95 dark:bg-opacity-95 flex items-center justify-center rounded-2xl z-10">
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full mb-4 animate-pulse">
                                <svg class="animate-spin h-10 w-10 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <p class="text-lg font-bold text-gray-900 dark:text-white mb-2">Starting Deployment...</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Please wait, you'll be redirected shortly</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
