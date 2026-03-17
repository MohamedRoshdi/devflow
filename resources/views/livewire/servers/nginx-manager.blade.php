<div>
    {{-- Hero Section --}}
    <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-orange-800 via-amber-900 to-yellow-800 p-8 shadow-2xl overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="nginx-pattern" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                        <path d="M0 0h40v40H0z" fill="none"/>
                        <path d="M20 0v40M0 20h40" stroke="currentColor" stroke-width="0.5" class="text-white"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#nginx-pattern)"/>
            </svg>
        </div>

        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex items-start gap-4">
                    <div class="p-4 bg-white/10 backdrop-blur-md rounded-2xl">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                        </svg>
                    </div>

                    <div>
                        <h1 class="text-3xl font-bold text-white">Nginx Management</h1>
                        <p class="text-orange-100 mt-2">Manage Nginx virtual hosts on <span class="font-semibold">{{ $server->name }}</span></p>

                        <div class="flex items-center gap-3 mt-3">
                            {{-- Nginx Status Badge --}}
                            @if($nginxStatus === 'active')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                    Nginx Active
                                </span>
                            @elseif($nginxStatus === 'inactive')
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-red-500/20 text-red-300 border border-red-500/30">
                                    <span class="w-2 h-2 rounded-full bg-red-400"></span>
                                    Nginx Stopped
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-slate-500/20 text-slate-300 border border-slate-500/30">
                                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                    Status Unknown
                                </span>
                            @endif

                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-white/10 text-white/90">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                </svg>
                                {{ $server->ip_address }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-wrap items-center gap-3">
                    <button
                        wire:click="testConfig"
                        wire:loading.attr="disabled"
                        wire:target="testConfig"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-all duration-200 font-medium disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="testConfig" class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Test Config
                        </span>
                        <span wire:loading wire:target="testConfig" class="flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Testing...
                        </span>
                    </button>

                    <button
                        wire:click="reloadNginx"
                        wire:loading.attr="disabled"
                        wire:target="reloadNginx"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-all duration-200 font-medium disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="reloadNginx" class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Reload
                        </span>
                        <span wire:loading wire:target="reloadNginx" class="flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Reloading...
                        </span>
                    </button>

                    <button
                        wire:click="restartNginx"
                        wire:loading.attr="disabled"
                        wire:target="restartNginx"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-500/20 hover:bg-red-500/30 text-red-200 border border-red-500/30 rounded-xl transition-all duration-200 font-medium disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="restartNginx" class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z"/>
                            </svg>
                            Restart
                        </span>
                        <span wire:loading wire:target="restartNginx" class="flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Restarting...
                        </span>
                    </button>

                    <a href="{{ route('servers.show', $server) }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-all duration-200 font-medium">
                        Back to Server
                    </a>
                </div>
            </div>

            {{-- Config Test Result --}}
            @if($configTestResult)
                <div class="mt-4 p-4 rounded-xl border {{ $configTestPassed ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-200' : 'bg-red-500/10 border-red-500/30 text-red-200' }}">
                    <div class="flex items-start gap-3">
                        @if($configTestPassed)
                            <svg class="w-5 h-5 mt-0.5 flex-shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @else
                            <svg class="w-5 h-5 mt-0.5 flex-shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @endif
                        <pre class="text-sm font-mono whitespace-pre-wrap break-all">{{ $configTestResult }}</pre>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="mb-6 bg-gradient-to-r from-green-500/20 to-emerald-500/20 border border-green-500/30 text-green-400 px-5 py-4 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 bg-gradient-to-r from-red-500/20 to-red-600/20 border border-red-500/30 text-red-400 px-5 py-4 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Stats Bar --}}
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-5">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Available Sites</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ count($sites) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-5">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Enabled Sites</p>
            <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ count($enabledSites) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-5">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Disabled Sites</p>
            <p class="text-3xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ count($sites) - count($enabledSites) }}</p>
        </div>
    </div>

    {{-- Sites List --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Virtual Hosts
            </h2>

            <button
                wire:click="loadSites"
                wire:loading.attr="disabled"
                wire:target="loadSites"
                class="inline-flex items-center gap-2 px-3 py-1.5 text-sm bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-all"
            >
                <svg class="w-4 h-4" wire:loading.class="animate-spin" wire:target="loadSites" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Refresh
            </button>
        </div>

        @if(count($sites) === 0)
            <div class="px-6 py-16 text-center">
                <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="text-gray-500 dark:text-gray-400 font-medium text-lg">No sites found</h3>
                <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">No files in <code class="font-mono">/etc/nginx/sites-available/</code></p>
            </div>
        @else
            <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                @foreach($sites as $site)
                    @php($isEnabled = in_array($site, $enabledSites, true))
                    <div wire:key="site-{{ $site }}" class="px-6 py-4 flex flex-col sm:flex-row sm:items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        {{-- Site name and status --}}
                        <div class="flex items-center gap-3 flex-1 min-w-0">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0
                                {{ $isEnabled ? 'bg-emerald-500/10' : 'bg-gray-100 dark:bg-gray-700' }}">
                                <svg class="w-5 h-5 {{ $isEnabled ? 'text-emerald-500' : 'text-gray-400 dark:text-gray-500' }}"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-gray-900 dark:text-white truncate">{{ $site }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">/etc/nginx/sites-available/{{ $site }}</p>
                            </div>
                            @if($isEnabled)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30 flex-shrink-0">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Enabled
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-600 flex-shrink-0">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                    Disabled
                                </span>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 flex-shrink-0">
                            {{-- View Config --}}
                            <button
                                wire:click="viewConfig('{{ $site }}')"
                                wire:loading.attr="disabled"
                                wire:target="viewConfig('{{ $site }}')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-blue-500/10 hover:bg-blue-500/20 text-blue-600 dark:text-blue-400 border border-blue-500/20 rounded-lg transition-all"
                                title="View Config"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                View
                            </button>

                            {{-- Enable / Disable toggle --}}
                            @if($isEnabled)
                                <button
                                    wire:click="disableSite('{{ $site }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="disableSite('{{ $site }}')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/20 rounded-lg transition-all"
                                    title="Disable Site"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                    </svg>
                                    Disable
                                </button>
                            @else
                                <button
                                    wire:click="enableSite('{{ $site }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="enableSite('{{ $site }}')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 rounded-lg transition-all"
                                    title="Enable Site"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Enable
                                </button>
                            @endif

                            {{-- Delete --}}
                            <button
                                wire:click="confirmDeleteSite('{{ $site }}')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 border border-red-500/20 rounded-lg transition-all"
                                title="Delete Site"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Delete
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Config Viewer Modal --}}
    @if($showConfigModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="config-modal-title"
        >
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" wire:click="closeConfigModal"></div>

            <div class="relative w-full max-w-4xl bg-gray-900 rounded-2xl shadow-2xl border border-gray-700/50 overflow-hidden">
                {{-- Modal Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700/50 bg-gray-800/80">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-orange-500/20 flex items-center justify-center">
                            <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 id="config-modal-title" class="text-white font-semibold">{{ $selectedSite }}</h3>
                            <p class="text-gray-400 text-xs">/etc/nginx/sites-available/{{ $selectedSite }}</p>
                        </div>
                    </div>
                    <button
                        wire:click="closeConfigModal"
                        class="p-2 text-gray-400 hover:text-white rounded-lg hover:bg-gray-700 transition-colors"
                        aria-label="Close"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Config Content --}}
                <div class="p-6 overflow-auto max-h-[60vh]">
                    <pre class="font-mono text-sm text-gray-300 leading-relaxed whitespace-pre-wrap break-all">{{ $siteConfig }}</pre>
                </div>

                {{-- Modal Footer --}}
                <div class="px-6 py-4 border-t border-gray-700/50 bg-gray-800/80 flex justify-end">
                    <button
                        wire:click="closeConfigModal"
                        class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-xl transition-all font-medium"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Password Confirmation Modal --}}
    @if($showPasswordConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             role="dialog"
             aria-modal="true"
             aria-labelledby="password-confirm-title">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" wire:click="cancelConfirmation"></div>

            <div class="relative w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700/50 overflow-hidden">
                <div class="flex items-center gap-4 px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                    <div class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-500/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 id="password-confirm-title" class="text-gray-900 dark:text-white font-semibold">Confirm Destructive Action</h3>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mt-0.5">{{ $pendingActionLabel }}</p>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Enter your password to continue:</p>
                    <div>
                        <input wire:model="confirmPassword"
                               type="password"
                               placeholder="Your password"
                               autofocus
                               wire:keydown.enter="executeConfirmedAction"
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition">
                        @error('confirmPassword')
                            <p class="mt-1.5 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <button wire:click="cancelConfirmation"
                            class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                        Cancel
                    </button>
                    <button wire:click="executeConfirmedAction"
                            wire:loading.attr="disabled"
                            wire:target="executeConfirmedAction"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white rounded-xl text-sm font-medium transition-colors">
                        <svg class="w-4 h-4 animate-spin" wire:loading wire:target="executeConfirmedAction" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Confirm Delete
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
