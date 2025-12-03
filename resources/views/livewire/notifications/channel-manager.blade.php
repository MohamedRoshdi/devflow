<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Hero Section with Gradient -->
        <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-rose-500 via-pink-500 to-fuchsia-500 dark:from-rose-600 dark:via-pink-600 dark:to-fuchsia-600 p-8 shadow-xl overflow-hidden">
            <div class="absolute inset-0 bg-black/10 dark:bg-black/20"></div>
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="p-2 bg-white/20 dark:bg-white/10 backdrop-blur-md rounded-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white">Notification Channels</h1>
                    </div>
                    <p class="text-white/90 text-lg">Configure notification channels for alerts and updates</p>
                </div>
                <button wire:click="addChannel"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        wire:target="addChannel"
                        class="px-6 py-3 bg-white/20 dark:bg-white/10 backdrop-blur-md text-white rounded-xl hover:bg-white/30 dark:hover:bg-white/20 transition-all shadow-lg hover:shadow-xl whitespace-nowrap">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Channel
                </button>
            </div>
        </div>

    <!-- Channels Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($channels as $channel)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition-shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            @switch($channel->provider)
                                @case('slack')
                                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M5.042 15.165a2.528 2.528 0 0 1-2.52 2.523A2.528 2.528 0 0 1 0 15.165a2.527 2.527 0 0 1 2.522-2.52h2.52v2.52zM6.313 15.165a2.527 2.527 0 0 1 2.521-2.52 2.527 2.527 0 0 1 2.521 2.52v6.313A2.528 2.528 0 0 1 8.834 24a2.528 2.528 0 0 1-2.521-2.522v-6.313zM8.834 5.042a2.528 2.528 0 0 1-2.521-2.52A2.528 2.528 0 0 1 8.834 0a2.528 2.528 0 0 1 2.521 2.522v2.52H8.834zM8.834 6.313a2.528 2.528 0 0 1 2.521 2.521 2.528 2.528 0 0 1-2.521 2.521H2.522A2.528 2.528 0 0 1 0 8.834a2.528 2.528 0 0 1 2.522-2.521h6.312zM18.956 8.834a2.528 2.528 0 0 1 2.522-2.521A2.528 2.528 0 0 1 24 8.834a2.528 2.528 0 0 1-2.522 2.521h-2.522V8.834zM17.688 8.834a2.528 2.528 0 0 1-2.523 2.521 2.527 2.527 0 0 1-2.52-2.521V2.522A2.527 2.527 0 0 1 15.165 0a2.528 2.528 0 0 1 2.523 2.522v6.312zM15.165 18.956a2.528 2.528 0 0 1 2.523 2.522A2.528 2.528 0 0 1 15.165 24a2.527 2.527 0 0 1-2.52-2.522v-2.522h2.52zM15.165 17.688a2.527 2.527 0 0 1-2.52-2.523 2.526 2.526 0 0 1 2.52-2.52h6.313A2.527 2.527 0 0 1 24 15.165a2.528 2.528 0 0 1-2.522 2.523h-6.313z"/>
                                        </svg>
                                    </div>
                                    @break
                                @case('discord')
                                    <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-indigo-600" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515a.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0a12.64 12.64 0 0 0-.617-1.25a.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057a19.9 19.9 0 0 0 5.993 3.03a.078.078 0 0 0 .084-.028a14.09 14.09 0 0 0 1.226-1.994a.076.076 0 0 0-.041-.106a13.107 13.107 0 0 1-1.872-.892a.077.077 0 0 1-.008-.128a10.2 10.2 0 0 0 .372-.292a.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127a12.299 12.299 0 0 1-1.873.892a.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028a19.839 19.839 0 0 0 6.002-3.03a.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419c0-1.333.956-2.419 2.157-2.419c1.21 0 2.176 1.096 2.157 2.42c0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419c0-1.333.955-2.419 2.157-2.419c1.21 0 2.176 1.096 2.157 2.42c0 1.333-.946 2.418-2.157 2.418z"/>
                                        </svg>
                                    </div>
                                    @break
                                @case('teams')
                                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M19.82 8.18H18v5.46c0 .2-.16.36-.36.36h-1.46c-.2 0-.36-.16-.36-.36V8.18H14c-.1 0-.18-.08-.18-.18V6.82c0-.1.08-.18.18-.18h5.82c.1 0 .18.08.18.18V8c0 .1-.08.18-.18.18zm-7.27-1.33h-1.64V5.73c0-.1-.08-.18-.18-.18H7.09c-.1 0-.18.08-.18.18v8c0 .05.02.09.05.13.04.04.08.05.13.05h5.46c.1 0 .18-.08.18-.18v-1.09c0-.1-.08-.18-.18-.18h-3.64V10h3.64c.1 0 .18-.08.18-.18V8.73c0-.1-.08-.18-.18-.18h-3.64V6.85h3.64c.1 0 .18-.08.18-.18V5.58c0-.1-.08-.18-.18-.18z"/>
                                        </svg>
                                    </div>
                                    @break
                                @default
                                    <div class="w-10 h-10 bg-gray-100 dark:bg-gray-900 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    </div>
                            @endswitch
                            <div class="ml-3">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $channel->name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ ucfirst($channel->provider) }}</p>
                            </div>
                        </div>
                        <button wire:click="toggleChannel({{ $channel->id }})"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50"
                                wire:target="toggleChannel({{ $channel->id }})"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors
                                {{ $channel->enabled ? 'bg-green-600' : 'bg-gray-300' }}">
                            <span class="inline-block h-5 w-5 transform rounded-full bg-white transition-transform
                                {{ $channel->enabled ? 'translate-x-5' : 'translate-x-0.5' }}"></span>
                        </button>
                    </div>

                    <div class="mb-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Events:</p>
                        <div class="flex flex-wrap gap-1">
                            @foreach($channel->events as $event)
                                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded text-xs">
                                    {{ str_replace('_', ' ', ucwords($event)) }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-4 border-t dark:border-gray-700">
                        <button wire:click="testChannel({{ $channel->id }})"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50"
                                wire:target="testChannel({{ $channel->id }})"
                                class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 text-sm font-medium">
                            <span wire:loading.remove wire:target="testChannel({{ $channel->id }})">Test</span>
                            <span wire:loading wire:target="testChannel({{ $channel->id }})">Testing...</span>
                        </button>
                        <div class="flex space-x-3">
                            <button wire:click="editChannel({{ $channel->id }})"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-50"
                                    wire:target="editChannel({{ $channel->id }})"
                                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 text-sm">
                                Edit
                            </button>
                            <button wire:click="deleteChannel({{ $channel->id }})"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-50"
                                    wire:target="deleteChannel({{ $channel->id }})"
                                    wire:confirm="Are you sure you want to delete this channel?"
                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 text-sm">
                                <span wire:loading.remove wire:target="deleteChannel({{ $channel->id }})">Delete</span>
                                <span wire:loading wire:target="deleteChannel({{ $channel->id }})">Deleting...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No notification channels</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Configure Slack, Discord, or other channels to receive notifications</p>
                </div>
            </div>
        @endforelse
    </div>

    @if($channels->hasPages())
        <div class="mt-6">
            {{ $channels->links() }}
        </div>
    @endif

    <!-- Add/Edit Channel Modal -->
    @if($showAddChannelModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" wire:click="$set('showAddChannelModal', false)"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full">
                    <form wire:submit.prevent="saveChannel">
                        <div class="px-6 py-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                {{ $editingChannel ? 'Edit Notification Channel' : 'Add Notification Channel' }}
                            </h3>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Channel Name</label>
                                    <input type="text" wire:model="name" placeholder="Production Alerts"
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Provider</label>
                                    <div class="grid grid-cols-4 gap-3">
                                        @foreach(['slack' => 'Slack', 'discord' => 'Discord', 'teams' => 'Teams', 'webhook' => 'Webhook'] as $value => $label)
                                            <label class="flex flex-col items-center p-3 border rounded-lg cursor-pointer
                                                {{ $provider === $value ? 'border-purple-500 bg-purple-50 dark:bg-purple-900' : 'border-gray-300 dark:border-gray-600' }}">
                                                <input type="radio" wire:model="provider" value="{{ $value }}" class="sr-only">
                                                <span class="text-sm font-medium {{ $provider === $value ? 'text-purple-900 dark:text-purple-100' : 'text-gray-900 dark:text-gray-100' }}">
                                                    {{ $label }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Webhook URL</label>
                                    <input type="url" wire:model="webhookUrl" placeholder="https://hooks.slack.com/services/..."
                                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    @error('webhookUrl') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                @if($provider === 'webhook')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Webhook Secret (optional)</label>
                                        <input type="text" wire:model="webhookSecret" placeholder="Optional secret for HMAC validation"
                                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>
                                @endif

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notification Events</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        @foreach($availableEvents as $eventKey => $eventName)
                                            <label class="inline-flex items-center">
                                                <input type="checkbox"
                                                       wire:click="toggleEvent('{{ $eventKey }}')"
                                                       @if(in_array($eventKey, $events)) checked @endif
                                                       class="rounded border-gray-300 text-purple-600">
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $eventName }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('events') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" wire:model="enabled" class="rounded border-gray-300 text-purple-600">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Enable this channel immediately</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-900 px-6 py-3 flex justify-end space-x-3">
                            <button type="button" wire:click="$set('showAddChannelModal', false)"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Cancel
                            </button>
                            <button type="submit"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-50 cursor-not-allowed"
                                    wire:target="saveChannel"
                                    class="px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700">
                                <span wire:loading.remove wire:target="saveChannel">{{ $editingChannel ? 'Update Channel' : 'Add Channel' }}</span>
                                <span wire:loading wire:target="saveChannel">Saving...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    </div>
</div>