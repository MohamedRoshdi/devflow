import './bootstrap';
import './keyboard-shortcuts';
import Sortable from 'sortablejs';
import Chart from 'chart.js/auto';

// Livewire v3 includes Alpine.js - don't import it separately!
// Alpine is available via Livewire's bundle

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
            console.log('Connecting to WebSocket channel: dashboard');

            // Listen on public dashboard channel for deployment updates
            window.Echo.channel('dashboard')
                .listen('DeploymentStarted', (e) => {
                    console.log('DeploymentStarted event received:', e);
                    showToast(`Deployment started for ${e.project_name}`, 'info');
                    if (window.Livewire) {
                        Livewire.dispatch('refresh-dashboard');
                    }
                })
                .listen('DeploymentCompleted', (e) => {
                    console.log('DeploymentCompleted event received:', e);
                    showToast(`Deployment completed for ${e.project_name}`, 'success');
                    if (window.Livewire) {
                        Livewire.dispatch('deployment-completed');
                    }
                })
                .listen('DeploymentFailed', (e) => {
                    console.log('DeploymentFailed event received:', e);
                    showToast(`Deployment failed for ${e.project_name}: ${e.error_message || 'Unknown error'}`, 'error');
                    if (window.Livewire) {
                        Livewire.dispatch('refresh-dashboard');
                    }
                })
                .listen('DashboardUpdated', (e) => {
                    console.log('DashboardUpdated event received:', e);
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
                console.log('SW registered:', registration);
            })
            .catch(error => {
                console.log('SW registration failed:', error);
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
                console.error('Error getting location:', error);
            }
        );
    }
};

