<div x-data="{ activeTab: 'overview' }">
    <!-- Hero Section with Project Status -->
    <div class="mb-8 bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-700 dark:to-purple-700 rounded-xl shadow-xl p-8 text-white">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-4 md:space-y-0">
            <div class="flex-1">
                <div class="flex items-center space-x-4 mb-2">
                    <h1 class="text-4xl font-bold">{{ $project->name }}</h1>
                    <span class="px-4 py-1.5 rounded-full text-sm font-semibold
                        @if($project->status === 'running') bg-green-500 text-white animate-pulse
                        @elseif($project->status === 'stopped') bg-gray-500 text-white
                        @elseif($project->status === 'building') bg-yellow-500 text-white animate-pulse
                        @else bg-red-500 text-white
                        @endif">
                        <span class="inline-block w-2 h-2 rounded-full bg-white mr-2"></span>
                        {{ ucfirst($project->status) }}
                    </span>
                </div>
                <div class="flex items-center space-x-4 text-blue-100">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        {{ $project->slug }}
                    </span>
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"></path>
                        </svg>
                        {{ $project->server->name ?? 'No Server' }}
                    </span>
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                        </svg>
                        {{ $project->framework ?? 'Unknown' }}
                    </span>
                    @if($project->environment)
                        <span class="flex items-center px-3 py-1 bg-white/20 rounded-full">
                            @if($project->environment === 'production') 🚀
                            @elseif($project->environment === 'staging') 🔧
                            @elseif($project->environment === 'development') 💻
                            @else 🏠
                            @endif
                            {{ ucfirst($project->environment) }}
                        </span>
                    @endif
                </div>
            </div>
            
            <div class="flex flex-wrap gap-3">
                @if($project->status === 'running')
                    <button wire:click="stopProject" wire:confirm="Stop this project?" 
                            class="px-6 py-3 bg-red-500 hover:bg-red-600 text-white rounded-lg font-semibold transition-all transform hover:scale-105 shadow-lg">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"></path>
                        </svg>
                        Stop
                    </button>
                @else
                    <button wire:click="startProject" 
                            class="px-6 py-3 bg-green-500 hover:bg-green-600 text-white rounded-lg font-semibold transition-all transform hover:scale-105 shadow-lg">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Start
                    </button>
                @endif
                <button wire:click="$set('showDeployModal', true)" 
                        class="px-6 py-3 bg-white hover:bg-blue-50 text-blue-600 rounded-lg font-semibold transition-all transform hover:scale-105 shadow-lg">
                    🚀 Deploy
                </button>
                <a href="{{ route('projects.edit', $project) }}" 
                   class="px-6 py-3 bg-white/10 hover:bg-white/20 border border-white/30 text-white rounded-lg font-semibold transition-all">
                    ✏️ Edit
                </a>
            </div>
        </div>

        <!-- Live URL Banner -->
        @if($project->status === 'running' && $project->port && $project->server)
            @php
                $url = 'http://' . $project->server->ip_address . ':' . $project->port;
            @endphp
            <div class="mt-6 flex items-center justify-between bg-white/10 backdrop-blur-sm border border-white/20 rounded-lg p-4">
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
                    <span class="font-medium">Live at:</span>
                    <a href="{{ $url }}" target="_blank" 
                       class="font-mono hover:underline flex items-center">
                        {{ $url }}
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    </a>
                </div>
                <button onclick="navigator.clipboard.writeText('{{ $url }}'); alert('URL copied!')" 
                        class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition">
                    📋 Copy
                </button>
            </div>
        @endif
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
    @if($updateStatus && !$updateStatus['up_to_date'])
        <div class="mb-6 bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 border-l-4 border-yellow-500 rounded-r-lg p-6 shadow-lg">
            <div class="flex items-start justify-between">
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-yellow-500 animate-bounce" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-yellow-900 dark:text-yellow-300">
                            {{ $updateStatus['commits_behind'] }} New Update{{ $updateStatus['commits_behind'] > 1 ? 's' : '' }} Available!
                        </h3>
                        <div class="mt-2 text-sm text-yellow-800 dark:text-yellow-400 space-y-1">
                            <p>Current: <code class="bg-yellow-200/50 dark:bg-yellow-900/40 px-2 py-1 rounded font-mono">{{ $updateStatus['local_commit'] }}</code></p>
                            <p>Latest: <code class="bg-yellow-200/50 dark:bg-yellow-900/40 px-2 py-1 rounded font-mono">{{ $updateStatus['remote_commit'] }}</code></p>
                        </div>
                    </div>
                </div>
                <button wire:click="$set('showDeployModal', true)" 
                        class="px-6 py-3 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg font-bold transition-all transform hover:scale-105 shadow-lg">
                    🚀 Deploy Now
                </button>
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
                <button @click="activeTab = 'overview'" 
                        :class="activeTab === 'overview' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span>Overview</span>
                </button>

                <button @click="activeTab = 'docker'" 
                        :class="activeTab === 'docker' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <span>Docker</span>
                </button>

                <button @click="activeTab = 'environment'" 
                        :class="activeTab === 'environment' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span>Environment</span>
                </button>

                <button @click="activeTab = 'git'" 
                        :class="activeTab === 'git' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                    </svg>
                    <span>Git & Commits</span>
                </button>

                <button @click="activeTab = 'deployments'" 
                        :class="activeTab === 'deployments' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Deployments</span>
                </button>
            </nav>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="min-h-screen">
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
        <div x-show="activeTab === 'docker'" x-transition class="space-y-8">
            @livewire('projects.project-docker-management', ['project' => $project], key('docker-' . $project->id))
        </div>

        <!-- Environment Tab -->
        <div x-show="activeTab === 'environment'" x-transition class="space-y-8">
            @livewire('projects.project-environment', ['project' => $project], key('env-' . $project->id))
        </div>

        <!-- Git & Commits Tab -->
        <div x-show="activeTab === 'git'" x-transition class="space-y-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg dark:shadow-gray-900/50 overflow-hidden" wire:poll.60s="checkForUpdates">
                <div class="bg-gradient-to-r from-green-500 to-green-600 p-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-bold text-white flex items-center">
                            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                            </svg>
                            Git Commits & Updates
                        </h2>
                        <div class="flex items-center space-x-3">
                            <span class="text-white/80 text-sm" wire:loading.remove wire:target="checkForUpdates">
                                Auto-checks every 60s
                            </span>
                            <button wire:click="checkForUpdates" 
                                    wire:loading.attr="disabled"
                                    class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition flex items-center space-x-2">
                                <svg wire:loading.remove wire:target="checkForUpdates" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <svg wire:loading wire:target="checkForUpdates" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Check Now</span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="p-6">
                    @if($project->current_commit_hash)
                        <div class="mb-6 p-6 bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border border-blue-200 dark:border-blue-700 rounded-xl">
                            <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                </svg>
                                Currently Deployed Commit
                            </h4>
                            <div class="flex items-start space-x-4">
                                <code class="text-sm bg-blue-600 text-white px-3 py-1.5 rounded-lg font-mono font-bold">
                                    {{ substr($project->current_commit_hash, 0, 7) }}
                                </code>
                                <div class="flex-1">
                                    <p class="text-base font-medium text-blue-900 dark:text-blue-200">{{ $project->current_commit_message }}</p>
                                    <p class="text-sm text-blue-700 dark:text-blue-400 mt-2">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        {{ $project->last_commit_at ? $project->last_commit_at->diffForHumans() : 'Unknown time' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(count($commits) > 0)
                        <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            Recent Commits on <span class="font-mono ml-2 text-green-600 dark:text-green-400">{{ $project->branch }}</span>
                        </h4>
                        <div class="space-y-3">
                            @foreach($commits as $commit)
                                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all border border-gray-200 dark:border-gray-600">
                                    <div class="flex items-start space-x-3">
                                        <code class="text-xs bg-gradient-to-r from-gray-700 to-gray-800 dark:from-gray-600 dark:to-gray-700 text-white px-3 py-1.5 rounded-lg font-mono font-bold flex-shrink-0 shadow">
                                            {{ $commit['short_hash'] }}
                                        </code>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white break-words">{{ $commit['message'] }}</p>
                                            <div class="flex items-center space-x-3 mt-2 text-xs text-gray-600 dark:text-gray-400">
                                                <span class="flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                                    </svg>
                                                    {{ $commit['author'] }}
                                                </span>
                                                <span class="flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                                    </svg>
                                                    {{ \Carbon\Carbon::createFromTimestamp($commit['timestamp'])->diffForHumans() }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-16">
                            <svg class="mx-auto h-20 w-20 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                            </svg>
                            <p class="mt-4 text-gray-500 dark:text-gray-400 text-lg">No commit history available</p>
                            <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">Deploy the project first to track commits</p>
                        </div>
                    @endif
                </div>
            </div>
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
        </div>
    </div>

    <!-- Deploy Modal -->
    @if($showDeployModal)
        <div class="fixed inset-0 bg-black/50 dark:bg-black/70 backdrop-blur-sm overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4" 
             wire:click="$set('showDeployModal', false)">
            <div class="relative mx-auto border border-gray-200 dark:border-gray-700 w-full max-w-lg shadow-2xl rounded-2xl bg-white dark:bg-gray-800 transform transition-all" 
                 wire:click.stop>
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
                                class="flex-1 px-6 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            Cancel
                        </button>
                        <button wire:click="deploy" 
                                wire:loading.attr="disabled"
                                class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg font-bold transition-all transform hover:scale-105 shadow-lg">
                            <span wire:loading.remove wire:target="deploy">🚀 Deploy Now</span>
                            <span wire:loading wire:target="deploy" class="flex items-center justify-center">
                                <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Deploying...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
