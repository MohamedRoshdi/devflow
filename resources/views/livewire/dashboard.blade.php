<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Monitor your infrastructure and deployments</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Servers Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-6 transition-colors">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Servers</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $stats['total_servers'] }}</p>
                    <p class="text-sm text-green-600 dark:text-green-400 mt-1">{{ $stats['online_servers'] }} online</p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-full transition-colors">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Projects Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-6 transition-colors">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Projects</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $stats['total_projects'] }}</p>
                    <p class="text-sm text-green-600 dark:text-green-400 mt-1">{{ $stats['running_projects'] }} running</p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-full transition-colors">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Deployments Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-6 transition-colors">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Deployments</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $stats['total_deployments'] }}</p>
                    <p class="text-sm text-green-600 dark:text-green-400 mt-1">{{ $stats['successful_deployments'] }} successful</p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-full transition-colors">
                    <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Failed Deployments Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-6 transition-colors">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Failed Deployments</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $stats['failed_deployments'] }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Last 30 days</p>
                </div>
                <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-full transition-colors">
                    <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Deployments -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 transition-colors">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Recent Deployments</h2>
            </div>
            <div class="p-6">
                @forelse($recentDeployments as $deployment)
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700 last:border-0">
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
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 transition-colors">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Projects</h2>
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

