<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Create New Project</h1>
        <p class="text-gray-600 mt-1">Set up a new deployment project</p>
    </div>

    <div class="bg-white rounded-lg shadow p-8">
        <form wire:submit="createProject" class="space-y-8">
            <!-- Basic Information -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Project Name *</label>
                        <input wire:model.live="name" 
                               id="name" 
                               type="text" 
                               required
                               placeholder="My Awesome Project"
                               class="input <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> 
                            <p class="text-red-500 text-sm mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div>
                        <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">Slug *</label>
                        <input wire:model="slug" 
                               id="slug" 
                               type="text" 
                               required
                               placeholder="my-awesome-project"
                               class="input <?php $__errorArgs = ['slug'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <?php $__errorArgs = ['slug'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> 
                            <p class="text-red-500 text-sm mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </div>

            <!-- Server Selection -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Server Configuration</h3>
                
                <?php if(session()->has('server_status_updated')): ?>
                    <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-2 rounded text-sm">
                        <?php echo e(session('server_status_updated')); ?>

                    </div>
                <?php endif; ?>

                <div>
                    <label for="server_id" class="block text-sm font-medium text-gray-700 mb-2">Select Server *</label>
                    <div class="space-y-3">
                        <?php $__empty_1 = true; $__currentLoopData = $servers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $server): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="border rounded-lg p-4 <?php echo e($server_id == $server->id ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-blue-300'); ?>">
                                <div class="flex items-center justify-between">
                                    <label class="flex items-center flex-1 cursor-pointer">
                                        <input type="radio" 
                                               wire:model="server_id" 
                                               value="<?php echo e($server->id); ?>"
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                        <div class="ml-3 flex-1">
                                            <div class="flex items-center">
                                                <span class="font-medium text-gray-900"><?php echo e($server->name); ?></span>
                                                <span class="ml-2 px-2 py-1 rounded-full text-xs
                                                    <?php if($server->status === 'online'): ?> bg-green-100 text-green-800
                                                    <?php elseif($server->status === 'offline'): ?> bg-red-100 text-red-800
                                                    <?php elseif($server->status === 'maintenance'): ?> bg-yellow-100 text-yellow-800
                                                    <?php else: ?> bg-gray-100 text-gray-800
                                                    <?php endif; ?>">
                                                    <?php echo e(ucfirst($server->status)); ?>

                                                </span>
                                            </div>
                                            <div class="text-sm text-gray-500 mt-1">
                                                <?php echo e($server->ip_address); ?> • <?php echo e($server->cpu_cores ?? '?'); ?> CPU • <?php echo e($server->memory_gb ?? '?'); ?> GB RAM
                                                <?php if($server->docker_installed): ?>
                                                    • <span class="text-green-600">Docker ✓</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </label>
                                    <button type="button"
                                            wire:click="refreshServerStatus(<?php echo e($server->id); ?>)"
                                            class="ml-3 text-blue-600 hover:text-blue-700 text-sm font-medium"
                                            title="Refresh server status">
                                        🔄 Refresh
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <p class="text-yellow-600 text-sm">
                                ⚠️ You need to add a server first. 
                                <a href="<?php echo e(route('servers.create')); ?>" class="underline font-medium">Add server now</a>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <?php $__errorArgs = ['server_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> 
                        <p class="text-red-500 text-sm mt-2"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            <!-- Repository -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Repository</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="repository_url" class="block text-sm font-medium text-gray-700 mb-2">Repository URL *</label>
                        <input wire:model="repository_url" 
                               id="repository_url" 
                               type="text" 
                               placeholder="https://github.com/user/repo.git or git@github.com:user/repo.git"
                               class="input <?php $__errorArgs = ['repository_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <p class="text-xs text-gray-500 mt-1">
                            Supports HTTPS or SSH format. SSH recommended for private repositories.
                        </p>
                        <?php $__errorArgs = ['repository_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> 
                            <p class="text-red-500 text-sm mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div>
                        <label for="branch" class="block text-sm font-medium text-gray-700 mb-2">Branch *</label>
                        <input wire:model="branch" 
                               id="branch" 
                               type="text" 
                               required
                               class="input">
                    </div>
                </div>
            </div>

            <!-- Framework & Runtime -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Framework & Runtime</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="framework" class="block text-sm font-medium text-gray-700 mb-2">Framework</label>
                        <select wire:model="framework" id="framework" class="input">
                            <option value="">Select Framework...</option>
                            <option value="Laravel">Laravel</option>
                            <option value="Node.js">Node.js</option>
                            <option value="React">React</option>
                            <option value="Vue">Vue.js</option>
                            <option value="Next.js">Next.js</option>
                            <option value="Django">Django</option>
                            <option value="Flask">Flask</option>
                        </select>
                    </div>

                    <div>
                        <label for="php_version" class="block text-sm font-medium text-gray-700 mb-2">PHP Version</label>
                        <select wire:model="php_version" id="php_version" class="input">
                            <option value="8.3">8.3</option>
                            <option value="8.2">8.2</option>
                            <option value="8.1">8.1</option>
                            <option value="8.0">8.0</option>
                        </select>
                    </div>

                    <div>
                        <label for="node_version" class="block text-sm font-medium text-gray-700 mb-2">Node Version</label>
                        <select wire:model="node_version" id="node_version" class="input">
                            <option value="20">20 (LTS)</option>
                            <option value="18">18 (LTS)</option>
                            <option value="16">16</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Build Configuration -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Build Configuration</h3>
                <div class="space-y-6">
                    <div>
                        <label for="root_directory" class="block text-sm font-medium text-gray-700 mb-2">Root Directory *</label>
                        <input wire:model="root_directory" 
                               id="root_directory" 
                               type="text" 
                               required
                               placeholder="/"
                               class="input">
                    </div>

                    <div>
                        <label for="build_command" class="block text-sm font-medium text-gray-700 mb-2">Build Command</label>
                        <input wire:model="build_command" 
                               id="build_command" 
                               type="text" 
                               placeholder="npm run build"
                               class="input">
                    </div>

                    <div>
                        <label for="start_command" class="block text-sm font-medium text-gray-700 mb-2">Start Command</label>
                        <input wire:model="start_command" 
                               id="start_command" 
                               type="text" 
                               placeholder="npm start"
                               class="input">
                    </div>
                </div>
            </div>

            <!-- Options -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Options</h3>
                <div class="flex items-center">
                    <input wire:model="auto_deploy" 
                           id="auto_deploy" 
                           type="checkbox"
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="auto_deploy" class="ml-2 block text-sm text-gray-900">
                        Enable auto-deployment on git push
                    </label>
                </div>
            </div>

            <!-- GPS Location -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">GPS Location (Optional)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="latitude" class="block text-sm font-medium text-gray-700 mb-2">Latitude</label>
                        <input wire:model="latitude" 
                               id="latitude" 
                               type="number" 
                               step="any"
                               placeholder="0.0"
                               class="input">
                    </div>

                    <div>
                        <label for="longitude" class="block text-sm font-medium text-gray-700 mb-2">Longitude</label>
                        <input wire:model="longitude" 
                               id="longitude" 
                               type="number" 
                               step="any"
                               placeholder="0.0"
                               class="input">
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-6 border-t">
                <a href="<?php echo e(route('projects.index')); ?>" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    Create Project
                </button>
            </div>
        </form>
    </div>
</div>

<?php /**PATH /home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/livewire/projects/project-create.blade.php ENDPATH**/ ?>