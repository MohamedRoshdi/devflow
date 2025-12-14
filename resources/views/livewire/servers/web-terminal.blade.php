<div class="web-terminal-container" x-data="webTerminal(@this, {{ $server->id }})" x-init="init()">
    {{-- Terminal Header --}}
    <div class="terminal-header">
        <div class="terminal-controls">
            <div class="terminal-dot red"></div>
            <div class="terminal-dot yellow"></div>
            <div class="terminal-dot green"></div>
        </div>
        <div class="terminal-title">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span x-text="isConnected ? 'Terminal - {{ $server->name }} ({{ $server->ip_address }})' : 'Terminal - Disconnected'"></span>
        </div>
        <div class="terminal-actions">
            <button @click="clearTerminal()" class="terminal-action-btn" title="Clear Terminal">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
            <button @click="toggleFullscreen()" class="terminal-action-btn" title="Fullscreen">
                <svg class="w-4 h-4" x-show="!isFullscreen" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                </svg>
                <svg class="w-4 h-4" x-show="isFullscreen" x-cloak fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25"/>
                </svg>
            </button>
            <button
                @click="isConnected ? disconnect() : connect()"
                :class="isConnected ? 'text-red-400 hover:text-red-300' : 'text-green-400 hover:text-green-300'"
                class="terminal-action-btn"
                :title="isConnected ? 'Disconnect' : 'Connect'"
            >
                <svg class="w-4 h-4" x-show="!isConnected" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                </svg>
                <svg class="w-4 h-4" x-show="isConnected" x-cloak fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Terminal Body --}}
    <div class="terminal-body" :class="{ 'terminal-fullscreen': isFullscreen }">
        <div id="terminal-{{ $server->id }}" class="terminal-instance" wire:ignore></div>

        {{-- Connection Overlay --}}
        <div x-show="!isConnected" x-cloak class="terminal-overlay">
            <div class="overlay-content">
                <svg class="w-16 h-16 text-gray-500 mb-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-300 mb-2">Not Connected</h3>
                <p class="text-gray-500 mb-4">Click connect to establish SSH session with {{ $server->name }}</p>
                <button @click="connect()" class="connect-btn">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
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
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="quick-commands-grid">
            <button @click="executeQuickCommand('ls -la')" class="quick-cmd-btn" title="ls -la">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
                <span>List Files</span>
            </button>
            <button @click="executeQuickCommand('pwd')" class="quick-cmd-btn" title="pwd">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>Current Dir</span>
            </button>
            <button @click="executeQuickCommand('df -h')" class="quick-cmd-btn" title="df -h">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                </svg>
                <span>Disk Usage</span>
            </button>
            <button @click="executeQuickCommand('free -h')" class="quick-cmd-btn" title="free -h">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                </svg>
                <span>Memory</span>
            </button>
            <button @click="executeQuickCommand('docker ps')" class="quick-cmd-btn" title="docker ps">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <span>Containers</span>
            </button>
            <button @click="executeQuickCommand('uptime')" class="quick-cmd-btn" title="uptime">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Uptime</span>
            </button>
            <button @click="executeQuickCommand('ps aux | head -15')" class="quick-cmd-btn" title="ps aux | head -15">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                <span>Processes</span>
            </button>
            <button @click="executeQuickCommand('netstat -tulpn')" class="quick-cmd-btn" title="netstat -tulpn">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
                <span>Ports</span>
            </button>
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
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Quick Commands
            </button>
        </div>
        <div class="status-right text-xs text-gray-500">
            <span>{{ $server->username . '@' . $server->ip_address . ':' . $server->port }}</span>
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
        margin: 0 auto;
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

{{-- Alpine component and xterm.js are loaded from app.js --}}
