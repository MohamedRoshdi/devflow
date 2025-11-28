<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Hero Header -->
    <div class="bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-500 dark:from-blue-600 dark:via-indigo-600 dark:to-purple-600 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="flex items-center space-x-4 mb-4">
                <div class="p-3 bg-white/20 backdrop-blur-md rounded-xl">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-4xl font-bold">API Documentation</h1>
                    <p class="text-white/90 text-lg mt-1">Complete API reference for DevFlow Pro v1</p>
                </div>
            </div>
            <div class="flex items-center space-x-4 mt-6">
                <span class="px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-sm font-medium">Version 1.0</span>
                <span class="px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-sm font-medium">REST API</span>
                <span class="px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-sm font-medium">JSON</span>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex gap-8">
            <!-- Sidebar Navigation -->
            <div class="w-64 flex-shrink-0">
                <div class="sticky top-8 bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4">
                    <nav class="space-y-1">
                        <!-- Authentication -->
                        <button wire:click="setSection('authentication')"
                                class="w-full text-left px-4 py-2 rounded-lg transition-colors {{ $activeSection === 'authentication' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                            </svg>
                            Authentication
                        </button>

                        <!-- Projects -->
                        <button wire:click="setSection('projects')"
                                class="w-full text-left px-4 py-2 rounded-lg transition-colors {{ $activeSection === 'projects' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                            </svg>
                            Projects
                        </button>

                        <!-- Servers -->
                        <button wire:click="setSection('servers')"
                                class="w-full text-left px-4 py-2 rounded-lg transition-colors {{ $activeSection === 'servers' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                            </svg>
                            Servers
                        </button>

                        <!-- Deployments -->
                        <button wire:click="setSection('deployments')"
                                class="w-full text-left px-4 py-2 rounded-lg transition-colors {{ $activeSection === 'deployments' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            Deployments
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1">
                @if($activeSection === 'authentication')
                    @include('livewire.docs.partials.authentication')
                @elseif($activeSection === 'projects')
                    @include('livewire.docs.partials.projects')
                @elseif($activeSection === 'servers')
                    @include('livewire.docs.partials.servers')
                @elseif($activeSection === 'deployments')
                    @include('livewire.docs.partials.deployments')
                @endif
            </div>
        </div>
    </div>
</div>
