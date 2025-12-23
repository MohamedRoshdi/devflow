<div>
    {{-- System Information Component --}}
    <div class="space-y-6">
        {{-- System Info Grid --}}
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-white">System Information</h3>
                <button wire:click="refreshSystemInfo" class="text-sm text-emerald-400 hover:text-emerald-300">Refresh</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($systemInfo as $key => $value)
                    <div class="bg-slate-900/50 rounded-lg p-4">
                        <div class="text-xs text-slate-400 mb-1">{{ ucwords(str_replace('_', ' ', $key)) }}</div>
                        <div class="text-white font-mono text-sm">{{ $value }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Configuration --}}
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Configuration</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-slate-900/50 rounded-lg p-4">
                    <div class="text-xs text-slate-400 mb-1">Environment</div>
                    <div class="text-white font-mono">{{ $appEnv }}</div>
                </div>
                <div class="bg-slate-900/50 rounded-lg p-4">
                    <div class="text-xs text-slate-400 mb-1">Cache Driver</div>
                    <div class="text-white font-mono">{{ $cacheDriver }}</div>
                </div>
                <div class="bg-slate-900/50 rounded-lg p-4">
                    <div class="text-xs text-slate-400 mb-1">Queue Driver</div>
                    <div class="text-white font-mono">{{ $queueDriver }}</div>
                </div>
                <div class="bg-slate-900/50 rounded-lg p-4">
                    <div class="text-xs text-slate-400 mb-1">Session Driver</div>
                    <div class="text-white font-mono">{{ $sessionDriver }}</div>
                </div>
            </div>
            <div class="mt-4">
                <button wire:click="toggleMaintenanceMode" class="px-4 py-2 rounded-lg font-medium text-sm {{ $maintenanceMode ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-amber-600 hover:bg-amber-500' }} text-white transition-colors">
                    {{ $maintenanceMode ? 'Disable Maintenance Mode' : 'Enable Maintenance Mode' }}
                </button>
            </div>
        </div>

        {{-- Database Info --}}
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Database</h3>
            @if(isset($databaseInfo['error']))
                <div class="text-red-400">Error: {{ $databaseInfo['error'] }}</div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="bg-slate-900/50 rounded-lg p-4">
                        <div class="text-xs text-slate-400 mb-1">Connection</div>
                        <div class="text-white font-mono">{{ $databaseInfo['connection'] ?? 'N/A' }}</div>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-4">
                        <div class="text-xs text-slate-400 mb-1">Database</div>
                        <div class="text-white font-mono">{{ $databaseInfo['database'] ?? 'N/A' }}</div>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-4">
                        <div class="text-xs text-slate-400 mb-1">Tables</div>
                        <div class="text-white font-mono">{{ $databaseInfo['tables_count'] ?? 'N/A' }}</div>
                    </div>
                </div>
                @if(!empty($pendingMigrations))
                    <div class="bg-amber-500/10 border border-amber-500/30 rounded-lg p-4 mb-4">
                        <div class="text-amber-400 font-medium mb-2">{{ count($pendingMigrations) }} Pending Migration(s)</div>
                        <div class="text-sm text-slate-300 space-y-1">
                            @foreach($pendingMigrations as $migration)
                                <div>• {{ $migration }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif
                <button wire:click="runMigrations" class="px-4 py-2 rounded-lg font-medium text-sm bg-emerald-600 text-white hover:bg-emerald-500 transition-colors">
                    Run Migrations
                </button>
            @endif
        </div>

        {{-- Domain Configuration --}}
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-white">Domain & URL</h3>
                <button wire:click="toggleDomainEditor" class="text-sm text-emerald-400 hover:text-emerald-300">
                    {{ $showDomainEditor ? 'Hide' : 'Edit' }}
                </button>
            </div>
            <div class="bg-slate-900/50 rounded-lg p-4 mb-4">
                <div class="text-xs text-slate-400 mb-1">APP_URL</div>
                <div class="text-white font-mono">{{ $currentAppUrl }}</div>
            </div>
            @if($showDomainEditor)
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Update APP_URL</label>
                        <input type="url" wire:model.defer="currentAppUrl" class="w-full px-4 py-2 rounded-lg bg-slate-900/50 border border-slate-700 text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <button wire:click="updateAppUrl($wire.currentAppUrl)" class="px-4 py-2 rounded-lg font-medium text-sm bg-emerald-600 text-white hover:bg-emerald-500 transition-colors">
                        Update URL
                    </button>
                </div>
            @endif
            @if(!empty($nginxSites))
                <div class="mt-4">
                    <div class="text-xs text-slate-400 mb-2">Nginx Sites Enabled</div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($nginxSites as $site)
                            <span class="px-3 py-1.5 rounded-lg text-sm bg-slate-900/50 text-slate-300">{{ $site }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Environment Editor --}}
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-white">Environment Variables</h3>
                <button wire:click="toggleEnvEditor" class="text-sm text-emerald-400 hover:text-emerald-300">
                    {{ $showEnvEditor ? 'Hide' : 'Show' }}
                </button>
            </div>
            @if($showEnvEditor)
                <div class="space-y-3">
                    @foreach($envVariables as $key => $value)
                        <div class="bg-slate-900/50 rounded-lg p-3">
                            <label class="block text-xs text-slate-400 mb-1">{{ $key }}</label>
                            <input type="text" wire:model.defer="envVariables.{{ $key }}" wire:change="updateEnvVariable('{{ $key }}', $event.target.value)" class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
