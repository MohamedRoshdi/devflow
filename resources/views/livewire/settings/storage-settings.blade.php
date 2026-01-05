<div>
    {{-- Hero Section --}}
    <div class="mb-8 rounded-2xl bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 p-8 text-white shadow-xl">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold">Remote Storage Settings</h1>
                <p class="mt-2 text-indigo-100">Configure cloud storage for backups and file management</p>
            </div>
            <button wire:click="openCreateModal"
                    class="flex items-center gap-2 rounded-xl bg-white/20 px-4 py-2 font-medium backdrop-blur-sm transition hover:bg-white/30">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Storage
            </button>
        </div>
    </div>

    {{-- Storage Configurations List --}}
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        @forelse($this->storageConfigs as $config)
            <div class="rounded-2xl border border-gray-200 bg-white shadow-lg transition hover:shadow-xl dark:border-gray-700 dark:bg-gray-800">
                {{-- Header --}}
                <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            {{-- Driver Icon --}}
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl
                                @if($config->driver === 's3') bg-gradient-to-br from-orange-500 to-amber-600
                                @elseif($config->driver === 'gcs') bg-gradient-to-br from-blue-500 to-cyan-600
                                @elseif($config->driver === 'ftp') bg-gradient-to-br from-green-500 to-emerald-600
                                @elseif($config->driver === 'sftp') bg-gradient-to-br from-purple-500 to-indigo-600
                                @else bg-gradient-to-br from-gray-500 to-slate-600 @endif">
                                @if($config->driver === 's3')
                                    <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2L2 7v10l10 5 10-5V7L12 2zm0 2.18L19.82 8 12 11.82 4.18 8 12 4.18zM4 9.18l7 3.5v7.14l-7-3.5V9.18zm16 0v7.14l-7 3.5v-7.14l7-3.5z"/>
                                    </svg>
                                @elseif($config->driver === 'gcs')
                                    <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12.01 2C6.49 2 2.02 6.48 2.02 12s4.47 10 9.99 10c5.52 0 10.01-4.48 10.01-10S17.53 2 12.01 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/>
                                        <circle cx="12" cy="12" r="5"/>
                                    </svg>
                                @elseif($config->driver === 'ftp' || $config->driver === 'sftp')
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
                                    </svg>
                                @else
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"/>
                                    </svg>
                                @endif
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $config->name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $config->driver_name }}</p>
                            </div>
                        </div>

                        {{-- Status Badge --}}
                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium
                            @if($config->status === 'active') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                            @elseif($config->status === 'testing') bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400
                            @else bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400 @endif">
                            {{ ucfirst($config->status) }}
                        </span>
                    </div>

                    @if($config->is_default)
                        <div class="mt-3">
                            <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Default
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Body --}}
                <div class="p-6">
                    <div class="space-y-3">
                        @if($config->bucket)
                            <div class="flex items-center gap-2 text-sm">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                </svg>
                                <span class="text-gray-600 dark:text-gray-300">Bucket:</span>
                                <span class="font-mono text-xs text-gray-900 dark:text-white">{{ $config->bucket }}</span>
                            </div>
                        @endif

                        @if($config->region)
                            <div class="flex items-center gap-2 text-sm">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-gray-600 dark:text-gray-300">Region:</span>
                                <span class="text-xs text-gray-900 dark:text-white">{{ $config->region }}</span>
                            </div>
                        @endif

                        @if($config->project)
                            <div class="flex items-center gap-2 text-sm">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                <span class="text-gray-600 dark:text-gray-300">Project:</span>
                                <span class="text-xs text-gray-900 dark:text-white">{{ $config->project->name }}</span>
                            </div>
                        @endif

                        @if($config->last_tested_at)
                            <div class="flex items-center gap-2 text-sm">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-gray-600 dark:text-gray-300">Last tested:</span>
                                <span class="text-xs text-gray-900 dark:text-white">{{ $config->last_tested_at->diffForHumans() }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="border-t border-gray-200 p-4 dark:border-gray-700">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex gap-2">
                            <button wire:click="testConnection({{ $config->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="testConnection({{ $config->id }})"
                                    class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                <span wire:loading.remove wire:target="testConnection({{ $config->id }})">Test</span>
                                <span wire:loading wire:target="testConnection({{ $config->id }})">Testing...</span>
                            </button>

                            @if(!$config->is_default)
                                <button wire:click="setAsDefault({{ $config->id }})"
                                        class="rounded-lg border border-blue-300 px-3 py-1.5 text-xs font-medium text-blue-700 transition hover:bg-blue-50 dark:border-blue-600 dark:text-blue-400 dark:hover:bg-blue-900/20">
                                    Set Default
                                </button>
                            @endif
                        </div>

                        <div class="flex gap-2">
                            <button wire:click="openEditModal({{ $config->id }})"
                                    class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-indigo-700">
                                Edit
                            </button>

                            <button wire:click="delete({{ $config->id }})"
                                    wire:confirm="Are you sure you want to delete this storage configuration?"
                                    class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-red-700">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-2xl border-2 border-dashed border-gray-300 p-12 text-center dark:border-gray-700">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No storage configurations</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Get started by adding your first remote storage configuration</p>
                <button wire:click="openCreateModal"
                        class="mt-6 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Storage Configuration
                </button>
            </div>
        @endforelse
    </div>

    {{-- Create/Edit Modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto"
             x-data="{ activeTab: @entangle('activeTab') }"
             @keydown.escape.window="$wire.set('showModal', false)"
             role="dialog"
             aria-modal="true"
             aria-labelledby="modal-title">
            <div class="flex min-h-screen items-center justify-center p-4">
                {{-- Backdrop --}}
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" wire:click="$set('showModal', false)"></div>

                {{-- Modal --}}
                <div class="relative w-full max-w-4xl rounded-2xl bg-white shadow-2xl dark:bg-gray-800">
                    {{-- Header --}}
                    <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $editingId ? 'Edit' : 'Add' }} Storage Configuration
                            </h2>
                            <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-500">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Body --}}
                    <div class="p-6">
                        {{-- Common Fields --}}
                        <div class="mb-6 space-y-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Configuration Name</label>
                                <input type="text" wire:model="name"
                                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                       placeholder="My S3 Storage">
                                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Project (Optional)</label>
                                <select wire:model="project_id"
                                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="">Global (All Projects)</option>
                                    @foreach($this->projects as $project)
                                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Storage Driver Tabs --}}
                        <div class="mb-6">
                            <div class="border-b border-gray-200 dark:border-gray-700">
                                <nav class="-mb-px flex space-x-4">
                                    <button type="button"
                                            @click="activeTab = 's3'"
                                            :class="activeTab === 's3' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                            class="border-b-2 px-1 py-4 text-sm font-medium transition">
                                        Amazon S3
                                    </button>
                                    <button type="button"
                                            @click="activeTab = 'gcs'"
                                            :class="activeTab === 'gcs' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                            class="border-b-2 px-1 py-4 text-sm font-medium transition">
                                        Google Cloud
                                    </button>
                                    <button type="button"
                                            @click="activeTab = 'ftp'"
                                            :class="activeTab === 'ftp' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                            class="border-b-2 px-1 py-4 text-sm font-medium transition">
                                        FTP
                                    </button>
                                    <button type="button"
                                            @click="activeTab = 'sftp'"
                                            :class="activeTab === 'sftp' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                                            class="border-b-2 px-1 py-4 text-sm font-medium transition">
                                        SFTP
                                    </button>
                                </nav>
                            </div>
                        </div>

                        {{-- S3 Tab --}}
                        <div x-show="activeTab === 's3'" x-cloak class="space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Access Key ID</label>
                                    <input type="text" wire:model="s3_access_key"
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                           placeholder="AKIAIOSFODNN7EXAMPLE">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Secret Access Key</label>
                                    <input type="password" wire:model="s3_secret_key"
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                           placeholder="wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY">
                                </div>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Bucket Name</label>
                                    <input type="text" wire:model="bucket"
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                           placeholder="my-backup-bucket">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Region</label>
                                    <input type="text" wire:model="region"
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                           placeholder="us-east-1">
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Custom Endpoint (Optional - for DigitalOcean Spaces, MinIO)</label>
                                <input type="text" wire:model="endpoint"
                                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                       placeholder="https://nyc3.digitaloceanspaces.com">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Path Prefix (Optional)</label>
                                <input type="text" wire:model="path_prefix"
                                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                       placeholder="backups/devflow">
                            </div>
                        </div>

                        {{-- GCS Tab --}}
                        <div x-show="activeTab === 'gcs'" x-cloak class="space-y-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Service Account JSON</label>
                                <textarea wire:model="gcs_service_account" rows="8"
                                          class="w-full font-mono text-xs rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                          placeholder='{"type": "service_account", "project_id": "...", ...}'></textarea>
                                <p class="mt-1 text-xs text-gray-500">Paste your service account JSON credentials</p>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Bucket Name</label>
                                <input type="text" wire:model="bucket"
                                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                       placeholder="my-gcs-bucket">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Path Prefix (Optional)</label>
                                <input type="text" wire:model="path_prefix"
                                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                       placeholder="backups/devflow">
                            </div>
                        </div>

                        {{-- FTP Tab --}}
                        <div x-show="activeTab === 'ftp'" x-cloak class="space-y-4">
                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="md:col-span-2">
                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Host</label>
                                    <input type="text" wire:model="ftp_host"
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                           placeholder="ftp.example.com">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Port</label>
                                    <input type="text" wire:model="ftp_port"
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                           placeholder="21">
                                </div>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                                    <input type="text" wire:model="ftp_username"
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                           placeholder="ftpuser">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                                    <input type="password" wire:model="ftp_password"
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                           placeholder="••••••••">
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Path</label>
                                <input type="text" wire:model="ftp_path"
                                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                       placeholder="/backups">
                            </div>
                            <div class="flex gap-6">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" wire:model="ftp_passive"
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Passive Mode</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" wire:model="ftp_ssl"
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Use SSL/TLS</span>
                                </label>
                            </div>
                        </div>

                        {{-- SFTP Tab --}}
                        <div x-show="activeTab === 'sftp'" x-cloak class="space-y-4">
                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="md:col-span-2">
                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Host</label>
                                    <input type="text" wire:model="sftp_host"
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                           placeholder="sftp.example.com">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Port</label>
                                    <input type="text" wire:model="sftp_port"
                                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                           placeholder="22">
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                                <input type="text" wire:model="sftp_username"
                                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                       placeholder="sftpuser">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Password (if not using private key)</label>
                                <input type="password" wire:model="sftp_password"
                                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                       placeholder="••••••••">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Private Key (if not using password)</label>
                                <textarea wire:model="sftp_private_key" rows="5"
                                          class="w-full font-mono text-xs rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                          placeholder="-----BEGIN RSA PRIVATE KEY-----&#10;...&#10;-----END RSA PRIVATE KEY-----"></textarea>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Passphrase (if private key is encrypted)</label>
                                <input type="password" wire:model="sftp_passphrase"
                                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                       placeholder="••••••••">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Path</label>
                                <input type="text" wire:model="sftp_path"
                                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                       placeholder="/backups">
                            </div>
                        </div>

                        {{-- Common Encryption Options --}}
                        <div class="mt-6 border-t border-gray-200 pt-6 dark:border-gray-700">
                            <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Encryption Options</h3>
                            <div class="space-y-4">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" wire:model="enable_encryption"
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Enable at-rest encryption (AES-256-GCM)</span>
                                </label>

                                @if($enable_encryption)
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 flex-1">Encryption Key</label>
                                            <button type="button" wire:click="generateEncryptionKey"
                                                    class="rounded-lg bg-green-600 px-3 py-1 text-xs font-medium text-white transition hover:bg-green-700">
                                                Generate Key
                                            </button>
                                        </div>
                                        <input type="text" wire:model="encryption_key"
                                               class="mt-1 w-full font-mono text-xs rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                               placeholder="Click 'Generate Key' to create a secure encryption key">
                                        <p class="mt-1 text-xs text-gray-500">Store this key securely - you will need it to decrypt files</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="border-t border-gray-200 p-6 dark:border-gray-700">
                        <div class="flex items-center justify-end gap-3">
                            <button type="button" wire:click="$set('showModal', false)"
                                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                Cancel
                            </button>
                            <button type="button" wire:click="save"
                                    wire:loading.attr="disabled"
                                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                                <span wire:loading.remove>{{ $editingId ? 'Update' : 'Create' }} Configuration</span>
                                <span wire:loading>Saving...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
