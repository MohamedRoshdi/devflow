<div class="space-y-6">
    <!-- Deployments Overview -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Deployments API</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
            Manage and track deployments for your projects. View deployment history, trigger new deployments, and rollback to previous versions.
        </p>

        <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded">
            <h4 class="font-semibold text-blue-900 dark:text-blue-400 mb-2">Base URL</h4>
            <code class="text-sm text-gray-700 dark:text-gray-300"><?php echo e(url('/api/v1')); ?></code>
        </div>
    </div>

    <!-- List Deployments -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">List Project Deployments</h3>
            <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 text-sm font-semibold rounded">GET</span>
        </div>

        <p class="text-gray-600 dark:text-gray-400 mb-4">Get all deployments for a specific project.</p>

        <div class="bg-gray-900 rounded-lg p-4 mb-4">
            <code class="text-sm text-blue-300">GET <?php echo e(url('/api/v1/projects/{slug}/deployments')); ?></code>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Query Parameters</h4>
        <div class="overflow-x-auto mb-4">
            <table class="min-w-full text-sm">
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr>
                        <td class="px-4 py-2"><code class="text-blue-600 dark:text-blue-400">status</code></td>
                        <td class="px-4 py-2 text-gray-600 dark:text-gray-400">Filter by status (pending, running, success, failed)</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2"><code class="text-blue-600 dark:text-blue-400">branch</code></td>
                        <td class="px-4 py-2 text-gray-600 dark:text-gray-400">Filter by branch name</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2"><code class="text-blue-600 dark:text-blue-400">triggered_by</code></td>
                        <td class="px-4 py-2 text-gray-600 dark:text-gray-400">Filter by trigger (manual, webhook, rollback)</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Example Response (200 OK)</h4>
        <div class="bg-gray-900 rounded-lg p-4">
            <pre class="text-xs text-gray-300 overflow-x-auto"><code>{
  "data": [
    {
      "id": 42,
      "commit_hash": "abc123def456",
      "commit_message": "Fix: Update database schema",
      "branch": "main",
      "status": "success",
      "started_at": "2025-11-28T12:00:00.000000Z",
      "completed_at": "2025-11-28T12:05:30.000000Z",
      "duration_seconds": 330,
      "triggered_by": "manual",
      "project": {
        "id": 1,
        "name": "My Laravel App",
        "slug": "my-laravel-app"
      },
      "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "links": {
        "self": "<?php echo e(url('/api/v1/deployments/42')); ?>",
        "rollback": "<?php echo e(url('/api/v1/deployments/42/rollback')); ?>"
      }
    }
  ],
  "meta": {
    "total": 128,
    "per_page": 15,
    "current_page": 1,
    "last_page": 9
  }
}</code></pre>
        </div>
    </div>

    <!-- Get Deployment -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Get Deployment</h3>
            <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 text-sm font-semibold rounded">GET</span>
        </div>

        <div class="bg-gray-900 rounded-lg p-4 mb-4">
            <code class="text-sm text-blue-300">GET <?php echo e(url('/api/v1/deployments/{id}')); ?></code>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Query Parameters</h4>
        <div class="overflow-x-auto mb-4">
            <table class="min-w-full text-sm">
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr>
                        <td class="px-4 py-2"><code class="text-blue-600 dark:text-blue-400">include_logs</code></td>
                        <td class="px-4 py-2 text-gray-600 dark:text-gray-400">Include output and error logs</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2"><code class="text-blue-600 dark:text-blue-400">include_snapshot</code></td>
                        <td class="px-4 py-2 text-gray-600 dark:text-gray-400">Include environment snapshot</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Example Response (200 OK)</h4>
        <div class="bg-gray-900 rounded-lg p-4">
            <pre class="text-xs text-gray-300 overflow-x-auto"><code>{
  "data": {
    "id": 42,
    "commit_hash": "abc123def456",
    "commit_message": "Fix: Update database schema",
    "branch": "main",
    "status": "success",
    "error_message": null,
    "started_at": "2025-11-28T12:00:00.000000Z",
    "completed_at": "2025-11-28T12:05:30.000000Z",
    "duration_seconds": 330,
    "triggered_by": "manual",
    "created_at": "2025-11-28T12:00:00.000000Z"
  }
}</code></pre>
        </div>
    </div>

    <!-- Create Deployment -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Create Deployment</h3>
            <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 text-sm font-semibold rounded">POST</span>
        </div>

        <p class="text-gray-600 dark:text-gray-400 mb-4">Trigger a new deployment for a project.</p>

        <div class="bg-gray-900 rounded-lg p-4 mb-4">
            <code class="text-sm text-blue-300">POST <?php echo e(url('/api/v1/projects/{slug}/deployments')); ?></code>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Request Body (Optional)</h4>
        <div class="bg-gray-900 rounded-lg p-4 mb-4">
            <pre class="text-xs text-gray-300 overflow-x-auto"><code>{
  "branch": "develop",
  "commit_hash": "abc123",
  "commit_message": "Custom deployment message"
}</code></pre>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Example Response (201 Created)</h4>
        <div class="bg-gray-900 rounded-lg p-4">
            <pre class="text-xs text-gray-300"><code>{
  "message": "Deployment created successfully",
  "data": {
    "id": 43,
    "status": "pending",
    "branch": "develop",
    "started_at": "2025-11-28T13:00:00.000000Z"
  }
}</code></pre>
        </div>
    </div>

    <!-- Rollback Deployment -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Rollback to Deployment</h3>
            <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 text-sm font-semibold rounded">POST</span>
        </div>

        <p class="text-gray-600 dark:text-gray-400 mb-4">
            Rollback to a previous successful deployment. This creates a new deployment that restores the specified version.
        </p>

        <div class="bg-gray-900 rounded-lg p-4 mb-4">
            <code class="text-sm text-blue-300">POST <?php echo e(url('/api/v1/deployments/{id}/rollback')); ?></code>
        </div>

        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-4">
            <p class="text-sm text-yellow-800 dark:text-yellow-400">
                <strong>Note:</strong> You can only rollback to deployments with status "success".
            </p>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Example Response (202 Accepted)</h4>
        <div class="bg-gray-900 rounded-lg p-4">
            <pre class="text-xs text-gray-300"><code>{
  "message": "Rollback deployment initiated successfully",
  "data": {
    "id": 44,
    "status": "pending",
    "triggered_by": "rollback",
    "rollback_of": {
      "id": 42,
      "commit_hash": "abc123def456"
    }
  }
}</code></pre>
        </div>
    </div>

    <!-- Deployment Status -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Deployment Statuses</h3>

        <div class="grid grid-cols-2 gap-4">
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex items-center mb-2">
                    <span class="w-3 h-3 bg-blue-500 rounded-full mr-2"></span>
                    <h4 class="font-semibold text-gray-900 dark:text-white">pending</h4>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Deployment queued, waiting to start</p>
            </div>

            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex items-center mb-2">
                    <span class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></span>
                    <h4 class="font-semibold text-gray-900 dark:text-white">running</h4>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Deployment currently in progress</p>
            </div>

            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex items-center mb-2">
                    <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                    <h4 class="font-semibold text-gray-900 dark:text-white">success</h4>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Deployment completed successfully</p>
            </div>

            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex items-center mb-2">
                    <span class="w-3 h-3 bg-red-500 rounded-full mr-2"></span>
                    <h4 class="font-semibold text-gray-900 dark:text-white">failed</h4>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Deployment failed with errors</p>
            </div>
        </div>
    </div>

    <!-- Error Responses -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Common Error Responses</h3>

        <div class="space-y-4">
            <div class="border-l-4 border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20 p-4">
                <h4 class="font-semibold text-yellow-900 dark:text-yellow-400 mb-2">409 Conflict - Deployment Already Running</h4>
                <div class="bg-gray-900 rounded p-3 text-xs">
                    <pre class="text-gray-300"><code>{
  "message": "A deployment is already in progress for this project",
  "error": "deployment_in_progress"
}</code></pre>
                </div>
            </div>

            <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900/20 p-4">
                <h4 class="font-semibold text-red-900 dark:text-red-400 mb-2">422 Unprocessable - Invalid Rollback Target</h4>
                <div class="bg-gray-900 rounded p-3 text-xs">
                    <pre class="text-gray-300"><code>{
  "message": "Can only rollback to successful deployments",
  "error": "invalid_deployment_status",
  "current_status": "failed"
}</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>
<?php /**PATH /home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/livewire/docs/partials/deployments.blade.php ENDPATH**/ ?>