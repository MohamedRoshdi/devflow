<div>
    {{-- Trigger Button --}}
    <button type="button"
            wire:click="open"
            class="inline-flex items-center px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 font-medium transition-colors duration-200">
        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/>
        </svg>
        Import from GitHub
    </button>

    {{-- Modal --}}
    @if($isOpen)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" wire:keydown.escape="close">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden">

                {{-- Header --}}
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Import from GitHub</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                @if($step === 'select-repo')
                                    Select a repository to import
                                @else
                                    Choose a branch to deploy
                                @endif
                            </p>
                        </div>
                        <button wire:click="close"
                                class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                            <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Steps Indicator --}}
                    <div class="flex items-center gap-2 mt-6">
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $step === 'select-repo' ? 'bg-blue-600 text-white' : 'bg-green-600 text-white' }}">
                                @if($step === 'select-branch')
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                @else
                                    1
                                @endif
                            </div>
                            <span class="ml-2 text-sm font-medium {{ $step === 'select-repo' ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400' }}">
                                Repository
                            </span>
                        </div>
                        <div class="flex-1 h-px bg-gray-300 dark:bg-gray-600 mx-2"></div>
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full {{ $step === 'select-branch' ? 'bg-blue-600 text-white' : 'bg-gray-300 dark:bg-gray-600 text-gray-600 dark:text-gray-400' }}">
                                2
                            </div>
                            <span class="ml-2 text-sm font-medium {{ $step === 'select-branch' ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400' }}">
                                Branch
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Content --}}
                <div class="p-6 overflow-y-auto max-h-[60vh]">
                    @if(!$this->connection)
                        {{-- No Connection --}}
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">GitHub Not Connected</h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-4">Please connect your GitHub account first</p>
                            <a href="{{ route('settings.github') }}"
                               class="inline-flex items-center px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 font-medium">
                                Go to GitHub Settings
                            </a>
                        </div>

                    @elseif($step === 'select-repo')
                        {{-- Repository Selection --}}
                        <div class="space-y-4">
                            {{-- Search and Filters --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="relative">
                                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    <input type="text"
                                           wire:model.live.debounce.300ms="search"
                                           placeholder="Search repositories..."
                                           class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>

                                <select wire:model.live="visibilityFilter"
                                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="all">All Repositories</option>
                                    <option value="public">Public Only</option>
                                    <option value="private">Private Only</option>
                                </select>
                            </div>

                            {{-- Repository Grid --}}
                            <div class="grid grid-cols-1 gap-3 mt-4">
                                @forelse($this->repositories as $repo)
                                    <button type="button"
                                            wire:click="selectRepository({{ $repo->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="selectRepository"
                                            class="text-left p-4 border-2 border-gray-200 dark:border-gray-700 rounded-xl hover:border-blue-500 dark:hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all duration-200 group">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 mb-2">
                                                    <h4 class="font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400">
                                                        {{ $repo->name }}
                                                    </h4>
                                                    @if($repo->private)
                                                        <span class="px-2 py-0.5 text-xs font-medium bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded">
                                                            Private
                                                        </span>
                                                    @endif
                                                    @if($repo->language)
                                                        <span class="px-2 py-0.5 text-xs font-medium {{ $repo->language_color }} text-white rounded">
                                                            {{ $repo->language }}
                                                        </span>
                                                    @endif
                                                </div>
                                                @if($repo->description)
                                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2 line-clamp-2">
                                                        {{ $repo->description }}
                                                    </p>
                                                @endif
                                                <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                                    <span class="flex items-center">
                                                        <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                        </svg>
                                                        {{ $repo->stars_count }}
                                                    </span>
                                                    <span class="flex items-center">
                                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                                        </svg>
                                                        {{ $repo->forks_count }}
                                                    </span>
                                                    <span>{{ $repo->full_name }}</span>
                                                </div>
                                            </div>
                                            <svg class="w-6 h-6 text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </div>
                                    </button>
                                @empty
                                    <div class="text-center py-12">
                                        <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                        </svg>
                                        <p class="text-gray-600 dark:text-gray-400">No repositories found</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                    @elseif($step === 'select-branch')
                        {{-- Branch Selection --}}
                        <div class="space-y-4">
                            @if($loadingBranches)
                                <div class="text-center py-12">
                                    <svg class="animate-spin w-12 h-12 mx-auto text-blue-600 dark:text-blue-400 mb-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <p class="text-gray-600 dark:text-gray-400">Loading branches...</p>
                                </div>
                            @else
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Select Branch
                                    </label>
                                    <select wire:model="selectedBranch"
                                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch['name'] }}">
                                                {{ $branch['name'] }}
                                                @if($branch['protected']) (Protected) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        This branch will be used for deployments
                                    </p>
                                </div>

                                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                    <div class="flex">
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                        </svg>
                                        <div class="flex-1">
                                            <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-1">
                                                Automatic Deployments
                                            </h4>
                                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                                You can enable webhooks later to automatically deploy when you push to this branch
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                    <div class="flex justify-between">
                        @if($step === 'select-branch')
                            <button type="button"
                                    wire:click="backToRepoSelection"
                                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 font-medium transition-colors duration-200">
                                Back
                            </button>
                        @else
                            <button type="button"
                                    wire:click="close"
                                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 font-medium transition-colors duration-200">
                                Cancel
                            </button>
                        @endif

                        @if($step === 'select-branch' && !$loadingBranches)
                            <button type="button"
                                    wire:click="confirmSelection"
                                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200">
                                Confirm Selection
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
