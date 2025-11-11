<div class="min-h-screen bg-white dark:bg-gray-900">
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 dark:bg-gray-900/80 backdrop-blur-lg border-b border-gray-200 dark:border-gray-800">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Logo -->
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-tr from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-gray-900 dark:text-white">DevFlow Pro</span>
                </div>

                <!-- Actions -->
                <div class="flex items-center space-x-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="px-6 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg font-medium hover:opacity-90 transition-opacity">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white font-medium">
                            Sign In
                        </a>
                        <a href="{{ route('register') }}" class="px-6 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg font-medium hover:opacity-90 transition-opacity">
                            Get Started
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-32 pb-20 px-6 lg:px-8">
        <div class="max-w-7xl mx-auto text-center">
            <!-- Badge -->
            @if($projects->count() > 0)
                <div class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-800 rounded-full mb-8">
                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $projects->count() }} {{ $projects->count() === 1 ? 'Project' : 'Projects' }} Live</span>
                </div>
            @endif

            <!-- Heading -->
            <h1 class="text-5xl md:text-6xl lg:text-7xl font-bold text-gray-900 dark:text-white mb-6 tracking-tight">
                Explore Our Live
                <span class="block bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                    Applications
                </span>
            </h1>

            <!-- Description -->
            <p class="text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto mb-12">
                Discover our portfolio of deployed applications, managed and scaled with DevFlow Pro
            </p>

            <!-- Stats -->
            <div class="grid grid-cols-3 gap-8 max-w-xl mx-auto">
                <div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ $projects->count() }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Projects</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">100%</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Uptime</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-white mb-1">24/7</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Available</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Projects Section -->
    @if($projects->count() > 0)
        <section class="py-20 px-6 lg:px-8 bg-gray-50 dark:bg-gray-800/50">
            <div class="max-w-7xl mx-auto">
                <!-- Section Header -->
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Our Projects</h2>
                    <p class="text-lg text-gray-600 dark:text-gray-400">Click to visit any application</p>
                </div>

                <!-- Projects Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($projects as $project)
                        @php
                            $url = $project->domain 
                                ? (str_starts_with($project->domain, 'http') ? $project->domain : 'http://' . $project->domain)
                                : 'http://' . $project->server->ip . ':' . $project->port;
                        @endphp

                        <a href="{{ $url }}" target="_blank" class="group block bg-white dark:bg-gray-900 rounded-2xl p-8 border border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700 hover:shadow-lg transition-all duration-300">
                            <!-- Header -->
                            <div class="flex items-start justify-between mb-6">
                                <!-- Icon -->
                                <div class="w-14 h-14 rounded-xl bg-gradient-to-tr from-blue-500 to-purple-600 flex items-center justify-center">
                                    @if($project->framework === 'Laravel')
                                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M23.642 5.43a.364.364 0 01.014.1v5.149c0 .135-.073.26-.189.326l-4.323 2.49v4.934a.378.378 0 01-.188.326L9.93 23.949a.316.316 0 01-.066.027c-.008.002-.016.008-.024.01a.348.348 0 01-.192 0c-.011-.002-.02-.008-.03-.012-.02-.008-.042-.014-.062-.025L.533 18.755a.376.376 0 01-.189-.326V2.974c0-.033.005-.066.014-.098.003-.012.01-.02.014-.032a.369.369 0 01.023-.058c.004-.013.015-.022.023-.033l.033-.045c.012-.01.025-.018.037-.027.014-.012.027-.024.041-.034H.53L5.043.05a.375.375 0 01.375 0L9.93 2.647h.002c.015.01.027.021.04.033l.038.027c.013.014.02.03.033.045.008.011.02.021.025.033.01.02.017.038.024.058.003.011.01.021.013.032.01.031.014.064.014.098v9.652l3.76-2.164V5.527c0-.033.004-.066.013-.098.003-.01.01-.02.013-.032a.487.487 0 01.024-.059c.007-.012.018-.02.025-.033.012-.015.021-.03.033-.043.012-.012.025-.02.037-.028.013-.012.027-.023.04-.032h.001l4.513-2.598a.375.375 0 01.375 0l4.513 2.598c.016.01.029.021.041.033l.038.027c.013.014.02.03.032.045.009.012.02.021.025.033.01.02.017.038.024.058.003.012.01.022.013.032zm-.74 5.032V6.179l-1.578.908-2.182 1.256v4.283zm-4.51 7.75v-4.287l-2.147 1.225-6.126 3.498v4.325zM1.093 3.624v14.588l8.273 4.761v-4.325l-4.322-2.445-.002-.003H5.04c-.014-.01-.025-.021-.04-.031-.011-.01-.024-.018-.035-.027l-.001-.002c-.013-.012-.021-.025-.031-.039-.01-.012-.021-.023-.028-.037h-.002c-.008-.014-.013-.031-.02-.047-.006-.016-.014-.027-.018-.043a.49.49 0 01-.008-.057c-.002-.014-.006-.027-.006-.041V5.789l-2.18-1.257zM5.23.81L1.47 2.974l3.76 2.164 3.758-2.164zm1.956 13.505l2.182-1.256V3.624l-1.58.91-2.182 1.255v9.435zm11.581-10.95l-3.76 2.163 3.76 2.163 3.759-2.164zm-.376 4.978L16.21 7.087 14.63 6.18v4.283l2.182 1.256 1.58.908zm-8.65 9.654l5.514-3.148 2.756-1.572-3.757-2.163-4.323 2.489-3.941 2.27z"/>
                                        </svg>
                                    @else
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                        </svg>
                                    @endif
                                </div>

                                <!-- Status -->
                                <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-semibold rounded-full">
                                    LIVE
                                </span>
                            </div>

                            <!-- Content -->
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                    {{ $project->name }}
                                </h3>
                                
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-4">
                                    {{ $project->framework }}
                                </p>

                                <div class="space-y-2">
                                    @if($project->domain)
                                        <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                                            </svg>
                                            <span class="truncate">{{ $project->domain }}</span>
                                        </div>
                                    @endif

                                    <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                                        </svg>
                                        {{ $project->server->name }}
                                    </div>

                                    @if($project->environment)
                                        <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            {{ ucfirst($project->environment) }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Arrow -->
                            <div class="mt-6 flex items-center text-blue-600 dark:text-blue-400 font-medium">
                                <span>Visit Project</span>
                                <svg class="w-5 h-5 ml-2 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                </svg>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @else
        <!-- Empty State -->
        <section class="py-20 px-6 lg:px-8">
            <div class="max-w-2xl mx-auto text-center">
                <div class="w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded-2xl flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">No Projects Yet</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-8">
                    We're currently working on exciting new projects. Check back soon!
                </p>
                @auth
                    <a href="{{ route('projects.create') }}" class="inline-flex items-center px-6 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg font-medium hover:opacity-90 transition-opacity">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Create Your First Project
                    </a>
                @endauth
            </div>
        </section>
    @endif

    <!-- Footer -->
    <footer class="py-12 px-6 lg:px-8 border-t border-gray-200 dark:border-gray-800">
        <div class="max-w-7xl mx-auto text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Powered by <span class="font-semibold text-gray-900 dark:text-white">DevFlow Pro</span> — Professional Deployment Management
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">
                © {{ date('Y') }} All rights reserved
            </p>
        </div>
    </footer>
</div>
