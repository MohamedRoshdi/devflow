@extends('docs.layout', ['title' => 'Search Results'])

@section('content')
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-8">
    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
        Search Results
    </h1>
    <p class="text-gray-600 dark:text-gray-400 mb-8">
        Found {{ count($results) }} result{{ count($results) !== 1 ? 's' : '' }} for "<strong>{{ $query }}</strong>"
    </p>

    @if(count($results) > 0)
    <div class="space-y-6">
        @foreach($results as $result)
        <div class="border-b border-gray-200 dark:border-gray-700 pb-6 last:border-0">
            <a
                href="{{ $result['url'] }}"
                class="text-xl font-semibold text-blue-600 dark:text-blue-400 hover:underline"
            >
                {{ $result['title'] }}
            </a>
            <p class="text-sm text-gray-500 dark:text-gray-500 mt-1 mb-2">
                /docs/{{ $result['category'] }}
            </p>
            <p class="text-gray-600 dark:text-gray-400">
                {!! $result['excerpt'] !!}
            </p>

            @if(count($result['sections']) > 0)
            <div class="mt-3 flex flex-wrap gap-2">
                <span class="text-sm text-gray-500 dark:text-gray-500">Sections:</span>
                @foreach($result['sections'] as $section)
                <a
                    href="{{ $result['url'] }}#{{ $section['slug'] }}"
                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-blue-100 dark:hover:bg-blue-900/20"
                >
                    {{ $section['title'] }}
                </a>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-12">
        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
            No results found
        </h3>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
            Try searching with different keywords
        </p>
        <a
            href="{{ route('docs.show') }}"
            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg"
        >
            Browse all documentation
        </a>
    </div>
    @endif
</div>
@endsection
