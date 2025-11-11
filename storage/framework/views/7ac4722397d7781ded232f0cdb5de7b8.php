<div class="space-y-6">
    
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Docker Management</h2>
            <p class="text-sm text-gray-600">Server: <?php echo e($server->name); ?></p>
        </div>
        <button wire:click="loadDockerInfo" 
                class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition"
                wire:loading.attr="disabled">
            <span wire:loading.remove>🔄 Refresh</span>
            <span wire:loading>Loading...</span>
        </button>
    </div>

    
    <?php if(session()->has('message')): ?>
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            <?php echo e(session('message')); ?>

        </div>
    <?php endif; ?>

    
    <?php if($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <?php echo e($error); ?>

        </div>
    <?php endif; ?>

    
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <button wire:click="switchTab('overview')" 
                    class="<?php if($activeTab === 'overview'): ?> border-blue-500 text-blue-600 <?php else: ?> border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 <?php endif; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                📊 Overview
            </button>
            <button wire:click="switchTab('images')" 
                    class="<?php if($activeTab === 'images'): ?> border-blue-500 text-blue-600 <?php else: ?> border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 <?php endif; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                🖼️ Images (<?php echo e(count($images)); ?>)
            </button>
            <button wire:click="switchTab('volumes')" 
                    class="<?php if($activeTab === 'volumes'): ?> border-blue-500 text-blue-600 <?php else: ?> border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 <?php endif; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                💾 Volumes (<?php echo e(count($volumes)); ?>)
            </button>
            <button wire:click="switchTab('networks')" 
                    class="<?php if($activeTab === 'networks'): ?> border-blue-500 text-blue-600 <?php else: ?> border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 <?php endif; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                🌐 Networks (<?php echo e(count($networks)); ?>)
            </button>
            <button wire:click="switchTab('cleanup')" 
                    class="<?php if($activeTab === 'cleanup'): ?> border-blue-500 text-blue-600 <?php else: ?> border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 <?php endif; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                🧹 Cleanup
            </button>
        </nav>
    </div>

    
    <?php if($activeTab === 'overview'): ?>
        <div class="space-y-6">
            
            <?php if($dockerInfo): ?>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Docker System Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="text-sm text-gray-600">Docker Version</div>
                            <div class="text-xl font-bold"><?php echo e($dockerInfo['ServerVersion'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="text-sm text-gray-600">Total Containers</div>
                            <div class="text-xl font-bold text-blue-600"><?php echo e($dockerInfo['Containers'] ?? 0); ?></div>
                            <div class="text-xs text-gray-500">
                                Running: <?php echo e($dockerInfo['ContainersRunning'] ?? 0); ?> | 
                                Stopped: <?php echo e($dockerInfo['ContainersStopped'] ?? 0); ?>

                            </div>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="text-sm text-gray-600">Total Images</div>
                            <div class="text-xl font-bold text-green-600"><?php echo e($dockerInfo['Images'] ?? 0); ?></div>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <div class="text-sm text-gray-600">Storage Driver</div>
                            <div class="text-lg font-semibold"><?php echo e($dockerInfo['Driver'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="bg-yellow-50 p-4 rounded-lg">
                            <div class="text-sm text-gray-600">Operating System</div>
                            <div class="text-lg font-semibold"><?php echo e($dockerInfo['OperatingSystem'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="bg-indigo-50 p-4 rounded-lg">
                            <div class="text-sm text-gray-600">CPU Cores</div>
                            <div class="text-xl font-bold text-indigo-600"><?php echo e($dockerInfo['NCPU'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            
            <?php if($diskUsage): ?>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Disk Usage</h3>
                    <div class="space-y-4">
                        <?php $__currentLoopData = $diskUsage; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <div class="font-semibold"><?php echo e($item['Type'] ?? 'Unknown'); ?></div>
                                    <div class="text-sm text-gray-600">
                                        Total: <?php echo e($item['TotalCount'] ?? 0); ?> | 
                                        Active: <?php echo e($item['Active'] ?? 0); ?>

                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold"><?php echo e($item['Size'] ?? 'N/A'); ?></div>
                                    <div class="text-sm text-orange-600">
                                        Reclaimable: <?php echo e($item['Reclaimable'] ?? '0'); ?>

                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    
    <?php if($activeTab === 'images'): ?>
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold">Docker Images</h3>
                <button wire:click="pruneImages" 
                        class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition"
                        wire:loading.attr="disabled">
                    🧹 Prune Unused Images
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Repository</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tag</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php $__empty_1 = true; $__currentLoopData = $images; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo e($image['Repository'] ?? 'N/A'); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo e($image['Tag'] ?? 'N/A'); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                    <?php echo e(Str::limit($image['ID'] ?? 'N/A', 12)); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo e($image['Size'] ?? 'N/A'); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo e($image['CreatedAt'] ?? 'N/A'); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <button wire:click="deleteImage('<?php echo e($image['ID']); ?>')" 
                                            class="text-red-600 hover:text-red-900"
                                            onclick="return confirm('Are you sure you want to delete this image?')">
                                        🗑️ Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">No images found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    
    <?php if($activeTab === 'volumes'): ?>
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold">Docker Volumes</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Driver</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mountpoint</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php $__empty_1 = true; $__currentLoopData = $volumes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $volume): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo e($volume['Name'] ?? 'N/A'); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo e($volume['Driver'] ?? 'N/A'); ?>

                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 font-mono text-xs">
                                    <?php echo e(Str::limit($volume['Mountpoint'] ?? 'N/A', 50)); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <button wire:click="deleteVolume('<?php echo e($volume['Name']); ?>')" 
                                            class="text-red-600 hover:text-red-900"
                                            onclick="return confirm('Are you sure? This will permanently delete all data in this volume!')">
                                        🗑️ Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">No volumes found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    
    <?php if($activeTab === 'networks'): ?>
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold">Docker Networks</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Driver</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scope</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php $__empty_1 = true; $__currentLoopData = $networks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $network): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo e($network['Name'] ?? 'N/A'); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                    <?php echo e(Str::limit($network['ID'] ?? 'N/A', 12)); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo e($network['Driver'] ?? 'N/A'); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo e($network['Scope'] ?? 'N/A'); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php if(!in_array($network['Name'], ['bridge', 'host', 'none'])): ?>
                                        <button wire:click="deleteNetwork('<?php echo e($network['Name']); ?>')" 
                                                class="text-red-600 hover:text-red-900"
                                                onclick="return confirm('Are you sure you want to delete this network?')">
                                            🗑️ Delete
                                        </button>
                                    <?php else: ?>
                                        <span class="text-gray-400">System Network</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">No networks found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    
    <?php if($activeTab === 'cleanup'): ?>
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">System Cleanup</h3>
                <p class="text-gray-600 mb-6">Free up disk space by removing unused Docker resources.</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="border border-gray-200 rounded-lg p-6">
                        <h4 class="font-semibold mb-2">🖼️ Prune Images</h4>
                        <p class="text-sm text-gray-600 mb-4">Remove dangling and unused images</p>
                        <button wire:click="pruneImages" 
                                class="w-full px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition"
                                wire:loading.attr="disabled">
                            Prune Images
                        </button>
                    </div>
                    
                    <div class="border border-gray-200 rounded-lg p-6">
                        <h4 class="font-semibold mb-2">🧹 System Prune</h4>
                        <p class="text-sm text-gray-600 mb-4">Remove all unused containers, networks, and images</p>
                        <button wire:click="systemPrune" 
                                class="w-full px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition"
                                wire:loading.attr="disabled"
                                onclick="return confirm('This will remove all unused Docker resources. Continue?')">
                            System Prune
                        </button>
                    </div>
                </div>

                <?php if($diskUsage): ?>
                    <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                        <h4 class="font-semibold mb-2">💡 Disk Space Summary</h4>
                        <div class="text-sm space-y-1">
                            <?php $__currentLoopData = $diskUsage; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="flex justify-between">
                                    <span><?php echo e($item['Type'] ?? 'Unknown'); ?>:</span>
                                    <span class="font-semibold"><?php echo e($item['Size'] ?? 'N/A'); ?> <span class="text-orange-600">(<?php echo e($item['Reclaimable'] ?? '0'); ?> reclaimable)</span></span>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    
    <div wire:loading class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-700">Loading...</span>
        </div>
    </div>
</div>

<?php /**PATH /home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/livewire/docker/docker-dashboard.blade.php ENDPATH**/ ?>