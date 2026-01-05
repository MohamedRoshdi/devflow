@props([
    'type' => 'info',
    'title' => null,
    'dismissible' => false,
    'icon' => true,
])

@php
    $typeConfig = match($type) {
        'success' => [
            'bg' => 'bg-emerald-50 dark:bg-emerald-900/20',
            'border' => 'border-emerald-200 dark:border-emerald-800/50',
            'icon_bg' => 'text-emerald-400 dark:text-emerald-500',
            'title' => 'text-emerald-800 dark:text-emerald-200',
            'text' => 'text-emerald-700 dark:text-emerald-300',
            'icon_path' => 'M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z',
            'dismiss_hover' => 'hover:bg-emerald-100 dark:hover:bg-emerald-800/50',
        ],
        'warning' => [
            'bg' => 'bg-amber-50 dark:bg-amber-900/20',
            'border' => 'border-amber-200 dark:border-amber-800/50',
            'icon_bg' => 'text-amber-400 dark:text-amber-500',
            'title' => 'text-amber-800 dark:text-amber-200',
            'text' => 'text-amber-700 dark:text-amber-300',
            'icon_path' => 'M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z',
            'dismiss_hover' => 'hover:bg-amber-100 dark:hover:bg-amber-800/50',
        ],
        'error', 'danger' => [
            'bg' => 'bg-red-50 dark:bg-red-900/20',
            'border' => 'border-red-200 dark:border-red-800/50',
            'icon_bg' => 'text-red-400 dark:text-red-500',
            'title' => 'text-red-800 dark:text-red-200',
            'text' => 'text-red-700 dark:text-red-300',
            'icon_path' => 'M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z',
            'dismiss_hover' => 'hover:bg-red-100 dark:hover:bg-red-800/50',
        ],
        default => [ // info
            'bg' => 'bg-blue-50 dark:bg-blue-900/20',
            'border' => 'border-blue-200 dark:border-blue-800/50',
            'icon_bg' => 'text-blue-400 dark:text-blue-500',
            'title' => 'text-blue-800 dark:text-blue-200',
            'text' => 'text-blue-700 dark:text-blue-300',
            'icon_path' => 'M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z',
            'dismiss_hover' => 'hover:bg-blue-100 dark:hover:bg-blue-800/50',
        ],
    };

    $role = in_array($type, ['error', 'danger', 'warning']) ? 'alert' : 'status';
    $ariaLive = in_array($type, ['error', 'danger']) ? 'assertive' : 'polite';
@endphp

<div
    x-data="{ show: true }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform -translate-y-2"
    x-transition:enter-end="opacity-100 transform translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    {{ $attributes->merge([
        'class' => "rounded-lg border p-4 {$typeConfig['bg']} {$typeConfig['border']}",
        'role' => $role,
        'aria-live' => $ariaLive,
    ]) }}
>
    <div class="flex">
        @if($icon)
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 {{ $typeConfig['icon_bg'] }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="{{ $typeConfig['icon_path'] }}" clip-rule="evenodd" />
                </svg>
            </div>
        @endif

        <div class="@if($icon) ms-3 @endif flex-1">
            @if($title)
                <h3 class="text-sm font-medium {{ $typeConfig['title'] }}">
                    {{ $title }}
                </h3>
                <div class="mt-2 text-sm {{ $typeConfig['text'] }}">
                    {{ $slot }}
                </div>
            @else
                <p class="text-sm {{ $typeConfig['text'] }}">
                    {{ $slot }}
                </p>
            @endif
        </div>

        @if($dismissible)
            <div class="ms-auto ps-3">
                <div class="-mx-1.5 -my-1.5">
                    <button
                        type="button"
                        @click="show = false"
                        class="inline-flex rounded-md p-1.5 {{ $typeConfig['icon_bg'] }} {{ $typeConfig['dismiss_hover'] }} focus:outline-none focus:ring-2 focus:ring-offset-2 {{ str_replace('text-', 'focus:ring-', $typeConfig['icon_bg']) }}"
                        aria-label="Dismiss"
                    >
                        <span class="sr-only">Dismiss</span>
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
