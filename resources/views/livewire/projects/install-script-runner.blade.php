<div>
    {{-- Run Install Script Button --}}
    <button wire:click="openModal"
            type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/30 hover:text-emerald-300 transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
        </svg>
        {{ __('install_script.run_install_script') }}
    </button>

    {{-- Modal --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            {{-- Background overlay --}}
            <div class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm transition-opacity" wire:click="closeModal"></div>

            {{-- Modal panel --}}
            <div class="relative inline-block align-bottom bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-700">
                {{-- Header --}}
                <div class="bg-gray-800/50 px-6 py-4 border-b border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-white flex items-center gap-2" id="modal-title">
                            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            {{ __('install_script.run_install_script') }}
                        </h3>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <p class="mt-1 text-sm text-gray-400">{{ $project->name }}</p>
                </div>

                {{-- Content --}}
                <div class="px-6 py-4 space-y-4">
                    {{-- Loading state --}}
                    @if($isChecking)
                    <div class="flex items-center justify-center py-8">
                        <svg class="animate-spin h-8 w-8 text-emerald-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span class="ml-3 text-gray-300">{{ __('install_script.checking_script') }}</span>
                    </div>
                    @elseif(!$hasInstallScript)
                    {{-- No install.sh found --}}
                    <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-yellow-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div>
                                <h4 class="font-medium text-yellow-400">{{ __('install_script.no_script_found') }}</h4>
                                <p class="mt-1 text-sm text-gray-400">{{ __('install_script.no_script_found_desc') }}</p>
                            </div>
                        </div>
                    </div>
                    @else
                    {{-- Script found - show configuration options --}}
                    <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl p-4 mb-4">
                        <div class="flex items-center gap-2 text-emerald-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="font-medium">{{ __('install_script.script_found') }}</span>
                        </div>
                    </div>

                    {{-- Configuration Options --}}
                    <div class="space-y-4">
                        {{-- Production Mode Toggle --}}
                        <div class="flex items-center justify-between p-3 bg-gray-700/30 rounded-xl">
                            <div>
                                <label class="font-medium text-white">{{ __('install_script.production_mode') }}</label>
                                <p class="text-xs text-gray-400">{{ __('install_script.production_mode_desc') }}</p>
                            </div>
                            <button type="button"
                                    wire:click="$toggle('productionMode')"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $productionMode ? 'bg-emerald-500' : 'bg-gray-600' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $productionMode ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </div>

                        {{-- Domain (required for production) --}}
                        @if($productionMode)
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">{{ __('install_script.domain') }} *</label>
                            <input type="text"
                                   wire:model="domain"
                                   class="w-full px-3 py-2 bg-gray-700/50 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                   placeholder="example.com">
                            @error('domain') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Email (required for production) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">{{ __('install_script.email') }} *</label>
                            <input type="email"
                                   wire:model="email"
                                   class="w-full px-3 py-2 bg-gray-700/50 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                   placeholder="admin@example.com">
                            @error('email') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                        </div>
                        @endif

                        {{-- Database Driver --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">{{ __('install_script.database') }}</label>
                            <select wire:model="dbDriver"
                                    class="w-full px-3 py-2 bg-gray-700/50 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                <option value="pgsql">PostgreSQL 16</option>
                                <option value="mysql">MySQL 8</option>
                            </select>
                        </div>

                        {{-- Database Password (optional) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">{{ __('install_script.db_password') }}</label>
                            <input type="password"
                                   wire:model="dbPassword"
                                   class="w-full px-3 py-2 bg-gray-700/50 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                   placeholder="{{ __('install_script.auto_generated') }}">
                            <p class="mt-1 text-xs text-gray-400">{{ __('install_script.db_password_hint') }}</p>
                        </div>

                        {{-- Skip SSL --}}
                        @if($productionMode)
                        <div class="flex items-center justify-between p-3 bg-gray-700/30 rounded-xl">
                            <div>
                                <label class="font-medium text-white">{{ __('install_script.skip_ssl') }}</label>
                                <p class="text-xs text-gray-400">{{ __('install_script.skip_ssl_desc') }}</p>
                            </div>
                            <button type="button"
                                    wire:click="$toggle('skipSsl')"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $skipSsl ? 'bg-emerald-500' : 'bg-gray-600' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $skipSsl ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </div>
                        @endif
                    </div>
                    @endif

                    {{-- Output Area --}}
                    @if($runOutput)
                    <div class="mt-4">
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium text-gray-300">{{ __('install_script.output') }}</label>
                            @if($runStatus === 'success')
                            <span class="text-xs text-emerald-400 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ __('install_script.completed') }}
                            </span>
                            @elseif($runStatus === 'error')
                            <span class="text-xs text-red-400 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                {{ __('install_script.failed') }}
                            </span>
                            @elseif($runStatus === 'running')
                            <span class="text-xs text-yellow-400 flex items-center gap-1">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                {{ __('install_script.running') }}
                            </span>
                            @endif
                        </div>
                        <pre class="bg-gray-900 rounded-lg p-4 text-sm text-gray-300 overflow-x-auto max-h-64 overflow-y-auto font-mono">{{ $runOutput }}</pre>
                    </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="bg-gray-800/50 px-6 py-4 border-t border-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        @if($hasInstallScript && !$isChecking)
                        <button wire:click="viewScript"
                                type="button"
                                class="px-4 py-2 text-sm font-medium text-gray-300 hover:text-white transition-colors"
                                {{ $isRunning ? 'disabled' : '' }}>
                            {{ __('install_script.view_script') }}
                        </button>
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        <button wire:click="closeModal"
                                type="button"
                                class="px-4 py-2 text-sm font-medium text-gray-300 hover:text-white transition-colors">
                            {{ __('install_script.close') }}
                        </button>
                        @if($hasInstallScript && !$isChecking)
                        <button wire:click="runInstallScript"
                                type="button"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                {{ $isRunning ? 'disabled' : '' }}>
                            @if($isRunning)
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            {{ __('install_script.running') }}
                            @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ __('install_script.run_script') }}
                            @endif
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
