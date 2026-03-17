<div class="space-y-6">
    {{-- Header with Breadcrumbs --}}
    <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-gray-200 dark:border-slate-700/50 overflow-hidden">
        <div class="bg-gradient-to-r from-teal-600 to-cyan-600 p-4 border-b border-teal-500/30">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                    {{ __('project_files.title') }}
                </h2>
                <button wire:click="loadFiles"
                        wire:loading.attr="disabled"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium bg-white/20 hover:bg-white/30 text-white transition-all disabled:opacity-50">
                    <span wire:loading.remove wire:target="loadFiles">{{ __('project_files.refresh') }}</span>
                    <span wire:loading wire:target="loadFiles" class="flex items-center gap-1">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        {{ __('project_files.loading') }}
                    </span>
                </button>
            </div>
        </div>

        {{-- Breadcrumb Navigation --}}
        <div class="p-4 bg-gray-50 dark:bg-slate-900/30 border-b border-gray-200 dark:border-slate-700/30">
            <nav class="flex items-center gap-2 text-sm overflow-x-auto">
                @foreach($this->breadcrumbs as $index => $crumb)
                    @if($index > 0)
                        <svg class="w-4 h-4 text-gray-400 dark:text-slate-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    @endif
                    @if($loop->last)
                        <span class="text-teal-600 dark:text-teal-400 font-medium flex-shrink-0">{{ $crumb['name'] }}</span>
                    @else
                        <button wire:click="navigateToBreadcrumb('{{ $crumb['path'] }}')"
                                class="text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white transition-colors flex-shrink-0">
                            {{ $crumb['name'] }}
                        </button>
                    @endif
                @endforeach
            </nav>
        </div>
    </div>

    {{-- Error Message --}}
    @if($error)
        <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <h4 class="font-medium text-red-400">{{ __('project_files.error') }}</h4>
                    <p class="mt-1 text-sm text-gray-400">{{ $error }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- File List --}}
    <div class="bg-white/80 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-gray-200 dark:border-slate-700/50 overflow-hidden">
        @if($isLoading)
            <div class="flex items-center justify-center py-16">
                <svg class="animate-spin h-8 w-8 text-teal-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span class="ml-3 text-gray-500 dark:text-slate-400">{{ __('project_files.loading_files') }}</span>
            </div>
        @elseif(count($files) === 0 && !$error)
            <div class="text-center py-16">
                <svg class="mx-auto h-16 w-16 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"/>
                </svg>
                <p class="mt-4 text-gray-500 dark:text-slate-400">{{ __('project_files.empty_directory') }}</p>
            </div>
        @else
            {{-- Up directory button --}}
            @if($this->relativePath !== '/')
                <button wire:click="navigateUp"
                        class="w-full flex items-center gap-3 p-4 hover:bg-gray-100 dark:hover:bg-slate-700/30 transition-colors border-b border-gray-200 dark:border-slate-700/30 text-left">
                    <div class="w-10 h-10 rounded-lg bg-gray-200 dark:bg-slate-700/50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-gray-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                    </div>
                    <span class="text-gray-600 dark:text-slate-400 font-medium">..</span>
                    <span class="text-xs text-gray-400 dark:text-slate-500">{{ __('project_files.parent_directory') }}</span>
                </button>
            @endif

            {{-- Files and Directories --}}
            @foreach($files as $file)
                @php
                    $icon = $this->getFileIcon($file['name'], $file['type']);
                @endphp
                <div class="flex items-center gap-3 p-4 hover:bg-gray-100 dark:hover:bg-slate-700/30 transition-colors border-b border-gray-200 dark:border-slate-700/30 last:border-b-0 group">
                    {{-- Icon --}}
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center
                        @if($file['type'] === 'directory') bg-amber-500/20 text-amber-400
                        @elseif($file['type'] === 'symlink') bg-purple-500/20 text-purple-400
                        @elseif(in_array($icon, ['php', 'javascript', 'vue', 'css', 'html'])) bg-blue-500/20 text-blue-400
                        @elseif($icon === 'shell') bg-emerald-500/20 text-emerald-400
                        @elseif($icon === 'image') bg-pink-500/20 text-pink-400
                        @elseif($icon === 'json' || $icon === 'yaml') bg-yellow-500/20 text-yellow-400
                        @else bg-gray-200 dark:bg-slate-700/50 text-gray-500 dark:text-slate-400
                        @endif">
                        @if($file['type'] === 'directory')
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M10 4H4c-1.11 0-2 .89-2 2v12a2 2 0 002 2h16a2 2 0 002-2V8c0-1.11-.9-2-2-2h-8l-2-2z"/>
                            </svg>
                        @elseif($file['type'] === 'symlink')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        @endif
                    </div>

                    {{-- Name and details --}}
                    <div class="flex-1 min-w-0">
                        @if($file['type'] === 'directory')
                            <button wire:click="navigateTo('{{ $file['name'] }}')"
                                    class="font-medium text-gray-900 dark:text-white hover:text-teal-600 dark:hover:text-teal-400 transition-colors truncate block text-left">
                                {{ $file['name'] }}
                            </button>
                        @else
                            <button wire:click="viewFile('{{ $file['name'] }}')"
                                    class="font-medium text-gray-700 dark:text-slate-300 hover:text-teal-600 dark:hover:text-teal-400 transition-colors truncate block text-left">
                                {{ $file['name'] }}
                            </button>
                        @endif
                        <div class="flex items-center gap-3 mt-1 text-xs text-gray-400 dark:text-slate-500">
                            <span>{{ $file['size'] }}</span>
                            <span class="font-mono">{{ $file['permissions'] }}</span>
                            <span>{{ $file['modified'] }}</span>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-2">
                        @if($file['type'] !== 'directory')
                            <button wire:click="viewFile('{{ $file['name'] }}')"
                                    class="p-2 rounded-lg bg-gray-200 dark:bg-slate-700/50 text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-300 dark:hover:bg-slate-600/50 transition-colors"
                                    title="{{ __('project_files.view') }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- File Viewer Modal --}}
    @if($showFileModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- Background overlay --}}
                <div class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm transition-opacity" wire:click="closeFileModal"></div>

                {{-- Modal panel --}}
                <div class="relative inline-block align-bottom bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-gray-700">
                    {{-- Header --}}
                    <div class="bg-gray-800/50 px-6 py-4 border-b border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-white flex items-center gap-2" id="modal-title">
                                <svg class="w-5 h-5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                {{ $selectedFile }}
                            </h3>
                            <button wire:click="closeFileModal" class="text-gray-400 hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <p class="mt-1 text-sm text-gray-400 font-mono">{{ $this->relativePath }}/{{ $selectedFile }}</p>
                    </div>

                    {{-- Content --}}
                    <div class="px-6 py-4">
                        @if($isLoadingFile)
                            <div class="flex items-center justify-center py-8">
                                <svg class="animate-spin h-8 w-8 text-teal-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span class="ml-3 text-gray-300">{{ __('project_files.loading_file') }}</span>
                            </div>
                        @else
                            <pre class="bg-gray-900 rounded-lg p-4 text-sm text-gray-300 overflow-x-auto max-h-[60vh] overflow-y-auto font-mono whitespace-pre-wrap">{{ $fileContent }}</pre>
                        @endif
                    </div>

                    {{-- Footer --}}
                    <div class="bg-gray-800/50 px-6 py-4 border-t border-gray-700 flex justify-end">
                        <button wire:click="closeFileModal"
                                type="button"
                                class="px-4 py-2 text-sm font-medium text-gray-300 hover:text-white transition-colors">
                            {{ __('project_files.close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
