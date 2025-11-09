<div>
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $server->name }}</h1>
            <p class="text-gray-600 mt-1">{{ $server->ip_address }} • {{ $server->hostname }}</p>
        </div>
        <div class="flex space-x-3">
            <button wire:click="pingServer" class="btn btn-secondary">
                Ping Server
            </button>
            <a href="{{ route('servers.index') }}" class="btn btn-secondary">
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

    <!-- Server Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Status</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">
                        <span class="px-3 py-1 rounded-full text-sm
                            @if($server->status === 'online') bg-green-100 text-green-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ ucfirst($server->status) }}
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600">CPU Cores</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ $server->cpu_cores ?? 'N/A' }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600">Memory</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">{{ $server->memory_gb ?? 'N/A' }} GB</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600">Docker</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">
                @if($server->docker_installed)
                    <span class="text-green-600">✓</span> {{ $server->docker_version }}
                @else
                    <span class="text-red-600">✗</span> Not Installed
                @endif
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Server Details -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Server Details</h2>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex justify-between">
                    <span class="text-gray-600">Operating System:</span>
                    <span class="font-medium">{{ $server->os ?? 'Unknown' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">SSH Port:</span>
                    <span class="font-medium">{{ $server->port }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Username:</span>
                    <span class="font-medium">{{ $server->username }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Location:</span>
                    <span class="font-medium">{{ $server->location_name ?? 'Unknown' }}</span>
                </div>
                @if($server->latitude && $server->longitude)
                    <div class="flex justify-between">
                        <span class="text-gray-600">GPS Coordinates:</span>
                        <span class="font-medium">{{ $server->latitude }}, {{ $server->longitude }}</span>
                    </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-gray-600">Last Ping:</span>
                    <span class="font-medium">{{ $server->last_ping_at ? $server->last_ping_at->diffForHumans() : 'Never' }}</span>
                </div>
            </div>
        </div>

        <!-- Recent Metrics -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Recent Metrics</h2>
            </div>
            <div class="p-6">
                @if($recentMetrics->count() > 0)
                    <div class="space-y-4">
                        @php $latestMetric = $recentMetrics->first(); @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">CPU Usage</span>
                                <span class="font-medium">{{ $latestMetric->cpu_usage }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $latestMetric->cpu_usage }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Memory Usage</span>
                                <span class="font-medium">{{ $latestMetric->memory_usage }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: {{ $latestMetric->memory_usage }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Disk Usage</span>
                                <span class="font-medium">{{ $latestMetric->disk_usage }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-yellow-600 h-2 rounded-full" style="width: {{ $latestMetric->disk_usage }}%"></div>
                            </div>
                        </div>
                    </div>
                @else
                    <p class="text-gray-500 text-center py-8">No metrics available</p>
                @endif
            </div>
        </div>

        <!-- Projects on Server -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Projects</h2>
            </div>
            <div class="p-6">
                @if($projects->count() > 0)
                    <div class="space-y-3">
                        @foreach($projects as $project)
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                                <a href="{{ route('projects.show', $project) }}" class="font-medium text-gray-900 hover:text-blue-600">
                                    {{ $project->name }}
                                </a>
                                <span class="text-sm text-gray-600">{{ ucfirst($project->status) }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-8">No projects on this server</p>
                @endif
            </div>
        </div>

        <!-- Recent Deployments -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Recent Deployments</h2>
            </div>
            <div class="p-6">
                @if($deployments->count() > 0)
                    <div class="space-y-3">
                        @foreach($deployments as $deployment)
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900">{{ $deployment->project->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $deployment->created_at->diffForHumans() }}</div>
                                </div>
                                <span class="px-2 py-1 rounded text-xs
                                    @if($deployment->status === 'success') bg-green-100 text-green-800
                                    @elseif($deployment->status === 'failed') bg-red-100 text-red-800
                                    @else bg-yellow-100 text-yellow-800
                                    @endif">
                                    {{ ucfirst($deployment->status) }}
                                </span>
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

