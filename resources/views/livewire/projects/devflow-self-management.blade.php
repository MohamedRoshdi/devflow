<div class="min-h-screen" x-data="{ activeTab: @entangle('activeTab') }">
    {{-- Animated Background --}}
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-gradient-to-br from-emerald-500/5 via-teal-500/5 to-cyan-500/5 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-gradient-to-tr from-violet-500/5 via-purple-500/5 to-fuchsia-500/5 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-gradient-to-r from-blue-500/3 to-indigo-500/3 rounded-full blur-3xl"></div>
    </div>

    {{-- Hero Header --}}
    <div class="relative mb-8">
        <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 rounded-3xl overflow-hidden border border-slate-700/50 shadow-2xl">
            {{-- Grid Pattern --}}
            <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23fff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>

            <div class="relative p-6 lg:p-8">
                {{-- Top Row: Logo, Title, Status --}}
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                    <div class="flex items-start gap-4">
                        {{-- Animated Logo --}}
                        <div class="relative">
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-400 via-teal-500 to-cyan-600 flex items-center justify-center shadow-xl shadow-emerald-500/30 transform hover:scale-105 transition-transform">
                                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                </svg>
                            </div>
                            <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full {{ $quickStats['maintenance_mode'] ? 'bg-amber-500' : 'bg-emerald-500' }} border-2 border-slate-900 flex items-center justify-center">
                                @if(!$quickStats['maintenance_mode'])
                                    <span class="w-2 h-2 rounded-full bg-white animate-ping"></span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <h1 class="text-2xl lg:text-3xl font-bold text-white tracking-tight">DevFlow Pro</h1>
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider {{ $quickStats['maintenance_mode'] ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' : 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' }}">
                                    {{ $quickStats['maintenance_mode'] ? 'Maintenance' : 'Live' }}
                                </span>
                            </div>
                            <p class="text-slate-400 text-sm">Self-Management Console</p>

                            {{-- Version Pills --}}
                            <div class="flex flex-wrap items-center gap-2 mt-3">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-800/80 text-slate-300 border border-slate-700/50">
                                    <svg class="w-3.5 h-3.5 text-red-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm0 18c-4.418 0-8-3.582-8-8s3.582-8 8-8 8 3.582 8 8-3.582 8-8 8z"/><path d="M12 6c-3.309 0-6 2.691-6 6s2.691 6 6 6 6-2.691 6-6-2.691-6-6-6z"/></svg>
                                    Laravel {{ $quickStats['laravel_version'] }}
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-800/80 text-slate-300 border border-slate-700/50">
                                    <svg class="w-3.5 h-3.5 text-indigo-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2z"/></svg>
                                    PHP {{ $quickStats['php_version'] }}
                                </span>
                                @if($quickStats['is_git_repo'])
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-800/80 text-slate-300 border border-slate-700/50">
                                        <svg class="w-3.5 h-3.5 text-orange-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                                        Git Enabled
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Quick Actions --}}
                    <div class="flex flex-wrap gap-2">
                        <button wire:click="refreshStats"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm bg-slate-800/80 text-slate-300 border border-slate-700/50 hover:bg-slate-700/80 hover:text-white transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Navigation Tabs --}}
    <div class="mb-6">
        <div class="bg-slate-900/50 border border-slate-700/50 rounded-2xl p-2 backdrop-blur-sm">
            <div class="flex flex-wrap gap-2">
                <button @click="activeTab = 'overview'" :class="{ 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-lg': activeTab === 'overview', 'text-slate-400 hover:text-white hover:bg-slate-800/50': activeTab !== 'overview' }"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zM14 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1h-4a1 1 0 01-1-1v-3z"/>
                    </svg>
                    Overview
                </button>
                <button @click="activeTab = 'git'" :class="{ 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-lg': activeTab === 'git', 'text-slate-400 hover:text-white hover:bg-slate-800/50': activeTab !== 'git' }"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm transition-all">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                    Git
                </button>
                <button @click="activeTab = 'system'" :class="{ 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-lg': activeTab === 'system', 'text-slate-400 hover:text-white hover:bg-slate-800/50': activeTab !== 'system' }"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                    </svg>
                    System
                </button>
                <button @click="activeTab = 'services'" :class="{ 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-lg': activeTab === 'services', 'text-slate-400 hover:text-white hover:bg-slate-800/50': activeTab !== 'services' }"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Services
                </button>
                <button @click="activeTab = 'cache'" :class="{ 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-lg': activeTab === 'cache', 'text-slate-400 hover:text-white hover:bg-slate-800/50': activeTab !== 'cache' }"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                    </svg>
                    Cache
                </button>
                <button @click="activeTab = 'logs'" :class="{ 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-lg': activeTab === 'logs', 'text-slate-400 hover:text-white hover:bg-slate-800/50': activeTab !== 'logs' }"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Logs
                </button>
                <button @click="activeTab = 'deployment'" :class="{ 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-lg': activeTab === 'deployment', 'text-slate-400 hover:text-white hover:bg-slate-800/50': activeTab !== 'deployment' }"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    Deploy
                </button>
            </div>
        </div>
    </div>

    {{-- Tab Content --}}
    <div class="relative">
        {{-- Overview Tab --}}
        <div x-show="activeTab === 'overview'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
            <div class="bg-slate-800/30 border border-slate-700/50 rounded-2xl p-8 text-center">
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-white mb-2">DevFlow Pro Self-Management</h2>
                <p class="text-slate-400 mb-6 max-w-2xl mx-auto">Manage your DevFlow installation, monitor services, handle deployments, and configure system settings all from this centralized console.</p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto">
                    <div class="bg-slate-900/50 rounded-xl p-4 border border-slate-700/50">
                        <div class="text-2xl font-bold text-emerald-400">{{ $quickStats['cache_driver'] }}</div>
                        <div class="text-xs text-slate-400 mt-1">Cache Driver</div>
                    </div>
                    <div class="bg-slate-900/50 rounded-xl p-4 border border-slate-700/50">
                        <div class="text-2xl font-bold text-teal-400">{{ $quickStats['queue_driver'] }}</div>
                        <div class="text-xs text-slate-400 mt-1">Queue Driver</div>
                    </div>
                    <div class="bg-slate-900/50 rounded-xl p-4 border border-slate-700/50">
                        <div class="text-2xl font-bold text-cyan-400">{{ $quickStats['is_git_repo'] ? 'Yes' : 'No' }}</div>
                        <div class="text-xs text-slate-400 mt-1">Git Repository</div>
                    </div>
                    <div class="bg-slate-900/50 rounded-xl p-4 border border-slate-700/50">
                        <div class="text-2xl font-bold {{ $quickStats['maintenance_mode'] ? 'text-amber-400' : 'text-emerald-400' }}">{{ $quickStats['maintenance_mode'] ? 'Down' : 'Live' }}</div>
                        <div class="text-xs text-slate-400 mt-1">Site Status</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Git Tab --}}
        <div x-show="activeTab === 'git'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
            @livewire('projects.dev-flow.git-manager')
        </div>

        {{-- System Tab --}}
        <div x-show="activeTab === 'system'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
            @livewire('projects.dev-flow.system-info')
        </div>

        {{-- Services Tab --}}
        <div x-show="activeTab === 'services'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
            @livewire('projects.dev-flow.service-manager')
        </div>

        {{-- Cache Tab --}}
        <div x-show="activeTab === 'cache'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
            @livewire('projects.dev-flow.cache-manager')
        </div>

        {{-- Logs Tab --}}
        <div x-show="activeTab === 'logs'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
            @livewire('projects.dev-flow.log-viewer')
        </div>

        {{-- Deployment Tab --}}
        <div x-show="activeTab === 'deployment'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
            @livewire('projects.dev-flow.deployment-actions')
        </div>
    </div>
</div>
