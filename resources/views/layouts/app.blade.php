<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ $title ?? config('app.name') }}</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#2563eb" id="theme-color-meta">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icon-192.png">
    
    <!-- Theme Script (must load before body to prevent flash) -->
    <script>
        // Check for saved theme preference or default to light mode
        const theme = localStorage.getItem('theme') || 'light';
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
    <!-- Navigation -->
    @auth
    <nav x-data="{ mobileMenuOpen: false }" class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="{{ route('dashboard') }}" class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            DevFlow Pro
                        </a>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="{{ route('home') }}" 
                           class="{{ request()->routeIs('home') ? 'border-blue-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium hover:border-blue-500 transition-colors">
                            Home
                        </a>
                        <a href="{{ route('dashboard') }}" 
                           class="{{ request()->routeIs('dashboard') ? 'border-blue-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium hover:border-blue-500 transition-colors">
                            Dashboard
                        </a>
                        <a href="{{ route('servers.index') }}" 
                           class="{{ request()->routeIs('servers.*') ? 'border-blue-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium hover:border-blue-500 transition-colors">
                            Servers
                        </a>
                        <a href="{{ route('projects.index') }}" 
                           class="{{ request()->routeIs('projects.*') ? 'border-blue-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium hover:border-blue-500 transition-colors">
                            Projects
                        </a>
                        <a href="{{ route('deployments.index') }}" 
                           class="{{ request()->routeIs('deployments.*') ? 'border-blue-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium hover:border-blue-500 transition-colors">
                            Deployments
                        </a>
                        <a href="{{ route('analytics') }}" 
                           class="{{ request()->routeIs('analytics') ? 'border-blue-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium hover:border-blue-500 transition-colors">
                            Analytics
                        </a>
                        <a href="{{ route('users.index') }}"
                           class="{{ request()->routeIs('users.*') ? 'border-blue-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium hover:border-blue-500 transition-colors">
                            Users
                        </a>
                        <!-- Advanced Features Dropdown -->
                        <div class="relative inline-block text-left" x-data="{ open: false }">
                            <button @click="open = !open" @click.away="open = false"
                                class="{{ request()->routeIs(['kubernetes.*', 'pipelines.*', 'scripts.*', 'notifications.*', 'tenants.*']) ? 'border-blue-500 text-gray-900 dark:text-white' : 'border-transparent text-gray-500 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }} inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium hover:border-blue-500 transition-colors">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                </svg>
                                Advanced
                                <svg class="ml-1 w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div x-show="open" x-transition
                                class="absolute z-50 mt-2 w-56 rounded-lg shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5">
                                <div class="py-1">
                                    <a href="{{ route('kubernetes.index') }}"
                                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 {{ request()->routeIs('kubernetes.*') ? 'bg-gray-100 dark:bg-gray-700' : '' }}">
                                        <svg class="inline w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM7 9H5v2h2V9zm8 0h-2v2h2V9zm-2 4h-2v2h2v-2zm-6 0H5v2h2v-2zm2-8v2h2V5H9zm2 4H9v2h2V9z" />
                                        </svg>
                                        Kubernetes
                                    </a>
                                    <a href="{{ route('pipelines.index') }}"
                                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 {{ request()->routeIs('pipelines.*') ? 'bg-gray-100 dark:bg-gray-700' : '' }}">
                                        <svg class="inline w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                                        </svg>
                                        CI/CD Pipelines
                                    </a>
                                    <a href="{{ route('scripts.index') }}"
                                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 {{ request()->routeIs('scripts.*') ? 'bg-gray-100 dark:bg-gray-700' : '' }}">
                                        <svg class="inline w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3.293 1.293a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414L7.586 10 5.293 7.707a1 1 0 010-1.414zM11 12a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                        </svg>
                                        Deployment Scripts
                                    </a>
                                    <a href="{{ route('notifications.index') }}"
                                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 {{ request()->routeIs('notifications.*') ? 'bg-gray-100 dark:bg-gray-700' : '' }}">
                                        <svg class="inline w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
                                        </svg>
                                        Notifications
                                    </a>
                                    <a href="{{ route('tenants.index') }}"
                                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 {{ request()->routeIs('tenants.*') ? 'bg-gray-100 dark:bg-gray-700' : '' }}">
                                        <svg class="inline w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9 2a2 2 0 00-2 2v8a2 2 0 002 2h6a2 2 0 002-2V6.414A2 2 0 0016.414 5L14 2.586A2 2 0 0012.586 2H9z" />
                                            <path d="M3 8a2 2 0 012-2v10h8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z" />
                                        </svg>
                                        Multi-Tenant
                                    </a>
                                    <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                                    <a href="{{ route('admin.system') }}"
                                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 {{ request()->routeIs('admin.*') ? 'bg-gray-100 dark:bg-gray-700' : '' }}">
                                        <svg class="inline w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 3.5a1.5 1.5 0 013 0V4a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-.5a1.5 1.5 0 000 3h.5a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-.5a1.5 1.5 0 00-3 0v.5a1 1 0 01-1 1H6a1 1 0 01-1-1v-3a1 1 0 00-1-1h-.5a1.5 1.5 0 010-3H4a1 1 0 001-1V6a1 1 0 011-1h3a1 1 0 001-1v-.5z" />
                                        </svg>
                                        System Admin
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center">
                    <!-- Theme Toggle Button -->
                    <button id="theme-toggle" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors mr-4" aria-label="Toggle theme">
                        <svg id="theme-toggle-light-icon" class="w-5 h-5 text-gray-700 dark:text-gray-300 hidden dark:block" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path>
                        </svg>
                        <svg id="theme-toggle-dark-icon" class="w-5 h-5 text-gray-700 dark:text-gray-300 block dark:hidden" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                        </svg>
                    </button>
                    
                    <div class="ml-3 relative">
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ auth()->user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-sm text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition-colors">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile menu button -->
            <div class="sm:hidden">
                <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500">
                    <svg class="h-6 w-6" x-show="!mobileMenuOpen" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg class="h-6 w-6" x-show="mobileMenuOpen" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile menu -->
        <div class="sm:hidden" x-show="mobileMenuOpen" x-transition>
            <div class="pt-2 pb-3 space-y-1">
                <a href="{{ route('home') }}" class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('home') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">Home</a>
                <a href="{{ route('dashboard') }}" class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('dashboard') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">Dashboard</a>
                <a href="{{ route('servers.index') }}" class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('servers.*') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">Servers</a>
                <a href="{{ route('projects.index') }}" class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('projects.*') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">Projects</a>
                <a href="{{ route('deployments.index') }}" class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('deployments.*') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">Deployments</a>
                <a href="{{ route('analytics') }}" class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('analytics') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">Analytics</a>
                <a href="{{ route('users.index') }}" class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('users.*') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">Users</a>

                <!-- Advanced Features Section -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-2">
                    <div class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Advanced Features</div>
                    <a href="{{ route('kubernetes.index') }}" class="block pl-6 pr-4 py-2 border-l-4 {{ request()->routeIs('kubernetes.*') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">Kubernetes</a>
                    <a href="{{ route('pipelines.index') }}" class="block pl-6 pr-4 py-2 border-l-4 {{ request()->routeIs('pipelines.*') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">CI/CD Pipelines</a>
                    <a href="{{ route('scripts.index') }}" class="block pl-6 pr-4 py-2 border-l-4 {{ request()->routeIs('scripts.*') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">Deployment Scripts</a>
                    <a href="{{ route('notifications.index') }}" class="block pl-6 pr-4 py-2 border-l-4 {{ request()->routeIs('notifications.*') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">Notifications</a>
                    <a href="{{ route('tenants.index') }}" class="block pl-6 pr-4 py-2 border-l-4 {{ request()->routeIs('tenants.*') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">Multi-Tenant</a>
                    <a href="{{ route('admin.system') }}" class="block pl-6 pr-4 py-2 border-l-4 {{ request()->routeIs('admin.*') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">System Admin</a>
                </div>
            </div>
        </div>
    </nav>
    @endauth

    <!-- Main Content -->
    <main class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{ $slot }}
        </div>
    </main>

    <!-- Toast Notifications -->
    <div id="toast-container" class="fixed bottom-4 right-4 space-y-2 z-50"></div>
    
    <!-- Theme Toggle Script -->
    <script>
        const themeToggleBtn = document.getElementById('theme-toggle');
        const htmlElement = document.documentElement;
        const themeColorMeta = document.getElementById('theme-color-meta');
        
        function setTheme(theme) {
            if (theme === 'dark') {
                htmlElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
                if (themeColorMeta) {
                    themeColorMeta.setAttribute('content', '#1f2937');
                }
            } else {
                htmlElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
                if (themeColorMeta) {
                    themeColorMeta.setAttribute('content', '#2563eb');
                }
            }
        }
        
        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', () => {
                const currentTheme = htmlElement.classList.contains('dark') ? 'dark' : 'light';
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                setTheme(newTheme);
            });
        }
    </script>
</body>
</html>

