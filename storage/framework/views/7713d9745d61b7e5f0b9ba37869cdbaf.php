<!-- Skeleton Loaders for Tab Content -->

<!-- Git Tab Skeleton Loader -->
<div x-show="activeTab === 'git' && $wire.commitsLoading" class="space-y-6 animate-pulse">
    <!-- Status Cards Skeleton -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="h-32 bg-gray-200 dark:bg-gray-700 rounded-xl"></div>
        <div class="h-32 bg-gray-200 dark:bg-gray-700 rounded-xl"></div>
        <div class="h-32 bg-gray-200 dark:bg-gray-700 rounded-xl"></div>
    </div>

    <!-- Commits List Skeleton -->
    <div class="space-y-4">
        <?php for($i = 0; $i < 5; $i++): ?>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 space-y-3">
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24"></div>
                        <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                        <div class="flex gap-3">
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-32"></div>
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-40"></div>
                        </div>
                    </div>
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24"></div>
                </div>
            </div>
        <?php endfor; ?>
    </div>
</div>

<!-- Docker Tab Skeleton Loader -->
<div x-show="activeTab === 'docker' && !dockerReady" class="space-y-6 animate-pulse">
    <!-- Container Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php for($i = 0; $i < 6; $i++): ?>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded w-32"></div>
                        <div class="h-6 w-16 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                    </div>
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24"></div>
                    <div class="space-y-2">
                        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-full"></div>
                        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-5/6"></div>
                    </div>
                </div>
            </div>
        <?php endfor; ?>
    </div>
</div>

<!-- Environment Tab Skeleton Loader -->
<div x-show="activeTab === 'environment'" class="space-y-6 animate-pulse">
    <div class="bg-white dark:bg-gray-800 rounded-xl p-8 border border-gray-200 dark:border-gray-700">
        <div class="space-y-4">
            <?php for($i = 0; $i < 8; $i++): ?>
                <div class="flex items-center gap-4 py-3 border-b border-gray-100 dark:border-gray-700">
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-40"></div>
                    <div class="flex-1 h-10 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    <div class="h-8 w-8 bg-gray-200 dark:bg-gray-700 rounded"></div>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- Deployments Tab Skeleton Loader -->
<div x-show="activeTab === 'deployments'" class="space-y-4 animate-pulse">
    <?php for($i = 0; $i < 5; $i++): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between gap-4">
                <div class="flex-1 space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="h-6 w-24 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                        <div class="h-6 w-20 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    </div>
                    <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                    <div class="flex gap-4">
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-32"></div>
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24"></div>
                    </div>
                </div>
                <div class="h-10 w-32 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
            </div>
        </div>
    <?php endfor; ?>
</div>

<!-- Logs Tab Skeleton Loader -->
<div x-show="activeTab === 'logs'" class="animate-pulse">
    <div class="bg-gray-900 rounded-xl p-6 border border-gray-700 min-h-[500px]">
        <div class="space-y-2">
            <?php for($i = 0; $i < 20; $i++): ?>
                <div class="h-4 bg-gray-800 rounded" style="width: <?php echo e(rand(60, 100)); ?>%"></div>
            <?php endfor; ?>
        </div>
    </div>
</div>
<?php /**PATH /home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/components/skeleton-loaders.blade.php ENDPATH**/ ?>