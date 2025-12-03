<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        
        <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 dark:from-indigo-600 dark:via-purple-600 dark:to-pink-600 p-8 shadow-xl overflow-hidden">
            <div class="absolute inset-0 bg-black/10 dark:bg-black/20 backdrop-blur-sm"></div>
            <div class="relative z-10 flex justify-between items-center">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="p-2 bg-white/20 dark:bg-white/10 backdrop-blur-md rounded-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <h1 class="text-4xl font-bold text-white">Default Setup Preferences</h1>
                    </div>
                    <p class="text-white/90 text-lg">Configure your default settings for new project creation</p>
                </div>
            </div>
        </div>

        
        <div class="space-y-6">

            
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Project Creation Defaults</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Default settings applied to all new projects</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-4">

                    
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/30 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-900 dark:text-white">Enable SSL Certificates</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Auto-generate and manage SSL certificates</p>
                            </div>
                        </div>
                        <button type="button" wire:click="$toggle('defaultEnableSsl')"
                                class="relative inline-flex h-8 w-14 rounded-full transition-colors <?php echo e($this->defaultEnableSsl ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600'); ?>">
                            <span class="inline-block h-6 w-6 transform rounded-full bg-white transition-transform <?php echo e($this->defaultEnableSsl ? 'translate-x-7' : 'translate-x-1'); ?> shadow-lg"></span>
                        </button>
                    </div>

                    
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/30 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-900 dark:text-white">Enable Webhooks</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Allow GitHub webhooks for automatic deployments</p>
                            </div>
                        </div>
                        <button type="button" wire:click="$toggle('defaultEnableWebhooks')"
                                class="relative inline-flex h-8 w-14 rounded-full transition-colors <?php echo e($this->defaultEnableWebhooks ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600'); ?>">
                            <span class="inline-block h-6 w-6 transform rounded-full bg-white transition-transform <?php echo e($this->defaultEnableWebhooks ? 'translate-x-7' : 'translate-x-1'); ?> shadow-lg"></span>
                        </button>
                    </div>

                    
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/30 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-900 dark:text-white">Enable Health Checks</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Monitor project health status regularly</p>
                            </div>
                        </div>
                        <button type="button" wire:click="$toggle('defaultEnableHealthChecks')"
                                class="relative inline-flex h-8 w-14 rounded-full transition-colors <?php echo e($this->defaultEnableHealthChecks ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600'); ?>">
                            <span class="inline-block h-6 w-6 transform rounded-full bg-white transition-transform <?php echo e($this->defaultEnableHealthChecks ? 'translate-x-7' : 'translate-x-1'); ?> shadow-lg"></span>
                        </button>
                    </div>

                    
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/30 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-cyan-100 dark:bg-cyan-900/30 rounded-lg">
                                <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                                </svg>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-900 dark:text-white">Enable Backups</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Automatically backup databases and files</p>
                            </div>
                        </div>
                        <button type="button" wire:click="$toggle('defaultEnableBackups')"
                                class="relative inline-flex h-8 w-14 rounded-full transition-colors <?php echo e($this->defaultEnableBackups ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600'); ?>">
                            <span class="inline-block h-6 w-6 transform rounded-full bg-white transition-transform <?php echo e($this->defaultEnableBackups ? 'translate-x-7' : 'translate-x-1'); ?> shadow-lg"></span>
                        </button>
                    </div>

                    
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/30 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                </svg>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-900 dark:text-white">Enable Notifications</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Receive deployment and system alerts</p>
                            </div>
                        </div>
                        <button type="button" wire:click="$toggle('defaultEnableNotifications')"
                                class="relative inline-flex h-8 w-14 rounded-full transition-colors <?php echo e($this->defaultEnableNotifications ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600'); ?>">
                            <span class="inline-block h-6 w-6 transform rounded-full bg-white transition-transform <?php echo e($this->defaultEnableNotifications ? 'translate-x-7' : 'translate-x-1'); ?> shadow-lg"></span>
                        </button>
                    </div>

                    
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/30 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-900 dark:text-white">Enable Auto Deploy</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Automatically deploy on repository changes</p>
                            </div>
                        </div>
                        <button type="button" wire:click="$toggle('defaultEnableAutoDeploy')"
                                class="relative inline-flex h-8 w-14 rounded-full transition-colors <?php echo e($this->defaultEnableAutoDeploy ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600'); ?>">
                            <span class="inline-block h-6 w-6 transform rounded-full bg-white transition-transform <?php echo e($this->defaultEnableAutoDeploy ? 'translate-x-7' : 'translate-x-1'); ?> shadow-lg"></span>
                        </button>
                    </div>

                </div>
            </div>

            
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-pink-100 dark:bg-pink-900/30 rounded-lg">
                            <svg class="w-6 h-6 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.5a2 2 0 00-1 3.75A4 4 0 0010 9h0a4 4 0 00-3.5 2.25A2 2 0 004 15v4a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">UI Preferences</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Customize your interface experience</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-4">

                    
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/30 rounded-lg">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                                <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1m-16 0H1m15.364 1.636l.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                            <label class="block text-sm font-medium text-gray-900 dark:text-white">Preferred Theme</label>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center p-3 border-2 <?php echo e($this->theme === 'dark' ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-600'); ?> rounded-lg cursor-pointer transition-colors">
                                <input type="radio" wire:model="theme" value="dark" class="w-4 h-4">
                                <span class="ml-3 font-medium text-gray-900 dark:text-white">Dark</span>
                            </label>
                            <label class="flex items-center p-3 border-2 <?php echo e($this->theme === 'light' ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-600'); ?> rounded-lg cursor-pointer transition-colors">
                                <input type="radio" wire:model="theme" value="light" class="w-4 h-4">
                                <span class="ml-3 font-medium text-gray-900 dark:text-white">Light</span>
                            </label>
                        </div>
                    </div>

                    
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/30 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-900 dark:text-white">Show Setup Wizard Tips</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Display helpful tips during project creation</p>
                            </div>
                        </div>
                        <button type="button" wire:click="$toggle('showWizardTips')"
                                class="relative inline-flex h-8 w-14 rounded-full transition-colors <?php echo e($this->showWizardTips ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600'); ?>">
                            <span class="inline-block h-6 w-6 transform rounded-full bg-white transition-transform <?php echo e($this->showWizardTips ? 'translate-x-7' : 'translate-x-1'); ?> shadow-lg"></span>
                        </button>
                    </div>

                </div>
            </div>

            
            <div class="flex justify-end space-x-3">
                <a href="javascript:window.history.back()"
                   class="inline-flex items-center px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 font-semibold transition-all duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Cancel
                </a>
                <button wire:click="save"
                        wire:loading.attr="disabled"
                        <?php echo e($this->isSaving ? 'disabled' : ''); ?>

                        class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 disabled:from-gray-400 disabled:to-gray-400 text-white rounded-lg font-semibold transition-all duration-200 hover:scale-105 shadow-lg">
                    <span wire:loading.remove>
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Save Preferences
                    </span>
                    <span wire:loading>
                        <svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Saving...
                    </span>
                </button>
            </div>

            
            <?php if($saveSuccess): ?>
                <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-4 border border-green-200 dark:border-green-800">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-sm font-medium text-green-800 dark:text-green-400">All settings have been saved successfully!</p>
                    </div>
                </div>
            <?php endif; ?>

        </div>

    </div>
</div>
<?php /**PATH /home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/livewire/settings/default-setup-preferences.blade.php ENDPATH**/ ?>