<div class="space-y-6">
    <!-- List Servers -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">List Servers</h3>
            <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 text-sm font-semibold rounded">GET</span>
        </div>

        <div class="bg-gray-900 rounded-lg p-4 mb-4">
            <code class="text-sm text-blue-300">GET {{ url('/api/v1/servers') }}</code>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Query Parameters</h4>
        <div class="overflow-x-auto mb-4">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr>
                        <td class="px-4 py-2"><code class="text-blue-600 dark:text-blue-400">status</code></td>
                        <td class="px-4 py-2 text-gray-600 dark:text-gray-400">Filter by status (online, offline, maintenance)</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2"><code class="text-blue-600 dark:text-blue-400">search</code></td>
                        <td class="px-4 py-2 text-gray-600 dark:text-gray-400">Search by name, hostname, or IP</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Example Response (200 OK)</h4>
        <div class="bg-gray-900 rounded-lg p-4">
            <pre class="text-xs text-gray-300 overflow-x-auto"><code>{
  "data": [
    {
      "id": 1,
      "name": "Production Server",
      "hostname": "prod.example.com",
      "ip_address": "192.168.1.100",
      "status": "online",
      "cpu_cores": 8,
      "memory_gb": 32,
      "docker_installed": true,
      "projects_count": 5,
      "links": {
        "self": "{{ url('/api/v1/servers/1') }}",
        "metrics": "{{ url('/api/v1/servers/1/metrics') }}"
      }
    }
  ]
}</code></pre>
        </div>
    </div>

    <!-- Get Server -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Get Server</h3>
            <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 text-sm font-semibold rounded">GET</span>
        </div>

        <div class="bg-gray-900 rounded-lg p-4">
            <code class="text-sm text-blue-300">GET {{ url('/api/v1/servers/{id}') }}</code>
        </div>
    </div>

    <!-- Create Server -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Create Server</h3>
            <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 text-sm font-semibold rounded">POST</span>
        </div>

        <div class="bg-gray-900 rounded-lg p-4 mb-4">
            <code class="text-sm text-blue-300">POST {{ url('/api/v1/servers') }}</code>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Request Body</h4>
        <div class="bg-gray-900 rounded-lg p-4">
            <pre class="text-xs text-gray-300 overflow-x-auto"><code>{
  "name": "New Server",
  "hostname": "server.example.com",
  "ip_address": "192.168.1.101",
  "port": 22,
  "username": "root",
  "ssh_key": "-----BEGIN RSA PRIVATE KEY-----\n...",
  "cpu_cores": 8,
  "memory_gb": 32,
  "disk_gb": 500
}</code></pre>
        </div>
    </div>

    <!-- Get Server Metrics -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Get Server Metrics</h3>
            <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 text-sm font-semibold rounded">GET</span>
        </div>

        <p class="text-gray-600 dark:text-gray-400 mb-4">Retrieve historical metrics for a server.</p>

        <div class="bg-gray-900 rounded-lg p-4 mb-4">
            <code class="text-sm text-blue-300">GET {{ url('/api/v1/servers/{id}/metrics') }}</code>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Query Parameters</h4>
        <div class="overflow-x-auto mb-4">
            <table class="min-w-full text-sm">
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr>
                        <td class="px-4 py-2"><code class="text-blue-600 dark:text-blue-400">range</code></td>
                        <td class="px-4 py-2 text-gray-600 dark:text-gray-400">Time range: 1h, 24h, 7d, 30d (default: 1h)</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Example Response (200 OK)</h4>
        <div class="bg-gray-900 rounded-lg p-4">
            <pre class="text-xs text-gray-300 overflow-x-auto"><code>{
  "data": {
    "metrics": [
      {
        "cpu_usage": 45.2,
        "memory_usage": 68.5,
        "disk_usage": 42.1,
        "recorded_at": "2025-11-28T12:00:00.000000Z"
      }
    ],
    "aggregates": {
      "avg_cpu": 45.2,
      "avg_memory": 68.5,
      "max_cpu": 78.3
    },
    "range": "1h",
    "count": 60
  }
}</code></pre>
        </div>
    </div>

    <!-- Update Server -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Update Server</h3>
            <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 text-sm font-semibold rounded">PUT</span>
        </div>

        <div class="bg-gray-900 rounded-lg p-4">
            <code class="text-sm text-blue-300">PUT {{ url('/api/v1/servers/{id}') }}</code>
        </div>
    </div>

    <!-- Delete Server -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Delete Server</h3>
            <span class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 text-sm font-semibold rounded">DELETE</span>
        </div>

        <div class="bg-gray-900 rounded-lg p-4 mb-4">
            <code class="text-sm text-blue-300">DELETE {{ url('/api/v1/servers/{id}') }}</code>
        </div>

        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <p class="text-sm text-yellow-800 dark:text-yellow-400">
                <strong>Note:</strong> Cannot delete a server that has active projects. Remove or reassign projects first.
            </p>
        </div>
    </div>
</div>
