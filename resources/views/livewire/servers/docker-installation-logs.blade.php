<div>
    @if($isVisible || $status === 'installing')
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true"
         wire:poll.1s="pollLogs">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity"></div>

        {{-- Modal Panel --}}
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-slate-900 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-4xl border border-slate-700">
                {{-- Header --}}
                <div class="bg-gradient-to-r from-cyan-600 to-blue-600 px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M13.983 11.078h2.119a.186.186 0 00.186-.185V9.006a.186.186 0 00-.186-.186h-2.119a.185.185 0 00-.185.185v1.888c0 .102.083.185.185.185m-2.954-5.43h2.118a.186.186 0 00.186-.186V3.574a.186.186 0 00-.186-.185h-2.118a.185.185 0 00-.185.185v1.888c0 .102.082.185.185.186"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white" id="modal-title">
                                {{ __('Docker Installation') }}
                            </h3>
                            <p class="text-sm text-cyan-100">{{ $server->name }} ({{ $server->ip_address }})</p>
                        </div>
                    </div>
                    <button wire:click="hide" class="text-white/70 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Progress Bar --}}
                <div class="px-6 py-3 bg-slate-800 border-b border-slate-700">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-slate-300">
                            @if($status === 'installing')
                                <span class="inline-flex items-center gap-2">
                                    <svg class="w-4 h-4 animate-spin text-cyan-400" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    {{ __('Installing...') }}
                                </span>
                            @elseif($status === 'completed')
                                <span class="inline-flex items-center gap-2 text-emerald-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    {{ __('Installation Complete') }}
                                </span>
                            @elseif($status === 'failed')
                                <span class="inline-flex items-center gap-2 text-red-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    {{ __('Installation Failed') }}
                                </span>
                            @else
                                {{ __('Preparing...') }}
                            @endif
                        </span>
                        <span class="text-sm font-bold text-white">{{ $progress }}%</span>
                    </div>
                    <div class="w-full bg-slate-700 rounded-full h-2.5 overflow-hidden">
                        <div class="h-2.5 rounded-full transition-all duration-500
                            @if($status === 'completed') bg-gradient-to-r from-emerald-500 to-teal-500
                            @elseif($status === 'failed') bg-gradient-to-r from-red-500 to-red-600
                            @else bg-gradient-to-r from-cyan-500 to-blue-500
                            @endif"
                            style="width: {{ $progress }}%">
                        </div>
                    </div>
                    @if($currentStep)
                        <p class="text-xs text-slate-400 mt-2">{{ $currentStep }}</p>
                    @endif
                </div>

                {{-- Log Output --}}
                <div class="bg-slate-950 p-4 h-96 overflow-y-auto font-mono text-sm" id="docker-logs-container">
                    @if(count($logs) > 0)
                        @foreach($logs as $log)
                            <div class="py-0.5 @if(str_contains($log, 'ERROR') || str_contains($log, 'error') || str_contains($log, 'failed')) text-red-400
                                @elseif(str_contains($log, 'Step') || str_contains($log, '===')) text-cyan-400 font-semibold
                                @elseif(str_contains($log, 'SUCCESS') || str_contains($log, 'success') || str_contains($log, 'Successfully')) text-emerald-400
                                @elseif(str_contains($log, 'WARNING') || str_contains($log, 'warning')) text-amber-400
                                @else text-slate-300
                                @endif">
                                <span class="text-slate-600 select-none me-2">{{ sprintf('%03d', $loop->iteration) }}</span>{{ $log }}
                            </div>
                        @endforeach
                    @else
                        <div class="flex items-center justify-center h-full text-slate-500">
                            <div class="text-center">
                                <svg class="w-12 h-12 mx-auto mb-3 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p>{{ __('Waiting for installation output...') }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Error Message --}}
                @if($errorMessage)
                    <div class="px-6 py-3 bg-red-900/30 border-t border-red-800">
                        <p class="text-sm text-red-400">
                            <strong>{{ __('Error:') }}</strong> {{ $errorMessage }}
                        </p>
                    </div>
                @endif

                {{-- Footer --}}
                <div class="px-6 py-4 bg-slate-800 border-t border-slate-700 flex items-center justify-between">
                    <div class="text-xs text-slate-500">
                        @if($status === 'installing')
                            {{ __('Installation may take 3-5 minutes. Please wait...') }}
                        @elseif($status === 'completed')
                            {{ __('Docker has been successfully installed on the server.') }}
                        @elseif($status === 'failed')
                            {{ __('Check the logs above for error details.') }}
                        @endif
                    </div>
                    <div class="flex gap-2">
                        @if($status !== 'installing')
                            <button wire:click="clearAndClose"
                                class="px-4 py-2 rounded-lg text-sm font-medium bg-slate-700 text-slate-300 hover:bg-slate-600 hover:text-white transition-all">
                                {{ __('Close') }}
                            </button>
                        @endif
                        @if($status === 'installing')
                            <button wire:click="hide"
                                class="px-4 py-2 rounded-lg text-sm font-medium bg-slate-700 text-slate-300 hover:bg-slate-600 hover:text-white transition-all">
                                {{ __('Run in Background') }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Minimized indicator when running in background --}}
    @if(!$isVisible && $status === 'installing')
        <button wire:click="show"
            class="fixed bottom-4 end-4 z-40 flex items-center gap-3 px-4 py-3 bg-gradient-to-r from-cyan-600 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl transition-all animate-pulse">
            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="font-medium">{{ __('Docker Installing...') }} {{ $progress }}%</span>
        </button>
    @endif
</div>

@script
<script>
    // Auto-scroll to bottom of logs
    $wire.on('logs-updated', () => {
        const container = document.getElementById('docker-logs-container');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    });
</script>
@endscript
