<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-purple-800 via-purple-900 to-purple-800 shadow-2xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex items-center gap-4">
                    <a href="<?php echo e(route('servers.security', $server)); ?>" class="p-2 bg-white/10 rounded-lg hover:bg-white/20 transition-colors">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <div class="flex items-center gap-3">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                            <div>
                                <h1 class="text-2xl font-bold text-white">Security Scans</h1>
                                <p class="text-white/80"><?php echo e($server->name); ?> - Scan History</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button wire:click="runScan" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-white text-purple-800 font-medium rounded-lg hover:bg-gray-100 transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" wire:loading.class="animate-spin" wire:target="runScan" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span wire:loading.remove wire:target="runScan">Run New Scan</span>
                        <span wire:loading wire:target="runScan">Scanning...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Message -->
    <?php if($flashMessage): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="rounded-lg p-4 <?php echo e($flashType === 'success' ? 'bg-green-900/50 text-green-200 border border-green-700' : 'bg-red-900/50 text-red-200 border border-red-700'); ?>">
                <?php echo e($flashMessage); ?>

            </div>
        </div>
    <?php endif; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Latest Scan Summary -->
        <?php if($this->latestScan): ?>
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6 mb-6">
                <h3 class="text-lg font-semibold text-white mb-4">Latest Scan Results</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-gray-900/50 rounded-lg">
                        <div class="text-3xl font-bold text-<?php echo e($this->latestScan->score_color); ?>-400"><?php echo e($this->latestScan->score); ?></div>
                        <div class="text-gray-400 text-sm">Security Score</div>
                    </div>
                    <div class="text-center p-4 bg-gray-900/50 rounded-lg">
                        <div class="text-3xl font-bold text-<?php echo e($this->latestScan->risk_level_color); ?>-400"><?php echo e(ucfirst($this->latestScan->risk_level)); ?></div>
                        <div class="text-gray-400 text-sm">Risk Level</div>
                    </div>
                    <div class="text-center p-4 bg-gray-900/50 rounded-lg">
                        <div class="text-3xl font-bold text-white"><?php echo e(count($this->latestScan->recommendations ?? [])); ?></div>
                        <div class="text-gray-400 text-sm">Recommendations</div>
                    </div>
                    <div class="text-center p-4 bg-gray-900/50 rounded-lg">
                        <div class="text-lg font-medium text-white"><?php echo e($this->latestScan->completed_at?->diffForHumans()); ?></div>
                        <div class="text-gray-400 text-sm">Last Scanned</div>
                    </div>
                </div>

                <?php if(!empty($this->latestScan->recommendations)): ?>
                    <div class="mt-6">
                        <h4 class="text-white font-medium mb-3">Recommendations</h4>
                        <div class="space-y-2">
                            <?php $__currentLoopData = $this->latestScan->recommendations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rec): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="flex items-start gap-3 p-3 bg-gray-900/50 rounded-lg">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                        <?php echo e($rec['priority'] === 'high' ? 'bg-red-500/20 text-red-400' : ''); ?>

                                        <?php echo e($rec['priority'] === 'medium' ? 'bg-yellow-500/20 text-yellow-400' : ''); ?>

                                        <?php echo e($rec['priority'] === 'low' ? 'bg-blue-500/20 text-blue-400' : ''); ?>">
                                        <?php echo e(strtoupper($rec['priority'])); ?>

                                    </span>
                                    <div>
                                        <h5 class="text-white font-medium"><?php echo e($rec['title']); ?></h5>
                                        <p class="text-gray-400 text-sm"><?php echo e($rec['description']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Scan History -->
        <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Scan History</h3>

            <?php if($this->scans->isEmpty()): ?>
                <div class="text-center py-8">
                    <svg class="w-12 h-12 text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="text-gray-400">No security scans have been run yet</p>
                    <button wire:click="runScan" class="mt-4 text-purple-400 hover:text-purple-300">
                        Run your first scan
                    </button>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-gray-400 text-sm border-b border-gray-700">
                                <th class="pb-3 font-medium">Date</th>
                                <th class="pb-3 font-medium">Score</th>
                                <th class="pb-3 font-medium">Risk Level</th>
                                <th class="pb-3 font-medium">Status</th>
                                <th class="pb-3 font-medium">Duration</th>
                                <th class="pb-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700/50">
                            <?php $__currentLoopData = $this->scans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $scan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr class="text-gray-300">
                                    <td class="py-3"><?php echo e($scan->created_at->format('M d, Y H:i')); ?></td>
                                    <td class="py-3">
                                        <?php if($scan->score): ?>
                                            <span class="font-bold text-<?php echo e($scan->score_color); ?>-400"><?php echo e($scan->score); ?></span>/100
                                        <?php else: ?>
                                            --
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3">
                                        <?php if($scan->risk_level): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-<?php echo e($scan->risk_level_color); ?>-500/20 text-<?php echo e($scan->risk_level_color); ?>-400">
                                                <?php echo e(ucfirst($scan->risk_level)); ?>

                                            </span>
                                        <?php else: ?>
                                            --
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            <?php echo e($scan->status === 'completed' ? 'bg-green-500/20 text-green-400' : ''); ?>

                                            <?php echo e($scan->status === 'running' ? 'bg-blue-500/20 text-blue-400' : ''); ?>

                                            <?php echo e($scan->status === 'failed' ? 'bg-red-500/20 text-red-400' : ''); ?>

                                            <?php echo e($scan->status === 'pending' ? 'bg-gray-500/20 text-gray-400' : ''); ?>">
                                            <?php echo e(ucfirst($scan->status)); ?>

                                        </span>
                                    </td>
                                    <td class="py-3 text-gray-400">
                                        <?php echo e($scan->duration ? $scan->duration . 's' : '--'); ?>

                                    </td>
                                    <td class="py-3 text-right">
                                        <?php if($scan->status === 'completed'): ?>
                                            <button wire:click="viewScanDetails(<?php echo e($scan->id); ?>)"
                                                class="text-purple-400 hover:text-purple-300 transition-colors">
                                                View Details
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    <?php echo e($this->scans->links()); ?>

                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Scan Details Modal -->
    <?php if($showDetails && $selectedScan): ?>
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/70 transition-opacity" wire:click="closeDetails"></div>

                <div class="relative w-full max-w-3xl bg-gray-800 rounded-2xl shadow-xl border border-gray-700 max-h-[90vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-semibold text-white">Scan Details</h3>
                            <button wire:click="closeDetails" class="text-gray-400 hover:text-white">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div class="text-center p-4 bg-gray-900/50 rounded-lg">
                                <div class="text-2xl font-bold text-<?php echo e($selectedScan->score_color); ?>-400"><?php echo e($selectedScan->score); ?></div>
                                <div class="text-gray-400 text-sm">Score</div>
                            </div>
                            <div class="text-center p-4 bg-gray-900/50 rounded-lg">
                                <div class="text-2xl font-bold text-<?php echo e($selectedScan->risk_level_color); ?>-400"><?php echo e(ucfirst($selectedScan->risk_level)); ?></div>
                                <div class="text-gray-400 text-sm">Risk Level</div>
                            </div>
                            <div class="text-center p-4 bg-gray-900/50 rounded-lg">
                                <div class="text-lg font-medium text-white"><?php echo e($selectedScan->completed_at?->format('M d, Y H:i')); ?></div>
                                <div class="text-gray-400 text-sm">Completed</div>
                            </div>
                        </div>

                        <?php if(!empty($selectedScan->findings)): ?>
                            <div class="mb-6">
                                <h4 class="text-white font-medium mb-3">Findings</h4>
                                <div class="bg-gray-900/50 rounded-lg p-4">
                                    <pre class="text-gray-300 text-sm overflow-x-auto"><?php echo e(json_encode($selectedScan->findings, JSON_PRETTY_PRINT)); ?></pre>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if(!empty($selectedScan->recommendations)): ?>
                            <div>
                                <h4 class="text-white font-medium mb-3">Recommendations</h4>
                                <div class="space-y-2">
                                    <?php $__currentLoopData = $selectedScan->recommendations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rec): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="flex items-start gap-3 p-3 bg-gray-900/50 rounded-lg">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                <?php echo e($rec['priority'] === 'high' ? 'bg-red-500/20 text-red-400' : ''); ?>

                                                <?php echo e($rec['priority'] === 'medium' ? 'bg-yellow-500/20 text-yellow-400' : ''); ?>

                                                <?php echo e($rec['priority'] === 'low' ? 'bg-blue-500/20 text-blue-400' : ''); ?>">
                                                <?php echo e(strtoupper($rec['priority'])); ?>

                                            </span>
                                            <div>
                                                <h5 class="text-white font-medium"><?php echo e($rec['title']); ?></h5>
                                                <p class="text-gray-400 text-sm"><?php echo e($rec['description']); ?></p>
                                                <?php if(!empty($rec['command'])): ?>
                                                    <code class="mt-2 block text-xs bg-gray-800 text-green-400 p-2 rounded"><?php echo e($rec['command']); ?></code>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH /home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/livewire/servers/security/security-scan-dashboard.blade.php ENDPATH**/ ?>