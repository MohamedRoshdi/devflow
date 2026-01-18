<div class="space-y-6">
    <!-- Projects Overview -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Projects API</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
            Manage your deployment projects through the DevFlow Pro API. All endpoints require authentication with a valid API token with appropriate permissions.
        </p>

        <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded">
            <h4 class="font-semibold text-blue-900 dark:text-blue-400 mb-2">Base URL</h4>
            <code class="text-sm text-gray-700 dark:text-gray-300">{{ url('/api/v1/projects') }}</code>
        </div>
    </div>

    <!-- List Projects -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">List Projects</h3>
            <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 text-sm font-semibold rounded">GET</span>
        </div>

        <p class="text-gray-600 dark:text-gray-400 mb-4">Retrieve a paginated list of all your projects.</p>

        <div class="bg-gray-900 rounded-lg p-4 mb-4">
            <div class="flex justify-between items-center mb-2">
                <span class="text-xs text-gray-400 font-semibold">ENDPOINT</span>
                <button onclick="navigator.clipboard.writeText('{{ url('/api/v1/projects') }}')"
                        class="text-xs text-blue-400 hover:text-blue-300">Copy</button>
            </div>
            <code class="text-sm text-blue-300">GET {{ url('/api/v1/projects') }}</code>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Query Parameters</h4>
        <div class="overflow-x-auto mb-4">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900">
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Parameter</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Type</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr>
                        <td class="px-4 py-2 text-sm"><code class="text-blue-600 dark:text-blue-400">status</code></td>
                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">string</td>
                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">Filter by status (running, stopped, building, error)</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 text-sm"><code class="text-blue-600 dark:text-blue-400">framework</code></td>
                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">string</td>
                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">Filter by framework</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 text-sm"><code class="text-blue-600 dark:text-blue-400">search</code></td>
                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">string</td>
                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">Search by name or slug</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 text-sm"><code class="text-blue-600 dark:text-blue-400">per_page</code></td>
                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">integer</td>
                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">Items per page (max 100, default 15)</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Example Request</h4>
        <div class="bg-gray-900 rounded-lg p-4 mb-4">
            <button onclick="navigator.clipboard.writeText(this.nextElementSibling.textContent)"
                    class="text-xs text-blue-400 hover:text-blue-300 float-right">Copy</button>
            <pre class="text-xs text-gray-300"><code>curl {{ url('/api/v1/projects?status=running&per_page=20') }} \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"</code></pre>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Example Response (200 OK)</h4>
        <div class="bg-gray-900 rounded-lg p-4">
            <pre class="text-xs text-gray-300 overflow-x-auto"><code>{
  "data": [
    {
      "id": 1,
      "name": "My Laravel App",
      "slug": "my-laravel-app",
      "framework": "laravel",
      "status": "running",
      "branch": "main",
      "php_version": "8.4",
      "created_at": "2025-11-28T12:00:00.000000Z",
      "links": {
        "self": "{{ url('/api/v1/projects/my-laravel-app') }}",
        "deploy": "{{ url('/api/v1/projects/my-laravel-app/deploy') }}"
      }
    }
  ],
  "meta": {
    "total": 42,
    "per_page": 20,
    "current_page": 1,
    "last_page": 3
  }
}</code></pre>
        </div>
    </div>

    <!-- Get Project -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Get Project</h3>
            <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 text-sm font-semibold rounded">GET</span>
        </div>

        <div class="bg-gray-900 rounded-lg p-4 mb-4">
            <code class="text-sm text-blue-300">GET {{ url('/api/v1/projects/{slug}') }}</code>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Example Response (200 OK)</h4>
        <div class="bg-gray-900 rounded-lg p-4">
            <pre class="text-xs text-gray-300 overflow-x-auto"><code>{
  "data": {
    "id": 1,
    "name": "My Laravel App",
    "slug": "my-laravel-app",
    "repository_url": "https://github.com/user/repo.git",
    "branch": "main",
    "framework": "laravel",
    "project_type": "single_tenant",
    "status": "running",
    "server": {
      "id": 1,
      "name": "Production Server",
      "ip_address": "192.168.1.100"
    },
    "latest_deployment": {
      "id": 42,
      "status": "success",
      "created_at": "2025-11-28T12:00:00.000000Z"
    }
  }
}</code></pre>
        </div>
    </div>

    <!-- Create Project -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Create Project</h3>
            <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 text-sm font-semibold rounded">POST</span>
        </div>

        <div class="bg-gray-900 rounded-lg p-4 mb-4">
            <code class="text-sm text-blue-300">POST {{ url('/api/v1/projects') }}</code>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Request Body</h4>
        <div class="bg-gray-900 rounded-lg p-4 mb-4">
            <pre class="text-xs text-gray-300 overflow-x-auto"><code>{
  "name": "My New Project",
  "slug": "my-new-project",
  "repository_url": "https://github.com/user/repo.git",
  "branch": "main",
  "framework": "laravel",
  "project_type": "single_tenant",
  "php_version": "8.4",
  "server_id": 1,
  "auto_deploy": true
}</code></pre>
        </div>
    </div>

    <!-- Deploy Project -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Deploy Project</h3>
            <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 text-sm font-semibold rounded">POST</span>
        </div>

        <p class="text-gray-600 dark:text-gray-400 mb-4">Trigger a deployment for a project.</p>

        <div class="bg-gray-900 rounded-lg p-4 mb-4">
            <code class="text-sm text-blue-300">POST {{ url('/api/v1/projects/{slug}/deploy') }}</code>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Example Response (202 Accepted)</h4>
        <div class="bg-gray-900 rounded-lg p-4">
            <pre class="text-xs text-gray-300"><code>{
  "message": "Deployment initiated successfully",
  "data": {
    "deployment_id": 42,
    "status": "pending"
  }
}</code></pre>
        </div>
    </div>

    <!-- Update Project -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Update Project</h3>
            <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 text-sm font-semibold rounded">PUT</span>
        </div>

        <div class="bg-gray-900 rounded-lg p-4 mb-4">
            <code class="text-sm text-blue-300">PUT {{ url('/api/v1/projects/{slug}') }}</code>
        </div>
    </div>

    <!-- Delete Project -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Delete Project</h3>
            <span class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 text-sm font-semibold rounded">DELETE</span>
        </div>

        <div class="bg-gray-900 rounded-lg p-4 mb-4">
            <code class="text-sm text-blue-300">DELETE {{ url('/api/v1/projects/{slug}') }}</code>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Example Response (204 No Content)</h4>
        <div class="bg-gray-900 rounded-lg p-4">
            <pre class="text-xs text-gray-300"><code>{
  "message": "Project deleted successfully"
}</code></pre>
        </div>
    </div>
</div>
