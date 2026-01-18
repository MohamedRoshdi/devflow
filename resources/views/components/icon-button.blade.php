{{--
    Icon Button Component

    Usage:
    <x-icon-button icon="trash" />
    <x-icon-button icon="pencil" variant="primary" />
    <x-icon-button icon="plus" size="lg" label="Add item" />

    Props:
    - icon: Name of heroicon (required)
    - variant: primary|secondary|success|danger|warning|info|ghost (default: ghost)
    - size: sm|md|lg (default: md)
    - label: Accessible label for screen readers (optional, uses icon name if not provided)
    - loading: Show loading spinner (default: false)
    - disabled: Disable the button (default: false)
--}}

@props([
    'icon',
    'variant' => 'ghost',
    'size' => 'md',
    'label' => null,
    'loading' => false,
    'disabled' => false,
])

@php
    // Size classes (square buttons)
    $sizeClasses = match($size) {
        'sm' => 'w-8 h-8',
        'lg' => 'w-12 h-12',
        default => 'w-10 h-10', // md
    };

    $iconSize = match($size) {
        'sm' => 'w-4 h-4',
        'lg' => 'w-6 h-6',
        default => 'w-5 h-5', // md
    };

    // Variant classes with dark mode
    $variantClasses = match($variant) {
        'primary' => 'bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white focus:ring-blue-500',
        'secondary' => 'bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-900 dark:text-slate-100 focus:ring-slate-500',
        'success' => 'bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600 text-white focus:ring-emerald-500',
        'danger' => 'bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white focus:ring-red-500',
        'warning' => 'bg-amber-500 hover:bg-amber-600 dark:bg-amber-400 dark:hover:bg-amber-500 text-white dark:text-slate-900 focus:ring-amber-500',
        'info' => 'bg-cyan-600 hover:bg-cyan-700 dark:bg-cyan-500 dark:hover:bg-cyan-600 text-white focus:ring-cyan-500',
        'ghost' => 'bg-transparent hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 focus:ring-slate-500',
        default => 'bg-transparent hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 focus:ring-slate-500',
    };

    // Base classes
    $baseClasses = 'inline-flex items-center justify-center rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-900';

    // Disabled classes
    $disabledClasses = ($disabled || $loading) ? 'opacity-60 cursor-not-allowed pointer-events-none' : '';

    // Accessible label
    $accessibleLabel = $label ?? ucfirst(str_replace('-', ' ', $icon));
@endphp

<button
    type="button"
    aria-label="{{ $accessibleLabel }}"
    title="{{ $accessibleLabel }}"
    {{ $attributes->merge([
        'class' => trim("{$baseClasses} {$sizeClasses} {$variantClasses} {$disabledClasses}"),
        'disabled' => $disabled || $loading,
    ]) }}
>
    @if($loading)
        <svg class="{{ $iconSize }} animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    @else
        <x-dynamic-component :component="'heroicon-o-' . $icon" :class="$iconSize" />
    @endif

    <span class="sr-only">{{ $accessibleLabel }}</span>
</button>
