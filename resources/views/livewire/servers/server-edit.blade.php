<div>
    <div class="mb-8">
        <div class="flex items-center space-x-4">
            <a href="{{ route('servers.show', $server) }}" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit Server</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Update server details for <span class="font-semibold">{{ $server->name }}</span></p>
            </div>
        </div>
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

        <form wire:submit="updateServer" class="space-y-6">
            <!-- Server Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Server Name</label>
                <input wire:model="name"
                       id="name"
                       type="text"
                       required
                       placeholder="Production Server 1"
                       class="input @error('name') border-red-500 @enderror">
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
                           class="input @error('ip_address') border-red-500 @enderror">
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
                           class="input @error('hostname') border-red-500 @enderror">
                    @error('hostname')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Port -->
                <div>
                    <label for="port" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SSH Port</label>
                    <input wire:model="port"
                           id="port"
                           type="number"
                           required
                           class="input @error('port') border-red-500 @enderror">
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
                           class="input @error('username') border-red-500 @enderror">
                    @error('username')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Authentication Method -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Authentication Method</label>
                <div class="flex space-x-4">
                    <label class="inline-flex items-center">
                        <input type="radio" wire:model.live="auth_method" value="password" class="form-radio text-blue-600 dark:bg-gray-700 dark:border-gray-600">
                        <span class="ml-2 text-gray-700 dark:text-gray-300">Password</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" wire:model.live="auth_method" value="key" class="form-radio text-blue-600 dark:bg-gray-700 dark:border-gray-600">
                        <span class="ml-2 text-gray-700 dark:text-gray-300">SSH Key</span>
                    </label>
                </div>
                <livewire:components.inline-help help-key="ssh-access-button" :collapsible="true" />
            </div>

            <!-- SSH Password -->
            @if($auth_method === 'password')
            <div>
                <label for="ssh_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    SSH Password
                    <span class="text-gray-400 text-xs">(Leave blank to keep current)</span>
                </label>
                <input wire:model="ssh_password"
                       id="ssh_password"
                       type="password"
                       placeholder="Enter new SSH password or leave blank"
                       class="input @error('ssh_password') border-red-500 @enderror">
                @error('ssh_password')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            @endif

            <!-- SSH Key -->
            @if($auth_method === 'key')
            <div>
                <label for="ssh_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    SSH Private Key
                    <span class="text-gray-400 text-xs">(Leave blank to keep current)</span>
                </label>
                <textarea wire:model="ssh_key"
                          id="ssh_key"
                          rows="6"
                          placeholder="-----BEGIN OPENSSH PRIVATE KEY-----"
                          class="input @error('ssh_key') border-red-500 @enderror"></textarea>
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

                    <div>
                        <label for="location_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Location Name</label>
                        <input wire:model="location_name"
                               id="location_name"
                               type="text"
                               placeholder="New York, USA"
                               class="input">
                    </div>
                </div>
            </div>

            <!-- Current Server Info -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Current Server Info</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                        <p class="font-semibold text-gray-900 dark:text-white capitalize">{{ $server->status }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">OS</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $server->os ?? 'Unknown' }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Docker</p>
                        <p class="font-semibold text-gray-900 dark:text-white">
                            @if($server->docker_installed)
                                v{{ $server->docker_version ?? 'Installed' }}
                            @else
                                Not Installed
                            @endif
                        </p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Last Ping</p>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $server->last_ping_at?->diffForHumans() ?? 'Never' }}</p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-6 border-t">
                <a href="{{ route('servers.show', $server) }}" class="btn btn-secondary">
                    Cancel
                </a>
                <div class="space-x-4">
                    <button type="button"
                            wire:click="testConnection"
                            wire:loading.attr="disabled"
                            wire:target="testConnection"
                            class="btn btn-secondary disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="testConnection">Test Connection</span>
                        <span wire:loading wire:target="testConnection">Testing...</span>
                    </button>
                    <button type="submit"
                            wire:loading.attr="disabled"
                            wire:target="updateServer"
                            class="btn btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="updateServer">Update Server</span>
                        <span wire:loading wire:target="updateServer">Updating...</span>
                    </button>
                </div>
                <livewire:components.inline-help help-key="add-server-button" :collapsible="true" />
            </div>
        </form>
    </div>
</div>
