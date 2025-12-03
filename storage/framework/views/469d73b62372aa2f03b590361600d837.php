<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-emerald-500 to-teal-500 p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        System Health Dashboard
                    </h1>
                    <p class="text-emerald-100 mt-1">Monitor the health status of all your projects and servers</p>
                </div>
                <div class="flex items-center gap-3">
                    <?php if($lastCheckedAt): ?>
                        <span class="text-sm text-emerald-100">
                            Last checked: <?php echo e(\Carbon\Carbon::parse($lastCheckedAt)->diffForHumans()); ?>

                        </span>
                    <?php endif; ?>
                    <button wire:click="refreshHealth"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg font-medium transition flex items-center gap-2">
                        <svg wire:loading.remove wire:target="refreshHealth" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <svg wire:loading wire:target="refreshHealth" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="p-6 grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="text-center p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                <div class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo e($stats['total']); ?></div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Projects</div>
            </div>
            <div class="text-center p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20">
                <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400"><?php echo e($stats['healthy']); ?></div>
                <div class="text-sm text-emerald-600 dark:text-emerald-400">Healthy</div>
            </div>
            <div class="text-center p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20">
                <div class="text-3xl font-bold text-amber-600 dark:text-amber-400"><?php echo e($stats['warning']); ?></div>
                <div class="text-sm text-amber-600 dark:text-amber-400">Warning</div>
            </div>
            <div class="text-center p-4 rounded-xl bg-red-50 dark:bg-red-900/20">
                <div class="text-3xl font-bold text-red-600 dark:text-red-400"><?php echo e($stats['critical']); ?></div>
                <div class="text-sm text-red-600 dark:text-red-400">Critical</div>
            </div>
            <div class="text-center p-4 rounded-xl bg-blue-50 dark:bg-blue-900/20">
                <div class="text-3xl font-bold text-blue-600 dark:text-blue-400"><?php echo e($stats['avg_score']); ?>%</div>
                <div class="text-sm text-blue-600 dark:text-blue-400">Avg Score</div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex items-center gap-2 bg-white dark:bg-gray-800 p-2 rounded-xl shadow">
        <?php $__currentLoopData = ['all' => 'All', 'healthy' => 'Healthy', 'warning' => 'Warning', 'critical' => 'Critical']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <button wire:click="$set('filterStatus', '<?php echo e($key); ?>')"
                    class="px-4 py-2 rounded-lg font-medium text-sm transition
                        <?php echo e($filterStatus === $key
                            ? 'bg-emerald-500 text-white'
                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'); ?>">
                <?php echo e($label); ?>

                <?php if($key === 'all'): ?>
                    <span class="ml-1 px-1.5 py-0.5 text-xs rounded-full bg-white/20"><?php echo e($stats['total']); ?></span>
                <?php elseif($key === 'healthy'): ?>
                    <span class="ml-1 px-1.5 py-0.5 text-xs rounded-full <?php echo e($filterStatus === $key ? 'bg-white/20' : 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400'); ?>"><?php echo e($stats['healthy']); ?></span>
                <?php elseif($key === 'warning'): ?>
                    <span class="ml-1 px-1.5 py-0.5 text-xs rounded-full <?php echo e($filterStatus === $key ? 'bg-white/20' : 'bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400'); ?>"><?php echo e($stats['warning']); ?></span>
                <?php elseif($key === 'critical'): ?>
                    <span class="ml-1 px-1.5 py-0.5 text-xs rounded-full <?php echo e($filterStatus === $key ? 'bg-white/20' : 'bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400'); ?>"><?php echo e($stats['critical']); ?></span>
                <?php endif; ?>
            </button>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <!-- Loading State -->
    <?php if($isLoading): ?>
        <div class="flex items-center justify-center py-12">
            <div class="text-center">
                <svg class="w-12 h-12 animate-spin text-emerald-500 mx-auto" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-4 text-gray-500 dark:text-gray-400">Loading health data...</p>
            </div>
        </div>
    <?php else: ?>
        <!-- Projects Health Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php $__empty_1 = true; $__currentLoopData = $filteredProjects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
                    <!-- Health Score Header -->
                    <div class="p-4 <?php echo e($project['health_score'] >= 80 ? 'bg-gradient-to-r from-emerald-500 to-green-500' : ($project['health_score'] >= 50 ? 'bg-gradient-to-r from-amber-500 to-orange-500' : 'bg-gradient-to-r from-red-500 to-rose-500')); ?>">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center">
                                    <span class="text-xl font-bold text-white"><?php echo e($project['health_score']); ?></span>
                                </div>
                                <div>
                                    <h3 class="font-bold text-white"><?php echo e($project['name']); ?></h3>
                                    <p class="text-sm text-white/80"><?php echo e($project['server_name']); ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <?php if($project['health_score'] >= 80): ?>
                                    <span class="inline-flex items-center gap-1 text-white text-sm font-medium">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Healthy
                                    </span>
                                <?php elseif($project['health_score'] >= 50): ?>
                                    <span class="inline-flex items-center gap-1 text-white text-sm font-medium">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        Warning
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 text-white text-sm font-medium">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        Critical
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Health Details -->
                    <div class="p-4 space-y-4">
                        <!-- Metrics -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status</div>
                                <div class="mt-1 font-semibold <?php echo e($project['status'] === 'running' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-600 dark:text-gray-400'); ?>">
                                    <?php echo e(ucfirst($project['status'])); ?>

                                </div>
                            </div>
                            <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Uptime</div>
                                <div class="mt-1 font-semibold <?php echo e($project['uptime_status'] === 'healthy' ? 'text-emerald-600 dark:text-emerald-400' : ($project['uptime_status'] === 'unhealthy' ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400')); ?>">
                                    <?php echo e(ucfirst($project['uptime_status'])); ?>

                                </div>
                            </div>
                            <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Response</div>
                                <div class="mt-1 font-semibold text-gray-900 dark:text-white">
                                    <?php echo e($project['response_time'] ? $project['response_time'] . 'ms' : 'N/A'); ?>

                                </div>
                            </div>
                            <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/50">
                                <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Last Deploy</div>
                                <div class="mt-1 font-semibold text-gray-900 dark:text-white text-sm">
                                    <?php echo e($project['last_deployment'] ?? 'Never'); ?>

                                </div>
                            </div>
                        </div>

                        <!-- Issues -->
                        <?php if(!empty($project['issues'])): ?>
                            <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                                <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Issues</h4>
                                <ul class="space-y-1">
                                    <?php $__currentLoopData = $project['issues']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $issue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li class="flex items-center gap-2 text-sm text-red-600 dark:text-red-400">
                                            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            <?php echo e($issue); ?>

                                        </li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Actions -->
                        <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                            <a href="<?php echo e(route('projects.show', $project['slug'])); ?>"
                               class="block w-full text-center px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium text-sm transition">
                                View Project
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="col-span-full text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <p class="mt-4 text-gray-500 dark:text-gray-400">No projects found matching the filter</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Servers Health -->
        <?php if(count($serversHealth) > 0): ?>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                        </svg>
                        Server Health
                    </h2>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php $__currentLoopData = $serversHealth; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $server): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center
                                        <?php echo e($server['health_score'] >= 80 ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400' : ($server['health_score'] >= 50 ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400' : 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400')); ?>">
                                        <span class="font-bold"><?php echo e($server['health_score']); ?></span>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white"><?php echo e($server['name']); ?></h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo e($server['ip_address']); ?> - <?php echo e($server['projects_count']); ?> projects</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-6 text-sm">
                                    <?php if($server['cpu_usage'] !== null): ?>
                                        <div class="text-center">
                                            <div class="font-semibold <?php echo e($server['cpu_usage'] > 80 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'); ?>"><?php echo e($server['cpu_usage']); ?>%</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">CPU</div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if($server['ram_usage'] !== null): ?>
                                        <div class="text-center">
                                            <div class="font-semibold <?php echo e($server['ram_usage'] > 80 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'); ?>"><?php echo e($server['ram_usage']); ?>%</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">RAM</div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if($server['disk_usage'] !== null): ?>
                                        <div class="text-center">
                                            <div class="font-semibold <?php echo e($server['disk_usage'] > 80 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'); ?>"><?php echo e($server['disk_usage']); ?>%</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Disk</div>
                                        </div>
                                    <?php endif; ?>
                                    <a href="<?php echo e(route('servers.show', $server['id'])); ?>"
                                       class="px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-sm font-medium transition">
                                        View
                                    </a>
                                </div>
                            </div>
                            <?php if(!empty($server['issues'])): ?>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <?php $__currentLoopData = $server['issues']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $issue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full text-xs">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            <?php echo e($issue); ?>

                                        </span>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php /**PATH /home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/livewire/dashboard/health-dashboard.blade.php ENDPATH**/ ?>