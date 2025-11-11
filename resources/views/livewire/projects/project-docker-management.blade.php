<style>
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fadeIn {
        animation: fadeIn 0.5s ease-out forwards;
    }
</style>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">🐳 Docker Management</h2>
            <button wire:click="loadDockerInfo" 
                    class="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded transition-all transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
                    wire:loading.attr="disabled"
                    wire:target="loadDockerInfo">
                <span wire:loading.remove wire:target="loadDockerInfo" class="flex items-center space-x-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Refresh</span>
                </span>
                <span wire:loading wire:target="loadDockerInfo" class="flex items-center space-x-1">
                    <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Loading...</span>
                </span>
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="mx-6 mt-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-400 px-4 py-3 rounded">
            {{ session('message') }}
        </div>
    @endif

    @if ($error)
        <div class="mx-6 mt-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-400 px-4 py-3 rounded">
            {{ $error }}
        </div>
    @endif

    <!-- Tabs -->
    <div class="border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
        <nav class="flex px-6 -mb-px space-x-8">
            <button wire:click="switchTab('overview')" 
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors
                    @if($activeTab === 'overview') border-blue-500 text-blue-600 dark:text-blue-400 @else border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600 @endif">
                Overview
            </button>
            <button wire:click="switchTab('images')" 
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors
                    @if($activeTab === 'images') border-blue-500 text-blue-600 dark:text-blue-400 @else border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600 @endif">
                Images ({{ count($images) }})
            </button>
            <button wire:click="switchTab('logs')" 
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors
                    @if($activeTab === 'logs') border-blue-500 text-blue-600 dark:text-blue-400 @else border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600 @endif">
                Container Logs
            </button>
        </nav>
    </div>

    <div class="p-6">
        @if($loading)
            <!-- Beautiful Loading Skeleton -->
            <div class="space-y-6 animate-pulse">
                <!-- Container Status Skeleton -->
                <div class="bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 rounded-lg p-6 h-64">
                    <div class="h-6 bg-gray-300 dark:bg-gray-600 rounded w-48 mb-6"></div>
                    <div class="space-y-4">
                        <div class="flex justify-between">
                            <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-24"></div>
                            <div class="h-6 bg-gray-300 dark:bg-gray-600 rounded w-32"></div>
                        </div>
                        <div class="flex justify-between">
                            <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-24"></div>
                            <div class="h-6 bg-gray-300 dark:bg-gray-600 rounded w-20"></div>
                        </div>
                        <div class="flex justify-between">
                            <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-24"></div>
                            <div class="h-6 bg-gray-300 dark:bg-gray-600 rounded w-40"></div>
                        </div>
                    </div>
                    <div class="mt-6 flex gap-3">
                        <div class="h-10 bg-gray-300 dark:bg-gray-600 rounded w-32"></div>
                        <div class="h-10 bg-gray-300 dark:bg-gray-600 rounded w-32"></div>
                        <div class="h-10 bg-gray-300 dark:bg-gray-600 rounded w-32"></div>
                    </div>
                </div>

                <!-- Resource Stats Skeleton -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @for($i = 0; $i < 4; $i++)
                        <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4 h-24">
                            <div class="h-3 bg-gray-300 dark:bg-gray-600 rounded w-20 mb-2"></div>
                            <div class="h-8 bg-gray-300 dark:bg-gray-600 rounded w-16"></div>
                        </div>
                    @endfor
                </div>

                <!-- Quick Stats Skeleton -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @for($i = 0; $i < 3; $i++)
                        <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4 h-28">
                            <div class="h-3 bg-gray-300 dark:bg-gray-600 rounded w-24 mb-2"></div>
                            <div class="h-8 bg-gray-300 dark:bg-gray-600 rounded w-12 mb-2"></div>
                            <div class="h-3 bg-gray-300 dark:bg-gray-600 rounded w-32"></div>
                        </div>
                    @endfor
                </div>

                <!-- Loading Text with Icon -->
                <div class="flex items-center justify-center py-8">
                    <div class="flex items-center space-x-3 px-6 py-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <svg class="animate-spin h-5 w-5 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm font-medium text-blue-900 dark:text-blue-300">Loading Docker information...</span>
                    </div>
                </div>
            </div>
        @else
            <!-- Overview Tab -->
            @if($activeTab === 'overview')
                <div class="space-y-6 animate-fadeIn">
                    <!-- Container Status Card -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-6 transition-colors">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Container Status</h3>
                        
                        @if($containerInfo)
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-700 dark:text-gray-300 font-medium">Name:</span>
                                    <code class="bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 px-3 py-1 rounded text-sm">{{ $containerInfo['Names'] ?? $project->slug }}</code>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-700 dark:text-gray-300 font-medium">Status:</span>
                                    <span class="px-3 py-1 rounded-full text-sm font-medium
                                        @if(isset($containerInfo['State']) && stripos($containerInfo['State'], 'running') !== false) bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                                        {{ $containerInfo['State'] ?? 'Unknown' }}
                                    </span>
                                </div>
                                @if(isset($containerInfo['Ports']))
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-700 dark:text-gray-300 font-medium">Ports:</span>
                                        <span class="text-gray-900 dark:text-white">{{ $containerInfo['Ports'] }}</span>
                                    </div>
                                @endif
                                @if(isset($containerInfo['Image']))
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-700 dark:text-gray-300 font-medium">Image:</span>
                                        <code class="bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 px-3 py-1 rounded text-sm">{{ $containerInfo['Image'] }}</code>
                                    </div>
                                @endif
                            </div>

                            <!-- Container Actions -->
                            <div class="mt-6 flex flex-wrap gap-3">
                                @if(isset($containerInfo['State']) && stripos($containerInfo['State'], 'running') !== false)
                                    <button wire:click="stopContainer" 
                                            wire:confirm="Are you sure you want to stop this container?"
                                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors">
                                        ⏹️ Stop Container
                                    </button>
                                    <button wire:click="restartContainer" 
                                            wire:confirm="Are you sure you want to restart this container?"
                                            class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg text-sm font-medium transition-colors">
                                        🔄 Restart Container
                                    </button>
                                @else
                                    <button wire:click="startContainer" 
                                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-colors">
                                        ▶️ Start Container
                                    </button>
                                @endif
                                
                                <button wire:click="exportContainer" 
                                        wire:confirm="This will create a backup image of the current container. Continue?"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                                    💾 Backup Container
                                </button>
                            </div>

                            <!-- Container Stats -->
                            @if($containerStats)
                                <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                                    @if(isset($containerStats['CPUPerc']))
                                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-blue-200 dark:border-blue-700 transition-colors">
                                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">CPU Usage</p>
                                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $containerStats['CPUPerc'] }}</p>
                                        </div>
                                    @endif
                                    @if(isset($containerStats['MemPerc']))
                                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-blue-200 dark:border-blue-700 transition-colors">
                                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Memory Usage</p>
                                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $containerStats['MemPerc'] }}</p>
                                        </div>
                                    @endif
                                    @if(isset($containerStats['NetIO']))
                                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-blue-200 dark:border-blue-700 transition-colors">
                                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Network I/O</p>
                                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $containerStats['NetIO'] }}</p>
                                        </div>
                                    @endif
                                    @if(isset($containerStats['BlockIO']))
                                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-blue-200 dark:border-blue-700 transition-colors">
                                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-1">Disk I/O</p>
                                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $containerStats['BlockIO'] }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @else
                            <div class="text-center py-8">
                                <div class="text-gray-400 text-5xl mb-3">📦</div>
                                <p class="text-gray-600 dark:text-gray-400 mb-4">No container found for this project</p>
                                <button wire:click="buildImage" 
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                                    🔨 Build Image & Start Container
                                </button>
                            </div>
                        @endif
                    </div>

                    <!-- Quick Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 transition-colors">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Docker Images</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ count($images) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Related to this project</p>
                        </div>
                        
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 transition-colors">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Project Status</p>
                            <p class="text-2xl font-bold">
                                <span class="px-3 py-1 rounded-full text-sm
                                    @if($project->status === 'running') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                    @elseif($project->status === 'stopped') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                    @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 @endif">
                                    {{ ucfirst($project->status) }}
                                </span>
                            </p>
                        </div>

                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 transition-colors">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Server</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $project->server->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $project->server->ip_address }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Images Tab -->
            @if($activeTab === 'images')
                <div class="animate-fadeIn">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Docker Images for {{ $project->name }}</h3>
                        <button wire:click="buildImage" 
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                            🔨 Build New Image
                        </button>
                    </div>

                    @if(count($images) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Repository</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tag</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Image ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Created</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Size</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($images as $image)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <code class="text-sm text-gray-900 dark:text-white">{{ $image['Repository'] ?? 'N/A' }}</code>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded">{{ $image['Tag'] ?? 'latest' }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <code class="text-xs text-gray-600 dark:text-gray-400">{{ substr($image['ID'] ?? '', 0, 12) }}</code>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                                {{ $image['CreatedSince'] ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                                {{ $image['Size'] ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <button wire:click="deleteImage('{{ $image['ID'] }}')" 
                                                        wire:confirm="Are you sure you want to delete this image?"
                                                        class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 font-medium transition-colors">
                                                    🗑️ Delete
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-12 bg-gray-50 dark:bg-gray-700 rounded-lg transition-colors">
                            <div class="text-gray-400 dark:text-gray-500 text-5xl mb-3">📦</div>
                            <p class="text-gray-600 dark:text-gray-400 mb-4">No Docker images found for this project</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Build an image to get started</p>
                            <button wire:click="buildImage" 
                                    class="px-4 py-2 bg-blue-600 dark:bg-blue-500 hover:bg-blue-700 dark:hover:bg-blue-600 text-white rounded-lg text-sm font-medium transition-colors">
                                🔨 Build Image
                            </button>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Logs Tab -->
            @if($activeTab === 'logs')
                <div class="animate-fadeIn">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-white">Container Logs</h3>
                        <div class="flex items-center space-x-3">
                            <select wire:model.live="logLines" class="border border-gray-300 rounded-lg px-3 py-1 text-sm">
                                <option value="50">Last 50 lines</option>
                                <option value="100">Last 100 lines</option>
                                <option value="200">Last 200 lines</option>
                                <option value="500">Last 500 lines</option>
                            </select>
                            <button wire:click="refreshLogs" 
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                                🔄 Refresh Logs
                            </button>
                        </div>
                    </div>

                    @if($containerLogs)
                        <div class="bg-gray-900 text-green-400 rounded-lg p-4 font-mono text-sm overflow-x-auto" style="max-height: 500px; overflow-y: auto;">
                            <pre class="whitespace-pre-wrap">{{ $containerLogs }}</pre>
                        </div>
                    @else
                        <div class="text-center py-12 bg-gray-50 rounded-lg">
                            <div class="text-gray-400 text-5xl mb-3">📝</div>
                            <p class="text-gray-600 dark:text-gray-400 mb-4">No logs available</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">Container must be running to view logs</p>
                        </div>
                    @endif
                </div>
            @endif
        @endif
    </div>
</div>

