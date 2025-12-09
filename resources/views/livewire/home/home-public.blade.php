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
                        <p class="text-lg font-bold text-gray-900 dark:text-white">NileStack</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <span class="font-medium text-blue-600 dark:text-blue-400">DevFlow Pro</span> Platform
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

        <!-- Who Is It For -->
        <section class="py-24 bg-white dark:bg-slate-900">
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
