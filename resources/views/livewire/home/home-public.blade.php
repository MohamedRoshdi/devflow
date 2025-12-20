<div class="min-h-screen bg-gray-50 dark:bg-gray-950 flex flex-col">
    <!-- Navigation -->
    <nav class="fixed top-0 inset-x-0 z-50">
        <div class="mx-auto w-full max-w-[1560px] px-6 md:px-10 lg:px-16">
            <div class="flex h-20 items-center justify-between rounded-full bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl shadow-sm shadow-blue-500/5 mt-6 px-6">
                <div class="flex items-center space-x-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-600 shadow-lg shadow-blue-500/30">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12h4l3-3 4 6 3-3h4"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">DevFlow Pro</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <span class="font-medium text-blue-600 dark:text-blue-400">Deployment</span> Platform
                        </p>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <a href="#platform" class="hidden md:inline-flex px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                        Platform
                    </a>
                    <a href="#workflow" class="hidden md:inline-flex px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                        Workflow
                    </a>
                    @auth
                    <a href="{{ route('docs.features') }}" class="hidden md:inline-flex px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors">
                        Features Guide
                    </a>
                    @endauth

                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded-full transition-colors">
                            Open Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded-full transition-colors">
                            Sign In
                        </a>
                    @endauth

                    <!-- Theme Toggle -->
                    <button id="theme-toggle" class="hidden sm:inline-flex items-center justify-center p-2 rounded-full border border-gray-200/70 text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition-colors" aria-label="Toggle theme">
                        <svg id="theme-toggle-light-icon" class="w-5 h-5 text-gray-600 dark:text-gray-300 hidden dark:block" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path>
                        </svg>
                        <svg id="theme-toggle-dark-icon" class="w-5 h-5 text-gray-600 dark:text-gray-300 block dark:hidden" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-1 mt-28 sm:mt-32">
        <!-- Hero -->
        <section class="relative overflow-hidden pt-40 pb-28">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-slate-800 to-blue-900 dark:from-slate-950 dark:via-slate-900 dark:to-blue-950"></div>
            <div class="absolute -top-40 -right-36 h-96 w-96 rounded-full bg-blue-600/40 blur-3xl"></div>
            <div class="absolute -bottom-40 -left-24 h-96 w-96 rounded-full bg-purple-500/40 blur-3xl"></div>

            <div class="relative z-10 mx-auto w-full max-w-[1560px] px-6 md:px-10 lg:px-16">
                <div class="grid gap-14 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                    <div>
                        <div class="inline-flex items-center space-x-3 rounded-full bg-white/10 px-5 py-2 text-sm font-medium text-white/80 ring-1 ring-white/20 backdrop-blur">
                            <span class="flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-300"></span>
                            </span>
                            <span>Platform Status: Operational</span>
                        </div>

                        <h1 class="mt-8 text-4xl font-semibold tracking-tight text-white sm:text-5xl lg:text-6xl xl:text-7xl">
                            Deploy production apps in minutes, not days.
                        </h1>
                        <p class="mt-6 text-lg text-slate-200 sm:text-xl lg:max-w-xl">
                            DevFlow Pro orchestrates provisioning, deployments, and uptime monitoring so teams can focus on shipping value. Explore the live experiences backed by our automated infrastructure.
                        </p>

                        <div class="mt-10 flex flex-wrap gap-4">
                            @auth
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-semibold text-slate-900 shadow-xl shadow-slate-900/20 transition hover:-translate-y-0.5 hover:shadow-2xl">
                                    Launch Control Center
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                    </svg>
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-semibold text-slate-900 shadow-xl shadow-slate-900/20 transition hover:-translate-y-0.5 hover:shadow-2xl">
                                    Request Access
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                    </svg>
                                </a>
                            @endauth
                        </div>

                        <dl class="mt-12 grid gap-8 sm:grid-cols-3">
                            <div>
                                <dt class="text-sm font-medium text-white/60">Platform Uptime</dt>
                                <dd class="mt-2 text-3xl font-semibold text-white">99.9%</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-white/60">Deploy Time</dt>
                                <dd class="mt-2 text-3xl font-semibold text-white">&lt;5 min</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-white/60">Support</dt>
                                <dd class="mt-2 text-3xl font-semibold text-white">24/7</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="lg:pl-12">
                        <div class="relative rounded-3xl border border-white/10 bg-white/5 p-8 shadow-2xl shadow-blue-900/40 backdrop-blur">
                            <div class="absolute -top-10 -right-10 h-32 w-32 rounded-full bg-blue-500/30 blur-2xl"></div>
                            <div class="absolute -bottom-14 -left-16 h-40 w-40 rounded-full bg-purple-500/30 blur-3xl"></div>
                            <div class="relative space-y-8">
                                <div>
                                    <p class="text-xs uppercase tracking-wider text-white/60">Deployment Insights</p>
                                    <p class="mt-2 text-3xl font-semibold text-white">Real-time orchestration</p>
                                </div>
                                <div class="grid gap-6 rounded-2xl bg-black/20 p-6 ring-1 ring-white/10">
                                    <div class="flex items-center justify-between text-white">
                                        <span class="text-sm font-medium text-white/70">Environment Sync</span>
                                        <span class="rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-semibold text-emerald-200">Healthy</span>
                                    </div>
                                    <div>
                                        <p class="text-4xl font-semibold text-white">12m</p>
                                        <p class="text-sm text-white/60">Average deployment time</p>
                                    </div>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="rounded-2xl bg-white/10 p-4 text-white">
                                        <p class="text-sm font-medium text-white/70">Cache & Config</p>
                                        <p class="mt-2 text-2xl font-semibold">Auto-optimized</p>
                                        <p class="mt-1 text-xs text-white/50">Laravel optimize suite runs on every deploy.</p>
                                    </div>
                                    <div class="rounded-2xl bg-white/10 p-4 text-white">
                                        <p class="text-sm font-medium text-white/70">Security</p>
                                        <p class="mt-2 text-2xl font-semibold">Keys Managed</p>
                                        <p class="mt-1 text-xs text-white/50">APP_KEY, APP_ENV, and secrets injected securely.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Platform Highlights -->
        <section id="platform" class="py-24 bg-slate-50 dark:bg-slate-900/50">
            <div class="mx-auto w-full max-w-[1560px] px-6 md:px-10 lg:px-16">
                <!-- Section Header -->
                <div class="mx-auto max-w-3xl text-center mb-16">
                    <h2 class="text-3xl font-semibold tracking-tight text-slate-900 dark:text-white sm:text-4xl">Platform Features</h2>
                    <p class="mt-4 text-lg text-slate-600 dark:text-slate-400">
                        Everything you need to deploy, manage, and monitor your applications with confidence.
                    </p>
                </div>

                <!-- Features Grid -->
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <!-- Feature 1: Infrastructure -->
                    <div class="rounded-3xl bg-white shadow-xl shadow-slate-200/70 p-8 dark:bg-slate-800 dark:shadow-none dark:ring-1 dark:ring-white/5 transition hover:shadow-2xl hover:-translate-y-1">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-lg shadow-blue-500/25">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                            </svg>
                        </div>
                        <h3 class="mt-6 text-xl font-semibold text-slate-900 dark:text-white">Server Management</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400">
                            Connect unlimited servers via SSH. Monitor CPU, memory, disk usage in real-time with automated health checks and alerts.
                        </p>
                    </div>

                    <!-- Feature 2: Deployments -->
                    <div class="rounded-3xl bg-white shadow-xl shadow-slate-200/70 p-8 dark:bg-slate-800 dark:shadow-none dark:ring-1 dark:ring-white/5 transition hover:shadow-2xl hover:-translate-y-1">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-purple-500 to-purple-600 text-white shadow-lg shadow-purple-500/25">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </div>
                        <h3 class="mt-6 text-xl font-semibold text-slate-900 dark:text-white">One-Click Deploys</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400">
                            Git-based deployments with automatic dependency installs, migrations, cache clearing, and queue restarts. Instant rollback support.
                        </p>
                    </div>

                    <!-- Feature 3: SSL & Domains -->
                    <div class="rounded-3xl bg-white shadow-xl shadow-slate-200/70 p-8 dark:bg-slate-800 dark:shadow-none dark:ring-1 dark:ring-white/5 transition hover:shadow-2xl hover:-translate-y-1">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 text-white shadow-lg shadow-emerald-500/25">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <h3 class="mt-6 text-xl font-semibold text-slate-900 dark:text-white">SSL & Security</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400">
                            Automatic SSL certificate provisioning and renewal. Firewall management, IP blocking, and security audit logging built-in.
                        </p>
                    </div>

                    <!-- Feature 4: Docker -->
                    <div class="rounded-3xl bg-white shadow-xl shadow-slate-200/70 p-8 dark:bg-slate-800 dark:shadow-none dark:ring-1 dark:ring-white/5 transition hover:shadow-2xl hover:-translate-y-1">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-500 to-cyan-600 text-white shadow-lg shadow-cyan-500/25">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                        <h3 class="mt-6 text-xl font-semibold text-slate-900 dark:text-white">Docker Integration</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400">
                            Full Docker and Docker Compose management. Start, stop, restart containers. View logs and manage images directly from the dashboard.
                        </p>
                    </div>

                    <!-- Feature 5: Monitoring -->
                    <div class="rounded-3xl bg-white shadow-xl shadow-slate-200/70 p-8 dark:bg-slate-800 dark:shadow-none dark:ring-1 dark:ring-white/5 transition hover:shadow-2xl hover:-translate-y-1">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 text-white shadow-lg shadow-amber-500/25">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h3 class="mt-6 text-xl font-semibold text-slate-900 dark:text-white">Real-time Monitoring</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400">
                            Live metrics dashboard with CPU, memory, and disk tracking. WebSocket-powered updates keep you informed without refreshing.
                        </p>
                    </div>

                    <!-- Feature 6: Backups -->
                    <div class="rounded-3xl bg-white shadow-xl shadow-slate-200/70 p-8 dark:bg-slate-800 dark:shadow-none dark:ring-1 dark:ring-white/5 transition hover:shadow-2xl hover:-translate-y-1">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-rose-500 to-rose-600 text-white shadow-lg shadow-rose-500/25">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                            </svg>
                        </div>
                        <h3 class="mt-6 text-xl font-semibold text-slate-900 dark:text-white">Database Backups</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400">
                            Scheduled database backups with retention policies. One-click restore and download. Multiple storage destinations supported.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- All Features -->
        <section id="features" class="py-24 bg-white dark:bg-slate-900">
            <div class="mx-auto w-full max-w-[1560px] px-6 md:px-10 lg:px-16">
                <div class="mx-auto max-w-3xl text-center mb-16">
                    <h2 class="text-3xl font-semibold tracking-tight text-slate-900 dark:text-white sm:text-4xl">Complete Feature Set</h2>
                    <p class="mt-4 text-lg text-slate-600 dark:text-slate-400">
                        Everything you need for modern DevOps - from deployment to monitoring to security.
                    </p>
                </div>

                <!-- Feature Categories -->
                <div class="space-y-12">
                    <!-- Core Features -->
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-blue-500 text-white flex items-center justify-center text-sm">1</span>
                            Core Infrastructure
                        </h3>
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                                    <span class="font-medium text-slate-900 dark:text-white">Server Management</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">SSH connection, metrics, health checks</p>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    <span class="font-medium text-slate-900 dark:text-white">Project Management</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Laravel, Symfony, WordPress support</p>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    <span class="font-medium text-slate-900 dark:text-white">One-Click Deploy</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Git-based with instant rollback</p>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                    <span class="font-medium text-slate-900 dark:text-white">Domain Management</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Multiple domains per project</p>
                            </div>
                        </div>
                    </div>

                    <!-- DevOps & CI/CD -->
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-purple-500 text-white flex items-center justify-center text-sm">2</span>
                            DevOps & CI/CD
                        </h3>
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <span class="font-medium text-slate-900 dark:text-white">CI/CD Pipelines</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Custom build stages and scripts</p>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    <span class="font-medium text-slate-900 dark:text-white">Docker Management</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Containers, compose, images</p>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                                    <span class="font-medium text-slate-900 dark:text-white">Kubernetes</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Cluster, pods, deployments</p>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                                    <span class="font-medium text-slate-900 dark:text-white">Deployment Scripts</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Custom pre/post deploy hooks</p>
                            </div>
                        </div>
                    </div>

                    <!-- Security -->
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-red-500 text-white flex items-center justify-center text-sm">3</span>
                            Security & SSL
                        </h3>
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    <span class="font-medium text-slate-900 dark:text-white">SSL Certificates</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Auto-provision and renewal</p>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/></svg>
                                    <span class="font-medium text-slate-900 dark:text-white">Firewall (UFW)</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">IP rules, port management</p>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    <span class="font-medium text-slate-900 dark:text-white">Fail2ban</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Intrusion prevention system</p>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                    <span class="font-medium text-slate-900 dark:text-white">SSH Keys</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Key management, rotation</p>
                            </div>
                        </div>
                    </div>

                    <!-- Monitoring & Logs -->
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center text-sm">4</span>
                            Monitoring & Logs
                        </h3>
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                    <span class="font-medium text-slate-900 dark:text-white">Real-time Metrics</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">CPU, memory, disk, network</p>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span class="font-medium text-slate-900 dark:text-white">Log Aggregation</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Centralized log viewer</p>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                    <span class="font-medium text-slate-900 dark:text-white">Health Checks</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">HTTP endpoints, uptime</p>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span class="font-medium text-slate-900 dark:text-white">Resource Alerts</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Threshold notifications</p>
                            </div>
                        </div>
                    </div>

                    <!-- Integrations & Notifications -->
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-green-500 text-white flex items-center justify-center text-sm">5</span>
                            Integrations & Notifications
                        </h3>
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-slate-900 dark:text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                                    <span class="font-medium text-slate-900 dark:text-white">GitHub</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">OAuth, webhooks, repo sync</p>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-purple-500" fill="currentColor" viewBox="0 0 24 24"><path d="M14.82 4.26a10.14 10.14 0 0 0-.53 1.1 14.66 14.66 0 0 0-4.58 0 10.14 10.14 0 0 0-.53-1.1 16 16 0 0 0-4.13 1.3 17.33 17.33 0 0 0-3 11.59 16.6 16.6 0 0 0 5.07 2.59A12.89 12.89 0 0 0 8.23 18a9.65 9.65 0 0 1-1.71-.83 3.39 3.39 0 0 0 .42-.33 11.66 11.66 0 0 0 10.12 0q.21.18.42.33a10.84 10.84 0 0 1-1.71.84 12.41 12.41 0 0 0 1.08 1.78 16.44 16.44 0 0 0 5.06-2.59 17.22 17.22 0 0 0-3-11.59 16.09 16.09 0 0 0-4.09-1.35zM8.68 14.81a1.94 1.94 0 0 1-1.8-2 1.93 1.93 0 0 1 1.8-2 1.93 1.93 0 0 1 1.8 2 1.93 1.93 0 0 1-1.8 2zm6.64 0a1.94 1.94 0 0 1-1.8-2 1.93 1.93 0 0 1 1.8-2 1.92 1.92 0 0 1 1.8 2 1.92 1.92 0 0 1-1.8 2z"/></svg>
                                    <span class="font-medium text-slate-900 dark:text-white">Discord</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Webhook notifications</p>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-pink-500" fill="currentColor" viewBox="0 0 24 24"><path d="M5.042 15.165a2.528 2.528 0 0 1-2.52 2.523A2.528 2.528 0 0 1 0 15.165a2.527 2.527 0 0 1 2.522-2.52h2.52v2.52zM6.313 15.165a2.527 2.527 0 0 1 2.521-2.52 2.527 2.527 0 0 1 2.521 2.52v6.313A2.528 2.528 0 0 1 8.834 24a2.528 2.528 0 0 1-2.521-2.522v-6.313zM8.834 5.042a2.528 2.528 0 0 1-2.521-2.52A2.528 2.528 0 0 1 8.834 0a2.528 2.528 0 0 1 2.521 2.522v2.52H8.834zM8.834 6.313a2.528 2.528 0 0 1 2.521 2.521 2.528 2.528 0 0 1-2.521 2.521H2.522A2.528 2.528 0 0 1 0 8.834a2.528 2.528 0 0 1 2.522-2.521h6.312zM18.956 8.834a2.528 2.528 0 0 1 2.522-2.521A2.528 2.528 0 0 1 24 8.834a2.528 2.528 0 0 1-2.522 2.521h-2.522V8.834zM17.688 8.834a2.528 2.528 0 0 1-2.523 2.521 2.527 2.527 0 0 1-2.52-2.521V2.522A2.527 2.527 0 0 1 15.165 0a2.528 2.528 0 0 1 2.523 2.522v6.312zM15.165 18.956a2.528 2.528 0 0 1 2.523 2.522A2.528 2.528 0 0 1 15.165 24a2.527 2.527 0 0 1-2.52-2.522v-2.522h2.52zM15.165 17.688a2.527 2.527 0 0 1-2.52-2.523 2.526 2.526 0 0 1 2.52-2.52h6.313A2.527 2.527 0 0 1 24 15.165a2.528 2.528 0 0 1-2.522 2.523h-6.313z"/></svg>
                                    <span class="font-medium text-slate-900 dark:text-white">Slack</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Deployment alerts</p>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                    <span class="font-medium text-slate-900 dark:text-white">Webhooks</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">GitLab, custom endpoints</p>
                            </div>
                        </div>
                    </div>

                    <!-- Team & Admin -->
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-slate-700 text-white flex items-center justify-center text-sm">6</span>
                            Team & Administration
                        </h3>
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    <span class="font-medium text-slate-900 dark:text-white">Team Management</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Invite members, roles</p>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    <span class="font-medium text-slate-900 dark:text-white">Multi-Tenant</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">SaaS tenant management</p>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
                                    <span class="font-medium text-slate-900 dark:text-white">Database Backups</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Scheduled, one-click restore</p>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-3 mb-2">
                                    <svg class="w-5 h-5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span class="font-medium text-slate-900 dark:text-white">API Access</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">REST API, tokens</p>
                            </div>
                        </div>
                    </div>
                </div>

                @auth
                <div class="mt-16 text-center">
                    <a href="{{ route('docs.features') }}" class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-full text-sm font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg shadow-blue-500/25">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        View Complete Documentation
                    </a>
                </div>
                @endauth
            </div>
        </section>

        <!-- Who Is It For -->
        <section class="py-24 bg-slate-50 dark:bg-slate-800/50">
            <div class="mx-auto w-full max-w-[1560px] px-6 md:px-10 lg:px-16">
                <div class="mx-auto max-w-3xl text-center mb-16">
                    <h2 class="text-3xl font-semibold tracking-tight text-slate-900 dark:text-white sm:text-4xl">Who is DevFlow Pro For?</h2>
                    <p class="mt-4 text-lg text-slate-600 dark:text-slate-400">
                        Whether you're a developer, DevOps engineer, or agency managing multiple clients, DevFlow Pro simplifies your workflow.
                    </p>
                </div>

                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Developers -->
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 p-6 hover:border-blue-500 dark:hover:border-blue-400 transition-colors">
                        <div class="text-4xl mb-4">👨‍💻</div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Developers</h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                            Stop SSH-ing into servers. Deploy with one click, rollback instantly, and focus on writing code.
                        </p>
                        <ul class="mt-4 space-y-2 text-xs text-slate-500 dark:text-slate-400">
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                                One-click deployments
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                                Environment variables
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
                                Real-time logs
                            </li>
                        </ul>
                    </div>

                    <!-- DevOps -->
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 p-6 hover:border-purple-500 dark:hover:border-purple-400 transition-colors">
                        <div class="text-4xl mb-4">🔧</div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">DevOps Engineers</h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                            Full infrastructure control with CI/CD pipelines, Kubernetes support, and automated monitoring.
                        </p>
                        <ul class="mt-4 space-y-2 text-xs text-slate-500 dark:text-slate-400">
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 bg-purple-500 rounded-full"></span>
                                CI/CD pipelines
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 bg-purple-500 rounded-full"></span>
                                Kubernetes integration
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 bg-purple-500 rounded-full"></span>
                                Docker management
                            </li>
                        </ul>
                    </div>

                    <!-- SysAdmins -->
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 p-6 hover:border-red-500 dark:hover:border-red-400 transition-colors">
                        <div class="text-4xl mb-4">🔐</div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">System Administrators</h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                            Security-first approach with firewall management, SSL automation, and comprehensive audit logs.
                        </p>
                        <ul class="mt-4 space-y-2 text-xs text-slate-500 dark:text-slate-400">
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                                Firewall (UFW) control
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                                SSL auto-renewal
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                                Security audits
                            </li>
                        </ul>
                    </div>

                    <!-- Agencies -->
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 p-6 hover:border-green-500 dark:hover:border-green-400 transition-colors">
                        <div class="text-4xl mb-4">🏢</div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Agencies & Freelancers</h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                            Manage unlimited client projects with team access, role-based permissions, and multi-tenant support.
                        </p>
                        <ul class="mt-4 space-y-2 text-xs text-slate-500 dark:text-slate-400">
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                Team collaboration
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                Role-based access
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                Multi-tenant SaaS
                            </li>
                        </ul>
                    </div>
                </div>

                @auth
                <div class="mt-12 text-center">
                    <a href="{{ route('docs.features') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-full text-sm font-semibold hover:bg-slate-800 dark:hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        View Complete Features Guide
                    </a>
                </div>
                @endauth
            </div>
        </section>

        <!-- Workflow -->
        <section id="workflow" class="relative overflow-hidden py-24">
            <div class="absolute inset-0 bg-gradient-to-tr from-blue-600/10 via-purple-600/10 to-emerald-500/10 dark:from-blue-600/10 dark:via-purple-700/10 dark:to-emerald-500/10"></div>
            <div class="relative mx-auto w-full max-w-[1560px] px-6 md:px-10 lg:px-16">
                <div class="mx-auto max-w-3xl text-center">
                    <h2 class="text-3xl font-semibold tracking-tight text-slate-900 dark:text-white sm:text-4xl">A workflow your team already understands</h2>
                    <p class="mt-4 text-lg text-slate-600 dark:text-slate-400">
                        From git push to production-ready infrastructure, every stage is orchestrated with guardrails built in.
                    </p>
                </div>

                <div class="mt-16 grid gap-8 lg:grid-cols-4">
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-blue-600/10 text-blue-600">01</span>
                        <h3 class="mt-6 text-lg font-semibold text-slate-900 dark:text-white">Connect your repo</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400">
                            Secure git integration with permissions handled through deploy keys and protected branches.
                        </p>
                    </div>
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-purple-600/10 text-purple-600">02</span>
                        <h3 class="mt-6 text-lg font-semibold text-slate-900 dark:text-white">Define environments</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400">
                            Toggle between local, development, staging, and production with environment-specific env vars.
                        </p>
                    </div>
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-600/10 text-emerald-600">03</span>
                        <h3 class="mt-6 text-lg font-semibold text-slate-900 dark:text-white">Deploy confidently</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400">
                            Automated build pipeline installs dependencies, runs migrations, links storage, and warms caches.
                        </p>
                    </div>
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-amber-500/10 text-amber-600">04</span>
                        <h3 class="mt-6 text-lg font-semibold text-slate-900 dark:text-white">Monitor & iterate</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400">
                            Livewire dashboards stream logs, deployment status, and post-deploy optimizations in one place.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Call To Action -->
        <section class="py-24">
            <div class="mx-auto w-full max-w-[1440px] overflow-hidden rounded-3xl bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 px-6 py-16 shadow-2xl md:px-12 lg:flex lg:items-center lg:justify-between lg:px-20">
                <div class="max-w-3xl">
                    <p class="text-sm font-semibold uppercase tracking-wider text-white/70">Ready when you are</p>
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight text-white sm:text-4xl">
                        Launch your next deployment with confidence.
                    </h2>
                    <p class="mt-4 text-base text-white/80">
                        Switch environments instantly, inspect logs in real-time, and enforce consistent Laravel optimizations without lifting a finger.
                    </p>
                </div>

                <div class="mt-10 lg:mt-0">
                    @auth
                        <a href="{{ route('projects.index') }}" class="inline-flex items-center justify-center rounded-full bg-white px-8 py-3 text-sm font-semibold text-slate-900 shadow-lg shadow-slate-900/20 transition hover:-translate-y-0.5 hover:shadow-2xl">
                            Manage Projects
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-full bg-white px-8 py-3 text-sm font-semibold text-slate-900 shadow-lg shadow-slate-900/20 transition hover:-translate-y-0.5 hover:shadow-2xl">
                            Request Access
                        </a>
                    @endauth
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200 bg-white py-12 dark:border-slate-800 dark:bg-slate-900">
        <div class="mx-auto w-full max-w-[1560px] px-6 md:px-10 lg:px-16">
            <div class="flex flex-col items-center justify-between gap-6 md:flex-row">
                <!-- NileStack Branding -->
                <div class="flex items-center space-x-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-600">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12h4l3-3 4 6 3-3h4"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">NileStack</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Professional Software Development</p>
                    </div>
                </div>

                <!-- Platform Attribution -->
                <div class="text-center">
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        Powered by <span class="font-semibold text-blue-600 dark:text-blue-400">DevFlow Pro</span>
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-500 mt-1">
                        Multi-Project Deployment & Management
                    </p>
                </div>

                <!-- Copyright -->
                <div class="flex flex-col items-center md:items-end gap-2">
                    <p class="text-xs text-slate-500 dark:text-slate-500">
                        &copy; {{ date('Y') }} NileStack. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>
</div>
