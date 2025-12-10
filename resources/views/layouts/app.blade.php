<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ $title ?? config('app.name') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.svg">

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#2563eb" id="theme-color-meta">
    <link rel="manifest" href="/manifest.json">
    
    <!-- Theme Script (must load before body to prevent flash) -->
    <script>
        // Check for saved theme preference or default to light mode
        const theme = localStorage.getItem('theme') || 'light';
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles

    <!-- Alpine.js x-cloak directive style -->
    <style>
        [x-cloak] { display: none !important; }
    </style>
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
                    <div class="hidden sm:ml-6 sm:flex sm:items-center sm:space-x-1">
                        <a href="{{ route('home') }}"
                           class="{{ request()->routeIs('home') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' }} inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                            Home
                        </a>
                        <a href="{{ route('dashboard') }}"
                           class="{{ request()->routeIs('dashboard') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' }} inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                            Dashboard
                        </a>
                        <a href="{{ route('servers.index') }}"
                           class="{{ request()->routeIs('servers.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' }} inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                            Servers
                        </a>
                        <!-- Projects Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" @click.away="open = false"
                                class="{{ request()->routeIs('projects.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' }} inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                Projects
                                <svg class="ml-1.5 w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95"
                                class="absolute left-0 z-50 mt-2 w-56 rounded-xl shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black/5 dark:ring-white/10 overflow-hidden">
                                <div class="py-1">
                                    <a href="{{ route('projects.index') }}"
                                       class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ request()->routeIs('projects.index') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : '' }}">
                                        <svg class="w-4 h-4 mr-3 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
                                        </svg>
                                        All Projects
                                    </a>
                                    <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                                    <div class="px-4 py-2 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">System</div>
                                    <a href="{{ route('projects.devflow') }}"
                                       class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ request()->routeIs('projects.devflow') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : '' }}">
                                        <svg class="w-4 h-4 mr-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        DevFlow Pro (Self)
                                    </a>
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('deployments.index') }}"
                           class="{{ request()->routeIs('deployments.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' }} inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                            Deployments
                        </a>
                        <a href="{{ route('analytics') }}"
                           class="{{ request()->routeIs('analytics') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' }} inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                            Analytics
                        </a>
                        <a href="{{ route('health.dashboard') }}"
                           class="{{ request()->routeIs('health.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' }} inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                            Health
                        </a>
                        <a href="{{ route('users.index') }}"
                           class="{{ request()->routeIs('users.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' }} inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                            Users
                        </a>
                        <!-- Logs Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" @click.away="open = false"
                                class="{{ request()->routeIs('logs.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' }} inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                Logs
                                <svg class="ml-1.5 w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95"
                                class="absolute left-0 z-50 mt-2 w-56 rounded-xl shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black/5 dark:ring-white/10 overflow-hidden">
                                <div class="py-1">
                                    <a href="{{ route('logs.index') }}"
                                       class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ request()->routeIs('logs.index') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : '' }}">
                                        <svg class="w-4 h-4 mr-3 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                                        </svg>
                                        Application Logs
                                    </a>
                                    <a href="{{ route('logs.notifications') }}"
                                       class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ request()->routeIs('logs.notifications') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : '' }}">
                                        <svg class="w-4 h-4 mr-3 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
                                        </svg>
                                        Notification Logs
                                    </a>
                                    <a href="{{ route('logs.webhooks') }}"
                                       class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ request()->routeIs('logs.webhooks') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : '' }}">
                                        <svg class="w-4 h-4 mr-3 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                        Webhook Logs
                                    </a>
                                    <a href="{{ route('logs.security') }}"
                                       class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ request()->routeIs('logs.security') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : '' }}">
                                        <svg class="w-4 h-4 mr-3 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        Security Audit
                                    </a>
                                </div>
                            </div>
                        </div>
                        <!-- DevOps Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" @click.away="open = false"
                                class="{{ request()->routeIs(['kubernetes.*', 'pipelines.*', 'scripts.*', 'notifications.*', 'tenants.*']) ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' }} inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                DevOps
                                <svg class="ml-1.5 w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95"
                                class="absolute right-0 z-50 mt-2 w-56 rounded-xl shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black/5 dark:ring-white/10 overflow-hidden">
                                <div class="py-1">
                                    <a href="{{ route('kubernetes.index') }}"
                                       class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ request()->routeIs('kubernetes.*') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : '' }}">
                                        <svg class="w-4 h-4 mr-3 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM7 9H5v2h2V9zm8 0h-2v2h2V9zm-2 4h-2v2h2v-2zm-6 0H5v2h2v-2zm2-8v2h2V5H9zm2 4H9v2h2V9z" />
                                        </svg>
                                        Kubernetes
                                    </a>
                                    <a href="{{ route('pipelines.index') }}"
                                       class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ request()->routeIs('pipelines.*') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : '' }}">
                                        <svg class="w-4 h-4 mr-3 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                                        </svg>
                                        CI/CD Pipelines
                                    </a>
                                    <a href="{{ route('scripts.index') }}"
                                       class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ request()->routeIs('scripts.*') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : '' }}">
                                        <svg class="w-4 h-4 mr-3 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3.293 1.293a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 01-1.414-1.414L7.586 10 5.293 7.707a1 1 0 010-1.414zM11 12a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                        </svg>
                                        Scripts
                                    </a>
                                    <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                                    <a href="{{ route('notifications.index') }}"
                                       class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ request()->routeIs('notifications.*') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : '' }}">
                                        <svg class="w-4 h-4 mr-3 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
                                        </svg>
                                        Notifications
                                    </a>
                                    <a href="{{ route('tenants.index') }}"
                                       class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ request()->routeIs('tenants.*') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : '' }}">
                                        <svg class="w-4 h-4 mr-3 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9 2a2 2 0 00-2 2v8a2 2 0 002 2h6a2 2 0 002-2V6.414A2 2 0 0016.414 5L14 2.586A2 2 0 0012.586 2H9z" />
                                            <path d="M3 8a2 2 0 012-2v10h8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z" />
                                        </svg>
                                        Multi-Tenant
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Settings Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" @click.away="open = false"
                                class="{{ request()->routeIs(['admin.*', 'settings.*', 'docs.*', 'teams.*']) ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' }} inline-flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                Settings
                                <svg class="ml-1.5 w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95"
                                class="absolute right-0 z-50 mt-2 w-56 rounded-xl shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black/5 dark:ring-white/10 overflow-hidden">
                                <div class="py-1">
                                    <div class="px-4 py-2 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Team</div>
                                    <a href="{{ route('teams.index') }}"
                                       class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ request()->routeIs('teams.*') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : '' }}">
                                        <svg class="w-4 h-4 mr-3 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                                        </svg>
                                        Teams
                                    </a>
                                    <a href="{{ route('settings.preferences') }}"
                                       class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ request()->routeIs('settings.preferences') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : '' }}">
                                        <svg class="w-4 h-4 mr-3 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                                        </svg>
                                        Preferences
                                    </a>
                                    <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                                    <div class="px-4 py-2 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">System</div>
                                    <a href="{{ route('admin.system') }}"
                                       class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ request()->routeIs('admin.*') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : '' }}">
                                        <svg class="w-4 h-4 mr-3 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 3.5a1.5 1.5 0 013 0V4a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-.5a1.5 1.5 0 000 3h.5a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-.5a1.5 1.5 0 00-3 0v.5a1 1 0 01-1 1H6a1 1 0 01-1-1v-3a1 1 0 00-1-1h-.5a1.5 1.5 0 010-3H4a1 1 0 001-1V6a1 1 0 011-1h3a1 1 0 001-1v-.5z" />
                                        </svg>
                                        System Admin
                                    </a>
                                    <a href="{{ route('settings.queue-monitor') }}"
                                       class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ request()->routeIs('settings.queue-monitor') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : '' }}">
                                        <svg class="w-4 h-4 mr-3 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm2 10a1 1 0 10-2 0v3a1 1 0 102 0v-3zm2-3a1 1 0 011 1v5a1 1 0 11-2 0v-5a1 1 0 011-1zm4-1a1 1 0 10-2 0v7a1 1 0 102 0V8z" clip-rule="evenodd" />
                                        </svg>
                                        Queue Monitor
                                    </a>
                                    <a href="{{ route('settings.system-status') }}"
                                       class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ request()->routeIs('settings.system-status') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : '' }}">
                                        <svg class="w-4 h-4 mr-3 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 6a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2zm0 6a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2z" clip-rule="evenodd" />
                                        </svg>
                                        System Status
                                    </a>
                                    <a href="{{ route('settings.system') }}"
                                       class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ request()->routeIs('settings.system') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : '' }}">
                                        <svg class="w-4 h-4 mr-3 text-gray-400 dark:text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                                        </svg>
                                        System Settings
                                    </a>
                                    <a href="{{ route('settings.redeploy') }}"
                                       class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ request()->routeIs('settings.redeploy') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : '' }}">
                                        <svg class="w-4 h-4 mr-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        System Redeploy
                                    </a>
                                    <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                                    <div class="px-4 py-2 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Documentation</div>
                                    <a href="{{ route('docs.features') }}"
                                       class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ request()->routeIs('docs.features') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : '' }}">
                                        <svg class="w-4 h-4 mr-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                        Features Guide
                                    </a>
                                    <a href="{{ route('docs.api') }}"
                                       class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ request()->routeIs('docs.api') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : '' }}">
                                        <svg class="w-4 h-4 mr-3 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                                        </svg>
                                        API Documentation
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
                <a href="{{ route('health.dashboard') }}" class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('health.*') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">Health</a>
                <a href="{{ route('users.index') }}" class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('users.*') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">Users</a>

                <!-- Logs Section -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-2">
                    <div class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Logs & Monitoring</div>
                    <a href="{{ route('logs.index') }}" class="block pl-6 pr-4 py-2 border-l-4 {{ request()->routeIs('logs.index') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">Application Logs</a>
                    <a href="{{ route('logs.notifications') }}" class="block pl-6 pr-4 py-2 border-l-4 {{ request()->routeIs('logs.notifications') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">Notification Logs</a>
                    <a href="{{ route('logs.webhooks') }}" class="block pl-6 pr-4 py-2 border-l-4 {{ request()->routeIs('logs.webhooks') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">Webhook Logs</a>
                    <a href="{{ route('logs.security') }}" class="block pl-6 pr-4 py-2 border-l-4 {{ request()->routeIs('logs.security') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">Security Audit</a>
                </div>

                <!-- Advanced Features Section -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-2">
                    <div class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Advanced Features</div>
                    <a href="{{ route('kubernetes.index') }}" class="block pl-6 pr-4 py-2 border-l-4 {{ request()->routeIs('kubernetes.*') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">Kubernetes</a>
                    <a href="{{ route('pipelines.index') }}" class="block pl-6 pr-4 py-2 border-l-4 {{ request()->routeIs('pipelines.*') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">CI/CD Pipelines</a>
                    <a href="{{ route('scripts.index') }}" class="block pl-6 pr-4 py-2 border-l-4 {{ request()->routeIs('scripts.*') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">Deployment Scripts</a>
                    <a href="{{ route('notifications.index') }}" class="block pl-6 pr-4 py-2 border-l-4 {{ request()->routeIs('notifications.*') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">Notifications</a>
                    <a href="{{ route('tenants.index') }}" class="block pl-6 pr-4 py-2 border-l-4 {{ request()->routeIs('tenants.*') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">Multi-Tenant</a>
                    <a href="{{ route('admin.system') }}" class="block pl-6 pr-4 py-2 border-l-4 {{ request()->routeIs('admin.*') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">System Admin</a>
                    <a href="{{ route('settings.queue-monitor') }}" class="block pl-6 pr-4 py-2 border-l-4 {{ request()->routeIs('settings.queue-monitor') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">Queue Monitor</a>
                    <a href="{{ route('settings.preferences') }}" class="block pl-6 pr-4 py-2 border-l-4 {{ request()->routeIs('settings.preferences') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">Preferences</a>
                    <a href="{{ route('settings.system-status') }}" class="block pl-6 pr-4 py-2 border-l-4 {{ request()->routeIs('settings.system-status') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">System Status</a>
                    <a href="{{ route('docs.api') }}" class="block pl-6 pr-4 py-2 border-l-4 {{ request()->routeIs('docs.api') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">API Documentation</a>
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

    @livewireScripts
</body>
</html>

