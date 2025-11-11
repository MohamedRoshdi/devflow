<div wire:poll.3s="refresh">
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Deployment #<?php echo e($deployment->id); ?></h1>
            <p class="text-gray-600 mt-1"><?php echo e($deployment->project->name); ?></p>
        </div>
        <div class="flex space-x-3">
            <?php if(in_array($deployment->status, ['pending', 'running'])): ?>
                <button wire:click="refresh" class="btn btn-sm btn-secondary">
                    <span wire:loading.remove wire:target="refresh">🔄 Refresh</span>
                    <span wire:loading wire:target="refresh">Refreshing...</span>
                </button>
            <?php endif; ?>
            <a href="<?php echo e(route('deployments.index')); ?>" class="btn btn-secondary">
                Back to List
            </a>
        </div>
    </div>

    <!-- Progress Bar (shown during deployment) -->
    <?php if(in_array($deployment->status, ['pending', 'running'])): ?>
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <div class="mb-4">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-lg font-semibold text-gray-900">
                        <?php if($deployment->status === 'running'): ?>
                            🚀 Deployment in Progress
                        <?php else: ?>
                            ⏳ Deployment Pending
                        <?php endif; ?>
                    </h3>
                    <span class="text-sm text-gray-600"><?php echo e($progress); ?>%</span>
                </div>
                
                <!-- Progress Bar -->
                <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                    <div class="bg-blue-600 h-3 rounded-full transition-all duration-500 ease-out relative overflow-hidden"
                         style="width: <?php echo e($progress); ?>%">
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-30 animate-shimmer"></div>
                    </div>
                </div>
                
                <?php if($currentStep): ?>
                    <p class="text-sm text-gray-600 mt-3 flex items-center">
                        <svg class="animate-spin h-4 w-4 mr-2 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="font-medium"><?php echo e($currentStep); ?></span>
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- Estimated Time -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
                <p class="text-sm text-blue-900">
                    ⏱️ <strong>Estimated time:</strong> 12-18 minutes
                    <?php if($deployment->started_at): ?>
                        <span class="ml-2">• Running for <?php echo e($deployment->started_at->diffForHumans(null, true)); ?></span>
                    <?php endif; ?>
                </p>
                <p class="text-xs text-blue-700 mt-1">
                    Large builds with npm can take time. The page auto-refreshes every 3 seconds.
                </p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Status Card -->
    <div class="bg-white rounded-lg shadow p-8 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <p class="text-sm font-medium text-gray-600">Status</p>
                <p class="mt-2">
                    <span class="px-4 py-2 rounded-full text-sm font-medium
                        <?php if($deployment->status === 'success'): ?> bg-green-100 text-green-800
                        <?php elseif($deployment->status === 'failed'): ?> bg-red-100 text-red-800
                        <?php elseif($deployment->status === 'running'): ?> bg-yellow-100 text-yellow-800
                        <?php else: ?> bg-gray-100 text-gray-800
                        <?php endif; ?>">
                        <?php echo e(ucfirst($deployment->status)); ?>

                    </span>
                </p>
            </div>
            
            <div>
                <p class="text-sm font-medium text-gray-600">Branch</p>
                <p class="text-lg font-bold text-gray-900 mt-2"><?php echo e($deployment->branch); ?></p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-600">Duration</p>
                <p class="text-lg font-bold text-gray-900 mt-2">
                    <?php if($deployment->duration_seconds): ?>
                        <?php echo e($deployment->duration_seconds); ?>s (<?php echo e(number_format($deployment->duration_seconds / 60, 1)); ?> min)
                    <?php elseif($deployment->started_at): ?>
                        <?php echo e($deployment->started_at->diffForHumans(null, true)); ?> (in progress)
                    <?php else: ?>
                        Pending...
                    <?php endif; ?>
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-600">Triggered By</p>
                <p class="text-lg font-bold text-gray-900 mt-2"><?php echo e(ucfirst($deployment->triggered_by)); ?></p>
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
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Deployment Details</h2>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex justify-between">
                    <span class="text-gray-600">Project:</span>
                    <a href="<?php echo e(route('projects.show', $deployment->project)); ?>" 
                       class="font-medium text-blue-600 hover:text-blue-800 hover:underline">
                        <?php echo e($deployment->project->name); ?>

                    </a>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Server:</span>
                    <span class="font-medium"><?php echo e($deployment->server->name ?? 'None'); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Commit Hash:</span>
                    <span class="font-medium font-mono text-sm"><?php echo e(substr($deployment->commit_hash ?? 'N/A', 0, 8)); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Started At:</span>
                    <span class="font-medium"><?php echo e($deployment->started_at ? $deployment->started_at->format('Y-m-d H:i:s') : '-'); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Completed At:</span>
                    <span class="font-medium"><?php echo e($deployment->completed_at ? $deployment->completed_at->format('Y-m-d H:i:s') : 'In progress'); ?></span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Commit Information</h2>
            </div>
            <div class="p-6">
                <p class="text-gray-900"><?php echo e($deployment->commit_message ?? 'No commit message available'); ?></p>
            </div>
        </div>
    </div>

    <!-- Deployment Steps Progress -->
    <?php if(in_array($deployment->status, ['pending', 'running'])): ?>
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Deployment Steps</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <?php
                        $logs = $deployment->output_log ?? '';
                        $steps = [
                            ['name' => 'Clone Repository', 'marker' => '=== Cloning Repository ===', 'complete_marker' => '✓ Repository cloned successfully'],
                            ['name' => 'Record Commit Info', 'marker' => '=== Recording Commit Information ===', 'complete_marker' => '✓ Commit information recorded'],
                            ['name' => 'Build Docker Image', 'marker' => '=== Building Docker Container ===', 'complete_marker' => '✓ Build successful'],
                            ['name' => 'Start Container', 'marker' => '=== Starting Container ===', 'complete_marker' => 'Container started'],
                        ];
                    ?>
                    
                    <?php $__currentLoopData = $steps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $isActive = str_contains($logs, $step['marker']);
                            $isComplete = str_contains($logs, $step['complete_marker']);
                        ?>
                        
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <?php if($isComplete): ?>
                                    <div class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center">
                                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                <?php elseif($isActive): ?>
                                    <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center">
                                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                <?php else: ?>
                                    <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                        <span class="text-gray-500 text-sm"><?php echo e($loop->iteration); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium <?php echo e($isActive ? 'text-blue-600' : ($isComplete ? 'text-green-600' : 'text-gray-500')); ?>">
                                    <?php echo e($step['name']); ?>

                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <?php if($isComplete): ?>
                                    <span class="text-xs text-green-600 font-medium">✓ Complete</span>
                                <?php elseif($isActive): ?>
                                    <span class="text-xs text-blue-600 font-medium">● In Progress</span>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">Pending</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Logs -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-900">Deployment Logs</h2>
            <?php if(in_array($deployment->status, ['pending', 'running'])): ?>
                <span class="flex items-center text-sm text-blue-600">
                    <span class="h-2 w-2 bg-blue-600 rounded-full mr-2 animate-pulse"></span>
                    Live updating
                </span>
            <?php endif; ?>
        </div>
        <div class="p-6">
            <?php if($deployment->output_log): ?>
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
                    <pre><?php echo e($deployment->output_log); ?></pre>
                </div>
                <?php if(in_array($deployment->status, ['pending', 'running'])): ?>
                    <p class="text-xs text-gray-500 mt-2 text-center">
                        💡 Logs auto-scroll to bottom. Scroll up to pause auto-scrolling.
                    </p>
                <?php endif; ?>
            <?php elseif(in_array($deployment->status, ['pending', 'running'])): ?>
                <div class="text-center py-12">
                    <svg class="animate-spin h-12 w-12 mx-auto text-blue-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-gray-500">⏳ Starting deployment...</p>
                    <p class="text-xs text-gray-400 mt-2">Logs will appear shortly</p>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-8">No logs available</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if($deployment->error_log): ?>
        <div class="bg-white rounded-lg shadow mt-8">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-red-600">❌ Error Logs</h2>
            </div>
            <div class="p-6">
                <div class="bg-red-50 text-red-900 p-4 rounded-lg font-mono text-sm overflow-x-auto max-h-96 overflow-y-auto">
                    <pre><?php echo e($deployment->error_log); ?></pre>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php /**PATH /home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/livewire/deployments/deployment-show.blade.php ENDPATH**/ ?>