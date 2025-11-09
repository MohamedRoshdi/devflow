<div>
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $project->name }}</h1>
            <p class="text-gray-600 mt-1">{{ $project->slug }}</p>
        </div>
        <div class="flex space-x-3">
            @if($project->status === 'running')
                <button wire:click="stopProject" wire:confirm="Stop this project?" class="btn btn-danger">
                    Stop Project
                </button>
            @else
                <button wire:click="startProject" class="btn btn-success">
                    Start Project
                </button>
            @endif
            <button wire:click="$set('showDeployModal', true)" class="btn btn-primary">
                🚀 Deploy
            </button>
            <a href="{{ route('projects.edit', $project) }}" class="btn btn-secondary">
                ✏️ Edit
            </a>
            <a href="{{ route('projects.index') }}" class="btn btn-secondary">
                Back to List
            </a>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    <!-- Deploy Modal -->
    @if($showDeployModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Deploy Project</h3>
                    <p class="text-sm text-gray-500 mb-6">
                        This will deploy the latest changes from <strong>{{ $project->branch }}</strong> branch.
                    </p>
                    <div class="flex justify-end space-x-3">
                        <button wire:click="$set('showDeployModal', false)" class="btn btn-secondary">
                            Cancel
                        </button>
                        <button wire:click="deploy" class="btn btn-primary">
                            Deploy Now
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Project Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600">Status</p>
            <p class="text-2xl font-bold mt-2">
                <span class="px-3 py-1 rounded-full text-sm
                    @if($project->status === 'running') bg-green-100 text-green-800
                    @elseif($project->status === 'stopped') bg-gray-100 text-gray-800
                    @elseif($project->status === 'building') bg-yellow-100 text-yellow-800
                    @else bg-red-100 text-red-800
                    @endif">
                    {{ ucfirst($project->status) }}
                </span>
            </p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600">Deployments</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ $project->deployments()->count() }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600">Domains</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ $project->domains->count() }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600">Storage Used</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ number_format($project->storage_used_mb / 1024, 2) }} GB</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Project Details -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Project Details</h2>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex justify-between">
                    <span class="text-gray-600">Server:</span>
                    <span class="font-medium">{{ $project->server->name ?? 'None' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Framework:</span>
                    <span class="font-medium">{{ $project->framework ?? 'Unknown' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">PHP Version:</span>
                    <span class="font-medium">{{ $project->php_version ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Node Version:</span>
                    <span class="font-medium">{{ $project->node_version ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Branch:</span>
                    <span class="font-medium">{{ $project->branch }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Auto Deploy:</span>
                    <span class="font-medium">{{ $project->auto_deploy ? 'Enabled' : 'Disabled' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Last Deployed:</span>
                    <span class="font-medium">{{ $project->last_deployed_at ? $project->last_deployed_at->diffForHumans() : 'Never' }}</span>
                </div>
            </div>
        </div>

        <!-- Domains -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900">Domains</h2>
                <button class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                    + Add Domain
                </button>
            </div>
            <div class="p-6">
                @if($domains->count() > 0)
                    <div class="space-y-3">
                        @foreach($domains as $domain)
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900">{{ $domain->domain }}</div>
                                    <div class="flex items-center space-x-2 mt-1">
                                        @if($domain->ssl_enabled)
                                            <span class="text-xs text-green-600">🔒 SSL Active</span>
                                        @else
                                            <span class="text-xs text-gray-500">🔓 No SSL</span>
                                        @endif
                                        @if($domain->is_primary)
                                            <span class="px-2 py-0.5 bg-blue-100 text-blue-800 text-xs rounded">Primary</span>
                                        @endif
                                    </div>
                                </div>
                                <span class="text-sm text-gray-600">{{ ucfirst($domain->status) }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-8">No domains configured</p>
                @endif
            </div>
        </div>

        <!-- Recent Deployments -->
        <div class="bg-white rounded-lg shadow lg:col-span-2">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Recent Deployments</h2>
            </div>
            <div class="p-6">
                @if($deployments->count() > 0)
                    <div class="space-y-4">
                        @foreach($deployments as $deployment)
                            <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <span class="px-3 py-1 rounded-full text-xs font-medium
                                            @if($deployment->status === 'success') bg-green-100 text-green-800
                                            @elseif($deployment->status === 'failed') bg-red-100 text-red-800
                                            @elseif($deployment->status === 'running') bg-yellow-100 text-yellow-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucfirst($deployment->status) }}
                                        </span>
                                        <span class="text-sm text-gray-900">
                                            {{ $deployment->commit_message ?? 'No message' }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ $deployment->created_at->diffForHumans() }}
                                        @if($deployment->duration_seconds)
                                            • Duration: {{ $deployment->duration_seconds }}s
                                        @endif
                                    </div>
                                </div>
                                <a href="{{ route('deployments.show', $deployment) }}" 
                                   class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                    View
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-8">No deployments yet</p>
                @endif
            </div>
        </div>
    </div>
</div>

