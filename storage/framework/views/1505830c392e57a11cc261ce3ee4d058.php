<div class="space-y-6">
    <!-- Webhooks Overview -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Webhooks</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
            DevFlow Pro supports webhooks from GitHub and GitLab to automatically trigger deployments when you push code to your repository.
            Webhooks do not require API token authentication but use a unique secret URL for each project.
        </p>

        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 p-6 rounded-lg">
            <h4 class="font-semibold text-blue-900 dark:text-blue-400 mb-3 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                How Webhooks Work
            </h4>
            <ol class="list-decimal list-inside space-y-2 text-gray-700 dark:text-gray-300 text-sm">
                <li>Enable webhooks for your project and get a unique secret URL</li>
                <li>Configure the webhook in your GitHub or GitLab repository settings</li>
                <li>When you push code, the Git provider sends a notification to DevFlow Pro</li>
                <li>DevFlow Pro validates the signature and triggers an automatic deployment</li>
                <li>Track webhook deliveries and deployment status in real-time</li>
            </ol>
        </div>
    </div>

    <!-- GitHub Webhook -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <div class="flex items-center space-x-3 mb-4">
            <svg class="w-8 h-8 text-gray-900 dark:text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
            </svg>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">GitHub Webhook</h3>
        </div>

        <p class="text-gray-600 dark:text-gray-400 mb-4">
            Configure GitHub to automatically deploy when pushing to your repository.
        </p>

        <div class="bg-gray-900 rounded-lg p-4 mb-6">
            <div class="flex justify-between items-center mb-2">
                <span class="text-xs text-gray-400 font-semibold">WEBHOOK URL</span>
                <button onclick="navigator.clipboard.writeText('<?php echo e(url('/webhooks/github/{secret}')); ?>')"
                        class="text-xs text-blue-400 hover:text-blue-300">Copy</button>
            </div>
            <pre class="text-sm text-gray-300"><code>POST <?php echo e(url('/webhooks/github/{secret}')); ?></code></pre>
        </div>

        <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-4 rounded mb-6">
            <p class="text-yellow-900 dark:text-yellow-400 text-sm">
                <strong>Important:</strong> Replace <code class="bg-yellow-200 dark:bg-yellow-800 px-1 rounded">{secret}</code> with your project's unique webhook secret.
                You can find this in your project settings when you enable webhooks.
            </p>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">GitHub Configuration Steps</h4>
        <div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4 mb-6">
            <ol class="list-decimal list-inside space-y-3 text-gray-700 dark:text-gray-300 text-sm">
                <li>Go to your GitHub repository settings</li>
                <li>Navigate to <strong>Webhooks</strong> → <strong>Add webhook</strong></li>
                <li>Enter the Payload URL: <code class="bg-gray-200 dark:bg-gray-800 px-2 py-1 rounded text-xs"><?php echo e(url('/webhooks/github/YOUR_SECRET')); ?></code></li>
                <li>Set Content type to: <code class="bg-gray-200 dark:bg-gray-800 px-2 py-1 rounded text-xs">application/json</code></li>
                <li>Select <strong>Just the push event</strong></li>
                <li>Ensure <strong>Active</strong> is checked</li>
                <li>Click <strong>Add webhook</strong></li>
            </ol>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Webhook Headers</h4>
        <div class="overflow-x-auto mb-6">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900">
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">Header</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr>
                        <td class="px-4 py-2 text-sm font-mono text-blue-600 dark:text-blue-400">X-GitHub-Event</td>
                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">Event type (must be "push" to trigger deployment)</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 text-sm font-mono text-blue-600 dark:text-blue-400">X-Hub-Signature-256</td>
                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">HMAC SHA256 signature for payload verification</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 text-sm font-mono text-blue-600 dark:text-blue-400">X-GitHub-Delivery</td>
                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">Unique delivery ID</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Example Payload (Push Event)</h4>
        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto mb-6">
            <pre class="text-xs text-gray-300"><code>{
  "ref": "refs/heads/main",
  "before": "abc123...",
  "after": "def456...",
  "repository": {
    "id": 123456,
    "name": "my-project",
    "full_name": "user/my-project",
    "clone_url": "https://github.com/user/my-project.git"
  },
  "pusher": {
    "name": "username",
    "email": "user@example.com"
  },
  "sender": {
    "login": "username",
    "id": 12345
  },
  "head_commit": {
    "id": "def456...",
    "message": "Update feature",
    "timestamp": "2025-12-03T10:00:00Z",
    "author": {
      "name": "Developer",
      "email": "dev@example.com"
    }
  }
}</code></pre>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Response Codes</h4>
        <div class="space-y-3">
            <div class="border-l-4 border-green-500 bg-green-50 dark:bg-green-900/20 p-4 rounded">
                <h5 class="font-semibold text-green-900 dark:text-green-400 mb-2">200 OK - Deployment Triggered</h5>
                <div class="bg-gray-900 rounded p-3 text-xs">
                    <pre class="text-gray-300"><code>{
  "message": "Deployment triggered successfully",
  "deployment_id": 42
}</code></pre>
                </div>
            </div>

            <div class="border-l-4 border-blue-500 bg-blue-50 dark:bg-blue-900/20 p-4 rounded">
                <h5 class="font-semibold text-blue-900 dark:text-blue-400 mb-2">200 OK - Event Acknowledged but Not Processed</h5>
                <div class="bg-gray-900 rounded p-3 text-xs">
                    <pre class="text-gray-300"><code>{
  "message": "Event acknowledged but not processed"
}</code></pre>
                </div>
                <p class="text-blue-900 dark:text-blue-400 text-xs mt-2">This happens when the branch doesn't match or event type is not "push"</p>
            </div>

            <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-900/20 p-4 rounded">
                <h5 class="font-semibold text-red-900 dark:text-red-400 mb-2">401 Unauthorized - Invalid Secret or Signature</h5>
                <div class="bg-gray-900 rounded p-3 text-xs">
                    <pre class="text-gray-300"><code>{
  "error": "Invalid webhook secret"
}</code></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- GitLab Webhook -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <div class="flex items-center space-x-3 mb-4">
            <svg class="w-8 h-8 text-orange-600 dark:text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                <path d="m23.6004 9.5927-.0337-.0862L20.3.9814a.851.851 0 0 0-.3362-.405.8748.8748 0 0 0-.9997.0539.8748.8748 0 0 0-.29.4399l-2.2055 6.748H7.5375l-2.2057-6.748a.8573.8573 0 0 0-.29-.4412.8748.8748 0 0 0-.9997-.0537.8585.8585 0 0 0-.3362.4049L.4332 9.5015l-.0325.0862a6.0657 6.0657 0 0 0 2.0119 7.0105l.0113.0087.03.0213 4.976 3.7264 2.462 1.8633 1.4995 1.1321a1.0085 1.0085 0 0 0 1.2197 0l1.4995-1.1321 2.4619-1.8633 5.006-3.7476.0125-.01a6.0682 6.0682 0 0 0 2.0094-7.003z"/>
            </svg>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">GitLab Webhook</h3>
        </div>

        <p class="text-gray-600 dark:text-gray-400 mb-4">
            Configure GitLab to automatically deploy when pushing to your repository.
        </p>

        <div class="bg-gray-900 rounded-lg p-4 mb-6">
            <div class="flex justify-between items-center mb-2">
                <span class="text-xs text-gray-400 font-semibold">WEBHOOK URL</span>
                <button onclick="navigator.clipboard.writeText('<?php echo e(url('/webhooks/gitlab/{secret}')); ?>')"
                        class="text-xs text-blue-400 hover:text-blue-300">Copy</button>
            </div>
            <pre class="text-sm text-gray-300"><code>POST <?php echo e(url('/webhooks/gitlab/{secret}')); ?></code></pre>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">GitLab Configuration Steps</h4>
        <div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4 mb-6">
            <ol class="list-decimal list-inside space-y-3 text-gray-700 dark:text-gray-300 text-sm">
                <li>Go to your GitLab project settings</li>
                <li>Navigate to <strong>Webhooks</strong></li>
                <li>Enter the URL: <code class="bg-gray-200 dark:bg-gray-800 px-2 py-1 rounded text-xs"><?php echo e(url('/webhooks/gitlab/YOUR_SECRET')); ?></code></li>
                <li>Enter your Secret token (your project's webhook secret)</li>
                <li>Select trigger: <strong>Push events</strong></li>
                <li>Leave <strong>Enable SSL verification</strong> checked</li>
                <li>Click <strong>Add webhook</strong></li>
            </ol>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Webhook Headers</h4>
        <div class="overflow-x-auto mb-6">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900">
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">Header</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr>
                        <td class="px-4 py-2 text-sm font-mono text-blue-600 dark:text-blue-400">X-Gitlab-Token</td>
                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">Secret token for authentication</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 text-sm font-mono text-blue-600 dark:text-blue-400">X-Gitlab-Event</td>
                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">Event type (e.g., "Push Hook")</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Example Payload (Push Event)</h4>
        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
            <pre class="text-xs text-gray-300"><code>{
  "object_kind": "push",
  "event_name": "push",
  "before": "abc123...",
  "after": "def456...",
  "ref": "refs/heads/main",
  "checkout_sha": "def456...",
  "user_name": "Developer",
  "user_email": "dev@example.com",
  "project": {
    "id": 123456,
    "name": "my-project",
    "web_url": "https://gitlab.com/user/my-project",
    "git_http_url": "https://gitlab.com/user/my-project.git"
  },
  "commits": [
    {
      "id": "def456...",
      "message": "Update feature",
      "timestamp": "2025-12-03T10:00:00Z",
      "author": {
        "name": "Developer",
        "email": "dev@example.com"
      }
    }
  ]
}</code></pre>
        </div>
    </div>

    <!-- Legacy Webhook (Simpler) -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <div class="flex items-center space-x-3 mb-4">
            <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-lg font-mono text-sm">POST</span>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Simple Deployment Webhook</h3>
        </div>

        <p class="text-gray-600 dark:text-gray-400 mb-4">
            A simpler webhook endpoint that works with any Git provider. Uses project slug as the token.
        </p>

        <div class="bg-gray-900 rounded-lg p-4 mb-6">
            <pre class="text-sm text-gray-300"><code>POST <?php echo e(url('/api/webhooks/deploy/{token}')); ?></code></pre>
        </div>

        <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded mb-6">
            <p class="text-blue-900 dark:text-blue-400 text-sm">
                <strong>Note:</strong> This endpoint requires the project's <code class="bg-blue-200 dark:bg-blue-800 px-1 rounded">auto_deploy</code> setting to be enabled.
                The token can be your project slug.
            </p>
        </div>

        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Supported Providers</h4>
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                <svg class="w-8 h-8 mx-auto mb-2 text-gray-900 dark:text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                </svg>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">GitHub</p>
            </div>
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                <svg class="w-8 h-8 mx-auto mb-2 text-orange-600 dark:text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="m23.6004 9.5927-.0337-.0862L20.3.9814a.851.851 0 0 0-.3362-.405.8748.8748 0 0 0-.9997.0539.8748.8748 0 0 0-.29.4399l-2.2055 6.748H7.5375l-2.2057-6.748a.8573.8573 0 0 0-.29-.4412.8748.8748 0 0 0-.9997-.0537.8585.8585 0 0 0-.3362.4049L.4332 9.5015l-.0325.0862a6.0657 6.0657 0 0 0 2.0119 7.0105l.0113.0087.03.0213 4.976 3.7264 2.462 1.8633 1.4995 1.1321a1.0085 1.0085 0 0 0 1.2197 0l1.4995-1.1321 2.4619-1.8633 5.006-3.7476.0125-.01a6.0682 6.0682 0 0 0 2.0094-7.003z"/>
                </svg>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">GitLab</p>
            </div>
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
                <svg class="w-8 h-8 mx-auto mb-2 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 0C5.37 0 0 5.37 0 12s5.37 12 12 12 12-5.37 12-12S18.63 0 12 0zm0 22.5C6.21 22.5 1.5 17.79 1.5 12S6.21 1.5 12 1.5 22.5 6.21 22.5 12 17.79 22.5 12 22.5zm-.5-14.25v6l5.25 3.15.75-1.23-4.5-2.67V8.25H11.5z"/>
                </svg>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">Bitbucket</p>
            </div>
        </div>
    </div>

    <!-- Webhook Security -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Security Considerations</h3>

        <div class="space-y-4">
            <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4 rounded">
                <h4 class="font-semibold text-green-900 dark:text-green-400 mb-2 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    Signature Verification
                </h4>
                <p class="text-green-900 dark:text-green-400 text-sm">
                    All GitHub webhooks are verified using HMAC SHA256 signature. GitLab webhooks use secret token authentication.
                    Invalid signatures are automatically rejected.
                </p>
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded">
                <h4 class="font-semibold text-blue-900 dark:text-blue-400 mb-2 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    Unique Secrets
                </h4>
                <p class="text-blue-900 dark:text-blue-400 text-sm">
                    Each project has a unique webhook secret. Secrets are automatically generated and cannot be guessed.
                    Regenerate secrets anytime from project settings.
                </p>
            </div>

            <div class="bg-purple-50 dark:bg-purple-900/20 border-l-4 border-purple-500 p-4 rounded">
                <h4 class="font-semibold text-purple-900 dark:text-purple-400 mb-2 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Delivery Logging
                </h4>
                <p class="text-purple-900 dark:text-purple-400 text-sm">
                    All webhook deliveries are logged with status, payload, and response. Review delivery history in your project settings
                    to troubleshoot issues.
                </p>
            </div>

            <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-4 rounded">
                <h4 class="font-semibold text-yellow-900 dark:text-yellow-400 mb-2 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Branch Filtering
                </h4>
                <p class="text-yellow-900 dark:text-yellow-400 text-sm">
                    Webhooks only trigger deployments when pushing to the configured branch. Pushes to other branches are ignored.
                    Configure branch in project settings.
                </p>
            </div>
        </div>
    </div>

    <!-- Testing Webhooks -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Testing Webhooks</h3>

        <p class="text-gray-600 dark:text-gray-400 mb-4">
            Test your webhook configuration using curl:
        </p>

        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto mb-4">
            <button onclick="navigator.clipboard.writeText(this.nextElementSibling.textContent.trim())"
                    class="text-xs text-blue-400 hover:text-blue-300 float-right">Copy</button>
            <pre class="text-xs text-gray-300"><code>curl -X POST <?php echo e(url('/webhooks/github/YOUR_SECRET')); ?> \
  -H "Content-Type: application/json" \
  -H "X-GitHub-Event: push" \
  -H "X-Hub-Signature-256: sha256=YOUR_SIGNATURE" \
  -d '{
    "ref": "refs/heads/main",
    "after": "abc123def456",
    "repository": {
      "name": "test-repo",
      "clone_url": "https://github.com/user/test-repo.git"
    },
    "pusher": {
      "name": "testuser",
      "email": "test@example.com"
    },
    "head_commit": {
      "id": "abc123def456",
      "message": "Test deployment",
      "timestamp": "2025-12-03T10:00:00Z"
    }
  }'</code></pre>
        </div>

        <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded">
            <p class="text-blue-900 dark:text-blue-400 text-sm">
                <strong>Tip:</strong> Most Git providers offer a "Test delivery" or "Redeliver" button in their webhook settings
                that you can use to quickly test your configuration.
            </p>
        </div>
    </div>
</div>
<?php /**PATH /home/roshdy/Work/projects/DEVFLOW_PRO/resources/views/livewire/docs/partials/webhooks.blade.php ENDPATH**/ ?>