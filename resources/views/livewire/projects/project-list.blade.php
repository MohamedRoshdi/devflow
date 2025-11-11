<div>
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Projects</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Manage your deployment projects</p>
        </div>
        <a href="{{ route('projects.create') }}" class="btn btn-primary">
            + New Project
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-6 mb-6 transition-colors">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                <input wire:model.live="search" 
                       type="text" 
                       placeholder="Search projects..." 
                       class="input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                <select wire:model.live="statusFilter" class="input">
                    <option value="">All Statuses</option>
                    <option value="running">Running</option>
                    <option value="stopped">Stopped</option>
                    <option value="building">Building</option>
                    <option value="error">Error</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Projects Grid -->
    @if($projects->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            @foreach($projects as $project)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 hover:shadow-lg dark:hover:shadow-gray-900 transition-all">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <a href="{{ route('projects.show', $project) }}" class="text-xl font-bold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                    {{ $project->name }}
                                </a>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $project->slug }}</p>
                            </div>
                            <div class="flex items-center">
                                <span class="w-3 h-3 rounded-full
                                    @if($project->status === 'running') bg-green-500
                                    @elseif($project->status === 'stopped') bg-gray-400
                                    @elseif($project->status === 'building') bg-yellow-500
                                    @else bg-red-500
                                    @endif">
                                </span>
                            </div>
                        </div>

                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"></path>
                                </svg>
                                {{ $project->server->name ?? 'No server' }}
                            </div>
                            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                                {{ $project->framework ?? 'Unknown' }}
                            </div>
                            @if($project->domains->count() > 0)
                                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                                    </svg>
                                    {{ $project->domains->first()->domain }}
                                </div>
                            @endif
                        </div>

                        <!-- Access URL for running projects -->
                        @if($project->status === 'running' && $project->port && $project->server)
                            @php
                                $url = 'http://' . $project->server->ip_address . ':' . $project->port;
                            @endphp
                            <div class="mb-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg">
                                <p class="text-xs text-green-700 dark:text-green-400 font-medium mb-1">🚀 Live at:</p>
                                <a href="{{ $url }}" target="_blank" 
                                   class="text-sm text-green-800 dark:text-green-300 hover:text-green-900 dark:hover:text-green-200 font-mono break-all flex items-center transition-colors">
                                    {{ $url }}
                                    <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                </a>
                            </div>
                        @endif

                        <div class="flex justify-between items-center pt-4 border-t border-gray-200 dark:border-gray-700">
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $project->last_deployed_at ? $project->last_deployed_at->diffForHumans() : 'Never deployed' }}
                            </span>
                            <div class="flex space-x-2">
                                <a href="{{ route('projects.show', $project) }}" 
                                   class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 text-sm font-medium transition-colors">
                                    View
                                </a>
                                <button wire:click="deleteProject({{ $project->id }})" 
                                        wire:confirm="Are you sure you want to delete this project?"
                                        class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 text-sm font-medium transition-colors">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-4 transition-colors">
            {{ $projects->links() }}
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 text-center py-12 transition-colors">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No projects</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new project.</p>
            <div class="mt-6">
                <a href="{{ route('projects.create') }}" class="btn btn-primary">
                    + New Project
                </a>
            </div>
        </div>
    @endif
</div>
