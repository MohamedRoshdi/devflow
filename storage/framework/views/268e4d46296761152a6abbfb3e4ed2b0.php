<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Hero Section with Gradient -->
        <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-violet-500 via-purple-500 to-fuchsia-500 dark:from-violet-600 dark:via-purple-600 dark:to-fuchsia-600 p-8 shadow-xl overflow-hidden">
            <div class="absolute inset-0 bg-black/10 dark:bg-black/20"></div>
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="p-2 bg-white/20 dark:bg-white/10 backdrop-blur-md rounded-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white">CI/CD Pipelines</h1>
                    </div>
                    <p class="text-white/90 text-lg">Build and manage your continuous integration and deployment pipelines</p>
                </div>
                <div>
                    <button wire:click="createPipeline" class="px-6 py-3 bg-white text-violet-600 rounded-lg hover:bg-white/90 transition-all shadow-lg hover:shadow-xl font-semibold flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Create Pipeline</span>
                    </button>
                </div>
            </div>
        </div>

    <!-- Pipelines Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php $__empty_1 = true; $__currentLoopData = $pipelines; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pipeline): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <?php switch($pipeline->provider):
                                case ('github'): ?>
                                    <svg class="w-8 h-8 text-gray-900 dark:text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                                    </svg>
                                    <?php break; ?>
                                <?php case ('gitlab'): ?>
                                    <svg class="w-8 h-8 text-orange-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M22.65 14.39L12 22.13 1.35 14.39a.84.84 0 0 1-.3-.94l1.22-3.78 2.44-7.51A.42.42 0 0 1 4.82 2a.43.43 0 0 1 .58 0 .42.42 0 0 1 .11.18l2.44 7.49h8.1l2.44-7.51A.42.42 0 0 1 18.6 2a.43.43 0 0 1 .58 0 .42.42 0 0 1 .11.18l2.44 7.51L23 13.45a.84.84 0 0 1-.35.94z"/>
                                    </svg>
                                    <?php break; ?>
                                <?php default: ?>
                                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                    </svg>
                            <?php endswitch; ?>
                            <div class="ml-3">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo e($pipeline->name); ?></h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo e($pipeline->project->name); ?></p>
                            </div>
                        </div>
                        <div class="relative">
                            <button wire:click="togglePipeline(<?php echo e($pipeline->id); ?>)"
                                    class="w-12 h-6 rounded-full <?php echo e($pipeline->enabled ? 'bg-green-500' : 'bg-gray-300'); ?> relative transition-colors">
                                <span class="absolute w-5 h-5 bg-white rounded-full shadow transition-transform <?php echo e($pipeline->enabled ? 'translate-x-6' : 'translate-x-0.5'); ?>" style="top: 0.125rem;"></span>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-2 mb-4">
                        <div class="flex items-center text-sm">
                            <span class="text-gray-500 dark:text-gray-400 w-20">Triggers:</span>
                            <div class="flex flex-wrap gap-1">
                                <?php $__currentLoopData = $pipeline->trigger_events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-xs">
                                        <?php echo e($event); ?>

                                    </span>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                        <div class="flex items-center text-sm">
                            <span class="text-gray-500 dark:text-gray-400 w-20">Branches:</span>
                            <div class="flex flex-wrap gap-1">
                                <?php $__currentLoopData = $pipeline->branch_filters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $branch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded text-xs">
                                        <?php echo e($branch); ?>

                                    </span>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    </div>

                    <?php if($pipeline->lastRun): ?>
                        <div class="border-t dark:border-gray-700 pt-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Last run:</span>
                                <div class="flex items-center">
                                    <?php switch($pipeline->lastRun->status):
                                        case ('success'): ?>
                                            <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                            <span class="text-green-600 dark:text-green-400">Success</span>
                                            <?php break; ?>
                                        <?php case ('failed'): ?>
                                            <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                            <span class="text-red-600 dark:text-red-400">Failed</span>
                                            <?php break; ?>
                                        <?php case ('running'): ?>
                                            <span class="w-2 h-2 bg-yellow-500 rounded-full mr-2 animate-pulse"></span>
                                            <span class="text-yellow-600 dark:text-yellow-400">Running</span>
                                            <?php break; ?>
                                        <?php default: ?>
                                            <span class="w-2 h-2 bg-gray-500 rounded-full mr-2"></span>
                                            <span class="text-gray-600 dark:text-gray-400"><?php echo e($pipeline->lastRun->status); ?></span>
                                    <?php endswitch; ?>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <?php echo e($pipeline->lastRun->created_at->diffForHumans()); ?>

                            </p>
                        </div>
                    <?php endif; ?>

                    <div class="flex justify-between items-center mt-4 pt-4 border-t dark:border-gray-700">
                        <div class="flex space-x-2">
                            <button wire:click="runPipeline(<?php echo e($pipeline->id); ?>)"
                                    class="p-2 text-green-600 hover:bg-green-100 dark:hover:bg-green-900 rounded-lg transition-colors"
                                    title="Run Pipeline">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </button>
                            <button wire:click="showPipelineConfig(<?php echo e($pipeline->id); ?>)"
                                    class="p-2 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900 rounded-lg transition-colors"
                                    title="View Configuration">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                            </button>
                            <button wire:click="editPipeline(<?php echo e($pipeline->id); ?>)"
                                    class="p-2 text-indigo-600 hover:bg-indigo-100 dark:hover:bg-indigo-900 rounded-lg transition-colors"
                                    title="Edit Pipeline">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                        </div>
                        <button wire:click="downloadConfig(<?php echo e($pipeline->id); ?>)"
                                class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                            Download
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="col-span-full">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No pipelines configured</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Get started by creating your first CI/CD pipeline</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if($pipelines->hasPages()): ?>
        <div class="mt-6">
            <?php echo e($pipelines->links()); ?>

        </div>
    <?php endif; ?>

    <!-- Create/Edit Pipeline Modal -->
    <?php if($showCreateModal): ?>
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showCreateModal', false)"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                    <form wire:submit.prevent="savePipeline">
                        <div class="px-6 py-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                <?php echo e($editingPipeline ? 'Edit Pipeline' : 'Create CI/CD Pipeline'); ?>

                            </h3>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Project</label>
                                    <select wire:model="projectId" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <option value="">Select a project...</option>
                                        <?php $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($project->id); ?>"><?php echo e($project->name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                    <?php $__errorArgs = ['projectId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-red-500 text-xs"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pipeline Name</label>
                                    <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
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
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">CI/CD Provider</label>
                                    <div class="mt-2 grid grid-cols-3 gap-3">
                                        <?php $__currentLoopData = ['github' => 'GitHub Actions', 'gitlab' => 'GitLab CI', 'bitbucket' => 'Bitbucket', 'jenkins' => 'Jenkins', 'custom' => 'Custom']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <label class="flex items-center p-3 border rounded-lg cursor-pointer <?php echo e($provider === $value ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900' : 'border-gray-300 dark:border-gray-600'); ?>">
                                                <input type="radio" wire:model="provider" value="<?php echo e($value); ?>" class="sr-only">
                                                <span class="text-sm font-medium <?php echo e($provider === $value ? 'text-indigo-900 dark:text-indigo-100' : 'text-gray-900 dark:text-gray-100'); ?>"><?php echo e($label); ?></span>
                                            </label>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Trigger Events</label>
                                    <div class="space-y-2">
                                        <?php $__currentLoopData = ['push' => 'Push to repository', 'pull_request' => 'Pull request', 'schedule' => 'Scheduled', 'manual' => 'Manual trigger']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <label class="inline-flex items-center mr-4">
                                                <input type="checkbox" wire:model="triggerEvents" value="<?php echo e($value); ?>" class="rounded border-gray-300 text-indigo-600">
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300"><?php echo e($label); ?></span>
                                            </label>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Branch Filters</label>
                                    <input type="text" wire:model="branchFilters.0" placeholder="main" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <p class="mt-1 text-xs text-gray-500">Comma-separated list of branches</p>
                                </div>

                                <div class="border-t dark:border-gray-700 pt-4">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Pipeline Stages</h4>
                                    <div class="space-y-2">
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" wire:model="enableTests" class="rounded border-gray-300 text-indigo-600">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Run tests</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" wire:model="enableBuild" class="rounded border-gray-300 text-indigo-600">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Build Docker image</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" wire:model="enableDeploy" class="rounded border-gray-300 text-indigo-600">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Deploy to production</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" wire:model="enableSecurityScan" class="rounded border-gray-300 text-indigo-600">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Security scanning</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" wire:model="enableQualityCheck" class="rounded border-gray-300 text-indigo-600">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Code quality checks</span>
                                        </label>
                                    </div>
                                </div>

                                <?php if($enableDeploy): ?>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Deployment Strategy</label>
                                        <select wire:model="deploymentStrategy" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                            <option value="docker">Docker Compose</option>
                                            <option value="kubernetes">Kubernetes</option>
                                            <option value="ssh">SSH Deploy</option>
                                            <option value="devflow">DevFlow API</option>
                                        </select>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-900 px-6 py-3 flex justify-end space-x-3">
                            <button type="button" wire:click="$set('showCreateModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                <?php echo e($editingPipeline ? 'Update Pipeline' : 'Create Pipeline'); ?>

                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- View Configuration Modal -->
    <?php if($showConfigModal && $selectedPipeline): ?>
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" wire:click="$set('showConfigModal', false)"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="px-6 py-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            Pipeline Configuration: <?php echo e($selectedPipeline->name); ?>

                        </h3>

                        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                            <pre class="text-sm text-gray-300"><code><?php echo e(yaml_emit($selectedPipeline->configuration)); ?></code></pre>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <button wire:click="$set('showConfigModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900">
                                Close
                            </button>
                            <button wire:click="downloadConfig(<?php echo e($selectedPipeline->id); ?>)" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                Download Configuration
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    </div>
</div><?php /**PATH /home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/livewire/cicd/pipeline-builder.blade.php ENDPATH**/ ?>