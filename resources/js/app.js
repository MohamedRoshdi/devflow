import './bootstrap';
import './keyboard-shortcuts';
import Sortable from 'sortablejs';
import Chart from 'chart.js/auto';
import { Terminal } from '@xterm/xterm';
import { FitAddon } from '@xterm/addon-fit';
import { WebLinksAddon } from '@xterm/addon-web-links';

// Livewire v3 includes Alpine.js - don't import it separately!
// Alpine is available via Livewire's bundle

// Make xterm available globally for the web terminal
window.Terminal = Terminal;
window.FitAddon = FitAddon;
window.WebLinksAddon = WebLinksAddon;

// Debug mode flag - only log in development
const DEBUG = import.meta.env.DEV || false;

// Debug logger function - conditionally logs based on environment
function debugLog(...args) {
    if (DEBUG) {
        console.log(...args);
    }
}

// Make Sortable and Chart.js available globally
window.Sortable = Sortable;
window.Chart = Chart;

// Dashboard Widget Drag-and-Drop
window.initDashboardSortable = function() {
    const widgetContainer = document.getElementById('dashboard-widgets');
    if (!widgetContainer) return;

    new Sortable(widgetContainer, {
        animation: 150,
        handle: '.widget-drag-handle',
        ghostClass: 'opacity-50',
        dragClass: 'shadow-2xl',
        chosenClass: 'ring-2 ring-blue-500',
        onEnd: function(evt) {
            // Get new order of widget IDs
            const widgets = widgetContainer.querySelectorAll('[data-widget-id]');
            const newOrder = Array.from(widgets).map(w => w.dataset.widgetId);

            // Send to Livewire
            if (window.Livewire) {
                Livewire.dispatch('widget-order-updated', { order: newOrder });
            }
        }
    });
};

// Initialize on page load and after Livewire updates
document.addEventListener('DOMContentLoaded', () => {
    window.initDashboardSortable();
});

document.addEventListener('livewire:navigated', () => {
    window.initDashboardSortable();
});

// Enhanced Toast Notification System
window.showToast = function(message, type = 'info', duration = 5000) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast toast-${type} toast-enter relative overflow-hidden`;

    const icons = {
        success: `<svg class="w-6 h-6 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>`,
        error: `<svg class="w-6 h-6 text-red-600 dark:text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>`,
        warning: `<svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>`,
        info: `<svg class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>`
    };

    toast.innerHTML = `
        ${icons[type]}
        <div class="flex-1">
            <p class="text-sm font-medium text-gray-900 dark:text-white">${message}</p>
        </div>
        <button onclick="this.parentElement.remove()" class="flex-shrink-0 ml-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
        <div class="toast-progress" style="animation-duration: ${duration}ms"></div>
    `;

    container.appendChild(toast);

    // Trigger enter animation
    requestAnimationFrame(() => {
        toast.classList.remove('toast-enter');
        toast.classList.add('toast-enter-active');
    });

    // Auto remove
    const timeoutId = setTimeout(() => {
        removeToast(toast);
    }, duration);

    // Allow manual close to cancel auto-remove
    toast.querySelector('button').addEventListener('click', () => {
        clearTimeout(timeoutId);
    });
};

function removeToast(toast) {
    toast.classList.remove('toast-enter-active');
    toast.classList.add('toast-exit', 'toast-exit-active');

    setTimeout(() => {
        toast.remove();
    }, 300);
}

// Listen for Livewire toast events
document.addEventListener('livewire:initialized', () => {
    Livewire.on('toast', (event) => {
        const { message, type, duration } = event;
        window.showToast(message, type || 'info', duration || 5000);
    });
});

// Real-time monitoring with Laravel Reverb WebSockets
document.addEventListener('DOMContentLoaded', () => {
    // Wait for Echo to be initialized
    setTimeout(() => {
        if (window.Echo) {
            debugLog('Connecting to WebSocket channel: dashboard');

            // Listen on public dashboard channel for deployment updates
            window.Echo.channel('dashboard')
                .listen('DeploymentStarted', (e) => {
                    debugLog('DeploymentStarted event received:', e);
                    showToast(`Deployment started for ${e.project_name}`, 'info');
                    if (window.Livewire) {
                        Livewire.dispatch('refresh-dashboard');
                    }
                })
                .listen('DeploymentCompleted', (e) => {
                    debugLog('DeploymentCompleted event received:', e);
                    showToast(`Deployment completed for ${e.project_name}`, 'success');
                    if (window.Livewire) {
                        Livewire.dispatch('deployment-completed');
                    }
                })
                .listen('DeploymentFailed', (e) => {
                    debugLog('DeploymentFailed event received:', e);
                    showToast(`Deployment failed for ${e.project_name}: ${e.error_message || 'Unknown error'}`, 'error');
                    if (window.Livewire) {
                        Livewire.dispatch('refresh-dashboard');
                    }
                })
                .listen('DashboardUpdated', (e) => {
                    debugLog('DashboardUpdated event received:', e);
                    if (window.Livewire) {
                        Livewire.dispatch('refresh-dashboard');
                    }
                });
        }
    }, 1000); // Give Echo time to initialize
});

// Service Worker Registration for PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js')
            .then(registration => {
                debugLog('SW registered:', registration);
            })
            .catch(error => {
                debugLog('SW registration failed:', error);
            });
    });
}

// GPS Location Tracking (with permission)
window.trackLocation = function() {
    if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                return {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude
                };
            },
            (error) => {
                debugLog('Error getting location:', error);
            }
        );
    }
};

// Web Terminal Alpine.js Component
document.addEventListener('alpine:init', () => {
    Alpine.data('webTerminal', (livewire, serverId) => ({
        terminal: null,
        fitAddon: null,
        isConnected: false,
        isConnecting: false,
        isFullscreen: false,
        showQuickCommands: false,
        currentLine: '',
        prompt: '',
        cursorPosition: 0,

        init() {
            this.initTerminal();
            this.setupEventListeners();
            this.setupLivewireListeners();

            // Auto-fit on window resize
            window.addEventListener('resize', () => {
                if (this.fitAddon) {
                    this.fitAddon.fit();
                }
            });
        },

        initTerminal() {
            this.terminal = new window.Terminal({
                theme: {
                    background: '#1e1e1e',
                    foreground: '#d4d4d4',
                    cursor: '#aeafad',
                    cursorAccent: '#1e1e1e',
                    selection: 'rgba(255, 255, 255, 0.3)',
                    black: '#000000',
                    red: '#cd3131',
                    green: '#0dbc79',
                    yellow: '#e5e510',
                    blue: '#2472c8',
                    magenta: '#bc3fbc',
                    cyan: '#11a8cd',
                    white: '#e5e5e5',
                    brightBlack: '#666666',
                    brightRed: '#f14c4c',
                    brightGreen: '#23d18b',
                    brightYellow: '#f5f543',
                    brightBlue: '#3b8eea',
                    brightMagenta: '#d670d6',
                    brightCyan: '#29b8db',
                    brightWhite: '#ffffff'
                },
                fontFamily: '"Cascadia Code", "Fira Code", "JetBrains Mono", Menlo, Monaco, "Courier New", monospace',
                fontSize: 14,
                lineHeight: 1.2,
                cursorBlink: true,
                cursorStyle: 'bar',
                scrollback: 10000,
                allowProposedApi: true
            });

            this.fitAddon = new window.FitAddon();
            this.terminal.loadAddon(this.fitAddon);
            this.terminal.loadAddon(new window.WebLinksAddon());

            const container = document.getElementById(`terminal-${serverId}`);
            if (container) {
                this.terminal.open(container);
                this.fitAddon.fit();
            }
        },

        setupEventListeners() {
            this.terminal.onData(data => {
                if (!this.isConnected) return;

                // Handle special keys
                switch (data) {
                    case '\r': // Enter
                        this.terminal.write('\r\n');
                        this.executeCurrentLine();
                        break;
                    case '\u007F': // Backspace
                        if (this.cursorPosition > 0) {
                            this.currentLine = this.currentLine.slice(0, this.cursorPosition - 1) + this.currentLine.slice(this.cursorPosition);
                            this.cursorPosition--;
                            this.terminal.write('\b \b');
                        }
                        break;
                    case '\u001b[A': // Arrow Up
                        livewire.dispatch('get-history-up');
                        break;
                    case '\u001b[B': // Arrow Down
                        livewire.dispatch('get-history-down');
                        break;
                    case '\u001b[C': // Arrow Right
                        if (this.cursorPosition < this.currentLine.length) {
                            this.cursorPosition++;
                            this.terminal.write(data);
                        }
                        break;
                    case '\u001b[D': // Arrow Left
                        if (this.cursorPosition > 0) {
                            this.cursorPosition--;
                            this.terminal.write(data);
                        }
                        break;
                    case '\u0003': // Ctrl+C
                        this.terminal.write('^C\r\n');
                        this.currentLine = '';
                        this.cursorPosition = 0;
                        this.writePrompt();
                        break;
                    case '\u000C': // Ctrl+L
                        this.clearTerminal();
                        break;
                    default:
                        // Regular character input
                        if (data >= String.fromCharCode(0x20) && data <= String.fromCharCode(0x7E)) {
                            this.currentLine = this.currentLine.slice(0, this.cursorPosition) + data + this.currentLine.slice(this.cursorPosition);
                            this.cursorPosition++;
                            this.terminal.write(data);
                        }
                }
            });
        },

        setupLivewireListeners() {
            Livewire.on('terminal-connected', ({ message }) => {
                this.isConnected = true;
                this.isConnecting = false;
                this.terminal.clear();
                this.terminal.write(message);
                this.writePrompt();
            });

            Livewire.on('terminal-disconnected', () => {
                this.isConnected = false;
                this.terminal.write('\r\n\x1b[31mDisconnected from server.\x1b[0m\r\n');
            });

            Livewire.on('terminal-output', ({ output, exitCode }) => {
                if (output) {
                    this.terminal.write(output);
                    if (!output.endsWith('\n')) {
                        this.terminal.write('\r\n');
                    }
                }
                this.writePrompt();
            });

            Livewire.on('terminal-error', ({ message }) => {
                this.isConnecting = false;
                this.terminal.write(`\x1b[31m${message}\x1b[0m\r\n`);
            });

            Livewire.on('terminal-clear', () => {
                this.terminal.clear();
                this.writePrompt();
            });

            Livewire.on('set-command', ({ command }) => {
                // Clear current line
                const clearChars = this.currentLine.length;
                this.terminal.write('\b'.repeat(clearChars) + ' '.repeat(clearChars) + '\b'.repeat(clearChars));

                // Write new command
                this.currentLine = command;
                this.cursorPosition = command.length;
                this.terminal.write(command);
            });
        },

        writePrompt() {
            this.currentLine = '';
            this.cursorPosition = 0;
            this.terminal.write('\x1b[32m$\x1b[0m ');
        },

        executeCurrentLine() {
            const command = this.currentLine.trim();
            this.currentLine = '';
            this.cursorPosition = 0;

            if (command) {
                livewire.dispatch('terminal-command', { command });
            } else {
                this.writePrompt();
            }
        },

        connect() {
            this.isConnecting = true;
            livewire.dispatch('terminal-connect');
        },

        disconnect() {
            livewire.dispatch('terminal-disconnect');
        },

        clearTerminal() {
            this.terminal.clear();
            if (this.isConnected) {
                this.writePrompt();
            }
        },

        toggleFullscreen() {
            this.isFullscreen = !this.isFullscreen;
            this.$nextTick(() => {
                if (this.fitAddon) {
                    this.fitAddon.fit();
                }
            });
        },

        executeQuickCommand(cmd) {
            if (!this.isConnected) {
                this.connect();
                setTimeout(() => this.executeQuickCommand(cmd), 1000);
                return;
            }

            this.currentLine = cmd;
            this.terminal.write(cmd + '\r\n');
            livewire.dispatch('terminal-command', { command: cmd });
        }
    }));
});

