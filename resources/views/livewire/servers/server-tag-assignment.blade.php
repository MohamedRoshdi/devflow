<div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Server Tags</h3>
            @if(session()->has('message'))
                <span class="text-sm text-green-600 dark:text-green-400">{{ session('message') }}</span>
            @endif
        </div>

        @if(count($availableTags) > 0)
            <div class="space-y-3 mb-4">
                @foreach($availableTags as $tag)
                    <label class="flex items-center justify-between p-3 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 rounded-lg cursor-pointer hover:shadow-md transition-all duration-200 group">
                        <div class="flex items-center space-x-3 flex-1">
                            <input type="checkbox"
                                   wire:click="toggleTag({{ $tag['id'] }})"
                                   @if(in_array($tag['id'], $selectedTags)) checked @endif
                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <div class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $tag['color'] }}"></div>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $tag['name'] }}</span>
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                <button wire:click="saveTags"
                        wire:loading.attr="disabled"
                        wire:target="saveTags"
                        class="bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-semibold px-6 py-2 rounded-lg transition-all duration-300 hover:scale-105 shadow-lg disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                    <span wire:loading.remove wire:target="saveTags">Save Tags</span>
                    <span wire:loading wire:target="saveTags" class="inline-flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Saving...
                    </span>
                </button>
            </div>
        @else
            <div class="text-center py-8">
                <div class="p-3 bg-gradient-to-br from-purple-100 to-pink-200 dark:from-purple-700 dark:to-pink-600 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-3">
                    <svg class="h-8 w-8 text-purple-600 dark:text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">No tags available</p>
                <a href="{{ route('servers.tags') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium">
                    Create your first tag
                </a>
            </div>
        @endif
    </div>
</div>
