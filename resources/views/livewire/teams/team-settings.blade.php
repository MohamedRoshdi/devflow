<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Hero Section -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center space-x-4">
                <img src="{{ $team->avatar_url }}" alt="{{ $team->name }}" class="w-16 h-16 rounded-lg">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $team->name }}</h1>
                    @if($team->description)
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $team->description }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex -mb-px">
                    <button wire:click="setActiveTab('general')" class="px-6 py-3 text-sm font-medium {{ $activeTab === 'general' ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
                        General
                    </button>
                    <button wire:click="setActiveTab('members')" class="px-6 py-3 text-sm font-medium {{ $activeTab === 'members' ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
                        Members
                    </button>
                    <button wire:click="setActiveTab('invitations')" class="px-6 py-3 text-sm font-medium {{ $activeTab === 'invitations' ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
                        Invitations
                    </button>
                    <button wire:click="setActiveTab('danger')" class="px-6 py-3 text-sm font-medium {{ $activeTab === 'danger' ? 'border-b-2 border-red-500 text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
                        Danger Zone
                    </button>
                </nav>
            </div>

            <div class="p-6">
                <!-- General Tab -->
                @if($activeTab === 'general')
                    <livewire:teams.team-general-settings :team="$team" :key="'general-'.$team->id" />
                @endif

                <!-- Members Tab -->
                @if($activeTab === 'members')
                    <livewire:teams.team-member-manager :team="$team" :key="'members-'.$team->id" />
                @endif

                <!-- Invitations Tab -->
                @if($activeTab === 'invitations')
                    <livewire:teams.team-invitations :team="$team" :key="'invitations-'.$team->id" />
                @endif

                <!-- Danger Zone Tab -->
                @if($activeTab === 'danger')
                    <div class="space-y-6">
                        <!-- Transfer Ownership -->
                        @if($team->isOwner(auth()->user()))
                            <div class="border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 bg-yellow-50 dark:bg-yellow-900/20">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Transfer Ownership</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Transfer this team to another member. You will become an admin.</p>
                                <button wire:click="openTransferModal" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-medium rounded-lg transition-colors">
                                    Transfer Ownership
                                </button>
                            </div>
                        @endif

                        <!-- Delete Team -->
                        @if($team->isOwner(auth()->user()))
                            <div class="border border-red-200 dark:border-red-800 rounded-lg p-6 bg-red-50 dark:bg-red-900/20">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Delete Team</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Permanently delete this team and all its data. This action cannot be undone.</p>
                                <button wire:click="openDeleteModal" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                                    Delete Team
                                </button>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Transfer Ownership Modal -->
    @if($showTransferModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75" wire:click="closeTransferModal"></div>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Transfer Ownership</h3>
                        <button wire:click="closeTransferModal" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <form wire:submit.prevent="transferOwnership" class="space-y-4">
                        <div>
                            <label for="newOwnerId" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select New Owner</label>
                            <select id="newOwnerId" wire:model="newOwnerId" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white" required>
                                <option value="">Select a member...</option>
                                @foreach($this->potentialOwners as $member)
                                    <option value="{{ $member->id }}">{{ $member->name }} ({{ $member->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">You will become an admin after transferring ownership.</p>
                        </div>
                        <div class="flex items-center justify-end space-x-3 pt-4">
                            <button type="button" wire:click="closeTransferModal" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-lg transition-colors">Transfer Ownership</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Team Modal -->
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75" wire:click="closeDeleteModal"></div>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-red-600 dark:text-red-400">Delete Team</h3>
                        <button wire:click="closeDeleteModal" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <form wire:submit.prevent="deleteTeam" class="space-y-4">
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                            <p class="text-sm text-red-800 dark:text-red-200">This action cannot be undone. All team data will be permanently deleted.</p>
                        </div>
                        <div>
                            <label for="deleteConfirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Type <strong>{{ $team->name }}</strong> to confirm
                            </label>
                            <input type="text" id="deleteConfirmation" wire:model="deleteConfirmation" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white" required>
                        </div>
                        <div class="flex items-center justify-end space-x-3 pt-4">
                            <button type="button" wire:click="closeDeleteModal" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">Delete Team</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
