<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    
    <title><?php echo e($title ?? config('app.name')); ?></title>
    
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-blue-600">DevFlow Pro</h1>
                <p class="text-gray-600 mt-2">Deployment Management System</p>
            </div>
            
            <?php echo e($slot); ?>

        </div>
    </div>
</body>
</html>

<?php /**PATH /home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/layouts/guest.blade.php ENDPATH**/ ?>