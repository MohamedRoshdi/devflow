<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-orange-800 via-red-900 to-orange-800 shadow-2xl">
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
                            </svg>
                            <div>
                                <h1 class="text-2xl font-bold text-white">Firewall Manager</h1>
                                <p class="text-white/80">{{ $server->name }} - UFW Configuration</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button wire:click="loadFirewallStatus" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" wire:loading.class="animate-spin" wire:target="loadFirewallStatus" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Refresh
                    </button>
                    @if($ufwInstalled && $ufwEnabled)
                        <button wire:click="openAddRuleModal"
                            class="px-4 py-2 bg-white text-orange-800 font-medium rounded-lg hover:bg-gray-100 transition-colors flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Rule
                        </button>
                    @endif
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
        <!-- Not Installed State -->
        @if(!$ufwInstalled)
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-8 text-center">
                <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <h3 class="text-xl font-semibold text-white mb-2">UFW Not Detected</h3>
                <p class="text-gray-400 mb-4">UFW (Uncomplicated Firewall) was not detected on this server.</p>

                @if($statusMessage)
                    <p class="text-yellow-400 text-sm mb-4">{{ $statusMessage }}</p>
                @endif

                @if($rawOutput)
                    <div class="mb-6 text-left max-w-lg mx-auto">
                        <details class="bg-gray-900/50 rounded-lg p-3">
                            <summary class="text-gray-400 text-sm cursor-pointer hover:text-gray-300">Show Debug Output</summary>
                            <pre class="mt-2 text-xs text-gray-500 overflow-x-auto whitespace-pre-wrap">{{ $rawOutput }}</pre>
                        </details>
                    </div>
                @endif

                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <button wire:click="loadFirewallStatus" wire:loading.attr="disabled"
                        class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors">
                        <span wire:loading.remove wire:target="loadFirewallStatus">Retry Detection</span>
                        <span wire:loading wire:target="loadFirewallStatus">Checking...</span>
                    </button>
                    <button wire:click="installUfw" wire:loading.attr="disabled"
                        class="px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-lg transition-colors">
                        <span wire:loading.remove wire:target="installUfw">Install UFW</span>
                        <span wire:loading wire:target="installUfw">Installing...</span>
                    </button>
                </div>
            </div>
        @else
            <!-- Status Card -->
            <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="p-3 rounded-lg {{ $ufwEnabled ? 'bg-green-500/20' : 'bg-red-500/20' }}">
                            <svg class="w-8 h-8 {{ $ufwEnabled ? 'text-green-400' : 'text-red-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-white">Firewall Status</h3>
                            <p class="text-gray-400">
                                UFW is currently
                                <span class="font-medium {{ $ufwEnabled ? 'text-green-400' : 'text-red-400' }}">
                                    {{ $ufwEnabled ? 'Active' : 'Inactive' }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <div>
                        @if($ufwEnabled)
                            <button wire:click="confirmDisableFirewall"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                                Disable Firewall
                            </button>
                        @else
                            <button wire:click="enableFirewall" wire:loading.attr="disabled"
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                                <span wire:loading.remove wire:target="enableFirewall">Enable Firewall</span>
                                <span wire:loading wire:target="enableFirewall">Enabling...</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Firewall Rules -->
            @if($ufwEnabled)
                <div class="bg-gray-800/50 backdrop-blur-sm rounded-2xl border border-gray-700/50 p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Firewall Rules</h3>

                    @if(empty($rules))
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="text-gray-400">No firewall rules configured</p>
                            <button wire:click="openAddRuleModal" class="mt-4 text-orange-400 hover:text-orange-300">
                                Add your first rule
                            </button>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-left text-gray-400 text-sm border-b border-gray-700">
                                        <th class="pb-3 font-medium">#</th>
                                        <th class="pb-3 font-medium">To</th>
                                        <th class="pb-3 font-medium">Action</th>
                                        <th class="pb-3 font-medium">From</th>
                                        <th class="pb-3 font-medium text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-700/50">
                                    @foreach($rules as $rule)
                                        <tr class="text-gray-300">
                                            <td class="py-3 text-gray-500">{{ $rule['number'] }}</td>
                                            <td class="py-3">{{ $rule['parsed']['to'] ?: 'Anywhere' }}</td>
                                            <td class="py-3">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                    {{ $rule['parsed']['action'] === 'allow' ? 'bg-green-500/20 text-green-400' : '' }}
                                                    {{ $rule['parsed']['action'] === 'deny' ? 'bg-red-500/20 text-red-400' : '' }}
                                                    {{ $rule['parsed']['action'] === 'reject' ? 'bg-orange-500/20 text-orange-400' : '' }}
                                                    {{ $rule['parsed']['action'] === 'limit' ? 'bg-yellow-500/20 text-yellow-400' : '' }}">
                                                    {{ strtoupper($rule['parsed']['action']) }}
                                                </span>
                                            </td>
                                            <td class="py-3">{{ $rule['parsed']['from'] ?: 'Anywhere' }}</td>
                                            <td class="py-3 text-right">
                                                <button wire:click="deleteRule({{ $rule['number'] }})"
                                                    wire:confirm="Are you sure you want to delete this rule?"
                                                    class="text-red-400 hover:text-red-300 transition-colors">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endif
        @endif
    </div>

    <!-- Add Rule Modal -->
    @if($showAddRuleModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/70 transition-opacity" wire:click="closeAddRuleModal"></div>

                <div class="relative w-full max-w-lg bg-gray-800 rounded-2xl shadow-xl border border-gray-700">
                    <div class="p-6">
                        <h3 class="text-xl font-semibold text-white mb-6">Add Firewall Rule</h3>

                        <form wire:submit.prevent="addRule" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Port</label>
                                <input type="text" wire:model="rulePort" placeholder="e.g., 80, 443, 8080:8090"
                                    class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                @error('rulePort') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Protocol</label>
                                    <select wire:model="ruleProtocol"
                                        class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-orange-500">
                                        <option value="tcp">TCP</option>
                                        <option value="udp">UDP</option>
                                        <option value="any">Any</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Action</label>
                                    <select wire:model="ruleAction"
                                        class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-orange-500">
                                        <option value="allow">Allow</option>
                                        <option value="deny">Deny</option>
                                        <option value="reject">Reject</option>
                                        <option value="limit">Limit</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">From IP (optional)</label>
                                <input type="text" wire:model="ruleFromIp" placeholder="e.g., 192.168.1.0/24"
                                    class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                                @error('ruleFromIp') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Description (optional)</label>
                                <input type="text" wire:model="ruleDescription" placeholder="e.g., Web server"
                                    class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                            </div>

                            <div class="flex justify-end gap-3 pt-4">
                                <button type="button" wire:click="closeAddRuleModal"
                                    class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors">
                                    Add Rule
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Confirm Disable Modal -->
    @if($showConfirmDisable)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/70 transition-opacity" wire:click="$set('showConfirmDisable', false)"></div>

                <div class="relative w-full max-w-md bg-gray-800 rounded-2xl shadow-xl border border-gray-700 p-6">
                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-500/20 mb-4">
                            <svg class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-white mb-2">Disable Firewall?</h3>
                        <p class="text-gray-400 mb-6">
                            This will disable the firewall and leave your server exposed. Are you sure you want to continue?
                        </p>
                        <div class="flex justify-center gap-3">
                            <button wire:click="$set('showConfirmDisable', false)"
                                class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors">
                                Cancel
                            </button>
                            <button wire:click="disableFirewall"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                                Yes, Disable
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
