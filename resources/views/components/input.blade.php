{{--
    Reusable Input Component

    Usage:
    <x-input name="email" type="email" label="Email Address" />
    <x-input name="password" type="password" label="Password" required />
    <x-input name="search" placeholder="Search..." icon="magnifying-glass" />
    <x-input name="amount" prefix="$" suffix="USD" />
    <x-input name="bio" type="textarea" rows="4" label="Biography" />
    <x-input name="country" type="select" :options="$countries" label="Country" />

    Props:
    - name: Input name attribute (required)
    - type: text|email|password|number|tel|url|search|textarea|select (default: text)
    - label: Label text (optional)
    - placeholder: Placeholder text (optional)
    - value: Current value (optional)
    - error: Error message to display (optional, auto-detected from $errors)
    - hint: Help text below input (optional)
    - required: Mark as required (default: false)
    - disabled: Disable the input (default: false)
    - readonly: Make readonly (default: false)
    - icon: Heroicon name for left icon (optional)
    - iconRight: Heroicon name for right icon (optional)
    - prefix: Text prefix inside input (optional)
    - suffix: Text suffix inside input (optional)
    - size: sm|md|lg (default: md)
    - options: Array for select type (optional)
    - rows: Rows for textarea (default: 3)
--}}

@props([
    'name',
    'type' => 'text',
    'label' => null,
    'placeholder' => null,
    'value' => null,
    'error' => null,
    'hint' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'icon' => null,
    'iconRight' => null,
    'prefix' => null,
    'suffix' => null,
    'size' => 'md',
    'options' => [],
    'rows' => 3,
])

@php
    $inputId = $name . '-' . uniqid();

    // Auto-detect error from Laravel's $errors bag
    $errorMessage = $error ?? ($errors->has($name) ? $errors->first($name) : null);
    $hasError = !empty($errorMessage);

    // Size classes
    $sizeClasses = match($size) {
        'sm' => 'px-3 py-1.5 text-sm',
        'lg' => 'px-4 py-3 text-base',
        default => 'px-3.5 py-2 text-sm', // md
    };

    $iconSizeClasses = match($size) {
        'sm' => 'w-4 h-4',
        'lg' => 'w-5 h-5',
        default => 'w-4 h-4', // md
    };

    // Padding adjustment for icons/prefix/suffix
    $paddingLeft = ($icon || $prefix) ? 'ps-10' : '';
    $paddingRight = ($iconRight || $suffix) ? 'pe-10' : '';

    // Base input classes
    $baseClasses = 'block w-full rounded-lg border bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-0';

    // Border classes based on error state
    $borderClasses = $hasError
        ? 'border-red-500 dark:border-red-400 focus:border-red-500 focus:ring-red-500/30'
        : 'border-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-blue-500/30';

    // Disabled/readonly classes
    $stateClasses = ($disabled || $readonly)
        ? 'bg-gray-50 dark:bg-gray-800 cursor-not-allowed opacity-60'
        : '';

    // Combined classes
    $inputClasses = trim("{$baseClasses} {$sizeClasses} {$borderClasses} {$stateClasses} {$paddingLeft} {$paddingRight}");
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'w-full']) }}>
    {{-- Label --}}
    @if($label)
        <label for="{{ $inputId }}" class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
            @if($required)
                <span class="text-red-500 dark:text-red-400" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    {{-- Input wrapper for icons/prefix/suffix --}}
    <div class="relative">
        {{-- Left icon --}}
        @if($icon)
            <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-gray-400 dark:text-gray-500">
                <x-dynamic-component :component="'heroicon-o-' . $icon" :class="$iconSizeClasses" />
            </div>
        @endif

        {{-- Prefix --}}
        @if($prefix)
            <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                <span class="text-gray-500 dark:text-gray-400 {{ $size === 'sm' ? 'text-sm' : 'text-base' }}">{{ $prefix }}</span>
            </div>
        @endif

        {{-- Input element --}}
        @if($type === 'textarea')
            <textarea
                id="{{ $inputId }}"
                name="{{ $name }}"
                rows="{{ $rows }}"
                placeholder="{{ $placeholder }}"
                @if($required) required aria-required="true" @endif
                @if($disabled) disabled aria-disabled="true" @endif
                @if($readonly) readonly @endif
                @if($hasError) aria-invalid="true" aria-describedby="{{ $inputId }}-error" @endif
                @if($hint && !$hasError) aria-describedby="{{ $inputId }}-hint" @endif
                {{ $attributes->except(['class'])->merge(['class' => $inputClasses . ' resize-none']) }}
            >{{ $value ?? old($name) }}</textarea>
        @elseif($type === 'select')
            <select
                id="{{ $inputId }}"
                name="{{ $name }}"
                @if($required) required aria-required="true" @endif
                @if($disabled) disabled aria-disabled="true" @endif
                @if($hasError) aria-invalid="true" aria-describedby="{{ $inputId }}-error" @endif
                @if($hint && !$hasError) aria-describedby="{{ $inputId }}-hint" @endif
                {{ $attributes->except(['class'])->merge(['class' => $inputClasses]) }}
            >
                @if($placeholder)
                    <option value="">{{ $placeholder }}</option>
                @endif
                @foreach($options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected(($value ?? old($name)) == $optionValue)>
                        {{ $optionLabel }}
                    </option>
                @endforeach
            </select>
        @else
            <input
                id="{{ $inputId }}"
                type="{{ $type }}"
                name="{{ $name }}"
                value="{{ $value ?? old($name) }}"
                placeholder="{{ $placeholder }}"
                @if($required) required aria-required="true" @endif
                @if($disabled) disabled aria-disabled="true" @endif
                @if($readonly) readonly @endif
                @if($hasError) aria-invalid="true" aria-describedby="{{ $inputId }}-error" @endif
                @if($hint && !$hasError) aria-describedby="{{ $inputId }}-hint" @endif
                {{ $attributes->except(['class'])->merge(['class' => $inputClasses]) }}
            />
        @endif

        {{-- Right icon --}}
        @if($iconRight)
            <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 dark:text-gray-500">
                <x-dynamic-component :component="'heroicon-o-' . $iconRight" :class="$iconSizeClasses" />
            </div>
        @endif

        {{-- Suffix --}}
        @if($suffix)
            <div class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3">
                <span class="text-gray-500 dark:text-gray-400 {{ $size === 'sm' ? 'text-sm' : 'text-base' }}">{{ $suffix }}</span>
            </div>
        @endif
    </div>

    {{-- Error message --}}
    @if($hasError)
        <p id="{{ $inputId }}-error" class="mt-1.5 text-sm text-red-600 dark:text-red-400 flex items-center gap-1" role="alert">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            {{ $errorMessage }}
        </p>
    @elseif($hint)
        <p id="{{ $inputId }}-hint" class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">
            {{ $hint }}
        </p>
    @endif
</div>
