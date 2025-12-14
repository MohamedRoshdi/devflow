<div class="web-terminal-container" x-data="webTerminal(@this, {{ $server->id }})" x-init="init()">
    {{-- Terminal Header --}}
    <div class="terminal-header">
        <div class="terminal-controls">
            <div class="terminal-dot red"></div>
            <div class="terminal-dot yellow"></div>
            <div class="terminal-dot green"></div>
        </div>
        <div class="terminal-title">
            <x-heroicon-o-command-line class="w-4 h-4" />
            <span x-text="isConnected ? 'Terminal - {{ $server->name }} ({{ $server->ip_address }})' : 'Terminal - Disconnected'"></span>
        </div>
        <div class="terminal-actions">
            <button @click="clearTerminal()" class="terminal-action-btn" title="Clear Terminal">
                <x-heroicon-o-trash class="w-4 h-4" />
            </button>
            <button @click="toggleFullscreen()" class="terminal-action-btn" title="Fullscreen">
                <x-heroicon-o-arrows-pointing-out class="w-4 h-4" x-show="!isFullscreen" />
                <x-heroicon-o-arrows-pointing-in class="w-4 h-4" x-show="isFullscreen" x-cloak />
            </button>
            <button
                @click="isConnected ? disconnect() : connect()"
                :class="isConnected ? 'text-red-400 hover:text-red-300' : 'text-green-400 hover:text-green-300'"
                class="terminal-action-btn"
                :title="isConnected ? 'Disconnect' : 'Connect'"
            >
                <x-heroicon-o-signal class="w-4 h-4" x-show="!isConnected" />
                <x-heroicon-o-signal-slash class="w-4 h-4" x-show="isConnected" x-cloak />
            </button>
        </div>
    </div>

    {{-- Terminal Body --}}
    <div class="terminal-body" :class="{ 'terminal-fullscreen': isFullscreen }">
        <div id="terminal-{{ $server->id }}" class="terminal-instance" wire:ignore></div>

        {{-- Connection Overlay --}}
        <div x-show="!isConnected" x-cloak class="terminal-overlay">
            <div class="overlay-content">
                <x-heroicon-o-server class="w-16 h-16 text-gray-500 mb-4" />
                <h3 class="text-xl font-semibold text-gray-300 mb-2">Not Connected</h3>
                <p class="text-gray-500 mb-4">Click connect to establish SSH session with {{ $server->name }}</p>
                <button @click="connect()" class="connect-btn">
                    <x-heroicon-o-play class="w-5 h-5 mr-2" />
                    Connect to Server
                </button>
            </div>
        </div>

        {{-- Loading Overlay --}}
        <div x-show="isConnecting" x-cloak class="terminal-overlay">
            <div class="overlay-content">
                <div class="loading-spinner"></div>
                <p class="text-gray-400 mt-4">Establishing connection...</p>
            </div>
        </div>
    </div>

    {{-- Quick Commands Panel --}}
    <div class="quick-commands-panel" x-show="showQuickCommands" x-cloak x-transition>
        <div class="quick-commands-header">
            <span class="text-sm font-medium text-gray-300">Quick Commands</span>
            <button @click="showQuickCommands = false" class="text-gray-500 hover:text-gray-300">
                <x-heroicon-o-x-mark class="w-4 h-4" />
            </button>
        </div>
        <div class="quick-commands-grid">
            @foreach([
                ['cmd' => 'ls -la', 'label' => 'List Files', 'icon' => 'folder'],
                ['cmd' => 'pwd', 'label' => 'Current Dir', 'icon' => 'map-pin'],
                ['cmd' => 'df -h', 'label' => 'Disk Usage', 'icon' => 'circle-stack'],
                ['cmd' => 'free -h', 'label' => 'Memory', 'icon' => 'cpu-chip'],
                ['cmd' => 'docker ps', 'label' => 'Containers', 'icon' => 'cube'],
                ['cmd' => 'uptime', 'label' => 'Uptime', 'icon' => 'clock'],
                ['cmd' => 'ps aux | head -15', 'label' => 'Processes', 'icon' => 'queue-list'],
                ['cmd' => 'netstat -tulpn', 'label' => 'Ports', 'icon' => 'globe-alt'],
            ] as $qcmd)
            <button
                @click="executeQuickCommand('{{ $qcmd['cmd'] }}')"
                class="quick-cmd-btn"
                title="{{ $qcmd['cmd'] }}"
            >
                <x-dynamic-component :component="'heroicon-o-' . $qcmd['icon']" class="w-4 h-4" />
                <span>{{ $qcmd['label'] }}</span>
            </button>
            @endforeach
        </div>
    </div>

    {{-- Status Bar --}}
    <div class="terminal-statusbar">
        <div class="status-left">
            <span class="status-indicator" :class="isConnected ? 'connected' : 'disconnected'"></span>
            <span x-text="isConnected ? 'Connected' : 'Disconnected'" class="text-xs"></span>
        </div>
        <div class="status-center">
            <button @click="showQuickCommands = !showQuickCommands" class="status-btn">
                <x-heroicon-o-bolt class="w-3 h-3 mr-1" />
                Quick Commands
            </button>
        </div>
        <div class="status-right text-xs text-gray-500">
            <span>{{ $server->username }}@{{ $server->ip_address }}:{{ $server->port }}</span>
        </div>
    </div>
</div>

@push('styles')
<style>
    .web-terminal-container {
        display: flex;
        flex-direction: column;
        background: #1e1e1e;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        height: 600px;
    }

    .terminal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 12px;
        background: #323232;
        border-bottom: 1px solid #3c3c3c;
    }

    .terminal-controls {
        display: flex;
        gap: 6px;
    }

    .terminal-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }

    .terminal-dot.red { background: #ff5f56; }
    .terminal-dot.yellow { background: #ffbd2e; }
    .terminal-dot.green { background: #27ca40; }

    .terminal-title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #9d9d9d;
        font-size: 13px;
        font-weight: 500;
    }

    .terminal-actions {
        display: flex;
        gap: 4px;
    }

    .terminal-action-btn {
        padding: 4px 8px;
        border-radius: 4px;
        color: #9d9d9d;
        transition: all 0.2s;
    }

    .terminal-action-btn:hover {
        background: #3c3c3c;
        color: #fff;
    }

    .terminal-body {
        flex: 1;
        position: relative;
        overflow: hidden;
        background: #1e1e1e;
    }

    .terminal-body.terminal-fullscreen {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 9999;
        height: 100vh !important;
    }

    .terminal-instance {
        height: 100%;
        padding: 8px;
    }

    .terminal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(30, 30, 30, 0.95);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
    }

    .overlay-content {
        text-align: center;
    }

    .connect-btn {
        display: inline-flex;
        align-items: center;
        padding: 10px 20px;
        background: #0e7c42;
        color: #fff;
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.2s;
    }

    .connect-btn:hover {
        background: #0d6e3a;
        transform: translateY(-1px);
    }

    .loading-spinner {
        width: 48px;
        height: 48px;
        border: 3px solid #3c3c3c;
        border-top-color: #0e7c42;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .quick-commands-panel {
        background: #252525;
        border-top: 1px solid #3c3c3c;
        padding: 12px;
    }

    .quick-commands-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .quick-commands-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 8px;
    }

    .quick-cmd-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        background: #1e1e1e;
        border: 1px solid #3c3c3c;
        border-radius: 6px;
        color: #9d9d9d;
        font-size: 12px;
        transition: all 0.2s;
    }

    .quick-cmd-btn:hover {
        background: #2d2d2d;
        border-color: #0e7c42;
        color: #fff;
    }

    .terminal-statusbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 4px 12px;
        background: #007acc;
        color: #fff;
        font-size: 12px;
    }

    .status-left, .status-right {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .status-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    .status-indicator.connected { background: #27ca40; }
    .status-indicator.disconnected { background: #ff5f56; }

    .status-btn {
        display: flex;
        align-items: center;
        padding: 2px 8px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 4px;
        transition: background 0.2s;
    }

    .status-btn:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    /* xterm.js customizations */
    .xterm {
        padding: 8px;
    }

    .xterm-viewport {
        overflow-y: auto !important;
    }

    .xterm-viewport::-webkit-scrollbar {
        width: 10px;
    }

    .xterm-viewport::-webkit-scrollbar-track {
        background: #1e1e1e;
    }

    .xterm-viewport::-webkit-scrollbar-thumb {
        background: #3c3c3c;
        border-radius: 5px;
    }

    .xterm-viewport::-webkit-scrollbar-thumb:hover {
        background: #4c4c4c;
    }
</style>
@endpush

@push('scripts')
<script type="module">
import { Terminal } from '@xterm/xterm';
import { FitAddon } from '@xterm/addon-fit';
import { WebLinksAddon } from '@xterm/addon-web-links';

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
            this.terminal = new Terminal({
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

            this.fitAddon = new FitAddon();
            this.terminal.loadAddon(this.fitAddon);
            this.terminal.loadAddon(new WebLinksAddon());

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
</script>
@endpush
