<div class="space-y-6">
    {{-- Header --}}
    <div class="card">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Pipeline Run #{{ $pipelineRun->id }}
                    </h2>

                    <span class="px-3 py-1 rounded-full text-sm font-medium
                        {{ $pipelineRun->status_color === 'green' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                        {{ $pipelineRun->status_color === 'red' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}
                        {{ $pipelineRun->status_color === 'yellow' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                        {{ $pipelineRun->status_color === 'blue' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                        {{ $pipelineRun->status_color === 'gray' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' : '' }}">
                        {{ ucfirst($pipelineRun->status) }}
                    </span>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-gray-600 dark:text-gray-400">
                    <div>
                        <span class="font-medium">Project:</span>
                        <a href="{{ route('projects.show', $pipelineRun->project->slug) }}" class="text-blue-600 hover:underline">
                            {{ $pipelineRun->project->name }}
                        </a>
                    </div>

                    <div>
                        <span class="font-medium">Triggered by:</span>
                        {{ ucfirst($pipelineRun->triggered_by) }}
                    </div>

                    @if($pipelineRun->branch)
                        <div>
                            <span class="font-medium">Branch:</span>
                            {{ $pipelineRun->branch }}
                        </div>
                    @endif

                    @if($pipelineRun->commit_sha)
                        <div>
                            <span class="font-medium">Commit:</span>
                            <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">
                                {{ substr($pipelineRun->commit_sha, 0, 7) }}
                            </code>
                        </div>
                    @endif

                    <div>
                        <span class="font-medium">Started:</span>
                        {{ $pipelineRun->started_at?->format('M d, Y H:i:s') ?? 'N/A' }}
                    </div>

                    <div>
                        <span class="font-medium">Duration:</span>
                        {{ $pipelineRun->formatted_duration }}
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-2">
                @if($pipelineRun->isRunning())
                    <button wire:click="cancelPipeline" class="btn btn-danger">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Cancel
                    </button>
                @endif

                @if($pipelineRun->isComplete() && $pipelineRun->status !== 'success')
                    <button wire:click="retryPipeline" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Retry
                    </button>
                @endif

                <button wire:click="refreshPipeline" class="btn btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </button>
            </div>
        </div>

        {{-- Progress Bar --}}
        @if($pipelineRun->isRunning())
            <div class="mt-4">
                <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                    <span>Overall Progress</span>
                    <span>{{ $this->progressPercent }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-500"
                         style="width: {{ $this->progressPercent }}%"></div>
                </div>
            </div>
        @endif
    </div>

    {{-- Statistics --}}
    <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
        @foreach([
            'total' => ['label' => 'Total Stages', 'color' => 'gray'],
            'success' => ['label' => 'Success', 'color' => 'green'],
            'failed' => ['label' => 'Failed', 'color' => 'red'],
            'running' => ['label' => 'Running', 'color' => 'yellow'],
            'pending' => ['label' => 'Pending', 'color' => 'blue'],
            'skipped' => ['label' => 'Skipped', 'color' => 'gray'],
        ] as $key => $stat)
            <div class="card">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">{{ $stat['label'] }}</div>
                <div class="text-2xl font-bold
                    {{ $stat['color'] === 'green' ? 'text-green-600 dark:text-green-400' : '' }}
                    {{ $stat['color'] === 'red' ? 'text-red-600 dark:text-red-400' : '' }}
                    {{ $stat['color'] === 'yellow' ? 'text-yellow-600 dark:text-yellow-400' : '' }}
                    {{ $stat['color'] === 'blue' ? 'text-blue-600 dark:text-blue-400' : '' }}
                    {{ $stat['color'] === 'gray' ? 'text-gray-900 dark:text-white' : '' }}">
                    {{ $this->statistics[$key] }}
                </div>
            </div>
        @endforeach
    </div>

    {{-- Stage Timeline --}}
    <div class="card">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Stage Timeline</h3>

            <div class="flex items-center gap-2">
                <button wire:click="expandAll" class="text-sm text-blue-600 hover:text-blue-700">
                    Expand All
                </button>
                <span class="text-gray-400">|</span>
                <button wire:click="collapseAll" class="text-sm text-blue-600 hover:text-blue-700">
                    Collapse All
                </button>
                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 ml-4">
                    <input type="checkbox" wire:model.live="autoScroll" class="rounded">
                    Auto-scroll
                </label>
            </div>
        </div>

        <div class="space-y-2">
            @foreach($this->stageRuns as $stageRun)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden
                    {{ $stageRun->status === 'running' ? 'ring-2 ring-blue-500' : '' }}">
                    {{-- Stage Header --}}
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-750"
                         wire:click="toggleStage({{ $stageRun->id }})">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3 flex-1">
                                {{-- Status Icon --}}
                                <div class="flex-shrink-0">
                                    @if($stageRun->status === 'success')
                                        <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    @elseif($stageRun->status === 'failed')
                                        <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                    @elseif($stageRun->status === 'running')
                                        <svg class="w-6 h-6 text-yellow-500 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    @elseif($stageRun->status === 'pending')
                                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                        </svg>
                                    @else
                                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                </div>

                                {{-- Stage Info --}}
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-medium text-gray-900 dark:text-white">
                                            {{ $stageRun->pipelineStage->name }}
                                        </h4>
                                        <span class="text-xs px-2 py-0.5 rounded
                                            {{ $stageRun->pipelineStage->type === 'pre_deploy' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                            {{ $stageRun->pipelineStage->type === 'deploy' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                            {{ $stageRun->pipelineStage->type === 'post_deploy' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : '' }}">
                                            {{ str_replace('_', ' ', $stageRun->pipelineStage->type) }}
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        @if($stageRun->started_at)
                                            Started: {{ $stageRun->started_at->format('H:i:s') }}
                                        @endif
                                        @if($stageRun->duration_seconds)
                                            • Duration: {{ $stageRun->formatted_duration }}
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Expand/Collapse Icon --}}
                            <svg class="w-5 h-5 text-gray-400 transform transition-transform
                                {{ $expandedStageId === $stageRun->id || $expandedStageId === -1 ? 'rotate-180' : '' }}"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    {{-- Stage Output --}}
                    @if($expandedStageId === $stageRun->id || $expandedStageId === -1)
                        <div class="p-4 bg-gray-900">
                            @if($stageRun->output)
                                <pre class="text-xs text-gray-100 font-mono overflow-x-auto whitespace-pre-wrap"
                                     x-data="{ autoScroll: @entangle('autoScroll') }"
                                     x-init="if(autoScroll) { $el.scrollTop = $el.scrollHeight; }"
                                     x-effect="if(autoScroll) { $el.scrollTop = $el.scrollHeight; }">{{ $stageRun->output }}</pre>

                                <button wire:click="downloadStageOutput({{ $stageRun->id }})"
                                        class="mt-2 text-xs text-blue-400 hover:text-blue-300">
                                    Download Output
                                </button>
                            @else
                                <p class="text-sm text-gray-400 italic">No output yet</p>
                            @endif

                            @if($stageRun->error_message)
                                <div class="mt-4 p-3 bg-red-900/50 border border-red-700 rounded">
                                    <p class="text-sm text-red-200 font-medium mb-1">Error:</p>
                                    <p class="text-xs text-red-300">{{ $stageRun->error_message }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
