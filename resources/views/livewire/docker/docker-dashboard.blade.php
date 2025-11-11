<div class="space-y-6">
    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-white">Docker Management</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">Server: {{ $server->name }}</p>
        </div>
        <button wire:click="loadDockerInfo" 
                class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition"
                wire:loading.attr="disabled">
            <span wire:loading.remove>🔄 Refresh</span>
            <span wire:loading>Loading...</span>
        </button>
    </div>

    {{-- Success Message --}}
    @if (session()->has('message'))
        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-400 px-4 py-3 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    {{-- Error Message --}}
    @if ($error)
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-400 px-4 py-3 rounded-lg">
            {{ $error }}
        </div>
    @endif

    {{-- Tabs --}}
    <div class="border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8">
            <button wire:click="switchTab('overview')" 
                    class="@if($activeTab === 'overview') border-blue-500 text-blue-600 @else border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                📊 Overview
            </button>
            <button wire:click="switchTab('images')" 
                    class="@if($activeTab === 'images') border-blue-500 text-blue-600 @else border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                🖼️ Images ({{ count($images) }})
            </button>
            <button wire:click="switchTab('volumes')" 
                    class="@if($activeTab === 'volumes') border-blue-500 text-blue-600 @else border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                💾 Volumes ({{ count($volumes) }})
            </button>
            <button wire:click="switchTab('networks')" 
                    class="@if($activeTab === 'networks') border-blue-500 text-blue-600 @else border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                🌐 Networks ({{ count($networks) }})
            </button>
            <button wire:click="switchTab('cleanup')" 
                    class="@if($activeTab === 'cleanup') border-blue-500 text-blue-600 @else border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                🧹 Cleanup
            </button>
        </nav>
    </div>

    {{-- Overview Tab --}}
    @if ($activeTab === 'overview')
        <div class="space-y-6">
            {{-- Docker Info --}}
            @if ($dockerInfo)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Docker System Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">Docker Version</div>
                            <div class="text-xl font-bold">{{ $dockerInfo['ServerVersion'] ?? 'N/A' }}</div>
                        </div>
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">Total Containers</div>
                            <div class="text-xl font-bold text-blue-600">{{ $dockerInfo['Containers'] ?? 0 }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                Running: {{ $dockerInfo['ContainersRunning'] ?? 0 }} | 
                                Stopped: {{ $dockerInfo['ContainersStopped'] ?? 0 }}
                            </div>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">Total Images</div>
                            <div class="text-xl font-bold text-green-600 dark:text-green-400">{{ $dockerInfo['Images'] ?? 0 }}</div>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <div class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">Storage Driver</div>
                            <div class="text-lg font-semibold">{{ $dockerInfo['Driver'] ?? 'N/A' }}</div>
                        </div>
                        <div class="bg-yellow-50 p-4 rounded-lg">
                            <div class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">Operating System</div>
                            <div class="text-lg font-semibold">{{ $dockerInfo['OperatingSystem'] ?? 'N/A' }}</div>
                        </div>
                        <div class="bg-indigo-50 p-4 rounded-lg">
                            <div class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">CPU Cores</div>
                            <div class="text-xl font-bold text-indigo-600">{{ $dockerInfo['NCPU'] ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Disk Usage --}}
            @if ($diskUsage)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Disk Usage</h3>
                    <div class="space-y-4">
                        @foreach ($diskUsage as $item)
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <div class="font-semibold">{{ $item['Type'] ?? 'Unknown' }}</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
                                        Total: {{ $item['TotalCount'] ?? 0 }} | 
                                        Active: {{ $item['Active'] ?? 0 }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold">{{ $item['Size'] ?? 'N/A' }}</div>
                                    <div class="text-sm text-orange-600">
                                        Reclaimable: {{ $item['Reclaimable'] ?? '0' }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Images Tab --}}
    @if ($activeTab === 'images')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold">Docker Images</h3>
                <button wire:click="pruneImages" 
                        class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition"
                        wire:loading.attr="disabled">
                    🧹 Prune Unused Images
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Repository</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tag</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Image ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Size</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($images as $image)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white dark:text-white">
                                    {{ $image['Repository'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    {{ $image['Tag'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono">
                                    {{ Str::limit($image['ID'] ?? 'N/A', 12) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    {{ $image['Size'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    {{ $image['CreatedAt'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    <button wire:click="deleteImage('{{ $image['ID'] }}')" 
                                            class="text-red-600 hover:text-red-900"
                                            onclick="return confirm('Are you sure you want to delete this image?')">
                                        🗑️ Delete
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400 dark:text-gray-400">No images found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Volumes Tab --}}
    @if ($activeTab === 'volumes')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                <h3 class="text-lg font-semibold">Docker Volumes</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Driver</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Mountpoint</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($volumes as $volume)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white dark:text-white">
                                    {{ $volume['Name'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    {{ $volume['Driver'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 font-mono text-xs">
                                    {{ Str::limit($volume['Mountpoint'] ?? 'N/A', 50) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    <button wire:click="deleteVolume('{{ $volume['Name'] }}')" 
                                            class="text-red-600 hover:text-red-900"
                                            onclick="return confirm('Are you sure? This will permanently delete all data in this volume!')">
                                        🗑️ Delete
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400 dark:text-gray-400">No volumes found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Networks Tab --}}
    @if ($activeTab === 'networks')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                <h3 class="text-lg font-semibold">Docker Networks</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Driver</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Scope</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($networks as $network)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white dark:text-white">
                                    {{ $network['Name'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono">
                                    {{ Str::limit($network['ID'] ?? 'N/A', 12) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    {{ $network['Driver'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    {{ $network['Scope'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    @if (!in_array($network['Name'], ['bridge', 'host', 'none']))
                                        <button wire:click="deleteNetwork('{{ $network['Name'] }}')" 
                                                class="text-red-600 hover:text-red-900"
                                                onclick="return confirm('Are you sure you want to delete this network?')">
                                            🗑️ Delete
                                        </button>
                                    @else
                                        <span class="text-gray-400">System Network</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400 dark:text-gray-400">No networks found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Cleanup Tab --}}
    @if ($activeTab === 'cleanup')
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">System Cleanup</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">Free up disk space by removing unused Docker resources.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                        <h4 class="font-semibold mb-2">🖼️ Prune Images</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Remove dangling and unused images</p>
                        <button wire:click="pruneImages" 
                                class="w-full px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition"
                                wire:loading.attr="disabled">
                            Prune Images
                        </button>
                    </div>
                    
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                        <h4 class="font-semibold mb-2">🧹 System Prune</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Remove all unused containers, networks, and images</p>
                        <button wire:click="systemPrune" 
                                class="w-full px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition"
                                wire:loading.attr="disabled"
                                onclick="return confirm('This will remove all unused Docker resources. Continue?')">
                            System Prune
                        </button>
                    </div>
                </div>

                @if ($diskUsage)
                    <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                        <h4 class="font-semibold mb-2">💡 Disk Space Summary</h4>
                        <div class="text-sm space-y-1">
                            @foreach ($diskUsage as $item)
                                <div class="flex justify-between">
                                    <span>{{ $item['Type'] ?? 'Unknown' }}:</span>
                                    <span class="font-semibold">{{ $item['Size'] ?? 'N/A' }} <span class="text-orange-600">({{ $item['Reclaimable'] ?? '0' }} reclaimable)</span></span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Loading Overlay --}}
    <div wire:loading class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 flex items-center space-x-3">
            <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-700">Loading...</span>
        </div>
    </div>
</div>

