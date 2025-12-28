<div>
    {{-- Trigger Button --}}
    <button wire:click="openModal"
            type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-medium text-sm bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/30 hover:text-emerald-300 transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        {{ __('install_script.generate_script') }}
    </button>

    {{-- Modal --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            {{-- Background overlay --}}
            <div wire:click="closeModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            {{-- Modal panel --}}
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                {{-- Header --}}
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-white flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            {{ __('install_script.title') }} - {{ $project->name }}
                        </h3>
                        <button wire:click="closeModal" class="text-white hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="px-6 py-4">
                    @if(!$showScript)
                    {{-- Configuration Form --}}
                    <div class="space-y-6">
                        {{-- Deployment Mode --}}
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                {{ __('install_script.deployment_mode') }}
                            </h4>
                            <div class="flex items-center space-x-6">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" wire:model.live="productionMode" value="0"
                                           class="form-radio h-5 w-5 text-blue-600">
                                    <span class="ml-2 text-gray-700 dark:text-gray-300">
                                        <span class="font-medium">{{ __('install_script.development') }}</span>
                                        <span class="text-sm text-gray-500 block">{{ __('install_script.development_desc') }}</span>
                                    </span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" wire:model.live="productionMode" value="1"
                                           class="form-radio h-5 w-5 text-emerald-600">
                                    <span class="ml-2 text-gray-700 dark:text-gray-300">
                                        <span class="font-medium">{{ __('install_script.production') }}</span>
                                        <span class="text-sm text-gray-500 block">{{ __('install_script.production_desc') }}</span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        {{-- Production Settings --}}
                        @if($productionMode)
                        <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-emerald-700 dark:text-emerald-300 mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                {{ __('install_script.production_settings') }}
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        {{ __('install_script.domain') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" wire:model="domain"
                                           placeholder="app.example.com"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                                    @error('domain') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        {{ __('install_script.email') }} <span class="text-red-500">*</span>
                                    </label>
                                    <input type="email" wire:model="email"
                                           placeholder="admin@example.com"
                                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                                    @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" wire:model="skipSsl"
                                           class="form-checkbox h-5 w-5 text-emerald-600 rounded">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                        {{ __('install_script.skip_ssl') }}
                                        <span class="text-gray-500">({{ __('install_script.skip_ssl_desc') }})</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                        @endif

                        {{-- Database Configuration --}}
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                {{ __('install_script.database_config') }}
                            </h4>
                            <div class="flex items-center space-x-6">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" wire:model="dbDriver" value="pgsql"
                                           class="form-radio h-5 w-5 text-blue-600">
                                    <span class="ml-2 text-gray-700 dark:text-gray-300">PostgreSQL 16</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" wire:model="dbDriver" value="mysql"
                                           class="form-radio h-5 w-5 text-orange-600">
                                    <span class="ml-2 text-gray-700 dark:text-gray-300">MySQL 8</span>
                                </label>
                            </div>
                        </div>

                        {{-- Security & Services --}}
                        @if($productionMode)
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                {{ __('install_script.security_services') }}
                            </h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" wire:model="enableUfw"
                                           class="form-checkbox h-5 w-5 text-emerald-600 rounded">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">UFW Firewall</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" wire:model="enableFail2ban"
                                           class="form-checkbox h-5 w-5 text-emerald-600 rounded">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Fail2ban</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" wire:model="enableRedis"
                                           class="form-checkbox h-5 w-5 text-red-600 rounded">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Redis</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" wire:model="enableSupervisor"
                                           class="form-checkbox h-5 w-5 text-purple-600 rounded">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Supervisor</span>
                                </label>
                            </div>
                        </div>
                        @endif

                        {{-- Queue Workers --}}
                        @if($enableSupervisor)
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                {{ __('install_script.queue_workers') }}
                            </h4>
                            <div class="flex items-center space-x-4">
                                <input type="range" wire:model.live="queueWorkers" min="1" max="10"
                                       class="w-48 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-600">
                                <span class="text-lg font-semibold text-gray-700 dark:text-gray-300">{{ $queueWorkers }}</span>
                            </div>
                        </div>
                        @endif

                        {{-- Estimated Time --}}
                        <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                            <span>{{ __('install_script.estimated_time') }}: {{ $this->estimatedInstallTime }}</span>
                        </div>
                    </div>
                    @else
                    {{-- Generated Script Display --}}
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $this->scriptLineCount }} {{ __('install_script.lines') }}
                            </div>
                            <div class="flex items-center space-x-2">
                                <button wire:click="copyToClipboard"
                                        class="inline-flex items-center px-3 py-1.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600 text-sm">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                    </svg>
                                    {{ __('install_script.copy') }}
                                </button>
                                <button wire:click="resetScript"
                                        class="inline-flex items-center px-3 py-1.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600 text-sm">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    {{ __('install_script.reconfigure') }}
                                </button>
                            </div>
                        </div>

                        <div class="relative">
                            <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto text-sm max-h-96 overflow-y-auto"><code>{{ $generatedScript }}</code></pre>
                        </div>

                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                            <h5 class="text-sm font-semibold text-blue-700 dark:text-blue-300 mb-2">
                                {{ __('install_script.usage_instructions') }}
                            </h5>
                            <ol class="text-sm text-blue-600 dark:text-blue-400 list-decimal list-inside space-y-1">
                                <li>{{ __('install_script.step_1') }}</li>
                                <li>{{ __('install_script.step_2') }}</li>
                                <li>{{ __('install_script.step_3') }}</li>
                            </ol>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 flex justify-end space-x-3">
                    <button wire:click="closeModal"
                            type="button"
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors">
                        {{ __('install_script.close') }}
                    </button>
                    @if(!$showScript)
                    <button wire:click="generateScript"
                            type="button"
                            class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-lg hover:from-emerald-700 hover:to-teal-700 transition-all">
                        <span wire:loading.remove wire:target="generateScript">{{ __('install_script.generate') }}</span>
                        <span wire:loading wire:target="generateScript">{{ __('install_script.generating') }}</span>
                    </button>
                    @else
                    <button wire:click="downloadScript"
                            type="button"
                            class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all">
                        <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        {{ __('install_script.download') }}
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    @script
    <script>
        $wire.on('copy-to-clipboard', ({ script }) => {
            navigator.clipboard.writeText(script).then(() => {
                // Success notification handled by Livewire dispatch
            });
        });

        $wire.on('download-file', ({ url, filename }) => {
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    </script>
    @endscript
</div>
