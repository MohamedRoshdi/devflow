<div class="relative" x-data="{ open: false, unreadCount: <?php if ((object) ('notifications') instanceof \Livewire\WireDirective) : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('notifications'->value()); ?>')<?php echo e('notifications'->hasModifier('live') ? '.live' : ''); ?><?php else : ?>window.Livewire.find('<?php echo e($__livewire->getId()); ?>').entangle('<?php echo e('notifications'); ?>')<?php endif; ?>.defer }">
    <!-- Notification Bell -->
    <button @click="open = !open"
            class="relative p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
        </svg>

        <!-- Unread Count Badge -->
        <?php if($notifications->where('read', false)->count() > 0): ?>
            <span class="absolute -top-1 -right-1 h-5 w-5 bg-red-500 rounded-full flex items-center justify-center">
                <span class="text-xs text-white font-bold">
                    <?php echo e($notifications->where('read', false)->count()); ?>

                </span>
            </span>
        <?php endif; ?>
    </button>

    <!-- Notification Dropdown -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95"
         @click.away="open = false"
         class="absolute right-0 mt-2 w-96 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 z-50">

        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Deployment Notifications
                </h3>
                <div class="flex items-center gap-2">
                    <!-- Sound Toggle -->
                    <button wire:click="toggleSound"
                            class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors"
                            title="<?php echo e($soundEnabled ? 'Disable' : 'Enable'); ?> sound">
                        <svg class="w-4 h-4 <?php echo e($soundEnabled ? 'text-blue-600' : 'text-gray-400'); ?>"
                             fill="currentColor" viewBox="0 0 20 20">
                            <path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"></path>
                        </svg>
                    </button>

                    <!-- Desktop Notifications Toggle -->
                    <button wire:click="toggleDesktopNotifications"
                            class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors"
                            title="<?php echo e($desktopNotificationsEnabled ? 'Disable' : 'Enable'); ?> desktop notifications">
                        <svg class="w-4 h-4 <?php echo e($desktopNotificationsEnabled ? 'text-blue-600' : 'text-gray-400'); ?>"
                             fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"></path>
                        </svg>
                    </button>

                    <!-- Clear All -->
                    <?php if($notifications->count() > 0): ?>
                        <button wire:click="clearAll"
                                class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors"
                                title="Clear all notifications">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="max-h-96 overflow-y-auto">
            <?php $__empty_1 = true; $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div wire:click="markAsRead('<?php echo e($notification['id']); ?>')"
                     class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer border-b border-gray-100 dark:border-gray-700/50 <?php echo e(!$notification['read'] ? 'bg-blue-50/50 dark:bg-blue-900/20' : ''); ?>">
                    <div class="flex items-start gap-3">
                        <!-- Icon -->
                        <div class="flex-shrink-0 mt-1">
                            <?php switch($notification['type']):
                                case ('success'): ?>
                                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <?php break; ?>
                                <?php case ('error'): ?>
                                    <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </div>
                                    <?php break; ?>
                                <?php case ('warning'): ?>
                                    <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                    </div>
                                    <?php break; ?>
                                <?php default: ?>
                                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                            <?php endswitch; ?>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                <?php echo e($notification['project_name']); ?>

                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                <?php echo e($notification['message']); ?>

                            </p>
                            <div class="flex items-center gap-3 mt-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                    <?php if($notification['status'] === 'success'): ?>
                                        bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                    <?php elseif($notification['status'] === 'failed'): ?>
                                        bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                    <?php elseif($notification['status'] === 'running'): ?>
                                        bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                                    <?php else: ?>
                                        bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400
                                    <?php endif; ?>">
                                    <?php echo e(ucfirst($notification['status'])); ?>

                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    <?php echo e($notification['timestamp']->diffForHumans()); ?>

                                </span>
                            </div>
                        </div>

                        <!-- View Button -->
                        <a href="<?php echo e(route('deployments.show', $notification['deployment_id'])); ?>"
                           class="flex-shrink-0 text-xs text-blue-600 dark:text-blue-400 hover:underline">
                            View →
                        </a>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="px-4 py-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No notifications yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript for Desktop Notifications -->
    <script>
        document.addEventListener('livewire:init', () => {
            // Request notification permission
            Livewire.on('request-notification-permission', () => {
                if ('Notification' in window && Notification.permission === 'default') {
                    Notification.requestPermission();
                }
            });

            // Show desktop notification
            Livewire.on('show-desktop-notification', (event) => {
                if ('Notification' in window && Notification.permission === 'granted') {
                    const notification = new Notification(event.title, {
                        body: event.body,
                        icon: event.icon,
                        tag: 'deployment-notification',
                        requireInteraction: false
                    });

                    notification.onclick = function() {
                        window.focus();
                        notification.close();
                    };

                    setTimeout(() => notification.close(), 5000);
                }
            });

            // Play notification sound
            Livewire.on('play-notification-sound', (event) => {
                const audio = new Audio(`/sounds/notification-${event.type}.mp3`);
                audio.volume = 0.5;
                audio.play().catch(e => console.log('Could not play sound:', e));
            });
        });
    </script>
</div><?php /**PATH /home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/livewire/notifications/deployment-notifications.blade.php ENDPATH**/ ?>