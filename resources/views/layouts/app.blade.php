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
    <nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 transition-colors duration-200">
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

