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
                    <a href="{{ route('home') }}" wire:navigate class="hidden md:inline-flex px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                        Portfolio
                    </a>

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

    @if($notFound)
        <!-- 404 Not Found -->
        <main class="flex-1 mt-28 sm:mt-32">
            <section class="relative overflow-hidden py-40">
                <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-slate-800 to-blue-900 dark:from-slate-950 dark:via-slate-900 dark:to-blue-950"></div>
                <div class="absolute -top-40 -right-36 h-96 w-96 rounded-full bg-blue-600/40 blur-3xl"></div>
                <div class="absolute -bottom-40 -left-24 h-96 w-96 rounded-full bg-purple-500/40 blur-3xl"></div>

                <div class="relative z-10 mx-auto w-full max-w-[1560px] px-6 md:px-10 lg:px-16 text-center">
                    <div class="flex h-20 w-20 mx-auto items-center justify-center rounded-3xl bg-white/10 backdrop-blur">
                        <svg class="h-10 w-10 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>

                    <h1 class="mt-8 text-4xl font-semibold tracking-tight text-white sm:text-5xl">
                        Project Not Found
                    </h1>
                    <p class="mt-6 text-lg text-slate-200 max-w-2xl mx-auto">
                        This project doesn't exist or is currently not available for public viewing. It may be in development, maintenance, or not yet deployed.
                    </p>

                    <div class="mt-10">
                        <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-semibold text-slate-900 shadow-xl shadow-slate-900/20 transition hover:-translate-y-0.5 hover:shadow-2xl">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Portfolio
                        </a>
                    </div>
                </div>
            </section>
        </main>
    @else
        @php
            // Security: Only use domain from relationship, never expose server IPs or internal infrastructure
            $primaryDomain = $project->domains->first()?->domain;
            $url = $primaryDomain
                ? (str_starts_with($primaryDomain, 'http')
                    ? preg_replace('/^http:/', 'https:', $primaryDomain)
                    : 'https://' . $primaryDomain)
                : null;
        @endphp

        <!-- Project Detail Content -->
        <main class="flex-1 mt-28 sm:mt-32">
            <!-- Hero Section -->
            <section class="relative overflow-hidden py-40">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-600 dark:from-blue-900 dark:via-indigo-900 dark:to-violet-900"></div>
                <div class="absolute -top-40 -right-36 h-96 w-96 rounded-full bg-white/20 blur-3xl"></div>
                <div class="absolute -bottom-40 -left-24 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>

                <div class="relative z-10 mx-auto w-full max-w-[1560px] px-6 md:px-10 lg:px-16">
                    <!-- Back Link -->
                    <div class="mb-8">
                        <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-2 text-sm font-medium text-white/80 hover:text-white transition-colors">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Portfolio
                        </a>
                    </div>

                    <div class="grid gap-14 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                        <div>
                            <!-- Live Status Badge -->
                            <div class="inline-flex items-center space-x-3 rounded-full bg-white/10 px-5 py-2 text-sm font-medium text-white/80 ring-1 ring-white/20 backdrop-blur">
                                <span class="flex h-2 w-2">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-300"></span>
                                </span>
                                <span>Live & Running</span>
                            </div>

                            <!-- Project Name -->
                            <h1 class="mt-8 text-4xl font-semibold tracking-tight text-white sm:text-5xl lg:text-6xl">
                                {{ $project->name }}
                            </h1>

                            <!-- Framework Badge -->
                            <div class="mt-6 inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white/90 backdrop-blur">
                                @if($project->framework === 'Laravel')
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M23.642 5.43a.364.364 0 01.014.1v5.149c0 .135-.073.26-.189.326l-4.323 2.49v4.934a.378.378 0 01-.188.326L9.93 23.949a.316.316 0 01-.066.027c-.008.002-.016.008-.024.01a.348.348 0 01-.192 0c-.011-.002-.02-.008-.03-.012-.02-.008-.042-.014-.062-.025L.533 18.755a.376.376 0 01-.189-.326V2.974c0-.033.005-.066.014-.098.003-.012.01-.02.014-.032a.369.369 0 01.023-.058c.004-.013.015-.022.023-.033l.033-.045c.012-.01.025-.018.037-.027.014-.012.027-.024.041-.034H.53L5.043.05a.375.375 0 01.375 0L9.93 2.647h.002c.015.01.027.021.04.033l.038.027c.013.014.02.03.033.045.008.011.02.021.025.033.01.02.017.038.024.058.003.011.01.021.013.032.01.031.014.064.014.098v9.652l3.76-2.164V5.527c0-.033.004-.066.013-.098.003-.01.01-.02.013-.032a.487.487 0 01.024-.059c.007-.012.018-.02.025-.033.012-.015.021-.03.033-.043.012-.012.025-.02.037-.028.013-.012.027-.023.04-.032h.001l4.513-2.598a.375.375 0 01.375 0l4.513 2.598c.016.01.029.021.041.033l.038.027c.013.014.02.03.032.045.009.012.02.021.025.033.01.02.017.038.024.058.003.012.01.022.013.032zm-.74 5.032V6.179l-1.578.908-2.182 1.256v4.283zm-4.51 7.75v-4.287l-2.147 1.225-6.126 3.498v4.325zM1.093 3.624v14.588l8.273 4.761v-4.325l-4.322-2.445-.002-.003H5.04c-.014-.01-.025-.021-.04-.031-.011-.01-.024-.018-.035-.027l-.001-.002c-.013-.012-.021-.025-.031-.039-.01-.012-.021-.023-.028-.037h-.002c-.008-.014-.013-.031-.02-.047-.006-.016-.014-.027-.018-.043a.49.49 0 01-.008-.057c-.002-.014-.006-.027-.006-.041V5.789l-2.18-1.257zM5.23.81L1.47 2.974l3.76 2.164 3.758-2.164zm1.956 13.505l2.182-1.256V3.624l-1.58.91-2.182 1.255v9.435zm11.581-10.95l-3.76 2.163 3.76 2.163 3.759-2.164zm-.376 4.978L16.21 7.087 14.63 6.18v4.283l2.182 1.256 1.58.908zm-8.65 9.654l5.514-3.148 2.756-1.572-3.757-2.163-4.323 2.489-3.941 2.27z"/>
                                    </svg>
                                @else
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                    </svg>
                                @endif
                                <span>{{ $project->framework ?? 'Framework' }}</span>
                            </div>

                            <!-- Description (if available from metadata) -->
                            @if($project->metadata && isset($project->metadata['description']))
                                <p class="mt-6 text-lg text-white/80 sm:text-xl lg:max-w-xl">
                                    {{ $project->metadata['description'] }}
                                </p>
                            @endif

                            <!-- Visit Project Button -->
                            @if($url)
                                <div class="mt-10">
                                    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-full bg-white px-8 py-4 text-base font-semibold text-slate-900 shadow-xl shadow-slate-900/20 transition hover:-translate-y-0.5 hover:shadow-2xl">
                                        Visit Live Project
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                        </svg>
                                    </a>
                                </div>
                            @endif
                        </div>

                        <!-- Project Info Card -->
                        <div class="lg:pl-12">
                            <div class="relative rounded-3xl border border-white/10 bg-white/5 p-8 shadow-2xl shadow-blue-900/40 backdrop-blur">
                                <div class="absolute -top-10 -right-10 h-32 w-32 rounded-full bg-white/20 blur-2xl"></div>
                                <div class="absolute -bottom-14 -left-16 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>

                                <div class="relative space-y-6">
                                    <div>
                                        <p class="text-xs uppercase tracking-wider text-white/60">Project Details</p>
                                    </div>

                                    <!-- Domain -->
                                    @if($primaryDomain)
                                        <div class="flex items-start gap-3 rounded-2xl bg-black/20 p-4 ring-1 ring-white/10">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10">
                                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs font-medium text-white/70 mb-1">Domain</p>
                                                <p class="text-sm font-semibold text-white break-all">{{ $primaryDomain }}</p>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Technical Details -->
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        @if($project->php_version)
                                            <div class="rounded-2xl bg-white/10 p-4 text-white">
                                                <div class="flex items-center gap-2 mb-2">
                                                    <svg class="h-4 w-4 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                                    </svg>
                                                    <p class="text-xs font-medium text-white/70">PHP Version</p>
                                                </div>
                                                <p class="text-lg font-semibold">{{ $project->php_version }}</p>
                                            </div>
                                        @endif

                                        @if($project->environment)
                                            <div class="rounded-2xl bg-white/10 p-4 text-white">
                                                <div class="flex items-center gap-2 mb-2">
                                                    <svg class="h-4 w-4 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <p class="text-xs font-medium text-white/70">Environment</p>
                                                </div>
                                                <p class="text-lg font-semibold capitalize">{{ $project->environment }}</p>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Deployed Date -->
                                    @if($project->created_at)
                                        <div class="rounded-2xl bg-white/10 p-4 text-white">
                                            <div class="flex items-center gap-2 mb-2">
                                                <svg class="h-4 w-4 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                <p class="text-xs font-medium text-white/70">First Deployed</p>
                                            </div>
                                            <p class="text-sm font-semibold">{{ $project->created_at->format('F j, Y') }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Additional Info Section -->
            <section class="py-24 bg-white dark:bg-slate-900">
                <div class="mx-auto w-full max-w-[1560px] px-6 md:px-10 lg:px-16">
                    <div class="mx-auto max-w-3xl text-center">
                        <h2 class="text-3xl font-semibold tracking-tight text-slate-900 dark:text-white sm:text-4xl">
                            Deployed with DevFlow Pro
                        </h2>
                        <p class="mt-4 text-lg text-slate-600 dark:text-slate-400">
                            This project is managed and deployed using DevFlow Pro's automated infrastructure. Every deployment inherits best-practice configurations, automated backups, and instant rollback support.
                        </p>
                    </div>

                    <div class="mt-16 grid gap-8 md:grid-cols-3">
                        <div class="rounded-3xl border border-slate-200 bg-gradient-to-b from-white to-slate-50 p-8 shadow-sm dark:border-slate-800 dark:from-slate-900 dark:to-slate-950">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-600/10 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <h3 class="mt-6 text-xl font-semibold text-slate-900 dark:text-white">Automated Deployment</h3>
                            <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400">
                                One-click deployments with git integration, automatic dependency management, and zero-downtime updates.
                            </p>
                        </div>

                        <div class="rounded-3xl border border-slate-200 bg-gradient-to-b from-white to-slate-50 p-8 shadow-sm dark:border-slate-800 dark:from-slate-900 dark:to-slate-950">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-600/10 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                            <h3 class="mt-6 text-xl font-semibold text-slate-900 dark:text-white">SSL & Security</h3>
                            <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400">
                                Automatic SSL certificates, secure environment management, and industry-standard security practices.
                            </p>
                        </div>

                        <div class="rounded-3xl border border-slate-200 bg-gradient-to-b from-white to-slate-50 p-8 shadow-sm dark:border-slate-800 dark:from-slate-900 dark:to-slate-950">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-600/10 text-purple-700 dark:bg-purple-500/10 dark:text-purple-300">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                                </svg>
                            </div>
                            <h3 class="mt-6 text-xl font-semibold text-slate-900 dark:text-white">Performance Monitoring</h3>
                            <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400">
                                Real-time health checks, deployment tracking, and comprehensive logging for optimal performance.
                            </p>
                        </div>
                    </div>

                    <div class="mt-16 text-center">
                        <a href="{{ route('home') }}" wire:navigate class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-900/10 transition hover:-translate-y-0.5 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            View More Projects
                        </a>
                    </div>
                </div>
            </section>
        </main>
    @endif

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
