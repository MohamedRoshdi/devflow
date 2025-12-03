<!-- Tab Loading Overlay Component -->
<div x-show="tabLoading"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm z-40 flex items-center justify-center"
     style="display: none;">
    <div class="relative">
        <!-- Animated loader -->
        <div class="flex flex-col items-center space-y-4">
            <!-- Spinner -->
            <div class="relative w-16 h-16">
                <div class="absolute inset-0 border-4 border-blue-200 dark:border-blue-800 rounded-full"></div>
                <div class="absolute inset-0 border-4 border-blue-600 dark:border-blue-400 rounded-full border-t-transparent animate-spin"></div>
            </div>

            <!-- Loading text -->
            <div class="text-center">
                <p class="text-lg font-semibold text-gray-900 dark:text-white animate-pulse">Loading content...</p>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Please wait a moment</p>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/components/tab-loading-overlay.blade.php ENDPATH**/ ?>