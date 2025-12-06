<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-12">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Hero Section --}}
        <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-600 dark:from-blue-600 dark:via-indigo-600 dark:to-purple-700 p-8 shadow-xl overflow-hidden">
            <div class="absolute inset-0 bg-black/10 dark:bg-black/20 backdrop-blur-sm"></div>
            <div class="relative z-10 flex justify-between items-center">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="p-2 bg-white/20 dark:bg-white/10 backdrop-blur-md rounded-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <h1 class="text-4xl font-bold text-white">System Settings</h1>
                    </div>
                    <p class="text-white/90 text-lg">Configure application-wide settings and features</p>
                </div>
                <div class="flex space-x-3">
                    <button wire:click="clearCache"
                            class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-md text-white rounded-lg font-medium transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Clear Cache
                    </button>
                </div>
            </div>
        </div>

        <div class="flex gap-8">
            {{-- Sidebar Navigation --}}
            <div class="w-64 flex-shrink-0">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden sticky top-6">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Settings Groups</h2>
                    </div>
                    <nav class="p-2">
                        @foreach($this->groups as $groupKey => $groupName)
                            <button wire:click="setActiveGroup('{{ $groupKey }}')"
                                    class="w-full flex items-center px-4 py-3 text-left rounded-lg transition-colors {{ $activeGroup === $groupKey ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                                @switch($groupKey)
                                    @case('general')
                                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                        </svg>
                                        @break
                                    @case('auth')
                                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                        </svg>
                                        @break
                                    @case('features')
                                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                        @break
                                    @case('mail')
                                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        @break
                                    @case('security')
                                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                        </svg>
                                        @break
                                @endswitch
                                <span class="font-medium">{{ $groupName }}</span>
                            </button>
                        @endforeach
                    </nav>
                </div>
            </div>

            {{-- Settings Content --}}
            <div class="flex-1">
                @foreach($this->groupedSettings as $group => $groupSettings)
                    <div x-show="$wire.activeGroup === '{{ $group }}'"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">

                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                    @switch($group)
                                        @case('general')
                                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                            </svg>
                                            @break
                                        @case('auth')
                                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                            </svg>
                                            @break
                                        @case('features')
                                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                            </svg>
                                            @break
                                        @case('mail')
                                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                            @break
                                        @case('security')
                                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                            </svg>
                                            @break
                                    @endswitch
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $this->groups[$group] ?? ucfirst($group) }}</h2>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        @switch($group)
                                            @case('general')
                                                Configure basic application settings
                                                @break
                                            @case('auth')
                                                Manage authentication and registration settings
                                                @break
                                            @case('features')
                                                Enable or disable application features
                                                @break
                                            @case('mail')
                                                Configure email sending settings
                                                @break
                                            @case('security')
                                                Security and rate limiting options
                                                @break
                                        @endswitch
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 space-y-4">
                            @foreach($groupSettings as $setting)
                                @if($setting->type === 'boolean')
                                    {{-- Boolean Toggle --}}
                                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/30 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                                        <div class="flex items-center space-x-3">
                                            <div class="p-2 rounded-lg {{ isset($settings[$setting->key]) && $settings[$setting->key] ? 'bg-green-100 dark:bg-green-900/30' : 'bg-gray-200 dark:bg-gray-600' }}">
                                                <svg class="w-5 h-5 {{ isset($settings[$setting->key]) && $settings[$setting->key] ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-900 dark:text-white">{{ $setting->getDisplayLabel() }}</label>
                                                @if($setting->description)
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $setting->description }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        <button type="button" wire:click="toggleSetting('{{ $setting->key }}')"
                                                class="relative inline-flex h-8 w-14 rounded-full transition-colors {{ isset($settings[$setting->key]) && $settings[$setting->key] ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                            <span class="inline-block h-6 w-6 transform rounded-full bg-white transition-transform {{ isset($settings[$setting->key]) && $settings[$setting->key] ? 'translate-x-7' : 'translate-x-1' }} mt-1 shadow-lg"></span>
                                        </button>
                                    </div>
                                @elseif($setting->type === 'integer')
                                    {{-- Integer Input --}}
                                    <div class="p-4 bg-gray-50 dark:bg-gray-700/30 rounded-lg">
                                        <div class="flex items-center space-x-3 mb-3">
                                            <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                                                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-900 dark:text-white">{{ $setting->getDisplayLabel() }}</label>
                                                @if($setting->description)
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $setting->description }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        <input type="number"
                                               wire:model="settings.{{ $setting->key }}"
                                               class="w-full px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent text-gray-900 dark:text-white">
                                    </div>
                                @else
                                    {{-- String/Text Input --}}
                                    <div class="p-4 bg-gray-50 dark:bg-gray-700/30 rounded-lg">
                                        <div class="flex items-center space-x-3 mb-3">
                                            <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-900 dark:text-white">{{ $setting->getDisplayLabel() }}</label>
                                                @if($setting->description)
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $setting->description }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        <input type="text"
                                               wire:model="settings.{{ $setting->key }}"
                                               class="w-full px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent text-gray-900 dark:text-white">
                                    </div>
                                @endif
                            @endforeach

                            @if($groupSettings->isEmpty())
                                <div class="text-center py-8">
                                    <svg class="w-12 h-12 text-gray-400 dark:text-gray-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p class="text-gray-500 dark:text-gray-400">No settings in this group yet.</p>
                                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Run the seeder to populate default settings.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                {{-- Action Buttons --}}
                <div class="mt-6 flex justify-between">
                    <button wire:click="resetToDefaults"
                            wire:confirm="Are you sure you want to reset all settings to defaults?"
                            class="inline-flex items-center px-5 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 font-medium transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Reset to Defaults
                    </button>

                    <button wire:click="save"
                            wire:loading.attr="disabled"
                            {{ $isSaving ? 'disabled' : '' }}
                            class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 disabled:from-gray-400 disabled:to-gray-400 text-white rounded-lg font-semibold transition-all duration-200 hover:scale-105 shadow-lg">
                        <span wire:loading.remove wire:target="save">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Save Settings
                        </span>
                        <span wire:loading wire:target="save">
                            <svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Saving...
                        </span>
                    </button>
                </div>

                {{-- Success Message --}}
                @if($saveSuccess)
                    <div class="mt-4 rounded-lg bg-green-50 dark:bg-green-900/20 p-4 border border-green-200 dark:border-green-800">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm font-medium text-green-800 dark:text-green-400">All settings have been saved successfully!</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
