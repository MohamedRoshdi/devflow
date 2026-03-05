<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Create New Project</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Set up a new deployment project with auto-configuration</p>
    </div>

    <!-- Progress Steps Indicator -->
    @php
        // Map validation errors to steps
        $stepErrors = [
            1 => $errors->hasAny(['name', 'slug', 'server_id', 'repository_url', 'branch']),
            2 => $errors->hasAny(['framework', 'deployment_method', 'php_version', 'node_version', 'root_directory', 'build_command', 'start_command']),
            3 => false, // Step 3 has no required validation
            4 => false, // Step 4 is review only
        ];
    @endphp
    <div class="mb-8">
        <!-- Mobile Version: Horizontal Scroll -->
        <div class="md:hidden overflow-x-auto pb-2 -mx-4 px-4">
            <div class="flex items-center gap-3 min-w-max">
                @foreach ([
                    1 => ['title' => 'Basic Info', 'short' => 'Info', 'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    2 => ['title' => 'Framework', 'short' => 'Framework', 'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4'],
                    3 => ['title' => 'Setup Options', 'short' => 'Setup', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
                    4 => ['title' => 'Review', 'short' => 'Review', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4']
                ] as $step => $info)
                    <div class="flex items-center gap-2">
                        <button wire:click="goToStep({{ $step }})"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50"
                                wire:target="goToStep,nextStep,previousStep"
                                class="flex flex-col items-center gap-1.5 min-w-[60px] touch-manipulation active:scale-95 relative
                                    {{ $currentStep >= $step ? 'cursor-pointer' : 'cursor-not-allowed' }}">
                            {{-- Error indicator badge --}}
                            @if ($stepErrors[$step] && $currentStep !== $step)
                                <span class="absolute -top-1 -right-1 flex h-5 w-5 z-10">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex items-center justify-center rounded-full h-5 w-5 bg-red-500 text-white text-xs font-bold">!</span>
                                </span>
                            @endif
                            <div class="flex items-center justify-center w-11 h-11 rounded-full transition-all
                                {{ $stepErrors[$step] && $currentStep !== $step ? 'ring-2 ring-red-500 ring-offset-2 dark:ring-offset-gray-900' : '' }}
                                {{ $currentStep == $step ? 'bg-blue-600 text-white ring-4 ring-blue-100 dark:ring-blue-900/50' : ($currentStep > $step ? ($stepErrors[$step] ? 'bg-red-500 text-white' : 'bg-green-500 text-white') : 'bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400') }}">
                                @if ($currentStep > $step)
                                    @if ($stepErrors[$step])
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @endif
                                @else
                                    <span class="text-sm font-bold">{{ $step }}</span>
                                @endif
                            </div>
                            <span class="text-xs font-medium whitespace-nowrap {{ $stepErrors[$step] && $currentStep !== $step ? 'text-red-500 dark:text-red-400' : ($currentStep >= $step ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500') }}">
                                {{ $info['short'] }}
                            </span>
                        </button>
                        @if ($step < 4)
                            <div class="w-8 h-0.5 flex-shrink-0 {{ $currentStep > $step ? ($stepErrors[$step] ? 'bg-red-500' : 'bg-green-500') : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Desktop Version: Original Layout -->
        <div class="hidden md:flex items-center justify-between">
            @foreach ([
                1 => ['title' => 'Basic Info', 'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                2 => ['title' => 'Framework', 'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4'],
                3 => ['title' => 'Setup Options', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
                4 => ['title' => 'Review', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4']
            ] as $step => $info)
                <div class="flex items-center {{ $step < 4 ? 'flex-1' : '' }}">
                    <div class="relative">
                        {{-- Error indicator badge --}}
                        @if ($stepErrors[$step] && $currentStep !== $step)
                            <span class="absolute -top-1 -right-1 flex h-5 w-5 z-10">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex items-center justify-center rounded-full h-5 w-5 bg-red-500 text-white text-xs font-bold">!</span>
                            </span>
                        @endif
                        <button wire:click="goToStep({{ $step }})"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50"
                                wire:target="goToStep,nextStep,previousStep"
                                title="{{ $stepErrors[$step] ? 'This step has validation errors - click to fix' : $info['title'] }}"
                                class="flex items-center justify-center w-10 h-10 rounded-full transition-all
                                    {{ $stepErrors[$step] && $currentStep !== $step ? 'ring-2 ring-red-500 ring-offset-2 dark:ring-offset-gray-900' : '' }}
                                    {{ $currentStep == $step ? 'bg-blue-600 text-white' : ($currentStep > $step ? ($stepErrors[$step] ? 'bg-red-500 text-white' : 'bg-green-500 text-white') : 'bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400') }}
                                    {{ $currentStep >= $step ? 'cursor-pointer hover:scale-105' : 'cursor-not-allowed' }}">
                            @if ($currentStep > $step)
                                @if ($stepErrors[$step])
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @endif
                            @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $info['icon'] }}"/>
                                </svg>
                            @endif
                        </button>
                    </div>
                    <span class="ml-2 text-sm font-medium {{ $stepErrors[$step] && $currentStep !== $step ? 'text-red-500 dark:text-red-400' : ($currentStep >= $step ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500') }}">
                        {{ $info['title'] }}
                        @if ($stepErrors[$step] && $currentStep !== $step)
                            <span class="text-xs">(has errors)</span>
                        @endif
                    </span>
                    @if ($step < 4)
                        <div class="flex-1 h-0.5 mx-4 {{ $currentStep > $step ? ($stepErrors[$step] ? 'bg-red-500' : 'bg-green-500') : 'bg-gray-200 dark:bg-gray-700' }}"></div>
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
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-50 cursor-not-allowed"
                                    wire:target="selectTemplate"
                                    class="p-3 rounded-lg border-2 text-center transition-all
                                        {{ $selectedTemplateId == $template->id
                                            ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30'
                                            : 'border-gray-200 dark:border-gray-600 hover:border-blue-300' }}">
                                    <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $template->name }}</p>
                                </button>
                            @endforeach
                        </div>
                        @if($selectedTemplateId)
                            <button type="button"
                                wire:click="clearTemplate"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                wire:target="clearTemplate"
                                class="mt-2 text-xs text-blue-600 dark:text-blue-400 hover:underline">
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
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">URL: {{ $slug ?: 'your-project' }}.{{ config('app.base_domain', 'nilestack.duckdns.org') }}</p>
                            @error('slug') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <!-- Server Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Server *</label>
                        <div class="space-y-2 max-h-64 overflow-y-auto relative">
                            <div wire:loading wire:target="server_id" class="absolute inset-0 bg-white/50 dark:bg-gray-800/50 flex items-center justify-center z-10 rounded-lg">
                                <svg class="animate-spin h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
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

                        {{-- Server health warnings --}}
                        @if($server_id)
                            @php $selectedServer = $servers->find($server_id); @endphp
                            @if($selectedServer)
                                @if($selectedServer->status === 'offline')
                                    <div class="mt-2 flex items-center gap-2 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">
                                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                        This server is currently offline. Deployments will fail until it is back online.
                                    </div>
                                @elseif($selectedServer->status !== 'online')
                                    <div class="mt-2 flex items-center gap-2 p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-300 text-sm">
                                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                        Server is in {{ $selectedServer->status }} mode. Deployments may be affected.
                                    </div>
                                @endif
                                @if(!$selectedServer->docker_installed)
                                    <div class="mt-2 flex items-center gap-2 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300 text-sm">
                                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Docker is not installed on this server. Bare Metal deployment does not require Docker.
                                    </div>
                                @endif
                            @endif
                        @endif
                    </div>

                    <!-- Repository -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-2 relative">
                            <label for="repository_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Repository URL *</label>
                            <div class="relative">
                                <input wire:model="repository_url" id="repository_url" type="text"
                                       placeholder="https://github.com/user/repo.git"
                                       class="input @error('repository_url') border-red-500 @enderror">
                                <div wire:loading wire:target="repository_url" class="absolute right-3 top-1/2 -translate-y-1/2">
                                    <svg class="animate-spin h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>
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

                    {{-- Repository Analysis Status --}}
                    @if($analyzing)
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <div class="flex items-center">
                                <svg class="animate-spin h-5 w-5 text-blue-600 dark:text-blue-400 mr-3" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-blue-900 dark:text-blue-100">Analyzing repository...</p>
                                    <p class="text-xs text-blue-700 dark:text-blue-300 mt-0.5">Detecting framework, versions, and build configuration</p>
                                </div>
                            </div>
                        </div>
                    @elseif($analysisResult && !$analysisError)
                        <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-green-900 dark:text-green-100">Auto-detected from repository</p>
                                        <div class="mt-1.5 flex flex-wrap gap-2">
                                            @foreach($analysisResult['sources'] ?? [] as $field => $source)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-green-100 dark:bg-green-800/50 text-green-800 dark:text-green-200">
                                                    {{ ucfirst(str_replace(['phpVersion', 'nodeVersion', 'buildCommand', 'startCommand', 'suggestedDeploymentMethod'], ['PHP Version', 'Node Version', 'Build Command', 'Start Command', 'Deployment Method'], $field)) }}
                                                    <span class="mx-1 text-green-400">-</span>
                                                    {{ $source }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <button type="button" wire:click="reanalyzeRepository" wire:loading.attr="disabled" class="text-xs text-green-700 dark:text-green-300 hover:underline whitespace-nowrap ml-4">
                                    Re-analyze
                                </button>
                            </div>
                        </div>
                    @elseif($analysisError)
                        <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-yellow-900 dark:text-yellow-100">{{ $analysisError }}</p>
                                        <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-0.5">Configure the fields manually below.</p>
                                    </div>
                                </div>
                                <button type="button" wire:click="reanalyzeRepository" wire:loading.attr="disabled" class="text-xs text-yellow-700 dark:text-yellow-300 hover:underline whitespace-nowrap ml-4">
                                    Retry
                                </button>
                            </div>
                        </div>
                    @endif

                    <!-- Framework Selection (pick what you're building first) -->
                    <div>
                        <label for="framework" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Framework
                            @if(!empty($analysisResult['sources']['framework']))
                                <span class="text-xs font-normal text-green-600 dark:text-green-400 ml-1">(detected: {{ $analysisResult['framework'] }})</span>
                            @endif
                        </label>
                        <select wire:model.live="framework" id="framework" class="input">
                            @foreach($this->frameworks as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Select the framework/technology your project uses</p>
                    </div>

                    <!-- Deployment Method Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Deployment Method *</label>
                        <div class="grid grid-cols-1 {{ $this->isStandardAllowed ? 'md:grid-cols-2' : '' }} gap-4">
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
                                            Docker
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

                            <!-- Bare Metal Option (only for Laravel / no framework) -->
                            @if($this->isStandardAllowed)
                            <label class="relative flex cursor-pointer rounded-lg border p-4 shadow-sm focus:outline-none {{ $deployment_method === 'standard' ? 'border-orange-500 ring-2 ring-orange-500 bg-orange-50 dark:bg-orange-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500' }}">
                                <input type="radio" wire:model.live="deployment_method" value="standard" class="sr-only">
                                <div class="flex flex-1 items-start">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <span class="block text-sm font-semibold {{ $deployment_method === 'standard' ? 'text-orange-900 dark:text-orange-100' : 'text-gray-900 dark:text-white' }}">
                                            Bare Metal
                                        </span>
                                        <span class="mt-1 flex items-center text-xs {{ $deployment_method === 'standard' ? 'text-orange-700 dark:text-orange-300' : 'text-gray-500 dark:text-gray-400' }}">
                                            Zero-downtime deployment (Nginx + PHP-FPM)
                                        </span>
                                    </div>
                                </div>
                                @if($deployment_method === 'standard')
                                    <svg class="h-5 w-5 text-orange-600 dark:text-orange-400 absolute top-4 right-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                            </label>
                            @endif
                        </div>
                        @error('deployment_method') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- PHP & Node Versions (based on framework needs) -->
                    @if($this->needsPhp || $this->needsNode)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @if($this->needsPhp)
                            <div>
                                <label for="php_version" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    PHP Version *
                                    @if(!empty($analysisResult['sources']['phpVersion']))
                                        <span class="text-xs font-normal text-green-600 dark:text-green-400 ml-1">(detected: {{ $analysisResult['phpVersion'] }})</span>
                                    @endif
                                </label>
                                <select wire:model="php_version" id="php_version" class="input">
                                    @foreach($this->phpVersions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            @if($this->needsNode)
                            <div>
                                <label for="node_version" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Node Version
                                    @if(!empty($analysisResult['sources']['nodeVersion']))
                                        <span class="text-xs font-normal text-green-600 dark:text-green-400 ml-1">(detected: {{ $analysisResult['nodeVersion'] }})</span>
                                    @endif
                                </label>
                                <select wire:model="node_version" id="node_version" class="input">
                                    <option value="22">22 (LTS)</option>
                                    <option value="20">20 (LTS)</option>
                                    <option value="18">18</option>
                                </select>
                            </div>
                            @endif
                        </div>
                    @elseif($deployment_method === 'docker')
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-blue-900 dark:text-blue-100">Docker Deployment Selected</p>
                                    <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">Runtime versions will be defined in your <code class="px-1 py-0.5 bg-blue-100 dark:bg-blue-800 rounded text-xs">docker-compose.yml</code> or <code class="px-1 py-0.5 bg-blue-100 dark:bg-blue-800 rounded text-xs">Dockerfile</code>.</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label for="root_directory" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Root Directory *</label>
                        <input wire:model="root_directory" id="root_directory" type="text" required placeholder="/" class="input">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="build_command" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Build Command
                                @if(!empty($analysisResult['sources']['buildCommand']))
                                    <span class="text-xs font-normal text-green-600 dark:text-green-400 ml-1">(detected)</span>
                                @endif
                            </label>
                            <input wire:model="build_command" id="build_command" type="text" placeholder="npm run build" class="input">
                        </div>
                        <div>
                            <label for="start_command" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Start Command
                                @if(!empty($analysisResult['sources']['startCommand']))
                                    <span class="text-xs font-normal text-green-600 dark:text-green-400 ml-1">(detected)</span>
                                @endif
                            </label>
                            <input wire:model="start_command" id="start_command" type="text" placeholder="npm start" class="input">
                        </div>
                    </div>

                    {{-- Deploy Pipeline Commands --}}
                    @if(!empty($install_commands) || !empty($build_commands) || !empty($post_deploy_commands))
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">Deploy Pipeline</h4>
                                </div>
                                @if($analysisResult)
                                    <span class="text-xs text-green-600 dark:text-green-400">Auto-generated from repository</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Commands that run during each deployment. Edit or reorder as needed.</p>
                        </div>

                        <div class="p-4 space-y-4">
                            {{-- Install Commands --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Install Dependencies</label>
                                <div class="space-y-1.5">
                                    @foreach($install_commands as $i => $cmd)
                                        <div class="flex items-center gap-2 group">
                                            <code class="flex-1 text-sm px-3 py-1.5 bg-gray-900 dark:bg-black text-green-400 rounded font-mono">{{ $cmd }}</code>
                                            <button type="button" wire:click="removeCommand('install', {{ $i }})"
                                                    class="opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-600 transition-opacity p-1" title="Remove">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mt-1.5 flex gap-2" x-data="{ cmd: '' }">
                                    <input x-model="cmd" type="text" placeholder="Add command..." class="input text-sm flex-1"
                                           @keydown.enter.prevent="if(cmd.trim()) { $wire.addCommand('install', cmd.trim()); cmd = ''; }">
                                    <button type="button" @click="if(cmd.trim()) { $wire.addCommand('install', cmd.trim()); cmd = ''; }"
                                            class="px-3 py-1 text-xs bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600">Add</button>
                                </div>
                            </div>

                            {{-- Build Commands --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Build Assets</label>
                                <div class="space-y-1.5">
                                    @foreach($build_commands as $i => $cmd)
                                        <div class="flex items-center gap-2 group">
                                            <code class="flex-1 text-sm px-3 py-1.5 bg-gray-900 dark:bg-black text-yellow-400 rounded font-mono">{{ $cmd }}</code>
                                            <button type="button" wire:click="removeCommand('build', {{ $i }})"
                                                    class="opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-600 transition-opacity p-1" title="Remove">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mt-1.5 flex gap-2" x-data="{ cmd: '' }">
                                    <input x-model="cmd" type="text" placeholder="Add command..." class="input text-sm flex-1"
                                           @keydown.enter.prevent="if(cmd.trim()) { $wire.addCommand('build', cmd.trim()); cmd = ''; }">
                                    <button type="button" @click="if(cmd.trim()) { $wire.addCommand('build', cmd.trim()); cmd = ''; }"
                                            class="px-3 py-1 text-xs bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600">Add</button>
                                </div>
                            </div>

                            {{-- Post-Deploy Commands --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Post-Deploy</label>
                                <div class="space-y-1.5">
                                    @foreach($post_deploy_commands as $i => $cmd)
                                        <div class="flex items-center gap-2 group">
                                            <code class="flex-1 text-sm px-3 py-1.5 bg-gray-900 dark:bg-black text-blue-400 rounded font-mono">{{ $cmd }}</code>
                                            <button type="button" wire:click="removeCommand('post_deploy', {{ $i }})"
                                                    class="opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-600 transition-opacity p-1" title="Remove">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mt-1.5 flex gap-2" x-data="{ cmd: '' }">
                                    <input x-model="cmd" type="text" placeholder="Add command..." class="input text-sm flex-1"
                                           @keydown.enter.prevent="if(cmd.trim()) { $wire.addCommand('post_deploy', cmd.trim()); cmd = ''; }">
                                    <button type="button" @click="if(cmd.trim()) { $wire.addCommand('post_deploy', cmd.trim()); cmd = ''; }"
                                            class="px-3 py-1 text-xs bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600">Add</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
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

                    <!-- Project Notes -->
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
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
                                <button type="button"
                                        wire:click="goToStep(1)"
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-50 cursor-not-allowed"
                                        wire:target="goToStep"
                                        class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                    Edit
                                </button>
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
                                <button type="button"
                                        wire:click="goToStep(2)"
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-50 cursor-not-allowed"
                                        wire:target="goToStep"
                                        class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                    Edit
                                </button>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div><span class="text-gray-500 dark:text-gray-400">Deployment:</span> <span class="text-gray-900 dark:text-white font-semibold">{{ $deployment_method === 'docker' ? 'Docker' : 'Bare Metal' }}</span></div>
                                <div><span class="text-gray-500 dark:text-gray-400">Framework:</span> <span class="text-gray-900 dark:text-white">{{ $framework ?: 'Not specified' }}</span></div>
                                @if($this->needsPhp)
                                    <div><span class="text-gray-500 dark:text-gray-400">PHP:</span> <span class="text-gray-900 dark:text-white">{{ $php_version }}</span></div>
                                @endif
                                @if($this->needsNode)
                                    <div><span class="text-gray-500 dark:text-gray-400">Node:</span> <span class="text-gray-900 dark:text-white">{{ $node_version }}</span></div>
                                @endif
                                <div><span class="text-gray-500 dark:text-gray-400">Root:</span> <span class="text-gray-900 dark:text-white">{{ $root_directory }}</span></div>
                            </div>
                        </div>

                        <!-- Setup Options Summary -->
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="font-medium text-gray-900 dark:text-white">Auto-Setup Features</h4>
                                <button type="button"
                                        wire:click="goToStep(3)"
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-50 cursor-not-allowed"
                                        wire:target="goToStep"
                                        class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                    Edit
                                </button>
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
                        <button type="button"
                                wire:click="previousStep"
                                wire:loading.attr="disabled"
                                wire:target="previousStep,nextStep,createProject"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                class="btn btn-secondary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                 wire:loading.remove wire:target="previousStep">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            <svg wire:loading wire:target="previousStep" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Previous
                        </button>
                    @else
                        <a href="{{ route('projects.index') }}" class="btn btn-secondary">Cancel</a>
                    @endif
                </div>

                <div>
                    @if ($currentStep < $totalSteps)
                        <button type="button"
                                wire:click="nextStep"
                                wire:loading.attr="disabled"
                                wire:target="previousStep,nextStep,createProject"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                class="btn btn-primary">
                            <span wire:loading.remove wire:target="nextStep">Next</span>
                            <span wire:loading wire:target="nextStep" class="flex items-center">
                                <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Loading...
                            </span>
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                 wire:loading.remove wire:target="nextStep">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    @else
                        <button type="submit"
                                wire:loading.attr="disabled"
                                wire:target="createProject"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                class="btn btn-primary bg-green-600 hover:bg-green-700">
                            <span wire:loading.remove wire:target="createProject" class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Create Project
                            </span>
                            <span wire:loading wire:target="createProject" class="flex items-center">
                                <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Creating Project...
                            </span>
                        </button>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Setup Progress Modal -->
    @if ($showProgressModal && $createdProjectId)
        @php $progress = $this->setupProgress; $setupDone = in_array($progress['status'] ?? '', ['completed', 'failed'], true); @endphp
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
             wire:poll.2s="$refresh"
             x-data="{ done: @js($setupDone), redirecting: false }"
             x-init="$watch('done', value => {
                 if (value && !redirecting) {
                     redirecting = true;
                     setTimeout(() => $wire.closeProgressAndRedirect(), 1500);
                 }
             })"
             x-effect="done = @js($setupDone)">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-3
                        {{ $setupDone ? 'bg-green-100 dark:bg-green-900/30' : 'bg-blue-100 dark:bg-blue-900/30' }}">
                        @if($setupDone)
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $setupDone ? 'Setup Complete' : 'Setting Up Project' }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $setupDone ? 'Redirecting to project page...' : 'Configuring your project features...' }}
                        </p>
                    </div>
                </div>

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
                                <span class="text-sm {{ $task['status'] === 'completed' ? 'text-green-600 dark:text-green-400' : ($task['status'] === 'failed' ? 'text-red-600 dark:text-red-400' : ($task['status'] === 'running' ? 'text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400')) }}">
                                    {{ $task['label'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="flex justify-end">
                    <button wire:click="closeProgressAndRedirect"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            wire:target="closeProgressAndRedirect"
                            class="btn btn-primary">
                        <span wire:loading.remove wire:target="closeProgressAndRedirect">
                            {{ $setupDone ? 'View Project' : 'Continue Anyway' }}
                        </span>
                        <span wire:loading wire:target="closeProgressAndRedirect" class="flex items-center">
                            <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Loading...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
