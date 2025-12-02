<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Create New Project</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Set up a new deployment project with auto-configuration</p>
    </div>

    <!-- Progress Steps Indicator -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            @foreach ([
                1 => ['title' => 'Basic Info', 'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                2 => ['title' => 'Framework', 'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4'],
                3 => ['title' => 'Setup Options', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
                4 => ['title' => 'Review', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4']
            ] as $step => $info)
                <div class="flex items-center {{ $step < 4 ? 'flex-1' : '' }}">
                    <button wire:click="goToStep({{ $step }})"
                            class="flex items-center justify-center w-10 h-10 rounded-full transition-all
                                {{ $currentStep == $step ? 'bg-blue-600 text-white' : ($currentStep > $step ? 'bg-green-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400') }}
                                {{ $currentStep >= $step ? 'cursor-pointer hover:scale-105' : 'cursor-not-allowed' }}">
                        @if ($currentStep > $step)
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $info['icon'] }}"/>
                            </svg>
                        @endif
                    </button>
                    <span class="ml-2 text-sm font-medium {{ $currentStep >= $step ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500' }}">
                        {{ $info['title'] }}
                    </span>
                    @if ($step < 4)
                        <div class="flex-1 h-0.5 mx-4 {{ $currentStep > $step ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8">
        <form wire:submit="createProject">
            <!-- Step 1: Basic Info -->
            @if ($currentStep === 1)
                <div class="space-y-6" x-data x-init="$el.querySelector('input')?.focus()">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Basic Information</h2>
                            <p class="text-gray-500 dark:text-gray-400 text-sm">Project name, server selection, and repository details</p>
                        </div>
                    </div>

                    <!-- Template Selection -->
                    @if($templates->count() > 0)
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Quick Start with Template</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            @foreach($templates as $template)
                                <button type="button"
                                    wire:click="selectTemplate({{ $template->id }})"
                                    class="p-3 rounded-lg border-2 text-center transition-all
                                        {{ $selectedTemplateId == $template->id
                                            ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30'
                                            : 'border-gray-200 dark:border-gray-600 hover:border-blue-300' }}">
                                    <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $template->name }}</p>
                                </button>
                            @endforeach
                        </div>
                        @if($selectedTemplateId)
                            <button type="button" wire:click="clearTemplate" class="mt-2 text-xs text-blue-600 dark:text-blue-400 hover:underline">
                                Clear template selection
                            </button>
                        @endif
                    </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Project Name *</label>
                            <input wire:model.live="name" id="name" type="text" required placeholder="My Awesome Project"
                                   class="input @error('name') border-red-500 @enderror">
                            @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Slug *</label>
                            <input wire:model="slug" id="slug" type="text" required placeholder="my-awesome-project"
                                   class="input @error('slug') border-red-500 @enderror">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">URL: {{ $slug }}.nilestack.duckdns.org</p>
                            @error('slug') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- Server Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Server *</label>
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            @forelse($servers as $server)
                                <label class="flex items-center p-3 border rounded-lg cursor-pointer transition-all
                                    {{ $server_id == $server->id ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30' : 'border-gray-200 dark:border-gray-700 hover:border-blue-300' }}">
                                    <input type="radio" wire:model="server_id" value="{{ $server->id }}" class="h-4 w-4 text-blue-600">
                                    <div class="ml-3 flex-1">
                                        <div class="flex items-center">
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $server->name }}</span>
                                            <span class="ml-2 px-2 py-0.5 rounded text-xs
                                                @if($server->status === 'online') bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300
                                                @else bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300 @endif">
                                                {{ ucfirst($server->status) }}
                                            </span>
                                        </div>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $server->ip_address }}</span>
                                    </div>
                                </label>
                            @empty
                                <p class="text-yellow-600 text-sm p-4 bg-yellow-50 dark:bg-yellow-900/30 rounded-lg">
                                    No servers available. <a href="{{ route('servers.create') }}" class="underline font-medium">Add a server first</a>
                                </p>
                            @endforelse
                        </div>
                        @error('server_id') <p class="text-red-500 text-sm mt-2">{{ $message }}</p> @enderror
                    </div>

                    <!-- Repository -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-2">
                            <label for="repository_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Repository URL *</label>
                            <input wire:model="repository_url" id="repository_url" type="text"
                                   placeholder="https://github.com/user/repo.git"
                                   class="input @error('repository_url') border-red-500 @enderror">
                            @error('repository_url') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="branch" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Branch *</label>
                            <input wire:model="branch" id="branch" type="text" required class="input">
                        </div>
                    </div>
                </div>
            @endif

            <!-- Step 2: Framework & Build -->
            @if ($currentStep === 2)
                <div class="space-y-6">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Framework & Build</h2>
                            <p class="text-gray-500 dark:text-gray-400 text-sm">Configure runtime versions and build commands</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="framework" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Framework</label>
                            <select wire:model="framework" id="framework" class="input">
                                @foreach($this->frameworks as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="php_version" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">PHP Version</label>
                            <select wire:model="php_version" id="php_version" class="input">
                                @foreach($this->phpVersions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="node_version" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Node Version</label>
                            <select wire:model="node_version" id="node_version" class="input">
                                <option value="20">20 (LTS)</option>
                                <option value="18">18 (LTS)</option>
                                <option value="16">16</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="root_directory" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Root Directory *</label>
                        <input wire:model="root_directory" id="root_directory" type="text" required placeholder="/" class="input">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="build_command" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Build Command</label>
                            <input wire:model="build_command" id="build_command" type="text" placeholder="npm run build" class="input">
                        </div>
                        <div>
                            <label for="start_command" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Command</label>
                            <input wire:model="start_command" id="start_command" type="text" placeholder="npm start" class="input">
                        </div>
                    </div>
                </div>
            @endif

            <!-- Step 3: Setup Options -->
            @if ($currentStep === 3)
                <div class="space-y-6">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Setup Options</h2>
                            <p class="text-gray-500 dark:text-gray-400 text-sm">Configure automatic setup features for your project</p>
                        </div>
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                        These features will be automatically configured after your project is created. You can change these settings later in project configuration.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- SSL Certificate -->
                        <label class="flex items-start p-4 border rounded-lg cursor-pointer transition-all hover:border-blue-300
                            {{ $enableSsl ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700' }}">
                            <input type="checkbox" wire:model="enableSsl" class="mt-1 h-4 w-4 text-blue-600 rounded">
                            <div class="ml-3">
                                <span class="font-medium text-gray-900 dark:text-white">SSL Certificate</span>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Auto-configure HTTPS with free SSL</p>
                            </div>
                        </label>

                        <!-- Git Webhooks -->
                        <label class="flex items-start p-4 border rounded-lg cursor-pointer transition-all hover:border-blue-300
                            {{ $enableWebhooks ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700' }}">
                            <input type="checkbox" wire:model="enableWebhooks" class="mt-1 h-4 w-4 text-blue-600 rounded">
                            <div class="ml-3">
                                <span class="font-medium text-gray-900 dark:text-white">Git Webhooks</span>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Auto-deploy on git push</p>
                            </div>
                        </label>

                        <!-- Health Checks -->
                        <label class="flex items-start p-4 border rounded-lg cursor-pointer transition-all hover:border-blue-300
                            {{ $enableHealthChecks ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700' }}">
                            <input type="checkbox" wire:model="enableHealthChecks" class="mt-1 h-4 w-4 text-blue-600 rounded">
                            <div class="ml-3">
                                <span class="font-medium text-gray-900 dark:text-white">Health Checks</span>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Monitor uptime every 5 minutes</p>
                            </div>
                        </label>

                        <!-- Database Backups -->
                        <label class="flex items-start p-4 border rounded-lg cursor-pointer transition-all hover:border-blue-300
                            {{ $enableBackups ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700' }}">
                            <input type="checkbox" wire:model="enableBackups" class="mt-1 h-4 w-4 text-blue-600 rounded">
                            <div class="ml-3">
                                <span class="font-medium text-gray-900 dark:text-white">Database Backups</span>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Daily automated backups</p>
                            </div>
                        </label>

                        <!-- Notifications -->
                        <label class="flex items-start p-4 border rounded-lg cursor-pointer transition-all hover:border-blue-300
                            {{ $enableNotifications ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700' }}">
                            <input type="checkbox" wire:model="enableNotifications" class="mt-1 h-4 w-4 text-blue-600 rounded">
                            <div class="ml-3">
                                <span class="font-medium text-gray-900 dark:text-white">Notifications</span>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Get alerts for important events</p>
                            </div>
                        </label>

                        <!-- Auto Deploy -->
                        <label class="flex items-start p-4 border rounded-lg cursor-pointer transition-all hover:border-blue-300
                            {{ $enableAutoDeploy ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700' }}">
                            <input type="checkbox" wire:model="enableAutoDeploy" class="mt-1 h-4 w-4 text-blue-600 rounded">
                            <div class="ml-3">
                                <span class="font-medium text-gray-900 dark:text-white">Initial Deployment</span>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Deploy immediately after setup</p>
                            </div>
                        </label>
                    </div>
                </div>
            @endif

            <!-- Step 4: Review -->
            @if ($currentStep === 4)
                <div class="space-y-6">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Review & Create</h2>
                            <p class="text-gray-500 dark:text-gray-400 text-sm">Confirm your project configuration</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <!-- Basic Info Summary -->
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="font-medium text-gray-900 dark:text-white">Basic Information</h4>
                                <button type="button" wire:click="goToStep(1)" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">Edit</button>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div><span class="text-gray-500 dark:text-gray-400">Name:</span> <span class="text-gray-900 dark:text-white">{{ $name }}</span></div>
                                <div><span class="text-gray-500 dark:text-gray-400">Slug:</span> <span class="text-gray-900 dark:text-white">{{ $slug }}</span></div>
                                <div><span class="text-gray-500 dark:text-gray-400">Server:</span> <span class="text-gray-900 dark:text-white">{{ $servers->find($server_id)?->name ?? 'Not selected' }}</span></div>
                                <div><span class="text-gray-500 dark:text-gray-400">Branch:</span> <span class="text-gray-900 dark:text-white">{{ $branch }}</span></div>
                                <div class="col-span-2"><span class="text-gray-500 dark:text-gray-400">Repository:</span> <span class="text-gray-900 dark:text-white text-xs break-all">{{ $repository_url }}</span></div>
                            </div>
                        </div>

                        <!-- Framework Summary -->
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="font-medium text-gray-900 dark:text-white">Framework & Build</h4>
                                <button type="button" wire:click="goToStep(2)" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">Edit</button>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div><span class="text-gray-500 dark:text-gray-400">Framework:</span> <span class="text-gray-900 dark:text-white">{{ $framework ?: 'Not specified' }}</span></div>
                                <div><span class="text-gray-500 dark:text-gray-400">PHP:</span> <span class="text-gray-900 dark:text-white">{{ $php_version }}</span></div>
                                <div><span class="text-gray-500 dark:text-gray-400">Node:</span> <span class="text-gray-900 dark:text-white">{{ $node_version }}</span></div>
                                <div><span class="text-gray-500 dark:text-gray-400">Root:</span> <span class="text-gray-900 dark:text-white">{{ $root_directory }}</span></div>
                            </div>
                        </div>

                        <!-- Setup Options Summary -->
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="font-medium text-gray-900 dark:text-white">Auto-Setup Features</h4>
                                <button type="button" wire:click="goToStep(3)" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">Edit</button>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if($enableSsl)
                                    <span class="px-2 py-1 bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300 text-xs rounded-full">SSL Certificate</span>
                                @endif
                                @if($enableWebhooks)
                                    <span class="px-2 py-1 bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300 text-xs rounded-full">Git Webhooks</span>
                                @endif
                                @if($enableHealthChecks)
                                    <span class="px-2 py-1 bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300 text-xs rounded-full">Health Checks</span>
                                @endif
                                @if($enableBackups)
                                    <span class="px-2 py-1 bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300 text-xs rounded-full">Database Backups</span>
                                @endif
                                @if($enableNotifications)
                                    <span class="px-2 py-1 bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300 text-xs rounded-full">Notifications</span>
                                @endif
                                @if($enableAutoDeploy)
                                    <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300 text-xs rounded-full">Initial Deployment</span>
                                @endif
                                @if(!$enableSsl && !$enableWebhooks && !$enableHealthChecks && !$enableBackups && !$enableNotifications && !$enableAutoDeploy)
                                    <span class="text-sm text-gray-500 dark:text-gray-400">No auto-setup features selected</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Navigation Buttons -->
            <div class="flex items-center justify-between pt-6 mt-6 border-t border-gray-200 dark:border-gray-700">
                <div>
                    @if ($currentStep > 1)
                        <button type="button" wire:click="previousStep" class="btn btn-secondary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Previous
                        </button>
                    @else
                        <a href="{{ route('projects.index') }}" class="btn btn-secondary">Cancel</a>
                    @endif
                </div>

                <div>
                    @if ($currentStep < $totalSteps)
                        <button type="button" wire:click="nextStep" class="btn btn-primary">
                            Next
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    @else
                        <button type="submit" wire:loading.attr="disabled" wire:target="createProject"
                                class="btn btn-primary bg-green-600 hover:bg-green-700 disabled:opacity-50">
                            <span wire:loading.remove wire:target="createProject">Create Project</span>
                            <span wire:loading wire:target="createProject">Creating...</span>
                        </button>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Setup Progress Modal -->
    @if ($showProgressModal && $createdProjectId)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:poll.2s="$refresh">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Setting Up Project</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Configuring your project features...</p>
                    </div>
                </div>

                @php $progress = $this->setupProgress; @endphp
                @if (!empty($progress['tasks']))
                    <div class="space-y-3 mb-6">
                        @foreach ($progress['tasks'] as $task)
                            <div class="flex items-center">
                                @if ($task['status'] === 'completed')
                                    <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @elseif ($task['status'] === 'running')
                                    <svg class="w-5 h-5 text-blue-500 animate-spin mr-3" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                @elseif ($task['status'] === 'failed')
                                    <svg class="w-5 h-5 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @else
                                    <div class="w-5 h-5 rounded-full border-2 border-gray-300 dark:border-gray-600 mr-3"></div>
                                @endif
                                <span class="text-sm {{ $task['status'] === 'completed' ? 'text-green-600 dark:text-green-400' : ($task['status'] === 'running' ? 'text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400') }}">
                                    {{ $task['label'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="flex justify-end">
                    <button wire:click="closeProgressAndRedirect"
                            class="btn btn-primary {{ ($progress['status'] ?? '') !== 'completed' && ($progress['status'] ?? '') !== 'failed' ? 'opacity-50' : '' }}">
                        {{ ($progress['status'] ?? '') === 'completed' || ($progress['status'] ?? '') === 'failed' ? 'View Project' : 'Continue Anyway' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
