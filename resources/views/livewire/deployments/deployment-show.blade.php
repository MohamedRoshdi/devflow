<div>
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Deployment #{{ $deployment->id }}</h1>
            <p class="text-gray-600 mt-1">{{ $deployment->project->name }}</p>
        </div>
        <a href="{{ route('deployments.index') }}" class="btn btn-secondary">
            Back to List
        </a>
    </div>

    <!-- Status Card -->
    <div class="bg-white rounded-lg shadow p-8 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <p class="text-sm font-medium text-gray-600">Status</p>
                <p class="mt-2">
                    <span class="px-4 py-2 rounded-full text-sm font-medium
                        @if($deployment->status === 'success') bg-green-100 text-green-800
                        @elseif($deployment->status === 'failed') bg-red-100 text-red-800
                        @elseif($deployment->status === 'running') bg-yellow-100 text-yellow-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ ucfirst($deployment->status) }}
                    </span>
                </p>
            </div>
            
            <div>
                <p class="text-sm font-medium text-gray-600">Branch</p>
                <p class="text-lg font-bold text-gray-900 mt-2">{{ $deployment->branch }}</p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-600">Duration</p>
                <p class="text-lg font-bold text-gray-900 mt-2">
                    {{ $deployment->duration_seconds ? $deployment->duration_seconds . 's' : 'In progress...' }}
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-600">Triggered By</p>
                <p class="text-lg font-bold text-gray-900 mt-2">{{ ucfirst($deployment->triggered_by) }}</p>
            </div>
        </div>
    </div>

    <!-- Details -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Deployment Details</h2>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex justify-between">
                    <span class="text-gray-600">Project:</span>
                    <a href="{{ route('projects.show', $deployment->project) }}" 
                       class="font-medium text-blue-600 hover:text-blue-800 hover:underline">
                        {{ $deployment->project->name }}
                    </a>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Server:</span>
                    <span class="font-medium">{{ $deployment->server->name ?? 'None' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Commit Hash:</span>
                    <span class="font-medium font-mono text-sm">{{ substr($deployment->commit_hash ?? 'N/A', 0, 8) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Started At:</span>
                    <span class="font-medium">{{ $deployment->started_at ? $deployment->started_at->format('Y-m-d H:i:s') : '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Completed At:</span>
                    <span class="font-medium">{{ $deployment->completed_at ? $deployment->completed_at->format('Y-m-d H:i:s') : 'In progress' }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Commit Information</h2>
            </div>
            <div class="p-6">
                <p class="text-gray-900">{{ $deployment->commit_message ?? 'No commit message available' }}</p>
            </div>
        </div>
    </div>

    <!-- Logs -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">Deployment Logs</h2>
        </div>
        <div class="p-6">
            @if($deployment->output_log)
                <div class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-sm overflow-x-auto">
                    <pre>{{ $deployment->output_log }}</pre>
                </div>
            @else
                <p class="text-gray-500 text-center py-8">No logs available yet</p>
            @endif
        </div>
    </div>

    @if($deployment->error_log)
        <div class="bg-white rounded-lg shadow mt-8">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-red-600">Error Logs</h2>
            </div>
            <div class="p-6">
                <div class="bg-red-50 text-red-900 p-4 rounded-lg font-mono text-sm overflow-x-auto">
                    <pre>{{ $deployment->error_log }}</pre>
                </div>
            </div>
        </div>
    @endif
</div>

