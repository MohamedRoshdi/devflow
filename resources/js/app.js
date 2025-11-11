import './bootstrap';

// Livewire v3 includes Alpine.js - don't import it separately!
// Alpine is available via Livewire's bundle

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

// Real-time monitoring with Pusher/WebSockets
if (window.Echo) {
    // Listen for deployment updates
    window.Echo.private(`user.${window.userId}`)
        .listen('DeploymentStarted', (e) => {
            showToast(`Deployment started for ${e.project.name}`, 'info');
        })
        .listen('DeploymentCompleted', (e) => {
            showToast(`Deployment completed for ${e.project.name}`, 'success');
            Livewire.dispatch('refresh-dashboard');
        })
        .listen('DeploymentFailed', (e) => {
            showToast(`Deployment failed for ${e.project.name}`, 'error');
            Livewire.dispatch('refresh-dashboard');
        });
}

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

