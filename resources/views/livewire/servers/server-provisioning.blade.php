<div class="space-y-6" @if($server->provision_status === 'provisioning') wire:poll.5s="refreshServerStatus" @endif>
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

        {{-- Progress Indicator - Only show during provisioning --}}
        @if($server->provision_status === 'provisioning' && $this->provisioningProgress['total_steps'] > 0)
            <div class="mb-6 p-4 bg-gray-900 border border-blue-500/30 rounded-lg space-y-3">
                {{-- Progress Bar --}}
                <div class="relative">
                    <div class="flex justify-between items-center mb-2">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-400 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm font-medium text-white">Provisioning Progress</span>
                            @if($this->provisioningProgress['current_task'])
                                <span class="text-xs text-blue-300">
                                    {{ str_replace('_', ' ', ucfirst($this->provisioningProgress['current_task'])) }}
                                </span>
                            @endif
                        </div>
                        <span class="text-sm font-bold text-blue-400">
                            {{ $this->provisioningProgress['percentage'] }}%
                        </span>
                    </div>

                    <div class="relative h-3 bg-gray-700 dark:bg-gray-800 rounded-full overflow-hidden shadow-inner">
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-600 via-blue-500 to-blue-400 rounded-full transition-all duration-500 ease-out shadow-lg"
                             style="width: {{ $this->provisioningProgress['percentage'] }}%">
                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-30 animate-shimmer"></div>
                        </div>
                    </div>
                </div>

                {{-- Step Counter and Estimated Time --}}
                <div class="flex justify-between items-center text-xs">
                    <span class="text-gray-300 font-medium">
                        <svg class="w-3 h-3 inline-block mr-1 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                        Step {{ $this->provisioningProgress['current_step'] }} of {{ $this->provisioningProgress['total_steps'] }}
                    </span>
                    @if($this->provisioningProgress['estimated_time_remaining'])
                        <span class="text-gray-300 font-medium">
                            <svg class="w-3 h-3 inline-block mr-1 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            ~{{ gmdate('i:s', $this->provisioningProgress['estimated_time_remaining']) }} remaining
                        </span>
                    @endif
                </div>
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
                                    <input type="checkbox" wire:model="installPostgreSQL" class="rounded text-blue-600">
                                    <span class="text-sm text-gray-300">PostgreSQL 16</span>
                                </label>

                                <label class="flex items-center gap-2 p-3 bg-gray-900 rounded-lg cursor-pointer hover:bg-gray-700/50">
                                    <input type="checkbox" wire:model="installRedis" class="rounded text-blue-600">
                                    <span class="text-sm text-gray-300">Redis Server</span>
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

                                <label class="flex items-center gap-2 p-3 bg-gray-900 rounded-lg cursor-pointer hover:bg-gray-700/50">
                                    <input type="checkbox" wire:model="installSupervisor" class="rounded text-blue-600">
                                    <span class="text-sm text-gray-300">Supervisor (Process manager)</span>
                                </label>

                                <label class="flex items-center gap-2 p-3 bg-gray-900 rounded-lg cursor-pointer hover:bg-gray-700/50">
                                    <input type="checkbox" wire:model.live="installFrankenphp" class="rounded text-blue-600">
                                    <span class="text-sm text-gray-300">FrankenPHP (Laravel Octane)</span>
                                </label>

                                <label class="flex items-center gap-2 p-3 bg-gray-900 rounded-lg cursor-pointer hover:bg-gray-700/50">
                                    <input type="checkbox" wire:model.live="installFail2ban" class="rounded text-blue-600">
                                    <span class="text-sm text-gray-300">Fail2ban (Intrusion prevention)</span>
                                </label>

                                <label class="flex items-center gap-2 p-3 bg-gray-900 rounded-lg cursor-pointer hover:bg-gray-700/50 col-span-2">
                                    <input type="checkbox" wire:model.live="configureWildcardNginx" class="rounded text-blue-600">
                                    <span class="text-sm text-gray-300">Wildcard Subdomain Routing</span>
                                    <span class="text-xs text-gray-500 ml-1">(*.domain.com → Octane)</span>
                                </label>
                            </div>
                        </div>

                        @if($configureWildcardNginx)
                            <div class="p-4 bg-gray-900 border border-blue-500/20 rounded-lg space-y-4">
                                <h4 class="text-sm font-medium text-blue-300 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                    </svg>
                                    Wildcard Nginx Configuration
                                </h4>
                                <p class="text-xs text-gray-400">
                                    Generates two server blocks: wildcard subdomain proxy (<code class="text-blue-300">*.domain</code>)
                                    and a custom domain catch-all. Requires Cloudflare origin certificates at
                                    <code class="text-blue-300">/etc/ssl/certs/cloudflare-origin.pem</code>.
                                </p>

                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">Base Domain</label>
                                    <input type="text" wire:model="wildcardDomain"
                                           class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-white placeholder-gray-500"
                                           placeholder="e.g. store-eg.com">
                                    @error('wildcardDomain')
                                        <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span>
                                    @enderror
                                    <p class="text-xs text-gray-500 mt-1">Nginx will serve <code>domain.com</code> and <code>*.domain.com</code></p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">Project Path</label>
                                    <input type="text" wire:model="wildcardProjectPath"
                                           class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-white placeholder-gray-500"
                                           placeholder="e.g. /var/www/e-store">
                                    @error('wildcardProjectPath')
                                        <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span>
                                    @enderror
                                    <p class="text-xs text-gray-500 mt-1">Absolute path to your Laravel project root</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">Octane Port</label>
                                    <input type="number" wire:model="octanePort" min="1024" max="65535"
                                           class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-white"
                                           placeholder="8090">
                                    @error('octanePort')
                                        <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span>
                                    @enderror
                                    <p class="text-xs text-gray-500 mt-1">Port where FrankenPHP/Octane is listening (default: 8090)</p>
                                </div>
                            </div>
                        @endif

                        {{-- Gap 3: Queue Worker Configuration (shown when Supervisor is checked) --}}
                        @if($installSupervisor)
                            <div class="p-4 bg-gray-900 border border-purple-500/20 rounded-lg space-y-4">
                                <h4 class="text-sm font-medium text-purple-300 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    Queue Worker Configuration
                                </h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">Worker Count</label>
                                        <input type="number" wire:model="queueWorkerCount" min="1" max="16"
                                               class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-white">
                                        @error('queueWorkerCount')
                                            <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span>
                                        @enderror
                                        <p class="text-xs text-gray-500 mt-1">Number of parallel queue worker processes</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">Queue Names</label>
                                        <input type="text" wire:model="queueNames"
                                               class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-white"
                                               placeholder="default">
                                        @error('queueNames')
                                            <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span>
                                        @enderror
                                        <p class="text-xs text-gray-500 mt-1">Comma-separated queue names (e.g. default,emails,notifications)</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Gap 4: Octane Settings (shown when FrankenPHP is checked) --}}
                        @if($installFrankenphp)
                            <div class="p-4 bg-gray-900 border border-orange-500/20 rounded-lg space-y-4">
                                <h4 class="text-sm font-medium text-orange-300 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    Octane / FrankenPHP Settings
                                </h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">Worker Count</label>
                                        <input type="number" wire:model="octaneWorkers" min="1" max="64"
                                               class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-white">
                                        @error('octaneWorkers')
                                            <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span>
                                        @enderror
                                        <p class="text-xs text-gray-500 mt-1">Number of FrankenPHP workers (default: 4)</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">Octane Port</label>
                                        <input type="number" wire:model="octanePort" min="1024" max="65535"
                                               class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-lg text-white"
                                               placeholder="8090">
                                        @error('octanePort')
                                            <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span>
                                        @enderror
                                        <p class="text-xs text-gray-500 mt-1">Port for Octane to listen on (default: 8090)</p>
                                    </div>
                                </div>
                            </div>
                        @endif

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

                        @if($installPostgreSQL)
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">PostgreSQL Password</label>
                                    <input type="password" wire:model="postgresqlPassword"
                                           class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white"
                                           placeholder="Enter PostgreSQL password">
                                    @error('postgresqlPassword')
                                        <span class="text-xs text-red-400 mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">Databases (comma-separated)</label>
                                    <input type="text" wire:model="postgresqlDatabases"
                                           class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white"
                                           placeholder="e.g. app_db, admin_db">
                                    <p class="text-xs text-gray-500 mt-1">Leave empty to create databases later</p>
                                </div>

                                {{-- Additional Databases (multi-connection .env support) --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">
                                        Additional Databases
                                        <span class="text-xs font-normal text-gray-500 ml-1">— adds separate .env connection blocks (e.g. for multi-vertical apps)</span>
                                    </label>

                                    @if(count($additionalDatabases) > 0)
                                        <div class="space-y-2 mb-3">
                                            @foreach($additionalDatabases as $index => $dbName)
                                                <div class="flex items-center gap-2 px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg">
                                                    <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 3 8 3s8-.79 8-3V7M4 7c0 2.21 3.582 3 8 3s8-.79 8-3M4 7c0-2.21 3.582 3 8 3s8-.79 8-3"></path>
                                                    </svg>
                                                    <span class="text-sm text-gray-300 flex-1 font-mono">{{ $dbName }}</span>
                                                    <button type="button"
                                                            wire:click="removeAdditionalDatabase({{ $index }})"
                                                            class="text-gray-500 hover:text-red-400 transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div class="flex gap-2">
                                        <input type="text"
                                               wire:model="newAdditionalDatabase"
                                               wire:keydown.enter.prevent="addAdditionalDatabase"
                                               class="flex-1 px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white text-sm"
                                               placeholder="e.g. lebsa">
                                        <button type="button"
                                                wire:click="addAdditionalDatabase"
                                                class="px-3 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors text-sm whitespace-nowrap">
                                            Add Database
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Each database gets its own <code class="text-gray-400">DB_*</code> block in .env using the name as prefix (e.g. <code class="text-gray-400">LEBSA_DB_DATABASE=lebsa</code>). Shares the same user/password as the main DB.
                                    </p>
                                </div>
                            </div>
                        @endif

                        @if($installRedis)
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">Redis Password</label>
                                    <input type="password" wire:model="redisPassword"
                                           class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white"
                                           placeholder="Optional">
                                    <p class="text-xs text-gray-500 mt-1">Leave empty for no authentication</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">Max Memory (MB)</label>
                                    <input type="number" wire:model="redisMaxMemoryMB" min="64" max="8192"
                                           class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white">
                                    @error('redisMaxMemoryMB')
                                        <span class="text-xs text-red-400 mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
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
