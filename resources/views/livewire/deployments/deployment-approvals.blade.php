<div>
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow dark:shadow-gray-900/60 overflow-hidden">
        <div class="bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 px-6 py-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Deployment Approvals
                    </h1>
                    <p class="text-white/80 mt-2 max-w-2xl">Review and approve pending deployments to production environments.</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('deployments.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white/20 hover:bg-white/30 text-white rounded-full font-semibold transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Deployments
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-8">
            <!-- Stats -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                <div class="p-4 rounded-xl border border-amber-100 bg-amber-50 dark:border-amber-900/60 dark:bg-amber-900/20">
                    <p class="text-xs uppercase font-semibold tracking-wide text-amber-700 dark:text-amber-300">Pending Approvals</p>
                    <p class="mt-2 text-2xl font-bold text-amber-900 dark:text-amber-100">{{ $stats['pending'] ?? 0 }}</p>
                </div>
                <div class="p-4 rounded-xl border border-emerald-100 bg-emerald-50 dark:border-emerald-900/60 dark:bg-emerald-900/20">
                    <p class="text-xs uppercase font-semibold tracking-wide text-emerald-700 dark:text-emerald-300">Approved</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-900 dark:text-emerald-100">{{ $stats['approved'] ?? 0 }}</p>
                </div>
                <div class="p-4 rounded-xl border border-rose-100 bg-rose-50 dark:border-rose-900/60 dark:bg-rose-900/20">
                    <p class="text-xs uppercase font-semibold tracking-wide text-rose-700 dark:text-rose-300">Rejected</p>
                    <p class="mt-2 text-2xl font-bold text-rose-900 dark:text-rose-100">{{ $stats['rejected'] ?? 0 }}</p>
                </div>
                <div class="p-4 rounded-xl border border-blue-100 bg-blue-50 dark:border-blue-900/60 dark:bg-blue-900/20">
                    <p class="text-xs uppercase font-semibold tracking-wide text-blue-700 dark:text-blue-300">Expired</p>
                    <p class="mt-2 text-2xl font-bold text-blue-900 dark:text-blue-100">{{ $stats['expired'] ?? 0 }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Search</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M9.5 17a7.5 7.5 0 117.5-7.5 7.5 7.5 0 01-7.5 7.5z" />
                            </svg>
                        </span>
                        <input type="text" placeholder="Project name or requester" wire:model.live.debounce.500ms="search"
                               class="w-full pl-9 pr-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-gray-200 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Status</label>
                    <select wire:model.live="statusFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-gray-200 focus:ring-blue-500 focus:border-blue-500">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="expired">Expired</option>
                        <option value="all">All statuses</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Project</label>
                    <select wire:model.live="projectFilter" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-gray-200 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All projects</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Approvals List -->
            @if($approvals->count())
                <div class="space-y-4">
                    @foreach($approvals as $approval)
                        <div class="p-6 bg-white dark:bg-gray-900 rounded-xl border-2 transition-all duration-200 hover:shadow-lg @if($approval->status === 'pending') border-amber-200 dark:border-amber-800/50 @elseif($approval->status === 'approved') border-emerald-200 dark:border-emerald-800/50 @elseif($approval->status === 'rejected') border-rose-200 dark:border-rose-800/50 @else border-gray-200 dark:border-gray-700 @endif">
                            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                <div class="flex-1 space-y-3">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wide shadow-lg @if($approval->status === 'pending') bg-gradient-to-r from-amber-500 to-orange-600 text-white shadow-amber-500/40 @elseif($approval->status === 'approved') bg-gradient-to-r from-emerald-500 to-green-600 text-white shadow-emerald-500/40 @elseif($approval->status === 'rejected') bg-gradient-to-r from-red-500 to-rose-600 text-white shadow-red-500/40 @else bg-gradient-to-r from-gray-500 to-slate-600 text-white shadow-gray-500/40 @endif">
                                            @if($approval->status === 'pending')
                                                <svg class="w-4 h-4 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            @elseif($approval->status === 'approved')
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            @elseif($approval->status === 'rejected')
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            @endif
                                            {{ ucfirst($approval->status) }}
                                        </span>
                                        <a href="{{ route('projects.show', $approval->deployment->project) }}" class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg text-xs font-semibold hover:bg-blue-200 dark:hover:bg-blue-900/50 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                            </svg>
                                            {{ $approval->deployment->project->name }}
                                        </a>
                                    </div>

                                    <div class="space-y-2">
                                        <div class="flex items-start gap-2">
                                            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <div>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">Requested by: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $approval->requester->name }}</span></p>
                                                <p class="text-xs text-gray-500 dark:text-gray-500">{{ $approval->requested_at->diffForHumans() }}</p>
                                            </div>
                                        </div>

                                        <div class="flex items-start gap-2">
                                            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                            </svg>
                                            <div>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">Branch: <code class="text-xs font-mono bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">{{ $approval->deployment->branch }}</code></p>
                                                @if($approval->deployment->commit_hash)
                                                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Commit: <code class="font-mono">{{ substr($approval->deployment->commit_hash, 0, 7) }}</code></p>
                                                @endif
                                            </div>
                                        </div>

                                        @if($approval->notes)
                                            <div class="flex items-start gap-2">
                                                <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                                </svg>
                                                <div class="flex-1">
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">Request Notes:</p>
                                                    <p class="text-sm text-gray-700 dark:text-gray-300 mt-1 bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">{{ $approval->notes }}</p>
                                                </div>
                                            </div>
                                        @endif

                                        @if($approval->approval_notes)
                                            <div class="flex items-start gap-2">
                                                <svg class="w-5 h-5 text-emerald-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <div class="flex-1">
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">Approval Notes by {{ $approval->approver->name }}:</p>
                                                    <p class="text-sm text-gray-700 dark:text-gray-300 mt-1 bg-emerald-50 dark:bg-emerald-900/20 p-3 rounded-lg">{{ $approval->approval_notes }}</p>
                                                </div>
                                            </div>
                                        @endif

                                        @if($approval->rejection_reason)
                                            <div class="flex items-start gap-2">
                                                <svg class="w-5 h-5 text-rose-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <div class="flex-1">
                                                    <p class="text-sm text-gray-600 dark:text-gray-400">Rejection Reason by {{ $approval->approver->name }}:</p>
                                                    <p class="text-sm text-gray-700 dark:text-gray-300 mt-1 bg-rose-50 dark:bg-rose-900/20 p-3 rounded-lg">{{ $approval->rejection_reason }}</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                @if($approval->status === 'pending')
                                    <div class="flex gap-3 lg:flex-col">
                                        <button wire:click="openApproveModal({{ $approval->id }})" class="flex-1 lg:flex-none inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-emerald-600 to-green-700 hover:from-emerald-700 hover:to-green-800 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Approve
                                        </button>
                                        <button wire:click="openRejectModal({{ $approval->id }})" class="flex-1 lg:flex-none inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-rose-600 to-red-700 hover:from-rose-700 hover:to-red-800 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                            Reject
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $approvals->links() }}
                </div>
            @else
                <div class="text-center py-16">
                    <svg class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-gray-100">No approvals found</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">There are no deployment approvals matching your criteria.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Approve Modal -->
    @if($showApproveModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ open: @entangle('showApproveModal') }">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75" @click="open = false"></div>

                <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white dark:bg-gray-800 rounded-2xl shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="px-6 pt-6 pb-4">
                        <div class="flex items-start">
                            <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-emerald-100 dark:bg-emerald-900/30 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div class="mt-0 ml-4 text-left">
                                <h3 class="text-lg font-semibold leading-6 text-gray-900 dark:text-gray-100">Approve Deployment</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Are you sure you want to approve this deployment? This will trigger the deployment process.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 pb-4">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Approval Notes (Optional)</label>
                        <textarea wire:model="approvalNotes" rows="3" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-gray-200 focus:ring-emerald-500 focus:border-emerald-500" placeholder="Add any notes about this approval..."></textarea>
                        @error('approvalNotes') <span class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 sm:flex sm:flex-row-reverse gap-3">
                        <button wire:click="approve" type="button" class="inline-flex justify-center w-full px-6 py-3 text-sm font-semibold text-white bg-gradient-to-r from-emerald-600 to-green-700 hover:from-emerald-700 hover:to-green-800 rounded-xl shadow-sm sm:w-auto transition">
                            Approve Deployment
                        </button>
                        <button @click="open = false" type="button" class="inline-flex justify-center w-full px-6 py-3 mt-3 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto transition">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Reject Modal -->
    @if($showRejectModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ open: @entangle('showRejectModal') }">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75" @click="open = false"></div>

                <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white dark:bg-gray-800 rounded-2xl shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="px-6 pt-6 pb-4">
                        <div class="flex items-start">
                            <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-rose-100 dark:bg-rose-900/30 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="w-6 h-6 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                            <div class="mt-0 ml-4 text-left">
                                <h3 class="text-lg font-semibold leading-6 text-gray-900 dark:text-gray-100">Reject Deployment</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Please provide a reason for rejecting this deployment request.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 pb-4">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Rejection Reason <span class="text-rose-600">*</span></label>
                        <textarea wire:model="rejectionReason" rows="3" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-gray-200 focus:ring-rose-500 focus:border-rose-500" placeholder="Explain why this deployment is being rejected..."></textarea>
                        @error('rejectionReason') <span class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 sm:flex sm:flex-row-reverse gap-3">
                        <button wire:click="reject" type="button" class="inline-flex justify-center w-full px-6 py-3 text-sm font-semibold text-white bg-gradient-to-r from-rose-600 to-red-700 hover:from-rose-700 hover:to-red-800 rounded-xl shadow-sm sm:w-auto transition">
                            Reject Deployment
                        </button>
                        <button @click="open = false" type="button" class="inline-flex justify-center w-full px-6 py-3 mt-3 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto transition">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
