<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-red-800 via-orange-900 to-amber-800 shadow-2xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex items-center gap-4">
                    <a href="<?php echo e(route('servers.show', $server)); ?>" class="p-2 bg-white/10 rounded-lg hover:bg-white/20 transition-colors">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <div class="flex items-center gap-3">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            <div>
                                <h1 class="text-2xl font-bold text-white">Security Dashboard</h1>
                                <p class="text-white/80"><?php echo e($server->name); ?> (<?php echo e($server->ip_address); ?>)</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button wire:click="refreshStatus" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" wire:loading.class="animate-spin" wire:target="refreshStatus" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Refresh
                    </button>
                    <button wire:click="runSecurityScan" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-white text-red-800 font-medium rounded-lg hover:bg-gray-100 transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" wire:loading.class="animate-spin" wire:target="runSecurityScan" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        <span wire:loading.remove wire:target="runSecurityScan">Run Security Scan</span>
                        <span wire:loading wire:target="runSecurityScan">Scanning...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Message -->
    <?php if($flashMessage): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="rounded-lg p-4 <?php echo e($flashType === 'success' ? 'bg-green-900/50 text-green-200 border border-green-700' : 'bg-red-900/50 text-red-200 border border-red-700'); ?>">
                <div class="flex items-center gap-2">
                    <?php if($flashType === 'success'): ?>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    <?php else: ?>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    <?php endif; ?>
                    <?php echo e($flashMessage); ?>

                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Security Score Card -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Main Score -->
            <div class="lg:col-span-1 bg-gray-800/50 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Security Score</h3>
                <div class="flex flex-col items-center">
                    <div class="relative w-40 h-40">
                        <svg class="w-40 h-40 transform -rotate-90" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="45" fill="none" stroke="#374151" stroke-width="8"/>
                            <circle cx="50" cy="50" r="45" fill="none"
                                stroke="<?php echo e($server->security_score >= 81 ? '#10b981' : ($server->security_score >= 61 ? '#eab308' : ($server->security_score >= 41 ? '#f97316' : '#ef4444'))); ?>"
                                stroke-width="8" stroke-linecap="round"
                                stroke-dasharray="<?php echo e(($server->security_score ?? 0) * 2.827); ?> 282.7"
                                class="transition-all duration-1000"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-4xl font-bold text-white"><?php echo e($server->security_score ?? '--'); ?></span>
                            <span class="text-gray-400 text-sm">/100</span>
                        </div>
                    </div>
                    <div class="mt-4 text-center">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                            <?php echo e($server->security_risk_level === 'secure' ? 'bg-emerald-500/20 text-emerald-400' : ''); ?>

                            <?php echo e($server->security_risk_level === 'low' ? 'bg-green-500/20 text-green-400' : ''); ?>

                            <?php echo e($server->security_risk_level === 'medium' ? 'bg-yellow-500/20 text-yellow-400' : ''); ?>

                            <?php echo e($server->security_risk_level === 'high' ? 'bg-orange-500/20 text-orange-400' : ''); ?>

                            <?php echo e($server->security_risk_level === 'critical' ? 'bg-red-500/20 text-red-400' : ''); ?>

                            <?php echo e($server->security_risk_level === 'unknown' ? 'bg-gray-500/20 text-gray-400' : ''); ?>">
                            <?php echo e(ucfirst($server->security_risk_level)); ?> Risk
                        </span>
                    </div>
                    <?php if($server->last_security_scan_at): ?>
                        <p class="mt-2 text-xs text-gray-500">
                            Last scan: <?php echo e($server->last_security_scan_at->diffForHumans()); ?>

                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="lg:col-span-2 grid grid-cols-2 sm:grid-cols-4 gap-4">
                <!-- UFW Status -->
                <a href="<?php echo e(route('servers.security.firewall', $server)); ?>"
                   class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-4 hover:border-orange-500/50 transition-colors">
                    <div class="flex items-center justify-between mb-2">
                        <svg class="w-8 h-8 <?php echo e($server->ufw_enabled ? 'text-green-400' : 'text-red-400'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
                        </svg>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?php echo e($server->ufw_enabled ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'); ?>">
                            <?php echo e($server->ufw_enabled ? 'Active' : 'Inactive'); ?>

                        </span>
                    </div>
                    <h4 class="text-white font-medium">Firewall</h4>
                    <p class="text-gray-400 text-sm">UFW <?php echo e($server->ufw_installed ? 'installed' : 'not installed'); ?></p>
                </a>

                <!-- Fail2ban Status -->
                <a href="<?php echo e(route('servers.security.fail2ban', $server)); ?>"
                   class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-4 hover:border-orange-500/50 transition-colors">
                    <div class="flex items-center justify-between mb-2">
                        <svg class="w-8 h-8 <?php echo e($server->fail2ban_enabled ? 'text-green-400' : 'text-red-400'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?php echo e($server->fail2ban_enabled ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'); ?>">
                            <?php echo e($server->fail2ban_enabled ? 'Active' : 'Inactive'); ?>

                        </span>
                    </div>
                    <h4 class="text-white font-medium">Fail2ban</h4>
                    <p class="text-gray-400 text-sm"><?php echo e($server->fail2ban_installed ? 'Installed' : 'Not installed'); ?></p>
                </a>

                <!-- SSH Status -->
                <a href="<?php echo e(route('servers.security.ssh', $server)); ?>"
                   class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-4 hover:border-orange-500/50 transition-colors">
                    <div class="flex items-center justify-between mb-2">
                        <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h4 class="text-white font-medium">SSH Config</h4>
                    <p class="text-gray-400 text-sm">Port <?php echo e($server->port); ?></p>
                </a>

                <!-- Open Ports -->
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                        </svg>
                    </div>
                    <h4 class="text-white font-medium">Open Ports</h4>
                    <p class="text-gray-400 text-sm"><?php echo e($securityOverview['open_ports']['count'] ?? '--'); ?> ports</p>
                </div>
            </div>
        </div>

        <!-- Quick Navigation -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <a href="<?php echo e(route('servers.security.firewall', $server)); ?>"
               class="flex items-center gap-4 bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-4 hover:border-orange-500/50 hover:bg-gray-800/70 transition-all">
                <div class="p-3 bg-orange-500/20 rounded-lg">
                    <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-white font-medium">Firewall Manager</h4>
                    <p class="text-gray-400 text-sm">Manage UFW rules</p>
                </div>
                <svg class="w-5 h-5 text-gray-500 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>

            <a href="<?php echo e(route('servers.security.fail2ban', $server)); ?>"
               class="flex items-center gap-4 bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-4 hover:border-red-500/50 hover:bg-gray-800/70 transition-all">
                <div class="p-3 bg-red-500/20 rounded-lg">
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-white font-medium">Fail2ban Manager</h4>
                    <p class="text-gray-400 text-sm">View bans & jails</p>
                </div>
                <svg class="w-5 h-5 text-gray-500 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>

            <a href="<?php echo e(route('servers.security.ssh', $server)); ?>"
               class="flex items-center gap-4 bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-4 hover:border-blue-500/50 hover:bg-gray-800/70 transition-all">
                <div class="p-3 bg-blue-500/20 rounded-lg">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-white font-medium">SSH Security</h4>
                    <p class="text-gray-400 text-sm">Harden SSH config</p>
                </div>
                <svg class="w-5 h-5 text-gray-500 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>

            <a href="<?php echo e(route('servers.security.scan', $server)); ?>"
               class="flex items-center gap-4 bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-4 hover:border-purple-500/50 hover:bg-gray-800/70 transition-all">
                <div class="p-3 bg-purple-500/20 rounded-lg">
                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-white font-medium">Scan History</h4>
                    <p class="text-gray-400 text-sm">View scan results</p>
                </div>
                <svg class="w-5 h-5 text-gray-500 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <!-- Recent Security Events -->
        <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Recent Security Events</h3>
            <?php if($this->recentEvents->isEmpty()): ?>
                <div class="text-center py-8">
                    <svg class="w-12 h-12 text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <p class="text-gray-400">No security events recorded yet</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php $__currentLoopData = $this->recentEvents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="flex items-start gap-4 p-3 rounded-lg bg-gray-900/50">
                            <div class="p-2 rounded-lg
                                <?php echo e($event->event_type_color === 'green' ? 'bg-green-500/20' : ''); ?>

                                <?php echo e($event->event_type_color === 'red' ? 'bg-red-500/20' : ''); ?>

                                <?php echo e($event->event_type_color === 'orange' ? 'bg-orange-500/20' : ''); ?>

                                <?php echo e($event->event_type_color === 'yellow' ? 'bg-yellow-500/20' : ''); ?>

                                <?php echo e($event->event_type_color === 'blue' ? 'bg-blue-500/20' : ''); ?>

                                <?php echo e($event->event_type_color === 'purple' ? 'bg-purple-500/20' : ''); ?>

                                <?php echo e($event->event_type_color === 'gray' ? 'bg-gray-500/20' : ''); ?>">
                                <svg class="w-5 h-5
                                    <?php echo e($event->event_type_color === 'green' ? 'text-green-400' : ''); ?>

                                    <?php echo e($event->event_type_color === 'red' ? 'text-red-400' : ''); ?>

                                    <?php echo e($event->event_type_color === 'orange' ? 'text-orange-400' : ''); ?>

                                    <?php echo e($event->event_type_color === 'yellow' ? 'text-yellow-400' : ''); ?>

                                    <?php echo e($event->event_type_color === 'blue' ? 'text-blue-400' : ''); ?>

                                    <?php echo e($event->event_type_color === 'purple' ? 'text-purple-400' : ''); ?>

                                    <?php echo e($event->event_type_color === 'gray' ? 'text-gray-400' : ''); ?>"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="text-white font-medium truncate"><?php echo e($event->getEventTypeLabel()); ?></h4>
                                    <span class="text-xs text-gray-500 whitespace-nowrap"><?php echo e($event->created_at->diffForHumans()); ?></span>
                                </div>
                                <?php if($event->details): ?>
                                    <p class="text-gray-400 text-sm truncate"><?php echo e($event->details); ?></p>
                                <?php endif; ?>
                                <?php if($event->source_ip): ?>
                                    <p class="text-gray-500 text-xs">IP: <?php echo e($event->source_ip); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php /**PATH /home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/livewire/servers/security/server-security-dashboard.blade.php ENDPATH**/ ?>