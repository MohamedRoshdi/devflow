<div>
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit Project</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Update project configuration</p>
        </div>
        <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">
            Cancel
        </a>
    </div>

    @if (session()->has('message'))
        <div class="mb-6 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-400 px-4 py-3 rounded">
            {{ session('message') }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8">
        <form wire:submit="updateProject" class="space-y-8">
            <!-- Basic Information -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Project Name *</label>
                        <div class="relative">
                            <input wire:model.live="name"
                                   id="name"
                                   type="text"
                                   required
                                   placeholder="My Awesome Project"
                                   class="input @error('name') border-red-500 @enderror">
                            <div wire:loading wire:target="name" class="absolute right-3 top-1/2 -translate-y-1/2">
                                <svg class="animate-spin h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                        @error('name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Slug
                            <span class="ml-1 text-xs text-gray-400 dark:text-gray-500 font-normal">(read-only — changing breaks URLs & webhooks)</span>
                        </label>
                        <input id="slug"
                               type="text"
                               value="{{ $project->slug }}"
                               disabled
                               class="input bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed opacity-75">
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                            The slug is locked to prevent breaking existing deployment paths and webhook URLs.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Server Selection -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Server Configuration</h3>

                @if (session()->has('server_status_updated'))
                    <div class="mb-4 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 text-blue-800 dark:text-blue-400 px-4 py-2 rounded text-sm">
                        {{ session('server_status_updated') }}
                    </div>
                @endif

                <div>
                    <label for="server_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Server *</label>
                    <div class="space-y-3 relative">
                        <div wire:loading wire:target="server_id" class="absolute inset-0 bg-white/50 dark:bg-gray-800/50 flex items-center justify-center z-10 rounded-lg">
                            <svg class="animate-spin h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        @forelse($servers as $server)
                            <div class="border rounded-lg p-4 {{ $server_id == $server->id ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-blue-300' }}">
                                <div class="flex items-center justify-between">
                                    <label class="flex items-center flex-1 cursor-pointer">
                                        <input type="radio"
                                               wire:model="server_id"
                                               value="{{ $server->id }}"
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                        <div class="ml-3 flex-1">
                                            <div class="flex items-center">
                                                <span class="font-medium text-gray-900 dark:text-white">{{ $server->name }}</span>
                                                <span class="ml-2 px-2 py-1 rounded-full text-xs
                                                    @if($server->status === 'online') bg-green-100 text-green-800
                                                    @elseif($server->status === 'offline') bg-red-100 text-red-800
                                                    @elseif($server->status === 'maintenance') bg-yellow-100 text-yellow-800
                                                    @else bg-gray-100 text-gray-800
                                                    @endif">
                                                    {{ ucfirst($server->status) }}
                                                </span>
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                {{ $server->ip_address }} • {{ $server->cpu_cores ?? '?' }} CPU • {{ $server->memory_gb ?? '?' }} GB RAM
                                                @if($server->docker_installed)
                                                    • <span class="text-green-600 dark:text-green-400">Docker ✓</span>
                                                @endif
                                            </div>
                                        </div>
                                    </label>
                                    <button type="button"
                                            wire:click="refreshServerStatus({{ $server->id }})"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50 cursor-not-allowed"
                                            wire:target="refreshServerStatus({{ $server->id }})"
                                            class="ml-3 text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 text-sm font-medium"
                                            title="Refresh server status">
                                        <span wire:loading.remove wire:target="refreshServerStatus({{ $server->id }})">🔄 Refresh</span>
                                        <span wire:loading wire:target="refreshServerStatus({{ $server->id }})" class="flex items-center">
                                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <p class="text-yellow-600 text-sm">
                                ⚠️ You need to add a server first.
                                <a href="{{ route('servers.create') }}" class="underline font-medium">Add server now</a>
                            </p>
                        @endforelse
                    </div>

                    @error('server_id')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Repository -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Repository</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="repository_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Repository URL *</label>
                        <div class="relative">
                            <input wire:model="repository_url"
                                   id="repository_url"
                                   type="text"
                                   placeholder="https://github.com/user/repo.git or git@github.com:user/repo.git"
                                   class="input @error('repository_url') border-red-500 @enderror">
                            <div wire:loading wire:target="repository_url" class="absolute right-3 top-1/2 -translate-y-1/2">
                                <svg class="animate-spin h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Supports HTTPS or SSH format. SSH recommended for private repositories.
                        </p>
                        @error('repository_url')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="branch" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Branch *</label>
                        <div class="relative">
                            <input wire:model="branch"
                                   id="branch"
                                   type="text"
                                   required
                                   class="input">
                            <div wire:loading wire:target="branch" class="absolute right-3 top-1/2 -translate-y-1/2">
                                <svg class="animate-spin h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Framework & Runtime -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Framework & Runtime</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="framework" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Framework</label>
                        <div class="relative">
                            <select wire:model="framework"
                                    id="framework"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-50"
                                    wire:target="framework"
                                    class="input">
                                <option value="">Select Framework...</option>
                                <option value="Laravel">Laravel</option>
                                <option value="Node.js">Node.js</option>
                                <option value="React">React</option>
                                <option value="Vue">Vue.js</option>
                                <option value="Next.js">Next.js</option>
                                <option value="Django">Django</option>
                                <option value="Flask">Flask</option>
                            </select>
                            <div wire:loading wire:target="framework" class="absolute right-10 top-1/2 -translate-y-1/2">
                                <svg class="animate-spin h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="php_version" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">PHP Version</label>
                        <div class="relative">
                            <select wire:model="php_version"
                                    id="php_version"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-50"
                                    wire:target="php_version"
                                    class="input">
                                <option value="8.3">8.3</option>
                                <option value="8.2">8.2</option>
                                <option value="8.1">8.1</option>
                                <option value="8.0">8.0</option>
                            </select>
                            <div wire:loading wire:target="php_version" class="absolute right-10 top-1/2 -translate-y-1/2">
                                <svg class="animate-spin h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="node_version" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Node Version</label>
                        <div class="relative">
                            <select wire:model="node_version"
                                    id="node_version"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-50"
                                    wire:target="node_version"
                                    class="input">
                                <option value="20">20 (LTS)</option>
                                <option value="18">18 (LTS)</option>
                                <option value="16">16</option>
                            </select>
                            <div wire:loading wire:target="node_version" class="absolute right-10 top-1/2 -translate-y-1/2">
                                <svg class="animate-spin h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Build Configuration -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Build Configuration</h3>
                <div class="space-y-6">
                    <div>
                        <label for="root_directory" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Root Directory *</label>
                        <div class="relative">
                            <input wire:model="root_directory"
                                   id="root_directory"
                                   type="text"
                                   required
                                   placeholder="/"
                                   class="input">
                            <div wire:loading wire:target="root_directory" class="absolute right-3 top-1/2 -translate-y-1/2">
                                <svg class="animate-spin h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="build_command" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Build Command</label>
                        <div class="relative">
                            <input wire:model="build_command"
                                   id="build_command"
                                   type="text"
                                   placeholder="npm run build"
                                   class="input">
                            <div wire:loading wire:target="build_command" class="absolute right-3 top-1/2 -translate-y-1/2">
                                <svg class="animate-spin h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="start_command" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Command</label>
                        <div class="relative">
                            <input wire:model="start_command"
                                   id="start_command"
                                   type="text"
                                   placeholder="npm start"
                                   class="input">
                            <div wire:loading wire:target="start_command" class="absolute right-3 top-1/2 -translate-y-1/2">
                                <svg class="animate-spin h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Deployment -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Deployment</h3>
                <div class="space-y-6">

                    {{-- Deployment Method --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Deployment Method</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="flex items-start gap-3 p-4 border rounded-lg cursor-pointer transition-colors
                                {{ $deployment_method === 'docker' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-blue-300' }}">
                                <input type="radio"
                                       wire:model.live="deployment_method"
                                       value="docker"
                                       class="mt-0.5 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white text-sm">Docker</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Containerized deployment using Docker Compose</div>
                                </div>
                            </label>
                            <label class="flex items-start gap-3 p-4 border rounded-lg cursor-pointer transition-colors
                                {{ $deployment_method === 'standard' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-blue-300' }}">
                                <input type="radio"
                                       wire:model.live="deployment_method"
                                       value="standard"
                                       class="mt-0.5 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white text-sm">Bare Metal</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Direct deployment to server filesystem</div>
                                </div>
                            </label>
                        </div>
                        @error('deployment_method')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Deploy Path (bare metal only) --}}
                    @if($deployment_method === 'standard')
                        <div>
                            <label for="deploy_path" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Deploy Path</label>
                            <input wire:model="deploy_path"
                                   id="deploy_path"
                                   type="text"
                                   placeholder="/var/www/my-project"
                                   class="input @error('deploy_path') border-red-500 @enderror">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Absolute path on the server where the project will be deployed. Must start with <code class="font-mono">/var/www/</code>.
                            </p>
                            @error('deploy_path')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    {{-- Octane (Laravel only) --}}
                    @if(in_array($framework, ['Laravel', 'laravel']))
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <input wire:model.live="use_octane"
                                       id="use_octane"
                                       type="checkbox"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <div>
                                    <label for="use_octane" class="text-sm font-medium text-gray-900 dark:text-white cursor-pointer">
                                        Enable Laravel Octane
                                    </label>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">High-performance application server for Laravel</p>
                                </div>
                            </div>

                            @if($use_octane)
                                <div class="ml-7">
                                    <label for="octane_server" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Octane Server</label>
                                    <select wire:model="octane_server"
                                            id="octane_server"
                                            class="input @error('octane_server') border-red-500 @enderror" style="max-width: 280px;">
                                        <option value="frankenphp">FrankenPHP (Recommended)</option>
                                        <option value="swoole">Swoole</option>
                                        <option value="roadrunner">RoadRunner</option>
                                    </select>
                                    @error('octane_server')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif
                        </div>
                    @endif

                </div>
            </div>

            <!-- Build Pipeline -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">Build Pipeline</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Define commands that run during each deployment phase.</p>

                <div class="space-y-8">

                    {{-- Install Commands --}}
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Install Commands</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400">e.g. <code class="font-mono">composer install --no-dev</code>, <code class="font-mono">npm ci</code></p>
                            </div>
                            <button type="button"
                                    wire:click="addInstallCommand"
                                    class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Command
                            </button>
                        </div>
                        @if(count($install_commands) > 0)
                            <div class="space-y-2">
                                @foreach($install_commands as $i => $cmd)
                                    <div class="flex items-center gap-2" wire:key="install-{{ $i }}">
                                        <span class="text-xs text-gray-400 dark:text-gray-500 w-5 text-right flex-shrink-0">{{ $i + 1 }}</span>
                                        <input wire:model="install_commands.{{ $i }}"
                                               type="text"
                                               placeholder="composer install --no-dev --optimize-autoloader"
                                               class="input flex-1 font-mono text-sm">
                                        <button type="button"
                                                wire:click="removeInstallCommand({{ $i }})"
                                                class="text-red-400 hover:text-red-600 flex-shrink-0 p-1"
                                                title="Remove command">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400 dark:text-gray-500 italic py-2">No install commands. Click "Add Command" to add one.</p>
                        @endif
                    </div>

                    {{-- Build Commands --}}
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Build Commands</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400">e.g. <code class="font-mono">npm run build</code>, <code class="font-mono">php artisan config:cache</code></p>
                            </div>
                            <button type="button"
                                    wire:click="addBuildCommand"
                                    class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Command
                            </button>
                        </div>
                        @if(count($build_commands) > 0)
                            <div class="space-y-2">
                                @foreach($build_commands as $i => $cmd)
                                    <div class="flex items-center gap-2" wire:key="build-{{ $i }}">
                                        <span class="text-xs text-gray-400 dark:text-gray-500 w-5 text-right flex-shrink-0">{{ $i + 1 }}</span>
                                        <input wire:model="build_commands.{{ $i }}"
                                               type="text"
                                               placeholder="npm run build"
                                               class="input flex-1 font-mono text-sm">
                                        <button type="button"
                                                wire:click="removeBuildCommand({{ $i }})"
                                                class="text-red-400 hover:text-red-600 flex-shrink-0 p-1"
                                                title="Remove command">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400 dark:text-gray-500 italic py-2">No build commands. Click "Add Command" to add one.</p>
                        @endif
                    </div>

                    {{-- Post-Deploy Commands --}}
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Post-Deploy Commands</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400">e.g. <code class="font-mono">php artisan migrate --force</code>, <code class="font-mono">php artisan queue:restart</code></p>
                            </div>
                            <button type="button"
                                    wire:click="addPostDeployCommand"
                                    class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Command
                            </button>
                        </div>
                        @if(count($post_deploy_commands) > 0)
                            <div class="space-y-2">
                                @foreach($post_deploy_commands as $i => $cmd)
                                    <div class="flex items-center gap-2" wire:key="post-{{ $i }}">
                                        <span class="text-xs text-gray-400 dark:text-gray-500 w-5 text-right flex-shrink-0">{{ $i + 1 }}</span>
                                        <input wire:model="post_deploy_commands.{{ $i }}"
                                               type="text"
                                               placeholder="php artisan migrate --force"
                                               class="input flex-1 font-mono text-sm">
                                        <button type="button"
                                                wire:click="removePostDeployCommand({{ $i }})"
                                                class="text-red-400 hover:text-red-600 flex-shrink-0 p-1"
                                                title="Remove command">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400 dark:text-gray-500 italic py-2">No post-deploy commands. Click "Add Command" to add one.</p>
                        @endif
                    </div>

                </div>
            </div>

            <!-- Options -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Options</h3>
                <div class="flex items-center">
                    <input wire:model="auto_deploy"
                           id="auto_deploy"
                           type="checkbox"
                           wire:loading.attr="disabled"
                           wire:loading.class="opacity-50 cursor-not-allowed"
                           wire:target="auto_deploy"
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="auto_deploy" class="ml-2 block text-sm text-gray-900 dark:text-white">
                        Enable auto-deployment on git push
                    </label>
                    <div wire:loading wire:target="auto_deploy" class="ml-2">
                        <svg class="animate-spin h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- GPS Location -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">GPS Location (Optional)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="latitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Latitude</label>
                        <div class="relative">
                            <input wire:model="latitude"
                                   id="latitude"
                                   type="number"
                                   step="any"
                                   placeholder="0.0"
                                   class="input">
                            <div wire:loading wire:target="latitude" class="absolute right-3 top-1/2 -translate-y-1/2">
                                <svg class="animate-spin h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="longitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Longitude</label>
                        <div class="relative">
                            <input wire:model="longitude"
                                   id="longitude"
                                   type="number"
                                   step="any"
                                   placeholder="0.0"
                                   class="input">
                            <div wire:loading wire:target="longitude" class="absolute right-3 top-1/2 -translate-y-1/2">
                                <svg class="animate-spin h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Project Notes -->
            <div class="border-t pt-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        {{ __('labels.project_notes') }}
                    </h3>
                    <a href="{{ route('docs.show', ['category' => 'features', 'page' => 'project-notes']) }}"
                       target="_blank"
                       class="text-sm text-amber-600 dark:text-amber-400 hover:underline flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ __('buttons.learn_more') }}
                    </a>
                </div>
                <div class="relative">
                    <textarea wire:model="notes"
                              id="notes"
                              rows="4"
                              maxlength="2000"
                              placeholder="{{ __('labels.project_notes_placeholder') }}"
                              class="input w-full resize-none"></textarea>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('labels.project_notes_hint') }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            <span x-text="$wire.notes?.length || 0"></span>/2000
                        </span>
                    </div>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-6 border-t">
                <a href="{{ route('projects.index') }}" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit"
                        wire:loading.attr="disabled"
                        wire:target="updateProject"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        class="btn btn-primary">
                    <span wire:loading.remove wire:target="updateProject" class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Update Project
                    </span>
                    <span wire:loading wire:target="updateProject" class="flex items-center">
                        <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Updating Project...
                    </span>
                </button>
            </div>
        </form>
    </div>

    <!-- Danger Zone -->
    <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow p-8 border border-red-200 dark:border-red-900/50">
        <h3 class="text-lg font-medium text-red-600 dark:text-red-400 mb-1">Danger Zone</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            Deleting a project is permanent and cannot be undone. All deployments, domains, and related data will be removed.
        </p>
        <button type="button"
                wire:click="confirmDestructiveAction('deleteProject', '', 'Delete Project: {{ $project->name }}')"
                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Delete Project
        </button>
    </div>

    <!-- Password Confirmation Modal -->
    @if($showPasswordConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm" x-data>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 w-full max-w-md mx-4 border border-red-200 dark:border-red-900/50"
                 role="dialog"
                 aria-modal="true"
                 aria-labelledby="delete-dialog-title"
                 @keydown.escape.window="$wire.cancelConfirmation()">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 id="delete-dialog-title" class="text-lg font-semibold text-gray-900 dark:text-white">Confirm Deletion</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $pendingActionLabel }}</p>
                    </div>
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                    Enter your password to confirm this irreversible action.
                </p>

                <div class="mb-4">
                    <label for="confirmPassword" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Password</label>
                    <input wire:model="confirmPassword"
                           id="confirmPassword"
                           type="password"
                           placeholder="Your password"
                           class="input @error('confirmPassword') border-red-500 @enderror"
                           @keydown.enter="$wire.executeConfirmedAction()">
                    @error('confirmPassword')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-3 justify-end">
                    <button type="button"
                            wire:click="cancelConfirmation"
                            class="btn btn-secondary">
                        Cancel
                    </button>
                    <button type="button"
                            wire:click="executeConfirmedAction"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            wire:target="executeConfirmedAction"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <span wire:loading.remove wire:target="executeConfirmedAction">Confirm Delete</span>
                        <span wire:loading wire:target="executeConfirmedAction" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Deleting...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
