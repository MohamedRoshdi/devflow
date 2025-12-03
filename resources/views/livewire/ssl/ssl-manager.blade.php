<div class="space-y-6">
    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-4">
            <div class="text-sm text-gray-400 mb-1">Total Certificates</div>
            <div class="text-2xl font-bold text-white">{{ $this->statistics['total'] }}</div>
        </div>

        <div class="bg-gray-800 border border-green-500/30 rounded-lg p-4">
            <div class="text-sm text-gray-400 mb-1">Active</div>
            <div class="text-2xl font-bold text-green-400">{{ $this->statistics['active'] }}</div>
        </div>

        <div class="bg-gray-800 border border-yellow-500/30 rounded-lg p-4">
            <div class="text-sm text-gray-400 mb-1">Expiring Soon</div>
            <div class="text-2xl font-bold text-yellow-400">{{ $this->statistics['expiring_soon'] }}</div>
        </div>

        <div class="bg-gray-800 border border-red-500/30 rounded-lg p-4">
            <div class="text-sm text-gray-400 mb-1">Expired</div>
            <div class="text-2xl font-bold text-red-400">{{ $this->statistics['expired'] }}</div>
        </div>

        <div class="bg-gray-800 border border-gray-700 rounded-lg p-4">
            <div class="text-sm text-gray-400 mb-1">Failed</div>
            <div class="text-2xl font-bold text-gray-400">{{ $this->statistics['failed'] }}</div>
        </div>
    </div>

    {{-- Critical Certificates Alert --}}
    @if($this->criticalCertificates->isNotEmpty())
        <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-red-400 mb-2">Critical: Certificates Expiring Within 7 Days</h3>
                    <div class="space-y-2">
                        @foreach($this->criticalCertificates as $cert)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-300">{{ $cert->domain_name }}</span>
                                <div class="flex items-center gap-3">
                                    <span class="text-red-400">{{ $cert->daysUntilExpiry() }} days left</span>
                                    <button wire:click="renewCertificate({{ $cert->domain_id }})"
                                            class="px-2 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs transition-colors">
                                        Renew Now
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Filters and Actions --}}
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4">
        <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
            <div class="flex flex-col md:flex-row gap-3 flex-1 w-full md:w-auto">
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search domains..."
                       class="px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white placeholder-gray-500 flex-1 md:max-w-xs">

                <select wire:model.live="statusFilter"
                        class="px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-white">
                    <option value="all">All Status</option>
                    <option value="issued">Active</option>
                    <option value="expiring">Expiring Soon (30d)</option>
                    <option value="expired">Expired</option>
                    <option value="pending">Pending</option>
                    <option value="failed">Failed</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button wire:click="openIssueModal"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                    Issue Certificate
                </button>

                @if($this->statistics['expiring_soon'] > 0)
                    <button wire:click="renewAllExpiring"
                            wire:confirm="Are you sure you want to renew all expiring certificates?"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        Renew All Expiring
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Certificates Table --}}
    <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-900 border-b border-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Domain</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Server</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Provider</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Issued</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Expires</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Days Left</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">Auto-Renew</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @forelse($this->certificates as $certificate)
                        <tr class="hover:bg-gray-700/50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    <span class="text-sm text-white font-medium">{{ $certificate->domain_name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm text-gray-300">{{ $certificate->server->name ?? 'N/A' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded text-xs font-medium {{ $certificate->getStatusBadgeClass() }}">
                                    {{ $certificate->getStatusLabel() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm text-gray-300">{{ ucfirst($certificate->provider ?? 'N/A') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm text-gray-300">
                                    {{ $certificate->issued_at?->format('Y-m-d') ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm text-gray-300">
                                    {{ $certificate->expires_at?->format('Y-m-d') ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $daysLeft = $certificate->daysUntilExpiry();
                                    $colorClass = match(true) {
                                        $daysLeft === null => 'text-gray-400',
                                        $daysLeft === 0 => 'text-red-400 font-semibold',
                                        $daysLeft <= 7 => 'text-red-400',
                                        $daysLeft <= 30 => 'text-yellow-400',
                                        default => 'text-green-400',
                                    };
                                @endphp
                                <span class="text-sm {{ $colorClass }}">
                                    {{ $daysLeft !== null ? $daysLeft . ' days' : 'Unknown' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm {{ $certificate->auto_renew ? 'text-green-400' : 'text-gray-400' }}">
                                    {{ $certificate->auto_renew ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="viewCertificate({{ $certificate->id }})"
                                            class="p-1 text-gray-400 hover:text-white transition-colors"
                                            title="View Details">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>

                                    @if($certificate->needsRenewal(30))
                                        <button wire:click="renewCertificate({{ $certificate->domain_id }})"
                                                class="p-1 text-yellow-400 hover:text-yellow-300 transition-colors"
                                                title="Renew Certificate">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                        </button>
                                    @endif

                                    <button wire:click="checkExpiry({{ $certificate->domain_id }})"
                                            class="p-1 text-blue-400 hover:text-blue-300 transition-colors"
                                            title="Check Expiry">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </button>

                                    @if($certificate->status === 'issued')
                                        <button wire:click="revokeCertificate({{ $certificate->domain_id }})"
                                                wire:confirm="Are you sure you want to revoke this certificate?"
                                                class="p-1 text-red-400 hover:text-red-300 transition-colors"
                                                title="Revoke Certificate">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-400">
                                No SSL certificates found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($this->certificates->hasPages())
            <div class="px-4 py-3 border-t border-gray-700">
                {{ $this->certificates->links() }}
            </div>
        @endif
    </div>

    {{-- Certificate Detail Modal --}}
    @if($showCertificateModal && $selectedDomain)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75"
                     wire:click="closeCertificateModal"></div>

                <div class="inline-block w-full max-w-2xl my-8 overflow-hidden text-left align-middle transition-all transform bg-gray-800 border border-gray-700 rounded-lg shadow-xl">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <h3 class="text-lg font-semibold text-white">Certificate Details: {{ $selectedDomain->domain }}</h3>
                    </div>

                    <div class="px-6 py-4 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Domain</label>
                                <p class="text-white">{{ $selectedDomain->domain }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Status</label>
                                <p class="text-white">{{ $selectedDomain->ssl_enabled ? 'Enabled' : 'Disabled' }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Provider</label>
                                <p class="text-white">{{ ucfirst($selectedDomain->ssl_provider ?? 'N/A') }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Auto-Renew</label>
                                <p class="text-white">{{ $selectedDomain->auto_renew_ssl ? 'Yes' : 'No' }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Issued At</label>
                                <p class="text-white">{{ $selectedDomain->ssl_issued_at?->format('Y-m-d H:i') ?? 'N/A' }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Expires At</label>
                                <p class="text-white">{{ $selectedDomain->ssl_expires_at?->format('Y-m-d H:i') ?? 'N/A' }}</p>
                            </div>
                        </div>

                        @if($selectedDomain->project)
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-1">Project</label>
                                <p class="text-white">{{ $selectedDomain->project->name }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="px-6 py-4 border-t border-gray-700 flex justify-end gap-3">
                        <button wire:click="closeCertificateModal"
                                class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Loading Overlay --}}
    @if($isProcessing)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75">
            <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 text-center">
                <svg class="animate-spin h-10 w-10 text-blue-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-white">Processing SSL operation...</p>
            </div>
        </div>
    @endif
</div>
