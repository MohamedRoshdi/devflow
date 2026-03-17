<div>
    {{-- Hero Header --}}
    <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-cyan-900 via-slate-900 to-blue-900 p-8 shadow-2xl overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="db-pattern" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                        <circle cx="20" cy="20" r="1" fill="currentColor" class="text-white"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#db-pattern)"/>
            </svg>
        </div>

        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex items-start gap-4">
                    <div class="p-4 bg-white/10 backdrop-blur-md rounded-2xl">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                        </svg>
                    </div>

                    <div>
                        <h1 class="text-3xl font-bold text-white">Database Management</h1>
                        <p class="text-cyan-100 mt-1">{{ $server->name }} — {{ $server->ip_address }}</p>
                        <div class="flex items-center gap-3 mt-3">
                            @if($dbType !== '')
                                @php
                                    $isPostgres = str_starts_with($dbType, 'postgresql');
                                @endphp
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium
                                    {{ $isPostgres ? 'bg-blue-500/20 text-blue-200 border border-blue-400/30' : 'bg-orange-500/20 text-orange-200 border border-orange-400/30' }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4"/>
                                    </svg>
                                    {{ ucfirst($dbType) }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium bg-gray-500/20 text-gray-300 border border-gray-400/30">
                                    No database detected
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    @if($dbType !== '')
                        <button wire:click="$set('showCreateDbModal', true)"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-xl transition-all font-medium shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Create Database
                        </button>

                        <button wire:click="$set('showCreateUserModal', true)"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition-all font-medium shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                            Create User
                        </button>

                        <button wire:click="refresh"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-all font-medium">
                            <svg class="w-4 h-4" wire:loading.class="animate-spin" wire:target="refresh" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Refresh
                        </button>
                    @endif

                    <a href="{{ route('servers.show', $server) }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-all font-medium">
                        ← Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session()->has('message'))
        <div class="mb-6 bg-gradient-to-r from-green-500/20 to-emerald-500/20 border border-green-500/30 text-green-400 px-5 py-4 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('message') }}
        </div>
    @endif

    @if(session()->has('error'))
        <div class="mb-6 bg-gradient-to-r from-red-500/20 to-red-600/20 border border-red-500/30 text-red-400 px-5 py-4 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    @if($dbType === '')
        {{-- No DB detected state --}}
        <div class="bg-gray-800 rounded-2xl shadow-xl p-12 text-center">
            <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
            </svg>
            <h3 class="text-xl font-semibold text-gray-300 mb-2">No Database Detected</h3>
            <p class="text-gray-500 max-w-md mx-auto">Neither PostgreSQL nor MySQL was found on this server. Install one to get started.</p>
        </div>
    @else
        {{-- Databases Section --}}
        <div class="bg-gray-800 rounded-2xl shadow-xl mb-6">
            <div class="flex items-center justify-between px-6 py-5 border-b border-gray-700">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                    </svg>
                    Databases
                    <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-medium bg-cyan-500/20 text-cyan-400">
                        {{ count($databases) }}
                    </span>
                </h3>
            </div>

            @if(count($databases) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-700">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Database</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Size</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            @foreach($databases as $db)
                                <tr wire:key="db-{{ $db['name'] }}" class="hover:bg-gray-700/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white text-xs font-bold">
                                                {{ strtoupper(substr($db['name'], 0, 2)) }}
                                            </div>
                                            <span class="font-mono text-sm text-white font-medium">{{ $db['name'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-gray-400">{{ $db['size'] }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button
                                            wire:click="confirmDestructiveAction('dropDatabase', '{{ $db['name'] }}', 'Permanently drop the database \'{{ $db['name'] }}\' and ALL its data. This cannot be undone.')"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg text-sm font-medium transition-colors border border-red-500/20 hover:border-red-500/40">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Drop
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-12 text-center">
                    <svg class="w-12 h-12 text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                    </svg>
                    <p class="text-gray-500 text-sm">No databases found</p>
                    <button wire:click="$set('showCreateDbModal', true)"
                            class="mt-3 text-sm text-cyan-400 hover:text-cyan-300 transition-colors">
                        + Create your first database
                    </button>
                </div>
            @endif
        </div>

        {{-- Users Section --}}
        <div class="bg-gray-800 rounded-2xl shadow-xl">
            <div class="flex items-center justify-between px-6 py-5 border-b border-gray-700">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Database Users
                    <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-500/20 text-indigo-400">
                        {{ count($dbUsers) }}
                    </span>
                </h3>
            </div>

            @if(count($dbUsers) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-700">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Username</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Role</th>
                                @if(count($databases) > 0)
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Grant Access</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            @foreach($dbUsers as $user)
                                <tr wire:key="user-{{ $user['name'] }}" class="hover:bg-gray-700/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold">
                                                {{ strtoupper(substr($user['name'], 0, 1)) }}
                                            </div>
                                            <span class="font-mono text-sm text-white font-medium">{{ $user['name'] }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($user['is_superuser'])
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                                Superuser
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-700 text-gray-400">
                                                Regular
                                            </span>
                                        @endif
                                    </td>
                                    @if(count($databases) > 0)
                                        <td class="px-6 py-4 text-right">
                                            <div x-data="{ open: false }" class="relative inline-block">
                                                <button @click="open = !open"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 rounded-lg text-sm font-medium transition-colors border border-indigo-500/20 hover:border-indigo-500/40">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                                    </svg>
                                                    Grant Access
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                    </svg>
                                                </button>
                                                <div x-show="open"
                                                     @click.away="open = false"
                                                     x-transition
                                                     class="mt-1 w-48 bg-gray-900 border border-gray-700 rounded-xl shadow-xl z-10">
                                                    @foreach($databases as $db)
                                                        <button wire:key="grant-{{ $user['name'] }}-{{ $db['name'] }}"
                                                                wire:click="grantAccess('{{ $user['name'] }}', '{{ $db['name'] }}')"
                                                                @click="open = false"
                                                                class="w-full text-left px-4 py-2.5 text-sm text-gray-300 hover:bg-gray-700 hover:text-white first:rounded-t-xl last:rounded-b-xl transition-colors font-mono">
                                                            {{ $db['name'] }}
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-12 text-center">
                    <svg class="w-12 h-12 text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <p class="text-gray-500 text-sm">No database users found</p>
                    <button wire:click="$set('showCreateUserModal', true)"
                            class="mt-3 text-sm text-indigo-400 hover:text-indigo-300 transition-colors">
                        + Create your first user
                    </button>
                </div>
            @endif
        </div>
    @endif

    {{-- Create Database Modal --}}
    @if($showCreateDbModal)
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-gray-800 rounded-2xl shadow-2xl border border-gray-700 max-w-md w-full">
                <div class="flex items-center justify-between px-6 py-5 border-b border-gray-700">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Create Database
                    </h3>
                    <button wire:click="$set('showCreateDbModal', false)"
                            class="text-gray-400 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Database Name</label>
                        <input wire:model="newDbName"
                               type="text"
                               placeholder="e.g. my_app_production"
                               class="w-full px-4 py-2.5 bg-gray-700 border border-gray-600 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent font-mono text-sm">
                        @error('newDbName')
                            <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-1.5 text-xs text-gray-500">Letters, numbers, and underscores only. Must start with a letter or underscore.</p>
                    </div>

                    @if(count($dbUsers) > 0 && str_starts_with($dbType, 'postgresql'))
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Owner (optional)</label>
                            <select wire:model="newDbOwner"
                                    class="w-full px-4 py-2.5 bg-gray-700 border border-gray-600 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent text-sm">
                                <option value="">— Default (postgres) —</option>
                                @foreach($dbUsers as $user)
                                    <option wire:key="owner-{{ $user['name'] }}" value="{{ $user['name'] }}">{{ $user['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-700">
                    <button wire:click="$set('showCreateDbModal', false)"
                            class="px-4 py-2 text-sm font-medium text-gray-400 hover:text-white transition-colors">
                        Cancel
                    </button>
                    <button wire:click="createDatabase"
                            wire:loading.attr="disabled"
                            wire:target="createDatabase"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-cyan-600 hover:bg-cyan-700 disabled:opacity-50 text-white rounded-xl text-sm font-medium transition-colors">
                        <svg class="w-4 h-4" wire:loading.class="animate-spin" wire:loading wire:target="createDatabase" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="createDatabase">Create Database</span>
                        <span wire:loading wire:target="createDatabase">Creating...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Password Confirmation Modal --}}
    @if($showPasswordConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
             role="dialog"
             aria-modal="true"
             aria-labelledby="password-confirm-title">
            <div class="bg-gray-800 rounded-2xl shadow-2xl border border-gray-700 w-full max-w-md">
                <div class="flex items-center gap-4 px-6 py-5 border-b border-gray-700">
                    <div class="w-10 h-10 rounded-xl bg-red-500/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 id="password-confirm-title" class="text-white font-semibold">Confirm Destructive Action</h3>
                        <p class="text-gray-400 text-sm mt-0.5">{{ $pendingActionLabel }}</p>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-sm text-gray-400">Enter your password to continue:</p>
                    <div>
                        <input wire:model="confirmPassword"
                               type="password"
                               placeholder="Your password"
                               autofocus
                               wire:keydown.enter="executeConfirmedAction"
                               class="w-full px-4 py-2.5 bg-gray-700 border border-gray-600 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent">
                        @error('confirmPassword')
                            <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-700">
                    <button wire:click="cancelConfirmation"
                            class="px-4 py-2 text-sm font-medium text-gray-400 hover:text-white transition-colors">
                        Cancel
                    </button>
                    <button wire:click="executeConfirmedAction"
                            wire:loading.attr="disabled"
                            wire:target="executeConfirmedAction"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white rounded-xl text-sm font-medium transition-colors">
                        <svg class="w-4 h-4 animate-spin" wire:loading wire:target="executeConfirmedAction" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Confirm Delete
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Create User Modal --}}
    @if($showCreateUserModal)
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-gray-800 rounded-2xl shadow-2xl border border-gray-700 max-w-md w-full">
                <div class="flex items-center justify-between px-6 py-5 border-b border-gray-700">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        Create Database User
                    </h3>
                    <button wire:click="$set('showCreateUserModal', false)"
                            class="text-gray-400 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Username</label>
                        <input wire:model="newUserName"
                               type="text"
                               placeholder="e.g. app_user"
                               class="w-full px-4 py-2.5 bg-gray-700 border border-gray-600 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm">
                        @error('newUserName')
                            <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Password</label>
                        <div class="flex gap-2">
                            <input wire:model="newUserPassword"
                                   type="text"
                                   placeholder="Enter a strong password"
                                   class="flex-1 px-4 py-2.5 bg-gray-700 border border-gray-600 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono text-sm">
                            <button wire:click="generatePassword"
                                    type="button"
                                    title="Generate random password"
                                    class="px-3 py-2.5 bg-gray-700 hover:bg-gray-600 border border-gray-600 rounded-xl text-gray-300 hover:text-white transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </button>
                        </div>
                        @error('newUserPassword')
                            <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-1.5 text-xs text-gray-500">Minimum 8 characters. Click the refresh icon to generate a random password.</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-700">
                    <button wire:click="$set('showCreateUserModal', false)"
                            class="px-4 py-2 text-sm font-medium text-gray-400 hover:text-white transition-colors">
                        Cancel
                    </button>
                    <button wire:click="createUser"
                            wire:loading.attr="disabled"
                            wire:target="createUser"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-xl text-sm font-medium transition-colors">
                        <svg class="w-4 h-4 animate-spin" wire:loading wire:target="createUser" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="createUser">Create User</span>
                        <span wire:loading wire:target="createUser">Creating...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
