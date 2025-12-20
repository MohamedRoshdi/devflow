<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'DevFlow Pro - Deployment Platform' }}</title>
    <meta name="title" content="{{ $title ?? 'DevFlow Pro - Deployment Platform' }}">
    <meta name="description" content="Professional multi-project deployment and management platform. Automated DevOps, CI/CD pipelines, and infrastructure management.">
    <meta name="author" content="DevFlow Pro">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $title ?? 'DevFlow Pro - Deployment Platform' }}">
    <meta property="og:description" content="Professional multi-project deployment and management platform.">
    <meta property="og:image" content="{{ url('/images/devflow-og.svg') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:type" content="image/svg+xml">
    <meta property="og:site_name" content="DevFlow Pro">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? 'DevFlow Pro - Deployment Platform' }}">
    <meta name="twitter:description" content="Professional multi-project deployment and management platform.">
    <meta name="twitter:image" content="{{ url('/images/devflow-og.svg') }}">
    <meta name="theme-color" content="#2563eb">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.svg">

    <script>
        const theme = localStorage.getItem('theme') || 'light';
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors duration-200">
    {{ $slot }}

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const themeToggleBtn = document.getElementById('theme-toggle');
            const htmlElement = document.documentElement;

            function setTheme(theme) {
                if (theme === 'dark') {
                    htmlElement.classList.add('dark');
                    localStorage.setItem('theme', 'dark');
                } else {
                    htmlElement.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                }
            }

            if (themeToggleBtn) {
                themeToggleBtn.addEventListener('click', () => {
                    const currentTheme = htmlElement.classList.contains('dark') ? 'dark' : 'light';
                    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                    setTheme(newTheme);
                });
            }
        });
    </script>

    @livewireScripts
</body>
</html>

