<div wire:poll.3s="refresh">
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-white">Deployment #{{ $deployment->id }}</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $deployment->project->name }}</p>
        </div>
        <div class="flex space-x-3">
            @if(in_array($deployment->status, ['pending', 'running']))
                <button wire:click="refresh" class="btn btn-sm btn-secondary">
                    <span wire:loading.remove wire:target="refresh">🔄 Refresh</span>
                    <span wire:loading wire:target="refresh">Refreshing...</span>
                </button>
            @endif
            <a href="{{ route('deployments.index') }}" class="btn btn-secondary">
                Back to List
            </a>
        </div>
    </div>

    <!-- Progress Bar (shown during deployment) -->
    @if(in_array($deployment->status, ['pending', 'running']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
            <div class="mb-4">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-white">
                        @if($deployment->status === 'running')
                            🚀 Deployment in Progress
                        @else
                            ⏳ Deployment Pending
                        @endif
                    </h3>
                    <span class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">{{ $progress }}%</span>
                </div>
                
                <!-- Progress Bar -->
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                    <div class="bg-blue-600 h-3 rounded-full transition-all duration-500 ease-out relative overflow-hidden"
                         style="width: {{ $progress }}%">
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-30 animate-shimmer"></div>
                    </div>
                </div>
                
                @if($currentStep)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-3 flex items-center">
                        <svg class="animate-spin h-4 w-4 mr-2 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="font-medium">{{ $currentStep }}</span>
                    </p>
                @endif
            </div>
            
            <!-- Estimated Time -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
                <p class="text-sm text-blue-900">
                    ⏱️ <strong>Estimated time:</strong> 12-18 minutes
                    @if($deployment->started_at)
                        <span class="ml-2">• Running for {{ $deployment->started_at->diffForHumans(null, true) }}</span>
                    @endif
                </p>
                <p class="text-xs text-blue-700 mt-1">
                    Large builds with npm can take time. The page auto-refreshes every 3 seconds.
                </p>
            </div>
        </div>
    @endif

    <!-- Status Card -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400">Status</p>
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
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400">Branch</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white mt-2">{{ $deployment->branch }}</p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400">Duration</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white mt-2">
                    @if($deployment->duration_seconds)
                        {{ $deployment->duration_seconds }}s ({{ number_format($deployment->duration_seconds / 60, 1) }} min)
                    @elseif($deployment->started_at)
                        {{ $deployment->started_at->diffForHumans(null, true) }} (in progress)
                    @else
                        Pending...
                    @endif
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400 dark:text-gray-400">Triggered By</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white mt-2">{{ ucfirst($deployment->triggered_by) }}</p>
            </div>
        </div>
    </div>

    <style>
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        .animate-shimmer {
            animation: shimmer 2s infinite;
        }
    </style>

    <!-- Details -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white dark:text-white">Deployment Details</h2>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400 dark:text-gray-400">Project:</span>
                    <a href="{{ route('projects.show', $deployment->project) }}" 
                       class="font-medium text-blue-600 hover:text-blue-800 hover:underline">
                        {{ $deployment->project->name }}
                    </a>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400 dark:text-gray-400">Server:</span>
                    <span class="font-medium">{{ $deployment->server->name ?? 'None' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400 dark:text-gray-400">Commit Hash:</span>
                    <span class="font-medium font-mono text-sm">{{ substr($deployment->commit_hash ?? 'N/A', 0, 8) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400 dark:text-gray-400">Started At:</span>
                    <span class="font-medium">{{ $deployment->started_at ? $deployment->started_at->format('Y-m-d H:i:s') : '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400 dark:text-gray-400">Completed At:</span>
                    <span class="font-medium">{{ $deployment->completed_at ? $deployment->completed_at->format('Y-m-d H:i:s') : 'In progress' }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white dark:text-white">Commit Information</h2>
            </div>
            <div class="p-6">
                <p class="text-gray-900 dark:text-white dark:text-white">{{ $deployment->commit_message ?? 'No commit message available' }}</p>
            </div>
        </div>
    </div>

    <!-- Deployment Steps Progress -->
    @if(in_array($deployment->status, ['pending', 'running']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-8">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white dark:text-white">Deployment Steps</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @php
                        $logs = $deployment->output_log ?? '';
                        $steps = [
                            ['name' => 'Clone Repository', 'marker' => '=== Cloning Repository ===', 'complete_marker' => '✓ Repository cloned successfully'],
                            ['name' => 'Record Commit Info', 'marker' => '=== Recording Commit Information ===', 'complete_marker' => '✓ Commit information recorded'],
                            ['name' => 'Build Docker Image', 'marker' => '=== Building Docker Container ===', 'complete_marker' => '✓ Build successful'],
                            ['name' => 'Start Container', 'marker' => '=== Starting Container ===', 'complete_marker' => 'Container started'],
                        ];
                    @endphp
                    
                    @foreach($steps as $step)
                        @php
                            $isActive = str_contains($logs, $step['marker']);
                            $isComplete = str_contains($logs, $step['complete_marker']);
                        @endphp
                        
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                @if($isComplete)
                                    <div class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center">
                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                @elseif($isActive)
                                    <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center">
                                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                @else
                                    <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                        <span class="text-gray-500 dark:text-gray-400 text-sm">{{ $loop->iteration }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1">
                                <p class="font-medium {{ $isActive ? 'text-blue-600' : ($isComplete ? 'text-green-600' : 'text-gray-500') }}">
                                    {{ $step['name'] }}
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                @if($isComplete)
                                    <span class="text-xs text-green-600 font-medium">✓ Complete</span>
                                @elseif($isActive)
                                    <span class="text-xs text-blue-600 font-medium">● In Progress</span>
                                @else
                                    <span class="text-xs text-gray-400">Pending</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Logs -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white dark:text-white">Deployment Logs</h2>
            @if(in_array($deployment->status, ['pending', 'running']))
                <span class="flex items-center text-sm text-blue-600">
                    <span class="h-2 w-2 bg-blue-600 rounded-full mr-2 animate-pulse"></span>
                    Live updating
                </span>
            @endif
        </div>
        <div class="p-6">
            @if($deployment->output_log)
                <div class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-sm overflow-x-auto max-h-96 overflow-y-auto"
                     id="deployment-logs"
                     x-data="{ autoScroll: true }"
                     x-init="
                        $watch('$wire.deployment.output_log', value => {
                            if (autoScroll) {
                                $nextTick(() => {
                                    $el.scrollTop = $el.scrollHeight;
                                });
                            }
                        });
                        $el.scrollTop = $el.scrollHeight;
                     "
                     @scroll="autoScroll = ($el.scrollHeight - $el.scrollTop - $el.clientHeight) < 10">
                    <pre>{{ $deployment->output_log }}</pre>
                </div>
                @if(in_array($deployment->status, ['pending', 'running']))
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 text-center">
                        💡 Logs auto-scroll to bottom. Scroll up to pause auto-scrolling.
                    </p>
                @endif
            @elseif(in_array($deployment->status, ['pending', 'running']))
                <div class="text-center py-12">
                    <svg class="animate-spin h-12 w-12 mx-auto text-blue-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400">⏳ Starting deployment...</p>
                    <p class="text-xs text-gray-400 mt-2">Logs will appear shortly</p>
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-center py-8">No logs available</p>
            @endif
        </div>
    </div>

    @if($deployment->error_log)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mt-8">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                <h2 class="text-xl font-bold text-red-600 dark:text-red-400">❌ Error Logs</h2>
            </div>
            <div class="p-6">
                <div class="bg-red-50 text-red-900 p-4 rounded-lg font-mono text-sm overflow-x-auto max-h-96 overflow-y-auto">
                    <pre>{{ $deployment->error_log }}</pre>
                </div>
            </div>
        </div>
    @endif
</div>

