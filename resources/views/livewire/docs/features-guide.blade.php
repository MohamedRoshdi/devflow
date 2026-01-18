<div class="space-y-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 rounded-2xl p-8 text-white">
        <div class="max-w-3xl">
            <h1 class="text-3xl font-bold">DevFlow Pro Features Guide</h1>
            <p class="mt-3 text-lg text-white/80">
                Everything you need to deploy, manage, and monitor your applications with confidence.
                Choose a category below to explore features.
            </p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('projects.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-slate-900 rounded-lg text-sm font-semibold hover:bg-white/90 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Create Project
                </a>
                <a href="{{ route('servers.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 text-white rounded-lg text-sm font-semibold hover:bg-white/20 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                    Add Server
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Start Steps -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Start (3 Steps)</h2>
        <div class="grid md:grid-cols-3 gap-4">
            <div class="flex items-start gap-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                <div class="flex-shrink-0 w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">1</div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Add Server</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Connect your VPS via SSH</p>
                    <a href="{{ route('servers.create') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline mt-2 inline-block">Add Server &rarr;</a>
                </div>
            </div>
            <div class="flex items-start gap-4 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                <div class="flex-shrink-0 w-10 h-10 bg-purple-600 text-white rounded-full flex items-center justify-center font-bold">2</div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Create Project</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Add your Git repository</p>
                    <a href="{{ route('projects.create') }}" class="text-sm text-purple-600 dark:text-purple-400 hover:underline mt-2 inline-block">Create Project &rarr;</a>
                </div>
            </div>
            <div class="flex items-start gap-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                <div class="flex-shrink-0 w-10 h-10 bg-green-600 text-white rounded-full flex items-center justify-center font-bold">3</div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Deploy</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Click deploy, watch it go live</p>
                    <a href="{{ route('projects.index') }}" class="text-sm text-green-600 dark:text-green-400 hover:underline mt-2 inline-block">View Projects &rarr;</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature Categories -->
    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Core Features -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Core Features</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Essential capabilities</p>
                </div>
            </div>
            <ul class="space-y-3">
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Project Management</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Create and manage web applications</p>
                    </div>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Server Management</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">SSH connectivity, metrics, health checks</p>
                    </div>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">One-Click Deploy</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Automated deployments with real-time logs</p>
                    </div>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Docker Integration</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Full container management</p>
                    </div>
                </li>
            </ul>
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('projects.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">Explore Projects &rarr;</a>
            </div>
        </div>

        <!-- DevOps Features -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">DevOps & Automation</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">CI/CD & Infrastructure</p>
                </div>
            </div>
            <ul class="space-y-3">
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">CI/CD Pipelines</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">GitHub Actions, GitLab CI integration</p>
                    </div>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Kubernetes</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Deploy to K8s clusters with auto-scaling</p>
                    </div>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Webhooks</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Auto-deploy on git push</p>
                    </div>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Custom Scripts</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Run deployment scripts in multiple languages</p>
                    </div>
                </li>
            </ul>
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('pipelines.index') }}" class="text-sm text-purple-600 dark:text-purple-400 hover:underline">View Pipelines &rarr;</a>
            </div>
        </div>

        <!-- Security Features -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Security</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Server protection</p>
                </div>
            </div>
            <ul class="space-y-3">
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Firewall (UFW)</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Manage firewall rules from dashboard</p>
                    </div>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Fail2ban</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Block brute force attacks</p>
                    </div>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">SSH Hardening</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Disable root login, change port</p>
                    </div>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">SSL Certificates</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Let's Encrypt with auto-renewal</p>
                    </div>
                </li>
            </ul>
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('servers.index') }}" class="text-sm text-red-600 dark:text-red-400 hover:underline">Manage Servers &rarr;</a>
            </div>
        </div>

        <!-- Monitoring Features -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Monitoring & Alerts</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Real-time insights</p>
                </div>
            </div>
            <ul class="space-y-3">
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Server Metrics</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">CPU, RAM, Disk monitoring</p>
                    </div>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Health Checks</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Automated uptime monitoring</p>
                    </div>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Resource Alerts</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Slack/Discord notifications</p>
                    </div>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Log Aggregation</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Centralized log viewing</p>
                    </div>
                </li>
            </ul>
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('health.dashboard') }}" class="text-sm text-green-600 dark:text-green-400 hover:underline">View Health &rarr;</a>
            </div>
        </div>

        <!-- Team Collaboration -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Team Collaboration</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Work together</p>
                </div>
            </div>
            <ul class="space-y-3">
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Teams</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Organize users and resources</p>
                    </div>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Role-Based Access</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Owner, Admin, Member, Viewer</p>
                    </div>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Audit Logs</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Track all actions</p>
                    </div>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">GitHub Integration</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">OAuth repository access</p>
                    </div>
                </li>
            </ul>
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('teams.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Manage Teams &rarr;</a>
            </div>
        </div>

        <!-- Advanced Features -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Advanced Features</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Enterprise capabilities</p>
                </div>
            </div>
            <ul class="space-y-3">
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Multi-Tenant</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">SaaS application management</p>
                    </div>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Database Backups</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Scheduled with S3 storage</p>
                    </div>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">API Access</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">RESTful API for automation</p>
                    </div>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">Server Backups</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Full server backup & restore</p>
                    </div>
                </li>
            </ul>
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('docs.api') }}" class="text-sm text-amber-600 dark:text-amber-400 hover:underline">API Docs &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Who Should Use What -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Feature Recommendations By Role</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-2xl">👨‍💻</span>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Developers</h3>
                </div>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <li>- Project Management</li>
                    <li>- One-Click Deploy</li>
                    <li>- Environment Variables</li>
                    <li>- Deployment Logs</li>
                </ul>
            </div>
            <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-2xl">🔧</span>
                    <h3 class="font-semibold text-gray-900 dark:text-white">DevOps</h3>
                </div>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <li>- CI/CD Pipelines</li>
                    <li>- Kubernetes</li>
                    <li>- Docker Management</li>
                    <li>- Server Monitoring</li>
                </ul>
            </div>
            <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-xl">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-2xl">🔐</span>
                    <h3 class="font-semibold text-gray-900 dark:text-white">SysAdmins</h3>
                </div>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <li>- Security Management</li>
                    <li>- SSL Certificates</li>
                    <li>- Server Backups</li>
                    <li>- Resource Alerts</li>
                </ul>
            </div>
            <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-2xl">🏢</span>
                    <h3 class="font-semibold text-gray-900 dark:text-white">Agencies</h3>
                </div>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <li>- Teams & Roles</li>
                    <li>- Multi-Tenant</li>
                    <li>- Client Projects</li>
                    <li>- Database Backups</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Help Section -->
    <div class="bg-gradient-to-r from-gray-800 to-gray-900 dark:from-gray-900 dark:to-black rounded-2xl p-8 text-white">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <h2 class="text-xl font-semibold">Need Help Getting Started?</h2>
                <p class="mt-2 text-gray-300">Check out our comprehensive documentation or reach out for support.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('docs.api') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 rounded-lg text-sm font-medium hover:bg-white/20 transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                    </svg>
                    API Documentation
                </a>
                <a href="{{ route('settings.preferences') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path>
                    </svg>
                    Setup Preferences
                </a>
            </div>
        </div>
    </div>
</div>
