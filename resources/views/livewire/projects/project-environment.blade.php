<div>
    @if (session()->has('message'))
        <div class="mb-6 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-400 px-4 py-3 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-400 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <!-- Environment Selection -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-6 mb-6 transition-colors">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Application Environment</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Configure the runtime environment for your application</p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Current:</span>
                <span class="px-3 py-1 rounded-full text-sm font-medium
                    @if($environment === 'production') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400
                    @elseif($environment === 'staging') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400
                    @elseif($environment === 'development') bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400
                    @else bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300
                    @endif">
                    {{ ucfirst($environment) }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @foreach(['local', 'development', 'staging', 'production'] as $env)
                <button wire:click="updateEnvironment('{{ $env }}')" 
                        wire:confirm="Are you sure you want to change to {{ $env }} environment?"
                        wire:loading.attr="disabled"
                        class="relative p-4 border-2 rounded-lg transition-all duration-200
                            @if($environment === $env)
                                border-blue-500 bg-blue-50 dark:bg-blue-900/20
                            @else
                                border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600
                            @endif">
                    
                    @if($environment === $env)
                        <div class="absolute top-2 right-2">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    @endif

                    <div class="text-center">
                        <div class="text-3xl mb-2">
                            @if($env === 'production') 🚀
                            @elseif($env === 'staging') 🔧
                            @elseif($env === 'development') 💻
                            @else 🏠
                            @endif
                        </div>
                        <div class="font-semibold text-gray-900 dark:text-white">{{ ucfirst($env) }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            @if($env === 'production') Live users
                            @elseif($env === 'staging') Pre-release
                            @elseif($env === 'development') Active dev
                            @else Your machine
                            @endif
                        </div>
                    </div>

                    <div wire:loading wire:target="updateEnvironment" class="absolute inset-0 bg-white dark:bg-gray-800 bg-opacity-75 flex items-center justify-center rounded-lg">
                        <svg class="animate-spin h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </button>
            @endforeach
        </div>

        <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div class="text-sm text-blue-800 dark:text-blue-300">
                    <strong>Note:</strong> Changing the environment will affect error reporting, caching, and debugging settings.
                    Re-deploy your application after changing the environment.
                </div>
            </div>
        </div>
    </div>

    <!-- Server .env File Section -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-6 mb-6 transition-colors">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                    </svg>
                    Server .env File
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">View and edit the actual .env file on the server</p>
            </div>
            <div class="flex items-center space-x-2">
                <button wire:click="loadServerEnv"
                        wire:loading.attr="disabled"
                        class="px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors flex items-center space-x-2">
                    <svg wire:loading.class="animate-spin" wire:target="loadServerEnv" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>Refresh</span>
                </button>
                <button wire:click="openServerEnvModal"
                        class="px-4 py-2 bg-blue-600 dark:bg-blue-500 hover:bg-blue-700 dark:hover:bg-blue-600 text-white rounded-lg transition-colors flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    <span>Add Variable</span>
                </button>
            </div>
        </div>

        @if($serverEnvLoading)
            <div class="flex items-center justify-center py-12">
                <svg class="animate-spin h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-3 text-gray-600 dark:text-gray-400">Loading server environment...</span>
            </div>
        @elseif($serverEnvError)
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div class="text-sm text-red-800 dark:text-red-300">{{ $serverEnvError }}</div>
                </div>
            </div>
        @elseif(count($serverEnvVariables) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Key</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Value</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($serverEnvVariables as $key => $value)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <code class="text-sm font-mono text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $key }}</code>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 dark:text-white font-mono truncate max-w-md">
                                        @if(str_contains(strtoupper($key), 'PASSWORD') || str_contains(strtoupper($key), 'SECRET') || str_contains(strtoupper($key), 'KEY') || str_contains(strtoupper($key), 'TOKEN'))
                                            <span class="text-gray-400 dark:text-gray-500">••••••••</span>
                                        @else
                                            {{ Str::limit($value, 50) }}
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button wire:click="editServerEnvVariable('{{ $key }}')"
                                            class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 mr-3 transition-colors">
                                        Edit
                                    </button>
                                    <button wire:click="deleteServerEnvVariable('{{ $key }}')"
                                            wire:confirm="Are you sure you want to delete '{{ $key }}' from the server .env file?"
                                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                Total: {{ count($serverEnvVariables) }} variables
            </div>
        @else
            <div class="text-center py-12">
                <div class="text-gray-400 dark:text-gray-500 text-5xl mb-3">📄</div>
                <p class="text-gray-500 dark:text-gray-400">No environment variables found in server .env file</p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Click "Add Variable" to create one</p>
            </div>
        @endif
    </div>

    <!-- Server Env Modal -->
    @if($showServerEnvModal)
        <div class="fixed inset-0 bg-gray-600 dark:bg-gray-900 bg-opacity-50 dark:bg-opacity-75 overflow-y-auto h-full w-full z-50" wire:click="closeServerEnvModal">
            <div class="relative top-20 mx-auto p-5 border border-gray-200 dark:border-gray-700 w-full max-w-2xl shadow-lg rounded-lg bg-white dark:bg-gray-800 transition-colors" @click.stop>
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6">
                        {{ $editingServerEnvKey ? 'Edit' : 'Add' }} Server Environment Variable
                    </h3>

                    <div class="space-y-4">
                        <div x-data="{ showServerSuggestions: false }">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Variable Name
                                <button type="button" @click="showServerSuggestions = !showServerSuggestions"
                                        class="ml-2 inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-xs">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Show common variables
                                </button>
                            </label>
                            <input wire:model="serverEnvKey"
                                   type="text"
                                   placeholder="e.g., APP_DEBUG, DB_HOST"
                                   {{ $editingServerEnvKey ? 'readonly' : '' }}
                                   class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 font-mono transition-colors uppercase {{ $editingServerEnvKey ? 'bg-gray-100 dark:bg-gray-900' : '' }}">
                            @error('serverEnvKey') <span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span> @enderror

                            {{-- Quick Variable Suggestions --}}
                            <div x-show="showServerSuggestions" x-collapse class="mt-3">
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach(['APP_ENV', 'APP_DEBUG', 'APP_URL', 'DB_HOST', 'DB_DATABASE', 'CACHE_DRIVER', 'QUEUE_CONNECTION', 'REDIS_HOST'] as $quickVar)
                                            <button type="button"
                                                    wire:click="$set('serverEnvKey', '{{ $quickVar }}')"
                                                    @click="showServerSuggestions = false"
                                                    class="px-2 py-1 text-xs font-mono bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded hover:bg-blue-100 dark:hover:bg-blue-800/50 transition-colors">
                                                {{ $quickVar }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Value
                                @if(str_contains(strtoupper($serverEnvKey ?? ''), 'PASSWORD') || str_contains(strtoupper($serverEnvKey ?? ''), 'SECRET'))
                                    <span class="ml-2 text-xs text-amber-500 dark:text-amber-400 font-normal">(sensitive)</span>
                                @endif
                            </label>
                            <textarea wire:model="serverEnvValue"
                                      rows="3"
                                      placeholder="Enter variable value..."
                                      class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 font-mono transition-colors"></textarea>
                            @error('serverEnvValue') <span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span> @enderror

                            {{-- Context-sensitive help --}}
                            @php
                                $serverVarHelp = match(strtoupper($serverEnvKey ?? '')) {
                                    'APP_DEBUG' => 'Use "true" for debugging, "false" for production. Shows detailed error pages when enabled.',
                                    'APP_ENV' => 'Set to "production" for live sites, "local" or "development" for dev environments.',
                                    'CACHE_DRIVER' => 'Recommended: "redis" for production, "file" for simple setups, "array" for testing.',
                                    'QUEUE_CONNECTION' => 'Use "redis" or "database" for production, "sync" processes jobs immediately.',
                                    default => null,
                                };
                            @endphp
                            @if($serverVarHelp)
                                <p class="mt-2 text-xs text-blue-600 dark:text-blue-400 flex items-center">
                                    <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ $serverVarHelp }}
                                </p>
                            @endif
                        </div>

                        <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <div class="text-sm text-yellow-800 dark:text-yellow-300">
                                    <strong>Important:</strong> Changes will be made directly to the server .env file. You may need to restart the application for changes to take effect.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" wire:click="closeServerEnvModal"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            Cancel
                        </button>
                        <button wire:click="saveServerEnvVariable"
                                wire:loading.attr="disabled"
                                class="px-4 py-2 bg-blue-600 dark:bg-blue-500 hover:bg-blue-700 dark:hover:bg-blue-600 text-white rounded-lg transition-colors flex items-center space-x-2">
                            <svg wire:loading wire:target="saveServerEnvVariable" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ $editingServerEnvKey ? 'Update' : 'Add' }} Variable</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Environment Variables -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-6 transition-colors">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Environment Variables</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Manage custom environment variables for your application</p>
            </div>
            <button wire:click="openEnvModal" 
                    class="px-4 py-2 bg-blue-600 dark:bg-blue-500 hover:bg-blue-700 dark:hover:bg-blue-600 text-white rounded-lg transition-colors flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                <span>Add Variable</span>
            </button>
        </div>

        @if(count($envVariables) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Key</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Value</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($envVariables as $key => $value)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <code class="text-sm font-mono text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $key }}</code>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 dark:text-white font-mono truncate max-w-md">
                                        @if(str_contains(strtoupper($key), 'PASSWORD') || str_contains(strtoupper($key), 'SECRET') || str_contains(strtoupper($key), 'KEY'))
                                            <span class="text-gray-400 dark:text-gray-500">••••••••</span>
                                        @else
                                            {{ $value }}
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button wire:click="editEnvVariable('{{ $key }}')" 
                                            class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 mr-3 transition-colors">
                                        Edit
                                    </button>
                                    <button wire:click="deleteEnvVariable('{{ $key }}')" 
                                            wire:confirm="Are you sure you want to delete this environment variable?"
                                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12">
                <div class="text-gray-400 dark:text-gray-500 text-5xl mb-3">🔧</div>
                <p class="text-gray-500 dark:text-gray-400">No environment variables configured</p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Click "Add Variable" to get started</p>
            </div>
        @endif
    </div>

    <!-- Add/Edit Environment Variable Modal -->
    @if($showEnvModal)
        <div class="fixed inset-0 bg-gray-600 dark:bg-gray-900 bg-opacity-50 dark:bg-opacity-75 overflow-y-auto h-full w-full z-50" wire:click="closeEnvModal">
            <div class="relative top-20 mx-auto p-5 border border-gray-200 dark:border-gray-700 w-full max-w-2xl shadow-lg rounded-lg bg-white dark:bg-gray-800 transition-colors" @click.stop>
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6">
                        {{ $editingEnvKey ? 'Edit' : 'Add' }} Environment Variable
                    </h3>
                    
                    <div class="space-y-4">
                        <div x-data="{ showSuggestions: false, suggestions: [] }">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Variable Name
                                <button type="button" @click="showSuggestions = !showSuggestions"
                                        class="ml-2 inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-xs">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Show common variables
                                </button>
                            </label>
                            <input wire:model="newEnvKey"
                                   type="text"
                                   placeholder="e.g., API_KEY, DATABASE_URL"
                                   {{ $editingEnvKey ? 'readonly' : '' }}
                                   class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 font-mono transition-colors {{ $editingEnvKey ? 'bg-gray-100 dark:bg-gray-900' : '' }}">
                            @error('newEnvKey') <span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span> @enderror

                            {{-- Common Variables Reference --}}
                            <div x-show="showSuggestions" x-collapse class="mt-3">
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                        </svg>
                                        Common Environment Variables
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm max-h-64 overflow-y-auto">
                                        @php
                                            $commonVars = [
                                                ['APP_ENV', 'Application environment (local, staging, production)'],
                                                ['APP_DEBUG', 'Enable/disable debug mode (true/false)'],
                                                ['APP_URL', 'Base URL of your application'],
                                                ['APP_KEY', 'Application encryption key (auto-generated)'],
                                                ['DB_HOST', 'Database server hostname'],
                                                ['DB_PORT', 'Database server port (default: 3306)'],
                                                ['DB_DATABASE', 'Database name'],
                                                ['DB_USERNAME', 'Database username'],
                                                ['DB_PASSWORD', 'Database password'],
                                                ['REDIS_HOST', 'Redis server hostname'],
                                                ['REDIS_PORT', 'Redis server port (default: 6379)'],
                                                ['REDIS_PASSWORD', 'Redis password (if required)'],
                                                ['CACHE_DRIVER', 'Cache driver (file, redis, memcached)'],
                                                ['SESSION_DRIVER', 'Session driver (file, cookie, redis)'],
                                                ['QUEUE_CONNECTION', 'Queue driver (sync, redis, database)'],
                                                ['MAIL_MAILER', 'Mail driver (smtp, sendmail, mailgun)'],
                                                ['MAIL_HOST', 'SMTP server hostname'],
                                                ['MAIL_PORT', 'SMTP server port'],
                                                ['MAIL_USERNAME', 'SMTP username'],
                                                ['MAIL_PASSWORD', 'SMTP password'],
                                                ['AWS_ACCESS_KEY_ID', 'AWS access key for S3/services'],
                                                ['AWS_SECRET_ACCESS_KEY', 'AWS secret key'],
                                                ['AWS_DEFAULT_REGION', 'AWS region (us-east-1, eu-west-1)'],
                                                ['AWS_BUCKET', 'S3 bucket name for file storage'],
                                                ['PUSHER_APP_KEY', 'Pusher/broadcasting app key'],
                                                ['STRIPE_KEY', 'Stripe publishable API key'],
                                                ['STRIPE_SECRET', 'Stripe secret API key'],
                                            ];
                                        @endphp
                                        @foreach($commonVars as [$varName, $description])
                                            <button type="button"
                                                    wire:click="$set('newEnvKey', '{{ $varName }}')"
                                                    @click="showSuggestions = false"
                                                    class="flex items-start p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600 text-left transition-colors group">
                                                <code class="text-xs font-mono text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 px-1.5 py-0.5 rounded mr-2 whitespace-nowrap group-hover:bg-blue-100 dark:group-hover:bg-blue-800/50">{{ $varName }}</code>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 leading-tight">{{ $description }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Value
                                <span class="ml-2 text-xs text-gray-400 dark:text-gray-500 font-normal">
                                    @if(str_contains(strtoupper($newEnvKey ?? ''), 'PASSWORD') || str_contains(strtoupper($newEnvKey ?? ''), 'SECRET') || str_contains(strtoupper($newEnvKey ?? ''), 'KEY'))
                                        (sensitive - will be encrypted)
                                    @endif
                                </span>
                            </label>
                            <textarea wire:model="newEnvValue"
                                      rows="3"
                                      placeholder="Enter variable value..."
                                      class="w-full border border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 font-mono transition-colors"></textarea>
                            @error('newEnvValue') <span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span> @enderror

                            {{-- Context-sensitive help based on variable name --}}
                            @php
                                $varHelp = match(true) {
                                    str_contains(strtoupper($newEnvKey ?? ''), 'DB_HOST') => 'Usually "localhost" or "127.0.0.1" for local, or your database server IP/hostname for remote.',
                                    str_contains(strtoupper($newEnvKey ?? ''), 'DB_PORT') => 'Default ports: MySQL=3306, PostgreSQL=5432, SQL Server=1433',
                                    str_contains(strtoupper($newEnvKey ?? ''), 'REDIS_HOST') => 'Usually "127.0.0.1" for local Redis or your Redis server hostname.',
                                    str_contains(strtoupper($newEnvKey ?? ''), 'APP_DEBUG') => 'Set to "true" for development, "false" for production. Never enable in production!',
                                    str_contains(strtoupper($newEnvKey ?? ''), 'APP_ENV') => 'Common values: local, development, staging, production',
                                    str_contains(strtoupper($newEnvKey ?? ''), 'CACHE_DRIVER') => 'Options: file (default), redis (recommended for production), memcached, array (testing)',
                                    str_contains(strtoupper($newEnvKey ?? ''), 'QUEUE_CONNECTION') => 'Options: sync (immediate), database, redis (recommended), beanstalkd, sqs',
                                    str_contains(strtoupper($newEnvKey ?? ''), 'MAIL_MAILER') => 'Options: smtp, sendmail, mailgun, ses, postmark, log (testing)',
                                    str_contains(strtoupper($newEnvKey ?? ''), 'AWS_REGION') => 'Example: us-east-1, us-west-2, eu-west-1, ap-southeast-1',
                                    default => null,
                                };
                            @endphp
                            @if($varHelp)
                                <p class="mt-2 text-xs text-blue-600 dark:text-blue-400 flex items-start">
                                    <svg class="w-4 h-4 mr-1 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                    </svg>
                                    {{ $varHelp }}
                                </p>
                            @endif
                        </div>

                        <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <div class="text-sm text-yellow-800 dark:text-yellow-300">
                                    <strong>Security:</strong> Never commit sensitive values to git. Environment variables are stored in the database and injected during deployment.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" wire:click="closeEnvModal"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            Cancel
                        </button>
                        <button wire:click="{{ $editingEnvKey ? 'updateEnvVariable' : 'addEnvVariable' }}"
                                class="px-4 py-2 bg-blue-600 dark:bg-blue-500 hover:bg-blue-700 dark:hover:bg-blue-600 text-white rounded-lg transition-colors">
                            {{ $editingEnvKey ? 'Update' : 'Add' }} Variable
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
