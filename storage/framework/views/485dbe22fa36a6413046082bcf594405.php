<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-blue-800 via-blue-900 to-blue-800 shadow-2xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex items-center gap-4">
                    <a href="<?php echo e(route('servers.security', $server)); ?>" class="p-2 bg-white/10 rounded-lg hover:bg-white/20 transition-colors">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <div class="flex items-center gap-3">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <div>
                                <h1 class="text-2xl font-bold text-white">SSH Security</h1>
                                <p class="text-white/80"><?php echo e($server->name); ?> - SSH Configuration</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button wire:click="loadSSHConfig" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" wire:loading.class="animate-spin" wire:target="loadSSHConfig" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Refresh
                    </button>
                    <button wire:click="$set('showHardenConfirm', true)"
                        class="px-4 py-2 bg-white text-blue-800 font-medium rounded-lg hover:bg-gray-100 transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        Quick Harden
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Message -->
    <?php if($flashMessage): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="rounded-lg p-4 <?php echo e($flashType === 'success' ? 'bg-green-900/50 text-green-200 border border-green-700' : 'bg-red-900/50 text-red-200 border border-red-700'); ?>">
                <?php echo e($flashMessage); ?>

            </div>
        </div>
    <?php endif; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Current Configuration -->
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6">
                <h3 class="text-lg font-semibold text-white mb-6">Current Configuration</h3>

                <div class="space-y-4">
                    <!-- SSH Port -->
                    <div class="flex items-center justify-between p-4 bg-gray-900/50 rounded-lg">
                        <div>
                            <h4 class="text-white font-medium">SSH Port</h4>
                            <p class="text-gray-400 text-sm">Current port: <?php echo e($port); ?></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="number" wire:model="port" min="1" max="65535"
                                class="w-24 px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-white text-center">
                            <button wire:click="changePort"
                                class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm">
                                Change
                            </button>
                        </div>
                    </div>

                    <!-- Root Login -->
                    <div class="flex items-center justify-between p-4 bg-gray-900/50 rounded-lg">
                        <div>
                            <h4 class="text-white font-medium">Root Login</h4>
                            <p class="text-gray-400 text-sm">Allow root user to login via SSH</p>
                        </div>
                        <button wire:click="toggleRootLogin"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors <?php echo e($rootLoginEnabled ? 'bg-green-600' : 'bg-gray-600'); ?>">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform <?php echo e($rootLoginEnabled ? 'translate-x-6' : 'translate-x-1'); ?>"></span>
                        </button>
                    </div>

                    <!-- Password Authentication -->
                    <div class="flex items-center justify-between p-4 bg-gray-900/50 rounded-lg">
                        <div>
                            <h4 class="text-white font-medium">Password Authentication</h4>
                            <p class="text-gray-400 text-sm">Allow login with password</p>
                        </div>
                        <button wire:click="togglePasswordAuth"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors <?php echo e($passwordAuthEnabled ? 'bg-green-600' : 'bg-gray-600'); ?>">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform <?php echo e($passwordAuthEnabled ? 'translate-x-6' : 'translate-x-1'); ?>"></span>
                        </button>
                    </div>

                    <!-- Public Key Authentication -->
                    <div class="flex items-center justify-between p-4 bg-gray-900/50 rounded-lg">
                        <div>
                            <h4 class="text-white font-medium">Public Key Authentication</h4>
                            <p class="text-gray-400 text-sm">Allow login with SSH keys</p>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo e($pubkeyAuthEnabled ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'); ?>">
                            <?php echo e($pubkeyAuthEnabled ? 'Enabled' : 'Disabled'); ?>

                        </span>
                    </div>

                    <!-- Max Auth Tries -->
                    <div class="flex items-center justify-between p-4 bg-gray-900/50 rounded-lg">
                        <div>
                            <h4 class="text-white font-medium">Max Auth Tries</h4>
                            <p class="text-gray-400 text-sm">Maximum authentication attempts</p>
                        </div>
                        <span class="text-white font-medium"><?php echo e($maxAuthTries); ?></span>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-gray-700">
                    <button wire:click="restartSSH"
                        wire:confirm="This will restart the SSH service. Make sure you don't lock yourself out! Continue?"
                        class="w-full px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition-colors flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Restart SSH Service
                    </button>
                </div>
            </div>

            <!-- Security Recommendations -->
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6">
                <h3 class="text-lg font-semibold text-white mb-6">Security Recommendations</h3>

                <div class="space-y-4">
                    <!-- Port Check -->
                    <div class="flex items-start gap-3 p-4 rounded-lg <?php echo e($port !== 22 ? 'bg-green-900/20 border border-green-700/50' : 'bg-yellow-900/20 border border-yellow-700/50'); ?>">
                        <svg class="w-6 h-6 <?php echo e($port !== 22 ? 'text-green-400' : 'text-yellow-400'); ?> flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?php if($port !== 22): ?>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            <?php else: ?>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            <?php endif; ?>
                        </svg>
                        <div>
                            <h4 class="text-white font-medium"><?php echo e($port !== 22 ? 'Non-standard port in use' : 'Using default SSH port'); ?></h4>
                            <p class="text-gray-400 text-sm"><?php echo e($port !== 22 ? 'Good! Using a non-standard port reduces automated attacks.' : 'Consider changing from port 22 to reduce automated attacks.'); ?></p>
                        </div>
                    </div>

                    <!-- Root Login Check -->
                    <div class="flex items-start gap-3 p-4 rounded-lg <?php echo e(!$rootLoginEnabled ? 'bg-green-900/20 border border-green-700/50' : 'bg-red-900/20 border border-red-700/50'); ?>">
                        <svg class="w-6 h-6 <?php echo e(!$rootLoginEnabled ? 'text-green-400' : 'text-red-400'); ?> flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?php if(!$rootLoginEnabled): ?>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            <?php else: ?>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            <?php endif; ?>
                        </svg>
                        <div>
                            <h4 class="text-white font-medium"><?php echo e(!$rootLoginEnabled ? 'Root login disabled' : 'Root login enabled'); ?></h4>
                            <p class="text-gray-400 text-sm"><?php echo e(!$rootLoginEnabled ? 'Good! Root login is disabled for security.' : 'Disable root login and use a regular user with sudo.'); ?></p>
                        </div>
                    </div>

                    <!-- Password Auth Check -->
                    <div class="flex items-start gap-3 p-4 rounded-lg <?php echo e(!$passwordAuthEnabled ? 'bg-green-900/20 border border-green-700/50' : 'bg-yellow-900/20 border border-yellow-700/50'); ?>">
                        <svg class="w-6 h-6 <?php echo e(!$passwordAuthEnabled ? 'text-green-400' : 'text-yellow-400'); ?> flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?php if(!$passwordAuthEnabled): ?>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            <?php else: ?>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            <?php endif; ?>
                        </svg>
                        <div>
                            <h4 class="text-white font-medium"><?php echo e(!$passwordAuthEnabled ? 'Key-only authentication' : 'Password authentication enabled'); ?></h4>
                            <p class="text-gray-400 text-sm"><?php echo e(!$passwordAuthEnabled ? 'Good! Only SSH keys are allowed for login.' : 'Consider using SSH keys only for better security.'); ?></p>
                        </div>
                    </div>

                    <!-- Max Auth Tries Check -->
                    <div class="flex items-start gap-3 p-4 rounded-lg <?php echo e($maxAuthTries <= 3 ? 'bg-green-900/20 border border-green-700/50' : 'bg-yellow-900/20 border border-yellow-700/50'); ?>">
                        <svg class="w-6 h-6 <?php echo e($maxAuthTries <= 3 ? 'text-green-400' : 'text-yellow-400'); ?> flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?php if($maxAuthTries <= 3): ?>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            <?php else: ?>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            <?php endif; ?>
                        </svg>
                        <div>
                            <h4 class="text-white font-medium"><?php echo e($maxAuthTries <= 3 ? 'Low auth tries limit' : 'High auth tries limit'); ?></h4>
                            <p class="text-gray-400 text-sm"><?php echo e($maxAuthTries <= 3 ? 'Good! Limited attempts reduce brute force risk.' : 'Consider lowering MaxAuthTries to 3 or less.'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Harden Confirm Modal -->
    <?php if($showHardenConfirm): ?>
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/70 transition-opacity" wire:click="$set('showHardenConfirm', false)"></div>

                <div class="relative w-full max-w-md bg-gray-800 rounded-2xl shadow-xl border border-gray-700 p-6">
                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-500/20 mb-4">
                            <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-2">Apply SSH Hardening?</h3>
                        <p class="text-gray-400 mb-4 text-sm">
                            This will:
                        </p>
                        <ul class="text-left text-gray-300 text-sm mb-6 space-y-1">
                            <li>&bull; Disable root login</li>
                            <li>&bull; Disable password authentication</li>
                            <li>&bull; Set MaxAuthTries to 3</li>
                            <li>&bull; Disable X11 forwarding</li>
                        </ul>
                        <p class="text-yellow-400 text-sm mb-6">
                            <strong>Warning:</strong> Make sure you have SSH key access before applying!
                        </p>
                        <div class="flex justify-center gap-3">
                            <button wire:click="$set('showHardenConfirm', false)"
                                class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors">
                                Cancel
                            </button>
                            <button wire:click="hardenSSH"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                                Apply Hardening
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH /home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/livewire/servers/security/s-s-h-security-manager.blade.php ENDPATH**/ ?>