<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Hero Section with Gradient -->
        <div class="relative mb-8 rounded-2xl bg-gradient-to-br from-teal-500 via-emerald-500 to-green-500 dark:from-teal-600 dark:via-emerald-600 dark:to-green-600 p-8 shadow-xl overflow-hidden">
            <div class="absolute inset-0 bg-black/10 dark:bg-black/20"></div>
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="p-2 bg-white/20 dark:bg-white/10 backdrop-blur-md rounded-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white">Script Manager</h1>
                    </div>
                    <p class="text-white/90 text-lg">Create and manage deployment scripts and automation tasks</p>
                </div>
                <div class="flex space-x-3">
                    <button wire:click="$set('showTemplateModal', true)" class="px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-md text-white rounded-lg transition-colors">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Templates
                    </button>
                    <button wire:click="createScript" class="px-4 py-2 bg-white hover:bg-white/90 text-gray-900 rounded-lg transition-colors font-medium">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Create Script
                    </button>
                </div>
            </div>
        </div>

    <!-- Scripts Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Script</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Language</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Settings</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($scripts as $script)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $script->name }}</div>
                                @if($script->description)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($script->description, 50) }}</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $script->type === 'deployment' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $script->type === 'rollback' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $script->type === 'backup' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $script->type === 'maintenance' ? 'bg-gray-100 text-gray-800' : '' }}
                                {{ $script->type === 'custom' ? 'bg-purple-100 text-purple-800' : '' }}">
                                {{ ucfirst($script->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                @switch($script->language)
                                    @case('bash')
                                    @case('sh')
                                        <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>
                                        </svg>
                                        @break
                                    @case('python')
                                        <svg class="w-5 h-5 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M11.914 0C5.82 0 6.2 2.656 6.2 2.656l.007 2.752h5.814v.826H3.9S0 5.789 0 11.969c0 6.18 3.403 5.96 3.403 5.96h2.03V15.05s-.109-3.42 3.35-3.42h5.766s3.24.052 3.24-3.133V3.202S18.28 0 11.913 0zM8.708 1.85c.578 0 1.046.47 1.046 1.052 0 .581-.468 1.051-1.046 1.051-.579 0-1.046-.47-1.046-1.051 0-.582.467-1.052 1.046-1.052z"/>
                                        </svg>
                                        @break
                                    @case('php')
                                        <svg class="w-5 h-5 text-indigo-600 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 5.601c-2.857 0-5.214.805-6.586 2.158C4.041 9.112 3.353 10.7 3.353 12c0 1.3.688 2.888 2.061 4.241C6.786 17.594 9.143 18.399 12 18.399s5.214-.805 6.586-2.158c1.373-1.353 2.061-2.941 2.061-4.241 0-1.3-.688-2.888-2.061-4.241C17.214 6.406 14.857 5.601 12 5.601z"/>
                                        </svg>
                                        @break
                                    @case('node')
                                        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2L2 7v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V7l-10-5z"/>
                                        </svg>
                                        @break
                                @endswitch
                                <span class="text-sm text-gray-900 dark:text-white">{{ ucfirst($script->language) }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <div class="space-y-1">
                                <div>Timeout: {{ $script->timeout }}s</div>
                                @if($script->retry_on_failure)
                                    <div>Retries: {{ $script->max_retries }}</div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button wire:click="toggleScript({{ $script->id }})"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors
                                    {{ $script->enabled ? 'bg-green-600' : 'bg-gray-200' }}">
                                <span class="inline-block h-5 w-5 transform rounded-full bg-white transition-transform
                                    {{ $script->enabled ? 'translate-x-5' : 'translate-x-0.5' }}"></span>
                            </button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="testScript({{ $script->id }})"
                                    class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 mr-3">
                                Test
                            </button>
                            <button wire:click="editScript({{ $script->id }})"
                                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3">
                                Edit
                            </button>
                            <button wire:click="downloadScript({{ $script->id }})"
                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3">
                                Download
                            </button>
                            <button wire:click="deleteScript({{ $script->id }})"
                                    onclick="return confirm('Are you sure?')"
                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                Delete
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                            </svg>
                            <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">No deployment scripts created</p>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-500">Get started by creating a script or using a template</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($scripts->hasPages())
            <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900">
                {{ $scripts->links() }}
            </div>
        @endif
    </div>

    <!-- Create/Edit Script Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" wire:click="$set('showCreateModal', false)"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                    <form wire:submit.prevent="saveScript">
                        <div class="px-6 py-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                {{ $editingScript ? 'Edit Script' : 'Create Deployment Script' }}
                            </h3>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Script Name</label>
                                    <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                                    <select wire:model="type" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <option value="deployment">Deployment</option>
                                        <option value="rollback">Rollback</option>
                                        <option value="maintenance">Maintenance</option>
                                        <option value="backup">Backup</option>
                                        <option value="custom">Custom</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                <input type="text" wire:model="description" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </div>

                            <div class="mt-4 grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Language</label>
                                    <select wire:model="language" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <option value="bash">Bash</option>
                                        <option value="sh">Shell</option>
                                        <option value="python">Python</option>
                                        <option value="php">PHP</option>
                                        <option value="node">Node.js</option>
                                        <option value="ruby">Ruby</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Timeout (seconds)</label>
                                    <input type="number" wire:model="timeout" min="10" max="3600" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </div>

                                <div class="flex items-end">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" wire:model="retryOnFailure" class="rounded border-gray-300 text-green-600">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Retry on failure</span>
                                    </label>
                                </div>
                            </div>

                            @if($retryOnFailure)
                                <div class="mt-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Max Retries</label>
                                    <input type="number" wire:model="maxRetries" min="1" max="10" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </div>
                            @endif

                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Script Content</label>
                                <div class="bg-gray-100 dark:bg-gray-900 rounded p-2 text-xs mb-2">
                                    Available variables: @{{PROJECT_NAME}}, @{{PROJECT_SLUG}}, @{{BRANCH}}, @{{COMMIT_HASH}}, @{{TIMESTAMP}}, @{{DOMAIN}}
                                </div>
                                <textarea wire:model="content" rows="15"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm"
                                    placeholder="#!/bin/bash&#10;&#10;echo 'Starting deployment for @{{PROJECT_NAME}}'&#10;cd @{{PROJECT_PATH}}&#10;git pull origin @{{BRANCH}}"></textarea>
                                @error('content') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-900 px-6 py-3 flex justify-end space-x-3">
                            <button type="button" wire:click="$set('showCreateModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                                {{ $editingScript ? 'Update Script' : 'Create Script' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Templates Modal -->
    @if($showTemplateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" wire:click="$set('showTemplateModal', false)"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-3xl w-full">
                    <div class="px-6 py-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Script Templates</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($templates as $key => $template)
                                <div class="border dark:border-gray-700 rounded-lg p-4 hover:border-green-500 cursor-pointer"
                                     wire:click="useTemplate('{{ $key }}')">
                                    <h4 class="font-medium text-gray-900 dark:text-white">{{ $template['name'] }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $template['description'] }}</p>
                                    <div class="mt-2 flex items-center text-xs text-gray-500">
                                        <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">{{ ucfirst($template['language']) }}</span>
                                        <span class="ml-2">{{ ucfirst($template['type']) }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-900 px-6 py-3 flex justify-end">
                        <button wire:click="$set('showTemplateModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Test Script Modal -->
    @if($showTestModal && $editingScript)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" wire:click="$set('showTestModal', false)"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-3xl w-full">
                    <div class="px-6 py-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            Test Script: {{ $editingScript->name }}
                        </h3>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Select Project</label>
                            <select wire:model="testProject" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Choose a project...</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                            @error('testProject') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        @if($testOutput)
                            <div class="bg-black rounded-lg p-4 text-green-400 font-mono text-sm overflow-x-auto">
                                <pre>{{ $testOutput }}</pre>
                            </div>
                        @endif
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-900 px-6 py-3 flex justify-end space-x-3">
                        <button wire:click="$set('showTestModal', false)" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                            Close
                        </button>
                        <button wire:click="runTest" :disabled="$wire.testRunning" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 disabled:opacity-50">
                            <span wire:loading.remove wire:target="runTest">Run Test</span>
                            <span wire:loading wire:target="runTest">Running...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    </div>
</div>