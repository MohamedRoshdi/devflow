<div class="space-y-6">
    {{-- Hero Section with Gradient --}}
    <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-blue-500 via-cyan-500 to-blue-600 p-8 text-white shadow-xl dark:from-blue-600 dark:via-cyan-600 dark:to-blue-700">
        <div class="absolute inset-0 bg-grid-white/10 [mask-image:linear-gradient(0deg,white,rgba(255,255,255,0.5))]"></div>
        <div class="relative">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold">Database Backups</h2>
                    <p class="mt-2 text-blue-100">Automated database backup and restore management</p>
                </div>
                <div class="flex gap-3">
                    <button wire:click="openCreateBackupModal"
                            class="rounded-lg bg-white/20 px-6 py-3 font-semibold text-white backdrop-blur-sm transition hover:bg-white/30">
                        <i class="fas fa-database mr-2"></i>
                        Create Backup Now
                    </button>
                    <button wire:click="openScheduleModal"
                            class="rounded-lg bg-white px-6 py-3 font-semibold text-blue-600 transition hover:bg-blue-50">
                        <i class="fas fa-clock mr-2"></i>
                        Create Schedule
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Backups</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total_backups'] }}</p>
                </div>
                <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900">
                    <i class="fas fa-database text-2xl text-blue-600 dark:text-blue-400"></i>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Scheduled</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['scheduled_backups'] }}</p>
                </div>
                <div class="rounded-full bg-cyan-100 p-3 dark:bg-cyan-900">
                    <i class="fas fa-clock text-2xl text-cyan-600 dark:text-cyan-400"></i>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Storage Used</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total_size'] }}</p>
                </div>
                <div class="rounded-full bg-indigo-100 p-3 dark:bg-indigo-900">
                    <i class="fas fa-hard-drive text-2xl text-indigo-600 dark:text-indigo-400"></i>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Last Backup</p>
                    <p class="mt-2 text-xl font-bold text-gray-900 dark:text-white">{{ $stats['last_backup'] }}</p>
                </div>
                <div class="rounded-full bg-green-100 p-3 dark:bg-green-900">
                    <i class="fas fa-check-circle text-2xl text-green-600 dark:text-green-400"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Schedules Section --}}
    @if($schedules->isNotEmpty())
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Backup Schedules</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Database</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Frequency</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Next Run</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Last Run</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Retention</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Storage</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                    @foreach($schedules as $schedule)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="flex items-center">
                                <i class="fas fa-database mr-2 text-gray-400"></i>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $schedule->database_name }}</span>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold
                                @if($schedule->database_type === 'mysql') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                @elseif($schedule->database_type === 'postgresql') bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300
                                @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300
                                @endif">
                                {{ strtoupper($schedule->database_type) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                            {{ $schedule->frequency_label }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                            {{ $schedule->next_run_at?->diffForHumans() ?? 'Not scheduled' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                            {{ $schedule->last_run_at?->diffForHumans() ?? 'Never' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                            {{ $schedule->retention_days }} days
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold
                                @if($schedule->storage_disk === 's3') bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300
                                @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300
                                @endif">
                                {{ strtoupper($schedule->storage_disk) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <button wire:click="toggleSchedule({{ $schedule->id }})"
                                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold transition
                                    @if($schedule->is_active)
                                        bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-900 dark:text-green-300 dark:hover:bg-green-800
                                    @else
                                        bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800
                                    @endif">
                                <span class="mr-1 h-2 w-2 rounded-full @if($schedule->is_active) bg-green-500 @else bg-gray-400 @endif"></span>
                                {{ $schedule->is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                            <button wire:click="deleteSchedule({{ $schedule->id }})"
                                    wire:confirm="Are you sure you want to delete this schedule?"
                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Backups List --}}
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Backup History</h3>
        </div>

        @if($backups->isEmpty())
        <div class="px-6 py-12 text-center">
            <i class="fas fa-database text-6xl text-gray-300 dark:text-gray-600"></i>
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No backups yet</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Create your first backup or schedule automated backups</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Database</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Size</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Storage</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                    @foreach($backups as $backup)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="flex items-center">
                                <i class="fas fa-database mr-2 text-gray-400"></i>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $backup->database_name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $backup->file_name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold
                                @if($backup->database_type === 'mysql') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                @elseif($backup->database_type === 'postgresql') bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300
                                @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300
                                @endif">
                                {{ strtoupper($backup->database_type) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                            {{ $backup->created_at->format('M d, Y H:i') }}
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $backup->created_at->diffForHumans() }}</div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                            {{ $backup->file_size_human }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-300">
                            {{ $backup->duration ?? 'N/A' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold
                                @if($backup->storage_disk === 's3') bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300
                                @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300
                                @endif">
                                {{ strtoupper($backup->storage_disk) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @if($backup->status === 'completed')
                                <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-800 dark:bg-green-900 dark:text-green-300">
                                    <i class="fas fa-check-circle mr-1"></i> Completed
                                </span>
                            @elseif($backup->status === 'running')
                                <span class="inline-flex rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                    <i class="fas fa-spinner fa-spin mr-1"></i> Running
                                </span>
                            @elseif($backup->status === 'failed')
                                <span class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-800 dark:bg-red-900 dark:text-red-300">
                                    <i class="fas fa-exclamation-circle mr-1"></i> Failed
                                </span>
                            @else
                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-800 dark:bg-gray-900 dark:text-gray-300">
                                    <i class="fas fa-clock mr-1"></i> Pending
                                </span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                            <div class="flex items-center justify-end gap-2">
                                @if($backup->status === 'completed')
                                    <button wire:click="downloadBackup({{ $backup->id }})"
                                            class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                            title="Download">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <button wire:click="confirmRestoreBackup({{ $backup->id }})"
                                            class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                            title="Restore">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                @endif
                                <button wire:click="confirmDeleteBackup({{ $backup->id }})"
                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                        title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
            {{ $backups->links() }}
        </div>
        @endif
    </div>

    {{-- Create Backup Modal --}}
    @if($showCreateBackupModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="showCreateBackupModal = false"></div>
            <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
            <div class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all dark:bg-gray-800 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                <div class="bg-white px-4 pb-4 pt-5 dark:bg-gray-800 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">Create Database Backup</h3>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Database Type</label>
                            <select wire:model="databaseType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="mysql">MySQL</option>
                                <option value="postgresql">PostgreSQL</option>
                                <option value="sqlite">SQLite</option>
                            </select>
                            @error('databaseType') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Database Name</label>
                            <input type="text" wire:model="databaseName" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="my_database">
                            @error('databaseName') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 dark:bg-gray-900 sm:flex sm:flex-row-reverse sm:px-6">
                    <button wire:click="createBackup" :disabled="isCreatingBackup" class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 sm:ml-3 sm:w-auto sm:text-sm">
                        <span wire:loading.remove wire:target="createBackup">Create Backup</span>
                        <span wire:loading wire:target="createBackup"><i class="fas fa-spinner fa-spin mr-2"></i>Creating...</span>
                    </button>
                    <button wire:click="showCreateBackupModal = false" type="button" class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Create Schedule Modal --}}
    @if($showScheduleModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="showScheduleModal = false"></div>
            <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
            <div class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all dark:bg-gray-800 sm:my-8 sm:w-full sm:max-w-2xl sm:align-middle">
                <div class="bg-white px-4 pb-4 pt-5 dark:bg-gray-800 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">Create Backup Schedule</h3>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Database Type</label>
                            <select wire:model="scheduleDatabaseType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="mysql">MySQL</option>
                                <option value="postgresql">PostgreSQL</option>
                                <option value="sqlite">SQLite</option>
                            </select>
                            @error('scheduleDatabaseType') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Database Name</label>
                            <input type="text" wire:model="scheduleDatabase" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="my_database">
                            @error('scheduleDatabase') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Frequency</label>
                            <select wire:model.live="frequency" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="hourly">Hourly</option>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                            @error('frequency') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Time</label>
                            <input type="time" wire:model="time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            @error('time') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>
                        @if($frequency === 'weekly')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Day of Week</label>
                            <select wire:model="dayOfWeek" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="0">Sunday</option>
                                <option value="1">Monday</option>
                                <option value="2">Tuesday</option>
                                <option value="3">Wednesday</option>
                                <option value="4">Thursday</option>
                                <option value="5">Friday</option>
                                <option value="6">Saturday</option>
                            </select>
                            @error('dayOfWeek') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>
                        @endif
                        @if($frequency === 'monthly')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Day of Month</label>
                            <input type="number" wire:model="dayOfMonth" min="1" max="31" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            @error('dayOfMonth') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>
                        @endif
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Retention (days)</label>
                            <input type="number" wire:model="retentionDays" min="1" max="365" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            @error('retentionDays') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Storage</label>
                            <select wire:model="storageDisk" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="local">Local</option>
                                <option value="s3">Amazon S3</option>
                            </select>
                            @error('storageDisk') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 dark:bg-gray-900 sm:flex sm:flex-row-reverse sm:px-6">
                    <button wire:click="createSchedule" :disabled="isCreatingSchedule" class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 sm:ml-3 sm:w-auto sm:text-sm">
                        <span wire:loading.remove wire:target="createSchedule">Create Schedule</span>
                        <span wire:loading wire:target="createSchedule"><i class="fas fa-spinner fa-spin mr-2"></i>Creating...</span>
                    </button>
                    <button wire:click="showScheduleModal = false" type="button" class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="showDeleteModal = false"></div>
            <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
            <div class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all dark:bg-gray-800 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                <div class="bg-white px-4 pb-4 pt-5 dark:bg-gray-800 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400"></i>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">Delete Backup</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Are you sure you want to delete this backup? This action cannot be undone.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 dark:bg-gray-900 sm:flex sm:flex-row-reverse sm:px-6">
                    <button wire:click="deleteBackup" class="inline-flex w-full justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                        Delete
                    </button>
                    <button wire:click="showDeleteModal = false" type="button" class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Restore Confirmation Modal --}}
    @if($showRestoreModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="showRestoreModal = false"></div>
            <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
            <div class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all dark:bg-gray-800 sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
                <div class="bg-white px-4 pb-4 pt-5 dark:bg-gray-800 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400"></i>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">Restore Database</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Are you sure you want to restore this backup? This will replace all current data in the database.</p>
                                <p class="mt-2 text-sm font-semibold text-red-600 dark:text-red-400">Warning: This action cannot be undone!</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 dark:bg-gray-900 sm:flex sm:flex-row-reverse sm:px-6">
                    <button wire:click="restoreBackup" class="inline-flex w-full justify-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                        <span wire:loading.remove wire:target="restoreBackup">Restore</span>
                        <span wire:loading wire:target="restoreBackup"><i class="fas fa-spinner fa-spin mr-2"></i>Restoring...</span>
                    </button>
                    <button wire:click="showRestoreModal = false" type="button" class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
