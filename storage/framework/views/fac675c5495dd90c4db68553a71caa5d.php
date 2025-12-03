<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Authentication</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
            DevFlow Pro API uses Bearer token authentication. You need to create an API token from the
            <a href="<?php echo e(route('settings.api-tokens')); ?>" class="text-blue-600 dark:text-blue-400 hover:underline">API Tokens</a> page.
        </p>

        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0 mt-1">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Base URL</h4>
                    <code class="text-sm text-gray-700 dark:text-gray-300"><?php echo e(url('/api/v1')); ?></code>
                </div>
            </div>
        </div>
    </div>

    <!-- How to Authenticate -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">How to Authenticate</h3>

        <p class="text-gray-600 dark:text-gray-400 mb-4">Include your API token in the Authorization header of every request:</p>

        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
            <div class="flex justify-between items-center mb-2">
                <span class="text-xs text-gray-400 font-semibold">HTTP HEADER</span>
                <button onclick="navigator.clipboard.writeText('Authorization: Bearer YOUR_API_TOKEN')"
                        class="text-xs text-blue-400 hover:text-blue-300">Copy</button>
            </div>
            <pre class="text-sm text-gray-300"><code>Authorization: Bearer YOUR_API_TOKEN</code></pre>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-4">
            <div>
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">cURL Example</h4>
                <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                    <button onclick="navigator.clipboard.writeText(this.nextElementSibling.textContent)"
                            class="text-xs text-blue-400 hover:text-blue-300 float-right">Copy</button>
                    <pre class="text-xs text-gray-300"><code>curl <?php echo e(url('/api/v1/projects')); ?> \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"</code></pre>
                </div>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">JavaScript Example</h4>
                <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                    <button onclick="navigator.clipboard.writeText(this.nextElementSibling.textContent)"
                            class="text-xs text-blue-400 hover:text-blue-300 float-right">Copy</button>
                    <pre class="text-xs text-gray-300"><code>fetch('<?php echo e(url('/api/v1/projects')); ?>', {
  headers: {
    'Authorization': 'Bearer YOUR_API_TOKEN',
    'Accept': 'application/json'
  }
});</code></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Token Abilities -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Token Abilities</h3>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
            When creating an API token, you can specify which abilities it has. Available abilities:
        </p>

        <div class="grid grid-cols-2 gap-4">
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Projects</h4>
                <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                    <li><code class="text-blue-600 dark:text-blue-400">projects:read</code> - View projects</li>
                    <li><code class="text-blue-600 dark:text-blue-400">projects:write</code> - Create/update projects</li>
                    <li><code class="text-blue-600 dark:text-blue-400">projects:delete</code> - Delete projects</li>
                    <li><code class="text-blue-600 dark:text-blue-400">projects:deploy</code> - Deploy projects</li>
                </ul>
            </div>

            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Servers</h4>
                <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                    <li><code class="text-blue-600 dark:text-blue-400">servers:read</code> - View servers</li>
                    <li><code class="text-blue-600 dark:text-blue-400">servers:write</code> - Create/update servers</li>
                    <li><code class="text-blue-600 dark:text-blue-400">servers:delete</code> - Delete servers</li>
                </ul>
            </div>

            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Deployments</h4>
                <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                    <li><code class="text-blue-600 dark:text-blue-400">deployments:read</code> - View deployments</li>
                    <li><code class="text-blue-600 dark:text-blue-400">deployments:write</code> - Create deployments</li>
                    <li><code class="text-blue-600 dark:text-blue-400">deployments:rollback</code> - Rollback deployments</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Error Responses -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Authentication Errors</h3>

        <div class="space-y-4">
            <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900/20 p-4">
                <h4 class="font-semibold text-red-900 dark:text-red-400 mb-2">401 Unauthorized - Missing Token</h4>
                <div class="bg-gray-900 rounded p-3 text-xs">
                    <pre class="text-gray-300"><code>{
  "message": "Unauthenticated. Please provide a valid Bearer token.",
  "error": "missing_token"
}</code></pre>
                </div>
            </div>

            <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900/20 p-4">
                <h4 class="font-semibold text-red-900 dark:text-red-400 mb-2">401 Unauthorized - Invalid Token</h4>
                <div class="bg-gray-900 rounded p-3 text-xs">
                    <pre class="text-gray-300"><code>{
  "message": "Invalid or expired token.",
  "error": "invalid_token"
}</code></pre>
                </div>
            </div>

            <div class="border-l-4 border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20 p-4">
                <h4 class="font-semibold text-yellow-900 dark:text-yellow-400 mb-2">403 Forbidden - Insufficient Permissions</h4>
                <div class="bg-gray-900 rounded p-3 text-xs">
                    <pre class="text-gray-300"><code>{
  "message": "This token does not have the required permission.",
  "error": "insufficient_permissions",
  "required_ability": "projects:write"
}</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/livewire/docs/partials/authentication.blade.php ENDPATH**/ ?>