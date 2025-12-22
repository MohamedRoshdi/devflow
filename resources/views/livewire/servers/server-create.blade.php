<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-white">Add New Server</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Connect a server to your DevFlow Pro account</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8">
        @if (session()->has('connection_test'))
            <div class="mb-6 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-400 px-4 py-3 rounded">
                {{ session('connection_test') }}
            </div>
        @endif

        @if (session()->has('connection_error'))
            <div class="mb-6 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-400 px-4 py-3 rounded">
                {{ session('connection_error') }}
            </div>
        @endif

        <form wire:submit="createServer" class="space-y-6">
            <!-- Server Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Server Name <span class="text-red-500">*</span></label>
                <input wire:model="name"
                       id="name"
                       type="text"
                       required
                       placeholder="Production Server 1"
                       wire:loading.attr="disabled"
                       wire:target="createServer,testConnection"
                       class="input @error('name') border-red-500 @enderror disabled:opacity-50 disabled:cursor-not-allowed">
                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- IP Address -->
                <div>
                    <label for="ip_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">IP Address <span class="text-red-500">*</span></label>
                    <input wire:model="ip_address"
                           id="ip_address"
                           type="text"
                           required
                           placeholder="192.168.1.100"
                           wire:loading.attr="disabled"
                           wire:target="createServer,testConnection"
                           class="input @error('ip_address') border-red-500 @enderror disabled:opacity-50 disabled:cursor-not-allowed">
                    @error('ip_address')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Hostname (Optional) -->
                <div>
                    <label for="hostname" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Domain/Hostname <span class="text-gray-400 text-xs">(Optional)</span></label>
                    <input wire:model="hostname"
                           id="hostname"
                           type="text"
                           placeholder="server1.example.com"
                           wire:loading.attr="disabled"
                           wire:target="createServer,testConnection"
                           class="input @error('hostname') border-red-500 @enderror disabled:opacity-50 disabled:cursor-not-allowed">
                    @error('hostname')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Port -->
                <div>
                    <label for="port" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SSH Port <span class="text-red-500">*</span></label>
                    <input wire:model="port"
                           id="port"
                           type="number"
                           required
                           wire:loading.attr="disabled"
                           wire:target="createServer,testConnection"
                           class="input @error('port') border-red-500 @enderror disabled:opacity-50 disabled:cursor-not-allowed">
                    @error('port')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SSH Username</label>
                    <input wire:model="username"
                           id="username"
                           type="text"
                           required
                           wire:loading.attr="disabled"
                           wire:target="createServer,testConnection"
                           class="input @error('username') border-red-500 @enderror disabled:opacity-50 disabled:cursor-not-allowed">
                    @error('username')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Authentication Method -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Authentication Method</label>
                <div class="flex flex-wrap gap-4">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="radio"
                               wire:model.live="auth_method"
                               value="host_key"
                               wire:loading.attr="disabled"
                               wire:target="createServer,testConnection"
                               class="form-radio text-blue-600 dark:bg-gray-700 dark:border-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span class="ml-2 text-gray-700 dark:text-gray-300">Host SSH Keys</span>
                    </label>
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="radio"
                               wire:model.live="auth_method"
                               value="password"
                               wire:loading.attr="disabled"
                               wire:target="createServer,testConnection"
                               class="form-radio text-blue-600 dark:bg-gray-700 dark:border-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span class="ml-2 text-gray-700 dark:text-gray-300">Password</span>
                    </label>
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="radio"
                               wire:model.live="auth_method"
                               value="key"
                               wire:loading.attr="disabled"
                               wire:target="createServer,testConnection"
                               class="form-radio text-blue-600 dark:bg-gray-700 dark:border-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span class="ml-2 text-gray-700 dark:text-gray-300">Custom SSH Key</span>
                    </label>
                </div>
            </div>

            <!-- SSH Password -->
            @if($auth_method === 'password')
            <div>
                <label for="ssh_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SSH Password <span class="text-red-500">*</span></label>
                <input wire:model="ssh_password"
                       id="ssh_password"
                       type="password"
                       required
                       placeholder="Enter SSH password"
                       wire:loading.attr="disabled"
                       wire:target="createServer,testConnection"
                       class="input @error('ssh_password') border-red-500 @enderror disabled:opacity-50 disabled:cursor-not-allowed">
                @error('ssh_password')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            @endif

            <!-- SSH Key -->
            @if($auth_method === 'key')
            <div>
                <label for="ssh_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SSH Private Key <span class="text-gray-400 text-xs">(optional - uses host keys if empty)</span></label>
                <textarea wire:model="ssh_key"
                          id="ssh_key"
                          rows="6"
                          placeholder="-----BEGIN OPENSSH PRIVATE KEY-----"
                          wire:loading.attr="disabled"
                          wire:target="createServer,testConnection"
                          class="input @error('ssh_key') border-red-500 @enderror disabled:opacity-50 disabled:cursor-not-allowed"></textarea>
                @error('ssh_key')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            @endif

            <!-- GPS Location -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">GPS Location (Optional)</h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="latitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Latitude</label>
                        <input wire:model="latitude"
                               id="latitude"
                               type="number"
                               step="any"
                               placeholder="0.0"
                               wire:loading.attr="disabled"
                               wire:target="createServer,testConnection"
                               class="input disabled:opacity-50 disabled:cursor-not-allowed">
                    </div>

                    <div>
                        <label for="longitude" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Longitude</label>
                        <input wire:model="longitude"
                               id="longitude"
                               type="number"
                               step="any"
                               placeholder="0.0"
                               wire:loading.attr="disabled"
                               wire:target="createServer,testConnection"
                               class="input disabled:opacity-50 disabled:cursor-not-allowed">
                    </div>

                    <div>
                        <label for="location_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Location Name</label>
                        <input wire:model="location_name"
                               id="location_name"
                               type="text"
                               placeholder="New York, USA"
                               wire:loading.attr="disabled"
                               wire:target="createServer,testConnection"
                               class="input disabled:opacity-50 disabled:cursor-not-allowed">
                    </div>
                </div>

                <button type="button"
                        wire:click="getLocation"
                        wire:loading.attr="disabled"
                        wire:target="getLocation,createServer,testConnection"
                        class="mt-4 inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 disabled:opacity-50 disabled:cursor-not-allowed transition-opacity">
                    <span wire:loading.remove wire:target="getLocation">
                        <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Use Current GPS Location
                    </span>
                    <span wire:loading wire:target="getLocation" class="inline-flex items-center">
                        <svg class="animate-spin h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Getting Location...
                    </span>
                </button>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-6 border-t">
                <a href="{{ route('servers.index') }}"
                   class="btn btn-secondary"
                   wire:loading.class="opacity-50 pointer-events-none"
                   wire:target="createServer,testConnection">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Cancel
                </a>
                <div class="flex items-center space-x-4">
                    <button type="button"
                            wire:click="testConnection"
                            wire:loading.attr="disabled"
                            wire:target="testConnection,createServer"
                            class="btn btn-secondary disabled:opacity-50 disabled:cursor-not-allowed transition-opacity inline-flex items-center">
                        <span wire:loading.remove wire:target="testConnection" class="inline-flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                            </svg>
                            Test Connection
                        </span>
                        <span wire:loading wire:target="testConnection" class="inline-flex items-center">
                            <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Testing Connection...
                        </span>
                    </button>
                    <button type="submit"
                            wire:loading.attr="disabled"
                            wire:target="createServer,testConnection"
                            class="btn btn-primary disabled:opacity-50 disabled:cursor-not-allowed transition-opacity inline-flex items-center">
                        <span wire:loading.remove wire:target="createServer" class="inline-flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Create Server
                        </span>
                        <span wire:loading wire:target="createServer" class="inline-flex items-center">
                            <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Creating Server...
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

