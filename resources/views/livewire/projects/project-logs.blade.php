<div class="bg-white dark:bg-gray-800 rounded-xl shadow dark:shadow-gray-900/40 overflow-hidden">
    <div class="bg-gradient-to-r from-slate-900 via-indigo-900 to-blue-900 px-6 py-8 text-white">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <h2 class="text-2xl font-bold flex items-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2v-9a2 2 0 012-2h2" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l3-3m-3 3l-3-3" />
                    </svg>
                    Application & Container Logs
                </h2>
                <p class="text-sm text-white/70 mt-2 max-w-2xl">
                    Inspect your container output or deep-dive into Laravel’s log stream without leaving DevFlow Pro. Use the toggles to flip between Docker and application logs, adjust the number of lines, then refresh on demand.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <button wire:click="refreshLogs" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white/15 hover:bg-white/25 text-sm font-semibold tracking-wide uppercase transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M5.63 18.37A9 9 0 1118.37 5.63L19 6M5 19l.63-.63" />
                    </svg>
                    <span wire:loading.remove>Refresh</span>
                    <span wire:loading>Refreshing…</span>
                </button>
            </div>
        </div>
    </div>

    <div class="p-6 space-y-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Log Source</span>
                <div class="inline-flex rounded-full bg-gray-100 dark:bg-gray-700 p-1">
                    <button wire:click="$set('logType','laravel')"
                            class="px-4 py-1.5 text-xs font-semibold rounded-full transition-all
                                @class([
                                    'bg-white text-indigo-600 shadow-sm' => $logType === 'laravel',
                                    'text-gray-500 dark:text-gray-300' => $logType !== 'laravel'
                                ])">
                        Laravel Log
                    </button>
                    <button wire:click="$set('logType','docker')"
                            class="px-4 py-1.5 text-xs font-semibold rounded-full transition-all
                                @class([
                                    'bg-white text-indigo-600 shadow-sm' => $logType === 'docker',
                                    'text-gray-500 dark:text-gray-300' => $logType !== 'docker'
                                ])">
                        Docker Output
                    </button>
                </div>
                @if($source)
                    <span class="text-xs text-gray-400 dark:text-gray-500">Source: {{ ucfirst($source) }}</span>
                @endif
            </div>

            <div class="flex items-center gap-3">
                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Lines</label>
                <select wire:model.live="lines"
                        class="border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-1.5 text-sm bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 focus:ring-indigo-500 focus:border-indigo-500">
                    @foreach([100, 200, 300, 500, 800, 1000] as $option)
                        <option value="{{ $option }}">{{ $option }} lines</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if($error)
            <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg">
                {{ $error }}
            </div>
        @endif

        @if($loading)
            <div class="bg-gray-900/90 text-green-400 rounded-xl p-6 font-mono text-sm h-96 flex items-center justify-center">
                <div class="flex items-center gap-3 text-green-300">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Loading logs...
                </div>
            </div>
        @else
            <div class="bg-gray-900 text-green-300 rounded-xl border border-gray-800 shadow-inner">
                <div class="px-4 py-3 border-b border-gray-800 flex items-center justify-between text-xs text-green-500 uppercase tracking-wide">
                    <span>{{ $logType === 'docker' ? 'Container Logs' : 'Laravel Application Log' }}</span>
                    <span>{{ now()->format('M d, Y • H:i:s') }}</span>
                </div>
                <div class="h-[28rem] overflow-y-auto scrollbar-thin">
                    <pre class="p-4 whitespace-pre-wrap text-sm leading-relaxed selection:bg-emerald-500/30">{{ $logs ?: 'No log output available.' }}</pre>
                </div>
            </div>
        @endif
    </div>
</div>
