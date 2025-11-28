<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-white">Create New Project</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Set up a new deployment project</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8">
        <form wire:submit="createProject" class="space-y-8">
            <!-- Template Selection -->
            @if($templates->count() > 0)
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Choose a Template</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Select a template to pre-configure your project settings</p>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($templates as $template)
                        <button type="button"
                            wire:click="selectTemplate({{ $template->id }})"
                            class="p-4 rounded-xl border-2 text-center transition-all hover:shadow-md
                                {{ $selectedTemplateId == $template->id
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 ring-2 ring-blue-500'
                                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}">
                            <div class="w-12 h-12 mx-auto mb-2 rounded-lg flex items-center justify-center
                                @switch($template->color)
                                    @case('red') bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 @break
                                    @case('green') bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400 @break
                                    @case('blue') bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 @break
                                    @case('purple') bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400 @break
                                    @case('orange') bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400 @break
                                    @case('cyan') bg-cyan-100 text-cyan-600 dark:bg-cyan-900/30 dark:text-cyan-400 @break
                                    @case('emerald') bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 @break
                                    @default bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400
                                @endswitch">
                                @switch($template->icon)
                                    @case('laravel')
                                        <svg class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor"><path d="M23.644 5.43c.009.032.014.065.014.099v5.15c0 .135-.073.26-.189.326l-4.323 2.49v4.934c0 .135-.072.258-.188.326l-9.033 5.21a.37.37 0 0 1-.091.04c-.01.003-.021.003-.032.007-.02.006-.041.01-.062.01s-.042-.004-.062-.01c-.012-.004-.022-.004-.032-.008a.35.35 0 0 1-.09-.039L.47 18.756a.377.377 0 0 1-.188-.326V4.93c0-.034.005-.067.014-.098.004-.013.014-.023.019-.035.01-.024.019-.049.034-.07.01-.014.027-.024.04-.036.015-.015.027-.032.044-.044l4.512-2.604a.38.38 0 0 1 .376 0l4.513 2.604c.017.012.03.03.044.044.013.012.03.022.04.036.014.021.023.046.033.07.006.012.016.022.02.035.009.032.013.065.013.099v9.645l3.758-2.166V7.43c0-.034.005-.067.014-.098.003-.013.014-.023.018-.035.01-.024.02-.049.034-.07.011-.014.028-.024.04-.036a.396.396 0 0 1 .045-.044l4.512-2.604a.378.378 0 0 1 .376 0l4.513 2.604c.017.012.03.03.044.044.013.012.03.022.04.036.015.021.024.046.034.07.005.012.015.022.019.035zM22.9 10.57V6.204l-1.58.911-2.178 1.255v4.366l3.758-2.166zm-4.512 7.763v-4.372l-2.14 1.227-6.13 3.514v4.414l8.27-4.783zM1.035 5.604v12.86l8.27 4.782v-4.414L4.8 16.188l-.005-.002-.004-.003c-.016-.01-.03-.028-.043-.043-.012-.012-.028-.02-.039-.034l-.003-.005a.35.35 0 0 1-.032-.068c-.006-.013-.017-.022-.021-.037a.34.34 0 0 1-.013-.094V7.37l-2.178-1.255-1.58-.911z"/></svg>
                                        @break
                                    @case('nodejs')
                                        <svg class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.985c-.275 0-.532-.074-.772-.202l-2.439-1.448c-.365-.203-.182-.276-.072-.314.496-.165.588-.201 1.101-.493.056-.027.129-.018.185.014l1.87 1.12c.074.036.166.036.221 0l7.319-4.237c.074-.036.11-.11.11-.202V7.768c0-.091-.036-.165-.11-.201l-7.319-4.219c-.073-.037-.165-.037-.221 0L4.552 7.566c-.073.036-.11.129-.11.201v8.457c0 .073.037.166.11.202l2 1.157c1.082.548 1.762-.095 1.762-.735V8.502c0-.11.091-.221.22-.221h.936c.108 0 .22.092.22.221v8.347c0 1.449-.789 2.294-2.164 2.294-.422 0-.752 0-1.688-.46l-1.925-1.099a1.55 1.55 0 0 1-.771-1.34V7.786c0-.55.293-1.064.771-1.339l7.316-4.237a1.637 1.637 0 0 1 1.544 0l7.317 4.237c.479.274.771.789.771 1.339v8.458c0 .549-.293 1.063-.771 1.34l-7.317 4.236c-.241.11-.516.165-.773.165zm2.256-5.816c-3.21 0-3.87-1.468-3.87-2.714 0-.11.092-.221.22-.221h.954c.11 0 .201.073.201.184.147.971.568 1.449 2.514 1.449 1.54 0 2.202-.35 2.202-1.175 0-.477-.183-.825-2.587-1.063-1.999-.2-3.246-.642-3.246-2.238 0-1.485 1.247-2.366 3.339-2.366 2.347 0 3.503.809 3.649 2.568a.297.297 0 0 1-.055.166.22.22 0 0 1-.165.073h-.953a.212.212 0 0 1-.202-.164c-.221-1.012-.789-1.34-2.292-1.34-1.689 0-1.891.587-1.891 1.027 0 .531.238.696 2.514.99 2.256.293 3.32.715 3.32 2.294-.018 1.615-1.339 2.531-3.652 2.531z"/></svg>
                                        @break
                                    @case('nextjs')
                                        <svg class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.355 0 0 5.355 0 12s5.355 12 12 12c6.599 0 12-5.355 12-12S18.645 0 12 0zm-1.063 18.75V9.469l6.75 9.281h-1.313l-5.437-7.5v6.75h-.938v-7.219L4.5 18.75h-.937L9 10.5V6.562h.937v12.188z"/></svg>
                                        @break
                                    @case('vuejs')
                                        <svg class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor"><path d="M24 1.61h-9.94L12 5.16 9.94 1.61H0l12 20.78L24 1.61zM12 14.08L5.16 2.23h4.43L12 6.41l2.41-4.18h4.43L12 14.08z"/></svg>
                                        @break
                                    @case('python')
                                        <svg class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor"><path d="M14.25.18l.9.2.73.26.59.3.45.32.34.34.25.34.16.33.1.3.04.26.02.2-.01.13V8.5l-.05.63-.13.55-.21.46-.26.38-.3.31-.33.25-.35.19-.35.14-.33.1-.3.07-.26.04-.21.02H8.77l-.69.05-.59.14-.5.22-.41.27-.33.32-.27.35-.2.36-.15.37-.1.35-.07.32-.04.27-.02.21v3.06H3.17l-.21-.03-.28-.07-.32-.12-.35-.18-.36-.26-.36-.36-.35-.46-.32-.59-.28-.73-.21-.88-.14-1.05-.05-1.23.06-1.22.16-1.04.24-.87.32-.71.36-.57.4-.44.42-.33.42-.24.4-.16.36-.1.32-.05.24-.01h.16l.06.01h8.16v-.83H6.18l-.01-2.75-.02-.37.05-.34.11-.31.17-.28.25-.26.31-.23.38-.2.44-.18.51-.15.58-.12.64-.1.71-.06.77-.04.84-.02 1.27.05zm-6.3 1.98l-.23.33-.08.41.08.41.23.34.33.22.41.09.41-.09.33-.22.23-.34.08-.41-.08-.41-.23-.33-.33-.22-.41-.09-.41.09zm13.09 3.95l.28.06.32.12.35.18.36.27.36.35.35.47.32.59.28.73.21.88.14 1.04.05 1.23-.06 1.23-.16 1.04-.24.86-.32.71-.36.57-.4.45-.42.33-.42.24-.4.16-.36.09-.32.05-.24.02-.16-.01h-8.22v.82h5.84l.01 2.76.02.36-.05.34-.11.31-.17.29-.25.25-.31.24-.38.2-.44.17-.51.15-.58.13-.64.09-.71.07-.77.04-.84.01-1.27-.04-1.07-.14-.9-.2-.73-.25-.59-.3-.45-.33-.34-.34-.25-.34-.16-.33-.1-.3-.04-.25-.02-.2.01-.13v-5.34l.05-.64.13-.54.21-.46.26-.38.3-.32.33-.24.35-.2.35-.14.33-.1.3-.06.26-.04.21-.02.13-.01h5.84l.69-.05.59-.14.5-.21.41-.28.33-.32.27-.35.2-.36.15-.36.1-.35.07-.32.04-.28.02-.21V6.07h2.09l.14.01zm-6.47 14.25l-.23.33-.08.41.08.41.23.33.33.23.41.08.41-.08.33-.23.23-.33.08-.41-.08-.41-.23-.33-.33-.23-.41-.08-.41.08z"/></svg>
                                        @break
                                    @case('go')
                                        <svg class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor"><path d="M1.811 10.231c-.047 0-.058-.023-.035-.059l.246-.315c.023-.035.081-.058.128-.058h4.172c.046 0 .058.035.035.07l-.199.303c-.023.036-.082.07-.117.07zM.047 11.306c-.047 0-.059-.023-.035-.058l.245-.316c.023-.035.082-.058.129-.058h5.328c.047 0 .07.035.058.07l-.093.28c-.012.047-.058.07-.105.07zm2.828 1.075c-.047 0-.059-.035-.035-.07l.163-.292c.023-.035.07-.07.117-.07h2.337c.047 0 .07.035.07.082l-.023.28c0 .047-.047.082-.082.082zm12.129-2.36c-.736.187-1.239.327-1.963.514-.176.046-.187.058-.34-.117-.174-.199-.303-.327-.548-.444-.737-.362-1.45-.257-2.115.175-.795.514-1.204 1.274-1.192 2.22.011.935.654 1.706 1.577 1.835.795.105 1.46-.175 1.987-.771.105-.13.198-.27.315-.434H10.47c-.245 0-.304-.152-.222-.35.152-.362.432-.97.596-1.274a.315.315 0 0 1 .292-.187h4.253c-.023.316-.023.631-.07.947a4.983 4.983 0 0 1-.958 2.29c-.841 1.11-1.94 1.8-3.33 1.986-1.145.152-2.209-.07-3.143-.77-.865-.655-1.356-1.52-1.484-2.595-.152-1.274.222-2.419.993-3.424.83-1.086 1.928-1.776 3.272-2.02 1.098-.2 2.15-.07 3.096.571.62.41 1.063.97 1.356 1.648.07.105.023.164-.117.2m3.868 6.461c-1.064-.024-2.034-.328-2.852-1.029a3.665 3.665 0 0 1-1.262-2.255c-.21-1.32.152-2.489.947-3.529.853-1.122 1.881-1.706 3.272-1.95 1.192-.21 2.314-.095 3.33.595.923.63 1.496 1.484 1.648 2.605.198 1.578-.257 2.863-1.344 3.962-.771.783-1.718 1.273-2.805 1.495-.315.06-.63.07-.934.106zm2.78-4.72c-.011-.153-.011-.27-.034-.387-.21-1.157-1.274-1.81-2.384-1.554-1.087.245-1.788.935-2.045 2.033-.21.912.234 1.835 1.075 2.21.643.28 1.285.244 1.905-.07.923-.48 1.425-1.228 1.484-2.233z"/></svg>
                                        @break
                                    @case('html')
                                        <svg class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor"><path d="M1.5 0h21l-1.91 21.563L11.977 24l-8.564-2.438L1.5 0zm7.031 9.75l-.232-2.718 10.059.003.23-2.622L5.412 4.41l.698 8.01h9.126l-.326 3.426-2.91.804-2.955-.81-.188-2.11H6.248l.33 4.171L12 19.351l5.379-1.443.744-8.157H8.531z"/></svg>
                                        @break
                                    @default
                                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                                @endswitch
                            </div>
                            <p class="font-medium text-gray-900 dark:text-white text-sm">{{ $template->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">{{ $template->description }}</p>
                        </button>
                    @endforeach
                </div>

                @if($selectedTemplateId)
                    <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-blue-700 dark:text-blue-400">
                                <span class="font-medium">Selected:</span> {{ $templates->find($selectedTemplateId)->name }}
                            </p>
                            <button type="button" wire:click="clearTemplate" class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 underline">
                                Clear selection
                            </button>
                        </div>
                    </div>
                @endif
            </div>
            @endif

            <!-- Basic Information -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Project Name *</label>
                        <input wire:model.live="name" 
                               id="name" 
                               type="text" 
                               required
                               placeholder="My Awesome Project"
                               class="input @error('name') border-red-500 @enderror">
                        @error('name') 
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Slug *</label>
                        <input wire:model="slug" 
                               id="slug" 
                               type="text" 
                               required
                               placeholder="my-awesome-project"
                               class="input @error('slug') border-red-500 @enderror">
                        @error('slug') 
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
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
                    <div class="space-y-3">
                        @forelse($servers as $server)
                            <div class="border rounded-lg p-4 {{ $server_id == $server->id ? 'border-blue-500 bg-blue-50' : 'border-gray-200 dark:border-gray-700 hover:border-blue-300' }}">
                                <div class="flex items-center justify-between">
                                    <label class="flex items-center flex-1 cursor-pointer">
                                        <input type="radio" 
                                               wire:model="server_id" 
                                               value="{{ $server->id }}"
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                        <div class="ml-3 flex-1">
                                            <div class="flex items-center">
                                                <span class="font-medium text-gray-900 dark:text-white dark:text-white">{{ $server->name }}</span>
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
                                            class="ml-3 text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 text-sm font-medium"
                                            title="Refresh server status">
                                        🔄 Refresh
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
                        <input wire:model="repository_url" 
                               id="repository_url" 
                               type="text" 
                               placeholder="https://github.com/user/repo.git or git@github.com:user/repo.git"
                               class="input @error('repository_url') border-red-500 @enderror">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Supports HTTPS or SSH format. SSH recommended for private repositories.
                        </p>
                        @error('repository_url') 
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="branch" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Branch *</label>
                        <input wire:model="branch" 
                               id="branch" 
                               type="text" 
                               required
                               class="input">
                    </div>
                </div>
            </div>

            <!-- Framework & Runtime -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Framework & Runtime</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="framework" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Framework</label>
                        <select wire:model="framework" id="framework" class="input">
                            <option value="">Select Framework...</option>
                            <option value="Laravel">Laravel</option>
                            <option value="Node.js">Node.js</option>
                            <option value="React">React</option>
                            <option value="Vue">Vue.js</option>
                            <option value="Next.js">Next.js</option>
                            <option value="Django">Django</option>
                            <option value="Flask">Flask</option>
                        </select>
                    </div>

                    <div>
                        <label for="php_version" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">PHP Version</label>
                        <select wire:model="php_version" id="php_version" class="input">
                            <option value="8.3">8.3</option>
                            <option value="8.2">8.2</option>
                            <option value="8.1">8.1</option>
                            <option value="8.0">8.0</option>
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
            </div>

            <!-- Build Configuration -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Build Configuration</h3>
                <div class="space-y-6">
                    <div>
                        <label for="root_directory" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Root Directory *</label>
                        <input wire:model="root_directory" 
                               id="root_directory" 
                               type="text" 
                               required
                               placeholder="/"
                               class="input">
                    </div>

                    <div>
                        <label for="build_command" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Build Command</label>
                        <input wire:model="build_command" 
                               id="build_command" 
                               type="text" 
                               placeholder="npm run build"
                               class="input">
                    </div>

                    <div>
                        <label for="start_command" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Command</label>
                        <input wire:model="start_command" 
                               id="start_command" 
                               type="text" 
                               placeholder="npm start"
                               class="input">
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
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="auto_deploy" class="ml-2 block text-sm text-gray-900 dark:text-white dark:text-white">
                        Enable auto-deployment on git push
                    </label>
                </div>
            </div>

            <!-- GPS Location -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">GPS Location (Optional)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="latitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Latitude</label>
                        <input wire:model="latitude" 
                               id="latitude" 
                               type="number" 
                               step="any"
                               placeholder="0.0"
                               class="input">
                    </div>

                    <div>
                        <label for="longitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Longitude</label>
                        <input wire:model="longitude" 
                               id="longitude" 
                               type="number" 
                               step="any"
                               placeholder="0.0"
                               class="input">
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-6 border-t">
                <a href="{{ route('projects.index') }}" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit"
                        wire:loading.attr="disabled"
                        wire:target="createProject"
                        class="btn btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="createProject">Create Project</span>
                    <span wire:loading wire:target="createProject">⏳ Creating Project...</span>
                </button>
            </div>
        </form>
    </div>
</div>

