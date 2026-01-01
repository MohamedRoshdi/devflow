{{--
    Keyboard Shortcuts Component

    Provides global keyboard shortcuts for common navigation and actions.
    Press ? to show the shortcuts help modal.

    Shortcuts:
    - Navigation: g+d (dashboard), g+p (projects), g+s (servers), g+l (deployments)
    - Actions: / (focus search), n (new), Escape (close modals)
    - Help: ? (show shortcuts)
--}}

<div x-data="keyboardShortcuts" @keydown.window="handleKeydown($event)" class="contents">
    {{-- Shortcuts Help Modal --}}
    <div x-show="showHelp"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[9999] overflow-y-auto"
         style="display: none;">

        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-black/50 dark:bg-black/70"
             @click="showHelp = false"></div>

        {{-- Modal Content --}}
        <div class="relative min-h-screen flex items-center justify-center p-4 pointer-events-none">
            <div x-show="showHelp"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden pointer-events-auto"
                 @click.stop>

                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-900/50">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900/50 rounded-lg">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707"/>
                            </svg>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Keyboard Shortcuts</h2>
                    </div>
                    <button @click="showHelp = false"
                            type="button"
                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Shortcuts List --}}
                <div class="p-6 overflow-y-auto max-h-[60vh]">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Navigation --}}
                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Navigation
                            </h3>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Go to Dashboard</span>
                                    <div class="flex items-center gap-1">
                                        <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600">g</kbd>
                                        <span class="text-gray-400 text-xs">then</span>
                                        <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600">d</kbd>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Go to Projects</span>
                                    <div class="flex items-center gap-1">
                                        <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600">g</kbd>
                                        <span class="text-gray-400 text-xs">then</span>
                                        <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600">p</kbd>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Go to Servers</span>
                                    <div class="flex items-center gap-1">
                                        <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600">g</kbd>
                                        <span class="text-gray-400 text-xs">then</span>
                                        <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600">s</kbd>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Go to Deployments</span>
                                    <div class="flex items-center gap-1">
                                        <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600">g</kbd>
                                        <span class="text-gray-400 text-xs">then</span>
                                        <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600">l</kbd>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Go to Settings</span>
                                    <div class="flex items-center gap-1">
                                        <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600">g</kbd>
                                        <span class="text-gray-400 text-xs">then</span>
                                        <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600">t</kbd>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                Actions
                            </h3>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Focus Search</span>
                                    <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600">/</kbd>
                                </div>
                                <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">New Item</span>
                                    <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600">n</kbd>
                                </div>
                                <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Refresh Page</span>
                                    <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600">r</kbd>
                                </div>
                                <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Toggle Dark Mode</span>
                                    <div class="flex items-center gap-1">
                                        <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600">Shift</kbd>
                                        <span class="text-gray-400">+</span>
                                        <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600">D</kbd>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- General --}}
                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                </svg>
                                General
                            </h3>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Show Shortcuts</span>
                                    <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600">?</kbd>
                                </div>
                                <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Close Modal / Cancel</span>
                                    <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600">Esc</kbd>
                                </div>
                                <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Submit Form</span>
                                    <div class="flex items-center gap-1">
                                        <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600">Ctrl</kbd>
                                        <span class="text-gray-400">+</span>
                                        <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600">Enter</kbd>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Forms --}}
                        <div>
                            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Forms
                            </h3>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Next Field</span>
                                    <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600">Tab</kbd>
                                </div>
                                <div class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Previous Field</span>
                                    <div class="flex items-center gap-1">
                                        <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600">Shift</kbd>
                                        <span class="text-gray-400">+</span>
                                        <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600">Tab</kbd>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                    <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                        Press <kbd class="px-1.5 py-0.5 text-xs font-mono bg-gray-200 dark:bg-gray-700 rounded">?</kbd> anytime to show this help
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Keyboard Shortcut Indicator (shows briefly when combo starts) --}}
    <div x-show="pendingKey"
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed bottom-20 left-1/2 -translate-x-1/2 z-50">
        <div class="bg-gray-900 dark:bg-gray-700 text-white px-4 py-2 rounded-lg shadow-lg flex items-center gap-2">
            <kbd class="px-2 py-1 text-sm font-mono bg-gray-700 dark:bg-gray-600 rounded" x-text="pendingKey"></kbd>
            <span class="text-gray-300 text-sm">waiting for next key...</span>
        </div>
    </div>
</div>

{{-- Alpine component registered in app.js --}}
