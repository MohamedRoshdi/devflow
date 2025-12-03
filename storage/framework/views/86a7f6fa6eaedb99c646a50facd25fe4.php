<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Hero Section with Gradient -->
        <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-cyan-500 via-teal-500 to-emerald-500 dark:from-cyan-600 dark:via-teal-600 dark:to-emerald-600 p-8 shadow-xl overflow-hidden">
            <div class="absolute inset-0 bg-black/10 dark:bg-black/20"></div>
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="p-2 bg-white/20 dark:bg-white/10 backdrop-blur-md rounded-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white">Tenant Manager</h1>
                    </div>
                    <p class="text-white/90 text-lg">Manage multi-tenant configurations and tenant databases</p>
                </div>
            </div>
        </div>

        <!-- Project Selector -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Multi-Tenant Project</label>
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-3">
                <?php $__empty_1 = true; $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <button wire:click="selectProject(<?php echo e($project->id); ?>)"
                            class="p-3 border-2 rounded-lg transition-colors text-left
                            <?php echo e($selectedProject == $project->id ? 'border-blue-500 bg-blue-50 dark:bg-blue-900' : 'border-gray-300 dark:border-gray-600 hover:border-gray-400'); ?>">
                        <div class="font-medium <?php echo e($selectedProject == $project->id ? 'text-blue-900 dark:text-blue-100' : 'text-gray-900 dark:text-white'); ?>">
                            <?php echo e($project->name); ?>

                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            <?php echo e($project->tenants_count ?? 0); ?> tenants
                        </div>
                    </button>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="col-span-full text-center py-8 text-gray-500 dark:text-gray-400">
                        No multi-tenant projects found. Create a project with type "multi_tenant" to manage tenants.
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php if($selectedProject): ?>
        <!-- Actions Bar -->
        <div class="flex justify-between items-center mb-4">
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    <?php echo e($tenants ? $tenants->total() : 0); ?> total tenants
                </span>
                <?php if($selectedTenants && count($selectedTenants) > 0): ?>
                    <span class="text-sm text-blue-600 dark:text-blue-400">
                        <?php echo e(count($selectedTenants)); ?> selected
                    </span>
                <?php endif; ?>
            </div>
            <div class="flex space-x-3">
                <button wire:click="showDeployToTenants" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    Deploy to Tenants
                </button>
                <button wire:click="createTenant" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Create Tenant
                </button>
            </div>
        </div>

        <!-- Tenants Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="w-8 px-6 py-3">
                            <input type="checkbox" wire:click="selectAllTenants" class="rounded border-gray-300 text-blue-600">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tenant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Domain</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Users</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Storage</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if($tenants): ?>
                        <?php $__empty_1 = true; $__currentLoopData = $tenants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tenant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <input type="checkbox"
                                           wire:click="toggleTenantSelection('<?php echo e($tenant->id); ?>')"
                                           <?php if(in_array($tenant->id, $selectedTenants)): ?> checked <?php endif; ?>
                                           class="rounded border-gray-300 text-blue-600">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo e($tenant->name); ?></div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">ID: <?php echo e($tenant->id); ?></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="https://<?php echo e($tenant->subdomain); ?>.<?php echo e($tenant->project->domains->first()->domain ?? 'example.com'); ?>"
                                       target="_blank"
                                       class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                        <?php echo e($tenant->subdomain); ?>

                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs leading-5 font-bold rounded-full shadow-md
                                        <?php if($tenant->plan === 'enterprise'): ?> bg-gradient-to-r from-purple-500 to-violet-500 text-white shadow-purple-500/30
                                        <?php elseif($tenant->plan === 'pro'): ?> bg-gradient-to-r from-blue-500 to-indigo-500 text-white shadow-blue-500/30
                                        <?php else: ?> bg-gradient-to-r from-gray-400 to-slate-500 text-white shadow-gray-500/30
                                        <?php endif; ?>">
                                        <?php if($tenant->plan === 'enterprise'): ?>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                                            </svg>
                                        <?php elseif($tenant->plan === 'pro'): ?>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                            </svg>
                                        <?php else: ?>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                        <?php endif; ?>
                                        <?php echo e(ucfirst($tenant->plan)); ?>

                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?php echo e($tenant->user_count ?? 0); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <?php echo e($this->formatBytes($tenant->storage_usage ?? 0)); ?>

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php switch($tenant->status):
                                        case ('active'): ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs leading-5 font-bold rounded-full shadow-md bg-gradient-to-r from-emerald-500 to-green-500 text-white shadow-emerald-500/30">
                                                <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                                Active
                                            </span>
                                            <?php break; ?>
                                        <?php case ('suspended'): ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs leading-5 font-bold rounded-full shadow-md bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-amber-500/30">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                Suspended
                                            </span>
                                            <?php break; ?>
                                        <?php case ('pending'): ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs leading-5 font-bold rounded-full shadow-md bg-gradient-to-r from-blue-500 to-indigo-500 text-white shadow-blue-500/30">
                                                <svg class="w-3.5 h-3.5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                Pending
                                            </span>
                                            <?php break; ?>
                                        <?php case ('inactive'): ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs leading-5 font-bold rounded-full shadow-md bg-gradient-to-r from-gray-400 to-slate-500 text-white shadow-gray-500/30">
                                                <span class="w-1.5 h-1.5 rounded-full bg-white/70"></span>
                                                Inactive
                                            </span>
                                            <?php break; ?>
                                        <?php default: ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs leading-5 font-bold rounded-full shadow-md bg-gradient-to-r from-gray-400 to-slate-500 text-white shadow-gray-500/30">
                                                <?php echo e(ucfirst($tenant->status)); ?>

                                            </span>
                                    <?php endswitch; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button wire:click="showTenantDetails(<?php echo e($tenant->id); ?>)"
                                            class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-2">
                                        Details
                                    </button>
                                    <button wire:click="toggleTenantStatus(<?php echo e($tenant->id); ?>)"
                                            class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300 mr-2">
                                        <?php echo e($tenant->status === 'active' ? 'Suspend' : 'Activate'); ?>

                                    </button>
                                    <button wire:click="editTenant(<?php echo e($tenant->id); ?>)"
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-2">
                                        Edit
                                    </button>
                                    <button wire:click="deleteTenant(<?php echo e($tenant->id); ?>)"
                                            onclick="return confirm('Are you sure? This will permanently delete the tenant and all its data.')"
                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">No tenants created yet</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if($tenants && $tenants->hasPages()): ?>
                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900">
                    <?php echo e($tenants->links()); ?>

                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Create/Edit Tenant Modal -->
    <?php if($showCreateModal): ?>
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" wire:click="$set('showCreateModal', false)"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full">
                    <form wire:submit.prevent="saveTenant">
                        <div class="px-6 py-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                <?php echo e($editingTenant ? 'Edit Tenant' : 'Create New Tenant'); ?>

                            </h3>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tenant Name</label>
                                    <input type="text" wire:model="tenantName" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <?php $__errorArgs = ['tenantName'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subdomain</label>
                                    <input type="text" wire:model="subdomain" placeholder="company-name" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <?php $__errorArgs = ['subdomain'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Admin Email</label>
                                    <input type="email" wire:model="adminEmail" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <?php $__errorArgs = ['adminEmail'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <?php if(!$editingTenant): ?>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Admin Password</label>
                                        <input type="password" wire:model="adminPassword" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <?php $__errorArgs = ['adminPassword'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>
                                <?php endif; ?>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Plan</label>
                                    <select wire:model="plan" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <option value="basic">Basic</option>
                                        <option value="pro">Pro</option>
                                        <option value="enterprise">Enterprise</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                    <select wire:model="status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <option value="active">Active</option>
                                        <option value="pending">Pending</option>
                                        <option value="suspended">Suspended</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-900 px-6 py-3 flex justify-end space-x-3">
                            <button type="button" wire:click="$set('showCreateModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                                <?php echo e($editingTenant ? 'Update Tenant' : 'Create Tenant'); ?>

                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Deploy to Tenants Modal -->
    <?php if($showDeployModal): ?>
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" wire:click="$set('showDeployModal', false)"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full">
                    <form wire:submit.prevent="deployToSelectedTenants">
                        <div class="px-6 py-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Deploy to Tenants</h3>

                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                        <?php echo e(count($selectedTenants)); ?> tenants selected for deployment
                                    </p>
                                    <?php $__errorArgs = ['selectedTenants'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Deployment Type</label>
                                    <select wire:model="deploymentType" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <option value="code_only">Code Only</option>
                                        <option value="code_and_migrations">Code + Migrations</option>
                                        <option value="full">Full Deployment</option>
                                        <option value="migrations_only">Migrations Only</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" wire:model="clearCache" class="rounded border-gray-300 text-blue-600">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Clear cache after deployment</span>
                                    </label>
                                </div>

                                <div>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" wire:model="maintenanceMode" class="rounded border-gray-300 text-blue-600">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Enable maintenance mode during deployment</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-900 px-6 py-3 flex justify-end space-x-3">
                            <button type="button" wire:click="$set('showDeployModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                                Deploy to <?php echo e(count($selectedTenants)); ?> Tenants
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tenant Details Modal -->
    <?php if($showDetailsModal && $editingTenant): ?>
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" wire:click="$set('showDetailsModal', false)"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-3xl w-full">
                    <div class="px-6 py-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            Tenant Details: <?php echo e($editingTenant->name); ?>

                        </h3>

                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Tenant ID</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white"><?php echo e($editingTenant->id); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Created</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white"><?php echo e($editingTenant->created_at->format('M d, Y')); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Database</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white"><?php echo e($editingTenant->database); ?></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400">Storage Usage</label>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white"><?php echo e($this->formatBytes($editingTenant->storage_usage ?? 0)); ?></p>
                            </div>
                        </div>

                        <div class="border-t dark:border-gray-700 pt-4">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Actions</h4>
                            <div class="flex space-x-3">
                                <button wire:click="backupTenant(<?php echo e($editingTenant->id); ?>)"
                                        class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                                    Backup
                                </button>
                                <button wire:click="resetTenantData(<?php echo e($editingTenant->id); ?>)"
                                        onclick="return confirm('This will reset all tenant data. Are you sure?')"
                                        class="px-4 py-2 bg-yellow-600 text-white text-sm rounded-md hover:bg-yellow-700">
                                    Reset Data
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-900 px-6 py-3 flex justify-end">
                        <button wire:click="$set('showDetailsModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i), 2) + ' ' + sizes[i];
    }
</script>
<?php $__env->stopPush(); ?><?php /**PATH /home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/livewire/multi-tenant/tenant-manager.blade.php ENDPATH**/ ?>