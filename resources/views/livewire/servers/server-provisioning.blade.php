<div class="space-y-6">
    {{-- Provisioning Status --}}
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-white">Server Provisioning</h3>

            @if($server->provision_status)
                <span class="px-3 py-1 rounded-full text-xs font-medium
                    {{ match($server->provision_status) {
                        'completed' => 'bg-green-500/20 text-green-400 border border-green-500/30',
                        'provisioning' => 'bg-blue-500/20 text-blue-400 border border-blue-500/30',
                        'failed' => 'bg-red-500/20 text-red-400 border border-red-500/30',
                        default => 'bg-gray-500/20 text-gray-400 border border-gray-500/30',
                    } }}">
                    {{ ucfirst($server->provision_status) }}
                </span>
            @else
                <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-500/20 text-gray-400 border border-gray-500/30">
                    Not Provisioned
                </span>
            @endif
        </div>

        @if($server->provisioned_at)
            <div class="text-sm text-gray-400 mb-4">
                <p>Provisioned: {{ $server->provisioned_at->diffForHumans() }}</p>
            </div>
        @endif

        @if($server->installed_packages && count($server->installed_packages) > 0)
            <div class="mb-4">
                <p class="text-sm text-gray-400 mb-2">Installed Packages:</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($server->installed_packages as $package)
                        <span class="px-2 py-1 bg-gray-700 text-gray-300 rounded text-xs">
                            {{ $package }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="flex gap-3">
            @if(!$server->isProvisioned())
                <button
                    wire:click="openProvisioningModal"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    Start Provisioning
                </button>
            @else
                <button
                    wire:click="openProvisioningModal"
                    class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors">
                    Re-provision Server
                </button>
            @endif

            <button
                wire:click="downloadProvisioningScript"
                class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors">
                Download Script
            </button>

            <button
                wire:click="refreshServerStatus"
                class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors">
                Refresh Status
            </button>
        </div>
    </div>

    {{-- Provisioning Logs --}}
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Provisioning Logs</h3>

        @if($this->provisioningLogs->isEmpty())
            <p class="text-gray-400 text-sm">No provisioning logs available.</p>
        @else
            <div class="space-y-3 max-h-96 overflow-y-auto">
                @foreach($this->provisioningLogs as $log)
                    <div class="bg-gray-900 border border-gray-700 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-medium text-white">
                                    {{ str_replace('_', ' ', ucfirst($log->task)) }}
                                </span>
                                <span class="px-2 py-1 rounded text-xs font-medium {{ $log->getStatusBadgeClass() }}">
                                    {{ ucfirst($log->status) }}
                                </span>
                            </div>
                            <span class="text-xs text-gray-500">
                                {{ $log->created_at->diffForHumans() }}
                            </span>
                        </div>

                        @if($log->duration_seconds)
                            <p class="text-xs text-gray-400 mb-2">
                                Duration: {{ $log->duration_seconds }}s
                            </p>
                        @endif

                        @if($log->error_message)
                            <div class="mt-2 p-2 bg-red-500/10 border border-red-500/30 rounded">
                                <p class="text-xs text-red-400">{{ $log->error_message }}</p>
                            </div>
                        @endif

                        @if($log->output && $log->isCompleted())
                            <details class="mt-2">
                                <summary class="text-xs text-gray-400 cursor-pointer hover:text-gray-300">
                                    View Output
                                </summary>
                                <pre class="mt-2 p-2 bg-black rounded text-xs text-gray-300 overflow-x-auto">{{ $log->output }}</pre>
                            </details>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Provisioning Modal --}}
    @if($showProvisioningModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: true }">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75"
                     wire:click="closeProvisioningModal"></div>

                <div class="inline-block w-full max-w-3xl my-8 overflow-hidden text-left align-middle transition-all transform bg-gray-800 border border-gray-700 rounded-lg shadow-xl">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <h3 class="text-lg font-semibold text-white">Provision Server: {{ $server->name }}</h3>
                    </div>

                    <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                        {{-- Package Selection --}}
                        <div>
                            <h4 class="text-sm font-medium text-white mb-3">Select Packages to Install</h4>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex items-center gap-2 p-3 bg-gray-900 rounded-lg cursor-pointer hover:bg-gray-700/50">
                                    <input type="checkbox" wire:model="installNginx" class="rounded text-blue-600">
                                    <span class="text-sm text-gray-300">Nginx Web Server</span>
                                </label>

                                <label class="flex items-center gap-2 p-3 bg-gray-900 rounded-lg cursor-pointer hover:bg-gray-700/50">
                                    <input type="checkbox" wire:model="installMySQL" class="rounded text-blue-600">
                                    <span class="text-sm text-gray-300">MySQL Database</span>
                                </label>

                                <label class="flex items-center gap-2 p-3 bg-gray-900 rounded-lg cursor-pointer hover:bg-gray-700/50">
                                    <input type="checkbox" wire:model="installPHP" class="rounded text-blue-600">
                                    <span class="text-sm text-gray-300">PHP {{ $phpVersion }}</span>
                                </label>

                                <label class="flex items-center gap-2 p-3 bg-gray-900 rounded-lg cursor-pointer hover:bg-gray-700/50">
                                    <input type="checkbox" wire:model="installComposer" class="rounded text-blue-600">
                                    <span class="text-sm text-gray-300">Composer</span>
                                </label>

                                <label class="flex items-center gap-2 p-3 bg-gray-900 rounded-lg cursor-pointer hover:bg-gray-700/50">
                                    <input type="checkbox" wire:model="installNodeJS" class="rounded text-blue-600">
                                    <span class="text-sm text-gray-300">Node.js {{ $nodeVersion }}</span>
                                </label>

                                <label class="flex items-center gap-2 p-3 bg-gray-900 rounded-lg cursor-pointer hover:bg-gray-700/50">
                                    <input type="checkbox" wire:model="configureFirewall" class="rounded text-blue-600">
                                    <span class="text-sm text-gray-300">UFW Firewall</span>
                                </label>

                                <label class="flex items-center gap-2 p-3 bg-gray-900 rounded-lg cursor-pointer hover:bg-gray-700/50">
                                    <input type="checkbox" wire:model="setupSwap" class="rounded text-blue-600">
                                    <span class="text-sm text-gray-300">Swap File</span>
                                </label>

                                <label class="flex items-center gap-2 p-3 bg-gray-900 rounded-lg cursor-pointer hover:bg-gray-700/50">
                                    <input type="checkbox" wire:model="secureSSH" class="rounded text-blue-600">
                                    <span class="text-sm text-gray-300">SSH Security</span>
                                </label>
                            </div>
                        </div>

                        {{-- Configuration Options --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">PHP Version</label>
                                <select wire:model="phpVersion" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white">
                                    <option value="8.1">PHP 8.1</option>
                                    <option value="8.2">PHP 8.2</option>
                                    <option value="8.3">PHP 8.3</option>
                                    <option value="8.4">PHP 8.4</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Node.js Version</label>
                                <select wire:model="nodeVersion" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white">
                                    <option value="18">Node.js 18 LTS</option>
                                    <option value="20">Node.js 20 LTS</option>
                                    <option value="22">Node.js 22 Current</option>
                                </select>
                            </div>
                        </div>

                        @if($installMySQL)
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">MySQL Root Password</label>
                                <input type="password" wire:model="mysqlPassword"
                                       class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white"
                                       placeholder="Enter MySQL root password">
                                @error('mysqlPassword')
                                    <span class="text-xs text-red-400 mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                        @endif

                        @if($setupSwap)
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Swap Size (GB)</label>
                                <input type="number" wire:model="swapSizeGB" min="1" max="32"
                                       class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white">
                                @error('swapSizeGB')
                                    <span class="text-xs text-red-400 mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                        @endif
                    </div>

                    <div class="px-6 py-4 border-t border-gray-700 flex justify-end gap-3">
                        <button wire:click="closeProvisioningModal"
                                class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors">
                            Cancel
                        </button>
                        <button wire:click="startProvisioning"
                                wire:loading.attr="disabled"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors disabled:opacity-50">
                            <span wire:loading.remove>Start Provisioning</span>
                            <span wire:loading>Starting...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
