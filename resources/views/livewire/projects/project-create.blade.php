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

                    <!-- Deployment Method Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Deployment Method *</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Docker Option -->
                            <label class="relative flex cursor-pointer rounded-lg border p-4 shadow-sm focus:outline-none {{ $deployment_method === 'docker' ? 'border-emerald-500 ring-2 ring-emerald-500 bg-emerald-50 dark:bg-emerald-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500' }}">
                                <input type="radio" wire:model.live="deployment_method" value="docker" class="sr-only">
                                <div class="flex flex-1 items-start">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M13.983 11.078h2.119a.186.186 0 00.186-.185V9.006a.186.186 0 00-.186-.186h-2.119a.185.185 0 00-.185.185v1.888c0 .102.083.185.185.185m-2.954-5.43h2.118a.186.186 0 00.186-.186V3.574a.186.186 0 00-.186-.185h-2.118a.185.185 0 00-.185.185v1.888c0 .102.082.185.185.185m0 2.716h2.118a.187.187 0 00.186-.186V6.29a.186.186 0 00-.186-.185h-2.118a.185.185 0 00-.185.185v1.887c0 .102.082.185.185.186m-2.93 0h2.12a.186.186 0 00.184-.186V6.29a.185.185 0 00-.185-.185H8.1a.185.185 0 00-.185.185v1.887c0 .102.083.185.185.186m-2.964 0h2.119a.186.186 0 00.185-.186V6.29a.185.185 0 00-.185-.185H5.136a.186.186 0 00-.186.185v1.887c0 .102.084.185.186.186m5.893 2.715h2.118a.186.186 0 00.186-.185V9.006a.186.186 0 00-.186-.186h-2.118a.185.185 0 00-.185.185v1.888c0 .102.082.185.185.185m-2.93 0h2.12a.185.185 0 00.184-.185V9.006a.185.185 0 00-.184-.186h-2.12a.185.185 0 00-.184.185v1.888c0 .102.083.185.185.185m-2.964 0h2.119a.185.185 0 00.185-.185V9.006a.185.185 0 00-.184-.186h-2.12a.186.186 0 00-.186.186v1.887c0 .102.084.185.186.185m-2.92 0h2.12a.185.185 0 00.184-.185V9.006a.185.185 0 00-.184-.186h-2.12a.185.185 0 00-.184.185v1.888c0 .102.082.185.185.185M23.763 9.89c-.065-.051-.672-.51-1.954-.51-.338 0-.676.03-1.01.087-.239-1.316-.988-2.438-2.233-3.325l-.31-.216-.31.216c-.436.305-.802.67-1.085 1.084a3.97 3.97 0 00-.606 2.072c0 .524.09 1.031.267 1.508-1.271.73-2.62.658-2.668.658H.388l-.03.32a6.61 6.61 0 00.534 3.94l.05.104.024.049c.707 1.283 1.948 2.193 3.635 2.672.238.068.475.124.713.167l-.003.045c0 .001.002.004.004.006.17.017.344.025.52.033.168.008.334.014.501.014 1.95 0 3.726-.34 5.275-.897a13.63 13.63 0 003.74-2.252c2.028-1.896 3.162-4.196 3.275-6.643h.148c1.224 0 1.983-.418 2.317-.673a3.07 3.07 0 00.68-.847l.082-.165z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <span class="block text-sm font-semibold {{ $deployment_method === 'docker' ? 'text-emerald-900 dark:text-emerald-100' : 'text-gray-900 dark:text-white' }}">
                                            🐳 Docker
                                        </span>
                                        <span class="mt-1 flex items-center text-xs {{ $deployment_method === 'docker' ? 'text-emerald-700 dark:text-emerald-300' : 'text-gray-500 dark:text-gray-400' }}">
                                            Uses docker-compose.yml from your repository
                                        </span>
                                    </div>
                                </div>
                                @if($deployment_method === 'docker')
                                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400 absolute top-4 right-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                            </label>

                            <!-- Standard Laravel Option -->
                            <label class="relative flex cursor-pointer rounded-lg border p-4 shadow-sm focus:outline-none {{ $deployment_method === 'standard' ? 'border-red-500 ring-2 ring-red-500 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500' }}">
                                <input type="radio" wire:model.live="deployment_method" value="standard" class="sr-only">
                                <div class="flex flex-1 items-start">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M23.642 5.43a.364.364 0 01.014.1v5.149c0 .135-.073.26-.189.326l-4.323 2.49v4.934a.378.378 0 01-.188.326L9.93 23.949a.316.316 0 01-.066.027c-.008.002-.016.008-.024.01a.348.348 0 01-.192 0c-.011-.002-.02-.008-.03-.012-.02-.008-.042-.014-.062-.025L.533 18.755a.376.376 0 01-.189-.326V2.974c0-.033.005-.066.014-.098.003-.012.01-.02.014-.032a.369.369 0 01.023-.058c.004-.013.015-.022.023-.033l.033-.045c.012-.01.025-.018.037-.027.014-.012.027-.024.041-.034H.53L5.043.05a.375.375 0 01.375 0L9.93 2.647h.002c.015.01.027.021.04.033l.038.027c.013.014.02.03.033.045.008.011.02.021.025.033.01.02.017.038.024.058.003.011.01.021.013.032.01.031.014.064.014.098v9.652l3.76-2.164V5.527c0-.033.004-.066.013-.098.003-.01.01-.02.013-.032a.487.487 0 01.024-.059c.007-.012.018-.02.025-.033.012-.015.021-.03.033-.043.012-.012.025-.02.037-.028.014-.01.026-.023.041-.032h.001l4.513-2.598a.375.375 0 01.375 0l4.513 2.598c.016.01.027.021.042.031.012.01.025.018.036.028.013.014.022.03.034.044.008.012.019.021.024.033.011.02.018.04.024.06.006.01.012.021.015.032zm-.74 5.032V6.179l-1.578.908-2.182 1.256v4.283zm-4.51 7.75v-4.287l-2.147 1.225-6.126 3.498v4.325zM1.093 3.624v14.588l8.273 4.761v-4.325l-4.322-2.445-.002-.003H5.04c-.014-.01-.025-.021-.04-.031-.011-.01-.024-.018-.035-.027l-.001-.002c-.013-.012-.021-.025-.031-.04-.01-.011-.021-.022-.028-.036h-.002c-.008-.014-.013-.031-.02-.047-.006-.016-.014-.027-.018-.043a.49.49 0 01-.008-.057c-.002-.014-.006-.027-.006-.041V5.789L1.093 3.624zm4.513-.908L3.36 1.56.813 2.716l2.246 1.297zm.375 6.873l2.182-1.256V3.624l-1.578.908-2.182 1.256v4.283zm9.134-8.128L12.869 0l-2.246 1.297 2.246 1.297zm.376 6.873l2.182-1.256V3.624l-1.578.908-2.182 1.256v4.283zM9.93 16.044l5.91-3.373-2.182-1.256-4.323 2.49z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <span class="block text-sm font-semibold {{ $deployment_method === 'standard' ? 'text-red-900 dark:text-red-100' : 'text-gray-900 dark:text-white' }}">
                                            🔧 Standard Laravel
                                        </span>
                                        <span class="mt-1 flex items-center text-xs {{ $deployment_method === 'standard' ? 'text-red-700 dark:text-red-300' : 'text-gray-500 dark:text-gray-400' }}">
                                            Traditional deployment (Nginx + PHP-FPM)
                                        </span>
                                    </div>
                                </div>
                                @if($deployment_method === 'standard')
                                    <svg class="h-5 w-5 text-red-600 dark:text-red-400 absolute top-4 right-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                            </label>
                        </div>
                        @error('deployment_method') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
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
                                <div><span class="text-gray-500 dark:text-gray-400">Deployment:</span> <span class="text-gray-900 dark:text-white font-semibold">{{ $deployment_method === 'docker' ? '🐳 Docker' : '🔧 Standard Laravel' }}</span></div>
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
