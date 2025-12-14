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
        // Load sidebar state
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles

    <!-- Alpine.js x-cloak directive style -->
    <style>
        [x-cloak] { display: none !important; }

        /* Custom scrollbar for sidebar */
        .sidebar-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .sidebar-scroll::-webkit-scrollbar-track {
            background: rgb(15 23 42); /* slate-900 */
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgb(51 65 85); /* slate-700 */
            border-radius: 3px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: rgb(71 85 105); /* slate-600 */
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
    <!-- Skip to Content Link (Accessibility) -->
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-[100] focus:px-4 focus:py-2 focus:bg-blue-600 focus:text-white focus:rounded-lg focus:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-all">
        Skip to main content
    </a>

    @auth
    <div x-data="{
        sidebarOpen: true,
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        mobileMenuOpen: false
    }" x-init="$watch('sidebarCollapsed', value => localStorage.setItem('sidebarCollapsed', value))">

        <!-- Sidebar -->
        <aside :class="sidebarCollapsed ? 'w-16' : 'w-64'"
               class="fixed inset-y-0 left-0 z-50 bg-slate-900 border-r border-slate-800 transition-all duration-300 hidden md:block flex flex-col">

            <!-- Logo Section -->
            <div class="flex items-center justify-between h-16 px-4 border-b border-slate-800 flex-shrink-0">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 overflow-hidden">
                    <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-blue-400 to-cyan-400 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-lg">D</span>
                    </div>
                    <span x-show="!sidebarCollapsed" class="text-xl font-bold bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent whitespace-nowrap">
                        DevFlow Pro
                    </span>
                </a>
                <button @click="sidebarCollapsed = !sidebarCollapsed"
                        class="p-1.5 rounded-lg hover:bg-slate-800 transition-colors text-slate-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                    </svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto py-4 px-2 space-y-1 sidebar-scroll">
                <!-- Main Section -->
                <div class="space-y-1">
                    <div x-show="!sidebarCollapsed" class="px-3 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        Main
                    </div>
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white border-l-4 border-blue-500 ml-0 -ml-2 pl-5' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zM14 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1h-4a1 1 0 01-1-1v-3z"/>
                        </svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Dashboard</span>
                    </a>
                </div>

                <!-- Infrastructure Section -->
                <div class="space-y-1 pt-4">
                    <div x-show="!sidebarCollapsed" class="px-3 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        Infrastructure
                    </div>

                    <!-- Servers with Dropdown -->
                    <div x-data="{ open: {{ request()->routeIs('servers.*') ? 'true' : 'false' }} }">
                        <button @click="open = !open"
                                class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('servers.*') ? 'bg-slate-800 text-white border-l-4 border-blue-500 ml-0 -ml-2 pl-5' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                </svg>
                                <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Servers</span>
                            </div>
                            <svg x-show="!sidebarCollapsed" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        <div x-show="open && !sidebarCollapsed" x-collapse class="ml-8 space-y-1 mt-1">
                            <a href="{{ route('servers.index') }}"
                               class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('servers.index') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                All Servers
                            </a>
                            @if(request()->route('server'))
                            <div class="border-t border-slate-700 my-2"></div>
                            <a href="{{ route('servers.edit', request()->route('server')) }}"
                               class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('servers.edit') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit Server
                                </span>
                            </a>
                            @endif
                        </div>
                    </div>

                    <!-- Projects with Dropdown -->
                    <div x-data="{ open: {{ request()->routeIs('projects.*') ? 'true' : 'false' }} }">
                        <button @click="open = !open"
                                class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('projects.*') ? 'bg-slate-800 text-white border-l-4 border-blue-500 ml-0 -ml-2 pl-5' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                </svg>
                                <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Projects</span>
                            </div>
                            <svg x-show="!sidebarCollapsed" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        <div x-show="open && !sidebarCollapsed" x-collapse class="ml-8 space-y-1 mt-1">
                            <a href="{{ route('projects.index') }}"
                               class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('projects.index') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                All Projects
                            </a>
                            <a href="{{ route('projects.devflow') }}"
                               class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('projects.devflow') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                DevFlow Pro (Self)
                            </a>
                            @if(request()->route('project'))
                            <div class="border-t border-slate-700 my-2"></div>
                            <a href="{{ route('projects.edit', request()->route('project')) }}"
                               class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('projects.edit') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit Project
                                </span>
                            </a>
                            @endif
                        </div>
                    </div>

                    <!-- Deployments with Dropdown -->
                    <div x-data="{ open: {{ request()->routeIs('deployments.*') ? 'true' : 'false' }} }">
                        <button @click="open = !open"
                                class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('deployments.*') ? 'bg-slate-800 text-white border-l-4 border-blue-500 ml-0 -ml-2 pl-5' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                </svg>
                                <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Deployments</span>
                            </div>
                            <svg x-show="!sidebarCollapsed" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        <div x-show="open && !sidebarCollapsed" x-collapse class="ml-8 space-y-1 mt-1">
                            <a href="{{ route('deployments.index') }}"
                               class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('deployments.index') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                All Deployments
                            </a>
                            <a href="{{ route('deployments.approvals') }}"
                               class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('deployments.approvals') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Deployment Approvals
                            </a>
                            <a href="{{ route('deployments.scheduled') }}"
                               class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('deployments.scheduled') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Scheduled Deployments
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Documentation Section (PROMINENT) -->
                <div class="space-y-1 pt-4 border-t border-slate-700/50 mt-4">
                    <div x-show="!sidebarCollapsed" class="px-3 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        Documentation
                    </div>

                    <!-- Documentation with Dropdown and Highlight -->
                    <div x-data="{ open: {{ request()->routeIs('docs.*') ? 'true' : 'false' }} }" class="bg-blue-500/5 rounded-lg">
                        <button @click="open = !open"
                                class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('docs.*') ? 'bg-slate-800 text-white border-l-4 border-blue-500 ml-0 -ml-2 pl-5' : 'text-blue-400 hover:bg-slate-800 hover:text-white' }}">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                                <span x-show="!sidebarCollapsed" class="whitespace-nowrap font-semibold">Documentation</span>
                            </div>
                            <svg x-show="!sidebarCollapsed" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        <div x-show="open && !sidebarCollapsed" x-collapse class="ml-8 space-y-1 mt-1 pb-2">
                            <a href="{{ route('docs.show') }}"
                               class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('docs.show') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                All Documentation
                            </a>
                            <a href="{{ route('docs.features') }}"
                               class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('docs.features') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Features Guide
                            </a>
                            <a href="{{ route('docs.api') }}"
                               class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('docs.api') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                API Documentation
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Monitoring & Health Section -->
                <div class="space-y-1 pt-4 border-t border-slate-700/50 mt-4">
                    <div x-show="!sidebarCollapsed" class="px-3 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        Monitoring & Health
                    </div>
                    <a href="{{ route('health.dashboard') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('health.*') ? 'bg-slate-800 text-white border-l-4 border-blue-500 ml-0 -ml-2 pl-5' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Health</span>
                    </a>
                    <a href="{{ route('analytics') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('analytics') ? 'bg-slate-800 text-white border-l-4 border-blue-500 ml-0 -ml-2 pl-5' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Analytics</span>
                    </a>

                    <!-- Logs with Dropdown -->
                    <div x-data="{ open: {{ request()->routeIs('logs.*') ? 'true' : 'false' }} }">
                        <button @click="open = !open"
                                class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('logs.*') ? 'bg-slate-800 text-white border-l-4 border-blue-500 ml-0 -ml-2 pl-5' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Logs</span>
                            </div>
                            <svg x-show="!sidebarCollapsed" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        <div x-show="open && !sidebarCollapsed" x-collapse class="ml-8 space-y-1 mt-1">
                            <a href="{{ route('logs.index') }}"
                               class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('logs.index') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Application
                            </a>
                            <a href="{{ route('logs.notifications') }}"
                               class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('logs.notifications') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Notifications
                            </a>
                            <a href="{{ route('logs.webhooks') }}"
                               class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('logs.webhooks') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Webhooks
                            </a>
                            <a href="{{ route('logs.security') }}"
                               class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('logs.security') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Security Audit
                            </a>
                            @if(request()->route('server'))
                            <div class="border-t border-slate-700 my-2"></div>
                            <a href="{{ route('servers.log-sources', request()->route('server')) }}"
                               class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('servers.log-sources') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                                    </svg>
                                    Server Log Sources
                                </span>
                            </a>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- DevOps Tools Section -->
                <div class="space-y-1 pt-4">
                    <div x-show="!sidebarCollapsed" class="px-3 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        DevOps Tools
                    </div>
                    <a href="{{ route('terminal') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('terminal') ? 'bg-slate-800 text-white border-l-4 border-blue-500 ml-0 -ml-2 pl-5' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">SSH Terminal</span>
                    </a>
                    <a href="{{ route('kubernetes.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('kubernetes.*') ? 'bg-slate-800 text-white border-l-4 border-blue-500 ml-0 -ml-2 pl-5' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Kubernetes</span>
                    </a>
                    <a href="{{ route('pipelines.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('pipelines.*') ? 'bg-slate-800 text-white border-l-4 border-blue-500 ml-0 -ml-2 pl-5' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Pipelines</span>
                    </a>
                    <a href="{{ route('scripts.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('scripts.*') ? 'bg-slate-800 text-white border-l-4 border-blue-500 ml-0 -ml-2 pl-5' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Scripts</span>
                    </a>
                </div>

                <!-- System & Admin Section -->
                <div class="space-y-1 pt-4 pb-4">
                    <div x-show="!sidebarCollapsed" class="px-3 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        System & Admin
                    </div>
                    <a href="{{ route('users.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('users.*') ? 'bg-slate-800 text-white border-l-4 border-blue-500 ml-0 -ml-2 pl-5' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Users</span>
                    </a>
                    <a href="{{ route('notifications.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('notifications.*') ? 'bg-slate-800 text-white border-l-4 border-blue-500 ml-0 -ml-2 pl-5' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Notifications</span>
                    </a>
                    <a href="{{ route('tenants.index') }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('tenants.*') ? 'bg-slate-800 text-white border-l-4 border-blue-500 ml-0 -ml-2 pl-5' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Multi-Tenant</span>
                    </a>

                    <!-- Settings with Dropdown -->
                    <div x-data="{ open: {{ request()->routeIs(['admin.*', 'settings.*', 'teams.*']) ? 'true' : 'false' }} }">
                        <button @click="open = !open"
                                class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-lg transition-colors {{ request()->routeIs(['admin.*', 'settings.*', 'teams.*']) ? 'bg-slate-800 text-white border-l-4 border-blue-500 ml-0 -ml-2 pl-5' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span x-show="!sidebarCollapsed" class="whitespace-nowrap">Settings</span>
                            </div>
                            <svg x-show="!sidebarCollapsed" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        <div x-show="open && !sidebarCollapsed" x-collapse class="ml-8 space-y-1 mt-1">
                            <!-- Priority Items -->
                            <a href="{{ route('settings.roles-permissions') }}"
                               class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('settings.roles-permissions') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Roles & Permissions
                            </a>
                            <a href="{{ route('teams.index') }}"
                               class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('teams.*') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Teams
                            </a>
                            <a href="{{ route('settings.preferences') }}"
                               class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('settings.preferences') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Preferences
                            </a>

                            <!-- System Management -->
                            <div class="border-t border-slate-700 my-2"></div>
                            <a href="{{ route('admin.system') }}"
                               class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.system') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                System Admin
                            </a>
                            <a href="{{ route('settings.system') }}"
                               class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('settings.system') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                System Settings
                            </a>
                            <a href="{{ route('settings.system-status') }}"
                               class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('settings.system-status') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                System Status
                            </a>
                            <a href="{{ route('settings.queue-monitor') }}"
                               class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('settings.queue-monitor') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Queue Monitor
                            </a>

                            <!-- Content Management -->
                            <div class="border-t border-slate-700 my-2"></div>
                            <a href="{{ route('admin.help-content') }}"
                               class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.help-content') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Help Content
                            </a>
                            <a href="{{ route('admin.templates') }}"
                               class="block px-3 py-2 rounded-lg text-sm {{ request()->routeIs('admin.templates') ? 'text-blue-400' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                                Templates
                            </a>
                        </div>
                    </div>
                </div>
            </nav>
        </aside>

        <!-- Mobile Sidebar Overlay -->
        <div x-show="mobileMenuOpen"
             @click="mobileMenuOpen = false"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/80 z-40 md:hidden"></div>

        <!-- Mobile Sidebar -->
        <aside x-show="mobileMenuOpen"
               x-transition:enter="transition ease-in-out duration-300 transform"
               x-transition:enter-start="-translate-x-full"
               x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in-out duration-300 transform"
               x-transition:leave-start="translate-x-0"
               x-transition:leave-end="-translate-x-full"
               class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 border-r border-slate-800 md:hidden overflow-y-auto">

            <!-- Mobile Logo -->
            <div class="flex items-center justify-between h-16 px-4 border-b border-slate-800">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-blue-400 to-cyan-400 rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-lg">D</span>
                    </div>
                    <span class="text-xl font-bold bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent">
                        DevFlow Pro
                    </span>
                </a>
                <button @click="mobileMenuOpen = false" class="p-2 rounded-lg hover:bg-slate-800 text-slate-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Mobile Navigation (same structure as desktop but without collapse) -->
            <nav class="py-4 px-2 space-y-1">
                <!-- Main -->
                <div class="px-3 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">Main</div>
                <a href="{{ route('home') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('home') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Home</span>
                </a>
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zM14 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1h-4a1 1 0 01-1-1v-3z"/>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <!-- Infrastructure -->
                <div class="px-3 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mt-4">Infrastructure</div>
                <a href="{{ route('servers.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('servers.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                    </svg>
                    <span>Servers</span>
                </a>
                <a href="{{ route('projects.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('projects.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                    <span>Projects</span>
                </a>
                <a href="{{ route('deployments.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('deployments.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                    <span>Deployments</span>
                </a>

                <!-- Other sections similarly simplified for mobile -->
            </nav>
        </aside>

        <!-- Top Bar -->
        <header :class="sidebarCollapsed ? 'left-16' : 'left-64'"
                class="fixed top-0 right-0 z-30 h-16 bg-slate-900 border-b border-slate-800 transition-all duration-300 hidden md:flex items-center justify-end px-6">

            <!-- Theme Toggle -->
            <button id="theme-toggle" class="p-2 rounded-lg hover:bg-slate-800 transition-colors mr-4" aria-label="Toggle theme">
                <svg id="theme-toggle-light-icon" class="w-5 h-5 text-slate-400 hover:text-white hidden dark:block transition-colors" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path>
                </svg>
                <svg id="theme-toggle-dark-icon" class="w-5 h-5 text-slate-400 hover:text-white block dark:hidden transition-colors" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                </svg>
            </button>

            <!-- User Menu -->
            <div class="flex items-center gap-3 pl-4 border-l border-slate-800">
                <span class="text-sm text-slate-400">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-sm text-slate-400 hover:text-red-400 transition-colors">
                        Logout
                    </button>
                </form>
            </div>
        </header>

        <!-- Mobile Top Bar -->
        <header class="fixed top-0 inset-x-0 z-30 h-16 bg-slate-900 border-b border-slate-800 flex md:hidden items-center justify-between px-4">
            <button @click="mobileMenuOpen = true" class="p-2 rounded-lg hover:bg-slate-800 text-slate-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                <div class="w-8 h-8 bg-gradient-to-br from-blue-400 to-cyan-400 rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-lg">D</span>
                </div>
                <span class="text-lg font-bold bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent">
                    DevFlow Pro
                </span>
            </a>

            <div class="flex items-center gap-2">
                <button id="theme-toggle-mobile" class="p-2 rounded-lg hover:bg-slate-800 transition-colors">
                    <svg class="w-5 h-5 text-slate-400 hover:text-white hidden dark:block" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path>
                    </svg>
                    <svg class="w-5 h-5 text-slate-400 hover:text-white block dark:hidden" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                    </svg>
                </button>
            </div>
        </header>

        <!-- Main Content -->
        <main id="main-content"
              :class="sidebarCollapsed ? 'md:ml-16' : 'md:ml-64'"
              class="transition-all duration-300 pt-16 md:pt-20 min-h-screen"
              tabindex="-1">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                @yield('content', $slot ?? '')
            </div>
        </main>
    </div>
    @endauth

    @guest
    <!-- Guest Layout (login/register pages) -->
    <main id="main-content" class="py-8" tabindex="-1">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @yield('content', $slot ?? '')
        </div>
    </main>
    @endguest

    <!-- Toast Notifications with Animations -->
    <x-toast-notification />

    <!-- Keyboard Shortcuts -->
    @auth
        <x-keyboard-shortcuts />
    @endauth

    <!-- Theme Toggle Script -->
    <script>
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeToggleMobileBtn = document.getElementById('theme-toggle-mobile');
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

        function toggleTheme() {
            const currentTheme = htmlElement.classList.contains('dark') ? 'dark' : 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
        }

        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', toggleTheme);
        }

        if (themeToggleMobileBtn) {
            themeToggleMobileBtn.addEventListener('click', toggleTheme);
        }
    </script>

    @livewireScripts
</body>
</html>
