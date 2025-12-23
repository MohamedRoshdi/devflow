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

                    <!-- Banned IPs -->
                    <div class="lg:col-span-3 bg-gray-800/50 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-white">
                                Banned IPs - {{ $selectedJail }}
                            </h3>
                            <span class="text-gray-400 text-sm">{{ count($bannedIPs) }} banned</span>
                        </div>

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
                                                    <button wire:click="unbanIP('{{ $ip }}')"
                                                        wire:confirm="Are you sure you want to unban {{ $ip }}?"
                                                        class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg transition-colors">
                                                        Unban
                                                    </button>
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
        @endif
    </div>
</div>
