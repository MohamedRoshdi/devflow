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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <h1 class="text-4xl font-bold text-white">Roles & Permissions</h1>
                    </div>
                    <p class="text-white/90 text-lg">Manage system roles and access control</p>
                </div>
                <button wire:click="createRole"
                        class="bg-white/20 hover:bg-white/30 backdrop-blur-md text-white font-semibold px-6 py-3 rounded-lg transition-all duration-300 hover:scale-105 shadow-lg flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    <span>Create Role</span>
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

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Roles Card -->
            <div class="relative bg-slate-800/50 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 p-6 border border-slate-700/50 overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-500/10 to-cyan-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-slate-400 text-sm font-medium mb-1">Total Roles</h3>
                    <p class="text-3xl font-bold text-white">{{ $roles->total() }}</p>
                </div>
            </div>

            <!-- Total Permissions Card -->
            <div class="relative bg-slate-800/50 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 p-6 border border-slate-700/50 overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-br from-purple-500/10 to-pink-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-slate-400 text-sm font-medium mb-1">Total Permissions</h3>
                    <p class="text-3xl font-bold text-white">{{ $permissions->count() }}</p>
                </div>
            </div>

            <!-- Permission Categories Card -->
            <div class="relative bg-slate-800/50 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 p-6 border border-slate-700/50 overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/10 to-blue-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-indigo-500 to-blue-500 rounded-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-slate-400 text-sm font-medium mb-1">Categories</h3>
                    <p class="text-3xl font-bold text-white">{{ count($groupedPermissions) }}</p>
                </div>
            </div>
        </div>

        <!-- Search Filter - Glassmorphism -->
        <div class="relative bg-slate-800/50 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 p-6 mb-6 border border-slate-700/50">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-slate-300 dark:text-slate-300 mb-2">Search Roles</label>
                    <input wire:model.live="search"
                           type="text"
                           placeholder="Search by role name..."
                           class="w-full bg-slate-900/50 border border-slate-600/50 rounded-lg px-4 py-2.5 text-white placeholder-slate-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 backdrop-blur-sm">
                </div>
                @if($search)
                    <div class="flex items-end">
                        <button wire:click="clearFilters"
                                class="px-4 py-2.5 bg-slate-700/50 hover:bg-slate-600/50 text-slate-300 hover:text-white rounded-lg transition-all duration-300 font-medium border border-slate-600/50 backdrop-blur-sm flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            <span>Clear</span>
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Roles Grid - Glassmorphism Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($roles as $role)
                <div class="relative bg-slate-800/50 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 p-6 border border-slate-700/50 overflow-hidden group">
                    <!-- Gradient Hover Effect -->
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-500/10 via-pink-500/10 to-blue-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                    <div class="relative z-10">
                        <!-- Role Header -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <!-- Role Icon with Gradient -->
                                    <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center shadow-lg">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-white group-hover:text-purple-300 transition-colors">{{ ucfirst($role->name) }}</h3>
                                        <span class="text-xs text-slate-400">Guard: {{ $role->guard_name }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Role Stats -->
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="bg-slate-900/30 rounded-lg p-3 border border-slate-700/30">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs text-slate-400 mb-1">Permissions</p>
                                        <p class="text-2xl font-bold text-blue-400">{{ $role->permissions_count }}</p>
                                    </div>
                                    <svg class="w-8 h-8 text-blue-400/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="bg-slate-900/30 rounded-lg p-3 border border-slate-700/30">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs text-slate-400 mb-1">Users</p>
                                        <p class="text-2xl font-bold text-green-400">{{ $role->users_count }}</p>
                                    </div>
                                    <svg class="w-8 h-8 text-green-400/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex space-x-2">
                            <button wire:click="managePermissions({{ $role->id }})"
                                    class="flex-1 inline-flex items-center justify-center px-3 py-2 rounded-lg bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-500 hover:to-blue-500 text-white font-medium transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-indigo-500/50 group/btn text-sm">
                                <svg class="w-4 h-4 mr-1.5 group-hover/btn:rotate-12 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                                Permissions
                            </button>
                            <button wire:click="editRole({{ $role->id }})"
                                    class="flex-1 inline-flex items-center justify-center px-3 py-2 rounded-lg bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-500 hover:to-cyan-500 text-white font-medium transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-blue-500/50 group/btn text-sm">
                                <svg class="w-4 h-4 mr-1.5 group-hover/btn:rotate-12 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Edit
                            </button>
                            <button wire:click="confirmDelete({{ $role->id }})"
                                    class="px-3 py-2 rounded-lg bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-500 hover:to-pink-500 text-white font-medium transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-red-500/50 group/btn">
                                <svg class="w-4 h-4 group-hover/btn:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <div class="relative bg-slate-800/50 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl shadow-xl p-16 border border-slate-700/50 text-center">
                        <div class="flex flex-col items-center justify-center space-y-4">
                            <div class="relative">
                                <div class="absolute inset-0 bg-gradient-to-br from-purple-500/20 to-pink-500/20 rounded-full blur-2xl"></div>
                                <div class="relative text-slate-500 dark:text-slate-600 text-6xl">🔐</div>
                            </div>
                            <p class="text-slate-400 dark:text-slate-500 text-lg font-medium">No roles found</p>
                            @if($search)
                                <button wire:click="clearFilters"
                                        class="inline-flex items-center px-4 py-2 rounded-lg bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 text-white font-medium transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-purple-500/50">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Clear filters
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($roles->hasPages())
            <div class="mt-8">
                {{ $roles->links() }}
            </div>
        @endif

        <!-- Create Role Modal -->
        @if($showCreateModal)
            <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-md overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4" wire:click="closeCreateModal">
                <div class="relative w-full max-w-3xl" @click.stop>
                    <!-- Animated Border Gradient -->
                    <div class="absolute inset-0 bg-gradient-to-r from-purple-600 via-pink-600 to-blue-600 rounded-2xl blur opacity-75 animate-pulse-slow"></div>

                    <!-- Modal Content -->
                    <div class="relative bg-slate-900/90 backdrop-blur-xl border border-slate-700/50 rounded-2xl shadow-2xl overflow-hidden">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-purple-600/20 via-pink-600/20 to-blue-600/20 border-b border-slate-700/50 px-6 py-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-purple-400 via-pink-400 to-blue-400">Create New Role</h3>
                                <button wire:click="closeCreateModal" class="text-slate-400 hover:text-white transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <form wire:submit="saveRole" class="p-6 space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-2">Role Name</label>
                                <input wire:model="roleName" type="text" required
                                       placeholder="e.g., Project Manager"
                                       class="w-full bg-slate-800/50 border border-slate-600/50 rounded-lg px-4 py-2.5 text-white placeholder-slate-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 backdrop-blur-sm">
                                @error('roleName') <span class="text-red-400 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-3">Permissions</label>
                                <div class="max-h-96 overflow-y-auto space-y-4 bg-slate-800/30 rounded-lg p-4 border border-slate-700/50">
                                    @foreach($groupedPermissions as $category => $categoryPermissions)
                                        <div class="space-y-2">
                                            <h4 class="text-sm font-bold text-slate-300 uppercase tracking-wider flex items-center space-x-2 border-b border-slate-700/50 pb-2">
                                                <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                                </svg>
                                                <span>{{ ucfirst($category) }}</span>
                                            </h4>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 pl-6">
                                                @foreach($categoryPermissions as $permission)
                                                    <label class="flex items-center p-2 rounded-lg hover:bg-slate-700/30 transition-colors cursor-pointer group">
                                                        <input wire:model="selectedPermissions" type="checkbox" value="{{ $permission->name }}"
                                                               class="rounded border-slate-600 text-purple-600 focus:ring-purple-500 focus:ring-offset-slate-900 bg-slate-800">
                                                        <span class="ml-3 text-sm text-slate-300 group-hover:text-white font-medium transition-colors">{{ str_replace('-', ' ', ucfirst($permission->name)) }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
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
                                    Create Role
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Edit Role Modal -->
        @if($showEditModal)
            <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-md overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4" wire:click="closeEditModal">
                <div class="relative w-full max-w-3xl" @click.stop>
                    <!-- Animated Border Gradient -->
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-600 via-cyan-600 to-purple-600 rounded-2xl blur opacity-75 animate-pulse-slow"></div>

                    <!-- Modal Content -->
                    <div class="relative bg-slate-900/90 backdrop-blur-xl border border-slate-700/50 rounded-2xl shadow-2xl overflow-hidden">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-blue-600/20 via-cyan-600/20 to-purple-600/20 border-b border-slate-700/50 px-6 py-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-cyan-400 to-purple-400">Edit Role</h3>
                                <button wire:click="closeEditModal" class="text-slate-400 hover:text-white transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <form wire:submit="updateRole" class="p-6 space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-2">Role Name</label>
                                <input wire:model="roleName" type="text" required
                                       class="w-full bg-slate-800/50 border border-slate-600/50 rounded-lg px-4 py-2.5 text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 backdrop-blur-sm">
                                @error('roleName') <span class="text-red-400 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-3">Permissions</label>
                                <div class="max-h-96 overflow-y-auto space-y-4 bg-slate-800/30 rounded-lg p-4 border border-slate-700/50">
                                    @foreach($groupedPermissions as $category => $categoryPermissions)
                                        <div class="space-y-2">
                                            <h4 class="text-sm font-bold text-slate-300 uppercase tracking-wider flex items-center space-x-2 border-b border-slate-700/50 pb-2">
                                                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                                </svg>
                                                <span>{{ ucfirst($category) }}</span>
                                            </h4>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 pl-6">
                                                @foreach($categoryPermissions as $permission)
                                                    <label class="flex items-center p-2 rounded-lg hover:bg-slate-700/30 transition-colors cursor-pointer group">
                                                        <input wire:model="selectedPermissions" type="checkbox" value="{{ $permission->name }}"
                                                               class="rounded border-slate-600 text-blue-600 focus:ring-blue-500 focus:ring-offset-slate-900 bg-slate-800">
                                                        <span class="ml-3 text-sm text-slate-300 group-hover:text-white font-medium transition-colors">{{ str_replace('-', ' ', ucfirst($permission->name)) }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
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
                                    Update Role
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Delete Confirmation Modal -->
        @if($showDeleteModal)
            <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-md overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4" wire:click="closeDeleteModal">
                <div class="relative w-full max-w-md" @click.stop>
                    <!-- Animated Border Gradient -->
                    <div class="absolute inset-0 bg-gradient-to-r from-red-600 to-pink-600 rounded-2xl blur opacity-75 animate-pulse-slow"></div>

                    <!-- Modal Content -->
                    <div class="relative bg-slate-900/90 backdrop-blur-xl border border-slate-700/50 rounded-2xl shadow-2xl overflow-hidden">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-red-600/20 to-pink-600/20 border-b border-slate-700/50 px-6 py-4">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 bg-red-500/20 rounded-lg">
                                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-white">Delete Role</h3>
                            </div>
                        </div>

                        <div class="p-6">
                            <p class="text-slate-300 mb-6">Are you sure you want to delete this role? This action cannot be undone.</p>

                            <div class="flex justify-end space-x-3">
                                <button type="button" wire:click="closeDeleteModal"
                                        class="px-5 py-2.5 bg-slate-700/50 hover:bg-slate-600/50 text-slate-300 hover:text-white rounded-lg transition-all duration-300 font-medium border border-slate-600/50 backdrop-blur-sm">
                                    Cancel
                                </button>
                                <button wire:click="deleteRole"
                                        class="px-5 py-2.5 bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-500 hover:to-pink-500 text-white rounded-lg transition-all duration-300 font-semibold hover:scale-105 hover:shadow-lg hover:shadow-red-500/50">
                                    Delete Role
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Manage Permissions Modal -->
        @if($showPermissionsModal)
            <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-md overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4" wire:click="closePermissionsModal">
                <div class="relative w-full max-w-3xl" @click.stop>
                    <!-- Animated Border Gradient -->
                    <div class="absolute inset-0 bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-600 rounded-2xl blur opacity-75 animate-pulse-slow"></div>

                    <!-- Modal Content -->
                    <div class="relative bg-slate-900/90 backdrop-blur-xl border border-slate-700/50 rounded-2xl shadow-2xl overflow-hidden">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-indigo-600/20 via-blue-600/20 to-cyan-600/20 border-b border-slate-700/50 px-6 py-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-blue-400 to-cyan-400">Manage Permissions</h3>
                                <button wire:click="closePermissionsModal" class="text-slate-400 hover:text-white transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <form wire:submit="updatePermissions" class="p-6 space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-slate-300 mb-3">Select Permissions</label>
                                <div class="max-h-96 overflow-y-auto space-y-4 bg-slate-800/30 rounded-lg p-4 border border-slate-700/50">
                                    @foreach($groupedPermissions as $category => $categoryPermissions)
                                        <div class="space-y-2">
                                            <h4 class="text-sm font-bold text-slate-300 uppercase tracking-wider flex items-center space-x-2 border-b border-slate-700/50 pb-2">
                                                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                                </svg>
                                                <span>{{ ucfirst($category) }}</span>
                                            </h4>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 pl-6">
                                                @foreach($categoryPermissions as $permission)
                                                    <label class="flex items-center p-2 rounded-lg hover:bg-slate-700/30 transition-colors cursor-pointer group">
                                                        <input wire:model="selectedPermissions" type="checkbox" value="{{ $permission->name }}"
                                                               class="rounded border-slate-600 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-slate-900 bg-slate-800">
                                                        <span class="ml-3 text-sm text-slate-300 group-hover:text-white font-medium transition-colors">{{ str_replace('-', ' ', ucfirst($permission->name)) }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex justify-end space-x-3 pt-4 border-t border-slate-700/50">
                                <button type="button" wire:click="closePermissionsModal"
                                        class="px-5 py-2.5 bg-slate-700/50 hover:bg-slate-600/50 text-slate-300 hover:text-white rounded-lg transition-all duration-300 font-medium border border-slate-600/50 backdrop-blur-sm">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-cyan-600 hover:from-indigo-500 hover:to-cyan-500 text-white rounded-lg transition-all duration-300 font-semibold hover:scale-105 hover:shadow-lg hover:shadow-indigo-500/50">
                                    Update Permissions
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
