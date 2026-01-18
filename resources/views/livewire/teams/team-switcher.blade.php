<div class="relative" x-data="{ open: @entangle('showDropdown') }">
    @if($this->currentTeam)
        <!-- Team Switcher Button -->
        <button @click="open = !open" type="button" class="flex items-center space-x-3 w-full px-4 py-2 text-left bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <img src="{{ $this->currentTeam['avatar_url'] }}" alt="{{ $this->currentTeam['name'] }}" class="w-8 h-8 rounded-lg">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $this->currentTeam['name'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ ucfirst($this->currentTeam['role']) }}</p>
            </div>
            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <div x-show="open"
             @click.away="open = false"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="absolute z-50 mt-2 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg overflow-hidden"
             style="display: none;">

            <!-- Current Team (highlighted) -->
            <div class="px-4 py-3 bg-indigo-50 dark:bg-indigo-900/20 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <img src="{{ $this->currentTeam['avatar_url'] }}" alt="{{ $this->currentTeam['name'] }}" class="w-8 h-8 rounded-lg">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $this->currentTeam['name'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Current team</p>
                    </div>
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </div>
            </div>

            <!-- Other Teams -->
            @if($this->teams->isNotEmpty())
                <div class="py-1">
                    <div class="px-4 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Switch to
                    </div>
                    @foreach($this->teams as $team)
                        <button wire:key="team-switch-{{ $team['id'] }}"
                                wire:click="switchTeam({{ $team['id'] }})"
                                type="button"
                                class="flex items-center space-x-3 w-full px-4 py-3 text-left hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <img src="{{ $team['avatar_url'] }}" alt="{{ $team['name'] }}" class="w-8 h-8 rounded-lg">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $team['name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ ucfirst($team['role']) }}</p>
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif

            <!-- Create/Manage Teams -->
            <div class="border-t border-gray-200 dark:border-gray-700 py-1">
                <a href="{{ route('teams.index') }}" class="flex items-center space-x-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Create or Manage Teams</span>
                </a>
            </div>
        </div>
    @else
        <!-- No Team Selected -->
        <a href="{{ route('teams.index') }}" class="flex items-center justify-center space-x-2 w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Create a Team</span>
        </a>
    @endif
</div>
