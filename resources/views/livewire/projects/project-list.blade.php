<div>
    <!-- Hero Section with Gradient -->
    <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-green-500 via-emerald-500 to-teal-500 dark:from-green-600 dark:via-emerald-600 dark:to-teal-600 p-8 shadow-xl overflow-hidden">
        <div class="absolute inset-0 bg-black/10 dark:bg-black/20 backdrop-blur-sm"></div>
        <div class="relative z-10 flex justify-between items-center">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <div class="p-2 bg-white/20 dark:bg-white/10 backdrop-blur-md rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                        </svg>
                    </div>
                    <h1 class="text-4xl font-bold text-white">Projects Management</h1>
                </div>
                <p class="text-white/90 text-lg">Manage and deploy your applications</p>
            </div>
            <a href="{{ route('projects.create') }}" class="bg-white/20 hover:bg-white/30 backdrop-blur-md text-white font-semibold px-6 py-3 rounded-lg transition-all duration-300 hover:scale-105 shadow-lg">
                + New Project
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 p-6 mb-6">
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
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 cursor-pointer relative group overflow-hidden"
                     onclick="window.location='{{ route('projects.show', $project) }}'">
                    <!-- Gradient Border Top -->
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r
                        @if($project->status === 'running') from-green-500 to-emerald-600
                        @elseif($project->status === 'stopped') from-gray-400 to-gray-500
                        @elseif($project->status === 'building') from-yellow-500 to-orange-500
                        @else from-red-500 to-red-600
                        @endif">
                    </div>

                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <a href="{{ route('projects.show', $project) }}" class="text-xl font-bold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                    {{ $project->name }}
                                </a>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $project->slug }}</p>
                            </div>
                            <div class="p-2 rounded-lg
                                @if($project->status === 'running') bg-gradient-to-br from-green-500 to-emerald-600
                                @elseif($project->status === 'stopped') bg-gradient-to-br from-gray-400 to-gray-500
                                @elseif($project->status === 'building') bg-gradient-to-br from-yellow-500 to-orange-500
                                @else bg-gradient-to-br from-red-500 to-red-600
                                @endif">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($project->status === 'running')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    @elseif($project->status === 'building')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    @endif
                                </svg>
                            </div>
                        </div>

                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                <div class="p-1 bg-blue-100 dark:bg-blue-900/30 rounded mr-2">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"></path>
                                    </svg>
                                </div>
                                {{ $project->server->name ?? 'No server' }}
                            </div>
                            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                <div class="p-1 bg-purple-100 dark:bg-purple-900/30 rounded mr-2">
                                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                    </svg>
                                </div>
                                {{ $project->framework ?? 'Unknown' }}
                            </div>
                            @if($project->domains->count() > 0)
                                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    <div class="p-1 bg-green-100 dark:bg-green-900/30 rounded mr-2">
                                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                                        </svg>
                                    </div>
                                    {{ $project->domains->first()->domain }}
                                </div>
                            @endif
                        </div>

                        <!-- Access URL for running projects -->
                        @if($project->status === 'running' && $project->port && $project->server)
                            @php
                                $url = 'http://' . $project->server->ip_address . ':' . $project->port;
                            @endphp
                            <div class="mb-3 p-3 bg-gradient-to-br from-green-500/10 to-emerald-500/10 dark:from-green-500/20 dark:to-emerald-500/20 border border-green-200 dark:border-green-700 rounded-lg backdrop-blur-sm">
                                <p class="text-xs text-green-700 dark:text-green-400 font-medium mb-1">🚀 Live at:</p>
                                <a href="{{ $url }}" target="_blank"
                                   onclick="event.stopPropagation()"
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
                            <div class="flex space-x-2" onclick="event.stopPropagation()">
                                <a href="{{ route('projects.show', $project) }}"
                                   class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 text-sm font-medium transition-colors">
                                    View
                                </a>
                                <button wire:click="deleteProject({{ $project->id }})"
                                        wire:confirm="Are you sure you want to delete this project?"
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-50"
                                        wire:target="deleteProject({{ $project->id }})"
                                        class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 text-sm font-medium transition-colors">
                                    <span wire:loading.remove wire:target="deleteProject({{ $project->id }})">Delete</span>
                                    <span wire:loading wire:target="deleteProject({{ $project->id }})">Deleting...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-4 transition-colors">
            {{ $projects->links() }}
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl text-center py-12 transition-colors">
            <div class="p-4 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                <svg class="h-10 w-10 text-gray-400 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No projects</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Get started by creating a new project.</p>
            <a href="{{ route('projects.create') }}" class="inline-block bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold px-6 py-3 rounded-lg transition-all duration-300 hover:scale-105 shadow-lg">
                + New Project
            </a>
        </div>
    @endif
</div>
