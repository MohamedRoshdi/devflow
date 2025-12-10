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
        <div class="flex-1 px-8 py-8">
            <style>
                .docs-content h1 {
                    font-size: 2rem;
                    font-weight: 700;
                    margin-bottom: 1.5rem;
                    padding-bottom: 1rem;
                    border-bottom: 1px solid #e5e7eb;
                }
                .dark .docs-content h1 { border-color: #374151; color: #fff; }
                .docs-content h2 {
                    font-size: 1.5rem;
                    font-weight: 600;
                    color: #2563eb;
                    margin-top: 2.5rem;
                    margin-bottom: 1rem;
                    padding-bottom: 0.5rem;
                    border-bottom: 1px solid #e5e7eb;
                }
                .dark .docs-content h2 { color: #60a5fa; border-color: #374151; }
                .docs-content h3 {
                    font-size: 1.25rem;
                    font-weight: 600;
                    color: #1f2937;
                    margin-top: 1.5rem;
                    margin-bottom: 0.75rem;
                }
                .dark .docs-content h3 { color: #f3f4f6; }
                .docs-content p {
                    color: #4b5563;
                    line-height: 1.75;
                    margin-bottom: 1rem;
                }
                .dark .docs-content p { color: #d1d5db; }
                .docs-content ul, .docs-content ol {
                    margin: 1rem 0;
                    padding-left: 1.5rem;
                }
                .docs-content ul { list-style-type: disc; }
                .docs-content ol { list-style-type: decimal; }
                .docs-content li {
                    color: #4b5563;
                    margin-bottom: 0.5rem;
                    line-height: 1.6;
                }
                .dark .docs-content li { color: #d1d5db; }
                .docs-content strong {
                    font-weight: 600;
                    color: #111827;
                }
                .dark .docs-content strong { color: #f9fafb; }
                .docs-content a {
                    color: #2563eb;
                    text-decoration: none;
                }
                .docs-content a:hover { text-decoration: underline; }
                .dark .docs-content a { color: #60a5fa; }
                .docs-content code {
                    background: #f3f4f6;
                    padding: 0.125rem 0.375rem;
                    border-radius: 0.25rem;
                    font-size: 0.875rem;
                    font-family: ui-monospace, monospace;
                }
                .dark .docs-content code { background: #374151; color: #e5e7eb; }
                .docs-content pre {
                    background: #1f2937;
                    padding: 1rem;
                    border-radius: 0.5rem;
                    overflow-x: auto;
                    margin: 1rem 0;
                }
                .docs-content pre code {
                    background: transparent;
                    padding: 0;
                    color: #e5e7eb;
                }
                .docs-content blockquote {
                    border-left: 4px solid #3b82f6;
                    background: #eff6ff;
                    padding: 0.75rem 1rem;
                    margin: 1rem 0;
                    border-radius: 0 0.5rem 0.5rem 0;
                }
                .dark .docs-content blockquote { background: rgba(59, 130, 246, 0.1); }
                .docs-content hr {
                    border: none;
                    border-top: 1px solid #e5e7eb;
                    margin: 2rem 0;
                }
                .dark .docs-content hr { border-color: #374151; }
                .heading-permalink {
                    opacity: 0;
                    margin-right: 0.5rem;
                    color: #9ca3af;
                    text-decoration: none;
                }
                .docs-content h2:hover .heading-permalink,
                .docs-content h3:hover .heading-permalink {
                    opacity: 1;
                }
            </style>
            <article class="docs-content max-w-none text-gray-900 dark:text-gray-100">
                {!! $content !!}
            </article>
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
