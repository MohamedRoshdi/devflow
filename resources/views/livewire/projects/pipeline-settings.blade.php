<div>
    @if (session()->has('message'))
        <div class="mb-6 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-400 px-4 py-3 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-400 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <!-- Pipeline CI/CD Toggle -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-6 mb-6 transition-colors">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Advanced Pipeline CI/CD
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Configure advanced deployment rules with branch filtering and commit message patterns
                </p>
            </div>
            <div>
                <button
                    wire:click="toggleEnabled"
                    type="button"
                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors
                        {{ $enabled ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700' }}">
                    <span class="sr-only">Enable pipeline CI/CD</span>
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform
                        {{ $enabled ? 'translate-x-6' : 'translate-x-1' }}">
                    </span>
                </button>
            </div>
        </div>
    </div>

    @if($enabled)
    <!-- Auto-Deploy Branches -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-6 mb-6 transition-colors">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
            </svg>
            Auto-Deploy Branches
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Only pushes to these branches will trigger deployments. Leave empty to deploy from any branch.
        </p>

        <div class="flex items-center space-x-2 mb-4">
            <input
                type="text"
                wire:model="newBranch"
                wire:keydown.enter="addBranch"
                placeholder="e.g., main, production, staging"
                class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 dark:focus:ring-blue-800 focus:ring-opacity-50">
            <button
                wire:click="addBranch"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </button>
        </div>

        @if(count($auto_deploy_branches) > 0)
        <div class="space-y-2">
            @foreach($auto_deploy_branches as $index => $branch)
            <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 px-4 py-2 rounded-lg">
                <span class="text-gray-900 dark:text-white font-mono text-sm">{{ $branch }}</span>
                <button
                    wire:click="removeBranch({{ $index }})"
                    class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">No branches configured. Deployments will trigger from any branch.</p>
        </div>
        @endif
    </div>

    <!-- Skip Patterns -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-6 mb-6 transition-colors">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
            </svg>
            Skip Deployment Patterns
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Commits with messages containing these patterns will NOT trigger deployments (highest priority).
        </p>

        <div class="flex items-center space-x-2 mb-4">
            <input
                type="text"
                wire:model="newSkipPattern"
                wire:keydown.enter="addSkipPattern"
                placeholder="e.g., [skip ci], [no deploy], WIP"
                class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 dark:focus:ring-blue-800 focus:ring-opacity-50">
            <button
                wire:click="addSkipPattern"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </button>
        </div>

        @if(count($skip_patterns) > 0)
        <div class="space-y-2">
            @foreach($skip_patterns as $index => $pattern)
            <div class="flex items-center justify-between bg-red-50 dark:bg-red-900/20 px-4 py-2 rounded-lg">
                <span class="text-gray-900 dark:text-white font-mono text-sm">{{ $pattern }}</span>
                <button
                    wire:click="removeSkipPattern({{ $index }})"
                    class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">No skip patterns configured.</p>
        </div>
        @endif
    </div>

    <!-- Deploy Patterns -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-6 mb-6 transition-colors">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Force Deploy Patterns
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            Commits with messages containing these patterns will ALWAYS trigger deployments (unless skip patterns match).
        </p>

        <div class="flex items-center space-x-2 mb-4">
            <input
                type="text"
                wire:model="newDeployPattern"
                wire:keydown.enter="addDeployPattern"
                placeholder="e.g., [deploy], [force deploy], HOTFIX"
                class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 dark:focus:ring-blue-800 focus:ring-opacity-50">
            <button
                wire:click="addDeployPattern"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </button>
        </div>

        @if(count($deploy_patterns) > 0)
        <div class="space-y-2">
            @foreach($deploy_patterns as $index => $pattern)
            <div class="flex items-center justify-between bg-green-50 dark:bg-green-900/20 px-4 py-2 rounded-lg">
                <span class="text-gray-900 dark:text-white font-mono text-sm">{{ $pattern }}</span>
                <button
                    wire:click="removeDeployPattern({{ $index }})"
                    class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">No deploy patterns configured.</p>
        </div>
        @endif
    </div>

    <!-- Webhook Configuration -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-6 mb-6 transition-colors">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Webhook Configuration</h3>

        @if($webhook_secret)
        <!-- GitHub Webhook URL -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                GitHub Webhook URL
            </label>
            <div class="flex items-center space-x-2">
                <input
                    type="text"
                    value="{{ $githubWebhookUrl }}"
                    readonly
                    class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 dark:focus:ring-blue-800 focus:ring-opacity-50">
                <button
                    onclick="navigator.clipboard.writeText('{{ $githubWebhookUrl }}'); alert('Copied to clipboard!')"
                    class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- GitLab Webhook URL -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                GitLab Webhook URL
            </label>
            <div class="flex items-center space-x-2">
                <input
                    type="text"
                    value="{{ $gitlabWebhookUrl }}"
                    readonly
                    class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 dark:focus:ring-blue-800 focus:ring-opacity-50">
                <button
                    onclick="navigator.clipboard.writeText('{{ $gitlabWebhookUrl }}'); alert('Copied to clipboard!')"
                    class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Webhook Secret -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Webhook Secret (for GitHub) / Token (for GitLab)
            </label>
            <div class="flex items-center space-x-2">
                <input
                    type="{{ $showSecret ? 'text' : 'password' }}"
                    value="{{ $webhook_secret }}"
                    readonly
                    class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 dark:focus:ring-blue-800 focus:ring-opacity-50">
                <button
                    wire:click="toggleSecretVisibility"
                    class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if($showSecret)
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        @endif
                    </svg>
                </button>
                <button
                    onclick="navigator.clipboard.writeText('{{ $webhook_secret }}'); alert('Secret copied to clipboard!')"
                    class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Regenerate Secret Button -->
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Need to regenerate your webhook secret?
            </p>
            <button
                wire:click="confirmRegenerate"
                class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition-colors">
                Regenerate Secret
            </button>
        </div>
        @else
        <!-- Generate Webhook Secret -->
        <div class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                No webhook secret configured yet. Generate one to enable webhooks.
            </p>
            <button
                wire:click="generateWebhookSecret"
                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                Generate Webhook Secret
            </button>
        </div>
        @endif

        <!-- Regenerate Confirmation Modal -->
        @if($showRegenerateConfirm)
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    Regenerate Webhook Secret?
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    This will invalidate the current webhook secret. You'll need to update the secret in your Git provider's webhook settings.
                </p>
                <div class="flex space-x-3">
                    <button
                        wire:click="generateWebhookSecret"
                        class="flex-1 px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition-colors">
                        Yes, Regenerate
                    </button>
                    <button
                        wire:click="cancelRegenerate"
                        class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Setup Instructions -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-gray-900/50 p-6 transition-colors">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Setup Instructions</h3>

        <!-- GitHub Setup -->
        <div class="mb-6">
            <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/>
                </svg>
                GitHub
            </h4>
            <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600 dark:text-gray-400">
                <li>Go to your repository on GitHub</li>
                <li>Click on <strong>Settings</strong> → <strong>Webhooks</strong> → <strong>Add webhook</strong></li>
                <li>Paste the GitHub Webhook URL in the <strong>Payload URL</strong> field</li>
                <li>Set <strong>Content type</strong> to <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">application/json</code></li>
                <li>Paste the Webhook Secret in the <strong>Secret</strong> field</li>
                <li>Select <strong>Just the push event</strong></li>
                <li>Ensure <strong>Active</strong> is checked and click <strong>Add webhook</strong></li>
            </ol>
        </div>

        <!-- GitLab Setup -->
        <div>
            <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M23.955 13.587l-1.342-4.135-2.664-8.189a.455.455 0 00-.867 0L16.418 9.45H7.582L4.919 1.263a.455.455 0 00-.867 0L1.388 9.452.046 13.587a.924.924 0 00.331 1.023L12 23.054l11.623-8.443a.92.92 0 00.332-1.024"/>
                </svg>
                GitLab
            </h4>
            <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600 dark:text-gray-400">
                <li>Go to your project on GitLab</li>
                <li>Click on <strong>Settings</strong> → <strong>Webhooks</strong></li>
                <li>Paste the GitLab Webhook URL in the <strong>URL</strong> field</li>
                <li>Paste the Webhook Secret in the <strong>Secret Token</strong> field</li>
                <li>Check only the <strong>Push events</strong> trigger</li>
                <li>Click <strong>Add webhook</strong></li>
            </ol>
        </div>
    </div>
    @endif
</div>
