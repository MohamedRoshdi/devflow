<div>
    <!-- Hero Section with Gradient -->
    <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-blue-500 via-purple-500 to-pink-500 dark:from-blue-600 dark:via-purple-600 dark:to-pink-600 p-8 shadow-xl overflow-hidden">
        <div class="absolute inset-0 bg-black/10 dark:bg-black/20 backdrop-blur-sm"></div>
        <div class="relative z-10">
            <div class="flex items-center space-x-3 mb-2">
                <div class="p-2 bg-white/20 dark:bg-white/10 backdrop-blur-md rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h1 class="text-4xl font-bold text-white">Infrastructure Overview</h1>
            </div>
            <p class="text-white/90 text-lg">Monitor your servers, projects, and deployments</p>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Servers Card -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-100">Total Servers</p>
                    <p class="text-3xl font-bold text-white mt-2">{{ $stats['total_servers'] }}</p>
                    <p class="text-sm text-blue-100 mt-1">{{ $stats['online_servers'] }} online</p>
                </div>
                <div class="p-2 bg-white/20 backdrop-blur-md rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Projects Card -->
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 dark:from-green-600 dark:to-emerald-700 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-green-100">Total Projects</p>
                    <p class="text-3xl font-bold text-white mt-2">{{ $stats['total_projects'] }}</p>
                    <p class="text-sm text-green-100 mt-1">{{ $stats['running_projects'] }} running</p>
                </div>
                <div class="p-2 bg-white/20 backdrop-blur-md rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Deployments Card -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 dark:from-purple-600 dark:to-purple-700 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-purple-100">Total Deployments</p>
                    <p class="text-3xl font-bold text-white mt-2">{{ $stats['total_deployments'] }}</p>
                    <p class="text-sm text-purple-100 mt-1">{{ $stats['successful_deployments'] }} successful</p>
                </div>
                <div class="p-2 bg-white/20 backdrop-blur-md rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Failed Deployments Card -->
        <div class="bg-gradient-to-br from-red-500 to-red-600 dark:from-red-600 dark:to-red-700 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-red-100">Failed Deployments</p>
                    <p class="text-3xl font-bold text-white mt-2">{{ $stats['failed_deployments'] }}</p>
                    <p class="text-sm text-red-100 mt-1">Last 30 days</p>
                </div>
                <div class="p-2 bg-white/20 backdrop-blur-md rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Deployments -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Recent Deployments</h2>
                </div>
            </div>
            <div class="p-6">
                @forelse($recentDeployments as $deployment)
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors rounded-lg px-3 -mx-3">
                        <div class="flex-1">
                            <a href="{{ route('projects.show', $deployment->project) }}" class="font-medium text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                {{ $deployment->project->name }}
                            </a>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ $deployment->commit_message ?? 'No commit message' }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                {{ $deployment->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <div>
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                @if($deployment->status === 'success') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                @elseif($deployment->status === 'failed') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                @elseif($deployment->status === 'running') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400
                                @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                @endif">
                                {{ ucfirst($deployment->status) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 dark:text-gray-400 text-center py-8">No deployments yet</p>
                @endforelse
            </div>
        </div>

        <!-- Projects Overview -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Projects</h2>
                </div>
                <a href="{{ route('projects.create') }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 text-sm font-medium transition-colors">
                    + New Project
                </a>
            </div>
            <div class="p-6">
                @forelse($projects as $project)
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer rounded-lg px-3 -mx-3"
                         onclick="window.location='{{ route('projects.show', $project) }}'">
                        <div class="flex-1">
                            <a href="{{ route('projects.show', $project) }}" class="font-medium text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                {{ $project->name }}
                            </a>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ $project->framework ?? 'Unknown' }} • {{ $project->server->name ?? 'No server' }}
                            </p>
                            @if($project->status === 'running' && $project->port && $project->server)
                                @php
                                    $url = 'http://' . $project->server->ip_address . ':' . $project->port;
                                @endphp
                                <a href="{{ $url }}" target="_blank"
                                   onclick="event.stopPropagation()"
                                   class="inline-flex items-center text-xs text-green-700 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 mt-1 font-mono transition-colors">
                                    🚀 {{ $url }}
                                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="w-3 h-3 rounded-full
                                @if($project->status === 'running') bg-green-500
                                @elseif($project->status === 'stopped') bg-gray-400
                                @else bg-yellow-500
                                @endif">
                            </span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ ucfirst($project->status) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 dark:text-gray-400 text-center py-8">No projects yet</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

