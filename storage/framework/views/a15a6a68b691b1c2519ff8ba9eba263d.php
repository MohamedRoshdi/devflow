<div class="space-y-6">
    <!-- Quick Commands -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Quick Commands</h3>
        <div class="space-y-3">
            <?php $__currentLoopData = $quickCommands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category => $commands): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div>
                    <h4 class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2"><?php echo e($category); ?></h4>
                    <div class="flex flex-wrap gap-2">
                        <?php $__currentLoopData = $commands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cmd => $description): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <button
                                wire:click="$set('command', '<?php echo e($cmd); ?>')"
                                class="px-3 py-1 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-xs text-gray-700 dark:text-gray-300 rounded transition"
                                title="<?php echo e($description); ?>">
                                <?php echo e($cmd); ?>

                            </button>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>

    <!-- Terminal Header -->
    <div class="bg-gray-800 rounded-t-lg px-4 py-3 flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <div class="flex space-x-2">
                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                <div class="w-3 h-3 rounded-full bg-green-500"></div>
            </div>
            <span class="ml-4 text-sm text-gray-400">
                <?php echo e($server->username); ?>{{ $server->ip_address }}
            </span>
        </div>
        <div class="flex items-center space-x-2">
            <span class="text-xs text-gray-500">SSH Terminal</span>
            <?php if(count($history) > 0): ?>
                <button wire:click="clearHistory"
                        class="text-xs text-red-400 hover:text-red-300 transition"
                        onclick="return confirm('Clear command history?')">
                    Clear History
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Command Input -->
    <div class="bg-gray-900 px-4 py-3">
        <form wire:submit.prevent="executeCommand" class="flex items-center space-x-2">
            <span class="text-green-400 font-mono text-sm">$</span>
            <input
                type="text"
                wire:model="command"
                placeholder="Enter command (e.g., ls -la)"
                class="flex-1 bg-transparent text-white font-mono text-sm focus:outline-none placeholder-gray-600"
                autofocus
                <?php if($isExecuting): ?> disabled <?php endif; ?>
            >
            <button
                type="submit"
                class="px-4 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded transition"
                <?php if($isExecuting): ?> disabled <?php endif; ?>
                wire:loading.attr="disabled"
                wire:target="executeCommand">
                <span wire:loading.remove wire:target="executeCommand">Execute</span>
                <span wire:loading wire:target="executeCommand">Running...</span>
            </button>
        </form>
    </div>

    <!-- Command History & Output -->
    <?php if(count($history) > 0): ?>
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Command History</h3>

            <?php $__currentLoopData = $history; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="bg-gray-900 rounded-lg overflow-hidden">
                    <!-- Command Header -->
                    <div class="px-4 py-2 bg-gray-800 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <span class="text-green-400 font-mono text-sm">$</span>
                            <span class="text-white font-mono text-sm"><?php echo e($item['command']); ?></span>
                            <?php if($item['success']): ?>
                                <span class="px-2 py-0.5 bg-green-900/50 text-green-400 text-xs rounded">
                                    ✓ Exit <?php echo e($item['exit_code']); ?>

                                </span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 bg-red-900/50 text-red-400 text-xs rounded">
                                    ✗ Exit <?php echo e($item['exit_code']); ?>

                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-gray-500"><?php echo e(\Carbon\Carbon::parse($item['timestamp'])->diffForHumans()); ?></span>
                            <button
                                wire:click="rerunCommand(<?php echo e($index); ?>)"
                                class="text-xs text-blue-400 hover:text-blue-300 transition"
                                title="Rerun this command">
                                ↻ Rerun
                            </button>
                        </div>
                    </div>

                    <!-- Command Output -->
                    <div class="px-4 py-3 bg-gray-900">
                        <pre class="text-sm text-gray-300 font-mono whitespace-pre-wrap overflow-x-auto max-h-96 overflow-y-auto"><?php echo e($item['output']); ?></pre>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php else: ?>
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <p class="text-sm">No commands executed yet. Type a command above or use quick commands.</p>
        </div>
    <?php endif; ?>

    <!-- Help Section -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-400 mb-2">💡 Tips</h4>
        <ul class="text-xs text-blue-800 dark:text-blue-300 space-y-1">
            <li>• Commands execute with user: <code class="px-1 py-0.5 bg-blue-100 dark:bg-blue-900/50 rounded"><?php echo e($server->username); ?></code></li>
            <li>• Maximum execution time: 5 minutes</li>
            <li>• Command history is saved (last 50 commands)</li>
            <li>• Use quick commands for common operations</li>
            <li>• Click "Rerun" to execute a previous command again</li>
        </ul>
    </div>
</div>
<?php /**PATH /home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/livewire/servers/s-s-h-terminal.blade.php ENDPATH**/ ?>