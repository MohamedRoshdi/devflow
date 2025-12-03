<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Invitation - <?php echo e(config('app.name')); ?></title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900">
    <div class="min-h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Logo -->
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Team Invitation</h2>
            </div>

            <?php if($expired): ?>
                <!-- Expired Invitation -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 border border-gray-200 dark:border-gray-700">
                    <div class="text-center">
                        <svg class="mx-auto h-16 w-16 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Invitation Expired</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            This invitation has expired. Please contact the team owner for a new invitation.
                        </p>
                        <div class="mt-6">
                            <a href="<?php echo e(route('dashboard')); ?>" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                                Go to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Active Invitation -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 border border-gray-200 dark:border-gray-700">
                    <!-- Team Info -->
                    <div class="text-center mb-6">
                        <img src="<?php echo e($invitation->team->avatar_url); ?>" alt="<?php echo e($invitation->team->name); ?>" class="w-20 h-20 mx-auto rounded-lg mb-4">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white"><?php echo e($invitation->team->name); ?></h3>
                        <?php if($invitation->team->description): ?>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400"><?php echo e($invitation->team->description); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Invitation Details -->
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 mb-6 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Invited by:</span>
                            <span class="text-sm text-gray-900 dark:text-white"><?php echo e($invitation->inviter->name); ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Your role:</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                <?php echo e($invitation->role === 'admin' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : ''); ?>

                                <?php echo e($invitation->role === 'member' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' : ''); ?>

                                <?php echo e($invitation->role === 'viewer' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : ''); ?>">
                                <?php echo e(ucfirst($invitation->role)); ?>

                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Expires:</span>
                            <span class="text-sm text-gray-900 dark:text-white"><?php echo e($invitation->expires_at->format('M d, Y')); ?></span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <?php if(auth()->guard()->check()): ?>
                        <?php if(auth()->user()->email === $invitation->email): ?>
                            <form action="<?php echo e(route('invitations.accept', $invitation->token)); ?>" method="POST">
                                <?php echo csrf_field(); ?>
                                <div class="flex flex-col space-y-3">
                                    <button type="submit" class="w-full px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                                        Accept Invitation
                                    </button>
                                    <a href="<?php echo e(route('teams.index')); ?>" class="w-full px-4 py-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium rounded-lg transition-colors text-center">
                                        Decline
                                    </a>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                    This invitation is for <?php echo e($invitation->email); ?>. Please log out and sign in with the correct account.
                                </p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="space-y-3">
                            <p class="text-sm text-gray-600 dark:text-gray-400 text-center mb-4">
                                Please log in to accept this invitation
                            </p>
                            <a href="<?php echo e(route('login')); ?>" class="block w-full px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors text-center">
                                Log In to Accept
                            </a>
                            <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                                Don't have an account?
                                <a href="<?php echo e(route('register')); ?>" class="text-indigo-600 dark:text-indigo-400 hover:underline">Sign up</a>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Info Box -->
                    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-blue-500 dark:text-blue-400 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            <div class="text-sm text-blue-800 dark:text-blue-200">
                                <p class="font-medium">What is <?php echo e(ucfirst($invitation->role)); ?>?</p>
                                <p class="mt-1">
                                    <?php if($invitation->role === 'admin'): ?>
                                        Admins can manage team settings, invite members, and manage projects.
                                    <?php elseif($invitation->role === 'member'): ?>
                                        Members can create and manage projects but cannot modify team settings.
                                    <?php else: ?>
                                        Viewers have read-only access to team projects and resources.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php /**PATH /home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/teams/invitation.blade.php ENDPATH**/ ?>