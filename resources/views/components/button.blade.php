{{--
    Reusable Button Component

    Usage:
    <x-button>Default Button</x-button>
    <x-button variant="primary">Primary</x-button>
    <x-button variant="danger" size="sm">Delete</x-button>
    <x-button variant="secondary" icon="plus">Add Item</x-button>
    <x-button variant="primary" :loading="$isLoading">Submit</x-button>

    Props:
    - variant: primary|secondary|success|danger|warning|info|ghost|outline (default: primary)
    - size: xs|sm|md|lg|xl (default: md)
    - icon: Name of heroicon to show (optional)
    - iconPosition: left|right (default: left)
    - loading: Show loading spinner (default: false)
    - disabled: Disable the button (default: false)
    - fullWidth: Make button full width (default: false)
    - type: button|submit|reset (default: button)
--}}

@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'iconPosition' => 'left',
    'loading' => false,
    'disabled' => false,
    'fullWidth' => false,
    'type' => 'button',
])

@php
    // Size classes
    $sizeClasses = match($size) {
        'xs' => 'px-2 py-1 text-xs gap-1',
        'sm' => 'px-3 py-1.5 text-sm gap-1.5',
        'lg' => 'px-5 py-3 text-base gap-2.5',
        'xl' => 'px-6 py-3.5 text-lg gap-3',
        default => 'px-4 py-2 text-sm gap-2', // md
    };

    $iconSize = match($size) {
        'xs' => 'w-3 h-3',
        'sm' => 'w-4 h-4',
        'lg' => 'w-5 h-5',
        'xl' => 'w-6 h-6',
        default => 'w-4 h-4', // md
    };

    // Variant classes with proper dark mode support
    $variantClasses = match($variant) {
        'primary' => 'bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white shadow-sm shadow-blue-600/30 dark:shadow-blue-500/30 focus:ring-blue-500',
        'secondary' => 'bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-900 dark:text-slate-100 focus:ring-slate-500',
        'success' => 'bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600 text-white shadow-sm shadow-emerald-600/30 dark:shadow-emerald-500/30 focus:ring-emerald-500',
        'danger' => 'bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white shadow-sm shadow-red-600/30 dark:shadow-red-500/30 focus:ring-red-500',
        'warning' => 'bg-amber-500 hover:bg-amber-600 dark:bg-amber-400 dark:hover:bg-amber-500 text-white dark:text-slate-900 shadow-sm shadow-amber-500/30 focus:ring-amber-500',
        'info' => 'bg-cyan-600 hover:bg-cyan-700 dark:bg-cyan-500 dark:hover:bg-cyan-600 text-white shadow-sm shadow-cyan-600/30 dark:shadow-cyan-500/30 focus:ring-cyan-500',
        'ghost' => 'bg-transparent hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 focus:ring-slate-500',
        'outline' => 'bg-transparent border border-slate-300 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 focus:ring-slate-500',
        default => 'bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white shadow-sm shadow-blue-600/30 dark:shadow-blue-500/30 focus:ring-blue-500',
    };

    // Base classes
    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-slate-900';

    // Disabled classes
    $disabledClasses = ($disabled || $loading) ? 'opacity-60 cursor-not-allowed pointer-events-none' : '';

    // Full width
    $widthClasses = $fullWidth ? 'w-full' : '';
@endphp

<button
    type="{{ $type }}"
    @if($loading) aria-busy="true" @endif
    @if($disabled) aria-disabled="true" @endif
    {{ $attributes->merge([
        'class' => trim("{$baseClasses} {$sizeClasses} {$variantClasses} {$disabledClasses} {$widthClasses}"),
        'disabled' => $disabled || $loading,
    ]) }}
>
    {{-- Loading spinner --}}
    @if($loading)
        <svg class="{{ $iconSize }} animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="sr-only">Loading...</span>
    @elseif($icon && $iconPosition === 'left')
        <x-dynamic-component :component="'heroicon-o-' . $icon" :class="$iconSize" aria-hidden="true" />
    @endif

    {{-- Button text --}}
    <span @if($loading) aria-hidden="true" @endif>{{ $slot }}</span>

    {{-- Right icon --}}
    @if($icon && $iconPosition === 'right' && !$loading)
        <x-dynamic-component :component="'heroicon-o-' . $icon" :class="$iconSize" aria-hidden="true" />
    @endif
</button>
