<div class="relative min-h-screen">
    <!-- Animated Background Orbs (3 layers) -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <!-- Layer 1 - Large slow orbs -->
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-purple-500/20 rounded-full blur-3xl animate-float"></div>
        <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-blue-500/20 rounded-full blur-3xl animate-float-delayed"></div>

        <!-- Layer 2 - Medium speed orbs -->
        <div class="absolute top-1/3 right-1/3 w-64 h-64 bg-indigo-500/15 rounded-full blur-2xl animate-pulse-slow"></div>
        <div class="absolute bottom-1/3 left-1/3 w-64 h-64 bg-pink-500/15 rounded-full blur-2xl animate-pulse-slower"></div>

        <!-- Layer 3 - Small fast orbs -->
        <div class="absolute top-1/2 left-1/2 w-32 h-32 bg-cyan-500/10 rounded-full blur-xl animate-bounce-slow"></div>
        <div class="absolute top-1/4 right-1/2 w-32 h-32 bg-fuchsia-500/10 rounded-full blur-xl animate-spin-very-slow"></div>
    </div>

    <div class="relative z-10">
        <!-- Hero Section with Gradient -->
        <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 dark:from-indigo-600 dark:via-purple-600 dark:to-pink-600 p-8 shadow-xl overflow-hidden">
            <div class="absolute inset-0 bg-black/10 dark:bg-black/20 backdrop-blur-sm"></div>
            <div class="relative z-10 flex justify-between items-center">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="p-2 bg-white/20 dark:bg-white/10 backdrop-blur-md rounded-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <h1 class="text-4xl font-bold text-white">User Management</h1>
                    </div>
                    <p class="text-white/90 text-lg">Manage user accounts and permissions</p>
                </div>
                <button wire:click="createUser"
                        class="bg-white/20 hover:bg-white/30 backdrop-blur-md text-white font-semibold px-6 py-3 rounded-lg transition-all duration-300 hover:scale-105 shadow-lg flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    <span>Add User</span>
                </button>
            </div>
        </div>

        @if (session()->has('message'))
            <div class="mb-6 bg-gradient-to-r from-green-500/20 to-emerald-500/20 dark:from-green-500/30 dark:to-emerald-500/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-400 px-4 py-3 rounded-lg backdrop-blur-sm">
                {{ session('message') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-6 bg-gradient-to-r from-red-500/20 to-red-600/20 dark:from-red-500/30 dark:to-red-600/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-400 px-4 py-3 rounded-lg backdrop-blur-sm">
                {{ session('error') }}
            </div>
        @endif

        <!-- Filters - Glassmorphism -->
        <div class="relative bg-white/80 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 p-6 mb-6 border border-slate-200 dark:border-slate-700/50">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Search Users</label>
                    <input wire:model.live.debounce.300ms="search"
                           type="text"
                           placeholder="Search by name or email..."
                           class="w-full bg-white dark:bg-slate-900/50 border border-slate-300 dark:border-slate-600/50 rounded-lg px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 backdrop-blur-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Filter by Role</label>
                    <select wire:model.live="roleFilter"
                            class="w-full bg-white dark:bg-slate-900/50 border border-slate-300 dark:border-slate-600/50 rounded-lg px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 backdrop-blur-sm">
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Users Table - Glassmorphism -->
        <div class="relative bg-white/80 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden border border-slate-200 dark:border-slate-700/50">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700/50">
                    <!-- Premium Gradient Header -->
                    <thead class="bg-gradient-to-r from-slate-100 via-slate-50 to-slate-100 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-purple-500 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <span>User</span>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    <span>Email</span>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-pink-500 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                    </svg>
                                    <span>Roles</span>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                    </svg>
                                    <span>Projects</span>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-cyan-500 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>Created</span>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                                <div class="flex items-center justify-end space-x-2">
                                    <svg class="w-4 h-4 text-orange-500 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                    </svg>
                                    <span>Actions</span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <!-- Enhanced Table Body with Glassmorphism -->
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700/30">
                        @forelse($users as $user)
                            <tr class="bg-white/50 dark:bg-slate-800/30 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-all duration-300 group backdrop-blur-sm hover:shadow-lg hover:shadow-purple-500/10">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <!-- Gradient Avatar Ring -->
                                        <div class="flex-shrink-0 h-11 w-11 relative">
                                            <div class="absolute inset-0 bg-gradient-to-br from-purple-500 via-pink-500 to-blue-500 rounded-full opacity-75 group-hover:opacity-100 transition-opacity duration-300 animate-spin-very-slow"></div>
                                            <div class="absolute inset-0.5 bg-white dark:bg-slate-900 rounded-full flex items-center justify-center">
                                                <span class="text-transparent bg-clip-text bg-gradient-to-br from-purple-400 via-pink-400 to-blue-400 font-bold text-lg">
                                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-semibold text-slate-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors">{{ $user->name }}</div>
                                            @if($user->id === auth()->id())
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gradient-to-r from-blue-500/20 to-cyan-500/20 text-cyan-600 dark:text-cyan-300 border border-cyan-500/30">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    You
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-slate-700 dark:text-slate-200 font-medium">{{ $user->email }}</div>
                                    @if($user->email_verified_at)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gradient-to-r from-green-500/20 to-emerald-500/20 text-emerald-600 dark:text-emerald-300 border border-emerald-500/30">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            Verified
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-200/50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-400 border border-slate-300 dark:border-slate-600/30">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                            </svg>
                                            Not verified
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse($user->roles as $role)
                                            <!-- Premium Role Badges with Gradients -->
                                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg shadow-sm transition-all duration-300 hover:scale-105
                                                @if($role->name === 'admin')
                                                    bg-gradient-to-r from-purple-600 to-pink-600 text-white border border-purple-400/30 hover:shadow-purple-500/50
                                                @elseif($role->name === 'manager')
                                                    bg-gradient-to-r from-blue-600 to-cyan-600 text-white border border-blue-400/30 hover:shadow-blue-500/50
                                                @else
                                                    bg-gradient-to-r from-slate-600 to-slate-700 text-slate-200 border border-slate-500/30 hover:shadow-slate-500/50
                                                @endif">
                                                @if($role->name === 'admin')
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"></path>
                                                    </svg>
                                                @elseif($role->name === 'manager')
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M6 6V5a3 3 0 013-3h2a3 3 0 013 3v1h2a2 2 0 012 2v3.57A22.952 22.952 0 0110 13a22.95 22.95 0 01-8-1.43V8a2 2 0 012-2h2zm2-1a1 1 0 011-1h2a1 1 0 011 1v1H8V5zm1 5a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                                    </svg>
                                                @else
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                                    </svg>
                                                @endif
                                                {{ ucfirst($role->name) }}
                                            </span>
                                        @empty
                                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-lg bg-slate-200/50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-400 border border-slate-300 dark:border-slate-600/30">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                                                </svg>
                                                No roles
                                            </span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-lg bg-gradient-to-r from-green-500/20 to-emerald-500/20 text-emerald-600 dark:text-emerald-300 border border-emerald-500/30 font-semibold text-sm">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                        </svg>
                                        {{ $user->projects()->count() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400 font-medium">
                                    <div class="flex items-center space-x-1.5">
                                        <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span>{{ $user->created_at->diffForHumans() }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <!-- Gradient Action Button - Edit -->
                                        <button wire:click="editUser({{ $user->id }})"
                                                class="inline-flex items-center px-3 py-1.5 rounded-lg bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-500 hover:to-cyan-500 text-white font-medium transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-blue-500/50 group">
                                            <svg class="w-4 h-4 mr-1.5 group-hover:rotate-12 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            Edit
                                        </button>
                                        @if($user->id !== auth()->id())
                                            <!-- Gradient Action Button - Delete -->
                                            <button wire:click="deleteUser({{ $user->id }})"
                                                    wire:confirm="Are you sure you want to delete this user?"
                                                    class="inline-flex items-center px-3 py-1.5 rounded-lg bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-500 hover:to-pink-500 text-white font-medium transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-red-500/50 group">
                                                <svg class="w-4 h-4 mr-1.5 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                Delete
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center bg-white/50 dark:bg-transparent">
                                    <div class="flex flex-col items-center justify-center space-y-4">
                                        <div class="relative">
                                            <div class="absolute inset-0 bg-gradient-to-br from-purple-500/20 to-pink-500/20 rounded-full blur-2xl"></div>
                                            <div class="relative text-slate-400 dark:text-slate-600 text-6xl">👥</div>
                                        </div>
                                        <p class="text-slate-600 dark:text-slate-500 text-lg font-medium">No users found</p>
                                        @if($search || $roleFilter)
                                            <button wire:click="clearFilters"
                                                    class="inline-flex items-center px-4 py-2 rounded-lg bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 text-white font-medium transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-purple-500/50">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                                Clear filters
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-900/30 backdrop-blur-sm">
                {{ $users->links() }}
            </div>
        </div>

        <!-- Create User Modal - Enhanced Glassmorphism -->
        @if($showCreateModal)
            <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-md overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4" wire:click="closeCreateModal">
                <div class="relative w-full max-w-2xl" @click.stop>
                    <!-- Animated Border Gradient -->
                    <div class="absolute inset-0 bg-gradient-to-r from-purple-600 via-pink-600 to-blue-600 rounded-2xl blur opacity-75 group-hover:opacity-100 transition duration-1000 animate-pulse-slow"></div>

                    <!-- Modal Content with Glassmorphism -->
                    <div class="relative bg-slate-900/90 backdrop-blur-xl border border-slate-700/50 rounded-2xl shadow-2xl overflow-hidden">
                        <!-- Header with Gradient -->
                        <div class="bg-gradient-to-r from-purple-600/20 via-pink-600/20 to-blue-600/20 border-b border-slate-700/50 px-6 py-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-purple-400 via-pink-400 to-blue-400">Create New User</h3>
                                <button wire:click="closeCreateModal" class="text-slate-400 hover:text-white transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <form wire:submit="saveUser" class="p-6 space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-2">Name</label>
                                <input wire:model="name" type="text" required
                                       class="w-full bg-slate-800/50 border border-slate-600/50 rounded-lg px-4 py-2.5 text-white placeholder-slate-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 backdrop-blur-sm">
                                @error('name') <span class="text-red-400 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-2">Email</label>
                                <input wire:model="email" type="email" required
                                       class="w-full bg-slate-800/50 border border-slate-600/50 rounded-lg px-4 py-2.5 text-white placeholder-slate-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 backdrop-blur-sm">
                                @error('email') <span class="text-red-400 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-2">Password</label>
                                <input wire:model="password" type="password" required
                                       class="w-full bg-slate-800/50 border border-slate-600/50 rounded-lg px-4 py-2.5 text-white placeholder-slate-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 backdrop-blur-sm">
                                @error('password') <span class="text-red-400 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-2">Confirm Password</label>
                                <input wire:model="password_confirmation" type="password" required
                                       class="w-full bg-slate-800/50 border border-slate-600/50 rounded-lg px-4 py-2.5 text-white placeholder-slate-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 backdrop-blur-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-3">Roles</label>
                                <div class="space-y-2 bg-slate-800/30 rounded-lg p-4 border border-slate-700/50">
                                    @foreach($roles as $role)
                                        <label class="flex items-center p-2 rounded-lg hover:bg-slate-700/30 transition-colors cursor-pointer group">
                                            <input wire:model="selectedRoles" type="checkbox" value="{{ $role->name }}"
                                                   class="rounded border-slate-600 text-purple-600 focus:ring-purple-500 focus:ring-offset-slate-900 bg-slate-800">
                                            <span class="ml-3 text-sm text-slate-300 group-hover:text-white font-medium transition-colors">{{ ucfirst($role->name) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex justify-end space-x-3 pt-4 border-t border-slate-700/50">
                                <button type="button" wire:click="closeCreateModal"
                                        class="px-5 py-2.5 bg-slate-700/50 hover:bg-slate-600/50 text-slate-300 hover:text-white rounded-lg transition-all duration-300 font-medium border border-slate-600/50 backdrop-blur-sm">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="px-5 py-2.5 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 text-white rounded-lg transition-all duration-300 font-semibold hover:scale-105 hover:shadow-lg hover:shadow-purple-500/50">
                                    Create User
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Edit User Modal - Enhanced Glassmorphism -->
        @if($showEditModal)
            <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-md overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4" wire:click="closeEditModal">
                <div class="relative w-full max-w-2xl" @click.stop>
                    <!-- Animated Border Gradient -->
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-600 via-cyan-600 to-purple-600 rounded-2xl blur opacity-75 group-hover:opacity-100 transition duration-1000 animate-pulse-slow"></div>

                    <!-- Modal Content with Glassmorphism -->
                    <div class="relative bg-slate-900/90 backdrop-blur-xl border border-slate-700/50 rounded-2xl shadow-2xl overflow-hidden">
                        <!-- Header with Gradient -->
                        <div class="bg-gradient-to-r from-blue-600/20 via-cyan-600/20 to-purple-600/20 border-b border-slate-700/50 px-6 py-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-cyan-400 to-purple-400">Edit User</h3>
                                <button wire:click="closeEditModal" class="text-slate-400 hover:text-white transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <form wire:submit="updateUser" class="p-6 space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-2">Name</label>
                                <input wire:model="name" type="text" required
                                       class="w-full bg-slate-800/50 border border-slate-600/50 rounded-lg px-4 py-2.5 text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 backdrop-blur-sm">
                                @error('name') <span class="text-red-400 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-2">Email</label>
                                <input wire:model="email" type="email" required
                                       class="w-full bg-slate-800/50 border border-slate-600/50 rounded-lg px-4 py-2.5 text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 backdrop-blur-sm">
                                @error('email') <span class="text-red-400 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-2">New Password <span class="text-slate-500 text-xs">(leave blank to keep current)</span></label>
                                <input wire:model="password" type="password"
                                       class="w-full bg-slate-800/50 border border-slate-600/50 rounded-lg px-4 py-2.5 text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 backdrop-blur-sm">
                                @error('password') <span class="text-red-400 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-2">Confirm Password</label>
                                <input wire:model="password_confirmation" type="password"
                                       class="w-full bg-slate-800/50 border border-slate-600/50 rounded-lg px-4 py-2.5 text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 backdrop-blur-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-3">Roles</label>
                                <div class="space-y-2 bg-slate-800/30 rounded-lg p-4 border border-slate-700/50">
                                    @foreach($roles as $role)
                                        <label class="flex items-center p-2 rounded-lg hover:bg-slate-700/30 transition-colors cursor-pointer group">
                                            <input wire:model="selectedRoles" type="checkbox" value="{{ $role->name }}"
                                                   class="rounded border-slate-600 text-blue-600 focus:ring-blue-500 focus:ring-offset-slate-900 bg-slate-800">
                                            <span class="ml-3 text-sm text-slate-300 group-hover:text-white font-medium transition-colors">{{ ucfirst($role->name) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex justify-end space-x-3 pt-4 border-t border-slate-700/50">
                                <button type="button" wire:click="closeEditModal"
                                        class="px-5 py-2.5 bg-slate-700/50 hover:bg-slate-600/50 text-slate-300 hover:text-white rounded-lg transition-all duration-300 font-medium border border-slate-600/50 backdrop-blur-sm">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-500 hover:to-cyan-500 text-white rounded-lg transition-all duration-300 font-semibold hover:scale-105 hover:shadow-lg hover:shadow-blue-500/50">
                                    Update User
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
