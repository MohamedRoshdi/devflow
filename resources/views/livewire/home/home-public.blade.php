<div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
    <!-- Header -->
    <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-md border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <!-- Logo/Title -->
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-600 via-purple-600 to-pink-600 rounded-xl flex items-center justify-center shadow-lg transform hover:scale-110 transition-transform duration-200">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-extrabold bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                            DevFlow Pro
                        </h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Deployment Platform</p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center space-x-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 hover:from-blue-700 hover:via-purple-700 hover:to-pink-700 text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                            </svg>
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 font-semibold transition-colors duration-200 px-4 py-2">
                            Login
                        </a>
                        <a href="{{ route('register') }}" class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 hover:from-blue-700 hover:via-purple-700 hover:to-pink-700 text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Get Started
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="w-full px-6 sm:px-8 lg:px-12 py-20">
        <!-- Hero Section -->
        <div class="text-center mb-28 max-w-6xl mx-auto">
            <!-- Floating Badge -->
            <div class="inline-flex items-center px-8 py-4 bg-white/90 dark:bg-gray-800/90 backdrop-blur-md rounded-full shadow-2xl mb-10 border-2 border-gray-200 dark:border-gray-700">
                <span class="relative flex h-4 w-4 mr-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-4 w-4 bg-green-500"></span>
                </span>
                <span class="text-lg font-bold text-gray-700 dark:text-gray-300">{{ $projects->count() }} Projects Live Now</span>
            </div>

            <!-- Main Heading -->
            <h2 class="text-6xl md:text-8xl lg:text-9xl font-black text-gray-900 dark:text-white mb-10 leading-tight">
                Welcome to
                <span class="block mt-4 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                    Our Projects
                </span>
            </h2>
            
            <p class="text-2xl md:text-3xl lg:text-4xl text-gray-600 dark:text-gray-400 max-w-5xl mx-auto font-semibold leading-relaxed">
                Explore our live applications, powered by
                <span class="font-black bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">DevFlow Pro</span>
                deployment platform
            </p>
            
            <!-- Stats Cards -->
            <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                <!-- Live Projects -->
                <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-md rounded-3xl shadow-2xl p-10 border-2 border-gray-200 dark:border-gray-700 transform hover:scale-110 transition-all duration-300">
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-2xl">
                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                        </svg>
                    </div>
                    <div class="text-6xl font-black text-blue-600 dark:text-blue-400 mb-4">{{ $projects->count() }}</div>
                    <div class="text-base font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Live Projects</div>
                </div>

                <!-- Uptime -->
                <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-md rounded-3xl shadow-2xl p-10 border-2 border-gray-200 dark:border-gray-700 transform hover:scale-110 transition-all duration-300">
                    <div class="w-20 h-20 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-2xl">
                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="text-6xl font-black text-green-600 dark:text-green-400 mb-4">100%</div>
                    <div class="text-base font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Uptime</div>
                </div>

                <!-- 24/7 -->
                <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-md rounded-3xl shadow-2xl p-10 border-2 border-gray-200 dark:border-gray-700 transform hover:scale-110 transition-all duration-300">
                    <div class="w-20 h-20 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-2xl">
                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="text-6xl font-black text-purple-600 dark:text-purple-400 mb-4">24/7</div>
                    <div class="text-base font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Available</div>
                </div>
            </div>
        </div>

        @if($projects->count() > 0)
            <!-- Section Title -->
            <div class="flex items-center justify-center mb-16 max-w-6xl mx-auto">
                <div class="h-1 flex-1 bg-gradient-to-r from-transparent via-gray-300 dark:via-gray-700 to-transparent"></div>
                <span class="px-10 text-2xl font-black text-gray-900 dark:text-white uppercase tracking-wider">Featured Projects</span>
                <div class="h-1 flex-1 bg-gradient-to-r from-transparent via-gray-300 dark:via-gray-700 to-transparent"></div>
            </div>

            <!-- Projects Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 max-w-6xl mx-auto">
                @foreach($projects as $project)
                    <div class="group relative bg-white/90 dark:bg-gray-800/90 backdrop-blur-md rounded-3xl shadow-2xl hover:shadow-3xl transition-all duration-500 transform hover:-translate-y-4 hover:scale-105 overflow-hidden border-2 border-gray-200 dark:border-gray-700">
                        <!-- Gradient Top -->
                        <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500"></div>
                        
                        <!-- Card Content -->
                        <div class="p-12">
                            <!-- Icon & Status -->
                            <div class="flex items-start justify-between mb-8">
                                <div class="w-24 h-24 bg-gradient-to-br from-blue-600 via-purple-600 to-pink-600 rounded-3xl flex items-center justify-center flex-shrink-0 shadow-2xl transform group-hover:scale-110 group-hover:rotate-6 transition-all duration-500">
                                    @if($project->framework === 'Laravel')
                                        <svg class="w-14 h-14 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M23.642 5.43a.364.364 0 01.014.1v5.149c0 .135-.073.26-.189.326l-4.323 2.49v4.934a.378.378 0 01-.188.326L9.93 23.949a.316.316 0 01-.066.027c-.008.002-.016.008-.024.01a.348.348 0 01-.192 0c-.011-.002-.02-.008-.03-.012-.02-.008-.042-.014-.062-.025L.533 18.755a.376.376 0 01-.189-.326V2.974c0-.033.005-.066.014-.098.003-.012.01-.02.014-.032a.369.369 0 01.023-.058c.004-.013.015-.022.023-.033l.033-.045c.012-.01.025-.018.037-.027.014-.012.027-.024.041-.034H.53L5.043.05a.375.375 0 01.375 0L9.93 2.647h.002c.015.01.027.021.04.033l.038.027c.013.014.02.03.033.045.008.011.02.021.025.033.01.02.017.038.024.058.003.011.01.021.013.032.01.031.014.064.014.098v9.652l3.76-2.164V5.527c0-.033.004-.066.013-.098.003-.01.01-.02.013-.032a.487.487 0 01.024-.059c.007-.012.018-.02.025-.033.012-.015.021-.03.033-.043.012-.012.025-.02.037-.028.013-.012.027-.023.04-.032h.001l4.513-2.598a.375.375 0 01.375 0l4.513 2.598c.016.01.029.021.041.033l.038.027c.013.014.02.03.032.045.009.012.02.021.025.033.01.02.017.038.024.058.003.012.01.022.013.032zm-.74 5.032V6.179l-1.578.908-2.182 1.256v4.283zm-4.51 7.75v-4.287l-2.147 1.225-6.126 3.498v4.325zM1.093 3.624v14.588l8.273 4.761v-4.325l-4.322-2.445-.002-.003H5.04c-.014-.01-.025-.021-.04-.031-.011-.01-.024-.018-.035-.027l-.001-.002c-.013-.012-.021-.025-.031-.039-.01-.012-.021-.023-.028-.037h-.002c-.008-.014-.013-.031-.02-.047-.006-.016-.014-.027-.018-.043a.49.49 0 01-.008-.057c-.002-.014-.006-.027-.006-.041V5.789l-2.18-1.257zM5.23.81L1.47 2.974l3.76 2.164 3.758-2.164zm1.956 13.505l2.182-1.256V3.624l-1.58.91-2.182 1.255v9.435zm11.581-10.95l-3.76 2.163 3.76 2.163 3.759-2.164zm-.376 4.978L16.21 7.087 14.63 6.18v4.283l2.182 1.256 1.58.908zm-8.65 9.654l5.514-3.148 2.756-1.572-3.757-2.163-4.323 2.489-3.941 2.27z"/>
                                        </svg>
                                    @elseif(in_array($project->framework, ['React', 'Vue', 'Next.js']))
                                        <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                                        </svg>
                                    @else
                                        <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                                        </svg>
                                    @endif
                                </div>
                                
                                <!-- Live Badge -->
                                <div class="flex flex-col items-end space-y-3">
                                    <span class="inline-flex items-center px-5 py-2.5 rounded-full text-base font-black bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300 shadow-xl">
                                        <span class="w-3 h-3 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                                        LIVE
                                    </span>
                                    @if($project->environment)
                                        <span class="px-5 py-2 rounded-full text-sm font-black shadow-xl
                                            @if($project->environment === 'production') bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300
                                            @elseif($project->environment === 'staging') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300
                                            @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                            @endif">
                                            {{ strtoupper($project->environment) }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Project Name -->
                            <h3 class="text-4xl font-black text-gray-900 dark:text-white mb-3 group-hover:text-transparent group-hover:bg-gradient-to-r group-hover:from-blue-600 group-hover:to-purple-600 group-hover:bg-clip-text transition-all duration-300">
                                {{ $project->name }}
                            </h3>
                            
                            <!-- Framework -->
                            <p class="text-lg font-bold text-gray-500 dark:text-gray-400 mb-6 uppercase tracking-wider">{{ $project->framework }}</p>

                            <!-- Meta Info -->
                            <div class="space-y-4 mb-8">
                                @if($project->domain)
                                    <div class="flex items-center text-lg text-gray-600 dark:text-gray-400">
                                        <svg class="w-6 h-6 mr-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                                        </svg>
                                        <span class="truncate font-bold">{{ $project->domain }}</span>
                                    </div>
                                @endif
                                <div class="flex items-center text-lg text-gray-600 dark:text-gray-400">
                                    <svg class="w-6 h-6 mr-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                                    </svg>
                                    <span class="font-bold">{{ $project->server->name }}</span>
                                </div>
                            </div>

                            <!-- Visit Button -->
                            @php
                                $url = $project->domain 
                                    ? (str_starts_with($project->domain, 'http') ? $project->domain : 'http://' . $project->domain)
                                    : 'http://' . $project->server->ip . ':' . $project->port;
                            @endphp
                            
                            <a href="{{ $url }}" target="_blank" 
                               class="w-full inline-flex items-center justify-center px-10 py-6 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 hover:from-blue-700 hover:via-purple-700 hover:to-pink-700 text-white font-black rounded-2xl transition-all duration-300 transform group-hover:scale-105 shadow-2xl hover:shadow-3xl text-xl">
                                <span>Visit Project</span>
                                <svg class="w-7 h-7 ml-3 transform group-hover:translate-x-2 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                </svg>
                            </a>
                        </div>

                        <!-- Animated Border -->
                        <div class="absolute inset-0 border-2 border-transparent group-hover:border-purple-500/50 dark:group-hover:border-purple-400/50 rounded-3xl transition-all duration-500 pointer-events-none"></div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="text-center py-32 max-w-4xl mx-auto">
                <div class="w-40 h-40 bg-gradient-to-br from-blue-100 to-purple-100 dark:from-gray-800 dark:to-gray-700 rounded-3xl flex items-center justify-center mx-auto mb-10 shadow-2xl">
                    <svg class="w-20 h-20 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                </div>
                <h3 class="text-5xl font-black text-gray-900 dark:text-white mb-6">No Projects Yet</h3>
                <p class="text-2xl text-gray-600 dark:text-gray-400 mb-12 max-w-2xl mx-auto leading-relaxed">
                    We're currently working on exciting new projects. Check back soon!
                </p>
                @auth
                    <a href="{{ route('projects.create') }}" class="inline-flex items-center px-10 py-5 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 hover:from-blue-700 hover:via-purple-700 hover:to-pink-700 text-white font-black rounded-2xl transition-all duration-300 transform hover:scale-105 shadow-2xl text-xl">
                        <svg class="w-7 h-7 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Create Your First Project
                    </a>
                @endauth
            </div>
        @endif
    </div>

    <!-- Footer -->
    <div class="mt-32 py-12 border-t border-gray-200 dark:border-gray-700 bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p class="text-base text-gray-600 dark:text-gray-400 mb-3">
                    Powered by 
                    <span class="font-black text-xl bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                        DevFlow Pro
                    </span>
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-500 font-medium">
                    Professional Deployment Management System
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-600 mt-4">
                    © {{ date('Y') }} All rights reserved.
                </p>
            </div>
        </div>
    </div>
</div>
