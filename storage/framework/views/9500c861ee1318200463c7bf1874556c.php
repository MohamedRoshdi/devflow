<div class="space-y-6">
    
    <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-purple-500 via-indigo-500 to-purple-600 p-8 text-white shadow-xl dark:from-purple-600 dark:via-indigo-600 dark:to-purple-700">
        <div class="absolute inset-0 bg-grid-white/10 [mask-image:linear-gradient(0deg,white,rgba(255,255,255,0.5))]"></div>
        <div class="relative">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold">File Backups</h2>
                    <p class="mt-2 text-purple-100">Full and incremental file system backups</p>
                </div>
                <div class="flex gap-3">
                    <button wire:click="openExcludePatternsModal"
                            class="rounded-lg bg-white/20 px-6 py-3 font-semibold text-white backdrop-blur-sm transition hover:bg-white/30">
                        <i class="fas fa-filter mr-2"></i>
                        Exclude Patterns
                    </button>
                    <button wire:click="openCreateModal"
                            class="rounded-lg bg-white px-6 py-3 font-semibold text-purple-600 transition hover:bg-purple-50">
                        <i class="fas fa-plus mr-2"></i>
                        Create Backup
                    </button>
                </div>
            </div>
        </div>
    </div>

    
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                <input type="text" wire:model.live="searchTerm"
                       placeholder="Search by filename..."
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                <select wire:model.live="filterType"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="all">All Types</option>
                    <option value="full">Full Backups</option>
                    <option value="incremental">Incremental Backups</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                <select wire:model.live="filterStatus"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="all">All Status</option>
                    <option value="completed">Completed</option>
                    <option value="running">Running</option>
                    <option value="pending">Pending</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
        </div>
    </div>

    
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                File Backups (<?php echo e(count($this->backups)); ?>)
            </h3>
        </div>

        <!--[if BLOCK]><![endif]--><?php if($this->backups->isEmpty()): ?>
            <div class="p-12 text-center">
                <i class="fas fa-archive mb-4 text-6xl text-gray-300 dark:text-gray-600"></i>
                <h3 class="mb-2 text-xl font-semibold text-gray-900 dark:text-white">No backups yet</h3>
                <p class="mb-4 text-gray-600 dark:text-gray-400">Create your first file backup to get started</p>
                <button wire:click="openCreateModal"
                        class="rounded-lg bg-purple-600 px-6 py-2 text-white transition hover:bg-purple-700">
                    <i class="fas fa-plus mr-2"></i>
                    Create Backup
                </button>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Filename</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Size</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Files</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Duration</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Storage</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                        <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $this->backups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $backup): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center">
                                        <!--[if BLOCK]><![endif]--><?php if($backup['parent_backup_id']): ?>
                                            <span class="mr-2 text-gray-400" style="margin-left: <?php echo e($backup['incremental_depth'] * 20); ?>px">
                                                <i class="fas fa-level-up-alt fa-rotate-90"></i>
                                            </span>
                                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo e($backup['filename']); ?></div>
                                            <!--[if BLOCK]><![endif]--><?php if($backup['checksum'] !== '-'): ?>
                                                <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($backup['checksum']); ?></div>
                                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                        <?php echo e($backup['type_color'] === 'purple' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : ''); ?>

                                        <?php echo e($backup['type_color'] === 'blue' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : ''); ?>">
                                        <?php echo e(ucfirst($backup['type'])); ?>

                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    <?php echo e($backup['size']); ?>

                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    <button wire:click="viewManifest(<?php echo e($backup['id']); ?>)"
                                            class="text-purple-600 hover:text-purple-800 dark:text-purple-400">
                                        <?php echo e($backup['files_count']); ?> files
                                    </button>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    <?php echo e($backup['duration']); ?>

                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    <span class="inline-flex items-center">
                                        <i class="fas fa-hdd mr-1 text-gray-400"></i>
                                        <?php echo e(strtoupper($backup['storage_disk'])); ?>

                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    <div><?php echo e($backup['created_at']); ?></div>
                                    <div class="text-xs"><?php echo e($backup['created_at_human']); ?></div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                        <?php echo e($backup['status_color'] === 'green' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ''); ?>

                                        <?php echo e($backup['status_color'] === 'blue' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : ''); ?>

                                        <?php echo e($backup['status_color'] === 'yellow' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : ''); ?>

                                        <?php echo e($backup['status_color'] === 'red' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : ''); ?>">
                                        <?php echo e(ucfirst($backup['status'])); ?>

                                    </span>
                                    <!--[if BLOCK]><![endif]--><?php if($backup['error_message']): ?>
                                        <div class="mt-1 text-xs text-red-600 dark:text-red-400"><?php echo e(Str::limit($backup['error_message'], 50)); ?></div>
                                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <!--[if BLOCK]><![endif]--><?php if($backup['status'] === 'completed'): ?>
                                            <button wire:click="openRestoreModal(<?php echo e($backup['id']); ?>)"
                                                    title="Restore"
                                                    class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                            <button wire:click="downloadBackup(<?php echo e($backup['id']); ?>)"
                                                    title="Download"
                                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                <i class="fas fa-download"></i>
                                            </button>
                                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                        <button wire:click="deleteBackup(<?php echo e($backup['id']); ?>)"
                                                wire:confirm="Are you sure you want to delete this backup?"
                                                title="Delete"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                    </tbody>
                </table>
            </div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </div>

    
    <!--[if BLOCK]><![endif]--><?php if($showCreateModal): ?>
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: <?php if ((object) ('showCreateModal') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('showCreateModal'->value()); ?>')<?php echo e('showCreateModal'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('showCreateModal'); ?>')<?php endif; ?> }"
             x-show="show"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="showCreateModal = false"></div>

                <div class="relative w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4"
                     x-transition:enter-end="opacity-100 translate-y-0">
                    <h3 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">Create File Backup</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Backup Type</label>
                            <select wire:model="backupType"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="full">Full Backup</option>
                                <option value="incremental">Incremental Backup</option>
                            </select>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                <!--[if BLOCK]><![endif]--><?php if($backupType === 'full'): ?>
                                    Complete backup of all project files
                                <?php else: ?>
                                    Only backs up files changed since the last full backup
                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                            </p>
                        </div>

                        <!--[if BLOCK]><![endif]--><?php if($backupType === 'incremental'): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Base Backup</label>
                                <select wire:model="baseBackupId"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="">Select base backup...</option>
                                    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $this->fullBackups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fullBackup): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($fullBackup['id']); ?>"><?php echo e($fullBackup['label']); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                                </select>
                                <!--[if BLOCK]><![endif]--><?php $__errorArgs = ['baseBackupId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-sm text-red-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
                            </div>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Storage Disk</label>
                            <select wire:model="storageDisk"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $this->storageDisks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $disk): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($disk['value']); ?>"><?php echo e($disk['label']); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                            </select>
                        </div>

                        <div class="rounded-md bg-blue-50 p-4 dark:bg-blue-900/20">
                            <div class="flex">
                                <i class="fas fa-info-circle text-blue-400"></i>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Backup Information</h3>
                                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                                        <p>The following patterns will be excluded:</p>
                                        <ul class="mt-1 list-inside list-disc">
                                            <li>Logs and cache files</li>
                                            <li>node_modules and vendor directories</li>
                                            <li>.git directory</li>
                                            <li>Custom exclude patterns you've configured</li>
                                        </ul>
                                        <p class="mt-2">Click "Exclude Patterns" to customize.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button wire:click="showCreateModal = false"
                                class="rounded-lg border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                            Cancel
                        </button>
                        <button wire:click="createBackup"
                                class="rounded-lg bg-purple-600 px-4 py-2 text-white hover:bg-purple-700">
                            <i class="fas fa-archive mr-2"></i>
                            Create Backup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

    
    <!--[if BLOCK]><![endif]--><?php if($showRestoreModal): ?>
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: <?php if ((object) ('showRestoreModal') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('showRestoreModal'->value()); ?>')<?php echo e('showRestoreModal'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('showRestoreModal'); ?>')<?php endif; ?> }"
             x-show="show"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="showRestoreModal = false"></div>

                <div class="relative w-full max-w-lg rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                    <h3 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">Restore File Backup</h3>

                    <div class="space-y-4">
                        <div class="rounded-md bg-yellow-50 p-4 dark:bg-yellow-900/20">
                            <div class="flex">
                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Warning</h3>
                                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                        <p>This will restore files from the backup. Make sure to backup current files before proceeding if needed.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" wire:model="overwriteOnRestore" id="overwrite"
                                   class="h-4 w-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <label for="overwrite" class="ml-2 block text-sm text-gray-900 dark:text-white">
                                Overwrite existing files
                            </label>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            If unchecked, existing files will be kept and only missing files will be restored.
                        </p>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button wire:click="showRestoreModal = false"
                                class="rounded-lg border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                            Cancel
                        </button>
                        <button wire:click="restoreBackup"
                                class="rounded-lg bg-green-600 px-4 py-2 text-white hover:bg-green-700">
                            <i class="fas fa-undo mr-2"></i>
                            Restore Backup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

    
    <!--[if BLOCK]><![endif]--><?php if($showManifestModal): ?>
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: <?php if ((object) ('showManifestModal') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('showManifestModal'->value()); ?>')<?php echo e('showManifestModal'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('showManifestModal'); ?>')<?php endif; ?> }"
             x-show="show"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="showManifestModal = false"></div>

                <div class="relative w-full max-w-3xl rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                    <h3 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">
                        Backup Manifest (<?php echo e(count($manifest)); ?> files)
                    </h3>

                    <div class="max-h-96 overflow-y-auto rounded-md bg-gray-50 p-4 dark:bg-gray-900">
                        <!--[if BLOCK]><![endif]--><?php if(empty($manifest)): ?>
                            <p class="text-gray-500 dark:text-gray-400">No manifest available</p>
                        <?php else: ?>
                            <ul class="space-y-1 font-mono text-sm text-gray-700 dark:text-gray-300">
                                <!--[if BLOCK]><![endif]--><?php $__currentLoopData = array_slice($manifest, 0, 100); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $file): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li class="flex items-center">
                                        <i class="fas fa-file mr-2 text-gray-400"></i>
                                        <?php echo e($file); ?>

                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <!--[if BLOCK]><![endif]--><?php if(count($manifest) > 100): ?>
                                    <li class="mt-2 text-gray-500 dark:text-gray-400">
                                        ... and <?php echo e(count($manifest) - 100); ?> more files
                                    </li>
                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                            </ul>
                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button wire:click="showManifestModal = false"
                                class="rounded-lg bg-gray-600 px-4 py-2 text-white hover:bg-gray-700">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

    
    <!--[if BLOCK]><![endif]--><?php if($showExcludePatternsModal): ?>
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: <?php if ((object) ('showExcludePatternsModal') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('showExcludePatternsModal'->value()); ?>')<?php echo e('showExcludePatternsModal'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('showExcludePatternsModal'); ?>')<?php endif; ?> }"
             x-show="show"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="showExcludePatternsModal = false"></div>

                <div class="relative w-full max-w-2xl rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                    <h3 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">Exclude Patterns</h3>

                    <div class="space-y-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Configure file and directory patterns to exclude from backups. Use glob patterns like *.log, node_modules/*, etc.
                        </p>

                        <div class="flex gap-2">
                            <input type="text" wire:model="newExcludePattern"
                                   wire:keydown.enter="addExcludePattern"
                                   placeholder="e.g., *.log or temp/*"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <button wire:click="addExcludePattern"
                                    class="rounded-lg bg-purple-600 px-4 py-2 text-white hover:bg-purple-700">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>

                        <div class="max-h-64 space-y-2 overflow-y-auto">
                            <!--[if BLOCK]><![endif]--><?php $__empty_1 = true; $__currentLoopData = $excludePatterns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $pattern): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <div class="flex items-center justify-between rounded-md bg-gray-50 p-3 dark:bg-gray-900">
                                    <span class="font-mono text-sm text-gray-700 dark:text-gray-300"><?php echo e($pattern); ?></span>
                                    <button wire:click="removeExcludePattern(<?php echo e($index); ?>)"
                                            class="text-red-600 hover:text-red-800 dark:text-red-400">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <p class="text-sm text-gray-500 dark:text-gray-400">No custom exclude patterns configured</p>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        </div>
                    </div>

                    <div class="mt-6 flex justify-between">
                        <button wire:click="resetExcludePatterns"
                                wire:confirm="Reset to default exclude patterns?"
                                class="rounded-lg border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                            <i class="fas fa-undo mr-2"></i>
                            Reset to Defaults
                        </button>
                        <button wire:click="showExcludePatternsModal = false"
                                class="rounded-lg bg-purple-600 px-4 py-2 text-white hover:bg-purple-700">
                            Done
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
</div>
<?php /**PATH /home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/livewire/projects/file-backup-manager.blade.php ENDPATH**/ ?>