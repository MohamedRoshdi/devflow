<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Hero Section with Gradient -->
        <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-sky-500 via-blue-500 to-indigo-500 dark:from-sky-600 dark:via-blue-600 dark:to-indigo-600 p-8 shadow-xl overflow-hidden">
            <div class="absolute inset-0 bg-black/10 dark:bg-black/20"></div>
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="p-2 bg-white/20 dark:bg-white/10 backdrop-blur-md rounded-lg">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M10.8 3.9l-6 3.5c-.5.3-.8.8-.8 1.4v6.5c0 .5.3 1.1.8 1.4l6 3.5c.5.3 1.1.3 1.5 0l6-3.5c.5-.3.8-.8.8-1.4V8.7c0-.5-.3-1.1-.8-1.4l-6-3.5c-.4-.2-1-.2-1.5.1zm8.9 4.4l-2.3 1.3V7.2c0-.3-.2-.5-.5-.5s-.5.2-.5.5v2.4l-4.4 2.5V9.7c0-.3-.2-.5-.5-.5s-.5.2-.5.5v2.4l-4.4-2.5V7.2c0-.3-.2-.5-.5-.5s-.5.2-.5.5v2.4L4.3 8.3c.1-.1.3-.2.5-.1l6 3.5c.1.1.3.1.4 0l6-3.5c.2-.1.4 0 .5.1zm-7.6 8.8v-2.4c0-.3-.2-.5-.5-.5s-.5.2-.5.5v2.4l-4.4 2.5V17c0-.3-.2-.5-.5-.5s-.5.2-.5.5v2.4L4.3 18c-.2-.1-.3-.3-.3-.5V11l2.3 1.3v2.4c0 .3.2.5.5.5s.5-.2.5-.5v-2.4L12 15l4.6-2.7v2.4c0 .3.2.5.5.5s.5-.2.5-.5v-2.4l2.3-1.3v6.5c0 .2-.1.4-.3.5l-1.9 1.1V17c0-.3-.2-.5-.5-.5s-.5.2-.5.5v2.7l-4.6 2.4z"/>
                            </svg>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white">Kubernetes Clusters</h1>
                    </div>
                    <p class="text-white/90 text-lg">Manage your Kubernetes clusters and deployments</p>
                </div>
                <div>
                    <button wire:click="addCluster" class="px-6 py-3 bg-white/20 dark:bg-white/10 backdrop-blur-md text-white rounded-xl hover:bg-white/30 dark:hover:bg-white/20 transition-all duration-200 border border-white/30 shadow-lg flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span class="font-medium">Add Cluster</span>
                    </button>
                </div>
            </div>
        </div>

    <!-- Clusters Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Endpoint</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Namespace</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Projects</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <?php $__empty_1 = true; $__currentLoopData = $clusters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cluster): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M10.8 3.9l-6 3.5c-.5.3-.8.8-.8 1.4v6.5c0 .5.3 1.1.8 1.4l6 3.5c.5.3 1.1.3 1.5 0l6-3.5c.5-.3.8-.8.8-1.4V8.7c0-.5-.3-1.1-.8-1.4l-6-3.5c-.4-.2-1-.2-1.5.1zm8.9 4.4l-2.3 1.3V7.2c0-.3-.2-.5-.5-.5s-.5.2-.5.5v2.4l-4.4 2.5V9.7c0-.3-.2-.5-.5-.5s-.5.2-.5.5v2.4l-4.4-2.5V7.2c0-.3-.2-.5-.5-.5s-.5.2-.5.5v2.4L4.3 8.3c.1-.1.3-.2.5-.1l6 3.5c.1.1.3.1.4 0l6-3.5c.2-.1.4 0 .5.1zm-7.6 8.8v-2.4c0-.3-.2-.5-.5-.5s-.5.2-.5.5v2.4l-4.4 2.5V17c0-.3-.2-.5-.5-.5s-.5.2-.5.5v2.4L4.3 18c-.2-.1-.3-.3-.3-.5V11l2.3 1.3v2.4c0 .3.2.5.5.5s.5-.2.5-.5v-2.4L12 15l4.6-2.7v2.4c0 .3.2.5.5.5s.5-.2.5-.5v-2.4l2.3-1.3v6.5c0 .2-.1.4-.3.5l-1.9 1.1V17c0-.3-.2-.5-.5-.5s-.5.2-.5.5v2.7l-4.6 2.4z"/>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        <?php echo e($cluster->name); ?>

                                        <?php if($cluster->is_default): ?>
                                            <span class="ml-2 px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Default</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?php echo e($cluster->endpoint); ?>

                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?php echo e($cluster->namespace); ?>

                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Connected
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <?php echo e($cluster->projects_count ?? 0); ?> projects
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="testClusterConnection(<?php echo e($cluster->id); ?>)"
                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3">
                                Test
                            </button>
                            <button wire:click="showDeployToCluster(<?php echo e($cluster->id); ?>)"
                                    class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 mr-3">
                                Deploy
                            </button>
                            <button wire:click="editCluster(<?php echo e($cluster->id); ?>)"
                                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3">
                                Edit
                            </button>
                            <button wire:click="deleteCluster(<?php echo e($cluster->id); ?>)"
                                    onclick="return confirm('Are you sure you want to delete this cluster?')"
                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                Delete
                            </button>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="text-gray-400">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="mt-4 text-lg">No Kubernetes clusters configured</p>
                                <p class="mt-2 text-sm">Get started by adding your first cluster</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if($clusters->hasPages()): ?>
            <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900">
                <?php echo e($clusters->links()); ?>

            </div>
        <?php endif; ?>
    </div>

    <!-- Add/Edit Cluster Modal -->
    <?php if($showAddClusterModal): ?>
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" wire:click="$set('showAddClusterModal', false)"></div>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <form wire:submit.prevent="saveCluster">
                        <div class="px-6 py-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                <?php echo e($editingCluster ? 'Edit Cluster' : 'Add Kubernetes Cluster'); ?>

                            </h3>

                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cluster Name</label>
                                    <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">API Endpoint</label>
                                    <input type="url" wire:model="endpoint" placeholder="https://k8s.example.com:6443" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <?php $__errorArgs = ['endpoint'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Namespace (optional)</label>
                                    <input type="text" wire:model="namespace" placeholder="default" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    <?php $__errorArgs = ['namespace'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kubeconfig</label>
                                    <textarea wire:model="kubeconfig" rows="8" placeholder="Paste your kubeconfig file content here..." class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm font-mono text-xs"></textarea>
                                    <?php $__errorArgs = ['kubeconfig'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" wire:model="isDefault" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Set as default cluster</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-900 px-6 py-3 flex justify-end space-x-3">
                            <button type="button" wire:click="$set('showAddClusterModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <?php echo e($editingCluster ? 'Update Cluster' : 'Add Cluster'); ?>

                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Deploy to Kubernetes Modal -->
    <?php if($showDeployModal): ?>
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" wire:click="$set('showDeployModal', false)"></div>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <form wire:submit.prevent="deployToKubernetes">
                        <div class="px-6 py-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Deploy to Kubernetes</h3>

                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Select Project</label>
                                    <select wire:model="deploymentProject" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        <option value="">Choose a project...</option>
                                        <?php $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($project->id); ?>"><?php echo e($project->name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                    <?php $__errorArgs = ['deploymentProject'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Replicas</label>
                                        <input type="number" wire:model="replicas" min="1" max="100" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Service Type</label>
                                        <select wire:model="serviceType" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                            <option value="ClusterIP">ClusterIP</option>
                                            <option value="NodePort">NodePort</option>
                                            <option value="LoadBalancer">LoadBalancer</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" wire:model="enableAutoscaling" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Enable Auto-scaling</span>
                                    </label>
                                </div>

                                <?php if($enableAutoscaling): ?>
                                    <div class="grid grid-cols-2 gap-4 pl-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Min Replicas</label>
                                            <input type="number" wire:model="minReplicas" min="1" max="50" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Replicas</label>
                                            <input type="number" wire:model="maxReplicas" min="2" max="100" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="border-t dark:border-gray-700 pt-4">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Resource Limits</h4>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">CPU Request</label>
                                            <input type="text" wire:model="cpuRequest" placeholder="100m" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">CPU Limit</label>
                                            <input type="text" wire:model="cpuLimit" placeholder="500m" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Memory Request</label>
                                            <input type="text" wire:model="memoryRequest" placeholder="256Mi" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Memory Limit</label>
                                            <input type="text" wire:model="memoryLimit" placeholder="512Mi" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-900 px-6 py-3 flex justify-end space-x-3">
                            <button type="button" wire:click="$set('showDeployModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                Deploy to Kubernetes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
    </div>
</div><?php /**PATH /home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/livewire/kubernetes/cluster-manager.blade.php ENDPATH**/ ?>