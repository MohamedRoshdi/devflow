@extends('docs.layout', ['title' => $title])

@section('content')
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
    <!-- Breadcrumb -->
    <div class="px-8 py-4 border-b border-gray-200 dark:border-gray-700">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="{{ route('docs.show') }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                        Documentation
                    </a>
                </li>
                <li>
                    <svg class="w-4 h-4 text-gray-400 mx-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </li>
                <li class="text-gray-700 dark:text-gray-300">
                    {{ $title }}
                </li>
            </ol>
        </nav>
    </div>

    <div class="flex">
        <!-- Content -->
        <div class="flex-1 px-8 py-8 docs-content">
            {!! $content !!}
        </div>

        <!-- Table of Contents -->
        @if(count($tableOfContents) > 0)
        <aside class="hidden xl:block w-64 flex-shrink-0 px-6 py-8 border-l border-gray-200 dark:border-gray-700">
            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                On this page
            </h4>
            <nav class="space-y-1">
                @foreach($tableOfContents as $item)
                <a
                    href="#{{ $item['slug'] }}"
                    class="block py-1 text-sm {{ $item['level'] === 3 ? 'pl-4' : '' }} text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
                >
                    {{ $item['title'] }}
                </a>
                @endforeach
            </nav>
        </aside>
        @endif
    </div>

    <!-- Footer Navigation -->
    <div class="px-8 py-6 border-t border-gray-200 dark:border-gray-700 flex justify-between">
        @php
            $currentIndex = array_search($category, array_column($categories, 'slug'));
            $prevCategory = $currentIndex > 0 ? $categories[$currentIndex - 1] : null;
            $nextCategory = $currentIndex < count($categories) - 1 ? $categories[$currentIndex + 1] : null;
        @endphp

        @if($prevCategory)
        <a
            href="{{ route('docs.show', ['category' => $prevCategory['slug']]) }}"
            class="flex items-center text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline"
        >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            {{ $prevCategory['title'] }}
        </a>
        @else
        <div></div>
        @endif

        @if($nextCategory)
        <a
            href="{{ route('docs.show', ['category' => $nextCategory['slug']]) }}"
            class="flex items-center text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline"
        >
            {{ $nextCategory['title'] }}
            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </a>
        @endif
    </div>
</div>
@endsection
