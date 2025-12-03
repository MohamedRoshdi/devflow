import './bootstrap';
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

// Toast Notification System
window.showToast = function(message, type = 'info') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500'
    };
    
    toast.className = `${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 opacity-0 translate-y-2`;
    toast.textContent = message;
    
    container.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => {
        toast.classList.remove('opacity-0', 'translate-y-2');
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
};

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

