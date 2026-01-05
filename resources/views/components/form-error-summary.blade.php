@props([
    'title' => 'There were errors with your submission',
])

@if ($errors->any())
    <div
        {{ $attributes->merge([
            'class' => 'rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/50 p-4 mb-6',
            'role' => 'alert',
            'aria-labelledby' => 'error-summary-title',
        ]) }}
    >
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400 dark:text-red-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ms-3">
                <h3 id="error-summary-title" class="text-sm font-medium text-red-800 dark:text-red-200">
                    {{ $title }}
                </h3>
                <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                    <ul class="list-disc space-y-1 ps-5" role="list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endif
