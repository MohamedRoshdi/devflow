<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-red-800 via-red-900 to-red-800 shadow-2xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex items-center gap-4">
                    <a href="{{ route('servers.security', $server) }}" class="p-2 bg-white/10 rounded-lg hover:bg-white/20 transition-colors">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <div class="flex items-center gap-3">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                            <div>
                                <h1 class="text-2xl font-bold text-white">Fail2ban Manager</h1>
                                <p class="text-white/80">{{ $server->name }} - Intrusion Prevention</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button wire:click="loadFail2banStatus" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" wire:loading.class="animate-spin" wire:target="loadFail2banStatus" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Message -->
    @if($flashMessage)
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="rounded-lg p-4 {{ $flashType === 'success' ? 'bg-green-900/50 text-green-200 border border-green-700' : 'bg-red-900/50 text-red-200 border border-red-700' }}">
                {{ $flashMessage }}
            </div>
        </div>
    @endif

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if(!$fail2banInstalled)
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-8 text-center">
                <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <h3 class="text-xl font-semibold text-white mb-2">Fail2ban Not Installed</h3>
                <p class="text-gray-400 mb-6">Fail2ban protects against brute-force attacks. Install it to enhance server security.</p>
                <button wire:click="installFail2ban" wire:loading.attr="disabled"
                    class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                    <span wire:loading.remove wire:target="installFail2ban">Install Fail2ban</span>
                    <span wire:loading wire:target="installFail2ban">Installing...</span>
                </button>
            </div>
        @else
            <!-- Status Card -->
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="p-3 rounded-lg {{ $fail2banEnabled ? 'bg-green-500/20' : 'bg-red-500/20' }}">
                            <svg class="w-8 h-8 {{ $fail2banEnabled ? 'text-green-400' : 'text-red-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-white">Fail2ban Status</h3>
                            <p class="text-gray-400">
                                Service is
                                <span class="font-medium {{ $fail2banEnabled ? 'text-green-400' : 'text-red-400' }}">
                                    {{ $fail2banEnabled ? 'Running' : 'Stopped' }}
                                </span>
                                @if(!empty($jails))
                                    &bull; {{ count($jails) }} jail(s) active
                                @endif
                            </p>
                        </div>
                    </div>
                    <div>
                        @if($fail2banEnabled)
                            <button wire:click="stopFail2ban" wire:loading.attr="disabled"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                                Stop Service
                            </button>
                        @else
                            <button wire:click="startFail2ban" wire:loading.attr="disabled"
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                                Start Service
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            @if($fail2banEnabled)
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Jails List -->
                    <div class="lg:col-span-1 bg-gray-800/50 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6">
                        <h3 class="text-lg font-semibold text-white mb-4">Jails</h3>
                        @if(empty($jails))
                            <p class="text-gray-400 text-sm">No jails configured</p>
                        @else
                            <div class="space-y-2">
                                @foreach($jails as $jail)
                                    <button wire:click="selectJail('{{ $jail }}')"
                                        class="w-full text-left px-4 py-3 rounded-lg transition-colors {{ $selectedJail === $jail ? 'bg-red-600 text-white' : 'bg-gray-900/50 text-gray-300 hover:bg-gray-700/50' }}">
                                        {{ $jail }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Banned & Whitelisted IPs -->
                    <div class="lg:col-span-3 bg-gray-800/50 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6">
                        <!-- Tabs -->
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex flex-wrap gap-2">
                                <button wire:click="switchTab('banned')"
                                    class="px-4 py-2 rounded-lg font-medium transition-colors {{ $activeTab === 'banned' ? 'bg-red-600 text-white' : 'bg-gray-700/50 text-gray-300 hover:bg-gray-600/50' }}">
                                    Banned IPs ({{ count($bannedIPs) }})
                                </button>
                                <button wire:click="switchTab('whitelist')"
                                    class="px-4 py-2 rounded-lg font-medium transition-colors {{ $activeTab === 'whitelist' ? 'bg-green-600 text-white' : 'bg-gray-700/50 text-gray-300 hover:bg-gray-600/50' }}">
                                    Whitelist ({{ count($whitelistedIPs) }})
                                </button>
                                <button wire:click="switchTab('attackers')"
                                    class="px-4 py-2 rounded-lg font-medium transition-colors {{ $activeTab === 'attackers' ? 'bg-orange-600 text-white' : 'bg-gray-700/50 text-gray-300 hover:bg-gray-600/50' }}">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                        SSH Attackers
                                    </span>
                                </button>
                                <button wire:click="switchTab('logins')"
                                    class="px-4 py-2 rounded-lg font-medium transition-colors {{ $activeTab === 'logins' ? 'bg-blue-600 text-white' : 'bg-gray-700/50 text-gray-300 hover:bg-gray-600/50' }}">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                        Login History
                                    </span>
                                </button>
                            </div>

                            @if($activeTab === 'banned' && count($bannedIPs) > 0)
                                <button wire:click="unbanAllIPs"
                                    wire:confirm="Are you sure you want to unban all {{ count($bannedIPs) }} IPs?"
                                    class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm rounded-lg transition-colors">
                                    Unban All
                                </button>
                            @elseif($activeTab === 'attackers')
                                <button wire:click="refreshAttackData"
                                    wire:loading.attr="disabled"
                                    class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm rounded-lg transition-colors flex items-center gap-2">
                                    <svg class="w-4 h-4" wire:loading.class="animate-spin" wire:target="refreshAttackData" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    Refresh
                                </button>
                            @endif
                        </div>

                        <!-- Banned IPs Tab Content -->
                        @if($activeTab === 'banned')
                            @if(empty($bannedIPs))
                                <div class="text-center py-8">
                                    <svg class="w-12 h-12 text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="text-gray-400">No IPs currently banned in this jail</p>
                                </div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="text-left text-gray-400 text-sm border-b border-gray-700">
                                                <th class="pb-3 font-medium">IP Address</th>
                                                <th class="pb-3 font-medium text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-700/50">
                                            @foreach($bannedIPs as $ip)
                                                <tr class="text-gray-300">
                                                    <td class="py-3">
                                                        <div class="flex items-center gap-2">
                                                            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                            </svg>
                                                            {{ $ip }}
                                                        </div>
                                                    </td>
                                                    <td class="py-3 text-right">
                                                        <div class="flex items-center justify-end gap-2">
                                                            <button wire:click="transferToWhitelist('{{ $ip }}')"
                                                                wire:confirm="Transfer {{ $ip }} to whitelist? This will unban the IP and prevent future bans."
                                                                class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors flex items-center gap-1"
                                                                title="Transfer to Whitelist">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                                </svg>
                                                                Whitelist
                                                            </button>
                                                            <button wire:click="unbanIP('{{ $ip }}')"
                                                                wire:confirm="Are you sure you want to unban {{ $ip }}?"
                                                                class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition-colors">
                                                                Unban
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        @endif

                        <!-- Whitelist Tab Content -->
                        @if($activeTab === 'whitelist')
                            <!-- Add to Whitelist Form -->
                            <div class="mb-6 bg-gray-900/50 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-gray-300 mb-3">Add IP to Whitelist</h4>
                                <div class="flex gap-3">
                                    <input type="text"
                                        wire:model="newWhitelistIP"
                                        placeholder="Enter IP address (e.g., 192.168.1.1)"
                                        class="flex-1 px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:border-green-500 focus:ring-1 focus:ring-green-500">
                                    <button wire:click="addToWhitelist"
                                        wire:loading.attr="disabled"
                                        class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                        <span wire:loading.remove wire:target="addToWhitelist">Add to Whitelist</span>
                                        <span wire:loading wire:target="addToWhitelist">Adding...</span>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">Whitelisted IPs will never be banned by Fail2ban</p>
                            </div>

                            <!-- Whitelisted IPs List -->
                            @if(empty($whitelistedIPs))
                                <div class="text-center py-8">
                                    <svg class="w-12 h-12 text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                    <p class="text-gray-400">No IPs whitelisted yet</p>
                                    <p class="text-gray-500 text-sm mt-1">Add trusted IPs to prevent them from being banned</p>
                                </div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="text-left text-gray-400 text-sm border-b border-gray-700">
                                                <th class="pb-3 font-medium">IP Address / Network</th>
                                                <th class="pb-3 font-medium text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-700/50">
                                            @foreach($whitelistedIPs as $ip)
                                                <tr class="text-gray-300">
                                                    <td class="py-3">
                                                        <div class="flex items-center gap-2">
                                                            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                                            </svg>
                                                            <span>{{ $ip }}</span>
                                                            @if(in_array($ip, ['127.0.0.1', '127.0.0.0/8', '::1']))
                                                                <span class="px-2 py-0.5 text-xs bg-gray-700 text-gray-400 rounded">System</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="py-3 text-right">
                                                        @if(!in_array($ip, ['127.0.0.1', '127.0.0.0/8', '::1']))
                                                            <button wire:click="removeFromWhitelist('{{ $ip }}')"
                                                                wire:confirm="Are you sure you want to remove {{ $ip }} from whitelist?"
                                                                class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition-colors">
                                                                Remove
                                                            </button>
                                                        @else
                                                            <span class="text-xs text-gray-500">Protected</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        @endif

                        <!-- SSH Attackers Tab Content -->
                        @if($activeTab === 'attackers')
                            <!-- Manual Ban Form -->
                            <div class="mb-6 bg-gray-900/50 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-gray-300 mb-3">Manually Ban IP</h4>
                                <div class="flex gap-3">
                                    <input type="text"
                                        wire:model="manualBanIP"
                                        placeholder="Enter IP address to ban"
                                        class="flex-1 px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:border-red-500 focus:ring-1 focus:ring-red-500">
                                    <button wire:click="manualBan"
                                        wire:loading.attr="disabled"
                                        class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                                        Ban IP
                                    </button>
                                </div>
                            </div>

                            <!-- Stats Bar -->
                            @if($totalAttacks > 0)
                                <div class="mb-4 p-4 bg-orange-900/30 border border-orange-700/50 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <svg class="w-8 h-8 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                            </svg>
                                            <div>
                                                <p class="text-orange-200 font-semibold">{{ number_format($totalAttacks) }} Failed Login Attempts</p>
                                                <p class="text-orange-300/70 text-sm">From {{ count($topAttackers) }} unique IP addresses</p>
                                            </div>
                                        </div>
                                        @if(count($selectedAttackers) > 0)
                                            <button wire:click="banSelectedAttackers"
                                                wire:confirm="Ban {{ count($selectedAttackers) }} selected IPs?"
                                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors flex items-center gap-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                </svg>
                                                Ban Selected ({{ count($selectedAttackers) }})
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @if(empty($topAttackers))
                                <div class="text-center py-8">
                                    <svg class="w-12 h-12 text-green-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                    <p class="text-gray-400">No attack data found</p>
                                    <p class="text-gray-500 text-sm mt-1">Click "Refresh" to scan SSH logs for attackers</p>
                                </div>
                            @else
                                <!-- Select All -->
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-4">
                                        <button wire:click="selectAllAttackers" class="text-sm text-blue-400 hover:text-blue-300">Select All</button>
                                        <button wire:click="deselectAllAttackers" class="text-sm text-gray-400 hover:text-gray-300">Deselect All</button>
                                    </div>
                                    <p class="text-gray-500 text-sm">Select IPs to ban in bulk</p>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="text-left text-gray-400 text-sm border-b border-gray-700">
                                                <th class="pb-3 font-medium w-12"></th>
                                                <th class="pb-3 font-medium">IP Address</th>
                                                <th class="pb-3 font-medium text-center">Attempts</th>
                                                <th class="pb-3 font-medium text-center">Threat Level</th>
                                                <th class="pb-3 font-medium text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-700/50">
                                            @foreach($topAttackers as $attacker)
                                                @php
                                                    $isBanned = in_array($attacker['ip'], $bannedIPs);
                                                    $threatLevel = $attacker['attempts'] >= 100 ? 'critical' : ($attacker['attempts'] >= 50 ? 'high' : ($attacker['attempts'] >= 20 ? 'medium' : 'low'));
                                                @endphp
                                                <tr class="text-gray-300 {{ $isBanned ? 'opacity-50' : '' }}">
                                                    <td class="py-3">
                                                        @if(!$isBanned)
                                                            <input type="checkbox"
                                                                wire:click="toggleAttackerSelection('{{ $attacker['ip'] }}')"
                                                                @checked(in_array($attacker['ip'], $selectedAttackers))
                                                                class="w-4 h-4 rounded bg-gray-700 border-gray-600 text-red-500 focus:ring-red-500 focus:ring-offset-gray-800">
                                                        @endif
                                                    </td>
                                                    <td class="py-3">
                                                        <div class="flex items-center gap-2">
                                                            @if($isBanned)
                                                                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                                </svg>
                                                            @else
                                                                <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                                </svg>
                                                            @endif
                                                            <span class="font-mono">{{ $attacker['ip'] }}</span>
                                                            @if($isBanned)
                                                                <span class="px-2 py-0.5 text-xs bg-red-900/50 text-red-400 rounded">Banned</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="py-3 text-center">
                                                        <span class="font-bold text-{{ $threatLevel === 'critical' ? 'red' : ($threatLevel === 'high' ? 'orange' : ($threatLevel === 'medium' ? 'yellow' : 'gray')) }}-400">
                                                            {{ number_format($attacker['attempts']) }}
                                                        </span>
                                                    </td>
                                                    <td class="py-3 text-center">
                                                        <span class="px-2 py-1 text-xs rounded-full {{
                                                            $threatLevel === 'critical' ? 'bg-red-900/50 text-red-400 border border-red-700' :
                                                            ($threatLevel === 'high' ? 'bg-orange-900/50 text-orange-400 border border-orange-700' :
                                                            ($threatLevel === 'medium' ? 'bg-yellow-900/50 text-yellow-400 border border-yellow-700' :
                                                            'bg-gray-700/50 text-gray-400 border border-gray-600')) }}">
                                                            {{ ucfirst($threatLevel) }}
                                                        </span>
                                                    </td>
                                                    <td class="py-3 text-right">
                                                        @if(!$isBanned)
                                                            <button wire:click="banAttacker('{{ $attacker['ip'] }}')"
                                                                wire:confirm="Ban IP {{ $attacker['ip'] }} with {{ $attacker['attempts'] }} failed attempts?"
                                                                class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg transition-colors">
                                                                Ban
                                                            </button>
                                                        @else
                                                            <button wire:click="unbanIP('{{ $attacker['ip'] }}')"
                                                                class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition-colors">
                                                                Unban
                                                            </button>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        @endif

                        <!-- Login History Tab Content -->
                        @if($activeTab === 'logins')
                            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                                <!-- Failed Logins -->
                                <div>
                                    <h4 class="text-lg font-semibold text-red-400 mb-4 flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                        Recent Failed Logins
                                    </h4>
                                    @if(empty($failedLogins))
                                        <div class="text-center py-8 bg-gray-900/50 rounded-lg">
                                            <p class="text-gray-400">No failed login data</p>
                                            <p class="text-gray-500 text-sm mt-1">Click "Refresh" on the Attackers tab to load data</p>
                                        </div>
                                    @else
                                        <div class="bg-gray-900/50 rounded-lg overflow-hidden max-h-[500px] overflow-y-auto">
                                            <table class="w-full text-sm">
                                                <thead class="sticky top-0 bg-gray-800">
                                                    <tr class="text-left text-gray-400">
                                                        <th class="px-4 py-2 font-medium">Time</th>
                                                        <th class="px-4 py-2 font-medium">IP</th>
                                                        <th class="px-4 py-2 font-medium">User</th>
                                                        <th class="px-4 py-2 font-medium">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-700/50">
                                                    @foreach(array_slice($failedLogins, 0, 30) as $login)
                                                        <tr class="text-gray-300 hover:bg-gray-800/50">
                                                            <td class="px-4 py-2 text-gray-500 font-mono text-xs">{{ $login['timestamp'] }}</td>
                                                            <td class="px-4 py-2 font-mono">{{ $login['ip'] }}</td>
                                                            <td class="px-4 py-2">
                                                                <span class="{{ $login['type'] === 'invalid_user' ? 'text-red-400' : 'text-yellow-400' }}">
                                                                    {{ $login['user'] }}
                                                                </span>
                                                            </td>
                                                            <td class="px-4 py-2">
                                                                <button wire:click="banAttacker('{{ $login['ip'] }}')"
                                                                    class="px-2 py-1 bg-red-600 hover:bg-red-700 text-white text-xs rounded transition-colors">
                                                                    Ban
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>

                                <!-- Successful Logins -->
                                <div>
                                    <h4 class="text-lg font-semibold text-green-400 mb-4 flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Recent Successful Logins
                                    </h4>
                                    @if(empty($successfulLogins))
                                        <div class="text-center py-8 bg-gray-900/50 rounded-lg">
                                            <p class="text-gray-400">No login data</p>
                                            <p class="text-gray-500 text-sm mt-1">Click "Refresh" on the Attackers tab to load data</p>
                                        </div>
                                    @else
                                        <div class="bg-gray-900/50 rounded-lg overflow-hidden max-h-[500px] overflow-y-auto">
                                            <table class="w-full text-sm">
                                                <thead class="sticky top-0 bg-gray-800">
                                                    <tr class="text-left text-gray-400">
                                                        <th class="px-4 py-2 font-medium">Time</th>
                                                        <th class="px-4 py-2 font-medium">IP</th>
                                                        <th class="px-4 py-2 font-medium">User</th>
                                                        <th class="px-4 py-2 font-medium">Method</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-700/50">
                                                    @foreach($successfulLogins as $login)
                                                        <tr class="text-gray-300 hover:bg-gray-800/50">
                                                            <td class="px-4 py-2 text-gray-500 font-mono text-xs">{{ $login['timestamp'] }}</td>
                                                            <td class="px-4 py-2 font-mono">{{ $login['ip'] }}</td>
                                                            <td class="px-4 py-2 text-green-400">{{ $login['user'] }}</td>
                                                            <td class="px-4 py-2">
                                                                <span class="px-2 py-0.5 text-xs rounded {{ $login['method'] === 'publickey' ? 'bg-blue-900/50 text-blue-400' : 'bg-yellow-900/50 text-yellow-400' }}">
                                                                    {{ $login['method'] }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
