<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Hero Section with Gradient -->
        <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 dark:from-indigo-600 dark:via-purple-600 dark:to-pink-600 p-8 shadow-xl overflow-hidden">
            <div class="absolute inset-0 bg-black/10 dark:bg-black/20"></div>
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="p-2 bg-white/20 dark:bg-white/10 backdrop-blur-md rounded-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white">Pipeline Builder</h1>
                    </div>
                    <p class="text-white/90 text-lg">
                        @if($project)
                            Configure deployment pipeline for {{ $project->name }}
                        @else
                            Visual CI/CD Pipeline Configuration
                        @endif
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <button wire:click="$set('showTemplateModal', true)" class="px-6 py-3 bg-white/20 backdrop-blur-md text-white rounded-lg hover:bg-white/30 transition-all shadow-lg hover:shadow-xl font-semibold flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                        </svg>
                        <span>Apply Template</span>
                    </button>
                </div>
            </div>
        </div>

        @if(!$project)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-12 text-center mb-8">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No Project Selected</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Please select a project from the projects page to configure its pipeline</p>
                <div class="mt-6">
                    <a href="{{ route('projects.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                        View Projects
                    </a>
                </div>
            </div>
        @else
            <!-- Three Column Layout: Pre-Deploy | Deploy | Post-Deploy -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                @foreach(['pre_deploy' => ['title' => 'Pre-Deploy', 'icon' => 'gear', 'color' => 'blue'], 'deploy' => ['title' => 'Deploy', 'icon' => 'rocket', 'color' => 'green'], 'post_deploy' => ['title' => 'Post-Deploy', 'icon' => 'check', 'color' => 'purple']] as $type => $config)
                    <div class="flex flex-col">
                        <!-- Column Header -->
                        <div class="bg-gradient-to-br from-{{ $config['color'] }}-500 to-{{ $config['color'] }}-600 dark:from-{{ $config['color'] }}-600 dark:to-{{ $config['color'] }}-700 rounded-t-2xl p-4 shadow-xl">
                            <div class="flex items-center justify-between text-white">
                                <div class="flex items-center space-x-2">
                                    @if($config['icon'] === 'gear')
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                    @elseif($config['icon'] === 'rocket')
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    @else
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    @endif
                                    <h3 class="font-bold text-lg">{{ $config['title'] }}</h3>
                                </div>
                                <span class="bg-white/20 backdrop-blur-md px-3 py-1 rounded-full text-sm font-medium">
                                    {{ count($stages[$type] ?? []) }}
                                </span>
                            </div>
                        </div>

                        <!-- Stages Container (Sortable) -->
                        <div class="bg-white dark:bg-gray-800 rounded-b-2xl shadow-xl p-4 min-h-[400px] flex-1"
                             data-stage-type="{{ $type }}"
                             id="stage-container-{{ $type }}">

                            <div class="space-y-3 sortable-container" data-type="{{ $type }}">
                                @forelse($stages[$type] ?? [] as $stage)
                                    <!-- Stage Card -->
                                    <div class="stage-item bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 rounded-xl p-4 border-2 border-transparent hover:border-{{ $config['color'] }}-400 dark:hover:border-{{ $config['color'] }}-500 transition-all cursor-move shadow-md hover:shadow-lg {{ !$stage['enabled'] ? 'opacity-60' : '' }}"
                                         data-stage-id="{{ $stage['id'] }}">

                                        <!-- Drag Handle -->
                                        <div class="flex items-start space-x-3">
                                            <div class="drag-handle cursor-move text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 mt-1">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                                </svg>
                                            </div>

                                            <!-- Stage Content -->
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-start justify-between mb-2">
                                                    <div class="flex items-center space-x-2 flex-1">
                                                        <!-- Icon based on stage type -->
                                                        <div class="p-2 bg-{{ $config['color'] }}-100 dark:bg-{{ $config['color'] }}-900/30 rounded-lg flex-shrink-0">
                                                            @php
                                                                $stageName = strtolower($stage['name']);
                                                                $iconPath = 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4'; // default code icon

                                                                if (str_contains($stageName, 'test')) {
                                                                    $iconPath = 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z';
                                                                } elseif (str_contains($stageName, 'build')) {
                                                                    $iconPath = 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z';
                                                                } elseif (str_contains($stageName, 'deploy')) {
                                                                    $iconPath = 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12';
                                                                } elseif (str_contains($stageName, 'install') || str_contains($stageName, 'composer') || str_contains($stageName, 'npm')) {
                                                                    $iconPath = 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4';
                                                                } elseif (str_contains($stageName, 'migrate') || str_contains($stageName, 'database')) {
                                                                    $iconPath = 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4';
                                                                }
                                                            @endphp
                                                            <svg class="w-4 h-4 text-{{ $config['color'] }}-600 dark:text-{{ $config['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"></path>
                                                            </svg>
                                                        </div>

                                                        <div class="flex-1 min-w-0">
                                                            <h4 class="font-semibold text-gray-900 dark:text-white truncate">{{ $stage['name'] }}</h4>
                                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                                {{ count($stage['commands']) }} command(s) • {{ $stage['timeout_seconds'] }}s timeout
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <!-- Toggle Switch -->
                                                    <button wire:click="toggleStage({{ $stage['id'] }})"
                                                            class="flex-shrink-0 w-10 h-6 rounded-full transition-colors relative {{ $stage['enabled'] ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}"
                                                            title="{{ $stage['enabled'] ? 'Enabled' : 'Disabled' }}">
                                                        <span class="absolute w-4 h-4 bg-white rounded-full shadow transition-transform top-1 {{ $stage['enabled'] ? 'translate-x-5' : 'translate-x-1' }}"></span>
                                                    </button>
                                                </div>

                                                <!-- Commands Preview -->
                                                <div class="mt-2 space-y-1">
                                                    @foreach(array_slice($stage['commands'], 0, 2) as $command)
                                                        <div class="text-xs font-mono bg-gray-800 dark:bg-gray-900 text-green-400 px-2 py-1 rounded overflow-hidden text-ellipsis whitespace-nowrap">
                                                            <span class="text-gray-500">$</span> {{ $command }}
                                                        </div>
                                                    @endforeach
                                                    @if(count($stage['commands']) > 2)
                                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                                            +{{ count($stage['commands']) - 2 }} more command(s)
                                                        </p>
                                                    @endif
                                                </div>

                                                <!-- Indicators -->
                                                <div class="mt-3 flex items-center space-x-2">
                                                    @if($stage['continue_on_failure'])
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                            </svg>
                                                            Continue on failure
                                                        </span>
                                                    @endif
                                                    @if(!empty($stage['environment_variables']))
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                            </svg>
                                                            {{ count($stage['environment_variables']) }} env var(s)
                                                        </span>
                                                    @endif
                                                </div>

                                                <!-- Action Buttons -->
                                                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600 flex items-center justify-end space-x-2">
                                                    <button wire:click="editStage({{ $stage['id'] }})"
                                                            class="p-1.5 text-indigo-600 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 rounded-lg transition-colors"
                                                            title="Edit">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                    </button>
                                                    <button wire:click="deleteStage({{ $stage['id'] }})"
                                                            onclick="return confirm('Are you sure you want to delete this stage?')"
                                                            class="p-1.5 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                                            title="Delete">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-12 text-gray-400 dark:text-gray-500">
                                        <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                        </svg>
                                        <p class="text-sm">No stages yet</p>
                                    </div>
                                @endforelse
                            </div>

                            <!-- Add Stage Button -->
                            <button wire:click="addStage('{{ $type }}')"
                                    class="mt-4 w-full px-4 py-3 bg-gradient-to-r from-{{ $config['color'] }}-500 to-{{ $config['color'] }}-600 hover:from-{{ $config['color'] }}-600 hover:to-{{ $config['color'] }}-700 text-white font-medium rounded-xl shadow-md hover:shadow-lg transition-all flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                <span>Add {{ $config['title'] }} Stage</span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Add/Edit Stage Modal -->
        @if($showStageModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showStageModal') }">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showStageModal', false)"></div>

                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl max-w-2xl w-full shadow-2xl">
                        <form wire:submit.prevent="saveStage">
                            <!-- Modal Header -->
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                    {{ $editingStageId ? 'Edit Stage' : 'Add New Stage' }}
                                </h3>
                            </div>

                            <!-- Modal Body -->
                            <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                                <!-- Stage Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Stage Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" wire:model="stageName"
                                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400"
                                           placeholder="e.g., Install Dependencies">
                                    @error('stageName') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                <!-- Stage Type -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Stage Type <span class="text-red-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-3 gap-3">
                                        @foreach(['pre_deploy' => 'Pre-Deploy', 'deploy' => 'Deploy', 'post_deploy' => 'Post-Deploy'] as $value => $label)
                                            <label class="relative flex items-center justify-center p-3 border-2 rounded-lg cursor-pointer transition-all {{ $stageType === $value ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30' : 'border-gray-300 dark:border-gray-600 hover:border-indigo-300' }}">
                                                <input type="radio" wire:model="stageType" value="{{ $value }}" class="sr-only">
                                                <span class="text-sm font-medium {{ $stageType === $value ? 'text-indigo-900 dark:text-indigo-100' : 'text-gray-900 dark:text-gray-100' }}">
                                                    {{ $label }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('stageType') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                <!-- Commands -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Commands <span class="text-red-500">*</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 font-normal">(one per line)</span>
                                    </label>
                                    <textarea wire:model="commands" rows="6"
                                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white font-mono text-sm focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400"
                                              placeholder="composer install&#10;npm run build&#10;php artisan migrate"></textarea>
                                    @error('commands') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                <!-- Timeout -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Timeout (seconds) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" wire:model="timeoutSeconds" min="10" max="3600"
                                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                                    @error('timeoutSeconds') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                <!-- Continue on Failure -->
                                <div>
                                    <label class="flex items-center space-x-3 cursor-pointer">
                                        <input type="checkbox" wire:model="continueOnFailure"
                                               class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Continue pipeline even if this stage fails
                                        </span>
                                    </label>
                                </div>

                                <!-- Environment Variables -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Environment Variables
                                    </label>

                                    @if(!empty($envVariables))
                                        <div class="space-y-2 mb-3">
                                            @foreach($envVariables as $key => $value)
                                                <div class="flex items-center space-x-2 bg-gray-50 dark:bg-gray-700 p-2 rounded-lg">
                                                    <span class="font-mono text-sm text-gray-700 dark:text-gray-300 flex-1">{{ $key }}={{ $value }}</span>
                                                    <button type="button" wire:click="removeEnvVariable('{{ $key }}')"
                                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div class="flex space-x-2">
                                        <input type="text" wire:model="newEnvKey" placeholder="KEY"
                                               class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                                        <input type="text" wire:model="newEnvValue" placeholder="value"
                                               class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                                        <button type="button" wire:click="addEnvVariable"
                                                class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors">
                                            Add
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Footer -->
                            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3 rounded-b-2xl">
                                <button type="button" wire:click="$set('showStageModal', false)"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="px-6 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 shadow-md hover:shadow-lg transition-all">
                                    {{ $editingStageId ? 'Update Stage' : 'Create Stage' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Template Selection Modal -->
        @if($showTemplateModal)
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showTemplateModal', false)"></div>

                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl max-w-2xl w-full shadow-2xl">
                        <!-- Modal Header -->
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Choose Pipeline Template</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Select a pre-configured template to get started quickly</p>
                        </div>

                        <!-- Templates Grid -->
                        <div class="px-6 py-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Laravel Template -->
                            <button wire:click="applyTemplate('laravel')"
                                    class="p-6 bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/30 dark:to-red-800/30 border-2 border-red-200 dark:border-red-700 rounded-xl hover:shadow-lg transition-all text-left">
                                <div class="flex items-center justify-center w-12 h-12 bg-red-500 rounded-xl mb-3">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M23.642 5.43a.364.364 0 01.014.1v5.149c0 .135-.073.26-.189.326l-4.323 2.49v4.934a.378.378 0 01-.188.326L9.93 23.949a.316.316 0 01-.066.027c-.008.002-.016.008-.024.01a.348.348 0 01-.192 0c-.011-.002-.02-.008-.03-.012-.02-.008-.042-.014-.062-.025L.533 18.755a.376.376 0 01-.189-.326V2.974c0-.033.005-.066.014-.098.003-.012.01-.02.014-.032a.369.369 0 01.023-.058c.004-.013.015-.022.023-.033l.033-.045c.012-.01.025-.018.037-.027.014-.012.027-.024.041-.034H.53L5.043.05a.375.375 0 01.375 0L9.93 2.647h.002c.015.01.027.021.04.033l.038.027c.013.014.02.03.033.045.008.011.02.021.025.033.01.02.017.038.024.058.003.011.01.021.013.032.01.031.014.064.014.098v9.652l3.76-2.164V5.527c0-.033.004-.066.013-.098.003-.01.01-.02.013-.032a.487.487 0 01.024-.059c.007-.012.018-.02.025-.033.012-.015.021-.03.033-.043.012-.012.025-.02.037-.028.013-.012.027-.023.041-.032h.001l4.513-2.598a.375.375 0 01.375 0l4.513 2.598c.016.01.027.021.042.031.012.01.025.018.036.028.013.014.022.03.034.044.008.012.019.021.024.033.011.02.018.04.024.06.006.01.012.021.015.032zm-.74 5.032V6.179l-1.578.908-2.182 1.256v4.283zm-4.51 7.75v-4.287l-2.147 1.225-6.126 3.498v4.325zM1.093 3.624v14.588l8.273 4.761v-4.325l-4.322-2.445-.002-.003H5.04c-.014-.01-.025-.021-.04-.031-.011-.01-.024-.018-.035-.027l-.001-.002c-.013-.012-.021-.025-.031-.039-.01-.012-.021-.023-.028-.036h-.002c-.008-.014-.013-.031-.02-.047-.006-.016-.014-.027-.018-.043a.49.49 0 01-.008-.057c-.002-.014-.006-.027-.006-.041V5.789l-2.18-1.257zM5.23.81L1.47 2.974l3.76 2.164 3.758-2.164zm1.956 13.505l2.182-1.256V3.624l-1.58.91-2.182 1.255v9.435zm11.581-10.95l-3.76 2.163 3.76 2.163 3.759-2.164zm-.376 4.978L16.21 7.087 14.03 5.831v4.282l2.182 1.256 1.58.908zm-8.65 9.654l5.514-3.148 2.756-1.572-3.757-2.163-4.323 2.489-3.941 2.27z"/>
                                    </svg>
                                </div>
                                <h4 class="font-bold text-gray-900 dark:text-white mb-1">Laravel</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Composer, NPM, migrations, and caching</p>
                            </button>

                            <!-- Node.js Template -->
                            <button wire:click="applyTemplate('nodejs')"
                                    class="p-6 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 border-2 border-green-200 dark:border-green-700 rounded-xl hover:shadow-lg transition-all text-left">
                                <div class="flex items-center justify-center w-12 h-12 bg-green-600 rounded-xl mb-3">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M11.998,24c-0.321,0-0.641-0.084-0.922-0.247l-2.936-1.737c-0.438-0.245-0.224-0.332-0.08-0.383 c0.585-0.203,0.703-0.25,1.328-0.604c0.065-0.037,0.151-0.023,0.218,0.017l2.256,1.339c0.082,0.045,0.197,0.045,0.272,0l8.795-5.076 c0.082-0.047,0.134-0.141,0.134-0.238V6.921c0-0.099-0.053-0.192-0.137-0.242l-8.791-5.072c-0.081-0.047-0.189-0.047-0.271,0 L3.075,6.68C2.99,6.729,2.936,6.825,2.936,6.921v10.15c0,0.097,0.054,0.189,0.139,0.235l2.409,1.392 c1.307,0.654,2.108-0.116,2.108-0.89V7.787c0-0.142,0.114-0.253,0.256-0.253h1.115c0.139,0,0.255,0.112,0.255,0.253v10.021 c0,1.745-0.95,2.745-2.604,2.745c-0.508,0-0.909,0-2.026-0.551L2.28,18.675c-0.57-0.329-0.922-0.945-0.922-1.604V6.921 c0-0.659,0.353-1.275,0.922-1.603l8.795-5.082c0.557-0.315,1.296-0.315,1.848,0l8.794,5.082c0.57,0.329,0.924,0.944,0.924,1.603 v10.15c0,0.659-0.354,1.273-0.924,1.604l-8.794,5.078C12.643,23.916,12.324,24,11.998,24z M19.099,13.993 c0-1.9-1.284-2.406-3.987-2.763c-2.731-0.361-3.009-0.548-3.009-1.187c0-0.528,0.235-1.233,2.258-1.233 c1.807,0,2.473,0.389,2.747,1.607c0.024,0.115,0.129,0.199,0.247,0.199h1.141c0.071,0,0.138-0.031,0.186-0.081 c0.048-0.054,0.074-0.123,0.067-0.196c-0.177-2.098-1.571-3.076-4.388-3.076c-2.508,0-4.004,1.058-4.004,2.833 c0,1.925,1.488,2.457,3.895,2.695c2.88,0.282,3.103,0.703,3.103,1.269c0,0.983-0.789,1.402-2.642,1.402 c-2.327,0-2.839-0.584-3.011-1.742c-0.02-0.124-0.126-0.215-0.253-0.215h-1.137c-0.141,0-0.254,0.112-0.254,0.253 c0,1.482,0.806,3.248,4.655,3.248C17.501,17.007,19.099,15.91,19.099,13.993z"/>
                                    </svg>
                                </div>
                                <h4 class="font-bold text-gray-900 dark:text-white mb-1">Node.js</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400">NPM install, tests, and build</p>
                            </button>

                            <!-- Static Site Template -->
                            <button wire:click="applyTemplate('static')"
                                    class="p-6 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 border-2 border-blue-200 dark:border-blue-700 rounded-xl hover:shadow-lg transition-all text-left">
                                <div class="flex items-center justify-center w-12 h-12 bg-blue-500 rounded-xl mb-3">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <h4 class="font-bold text-gray-900 dark:text-white mb-1">Static Site</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Simple file copy deployment</p>
                            </button>
                        </div>

                        <!-- Modal Footer -->
                        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 flex justify-end rounded-b-2xl">
                            <button wire:click="$set('showTemplateModal', false)"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- SortableJS Integration -->
    @script
    <script>
        // Initialize Sortable.js for drag-and-drop
        document.addEventListener('livewire:navigated', function() {
            initializeSortable();
        });

        function initializeSortable() {
            const sortableContainers = document.querySelectorAll('.sortable-container');

            sortableContainers.forEach(container => {
                if (container._sortable) {
                    container._sortable.destroy();
                }

                const sortable = Sortable.create(container, {
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'opacity-50',
                    dragClass: 'shadow-2xl',
                    group: {
                        name: container.dataset.type,
                        pull: false,
                        put: false
                    },
                    onEnd: function(evt) {
                        const stageIds = [];
                        const items = evt.to.querySelectorAll('.stage-item');

                        items.forEach(item => {
                            stageIds.push(parseInt(item.dataset.stageId));
                        });

                        $wire.dispatch('stages-reordered', {
                            stageIds: stageIds,
                            type: container.dataset.type
                        });
                    }
                });

                container._sortable = sortable;
            });
        }

        // Initialize on first load
        setTimeout(initializeSortable, 100);

        // Re-initialize after Livewire updates
        Livewire.hook('morph.updated', () => {
            setTimeout(initializeSortable, 100);
        });
    </script>
    @endscript
</div>
