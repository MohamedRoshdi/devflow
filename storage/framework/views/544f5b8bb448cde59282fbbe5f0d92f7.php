<div>
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900"><?php echo e($server->name); ?></h1>
            <p class="text-gray-600 mt-1"><?php echo e($server->ip_address); ?> • <?php echo e($server->hostname); ?></p>
        </div>
        <div class="flex space-x-3">
            <?php if($server->docker_installed): ?>
                <a href="<?php echo e(route('docker.dashboard', $server)); ?>" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    🐳 Docker Management
                </a>
            <?php else: ?>
                <button wire:click="checkDockerStatus" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                    🔍 Detect Docker
                </button>
            <?php endif; ?>
            <button wire:click="pingServer" class="btn btn-secondary">
                Ping Server
            </button>
            <a href="<?php echo e(route('servers.index')); ?>" class="btn btn-secondary">
                Back to List
            </a>
        </div>
    </div>

    <?php if(session()->has('message')): ?>
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
            <?php echo e(session('message')); ?>

        </div>
    <?php endif; ?>

    <?php if(session()->has('error')): ?>
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
            <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?>

    <!-- Server Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Status</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">
                        <span class="px-3 py-1 rounded-full text-sm
                            <?php if($server->status === 'online'): ?> bg-green-100 text-green-800
                            <?php else: ?> bg-red-100 text-red-800
                            <?php endif; ?>">
                            <?php echo e(ucfirst($server->status)); ?>

                        </span>
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600">CPU Cores</p>
            <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo e($server->cpu_cores ?? 'N/A'); ?></p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600">Memory</p>
            <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo e($server->memory_gb ?? 'N/A'); ?> GB</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm font-medium text-gray-600">Docker</p>
            <p class="text-2xl font-bold text-gray-900 mt-2">
                <?php if($server->docker_installed): ?>
                    <span class="text-green-600">✓</span> <?php echo e($server->docker_version); ?>

                <?php else: ?>
                    <span class="text-red-600">✗</span> Not Installed
                <?php endif; ?>
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
                    <span class="font-medium"><?php echo e($server->os ?? 'Unknown'); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">SSH Port:</span>
                    <span class="font-medium"><?php echo e($server->port); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Username:</span>
                    <span class="font-medium"><?php echo e($server->username); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Location:</span>
                    <span class="font-medium"><?php echo e($server->location_name ?? 'Unknown'); ?></span>
                </div>
                <?php if($server->latitude && $server->longitude): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">GPS Coordinates:</span>
                        <span class="font-medium"><?php echo e($server->latitude); ?>, <?php echo e($server->longitude); ?></span>
                    </div>
                <?php endif; ?>
                <div class="flex justify-between">
                    <span class="text-gray-600">Last Ping:</span>
                    <span class="font-medium"><?php echo e($server->last_ping_at ? $server->last_ping_at->diffForHumans() : 'Never'); ?></span>
                </div>
            </div>
        </div>

        <!-- Recent Metrics -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Recent Metrics</h2>
            </div>
            <div class="p-6">
                <?php if($recentMetrics->count() > 0): ?>
                    <div class="space-y-4">
                        <?php $latestMetric = $recentMetrics->first(); ?>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">CPU Usage</span>
                                <span class="font-medium"><?php echo e($latestMetric->cpu_usage); ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo e($latestMetric->cpu_usage); ?>%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Memory Usage</span>
                                <span class="font-medium"><?php echo e($latestMetric->memory_usage); ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo e($latestMetric->memory_usage); ?>%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Disk Usage</span>
                                <span class="font-medium"><?php echo e($latestMetric->disk_usage); ?>%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-yellow-600 h-2 rounded-full" style="width: <?php echo e($latestMetric->disk_usage); ?>%"></div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-8">No metrics available</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Projects on Server -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Projects</h2>
            </div>
            <div class="p-6">
                <?php if($projects->count() > 0): ?>
                    <div class="space-y-3">
                        <?php $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                                <a href="<?php echo e(route('projects.show', $project)); ?>" class="font-medium text-gray-900 hover:text-blue-600">
                                    <?php echo e($project->name); ?>

                                </a>
                                <span class="text-sm text-gray-600"><?php echo e(ucfirst($project->status)); ?></span>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-8">No projects on this server</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Deployments -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Recent Deployments</h2>
            </div>
            <div class="p-6">
                <?php if($deployments->count() > 0): ?>
                    <div class="space-y-3">
                        <?php $__currentLoopData = $deployments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $deployment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900"><?php echo e($deployment->project->name); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo e($deployment->created_at->diffForHumans()); ?></div>
                                </div>
                                <span class="px-2 py-1 rounded text-xs
                                    <?php if($deployment->status === 'success'): ?> bg-green-100 text-green-800
                                    <?php elseif($deployment->status === 'failed'): ?> bg-red-100 text-red-800
                                    <?php else: ?> bg-yellow-100 text-yellow-800
                                    <?php endif; ?>">
                                    <?php echo e(ucfirst($deployment->status)); ?>

                                </span>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-8">No deployments yet</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php /**PATH /home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/livewire/servers/server-show.blade.php ENDPATH**/ ?>