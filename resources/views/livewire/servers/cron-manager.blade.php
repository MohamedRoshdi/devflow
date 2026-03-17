<div>
    <!-- Header -->
    <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-slate-800 via-slate-900 to-slate-800 p-8 shadow-2xl overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="cron-pattern" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                        <rect x="0" y="0" width="4" height="4" fill="currentColor" class="text-white"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#cron-pattern)"/>
            </svg>
        </div>

        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex items-start gap-4">
                    <div class="p-4 bg-white/10 backdrop-blur-md rounded-2xl">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>

                    <div>
                        <h1 class="text-3xl font-bold text-white">Cron Scheduler</h1>
                        <p class="text-white/70 mt-1">{{ $server->name }} — {{ $server->ip_address }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button wire:click="loadCrontab"
                            wire:loading.attr="disabled"
                            wire:target="loadCrontab"
                            class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-all duration-200 font-medium flex items-center gap-2 disabled:opacity-50">
                        <svg wire:loading.remove wire:target="loadCrontab" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <svg wire:loading wire:target="loadCrontab" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Refresh
                    </button>

                    <button wire:click="$set('showCreateModal', true)"
                            class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-all duration-200 font-medium flex items-center gap-2 shadow-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Entry
                    </button>

                    <a href="{{ route('servers.show', $server) }}"
                       class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-all duration-200 font-medium">
                        ← Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="mb-6 bg-gradient-to-r from-green-500/20 to-emerald-500/20 border border-green-500/30 text-green-400 px-5 py-4 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 bg-gradient-to-r from-red-500/20 to-red-600/20 border border-red-500/30 text-red-400 px-5 py-4 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    <!-- Cron Entries -->
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Scheduled Jobs ({{ count($cronEntries) }})
            </h2>
        </div>

        @if (count($cronEntries) > 0)
            <div class="space-y-3">
                @foreach ($cronEntries as $index => $entry)
                    @php
                        $human = $this->humanSchedule($entry['schedule']);
                    @endphp
                    <div wire:key="cron-entry-{{ $index }}"
                         class="bg-gray-800 rounded-2xl shadow-lg border border-gray-700/50 p-5 transition-all">

                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-4 min-w-0 flex-1">
                                <!-- Clock icon -->
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-white flex-shrink-0 shadow-md shadow-indigo-500/20">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <!-- Schedule expression + human label -->
                                    <div class="flex items-center gap-2 mb-2 flex-wrap">
                                        <code class="px-2.5 py-1 rounded-lg bg-gray-700 text-gray-200 text-sm font-mono border border-gray-600">{{ $entry['schedule'] }}</code>
                                        @if ($human !== '')
                                            <span class="text-xs text-gray-500 dark:text-gray-400 italic">{{ $human }}</span>
                                        @endif
                                    </div>

                                    <!-- Command -->
                                    <div class="px-3 py-2 rounded-lg bg-gray-900/50 border border-gray-700/50 mb-2">
                                        <p class="text-xs font-mono text-gray-300 break-all" title="{{ $entry['command'] }}">
                                            {{ $entry['command'] }}
                                        </p>
                                    </div>

                                    <!-- Comment -->
                                    @if ($entry['comment'] !== '')
                                        <p class="text-xs text-gray-400 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                            </svg>
                                            {{ $entry['comment'] }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <!-- Delete button -->
                            <button wire:click="deleteEntry({{ $index }})"
                                    wire:confirm="Remove this cron entry? This cannot be undone."
                                    wire:loading.attr="disabled"
                                    wire:target="deleteEntry({{ $index }})"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-red-900/30 text-red-400 hover:bg-red-900/50 transition-colors flex-shrink-0 disabled:opacity-50">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Delete
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-gray-800 rounded-2xl shadow-lg p-12 text-center">
                <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-gray-400 mb-4">No cron entries found for the deploy user on this server.</p>
                <button wire:click="$set('showCreateModal', true)"
                        class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                    + Schedule your first job
                </button>
            </div>
        @endif
    </div>

    <!-- Add Entry Modal -->
    @if ($showCreateModal)
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
             role="dialog"
             aria-modal="true"
             aria-labelledby="cron-modal-title">
            <div class="bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-700 flex items-center justify-between">
                    <h3 id="cron-modal-title" class="text-xl font-bold text-white">Add Cron Entry</h3>
                    <button wire:click="$set('showCreateModal', false)"
                            class="text-gray-400 hover:text-gray-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-5">
                    <!-- Schedule Presets -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            Quick Presets
                        </label>
                        <div class="flex flex-wrap gap-2">
                            @foreach ([
                                '* * * * *'    => 'Every minute',
                                '*/5 * * * *'  => 'Every 5 min',
                                '0 * * * *'    => 'Every hour',
                                '0 0 * * *'    => 'Daily midnight',
                                '0 0 * * 1'    => 'Weekly Mon',
                            ] as $preset => $label)
                                <button type="button"
                                        wire:click="$set('newSchedule', '{{ $preset }}')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors
                                               {{ $newSchedule === $preset
                                                    ? 'bg-indigo-600 border-indigo-600 text-white'
                                                    : 'bg-gray-700 border-gray-600 text-gray-300 hover:bg-gray-600' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Schedule Expression -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">
                            Schedule Expression
                        </label>
                        <input type="text"
                               wire:model.blur="newSchedule"
                               placeholder="* * * * *"
                               class="w-full px-4 py-2.5 border border-gray-600 rounded-xl bg-gray-700 text-white placeholder-gray-500 font-mono text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <p class="mt-1 text-xs text-gray-400">Format: <span class="font-mono">minute  hour  day-of-month  month  day-of-week</span></p>
                        @error('newSchedule') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <!-- Command -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">Command</label>
                        <input type="text"
                               wire:model.blur="newCommand"
                               placeholder="/usr/bin/php /var/www/app/artisan schedule:run"
                               class="w-full px-4 py-2.5 border border-gray-600 rounded-xl bg-gray-700 text-white placeholder-gray-500 font-mono text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        @error('newCommand') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <!-- Comment -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">
                            Comment / Description
                            <span class="text-gray-400 font-normal text-xs ms-1">(optional)</span>
                        </label>
                        <input type="text"
                               wire:model.blur="newComment"
                               placeholder="e.g. Laravel scheduler"
                               class="w-full px-4 py-2.5 border border-gray-600 rounded-xl bg-gray-700 text-white placeholder-gray-500 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                        @error('newComment') <p class="mt-1 text-sm text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <!-- Preview -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
                        <div class="flex gap-3">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="text-sm text-blue-800 dark:text-blue-200 min-w-0">
                                <p class="font-semibold mb-1">Entry preview:</p>
                                <p class="font-mono text-xs break-all text-blue-700 dark:text-blue-300">
                                    {{ $newSchedule ?: '* * * * *' }} {{ $newCommand ?: '<command>' }}{{ $newComment ? ' # '.$newComment : '' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 border-t border-gray-700 flex gap-3">
                    <button wire:click="addEntry"
                            wire:loading.attr="disabled"
                            wire:target="addEntry"
                            class="flex-1 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-colors font-medium flex items-center justify-center gap-2 disabled:opacity-50">
                        <svg wire:loading.remove wire:target="addEntry" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <svg wire:loading wire:target="addEntry" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Add Entry
                    </button>
                    <button wire:click="$set('showCreateModal', false)"
                            class="flex-1 px-4 py-2.5 bg-gray-700 hover:bg-gray-600 text-white rounded-xl transition-colors font-medium">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
