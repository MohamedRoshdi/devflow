<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Hero Section -->
    <div class="mb-10 relative">
        <div class="absolute inset-0 rounded-3xl bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 opacity-80 blur-xl"></div>
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-900/90 to-purple-900 text-white shadow-2xl">
            <div class="absolute inset-y-0 right-0 w-1/2 bg-[radial-gradient(circle_at_top,_rgba(255,255,255,0.12),_transparent_55%)]"></div>
            <div class="relative p-8 xl:p-10">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <span class="px-3 py-1 text-xs font-semibold tracking-wide uppercase bg-white/10 text-white/80 rounded-full">Project Templates</span>
                        </div>
                        <h1 class="text-4xl lg:text-5xl font-extrabold tracking-tight mb-3">Template Manager</h1>
                        <p class="text-white/80 text-lg max-w-2xl">Create and manage reusable project templates with predefined configurations</p>
                    </div>
                    <div class="hidden lg:block">
                        <svg class="w-32 h-32 text-white/20" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="mb-6 bg-green-50 dark:bg-green-900/30 border-l-4 border-green-500 text-green-800 dark:text-green-400 px-6 py-4 rounded-r-lg shadow">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('message') }}
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 text-red-800 dark:text-red-400 px-6 py-4 rounded-r-lg shadow">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                {{ session('error') }}
            </div>
        </div>
    @endif

    <!-- Search and Filter Bar -->
    <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg dark:shadow-gray-900/50 p-6">
        <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
            <div class="flex-1 w-full sm:w-auto">
                <input type="text" wire:model.live.debounce.300ms="searchTerm"
                       placeholder="Search templates..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <div class="flex gap-3 w-full sm:w-auto">
                <select wire:model.live="frameworkFilter"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    <option value="all">All Frameworks</option>
                    <option value="laravel">Laravel</option>
                    <option value="react">React</option>
                    <option value="vue">Vue</option>
                    <option value="nodejs">Node.js</option>
                    <option value="php">PHP</option>
                    <option value="python">Python</option>
                    <option value="docker">Docker</option>
                    <option value="custom">Custom</option>
                </select>
                <button wire:click="openCreateModal"
                        class="px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-lg font-semibold transition-all transform hover:scale-105 shadow whitespace-nowrap">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    New Template
                </button>
            </div>
        </div>
    </div>

    <!-- Templates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($this->templates as $template)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg dark:shadow-gray-900/50 overflow-hidden hover:shadow-xl transition-all transform hover:-translate-y-1">
                <!-- Template Header -->
                <div class="p-6 {{ $template->color ? 'bg-gradient-to-r from-'.$template->color.'-500 to-'.$template->color.'-600' : 'bg-gradient-to-r from-gray-700 to-gray-800' }}">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center space-x-3">
                            @if($template->icon)
                                <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center text-2xl">
                                    {{ $template->icon }}
                                </div>
                            @else
                                <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            @if($template->is_system)
                                <span class="px-2 py-1 bg-white/20 text-white text-xs font-semibold rounded-full">System</span>
                            @endif
                            <button wire:click="toggleTemplateStatus({{ $template->id }})"
                                    class="px-2 py-1 {{ $template->is_active ? 'bg-green-500/20 text-green-200' : 'bg-gray-500/20 text-gray-300' }} text-xs font-semibold rounded-full">
                                {{ $template->is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold text-white">{{ $template->name }}</h3>
                    <p class="text-white/70 text-sm mt-1 uppercase tracking-wide">{{ ucfirst($template->framework) }}</p>
                </div>

                <!-- Template Body -->
                <div class="p-6">
                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-4 line-clamp-2">
                        {{ $template->description ?? 'No description available' }}
                    </p>

                    <!-- Template Details -->
                    <div class="space-y-2 mb-4">
                        <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                            </svg>
                            Branch: {{ $template->default_branch }}
                        </div>
                        @if($template->php_version)
                            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                                </svg>
                                PHP: {{ $template->php_version }}
                            </div>
                        @endif
                        @if($template->node_version)
                            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                                </svg>
                                Node: {{ $template->node_version }}
                            </div>
                        @endif
                    </div>

                    <!-- Commands Count -->
                    <div class="flex gap-2 mb-4">
                        @if($template->install_commands)
                            <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 text-xs rounded-full">
                                {{ count($template->install_commands) }} Install
                            </span>
                        @endif
                        @if($template->build_commands)
                            <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 text-xs rounded-full">
                                {{ count($template->build_commands) }} Build
                            </span>
                        @endif
                        @if($template->post_deploy_commands)
                            <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-400 text-xs rounded-full">
                                {{ count($template->post_deploy_commands) }} Deploy
                            </span>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <button wire:click="openPreviewModal({{ $template->id }})"
                                class="flex-1 px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-sm">
                            Preview
                        </button>
                        <button wire:click="cloneTemplate({{ $template->id }})"
                                class="flex-1 px-3 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors text-sm">
                            Clone
                        </button>
                        @if(!$template->is_system)
                            <button wire:click="openEditModal({{ $template->id }})"
                                    class="px-3 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button wire:click="openDeleteModal({{ $template->id }})"
                                    class="px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 bg-white dark:bg-gray-800 rounded-xl shadow-lg dark:shadow-gray-900/50 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
                <p class="text-gray-500 dark:text-gray-400 mb-4">No templates found</p>
                <button wire:click="openCreateModal"
                        class="px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-lg font-semibold transition-all transform hover:scale-105 shadow">
                    Create Your First Template
                </button>
            </div>
        @endforelse
    </div>

    <!-- Create/Edit Modal -->
    @if($showCreateModal || $showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="{{ $showCreateModal ? 'showCreateModal' : 'showEditModal' }} = false"></div>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                        <h3 class="text-2xl font-bold text-white">
                            {{ $editingTemplateId ? 'Edit Template' : 'Create New Template' }}
                        </h3>
                    </div>

                    <div class="px-6 py-6">
                        <!-- Tabs -->
                        <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
                            <nav class="-mb-px flex space-x-8">
                                <button wire:click="$set('activeTab', 'basic')"
                                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'basic' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                                    Basic Info
                                </button>
                                <button wire:click="$set('activeTab', 'commands')"
                                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'commands' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                                    Commands
                                </button>
                                <button wire:click="$set('activeTab', 'docker')"
                                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'docker' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                                    Docker
                                </button>
                                <button wire:click="$set('activeTab', 'env')"
                                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'env' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                                    Environment
                                </button>
                            </nav>
                        </div>

                        <form wire:submit.prevent="{{ $editingTemplateId ? 'updateTemplate' : 'createTemplate' }}">
                            <!-- Basic Info Tab -->
                            @if($activeTab === 'basic')
                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Template Name</label>
                                            <input type="text" wire:model.live="name"
                                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Slug</label>
                                            <input type="text" wire:model="slug"
                                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                            @error('slug') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                                        <textarea wire:model="description" rows="3"
                                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"></textarea>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Framework</label>
                                            <select wire:model="framework"
                                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                                <option value="laravel">Laravel</option>
                                                <option value="react">React</option>
                                                <option value="vue">Vue</option>
                                                <option value="nodejs">Node.js</option>
                                                <option value="php">PHP</option>
                                                <option value="python">Python</option>
                                                <option value="docker">Docker</option>
                                                <option value="custom">Custom</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Icon (Emoji)</label>
                                            <input type="text" wire:model="icon" placeholder="🚀"
                                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Color</label>
                                            <select wire:model="color"
                                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                                <option value="">Default</option>
                                                <option value="blue">Blue</option>
                                                <option value="green">Green</option>
                                                <option value="red">Red</option>
                                                <option value="purple">Purple</option>
                                                <option value="indigo">Indigo</option>
                                                <option value="pink">Pink</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Default Branch</label>
                                            <input type="text" wire:model="default_branch"
                                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">PHP Version</label>
                                            <select wire:model="php_version"
                                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                                <option value="">None</option>
                                                <option value="8.1">8.1</option>
                                                <option value="8.2">8.2</option>
                                                <option value="8.3">8.3</option>
                                                <option value="8.4">8.4</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Node Version</label>
                                            <input type="text" wire:model="node_version" placeholder="20.x"
                                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Health Check Path</label>
                                        <input type="text" wire:model="health_check_path" placeholder="/api/health"
                                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                    </div>

                                    <div class="flex items-center">
                                        <input type="checkbox" wire:model="is_active" id="is_active"
                                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                        <label for="is_active" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Active</label>
                                    </div>
                                </div>
                            @endif

                            <!-- Commands Tab -->
                            @if($activeTab === 'commands')
                                <div class="space-y-6">
                                    <!-- Install Commands -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Install Commands</label>
                                        <div class="space-y-2 mb-2">
                                            @foreach($install_commands as $index => $command)
                                                <div class="flex gap-2">
                                                    <input type="text" value="{{ $command }}" readonly
                                                           class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white font-mono text-sm">
                                                    <button type="button" wire:click="removeInstallCommand({{ $index }})"
                                                            class="px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="flex gap-2">
                                            <input type="text" wire:model="newInstallCommand" placeholder="composer install"
                                                   class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm">
                                            <button type="button" wire:click="addInstallCommand"
                                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">Add</button>
                                        </div>
                                    </div>

                                    <!-- Build Commands -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Build Commands</label>
                                        <div class="space-y-2 mb-2">
                                            @foreach($build_commands as $index => $command)
                                                <div class="flex gap-2">
                                                    <input type="text" value="{{ $command }}" readonly
                                                           class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white font-mono text-sm">
                                                    <button type="button" wire:click="removeBuildCommand({{ $index }})"
                                                            class="px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="flex gap-2">
                                            <input type="text" wire:model="newBuildCommand" placeholder="npm run build"
                                                   class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm">
                                            <button type="button" wire:click="addBuildCommand"
                                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">Add</button>
                                        </div>
                                    </div>

                                    <!-- Post Deploy Commands -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Post-Deploy Commands</label>
                                        <div class="space-y-2 mb-2">
                                            @foreach($post_deploy_commands as $index => $command)
                                                <div class="flex gap-2">
                                                    <input type="text" value="{{ $command }}" readonly
                                                           class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white font-mono text-sm">
                                                    <button type="button" wire:click="removePostDeployCommand({{ $index }})"
                                                            class="px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="flex gap-2">
                                            <input type="text" wire:model="newPostDeployCommand" placeholder="php artisan migrate"
                                                   class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm">
                                            <button type="button" wire:click="addPostDeployCommand"
                                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">Add</button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Docker Tab -->
                            @if($activeTab === 'docker')
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Docker Compose Template</label>
                                        <textarea wire:model="docker_compose_template" rows="10"
                                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm focus:ring-2 focus:ring-blue-500"
                                                  placeholder="version: '3.8'&#10;services:&#10;  app:&#10;    image: php:8.4-fpm"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Dockerfile Template</label>
                                        <textarea wire:model="dockerfile_template" rows="10"
                                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm focus:ring-2 focus:ring-blue-500"
                                                  placeholder="FROM php:8.4-fpm&#10;WORKDIR /var/www"></textarea>
                                    </div>
                                </div>
                            @endif

                            <!-- Environment Tab -->
                            @if($activeTab === 'env')
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Environment Variables Template</label>
                                        <div class="space-y-2 mb-4">
                                            @foreach($env_template as $key => $value)
                                                <div class="flex gap-2">
                                                    <input type="text" value="{{ $key }}" readonly
                                                           class="w-1/3 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white font-mono text-sm">
                                                    <input type="text" value="{{ $value }}" readonly
                                                           class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white font-mono text-sm">
                                                    <button type="button" wire:click="removeEnvVariable('{{ $key }}')"
                                                            class="px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="flex gap-2">
                                            <input type="text" wire:model="newEnvKey" placeholder="APP_NAME"
                                                   class="w-1/3 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm">
                                            <input type="text" wire:model="newEnvValue" placeholder="MyApp"
                                                   class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono text-sm">
                                            <button type="button" wire:click="addEnvVariable"
                                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">Add</button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="mt-6 flex justify-end gap-3">
                                <button type="button" wire:click="{{ $showCreateModal ? 'showCreateModal' : 'showEditModal' }} = false"
                                        class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-lg font-semibold transition-all transform hover:scale-105 shadow">
                                    {{ $editingTemplateId ? 'Update Template' : 'Create Template' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Preview Modal -->
    @if($showPreviewModal && $previewingTemplateId)
        @php
            $template = \App\Models\ProjectTemplate::find($previewingTemplateId);
        @endphp
        @if($template)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="showPreviewModal = false"></div>

                    <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4">
                            <h3 class="text-2xl font-bold text-white">Template Preview: {{ $template->name }}</h3>
                        </div>

                        <div class="px-6 py-6 max-h-[70vh] overflow-y-auto">
                            <div class="space-y-6">
                                <!-- Basic Info -->
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Basic Information</h4>
                                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 space-y-2">
                                        <p class="text-sm"><span class="font-medium text-gray-700 dark:text-gray-300">Framework:</span> <span class="text-gray-600 dark:text-gray-400">{{ ucfirst($template->framework) }}</span></p>
                                        <p class="text-sm"><span class="font-medium text-gray-700 dark:text-gray-300">Branch:</span> <span class="text-gray-600 dark:text-gray-400">{{ $template->default_branch }}</span></p>
                                        @if($template->php_version)
                                            <p class="text-sm"><span class="font-medium text-gray-700 dark:text-gray-300">PHP Version:</span> <span class="text-gray-600 dark:text-gray-400">{{ $template->php_version }}</span></p>
                                        @endif
                                        @if($template->node_version)
                                            <p class="text-sm"><span class="font-medium text-gray-700 dark:text-gray-300">Node Version:</span> <span class="text-gray-600 dark:text-gray-400">{{ $template->node_version }}</span></p>
                                        @endif
                                    </div>
                                </div>

                                <!-- Commands -->
                                @if($template->formatted_commands)
                                    <div>
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Commands</h4>
                                        <div class="space-y-3">
                                            @foreach($template->formatted_commands as $type => $commands)
                                                <div>
                                                    <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ $type }}</h5>
                                                    <div class="bg-gray-900 rounded-lg p-3">
                                                        @foreach($commands as $command)
                                                            <p class="text-green-400 font-mono text-sm">$ {{ $command }}</p>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Environment Variables -->
                                @if($template->env_template)
                                    <div>
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Environment Variables</h4>
                                        <div class="bg-gray-900 rounded-lg p-3 font-mono text-sm">
                                            @foreach($template->env_template as $key => $value)
                                                <p class="text-blue-400">{{ $key }}={{ $value }}</p>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Docker Templates -->
                                @if($template->docker_compose_template)
                                    <div>
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Docker Compose</h4>
                                        <div class="bg-gray-900 rounded-lg p-3">
                                            <pre class="text-blue-400 font-mono text-sm overflow-x-auto">{{ $template->docker_compose_template }}</pre>
                                        </div>
                                    </div>
                                @endif

                                @if($template->dockerfile_template)
                                    <div>
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Dockerfile</h4>
                                        <div class="bg-gray-900 rounded-lg p-3">
                                            <pre class="text-blue-400 font-mono text-sm overflow-x-auto">{{ $template->dockerfile_template }}</pre>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 flex justify-end">
                            <button wire:click="showPreviewModal = false"
                                    class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="showDeleteModal = false"></div>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-gradient-to-r from-red-600 to-pink-600 px-6 py-4">
                        <h3 class="text-2xl font-bold text-white">Delete Template</h3>
                    </div>

                    <div class="px-6 py-6">
                        <p class="text-gray-700 dark:text-gray-300 mb-4">Are you sure you want to delete this template? This action cannot be undone.</p>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 flex justify-end gap-3">
                        <button wire:click="showDeleteModal = false"
                                class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                            Cancel
                        </button>
                        <button wire:click="deleteTemplate"
                                class="px-6 py-2 bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 text-white rounded-lg font-semibold transition-all transform hover:scale-105 shadow">
                            Delete Template
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
