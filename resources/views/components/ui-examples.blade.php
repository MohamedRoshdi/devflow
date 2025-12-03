{{--
    UI/UX Component Examples
    This file demonstrates how to use the new Phase 8 components
--}}

<div class="space-y-12">
    {{-- Theme Toggle Example --}}
    <section>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Theme Toggle</h2>
        <div class="card">
            <p class="text-gray-600 dark:text-gray-400 mb-4">Click the button to toggle between light and dark themes:</p>
            <x-theme-toggle />
        </div>
        <div class="mt-4 p-4 bg-gray-100 dark:bg-gray-900 rounded-lg">
            <code class="text-sm text-gray-800 dark:text-gray-200">
                &lt;x-theme-toggle /&gt;
            </code>
        </div>
    </section>

    {{-- Skeleton Loaders Examples --}}
    <section>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Skeleton Loaders</h2>

        <div class="space-y-6">
            {{-- Stats Skeleton --}}
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Stats Cards</h3>
                <x-skeleton-loader type="stats" :count="4" />
            </div>

            {{-- Card Skeleton --}}
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Cards</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <x-skeleton-loader type="card" :count="3" />
                </div>
            </div>

            {{-- List Skeleton --}}
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">List Items</h3>
                <x-skeleton-loader type="list" :count="5" />
            </div>

            {{-- Table Skeleton --}}
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Table</h3>
                <x-skeleton-loader type="table" :count="5" />
            </div>

            {{-- Text Skeleton --}}
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Text Lines</h3>
                <x-skeleton-loader type="text" :count="8" />
            </div>
        </div>

        <div class="mt-4 p-4 bg-gray-100 dark:bg-gray-900 rounded-lg">
            <code class="text-sm text-gray-800 dark:text-gray-200">
                &lt;x-skeleton-loader type="stats" :count="4" /&gt;<br>
                &lt;x-skeleton-loader type="card" :count="3" /&gt;<br>
                &lt;x-skeleton-loader type="list" :count="5" /&gt;<br>
                &lt;x-skeleton-loader type="table" :count="5" /&gt;<br>
                &lt;x-skeleton-loader type="text" :count="8" /&gt;
            </code>
        </div>
    </section>

    {{-- Empty States Examples --}}
    <section>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Empty States</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- No Projects --}}
            <div class="card">
                <x-empty-state
                    title="No projects yet"
                    description="Get started by creating your first project. Deploy and manage your applications with ease."
                    icon="folder"
                    buttonText="Create Project"
                    buttonRoute="/projects/create"
                    secondaryButtonText="View Documentation"
                    secondaryButtonRoute="/docs"
                />
            </div>

            {{-- No Servers --}}
            <div class="card">
                <x-empty-state
                    title="No servers configured"
                    description="Add your first server to start deploying applications."
                    icon="server"
                    buttonText="Add Server"
                    buttonAction="$dispatch('open-server-modal')"
                />
            </div>

            {{-- No Deployments --}}
            <div class="card">
                <x-empty-state
                    title="No deployments yet"
                    description="Your deployment history will appear here once you start deploying projects."
                    icon="clock"
                />
            </div>

            {{-- No Search Results --}}
            <div class="card">
                <x-empty-state
                    title="No results found"
                    description="Try adjusting your search terms or filters."
                    icon="search"
                />
            </div>
        </div>

        <div class="mt-4 p-4 bg-gray-100 dark:bg-gray-900 rounded-lg">
            <code class="text-sm text-gray-800 dark:text-gray-200">
                &lt;x-empty-state<br>
                &nbsp;&nbsp;&nbsp;&nbsp;title="No projects yet"<br>
                &nbsp;&nbsp;&nbsp;&nbsp;description="Get started by creating your first project."<br>
                &nbsp;&nbsp;&nbsp;&nbsp;icon="folder"<br>
                &nbsp;&nbsp;&nbsp;&nbsp;buttonText="Create Project"<br>
                &nbsp;&nbsp;&nbsp;&nbsp;buttonRoute="/projects/create"<br>
                /&gt;
            </code>
        </div>
    </section>

    {{-- Toast Notifications Example --}}
    <section>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Toast Notifications</h2>

        <div class="card">
            <p class="text-gray-600 dark:text-gray-400 mb-4">Click the buttons to see different notification styles:</p>

            <div class="flex flex-wrap gap-3">
                <button onclick="window.showToast('Operation completed successfully!', 'success')" class="btn btn-success">
                    Success Toast
                </button>
                <button onclick="window.showToast('Something went wrong!', 'error')" class="btn btn-danger">
                    Error Toast
                </button>
                <button onclick="window.showToast('Please review your changes', 'warning')" class="btn btn-secondary">
                    Warning Toast
                </button>
                <button onclick="window.showToast('Deployment in progress...', 'info')" class="btn btn-primary">
                    Info Toast
                </button>
            </div>
        </div>

        <div class="mt-4 p-4 bg-gray-100 dark:bg-gray-900 rounded-lg">
            <code class="text-sm text-gray-800 dark:text-gray-200">
                // JavaScript<br>
                window.showToast('Message', 'success');<br>
                window.showToast('Message', 'error');<br>
                window.showToast('Message', 'warning');<br>
                window.showToast('Message', 'info');<br>
                <br>
                // Livewire<br>
                $this->dispatch('toast', message: 'Success!', type: 'success');
            </code>
        </div>
    </section>

    {{-- Keyboard Shortcuts Example --}}
    <section>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Keyboard Shortcuts</h2>

        <div class="card">
            <p class="text-gray-600 dark:text-gray-400 mb-4">The following keyboard shortcuts are available:</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-3">
                    <h4 class="font-semibold text-gray-900 dark:text-white">Navigation</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Dashboard</span>
                            <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs">⌘/Ctrl + D</kbd>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Servers</span>
                            <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs">⌘/Ctrl + S</kbd>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Projects</span>
                            <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs">⌘/Ctrl + P</kbd>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <h4 class="font-semibold text-gray-900 dark:text-white">Actions</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Command Palette</span>
                            <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs">⌘/Ctrl + K</kbd>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">New Project</span>
                            <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs">⌘/Ctrl + N</kbd>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Show Help</span>
                            <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs">⌘/Ctrl + /</kbd>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <p class="text-sm text-blue-800 dark:text-blue-300">
                    Press <kbd class="px-2 py-1 bg-blue-200 dark:bg-blue-800 rounded text-xs">⌘/Ctrl + K</kbd> to open the command palette
                    or <kbd class="px-2 py-1 bg-blue-200 dark:bg-blue-800 rounded text-xs">⌘/Ctrl + /</kbd> for the full shortcuts guide.
                </p>
            </div>
        </div>
    </section>

    {{-- Animation Examples --}}
    <section>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Animations</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="card animate-fadeIn hover-lift">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Fade In</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Uses animate-fadeIn class</p>
            </div>

            <div class="card animate-slideUp hover-lift">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Slide Up</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Uses animate-slideUp class</p>
            </div>

            <div class="card animate-scaleIn hover-lift">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Scale In</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Uses animate-scaleIn class</p>
            </div>
        </div>

        <div class="mt-4 p-4 bg-gray-100 dark:bg-gray-900 rounded-lg">
            <code class="text-sm text-gray-800 dark:text-gray-200">
                &lt;div class="animate-fadeIn"&gt;...&lt;/div&gt;<br>
                &lt;div class="animate-slideUp"&gt;...&lt;/div&gt;<br>
                &lt;div class="animate-scaleIn"&gt;...&lt;/div&gt;<br>
                &lt;div class="hover-lift"&gt;...&lt;/div&gt;
            </code>
        </div>
    </section>
</div>
