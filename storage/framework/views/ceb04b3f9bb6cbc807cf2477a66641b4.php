<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'status' => 'unknown',
    'type' => 'status', // 'status', 'deployment', 'plan', 'server'
    'size' => 'md', // 'sm', 'md', 'lg'
    'animated' => true,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'status' => 'unknown',
    'type' => 'status', // 'status', 'deployment', 'plan', 'server'
    'size' => 'md', // 'sm', 'md', 'lg'
    'animated' => true,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $sizeClasses = match($size) {
        'sm' => 'px-2 py-0.5 text-xs gap-1',
        'lg' => 'px-4 py-2 text-sm gap-2',
        default => 'px-2.5 py-1 text-xs gap-1.5',
    };

    $iconSize = match($size) {
        'sm' => 'w-3 h-3',
        'lg' => 'w-5 h-5',
        default => 'w-3.5 h-3.5',
    };

    $dotSize = match($size) {
        'sm' => 'w-1 h-1',
        'lg' => 'w-2 h-2',
        default => 'w-1.5 h-1.5',
    };

    // Normalize status
    $normalizedStatus = strtolower($status);

    // Get colors based on status and type - VIBRANT COLORFUL BADGES
    $config = match(true) {
        // Success states - Vibrant Green
        in_array($normalizedStatus, ['success', 'running', 'active', 'online', 'healthy', 'completed']) => [
            'bg' => 'bg-gradient-to-r from-emerald-500 to-green-500 shadow-md shadow-emerald-500/30',
            'text' => 'text-white',
            'ring' => '',
            'dot' => 'bg-white',
            'icon' => 'check',
        ],
        // Warning/In-progress states - Vibrant Amber/Orange
        in_array($normalizedStatus, ['building', 'deploying', 'pending', 'processing', 'waiting']) => [
            'bg' => 'bg-gradient-to-r from-amber-500 to-orange-500 shadow-md shadow-amber-500/30',
            'text' => 'text-white',
            'ring' => '',
            'dot' => 'bg-white',
            'icon' => 'clock',
        ],
        // Error states - Vibrant Red
        in_array($normalizedStatus, ['failed', 'error', 'offline', 'unhealthy', 'crashed']) => [
            'bg' => 'bg-gradient-to-r from-red-500 to-rose-500 shadow-md shadow-red-500/30',
            'text' => 'text-white',
            'ring' => '',
            'dot' => 'bg-white',
            'icon' => 'x',
        ],
        // Stopped/Paused states - Vibrant Orange/Yellow
        in_array($normalizedStatus, ['stopped', 'paused', 'suspended', 'maintenance']) => [
            'bg' => 'bg-gradient-to-r from-orange-500 to-amber-500 shadow-md shadow-orange-500/30',
            'text' => 'text-white',
            'ring' => '',
            'dot' => 'bg-white',
            'icon' => 'pause',
        ],
        // Info/Blue states - Vibrant Blue
        in_array($normalizedStatus, ['info', 'queued', 'scheduled']) => [
            'bg' => 'bg-gradient-to-r from-blue-500 to-indigo-500 shadow-md shadow-blue-500/30',
            'text' => 'text-white',
            'ring' => '',
            'dot' => 'bg-white',
            'icon' => 'info',
        ],
        // Pro plan - Vibrant Blue/Indigo
        $type === 'plan' && $normalizedStatus === 'pro' => [
            'bg' => 'bg-gradient-to-r from-blue-500 to-indigo-500 shadow-md shadow-blue-500/30',
            'text' => 'text-white',
            'ring' => '',
            'dot' => null,
            'icon' => 'bolt',
        ],
        // Enterprise plan - Vibrant Purple
        $type === 'plan' && $normalizedStatus === 'enterprise' => [
            'bg' => 'bg-gradient-to-r from-purple-500 to-violet-500 shadow-md shadow-purple-500/30',
            'text' => 'text-white',
            'ring' => '',
            'dot' => null,
            'icon' => 'sparkles',
        ],
        // Default/Unknown - Gray
        default => [
            'bg' => 'bg-gradient-to-r from-gray-400 to-slate-500 shadow-md shadow-gray-500/30',
            'text' => 'text-white',
            'ring' => '',
            'dot' => 'bg-white/70',
            'icon' => null,
        ],
    };

    $shouldAnimate = $animated && in_array($normalizedStatus, ['running', 'building', 'deploying', 'pending', 'processing', 'active', 'online']);
?>

<span <?php echo e($attributes->merge(['class' => "inline-flex items-center font-semibold rounded-full leading-5 {$sizeClasses} {$config['bg']} {$config['text']} {$config['ring']}"])); ?>>
    <?php if($config['icon'] === 'check'): ?>
        <svg class="<?php echo e($iconSize); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
    <?php elseif($config['icon'] === 'x'): ?>
        <svg class="<?php echo e($iconSize); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
    <?php elseif($config['icon'] === 'clock'): ?>
        <svg class="<?php echo e($iconSize); ?> <?php echo e($shouldAnimate ? 'animate-pulse' : ''); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
    <?php elseif($config['icon'] === 'pause'): ?>
        <svg class="<?php echo e($iconSize); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
    <?php elseif($config['icon'] === 'info'): ?>
        <svg class="<?php echo e($iconSize); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
    <?php elseif($config['icon'] === 'bolt'): ?>
        <svg class="<?php echo e($iconSize); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
        </svg>
    <?php elseif($config['icon'] === 'sparkles'): ?>
        <svg class="<?php echo e($iconSize); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
        </svg>
    <?php elseif($config['dot']): ?>
        <span class="rounded-full <?php echo e($dotSize); ?> <?php echo e($config['dot']); ?> <?php echo e($shouldAnimate ? 'animate-pulse' : ''); ?>"></span>
    <?php endif; ?>

    <?php echo e($slot->isEmpty() ? ucfirst($status) : $slot); ?>

</span>
<?php /**PATH /home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/components/status-badge.blade.php ENDPATH**/ ?>