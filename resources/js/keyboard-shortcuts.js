/**
 * Keyboard Shortcuts Manager for DevFlow Pro
 * Handles global keyboard shortcuts and command palette
 */

class KeyboardShortcuts {
    constructor() {
        this.shortcuts = {
            // Navigation Shortcuts
            'cmd+d': { action: () => this.navigate('/dashboard'), description: 'Go to Dashboard' },
            'cmd+h': { action: () => this.navigate('/home'), description: 'Go to Home' },
            'cmd+s': { action: () => this.navigate('/servers'), description: 'Go to Servers' },
            'cmd+p': { action: () => this.navigate('/projects'), description: 'Go to Projects' },
            'cmd+e': { action: () => this.navigate('/deployments'), description: 'Go to Deployments' },

            // Action Shortcuts
            'cmd+n': { action: () => this.newProject(), description: 'Create New Project' },
            'cmd+k': { action: () => this.toggleCommandPalette(), description: 'Open Command Palette' },
            'cmd+/': { action: () => this.toggleShortcutsHelp(), description: 'Show Keyboard Shortcuts' },
            'cmd+r': { action: () => this.refreshPage(), description: 'Refresh Current Page' },

            // Search & Filter
            'cmd+f': { action: () => this.focusSearch(), description: 'Focus Search' },

            // Escape Actions
            'escape': { action: () => this.closeModals(), description: 'Close Modals/Overlays' }
        };

        this.commandPaletteOpen = false;
        this.helpModalOpen = false;
        this.init();
    }

    init() {
        document.addEventListener('keydown', (e) => this.handleKeyPress(e));
        this.createCommandPalette();
        this.createShortcutsHelp();
    }

    handleKeyPress(e) {
        const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
        const cmdKey = isMac ? e.metaKey : e.ctrlKey;

        // Build shortcut key string
        let shortcut = '';
        if (cmdKey) shortcut += 'cmd+';
        if (e.shiftKey) shortcut += 'shift+';
        if (e.altKey) shortcut += 'alt+';
        shortcut += e.key.toLowerCase();

        // Check if shortcut exists
        if (this.shortcuts[shortcut]) {
            // Don't prevent default for cmd+r (refresh)
            if (shortcut !== 'cmd+r') {
                e.preventDefault();
            }
            this.shortcuts[shortcut].action();
        }
    }

    navigate(path) {
        window.location.href = path;
    }

    newProject() {
        const createButton = document.querySelector('[data-action="create-project"]');
        if (createButton) {
            createButton.click();
        } else {
            // If no create button, go to projects page
            this.navigate('/projects');
        }
    }

    toggleCommandPalette() {
        const palette = document.getElementById('command-palette');
        if (palette) {
            this.commandPaletteOpen = !this.commandPaletteOpen;
            palette.classList.toggle('hidden', !this.commandPaletteOpen);

            if (this.commandPaletteOpen) {
                const input = palette.querySelector('input');
                if (input) input.focus();
            }
        }
    }

    toggleShortcutsHelp() {
        const helpModal = document.getElementById('shortcuts-help-modal');
        if (helpModal) {
            this.helpModalOpen = !this.helpModalOpen;
            helpModal.classList.toggle('hidden', !this.helpModalOpen);
        }
    }

    refreshPage() {
        // Allow default browser refresh
        return true;
    }

    focusSearch() {
        const searchInput = document.querySelector('input[type="search"], input[placeholder*="Search"]');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }

    closeModals() {
        // Close command palette
        if (this.commandPaletteOpen) {
            this.toggleCommandPalette();
        }

        // Close help modal
        if (this.helpModalOpen) {
            this.toggleShortcutsHelp();
        }

        // Close any other modals
        const modals = document.querySelectorAll('[data-modal], [x-data*="modal"]');
        modals.forEach(modal => {
            if (!modal.classList.contains('hidden')) {
                const closeButton = modal.querySelector('[data-close], [x-on\\:click*="close"]');
                if (closeButton) closeButton.click();
            }
        });
    }

    createCommandPalette() {
        const palette = document.createElement('div');
        palette.id = 'command-palette';
        palette.className = 'hidden fixed inset-0 z-50 overflow-y-auto';
        palette.setAttribute('x-data', '{ search: "" }');

        palette.innerHTML = `
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <!-- Backdrop -->
                <div class="fixed inset-0 transition-opacity bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75"
                     onclick="document.getElementById('command-palette').classList.add('hidden')"></div>

                <!-- Palette -->
                <div class="relative inline-block w-full max-w-2xl mx-auto mt-20 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-2xl rounded-2xl">
                    <!-- Search Input -->
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <input
                            type="text"
                            x-model="search"
                            placeholder="Type a command or search..."
                            class="w-full px-4 py-3 text-lg bg-transparent border-0 focus:ring-0 text-gray-900 dark:text-white placeholder-gray-400"
                            autofocus
                        >
                    </div>

                    <!-- Commands List -->
                    <div class="max-h-96 overflow-y-auto p-2">
                        ${this.renderCommandsList()}
                    </div>

                    <!-- Footer -->
                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>Navigate with ↑↓ arrows, select with Enter</span>
                            <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded">ESC</kbd>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(palette);
    }

    renderCommandsList() {
        const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
        const cmdSymbol = isMac ? '⌘' : 'Ctrl';

        return Object.entries(this.shortcuts)
            .map(([key, { description }]) => {
                const displayKey = key.replace('cmd', cmdSymbol).replace('+', ' + ').toUpperCase();
                return `
                    <button
                        class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors group"
                        onclick="window.keyboardShortcuts.shortcuts['${key}'].action(); document.getElementById('command-palette').classList.add('hidden');"
                    >
                        <span class="text-gray-900 dark:text-white">${description}</span>
                        <kbd class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600 group-hover:border-blue-500 dark:group-hover:border-blue-400">
                            ${displayKey}
                        </kbd>
                    </button>
                `;
            })
            .join('');
    }

    createShortcutsHelp() {
        const helpModal = document.createElement('div');
        helpModal.id = 'shortcuts-help-modal';
        helpModal.className = 'hidden fixed inset-0 z-50 overflow-y-auto';

        const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
        const cmdSymbol = isMac ? '⌘' : 'Ctrl';

        helpModal.innerHTML = `
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                <!-- Backdrop -->
                <div class="fixed inset-0 transition-opacity bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75"
                     onclick="document.getElementById('shortcuts-help-modal').classList.add('hidden')"></div>

                <!-- Modal -->
                <div class="relative inline-block w-full max-w-3xl mx-auto mt-20 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-2xl rounded-2xl">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Keyboard Shortcuts</h3>
                            <button
                                onclick="document.getElementById('shortcuts-help-modal').classList.add('hidden')"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="px-6 py-4 max-h-96 overflow-y-auto">
                        <div class="space-y-6">
                            ${this.renderShortcutsHelp(cmdSymbol)}
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Press <kbd class="px-2 py-1 text-xs bg-gray-200 dark:bg-gray-700 rounded">${cmdSymbol} + /</kbd> to show this dialog anytime
                        </p>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(helpModal);
    }

    renderShortcutsHelp(cmdSymbol) {
        const categories = {
            'Navigation': ['cmd+d', 'cmd+h', 'cmd+s', 'cmd+p', 'cmd+e'],
            'Actions': ['cmd+n', 'cmd+k', 'cmd+r', 'cmd+f'],
            'General': ['escape', 'cmd+/']
        };

        return Object.entries(categories)
            .map(([category, keys]) => `
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">${category}</h4>
                    <div class="space-y-2">
                        ${keys.map(key => {
                            const { description } = this.shortcuts[key];
                            const displayKey = key.replace('cmd', cmdSymbol).replace('+', ' + ').toUpperCase();
                            return `
                                <div class="flex items-center justify-between py-2">
                                    <span class="text-gray-900 dark:text-white">${description}</span>
                                    <kbd class="px-3 py-1.5 text-sm font-mono bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded border border-gray-300 dark:border-gray-600">
                                        ${displayKey}
                                    </kbd>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            `)
            .join('');
    }
}

// Initialize keyboard shortcuts when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.keyboardShortcuts = new KeyboardShortcuts();
    });
} else {
    window.keyboardShortcuts = new KeyboardShortcuts();
}

export default KeyboardShortcuts;
