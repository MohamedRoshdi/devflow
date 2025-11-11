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
                <!-- Hostname -->
                <div>
                    <label for="hostname" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Hostname</label>
                    <input wire:model="hostname" 
                           id="hostname" 
                           type="text" 
                           required
                           placeholder="server1.example.com"
                           class="input @error('hostname') border-red-500 @enderror">
                    @error('hostname') 
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- IP Address -->
                <div>
                    <label for="ip_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">IP Address</label>
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

            <!-- SSH Key -->
            <div>
                <label for="ssh_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SSH Private Key (Optional)</label>
                <textarea wire:model="ssh_key" 
                          id="ssh_key" 
                          rows="6"
                          placeholder="-----BEGIN OPENSSH PRIVATE KEY-----"
                          class="input @error('ssh_key') border-red-500 @enderror"></textarea>
                @error('ssh_key') 
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

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

                <button type="button" 
                        wire:click="getLocation"
                        class="mt-4 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300">
                    📍 Use Current GPS Location
                </button>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-6 border-t">
                <a href="{{ route('servers.index') }}" class="btn btn-secondary">
                    Cancel
                </a>
                <div class="space-x-4">
                    <button type="button" 
                            wire:click="testConnection"
                            class="btn btn-secondary">
                        Test Connection
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Add Server
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

