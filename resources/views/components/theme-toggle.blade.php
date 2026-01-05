@props(['class' => ''])

<div x-data="themeToggle()" class="{{ $class }}">
    <button
        @click="toggle()"
        class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400"
        aria-label="Toggle theme"
        x-cloak
    >
        <!-- Light Mode Icon (Shown in Dark Mode) -->
        <svg
            x-show="theme === 'dark'"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 rotate-90"
            x-transition:enter-end="opacity-100 rotate-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 rotate-0"
            x-transition:leave-end="opacity-0 rotate-90"
            class="w-5 h-5 text-gray-700 dark:text-gray-300"
            fill="currentColor"
            viewBox="0 0 20 20"
            aria-hidden="true"
        >
            <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path>
        </svg>

        <!-- Dark Mode Icon (Shown in Light Mode) -->
        <svg
            x-show="theme === 'light'"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 rotate-90"
            x-transition:enter-end="opacity-100 rotate-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 rotate-0"
            x-transition:leave-end="opacity-0 rotate-90"
            class="w-5 h-5 text-gray-700 dark:text-gray-300"
            fill="currentColor"
            viewBox="0 0 20 20"
            aria-hidden="true"
        >
            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
        </svg>

        <!-- System Mode Icon (If implementing system preference) -->
        <svg
            x-show="theme === 'system'"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 rotate-90"
            x-transition:enter-end="opacity-100 rotate-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 rotate-0"
            x-transition:leave-end="opacity-0 rotate-90"
            class="w-5 h-5 text-gray-700 dark:text-gray-300"
            fill="currentColor"
            viewBox="0 0 20 20"
            aria-hidden="true"
        >
            <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd" />
        </svg>
    </button>
</div>

<script>
    function themeToggle() {
        return {
            theme: localStorage.getItem('theme') || 'light',

            init() {
                this.applyTheme();

                // Watch for system preference changes
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                    if (this.theme === 'system') {
                        this.applyTheme();
                    }
                });
            },

            toggle() {
                // Cycle through: light -> dark -> system -> light
                if (this.theme === 'light') {
                    this.theme = 'dark';
                } else if (this.theme === 'dark') {
                    this.theme = 'system';
                } else {
                    this.theme = 'light';
                }

                localStorage.setItem('theme', this.theme);
                this.applyTheme();
            },

            applyTheme() {
                let isDark = false;

                if (this.theme === 'dark') {
                    isDark = true;
                } else if (this.theme === 'system') {
                    isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                }

                if (isDark) {
                    document.documentElement.classList.add('dark');
                    document.getElementById('theme-color-meta')?.setAttribute('content', '#1f2937');
                } else {
                    document.documentElement.classList.remove('dark');
                    document.getElementById('theme-color-meta')?.setAttribute('content', '#2563eb');
                }
            }
        }
    }
</script>
