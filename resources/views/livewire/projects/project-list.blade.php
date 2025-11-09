<div>
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Projects</h1>
            <p class="text-gray-600 mt-1">Manage your deployment projects</p>
        </div>
        <a href="{{ route('projects.create') }}" class="btn btn-primary">
            + New Project
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input wire:model.live="search" 
                       type="text" 
                       placeholder="Search projects..." 
                       class="input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
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
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <a href="{{ route('projects.show', $project) }}" class="text-xl font-bold text-gray-900 hover:text-blue-600">
                                    {{ $project->name }}
                                </a>
                                <p class="text-sm text-gray-500 mt-1">{{ $project->slug }}</p>
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
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"></path>
                                </svg>
                                {{ $project->server->name ?? 'No server' }}
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                                {{ $project->framework ?? 'Unknown' }}
                            </div>
                            @if($project->domains->count() > 0)
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                                    </svg>
                                    {{ $project->domains->first()->domain }}
                                </div>
                            @endif
                        </div>

                        <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                            <span class="text-xs text-gray-500">
                                {{ $project->last_deployed_at ? $project->last_deployed_at->diffForHumans() : 'Never deployed' }}
                            </span>
                            <div class="flex space-x-2">
                                <a href="{{ route('projects.show', $project) }}" 
                                   class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                    View
                                </a>
                                <button wire:click="deleteProject({{ $project->id }})" 
                                        wire:confirm="Are you sure you want to delete this project?"
                                        class="text-red-600 hover:text-red-700 text-sm font-medium">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            {{ $projects->links() }}
        </div>
    @else
        <div class="bg-white rounded-lg shadow text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No projects</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating a new project.</p>
            <div class="mt-6">
                <a href="{{ route('projects.create') }}" class="btn btn-primary">
                    + New Project
                </a>
            </div>
        </div>
    @endif
</div>

