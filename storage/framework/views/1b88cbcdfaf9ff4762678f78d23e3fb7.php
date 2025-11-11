<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    
    <title><?php echo e($title ?? config('app.name')); ?></title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#2563eb">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icon-192.png">
    
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <?php if(auth()->guard()->check()): ?>
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="<?php echo e(route('dashboard')); ?>" class="text-2xl font-bold text-blue-600">
                            DevFlow Pro
                        </a>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="<?php echo e(route('dashboard')); ?>" 
                           class="<?php echo e(request()->routeIs('dashboard') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-900'); ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium hover:border-blue-500">
                            Dashboard
                        </a>
                        <a href="<?php echo e(route('servers.index')); ?>" 
                           class="<?php echo e(request()->routeIs('servers.*') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-900'); ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium hover:border-blue-500">
                            Servers
                        </a>
                        <a href="<?php echo e(route('projects.index')); ?>" 
                           class="<?php echo e(request()->routeIs('projects.*') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-900'); ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium hover:border-blue-500">
                            Projects
                        </a>
                        <a href="<?php echo e(route('deployments.index')); ?>" 
                           class="<?php echo e(request()->routeIs('deployments.*') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-900'); ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium hover:border-blue-500">
                            Deployments
                        </a>
                        <a href="<?php echo e(route('analytics')); ?>" 
                           class="<?php echo e(request()->routeIs('analytics') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-900'); ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium hover:border-blue-500">
                            Analytics
                        </a>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="ml-3 relative">
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-700"><?php echo e(auth()->user()->name); ?></span>
                            <form method="POST" action="<?php echo e(route('logout')); ?>" class="inline">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="text-sm text-red-600 hover:text-red-700">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <?php echo e($slot); ?>

        </div>
    </main>

    <!-- Toast Notifications -->
    <div id="toast-container" class="fixed bottom-4 right-4 space-y-2 z-50"></div>
</body>
</html>

<?php /**PATH /home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/layouts/app.blade.php ENDPATH**/ ?>