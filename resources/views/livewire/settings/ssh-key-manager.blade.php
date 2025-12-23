<div>
    <!-- Hero Section -->
    <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-purple-500 via-indigo-500 to-blue-500 dark:from-purple-600 dark:via-indigo-600 dark:to-blue-600 p-8 shadow-xl overflow-hidden">
        <div class="absolute inset-0 bg-black/10 dark:bg-black/20 backdrop-blur-sm"></div>
        <div class="relative z-10 flex justify-between items-center">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <div class="p-2 bg-white/20 dark:bg-white/10 backdrop-blur-md rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                    </div>
                    <h1 class="text-4xl font-bold text-white">SSH Key Management</h1>
                </div>
                <p class="text-white/90 text-lg">Manage your SSH keys for secure server access</p>
            </div>
            <div class="flex space-x-3">
                <button wire:click="openCreateModal"
                        class="bg-white/20 hover:bg-white/30 backdrop-blur-md text-white font-semibold px-6 py-3 rounded-lg transition-all duration-300 hover:scale-105 shadow-lg">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Generate New Key
                </button>
                <button wire:click="openImportModal"
                        class="bg-white/20 hover:bg-white/30 backdrop-blur-md text-white font-semibold px-6 py-3 rounded-lg transition-all duration-300 hover:scale-105 shadow-lg">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    Import Existing Key
                </button>
            </div>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="mb-6 bg-gradient-to-r from-green-500/20 to-emerald-500/20 dark:from-green-500/30 dark:to-emerald-500/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-400 px-4 py-3 rounded-lg backdrop-blur-sm">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 bg-gradient-to-r from-red-500/20 to-red-600/20 dark:from-red-500/30 dark:to-red-600/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-400 px-4 py-3 rounded-lg backdrop-blur-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- SSH Keys List -->
    @if($keys->count() > 0)
        <div class="grid grid-cols-1 gap-6">
            @foreach($keys as $key)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                    <!-- Gradient Border Top -->
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-purple-500 to-blue-600"></div>

                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $key->name }}</h3>
                                <div class="mt-2 space-y-1">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        <span class="font-medium">Type:</span>
                                        <span class="ml-2 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 rounded text-xs font-semibold uppercase">{{ $key->type }}</span>
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 font-mono break-all">
                                        <span class="font-medium">Fingerprint:</span> {{ $key->fingerprint }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        <span class="font-medium">Created:</span> {{ $key->created_at->format('M d, Y H:i') }}
                                    </p>
                                </div>
                            </div>
                            <div class="p-2 bg-gradient-to-br from-purple-500 to-blue-600 rounded-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Deployed Servers -->
                        @if($key->servers->count() > 0)
                            <div class="mb-4 p-4 bg-gradient-to-br from-green-500/10 to-emerald-500/10 dark:from-green-500/20 dark:to-emerald-500/20 rounded-lg">
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Deployed to {{ $key->servers->count() }} server(s):</h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($key->servers as $server)
                                        <div class="inline-flex items-center bg-white dark:bg-gray-700 px-3 py-1 rounded-full text-sm">
                                            <span class="text-gray-900 dark:text-white">{{ $server->name }}</span>
                                            <button wire:click="removeFromServer({{ $key->id }}, {{ $server->id }})"
                                                    wire:confirm="Are you sure you want to remove this SSH key from {{ $server->name }}?"
                                                    class="ml-2 text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="mb-4 p-4 bg-gradient-to-br from-yellow-500/10 to-orange-500/10 dark:from-yellow-500/20 dark:to-orange-500/20 rounded-lg">
                                <p class="text-sm text-gray-600 dark:text-gray-400">Not deployed to any servers</p>
                            </div>
                        @endif

                        <!-- Actions -->
                        <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button wire:click="openViewKeyModal({{ $key->id }})"
                                    class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 text-sm font-medium transition-colors inline-flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                View Public Key
                            </button>
                            <button wire:click="copyPublicKey({{ $key->id }})"
                                    class="text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 text-sm font-medium transition-colors inline-flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                Copy Public Key
                            </button>
                            <button wire:click="downloadPrivateKey({{ $key->id }})"
                                    class="text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 text-sm font-medium transition-colors inline-flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Download Private Key
                            </button>
                            <button wire:click="openDeployModal({{ $key->id }})"
                                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 text-sm font-medium transition-colors inline-flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                Deploy to Server
                            </button>
                            <button wire:click="deleteKey({{ $key->id }})"
                                    wire:confirm="Are you sure you want to delete this SSH key? This will remove it from all deployed servers."
                                    class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 text-sm font-medium transition-colors inline-flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl text-center py-12 transition-colors">
            <div class="p-4 bg-gradient-to-br from-purple-100 to-indigo-200 dark:from-purple-700 dark:to-indigo-600 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                <svg class="h-10 w-10 text-purple-600 dark:text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No SSH keys</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Get started by generating or importing an SSH key.</p>
            <div class="flex justify-center space-x-3">
                <button wire:click="openCreateModal"
                        class="inline-block bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 text-white font-semibold px-6 py-3 rounded-lg transition-all duration-300 hover:scale-105 shadow-lg">
                    Generate New Key
                </button>
                <button wire:click="openImportModal"
                        class="inline-block bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-semibold px-6 py-3 rounded-lg transition-all duration-300 hover:scale-105 shadow-lg">
                    Import Existing Key
                </button>
            </div>
        </div>
    @endif

    <!-- Generate Key Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4" wire:click.self="closeModals">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Generate SSH Key</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Create a new SSH key pair for secure authentication</p>
                </div>

                @if($generatedKey)
                    <!-- Show generated key -->
                    <div class="p-6 space-y-6">
                        <div class="bg-gradient-to-r from-green-500/20 to-emerald-500/20 dark:from-green-500/30 dark:to-emerald-500/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-400 px-4 py-3 rounded-lg">
                            <p class="font-semibold">SSH Key Generated Successfully!</p>
                            <p class="text-sm mt-1">Save your private key securely. You won't be able to retrieve it again.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Public Key</label>
                            <textarea readonly
                                      class="input font-mono text-sm h-24"
                                      id="generated-public-key">{{ $generatedKey['public_key'] }}</textarea>
                            <button type="button"
                                    onclick="navigator.clipboard.writeText(document.getElementById('generated-public-key').value)"
                                    class="mt-2 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300">
                                Copy to clipboard
                            </button>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fingerprint</label>
                            <input type="text" readonly value="{{ $generatedKey['fingerprint'] }}" class="input font-mono text-sm">
                        </div>

                        <div class="flex justify-between">
                            <button type="button"
                                    wire:click="downloadPrivateKey({{ $generatedKey['id'] }})"
                                    class="bg-purple-600 hover:bg-purple-700 text-white font-semibold px-6 py-3 rounded-lg transition-colors">
                                Download Private Key
                            </button>
                            <button type="button"
                                    wire:click="closeModals"
                                    class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-6 py-3 rounded-lg transition-colors">
                                Done
                            </button>
                        </div>
                    </div>
                @else
                    <!-- Generate form -->
                    <form wire:submit.prevent="generateKey" class="p-6 space-y-6">
                        <div>
                            <label for="newKeyName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Key Name</label>
                            <input wire:model="newKeyName"
                                   id="newKeyName"
                                   type="text"
                                   required
                                   placeholder="production-server"
                                   class="input @error('newKeyName') border-red-500 @enderror">
                            @error('newKeyName')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="newKeyType" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Key Type</label>
                            <select wire:model="newKeyType" id="newKeyType" class="input">
                                <option value="ed25519">ED25519 (Recommended)</option>
                                <option value="rsa">RSA 4096</option>
                                <option value="ecdsa">ECDSA</option>
                            </select>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ED25519 is the most secure and recommended option</p>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button type="button"
                                    wire:click="closeModals"
                                    class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-6 py-3 rounded-lg transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-50"
                                    wire:target="generateKey"
                                    class="bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 text-white font-semibold px-6 py-3 rounded-lg transition-all">
                                <span wire:loading.remove wire:target="generateKey">Generate Key</span>
                                <span wire:loading wire:target="generateKey">Generating...</span>
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif

    <!-- Import Key Modal -->
    @if($showImportModal)
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4" wire:click.self="closeModals">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Import SSH Key</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Import an existing SSH key pair</p>
                </div>

                <form wire:submit.prevent="importKey" class="p-6 space-y-6">
                    <div>
                        <label for="importKeyName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Key Name</label>
                        <input wire:model="importKeyName"
                               id="importKeyName"
                               type="text"
                               required
                               placeholder="my-existing-key"
                               class="input @error('importKeyName') border-red-500 @enderror">
                        @error('importKeyName')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="importPublicKey" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Public Key</label>
                        <textarea wire:model="importPublicKey"
                                  id="importPublicKey"
                                  required
                                  rows="4"
                                  placeholder="ssh-rsa AAAAB3NzaC1yc2EAAAA..."
                                  class="input font-mono text-sm @error('importPublicKey') border-red-500 @enderror"></textarea>
                        @error('importPublicKey')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="importPrivateKey" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Private Key</label>
                        <textarea wire:model="importPrivateKey"
                                  id="importPrivateKey"
                                  required
                                  rows="8"
                                  placeholder="-----BEGIN OPENSSH PRIVATE KEY-----&#10;..."
                                  class="input font-mono text-sm @error('importPrivateKey') border-red-500 @enderror"></textarea>
                        @error('importPrivateKey')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button"
                                wire:click="closeModals"
                                class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-6 py-3 rounded-lg transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50"
                                wire:target="importKey"
                                class="bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-semibold px-6 py-3 rounded-lg transition-all">
                            <span wire:loading.remove wire:target="importKey">Import Key</span>
                            <span wire:loading wire:target="importKey">Importing...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Deploy to Server Modal -->
    @if($showDeployModal)
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4" wire:click.self="closeModals">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-xl w-full">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Deploy SSH Key to Server</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Select a server to deploy this key</p>
                </div>

                <form wire:submit.prevent="deployToServer" class="p-6 space-y-6">
                    <div>
                        <label for="selectedServerId" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Server</label>
                        <select wire:model="selectedServerId"
                                id="selectedServerId"
                                required
                                class="input @error('selectedServerId') border-red-500 @enderror">
                            <option value="">Choose a server...</option>
                            @foreach($servers as $server)
                                <option value="{{ $server->id }}">{{ $server->name }} ({{ $server->ip_address }})</option>
                            @endforeach
                        </select>
                        @error('selectedServerId')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button"
                                wire:click="closeModals"
                                class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-6 py-3 rounded-lg transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50"
                                wire:target="deployToServer"
                                class="bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-semibold px-6 py-3 rounded-lg transition-all">
                            <span wire:loading.remove wire:target="deployToServer">Deploy Key</span>
                            <span wire:loading wire:target="deployToServer">Deploying...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- View Public Key Modal -->
    @if($showViewKeyModal && $viewingKey)
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4" wire:click.self="closeModals">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-3xl w-full">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $viewingKey['name'] }}</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Public key details</p>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Type</label>
                        <p class="text-gray-900 dark:text-white uppercase">{{ $viewingKey['type'] }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fingerprint</label>
                        <p class="text-gray-900 dark:text-white font-mono text-sm">{{ $viewingKey['fingerprint'] }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Public Key</label>
                        <textarea readonly
                                  class="input font-mono text-sm h-32"
                                  id="view-public-key">{{ $viewingKey['public_key'] }}</textarea>
                        <button type="button"
                                onclick="navigator.clipboard.writeText(document.getElementById('view-public-key').value)"
                                class="mt-2 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300">
                            Copy to clipboard
                        </button>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Created</label>
                        <p class="text-gray-900 dark:text-white">{{ $viewingKey['created_at'] }}</p>
                    </div>

                    <div class="flex justify-end">
                        <button type="button"
                                wire:click="closeModals"
                                class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-6 py-3 rounded-lg transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@script
<script>
    // Handle copy to clipboard event
    $wire.on('copy-to-clipboard', (event) => {
        const text = event[0].text;
        navigator.clipboard.writeText(text).then(() => {
            console.log('Copied to clipboard');
        }).catch(err => {
            console.error('Failed to copy:', err);
        });
    });

    // Handle private key download
    $wire.on('download-private-key', (event) => {
        const data = event[0];
        const blob = new Blob([data.content], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = data.filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
    });
</script>
@endscript
