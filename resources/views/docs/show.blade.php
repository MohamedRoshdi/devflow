@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-slate-900 rounded-2xl shadow-xl border border-slate-700/50 overflow-hidden">
    <!-- Hero Header -->
    <div class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 px-8 py-6 border-b border-slate-700/50">
        <div class="absolute inset-0 bg-grid-pattern opacity-5"></div>
        <div class="relative flex items-center justify-between">
            <div>
                <nav class="flex mb-2" aria-label="Breadcrumb">
                    <ol class="flex items-center space-x-2 text-sm">
                        <li>
                            <a href="{{ route('docs.show') }}" class="text-blue-400 hover:text-blue-300 transition-colors">
                                Documentation
                            </a>
                        </li>
                        <li>
                            <svg class="w-4 h-4 text-slate-500 mx-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </li>
                        <li class="text-slate-300">
                            {{ $title }}
                        </li>
                    </ol>
                </nav>
                <h1 class="text-3xl font-bold text-white">{{ $title }}</h1>
                @if($description)
                <p class="text-slate-400 mt-1">{{ $description }}</p>
                @endif
            </div>
            <div class="p-3 bg-gradient-to-br from-blue-500/20 to-purple-500/20 rounded-xl">
                <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
        </div>
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
                    border-bottom: 1px solid rgb(51 65 85 / 0.5);
                    color: #fff;
                }
                .docs-content h2 {
                    font-size: 1.5rem;
                    font-weight: 600;
                    color: #60a5fa;
                    margin-top: 2.5rem;
                    margin-bottom: 1rem;
                    padding-bottom: 0.5rem;
                    border-bottom: 1px solid rgb(51 65 85 / 0.5);
                }
                .docs-content h3 {
                    font-size: 1.25rem;
                    font-weight: 600;
                    color: #e2e8f0;
                    margin-top: 1.5rem;
                    margin-bottom: 0.75rem;
                }
                .docs-content p {
                    color: #cbd5e1;
                    line-height: 1.75;
                    margin-bottom: 1rem;
                }
                .docs-content ul, .docs-content ol {
                    margin: 1rem 0;
                    padding-left: 1.5rem;
                }
                .docs-content ul { list-style-type: disc; }
                .docs-content ol { list-style-type: decimal; }
                .docs-content li {
                    color: #cbd5e1;
                    margin-bottom: 0.5rem;
                    line-height: 1.6;
                }
                .docs-content strong {
                    font-weight: 600;
                    color: #f1f5f9;
                }
                .docs-content a {
                    color: #60a5fa;
                    text-decoration: none;
                    transition: color 0.2s;
                }
                .docs-content a:hover {
                    text-decoration: underline;
                    color: #93c5fd;
                }
                .docs-content code {
                    background: rgb(51 65 85 / 0.5);
                    padding: 0.125rem 0.375rem;
                    border-radius: 0.25rem;
                    font-size: 0.875rem;
                    font-family: ui-monospace, monospace;
                    color: #93c5fd;
                }
                .docs-content pre {
                    background: rgb(15 23 42 / 0.8);
                    border: 1px solid rgb(51 65 85 / 0.5);
                    padding: 1rem;
                    border-radius: 0.5rem;
                    overflow-x: auto;
                    margin: 1rem 0;
                }
                .docs-content pre code {
                    background: transparent;
                    padding: 0;
                    color: #e2e8f0;
                }
                .docs-content blockquote {
                    border-left: 4px solid #3b82f6;
                    background: rgba(59, 130, 246, 0.1);
                    padding: 0.75rem 1rem;
                    margin: 1rem 0;
                    border-radius: 0 0.5rem 0.5rem 0;
                    color: #cbd5e1;
                }
                .docs-content hr {
                    border: none;
                    border-top: 1px solid rgb(51 65 85 / 0.5);
                    margin: 2rem 0;
                }
                .docs-content table {
                    width: 100%;
                    margin: 1rem 0;
                    border-collapse: collapse;
                    border: 1px solid rgb(51 65 85 / 0.5);
                    border-radius: 0.5rem;
                    overflow: hidden;
                }
                .docs-content th {
                    background: rgb(51 65 85 / 0.5);
                    padding: 0.75rem;
                    text-align: left;
                    font-weight: 600;
                    color: #e2e8f0;
                    border-bottom: 1px solid rgb(51 65 85 / 0.5);
                }
                .docs-content td {
                    padding: 0.75rem;
                    border-top: 1px solid rgb(51 65 85 / 0.3);
                    color: #cbd5e1;
                }
                .heading-permalink {
                    opacity: 0;
                    margin-right: 0.5rem;
                    color: #64748b;
                    text-decoration: none;
                    transition: opacity 0.2s;
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
        <aside class="hidden xl:block w-64 flex-shrink-0 px-6 py-8 border-l border-slate-700/50">
            <div class="sticky top-8">
                <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">
                    On this page
                </h4>
                <nav class="space-y-1">
                    @foreach($tableOfContents as $item)
                    <a
                        href="#{{ $item['slug'] }}"
                        class="block py-1 text-sm {{ $item['level'] === 3 ? 'pl-4' : '' }} text-slate-300 hover:text-blue-400 transition-colors"
                    >
                        {{ $item['title'] }}
                    </a>
                    @endforeach
                </nav>
            </div>
        </aside>
        @endif
    </div>

    <!-- Footer Navigation -->
    <div class="px-8 py-6 border-t border-slate-700/50 flex justify-between items-center bg-slate-800/30">
        @php
            $currentIndex = array_search($category, array_column($categories, 'slug'));
            $prevCategory = $currentIndex > 0 ? $categories[$currentIndex - 1] : null;
            $nextCategory = $currentIndex < count($categories) - 1 ? $categories[$currentIndex + 1] : null;
        @endphp

        @if($prevCategory)
        <a
            href="{{ route('docs.show', ['category' => $prevCategory['slug']]) }}"
            class="flex items-center px-4 py-2 text-sm font-medium text-blue-400 hover:text-blue-300 hover:bg-slate-700/50 rounded-lg transition-all"
        >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            <span>{{ $prevCategory['title'] }}</span>
        </a>
        @else
        <div></div>
        @endif

        @if($nextCategory)
        <a
            href="{{ route('docs.show', ['category' => $nextCategory['slug']]) }}"
            class="flex items-center px-4 py-2 text-sm font-medium text-blue-400 hover:text-blue-300 hover:bg-slate-700/50 rounded-lg transition-all"
        >
            <span>{{ $nextCategory['title'] }}</span>
            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </a>
        @endif
    </div>
    </div>
</div>
@endsection
