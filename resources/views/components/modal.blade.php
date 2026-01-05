@props([
    'show' => false,
    'maxWidth' => '2xl',
    'closeable' => true,
    'closeAction' => null,
    'title' => '',
])

@php
$maxWidthClass = [
    'sm' => 'max-w-sm',
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
    'xl' => 'max-w-xl',
    '2xl' => 'max-w-2xl',
    '3xl' => 'max-w-3xl',
    '4xl' => 'max-w-4xl',
    '5xl' => 'max-w-5xl',
    '6xl' => 'max-w-6xl',
    'full' => 'max-w-full',
][$maxWidth];

$closeHandler = $closeAction ?? ($closeable ? "\$wire.set('" . ($attributes->get('wire:model') ?? 'showModal') . "', false)" : '');
@endphp

<div
    x-data="{ show: @entangle($attributes->wire('model')) }"
    x-show="show"
    x-on:keydown.escape.window="@if($closeable) show = false; {{ $closeHandler }} @endif"
    x-transition:enter="ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
    role="dialog"
    aria-modal="true"
    @if($title) aria-labelledby="modal-title" @endif
>
    <div class="flex min-h-screen items-center justify-center p-4">
        {{-- Backdrop --}}
        <div
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/50 backdrop-blur-sm"
            @if($closeable) @click="{{ $closeHandler }}" @endif
            aria-hidden="true"
        ></div>

        {{-- Modal Content --}}
        <div
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative w-full {{ $maxWidthClass }} transform overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-slate-800 border border-slate-200 dark:border-slate-700"
            @click.stop
        >
            {{-- Header --}}
            @if($title || $closeable)
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 px-6 py-4">
                    @if($title)
                        <h2 id="modal-title" class="text-xl font-bold text-slate-900 dark:text-white">
                            {{ $title }}
                        </h2>
                    @else
                        <div></div>
                    @endif

                    @if($closeable)
                        <button
                            type="button"
                            @click="{{ $closeHandler }}"
                            class="text-slate-400 hover:text-slate-500 dark:hover:text-slate-300 transition-colors"
                            aria-label="Close modal"
                        >
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    @endif
                </div>
            @endif

            {{-- Body --}}
            <div class="px-6 py-4">
                {{ $slot }}
            </div>

            {{-- Footer --}}
            @if(isset($footer))
                <div class="flex items-center justify-end gap-3 border-t border-slate-200 dark:border-slate-700 px-6 py-4 bg-slate-50 dark:bg-slate-900/50">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
