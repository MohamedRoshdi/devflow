<div class="space-y-6">
    
    <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-cyan-500 via-blue-500 to-indigo-500 dark:from-cyan-600 dark:via-blue-600 dark:to-indigo-600 p-8 shadow-xl overflow-hidden">
        <div class="absolute inset-0 bg-black/10 dark:bg-black/20 backdrop-blur-sm"></div>
        <div class="relative z-10 flex justify-between items-center">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <div class="p-2 bg-white/20 dark:bg-white/10 backdrop-blur-md rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <h1 class="text-4xl font-bold text-white">Docker Management</h1>
                </div>
                <p class="text-white/90 text-lg">Server: <?php echo e($server->name); ?></p>
            </div>
            <button wire:click="loadDockerInfo"
                    class="bg-white/20 hover:bg-white/30 backdrop-blur-md text-white font-semibold px-6 py-3 rounded-lg transition-all duration-300 hover:scale-105 shadow-lg flex items-center space-x-2"
                    wire:loading.attr="disabled"
                    wire:target="loadDockerInfo">
                <span wire:loading.remove wire:target="loadDockerInfo" class="flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Refresh</span>
                </span>
                <span wire:loading wire:target="loadDockerInfo" class="flex items-center space-x-2">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Refreshing...</span>
                </span>
            </button>
        </div>
    </div>

    
    <?php if(session()->has('message')): ?>
        <div class="mb-6 bg-gradient-to-r from-green-500/20 to-emerald-500/20 dark:from-green-500/30 dark:to-emerald-500/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-400 px-4 py-3 rounded-lg backdrop-blur-sm">
            <?php echo e(session('message')); ?>

        </div>
    <?php endif; ?>

    
    <?php if($error): ?>
        <div class="mb-6 bg-gradient-to-r from-red-500/20 to-red-600/20 dark:from-red-500/30 dark:to-red-600/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-400 px-4 py-3 rounded-lg backdrop-blur-sm">
            <?php echo e($error); ?>

        </div>
    <?php endif; ?>

    
    <div class="border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8">
            <button wire:click="switchTab('overview')" 
                    class="<?php if($activeTab === 'overview'): ?> border-blue-500 text-blue-600 <?php else: ?> border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 hover:border-gray-300 <?php endif; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                📊 Overview
            </button>
            <button wire:click="switchTab('images')" 
                    class="<?php if($activeTab === 'images'): ?> border-blue-500 text-blue-600 <?php else: ?> border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 hover:border-gray-300 <?php endif; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                🖼️ Images (<?php echo e(count($images)); ?>)
            </button>
            <button wire:click="switchTab('volumes')" 
                    class="<?php if($activeTab === 'volumes'): ?> border-blue-500 text-blue-600 <?php else: ?> border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 hover:border-gray-300 <?php endif; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                💾 Volumes (<?php echo e(count($volumes)); ?>)
            </button>
            <button wire:click="switchTab('networks')" 
                    class="<?php if($activeTab === 'networks'): ?> border-blue-500 text-blue-600 <?php else: ?> border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 hover:border-gray-300 <?php endif; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                🌐 Networks (<?php echo e(count($networks)); ?>)
            </button>
            <button wire:click="switchTab('cleanup')" 
                    class="<?php if($activeTab === 'cleanup'): ?> border-blue-500 text-blue-600 <?php else: ?> border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 hover:border-gray-300 <?php endif; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                🧹 Cleanup
            </button>
        </nav>
    </div>

    
    <div wire:loading wire:target="loadDockerInfo,switchTab" class="fixed inset-0 z-40 flex items-center justify-center">
        <div class="absolute inset-0 backdrop-blur-md bg-slate-900/70"></div>
        <div class="relative">
            <div class="absolute -inset-1 rounded-3xl bg-gradient-to-r from-blue-500/60 via-purple-500/60 to-indigo-500/60 blur-xl opacity-75 animate-pulse"></div>
            <div class="relative bg-slate-900/95 border border-white/10 rounded-3xl px-10 py-8 shadow-2xl text-center space-y-5">
                <div class="flex items-center justify-center space-x-3 text-blue-300">
                    <svg class="w-6 h-6 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm font-semibold tracking-wide uppercase">Docker telemetry incoming…</span>
                </div>
                <div class="text-white text-lg font-semibold">Syncing server metrics</div>
                <p class="text-sm text-slate-400 max-w-sm">
                    Fetching container stats, images, volumes, networks and disk usage directly from <span class="font-semibold"><?php echo e($server->name); ?></span>.
                </p>
                <div class="space-y-2 text-left text-xs text-slate-400 font-medium">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Container information
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse delay-150"></span>
                        Resource metrics
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-purple-400 animate-pulse delay-300"></span>
                        Disk usage snapshots
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <?php if($activeTab === 'overview'): ?>
        <div class="space-y-6">
            
            <?php if($dockerInfo): ?>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Docker System Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 p-4 rounded-lg transition-all hover:-translate-y-1 shadow-md">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Docker Version</div>
                            <div class="text-xl font-bold text-gray-900 dark:text-white"><?php echo e($dockerInfo['ServerVersion'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-900/40 p-4 rounded-lg transition-all hover:-translate-y-1 shadow-md">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Containers</div>
                            <div class="text-xl font-bold text-blue-600 dark:text-blue-400"><?php echo e($dockerInfo['Containers'] ?? 0); ?></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Running: <?php echo e($dockerInfo['ContainersRunning'] ?? 0); ?> |
                                Stopped: <?php echo e($dockerInfo['ContainersStopped'] ?? 0); ?>

                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-900/40 p-4 rounded-lg transition-all hover:-translate-y-1 shadow-md">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Images</div>
                            <div class="text-xl font-bold text-green-600 dark:text-green-400"><?php echo e($dockerInfo['Images'] ?? 0); ?></div>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-900/40 p-4 rounded-lg transition-all hover:-translate-y-1 shadow-md">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Storage Driver</div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo e($dockerInfo['Driver'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-900/40 p-4 rounded-lg transition-all hover:-translate-y-1 shadow-md">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Operating System</div>
                            <div class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo e($dockerInfo['OperatingSystem'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/20 dark:to-indigo-900/40 p-4 rounded-lg transition-all hover:-translate-y-1 shadow-md">
                            <div class="text-sm text-gray-600 dark:text-gray-400">CPU Cores</div>
                            <div class="text-xl font-bold text-indigo-600 dark:text-indigo-400"><?php echo e($dockerInfo['NCPU'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            
            <?php if($diskUsage): ?>
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Disk Usage</h3>
                    <div class="space-y-4">
                        <?php $__currentLoopData = $diskUsage; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="flex items-center justify-between p-4 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 rounded-lg hover:-translate-y-1 transition-all shadow-md">
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white"><?php echo e($item['Type'] ?? 'Unknown'); ?></div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        Total: <?php echo e($item['TotalCount'] ?? 0); ?> |
                                        Active: <?php echo e($item['Active'] ?? 0); ?>

                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-gray-900 dark:text-white"><?php echo e($item['Size'] ?? 'N/A'); ?></div>
                                    <div class="text-sm text-orange-600 dark:text-orange-400">
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
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Docker Images</h3>
                <button wire:click="pruneImages"
                        class="px-4 py-2 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white rounded-lg transition-all hover:scale-105 shadow-lg"
                        wire:loading.attr="disabled">
                    🧹 Prune Unused Images
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Repository</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tag</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Image ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Size</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php $__empty_1 = true; $__currentLoopData = $images; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white dark:text-white">
                                    <?php echo e($image['Repository'] ?? 'N/A'); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    <?php echo e($image['Tag'] ?? 'N/A'); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono">
                                    <?php echo e(Str::limit($image['ID'] ?? 'N/A', 12)); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    <?php echo e($image['Size'] ?? 'N/A'); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    <?php echo e($image['CreatedAt'] ?? 'N/A'); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    <button wire:click="deleteImage('<?php echo e($image['ID']); ?>')"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50"
                                            wire:target="deleteImage('<?php echo e($image['ID']); ?>')"
                                            class="text-red-600 hover:text-red-900"
                                            onclick="return confirm('Are you sure you want to delete this image?')">
                                        <span wire:loading.remove wire:target="deleteImage('<?php echo e($image['ID']); ?>')">🗑️ Delete</span>
                                        <span wire:loading wire:target="deleteImage('<?php echo e($image['ID']); ?>')">⏳ Deleting...</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400 dark:text-gray-400">No images found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    
    <?php if($activeTab === 'volumes'): ?>
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Docker Volumes</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Driver</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Mountpoint</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php $__empty_1 = true; $__currentLoopData = $volumes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $volume): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white dark:text-white">
                                    <?php echo e($volume['Name'] ?? 'N/A'); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    <?php echo e($volume['Driver'] ?? 'N/A'); ?>

                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 font-mono text-xs">
                                    <?php echo e(Str::limit($volume['Mountpoint'] ?? 'N/A', 50)); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    <button wire:click="deleteVolume('<?php echo e($volume['Name']); ?>')"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50"
                                            wire:target="deleteVolume('<?php echo e($volume['Name']); ?>')"
                                            class="text-red-600 hover:text-red-900"
                                            onclick="return confirm('Are you sure? This will permanently delete all data in this volume!')">
                                        <span wire:loading.remove wire:target="deleteVolume('<?php echo e($volume['Name']); ?>')">🗑️ Delete</span>
                                        <span wire:loading wire:target="deleteVolume('<?php echo e($volume['Name']); ?>')">⏳ Deleting...</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400 dark:text-gray-400">No volumes found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    
    <?php if($activeTab === 'networks'): ?>
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Docker Networks</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Driver</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Scope</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php $__empty_1 = true; $__currentLoopData = $networks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $network): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white dark:text-white">
                                    <?php echo e($network['Name'] ?? 'N/A'); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono">
                                    <?php echo e(Str::limit($network['ID'] ?? 'N/A', 12)); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    <?php echo e($network['Driver'] ?? 'N/A'); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    <?php echo e($network['Scope'] ?? 'N/A'); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    <?php if(!in_array($network['Name'], ['bridge', 'host', 'none'])): ?>
                                        <button wire:click="deleteNetwork('<?php echo e($network['Name']); ?>')"
                                                wire:loading.attr="disabled"
                                                wire:loading.class="opacity-50"
                                                wire:target="deleteNetwork('<?php echo e($network['Name']); ?>')"
                                                class="text-red-600 hover:text-red-900"
                                                onclick="return confirm('Are you sure you want to delete this network?')">
                                            <span wire:loading.remove wire:target="deleteNetwork('<?php echo e($network['Name']); ?>')">🗑️ Delete</span>
                                            <span wire:loading wire:target="deleteNetwork('<?php echo e($network['Name']); ?>')">⏳ Deleting...</span>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-gray-400">System Network</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400 dark:text-gray-400">No networks found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    
    <?php if($activeTab === 'cleanup'): ?>
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">System Cleanup</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">Free up disk space by removing unused Docker resources.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-900/40 rounded-lg p-6 hover:-translate-y-1 transition-all shadow-md">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">🖼️ Prune Images</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Remove dangling and unused images</p>
                        <button wire:click="pruneImages"
                                class="w-full px-4 py-2 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white rounded-lg transition-all hover:scale-105 shadow-lg"
                                wire:loading.attr="disabled">
                            Prune Images
                        </button>
                    </div>

                    <div class="border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-900/40 rounded-lg p-6 hover:-translate-y-1 transition-all shadow-md">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">🧹 System Prune</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Remove all unused containers, networks, and images</p>
                        <button wire:click="systemPrune"
                                class="w-full px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-lg transition-all hover:scale-105 shadow-lg"
                                wire:loading.attr="disabled"
                                onclick="return confirm('This will remove all unused Docker resources. Continue?')">
                            System Prune
                        </button>
                    </div>
                </div>

                <?php if($diskUsage): ?>
                    <div class="mt-6 p-4 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-900/40 rounded-lg backdrop-blur-sm border border-blue-200 dark:border-blue-700">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">💡 Disk Space Summary</h4>
                        <div class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                            <?php $__currentLoopData = $diskUsage; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="flex justify-between">
                                    <span><?php echo e($item['Type'] ?? 'Unknown'); ?>:</span>
                                    <span class="font-semibold"><?php echo e($item['Size'] ?? 'N/A'); ?> <span class="text-orange-600 dark:text-orange-400">(<?php echo e($item['Reclaimable'] ?? '0'); ?> reclaimable)</span></span>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php /**PATH /home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/livewire/docker/docker-dashboard.blade.php ENDPATH**/ ?>