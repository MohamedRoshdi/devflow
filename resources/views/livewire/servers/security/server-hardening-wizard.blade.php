<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-emerald-800 via-green-900 to-emerald-800 shadow-2xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex items-center gap-4">
                    <a href="{{ route('servers.security.guardian', $server) }}" class="p-2 bg-white/10 rounded-lg hover:bg-white/20 transition-colors">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-white">Server Hardening Wizard</h1>
                        <p class="text-white/80">{{ $server->name }} - Step-by-Step Security Hardening</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Message -->
    @if($flashMessage)
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="rounded-lg p-4
                {{ $flashType === 'success' ? 'bg-green-900/50 text-green-200 border border-green-700' : '' }}
                {{ $flashType === 'warning' ? 'bg-yellow-900/50 text-yellow-200 border border-yellow-700' : '' }}
                {{ $flashType === 'error' ? 'bg-red-900/50 text-red-200 border border-red-700' : '' }}
                {{ $flashType === 'info' ? 'bg-blue-900/50 text-blue-200 border border-blue-700' : '' }}">
                {{ $flashMessage }}
            </div>
        </div>
    @endif

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Step Indicator -->
        <div class="flex items-center justify-center mb-8">
            @for($i = 1; $i <= $totalSteps; $i++)
                <div class="flex items-center">
                    <button wire:click="goToStep({{ $i }})"
                        class="w-10 h-10 rounded-full flex items-center justify-center font-medium text-sm transition-colors
                            {{ $currentStep === $i ? 'bg-emerald-600 text-white' : ($currentStep > $i ? 'bg-emerald-800 text-emerald-300' : 'bg-gray-700 text-gray-400') }}">
                        {{ $i }}
                    </button>
                    @if($i < $totalSteps)
                        <div class="w-12 h-0.5 {{ $currentStep > $i ? 'bg-emerald-600' : 'bg-gray-700' }}"></div>
                    @endif
                </div>
            @endfor
        </div>

        <!-- Step Content -->
        <div class="bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-6">
            <!-- Step 1: SSH Hardening -->
            @if($currentStep === 1)
                <h3 class="text-lg font-semibold text-white mb-6">Step 1: SSH Hardening</h3>

                <div class="space-y-6">
                    <label class="flex items-start gap-4 p-4 bg-gray-900/50 rounded-lg cursor-pointer">
                        <input type="checkbox" wire:model="changeSSHPort" class="mt-1 rounded bg-gray-700 border-gray-600 text-emerald-500 focus:ring-emerald-500">
                        <div>
                            <p class="font-medium text-white">Change SSH Port</p>
                            <p class="text-sm text-gray-400">Move SSH from default port 22 to a custom port to reduce brute-force attacks.</p>
                            @if($changeSSHPort)
                                <div class="mt-3">
                                    <label class="text-sm text-gray-400">New SSH Port:</label>
                                    <input type="number" wire:model="newSSHPort" min="1024" max="65535"
                                        class="ml-2 w-24 bg-gray-700 border border-gray-600 rounded text-white text-sm px-2 py-1 focus:border-emerald-500 focus:ring-emerald-500">
                                </div>
                                <p class="text-xs text-yellow-400 mt-2">Both sshd_config AND systemd socket override will be updated. UFW rule will be added BEFORE restarting SSH.</p>
                            @endif
                        </div>
                    </label>

                    <label class="flex items-start gap-4 p-4 bg-gray-900/50 rounded-lg cursor-pointer">
                        <input type="checkbox" wire:model="hardenSSHConfig" class="mt-1 rounded bg-gray-700 border-gray-600 text-emerald-500 focus:ring-emerald-500">
                        <div>
                            <p class="font-medium text-white">Harden SSH Configuration</p>
                            <p class="text-sm text-gray-400">Disable password auth, limit max retries, set login grace time, restrict root login.</p>
                        </div>
                    </label>
                </div>
            @endif

            <!-- Step 2: Firewall -->
            @if($currentStep === 2)
                <h3 class="text-lg font-semibold text-white mb-6">Step 2: Firewall (UFW)</h3>

                <label class="flex items-start gap-4 p-4 bg-gray-900/50 rounded-lg cursor-pointer">
                    <input type="checkbox" wire:model="setupFirewall" class="mt-1 rounded bg-gray-700 border-gray-600 text-emerald-500 focus:ring-emerald-500">
                    <div>
                        <p class="font-medium text-white">Setup UFW Firewall</p>
                        <p class="text-sm text-gray-400">Enable UFW with sensible defaults: allow SSH, HTTP (80), HTTPS (443). Default deny incoming, allow outgoing.</p>
                        <div class="mt-3 text-xs text-gray-500 space-y-1">
                            <p>Rules that will be applied:</p>
                            <code class="block bg-gray-800 rounded p-2 text-green-400">
                                ufw default deny incoming<br>
                                ufw default allow outgoing<br>
                                ufw allow {{ $changeSSHPort ? $newSSHPort : ($server->port ?? 22) }}/tcp<br>
                                ufw allow 80/tcp<br>
                                ufw allow 443/tcp<br>
                                ufw --force enable
                            </code>
                        </div>
                    </div>
                </label>
            @endif

            <!-- Step 3: Fail2ban -->
            @if($currentStep === 3)
                <h3 class="text-lg font-semibold text-white mb-6">Step 3: Fail2ban</h3>

                <label class="flex items-start gap-4 p-4 bg-gray-900/50 rounded-lg cursor-pointer mb-4">
                    <input type="checkbox" wire:model="installFail2ban" class="mt-1 rounded bg-gray-700 border-gray-600 text-emerald-500 focus:ring-emerald-500">
                    <div>
                        <p class="font-medium text-white">Install & Configure Fail2ban</p>
                        <p class="text-sm text-gray-400">Automatically ban IPs after repeated failed login attempts.</p>
                    </div>
                </label>

                @if($installFail2ban)
                    <div class="grid grid-cols-2 gap-4 pl-12">
                        <div>
                            <label class="text-sm text-gray-400">Max Retries</label>
                            <input type="number" wire:model="fail2banMaxRetry" min="1" max="10"
                                class="w-full mt-1 bg-gray-700 border border-gray-600 rounded text-white text-sm px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
                            <p class="text-xs text-gray-500 mt-1">Failed attempts before ban</p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-400">Ban Time (seconds)</label>
                            <input type="number" wire:model="fail2banBanTime" min="60"
                                class="w-full mt-1 bg-gray-700 border border-gray-600 rounded text-white text-sm px-3 py-2 focus:border-emerald-500 focus:ring-emerald-500">
                            <p class="text-xs text-gray-500 mt-1">{{ number_format($fail2banBanTime / 3600, 1) }} hours</p>
                        </div>
                    </div>
                @endif
            @endif

            <!-- Step 4: Kernel & Services -->
            @if($currentStep === 4)
                <h3 class="text-lg font-semibold text-white mb-6">Step 4: Kernel Hardening & Services</h3>

                <div class="space-y-4">
                    <label class="flex items-start gap-4 p-4 bg-gray-900/50 rounded-lg cursor-pointer">
                        <input type="checkbox" wire:model="hardenSysctl" class="mt-1 rounded bg-gray-700 border-gray-600 text-emerald-500 focus:ring-emerald-500">
                        <div>
                            <p class="font-medium text-white">Kernel Hardening (sysctl)</p>
                            <p class="text-sm text-gray-400">Disable IP forwarding, enable SYN cookies, ignore ICMP redirects, enable TCP SYN flood protection.</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-4 p-4 bg-gray-900/50 rounded-lg cursor-pointer">
                        <input type="checkbox" wire:model="disableUnused" class="mt-1 rounded bg-gray-700 border-gray-600 text-emerald-500 focus:ring-emerald-500">
                        <div>
                            <p class="font-medium text-white">Disable Unused Services</p>
                            <p class="text-sm text-gray-400">Disable unnecessary services like avahi-daemon, cups, bluetooth if running.</p>
                        </div>
                    </label>
                </div>
            @endif

            <!-- Navigation -->
            <div class="flex items-center justify-between mt-8 pt-4 border-t border-gray-700/50">
                @if($currentStep > 1)
                    <button wire:click="previousStep" class="px-4 py-2 bg-gray-700 text-gray-300 rounded-lg hover:bg-gray-600 transition-colors">
                        Previous
                    </button>
                @else
                    <div></div>
                @endif

                @if($currentStep < $totalSteps)
                    <button wire:click="nextStep" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
                        Next
                    </button>
                @else
                    <button wire:click="applyHardening" wire:loading.attr="disabled"
                        wire:confirm="Apply hardening configuration to {{ $server->name }}?"
                        class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" wire:loading.class="animate-spin" wire:target="applyHardening" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span wire:loading.remove wire:target="applyHardening">Apply Hardening</span>
                        <span wire:loading wire:target="applyHardening">Applying...</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- Results -->
        @if(!empty($results))
            <div class="mt-8 bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-6">
                <h3 class="font-semibold text-white mb-4">Hardening Results</h3>
                <div class="space-y-3">
                    @foreach($results as $step => $result)
                        <div class="flex items-center gap-3 p-3 bg-gray-900/50 rounded-lg">
                            @if($result['success'] ?? false)
                                <svg class="w-5 h-5 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            @endif
                            <div class="flex-1">
                                <p class="text-sm font-medium text-white">{{ ucfirst(str_replace('_', ' ', $step)) }}</p>
                                <p class="text-xs text-gray-400">{{ $result['message'] ?? '' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Current Hardening Status -->
        @php $status = $this->hardeningStatus; @endphp
        @if(!empty($status))
            <div class="mt-8 bg-gray-800/50 backdrop-blur-sm rounded-xl border border-gray-700/50 p-6">
                <h3 class="font-semibold text-white mb-4">Current Hardening Status</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($status as $key => $value)
                        <div class="flex items-center justify-between p-3 bg-gray-900/50 rounded-lg">
                            <span class="text-sm text-gray-400">{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                            @if(is_bool($value))
                                <span class="px-2 py-0.5 rounded text-xs {{ $value ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                                    {{ $value ? 'Yes' : 'No' }}
                                </span>
                            @else
                                <span class="text-sm text-white">{{ is_array($value) ? json_encode($value) : $value }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
