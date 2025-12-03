<div class="space-y-6">
    {{-- Header with filters --}}
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
            Pipeline Run History
        </h2>

        <div class="flex items-center gap-2">
            <button wire:click="refreshRuns" class="btn btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh
            </button>
        </div>
    </div>

    {{-- Status Filter Pills --}}
    <div class="flex flex-wrap gap-2">
        @foreach(['all' => 'All', 'running' => 'Running', 'success' => 'Success', 'failed' => 'Failed', 'pending' => 'Pending', 'cancelled' => 'Cancelled'] as $status => $label)
            <button
                wire:click="setStatusFilter('{{ $status }}')"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-colors
                    {{ $statusFilter === $status
                        ? 'bg-blue-600 text-white'
                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                {{ $label }}
                <span class="ml-1 px-2 py-0.5 rounded-full text-xs
                    {{ $statusFilter === $status ? 'bg-blue-500' : 'bg-gray-300 dark:bg-gray-600' }}">
                    {{ $this->statusCounts[$status] }}
                </span>
            </button>
        @endforeach
    </div>

    {{-- Pipeline Runs List --}}
    <div class="space-y-4">
        @forelse($this->pipelineRuns as $run)
            <div class="card hover:shadow-lg transition-shadow cursor-pointer" wire:click="viewRun({{ $run->id }})">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        {{-- Status and ID --}}
                        <div class="flex items-center gap-3 mb-2">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                {{ $run->status_color === 'green' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                {{ $run->status_color === 'red' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
                                {{ $run->status_color === 'yellow' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                                {{ $run->status_color === 'blue' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                {{ $run->status_color === 'gray' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' : '' }}">
                                <svg class="w-3 h-3 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                {{ ucfirst($run->status) }}
                            </span>

                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                Run #{{ $run->id }}
                            </span>

                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                {{ $run->created_at->diffForHumans() }}
                            </span>
                        </div>

                        {{-- Trigger Info --}}
                        <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                            <span>
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Triggered by: <span class="font-medium">{{ ucfirst($run->triggered_by) }}</span>
                            </span>

                            @if($run->branch)
                                <span>
                                    <svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.707 3.293a1 1 0 010 1.414L5.414 7H11a7 7 0 017 7v2a1 1 0 11-2 0v-2a5 5 0 00-5-5H5.414l2.293 2.293a1 1 0 11-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    Branch: <span class="font-medium">{{ $run->branch }}</span>
                                </span>
                            @endif

                            @if($run->duration)
                                <span>
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Duration: <span class="font-medium">{{ $run->formatted_duration }}</span>
                                </span>
                            @endif
                        </div>

                        {{-- Stage Progress --}}
                        <div class="mt-3">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    Stages: {{ $run->stageRuns->count() }}
                                </span>
                                <div class="flex gap-1">
                                    @foreach($run->stageRuns as $stageRun)
                                        <div class="w-2 h-2 rounded-full
                                            {{ $stageRun->status === 'success' ? 'bg-green-500' : '' }}
                                            {{ $stageRun->status === 'failed' ? 'bg-red-500' : '' }}
                                            {{ $stageRun->status === 'running' ? 'bg-yellow-500 animate-pulse' : '' }}
                                            {{ $stageRun->status === 'pending' ? 'bg-gray-300 dark:bg-gray-600' : '' }}
                                            {{ $stageRun->status === 'skipped' ? 'bg-gray-400 dark:bg-gray-500' : '' }}"
                                            title="{{ $stageRun->pipelineStage->name }} - {{ $stageRun->status }}">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2">
                        @if($run->status === 'failed')
                            <button
                                wire:click.stop="retryRun({{ $run->id }})"
                                class="btn btn-sm btn-secondary"
                                title="Retry Pipeline">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </button>
                        @endif

                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </div>
            </div>
        @empty
            <div class="card text-center py-12">
                <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <p class="text-gray-600 dark:text-gray-400">No pipeline runs found</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $this->pipelineRuns->links() }}
    </div>
</div>
