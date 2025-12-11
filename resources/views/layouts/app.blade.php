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
    <nav x-data="{ mobileMenuOpen: false }" class="bg-slate-900 border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="{{ route('dashboard') }}" class="text-xl font-bold bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent">
                            DevFlow Pro
                        </a>
                    </div>
                    <div class="hidden sm:ml-8 sm:flex sm:items-center sm:space-x-1">
                        <a href="{{ route('home') }}"
                           class="{{ request()->routeIs('home') ? 'text-white border-b-2 border-blue-400' : 'text-slate-400 hover:text-white' }} inline-flex items-center px-3 py-2 text-sm font-medium transition-colors">
                            Home
                        </a>
                        <a href="{{ route('dashboard') }}"
                           class="{{ request()->routeIs('dashboard') ? 'text-white border-b-2 border-blue-400' : 'text-slate-400 hover:text-white' }} inline-flex items-center px-3 py-2 text-sm font-medium transition-colors">
                            Dashboard
                        </a>
                        <a href="{{ route('servers.index') }}"
                           class="{{ request()->routeIs('servers.*') ? 'text-white border-b-2 border-blue-400' : 'text-slate-400 hover:text-white' }} inline-flex items-center px-3 py-2 text-sm font-medium transition-colors">
                            Servers
                        </a>
                        <!-- Projects Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" @click.away="open = false"
                                class="{{ request()->routeIs('projects.*') ? 'text-white border-b-2 border-blue-400' : 'text-slate-400 hover:text-white' }} inline-flex items-center px-3 py-2 text-sm font-medium transition-colors">
                                Projects
                                <svg class="ml-1.5 w-3 h-3 transition-transform" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95"
                                class="absolute left-0 z-50 mt-2 w-56 rounded-lg shadow-lg bg-slate-800 border border-slate-700 overflow-hidden">
                                <div class="py-1">
                                    <a href="{{ route('projects.index') }}"
                                       class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('projects.index') ? 'text-blue-400 bg-slate-700/50' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }} transition-colors">
                                        All Projects
                                    </a>
                                    <div class="border-t border-slate-700 my-1"></div>
                                    <div class="px-4 py-1.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">System</div>
                                    <a href="{{ route('projects.devflow') }}"
                                       class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('projects.devflow') ? 'text-blue-400 bg-slate-700/50' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }} transition-colors">
                                        DevFlow Pro (Self)
                                    </a>
                                </div>
                            </div>
                        </div>
                        <!-- Deployments Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" @click.away="open = false"
                                class="{{ request()->routeIs('deployments.*') ? 'text-white border-b-2 border-blue-400' : 'text-slate-400 hover:text-white' }} inline-flex items-center px-3 py-2 text-sm font-medium transition-colors">
                                Deployments
                                <svg class="ml-1.5 w-3 h-3 transition-transform" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95"
                                class="absolute left-0 z-50 mt-2 w-56 rounded-lg shadow-lg bg-slate-800 border border-slate-700 overflow-hidden">
                                <div class="py-1">
                                    <a href="{{ route('deployments.index') }}"
                                       class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('deployments.index') ? 'text-blue-400 bg-slate-700/50' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }} transition-colors">
                                        All Deployments
                                    </a>
                                    <a href="{{ route('deployments.approvals') }}"
                                       class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('deployments.approvals') ? 'text-blue-400 bg-slate-700/50' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }} transition-colors">
                                        Deployment Approvals
                                    </a>
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('analytics') }}"
                           class="{{ request()->routeIs('analytics') ? 'text-white border-b-2 border-blue-400' : 'text-slate-400 hover:text-white' }} inline-flex items-center px-3 py-2 text-sm font-medium transition-colors">
                            Analytics
                        </a>
                        <a href="{{ route('health.dashboard') }}"
                           class="{{ request()->routeIs('health.*') ? 'text-white border-b-2 border-blue-400' : 'text-slate-400 hover:text-white' }} inline-flex items-center px-3 py-2 text-sm font-medium transition-colors">
                            Health
                        </a>
                        <a href="{{ route('users.index') }}"
                           class="{{ request()->routeIs('users.*') ? 'text-white border-b-2 border-blue-400' : 'text-slate-400 hover:text-white' }} inline-flex items-center px-3 py-2 text-sm font-medium transition-colors">
                            Users
                        </a>
                        <!-- Logs Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" @click.away="open = false"
                                class="{{ request()->routeIs('logs.*') ? 'text-white border-b-2 border-blue-400' : 'text-slate-400 hover:text-white' }} inline-flex items-center px-3 py-2 text-sm font-medium transition-colors">
                                Logs
                                <svg class="ml-1.5 w-3 h-3 transition-transform" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95"
                                class="absolute left-0 z-50 mt-2 w-56 rounded-lg shadow-lg bg-slate-800 border border-slate-700 overflow-hidden">
                                <div class="py-1">
                                    <a href="{{ route('logs.index') }}"
                                       class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('logs.index') ? 'text-blue-400 bg-slate-700/50' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }} transition-colors">
                                        Application Logs
                                    </a>
                                    <a href="{{ route('logs.notifications') }}"
                                       class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('logs.notifications') ? 'text-blue-400 bg-slate-700/50' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }} transition-colors">
                                        Notification Logs
                                    </a>
                                    <a href="{{ route('logs.webhooks') }}"
                                       class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('logs.webhooks') ? 'text-blue-400 bg-slate-700/50' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }} transition-colors">
                                        Webhook Logs
                                    </a>
                                    <a href="{{ route('logs.security') }}"
                                       class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('logs.security') ? 'text-blue-400 bg-slate-700/50' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }} transition-colors">
                                        Security Audit
                                    </a>
                                </div>
                            </div>
                        </div>
                        <!-- DevOps Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" @click.away="open = false"
                                class="{{ request()->routeIs(['kubernetes.*', 'pipelines.*', 'scripts.*', 'notifications.*', 'tenants.*']) ? 'text-white border-b-2 border-blue-400' : 'text-slate-400 hover:text-white' }} inline-flex items-center px-3 py-2 text-sm font-medium transition-colors">
                                DevOps
                                <svg class="ml-1.5 w-3 h-3 transition-transform" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95"
                                class="absolute right-0 z-50 mt-2 w-56 rounded-lg shadow-lg bg-slate-800 border border-slate-700 overflow-hidden">
                                <div class="py-1">
                                    <a href="{{ route('kubernetes.index') }}"
                                       class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('kubernetes.*') ? 'text-blue-400 bg-slate-700/50' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }} transition-colors">
                                        Kubernetes
                                    </a>
                                    <a href="{{ route('pipelines.index') }}"
                                       class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('pipelines.*') ? 'text-blue-400 bg-slate-700/50' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }} transition-colors">
                                        CI/CD Pipelines
                                    </a>
                                    <a href="{{ route('scripts.index') }}"
                                       class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('scripts.*') ? 'text-blue-400 bg-slate-700/50' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }} transition-colors">
                                        Scripts
                                    </a>
                                    <div class="border-t border-slate-700 my-1"></div>
                                    <a href="{{ route('notifications.index') }}"
                                       class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('notifications.*') ? 'text-blue-400 bg-slate-700/50' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }} transition-colors">
                                        Notifications
                                    </a>
                                    <a href="{{ route('tenants.index') }}"
                                       class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('tenants.*') ? 'text-blue-400 bg-slate-700/50' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }} transition-colors">
                                        Multi-Tenant
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Settings Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" @click.away="open = false"
                                class="{{ request()->routeIs(['admin.*', 'settings.*', 'docs.*', 'teams.*']) ? 'text-white border-b-2 border-blue-400' : 'text-slate-400 hover:text-white' }} inline-flex items-center px-3 py-2 text-sm font-medium transition-colors">
                                Settings
                                <svg class="ml-1.5 w-3 h-3 transition-transform" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95"
                                class="absolute right-0 z-50 mt-2 w-56 rounded-lg shadow-lg bg-slate-800 border border-slate-700 overflow-hidden">
                                <div class="py-1">
                                    <div class="px-4 py-1.5 text-xs font-semibold text-slate-500 uppercase">Team</div>
                                    <a href="{{ route('teams.index') }}"
                                       class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('teams.*') ? 'text-blue-400 bg-slate-700/50' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }} transition-colors">
                                        Teams
                                    </a>
                                    <a href="{{ route('settings.preferences') }}"
                                       class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('settings.preferences') ? 'text-blue-400 bg-slate-700/50' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }} transition-colors">
                                        Preferences
                                    </a>
                                    <div class="border-t border-slate-700 my-1"></div>
                                    <div class="px-4 py-1.5 text-xs font-semibold text-slate-500 uppercase">System</div>
                                    <a href="{{ route('admin.system') }}"
                                       class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.system') ? 'text-blue-400 bg-slate-700/50' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }} transition-colors">
                                        System Admin
                                    </a>
                                    <a href="{{ route('admin.help-content') }}"
                                       class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.help-content') ? 'text-blue-400 bg-slate-700/50' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }} transition-colors">
                                        Help Content
                                    </a>
                                    <a href="{{ route('admin.templates') }}"
                                       class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('admin.templates') ? 'text-blue-400 bg-slate-700/50' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }} transition-colors">
                                        Project Templates
                                    </a>
                                    <a href="{{ route('settings.queue-monitor') }}"
                                       class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('settings.queue-monitor') ? 'text-blue-400 bg-slate-700/50' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }} transition-colors">
                                        Queue Monitor
                                    </a>
                                    <a href="{{ route('settings.system-status') }}"
                                       class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('settings.system-status') ? 'text-blue-400 bg-slate-700/50' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }} transition-colors">
                                        System Status
                                    </a>
                                    <a href="{{ route('settings.system') }}"
                                       class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('settings.system') ? 'text-blue-400 bg-slate-700/50' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }} transition-colors">
                                        System Settings
                                    </a>
                                    <div class="border-t border-slate-700 my-1"></div>
                                    <div class="px-4 py-1.5 text-xs font-semibold text-slate-500 uppercase">Documentation</div>
                                    <a href="{{ route('docs.show') }}"
                                       class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('docs.show') && !request()->route('category') ? 'text-blue-400 bg-slate-700/50' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }} transition-colors">
                                        All Documentation
                                    </a>
                                    <a href="{{ route('docs.features') }}"
                                       class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('docs.features') ? 'text-blue-400 bg-slate-700/50' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }} transition-colors">
                                        Features Guide
                                    </a>
                                    <a href="{{ route('docs.api') }}"
                                       class="flex items-center px-4 py-2 text-sm {{ request()->routeIs('docs.api') ? 'text-blue-400 bg-slate-700/50' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }} transition-colors">
                                        API Documentation
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <!-- Theme Toggle Button -->
                    <button id="theme-toggle" class="p-2 rounded-lg hover:bg-slate-800 transition-colors" aria-label="Toggle theme">
                        <svg id="theme-toggle-light-icon" class="w-5 h-5 text-slate-400 hover:text-white hidden dark:block transition-colors" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path>
                        </svg>
                        <svg id="theme-toggle-dark-icon" class="w-5 h-5 text-slate-400 hover:text-white block dark:hidden transition-colors" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                        </svg>
                    </button>

                    <div class="flex items-center gap-3 pl-4 border-l border-slate-800">
                        <span class="text-sm text-slate-400">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-sm text-slate-400 hover:text-red-400 transition-colors">
                                Logout
                            </button>
                        </form>
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
                <!-- Deployments Section -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-2">
                    <div class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Deployments</div>
                    <a href="{{ route('deployments.index') }}" class="block pl-6 pr-4 py-2 border-l-4 {{ request()->routeIs('deployments.index') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">All Deployments</a>
                    <a href="{{ route('deployments.approvals') }}" class="block pl-6 pr-4 py-2 border-l-4 {{ request()->routeIs('deployments.approvals') ? 'border-blue-500 text-blue-700 bg-blue-50 dark:bg-blue-900/20' : 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-700' }} text-base font-medium">Deployment Approvals</a>
                </div>
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

