@props([
    'type' => 'default',
    'count' => 1,
    'class' => ''
])

<div class="{{ $class }}" role="status" aria-busy="true" aria-label="Loading content">
    <span class="sr-only">Loading...</span>
    @if($type === 'card')
        {{-- Card Skeleton --}}
        @for($i = 0; $i < $count; $i++)
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 animate-pulse" aria-hidden="true">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded w-1/3 shimmer"></div>
                        <div class="h-8 w-20 bg-gray-200 dark:bg-gray-700 rounded-full shimmer"></div>
                    </div>
                    <div class="space-y-2">
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-full shimmer"></div>
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-5/6 shimmer"></div>
                    </div>
                    <div class="flex gap-2">
                        <div class="h-9 w-24 bg-gray-200 dark:bg-gray-700 rounded-lg shimmer"></div>
                        <div class="h-9 w-24 bg-gray-200 dark:bg-gray-700 rounded-lg shimmer"></div>
                    </div>
                </div>
            </div>
        @endfor

    @elseif($type === 'stats')
        {{-- Stats Cards Skeleton --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" aria-hidden="true">
            @for($i = 0; $i < $count; $i++)
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 animate-pulse">
                    <div class="flex items-center justify-between">
                        <div class="space-y-3 flex-1">
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24 shimmer"></div>
                            <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-16 shimmer"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-32 shimmer"></div>
                        </div>
                        <div class="h-12 w-12 bg-gray-200 dark:bg-gray-700 rounded-lg shimmer"></div>
                    </div>
                </div>
            @endfor
        </div>

    @elseif($type === 'list')
        {{-- List Items Skeleton --}}
        <div class="space-y-3" aria-hidden="true">
            @for($i = 0; $i < $count; $i++)
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 animate-pulse">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-4 flex-1">
                            <div class="h-10 w-10 bg-gray-200 dark:bg-gray-700 rounded-full shimmer"></div>
                            <div class="space-y-2 flex-1">
                                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/3 shimmer"></div>
                                <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2 shimmer"></div>
                            </div>
                        </div>
                        <div class="h-8 w-8 bg-gray-200 dark:bg-gray-700 rounded shimmer"></div>
                    </div>
                </div>
            @endfor
        </div>

    @elseif($type === 'table')
        {{-- Table Skeleton --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden" aria-hidden="true">
            <div class="animate-pulse">
                {{-- Table Header --}}
                <div class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex gap-4">
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/6 shimmer"></div>
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4 shimmer"></div>
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/6 shimmer"></div>
                        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/6 shimmer"></div>
                    </div>
                </div>
                {{-- Table Rows --}}
                @for($i = 0; $i < $count; $i++)
                    <div class="border-b border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex gap-4">
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/6 shimmer"></div>
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4 shimmer"></div>
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/6 shimmer"></div>
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/6 shimmer"></div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>

    @elseif($type === 'text')
        {{-- Text Lines Skeleton --}}
        <div class="space-y-3 animate-pulse" aria-hidden="true">
            @for($i = 0; $i < $count; $i++)
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded shimmer" style="width: {{ rand(60, 100) }}%"></div>
            @endfor
        </div>

    @else
        {{-- Default Generic Skeleton --}}
        <div class="space-y-4 animate-pulse" aria-hidden="true">
            @for($i = 0; $i < $count; $i++)
                <div class="h-20 bg-gray-200 dark:bg-gray-700 rounded-lg shimmer"></div>
            @endfor
        </div>
    @endif
</div>

<style>
    @keyframes shimmer {
        0% {
            background-position: -1000px 0;
        }
        100% {
            background-position: 1000px 0;
        }
    }

    .shimmer {
        background: linear-gradient(
            to right,
            rgba(229, 231, 235, 0) 0%,
            rgba(229, 231, 235, 0.8) 50%,
            rgba(229, 231, 235, 0) 100%
        );
        background-size: 1000px 100%;
        animation: shimmer 2s infinite;
    }

    .dark .shimmer {
        background: linear-gradient(
            to right,
            rgba(55, 65, 81, 0) 0%,
            rgba(55, 65, 81, 0.8) 50%,
            rgba(55, 65, 81, 0) 100%
        );
        background-size: 1000px 100%;
    }
</style>
