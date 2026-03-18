<div>
    <!-- Hero Section -->
    <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-orange-500 via-orange-600 to-amber-600 dark:from-orange-600 dark:via-orange-700 dark:to-amber-700 p-8 shadow-xl overflow-hidden">
        <div class="absolute inset-0 bg-black/10 dark:bg-black/20"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <div class="p-2 bg-white/20 dark:bg-white/10 backdrop-blur-md rounded-lg">
                        <!-- Cloudflare-style cloud icon -->
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M10.585 19.99c-.05-.1-.09-.21-.09-.32 0-.23.13-.44.33-.55l.44-.22c.63-.31 1.08-.85 1.23-1.49.16-.66.03-1.35-.37-1.9-.37-.51-.93-.83-1.55-.86H4.5a2.5 2.5 0 110-5h.14a3.5 3.5 0 016.72-1.28A4 4 0 0119 12.5a3.5 3.5 0 01-3.5 3.5h-4.07l-.83 4z"/>
                        </svg>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-bold text-white">Cloudflare DNS</h1>
                </div>
                <p class="text-white/90 text-lg">Manage DNS records for your Cloudflare zone</p>
            </div>
            <div class="flex items-center gap-3">
                @if($isConnected)
                    <span class="inline-flex items-center gap-2 px-4 py-2 bg-green-500/20 backdrop-blur-md border border-green-400/30 text-green-100 rounded-lg font-medium text-sm">
                        <span class="w-2 h-2 rounded-full bg-green-300 animate-pulse"></span>
                        Connected
                    </span>
                    <button wire:click="openAddModal"
                            class="bg-white/20 hover:bg-white/30 backdrop-blur-md text-white font-semibold px-6 py-3 rounded-lg transition-all duration-300 hover:scale-105 shadow-lg">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Record
                    </button>
                @else
                    <span class="inline-flex items-center gap-2 px-4 py-2 bg-red-500/20 backdrop-blur-md border border-red-400/30 text-red-100 rounded-lg font-medium text-sm">
                        <span class="w-2 h-2 rounded-full bg-red-300"></span>
                        Not Configured
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Configuration Form (when not connected) -->
    @if(!$isConnected)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 mb-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                    <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Configure Cloudflare Access</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Enter your API token and Zone ID to get started</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        API Token
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="password" wire:model="apiToken"
                           placeholder="Your Cloudflare API Token"
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 dark:focus:ring-orange-600 focus:border-transparent placeholder-gray-400 dark:placeholder-gray-500">
                    @error('apiToken')
                        <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                    @enderror
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Create at <a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank" class="text-orange-500 hover:underline">Cloudflare Dashboard → API Tokens</a>
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Zone ID
                        <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model="zoneId"
                           placeholder="e.g. a1b2c3d4e5f6..."
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 dark:focus:ring-orange-600 focus:border-transparent font-mono text-sm placeholder-gray-400 dark:placeholder-gray-500">
                    @error('zoneId')
                        <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                    @enderror
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Found in your Cloudflare domain's Overview page (right sidebar)
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button wire:click="saveCredentials" wire:loading.attr="disabled"
                        class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-semibold rounded-lg shadow-lg transition-all duration-300 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                    <svg wire:loading wire:target="saveCredentials" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Save & Connect
                </button>
                <button wire:click="testConnection" wire:loading.attr="disabled"
                        class="inline-flex items-center px-6 py-2.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg transition-colors disabled:opacity-50">
                    <svg wire:loading wire:target="testConnection" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Test Connection
                </button>
            </div>
        </div>
    @endif

    <!-- Zone Info Bar (when connected) -->
    @if($isConnected && $zoneInfo !== null)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 mb-6 flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="flex items-center gap-3 flex-1">
                <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex-shrink-0">
                    <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ $zoneInfo['name'] ?? 'Unknown Zone' }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $zoneInfo['id'] ?? $zoneId }}</p>
                </div>
                @php
                    $zoneStatus = $zoneInfo['status'] ?? 'unknown';
                @endphp
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                    {{ $zoneStatus === 'active' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400' }}">
                    {{ ucfirst($zoneStatus) }}
                </span>
            </div>
            <div class="flex items-center gap-3">
                <button wire:click="loadRecords" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
                    <svg wire:loading wire:target="loadRecords" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <svg wire:loading.remove wire:target="loadRecords" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Refresh
                </button>
                <button wire:click="$set('isConnected', false)"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Reconfigure
                </button>
            </div>
        </div>
    @endif

    <!-- DNS Records Table (when connected) -->
    @if($isConnected)
        @if(count($records) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">DNS Records</h3>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ count($records) }} record{{ count($records) !== 1 ? 's' : '' }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Content</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Proxy</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">TTL</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($records as $record)
                                <tr wire:key="record-{{ $record['id'] }}" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $typeColors = [
                                                'A' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
                                                'AAAA' => 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-400',
                                                'CNAME' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
                                                'MX' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400',
                                                'TXT' => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
                                            ];
                                            $typeColor = $typeColors[$record['type']] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300';
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold font-mono {{ $typeColor }}">
                                            {{ $record['type'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white font-mono">{{ $record['name'] }}</span>
                                    </td>
                                    <td class="px-6 py-4 max-w-xs">
                                        <span class="text-sm text-gray-600 dark:text-gray-300 font-mono truncate block" title="{{ $record['content'] }}">
                                            {{ $record['content'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button wire:click="toggleProxy('{{ $record['id'] }}')"
                                                wire:loading.attr="disabled"
                                                wire:target="toggleProxy('{{ $record['id'] }}')"
                                                title="{{ ($record['proxied'] ?? false) ? 'Proxied (click to disable)' : 'DNS only (click to enable proxy)' }}"
                                                class="flex items-center gap-2 group">
                                            @if($record['proxied'] ?? false)
                                                <!-- Orange cloud = proxied -->
                                                <svg class="w-8 h-5 text-orange-500 group-hover:text-orange-600 transition-colors" fill="currentColor" viewBox="0 0 32 20">
                                                    <path d="M25.2 8.4C24.8 5.4 22.3 3 19.2 3c-1.8 0-3.4.8-4.5 2.1C14 4.4 12.6 4 11 4 7.7 4 5 6.7 5 10c0 .1 0 .2 0 .3C3.3 10.8 2 12.3 2 14c0 2.2 1.8 4 4 4h19c2.2 0 4-1.8 4-4 0-2-1.5-3.7-3.8-3.6z"/>
                                                </svg>
                                                <span class="text-xs text-orange-600 dark:text-orange-400 font-medium">Proxied</span>
                                            @else
                                                <!-- Gray cloud = DNS only -->
                                                <svg class="w-8 h-5 text-gray-400 group-hover:text-gray-500 transition-colors" fill="currentColor" viewBox="0 0 32 20">
                                                    <path d="M25.2 8.4C24.8 5.4 22.3 3 19.2 3c-1.8 0-3.4.8-4.5 2.1C14 4.4 12.6 4 11 4 7.7 4 5 6.7 5 10c0 .1 0 .2 0 .3C3.3 10.8 2 12.3 2 14c0 2.2 1.8 4 4 4h19c2.2 0 4-1.8 4-4 0-2-1.5-3.7-3.8-3.6z"/>
                                                </svg>
                                                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">DNS only</span>
                                            @endif
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ ($record['ttl'] ?? 1) === 1 ? 'Auto' : ($record['ttl'] . 's') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <button wire:click="deleteRecord('{{ $record['id'] }}')"
                                                wire:confirm="Delete this DNS record? This cannot be undone."
                                                wire:loading.attr="disabled"
                                                wire:target="deleteRecord('{{ $record['id'] }}')"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-12 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No DNS Records</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">This zone has no DNS records yet. Add your first record to get started.</p>
                <button wire:click="openAddModal"
                        class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-semibold rounded-lg shadow-lg transition-all duration-300 hover:scale-105">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add First Record
                </button>
            </div>
        @endif
    @endif

    <!-- Add Record Modal -->
    @if($showAddModal)
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50"
             wire:click.self="closeAddModal">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full mx-4">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Add DNS Record</h2>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Create a new DNS record in your Cloudflare zone</p>
                </div>

                <div class="p-6 space-y-5">
                    <!-- Record Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Record Type</label>
                        <select wire:model="newRecordType"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 dark:focus:ring-orange-600 focus:border-transparent">
                            <option value="A">A — IPv4 address</option>
                            <option value="AAAA">AAAA — IPv6 address</option>
                            <option value="CNAME">CNAME — Canonical name</option>
                            <option value="MX">MX — Mail exchange</option>
                            <option value="TXT">TXT — Text record</option>
                        </select>
                        @error('newRecordType') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Name</label>
                        <input type="text" wire:model="newRecordName"
                               placeholder="e.g. @ or subdomain"
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 dark:focus:ring-orange-600 focus:border-transparent font-mono placeholder-gray-400 dark:placeholder-gray-500">
                        @error('newRecordName') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Content -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Content</label>
                        <input type="text" wire:model="newRecordContent"
                               placeholder="{{ $newRecordType === 'A' ? 'e.g. 192.0.2.1' : ($newRecordType === 'CNAME' ? 'e.g. example.com' : 'Value') }}"
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 dark:focus:ring-orange-600 focus:border-transparent font-mono placeholder-gray-400 dark:placeholder-gray-500">
                        @error('newRecordContent') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- TTL + Proxied row -->
                    <div class="flex items-start gap-6">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">TTL</label>
                            <select wire:model.number="newRecordTtl"
                                    :disabled="{{ $newRecordProxied ? 'true' : 'false' }}"
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 dark:focus:ring-orange-600 focus:border-transparent disabled:opacity-50">
                                <option value="1">Auto</option>
                                <option value="60">1 minute</option>
                                <option value="300">5 minutes</option>
                                <option value="3600">1 hour</option>
                                <option value="86400">1 day</option>
                            </select>
                        </div>

                        <div class="pt-8">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" wire:model="newRecordProxied"
                                       class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-orange-500 focus:ring-orange-500 dark:bg-gray-700">
                                <div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Proxied</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Route through Cloudflare</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                    <button wire:click="closeAddModal"
                            class="px-6 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors font-medium">
                        Cancel
                    </button>
                    <button wire:click="addRecord" wire:loading.attr="disabled"
                            class="px-6 py-2.5 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white rounded-lg transition-all duration-300 hover:scale-105 shadow-lg font-medium disabled:opacity-50 disabled:hover:scale-100">
                        <svg wire:loading wire:target="addRecord" class="animate-spin w-4 h-4 mr-2 inline" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Create Record
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
