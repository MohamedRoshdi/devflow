{{--
    Toast Notification Component with Animations

    Usage: Just include this component in your layout.
    It listens for Livewire events and session flash messages.

    Livewire events:
    - $this->dispatch('toast', type: 'success', message: 'Your message');
    - $this->dispatch('toast', type: 'error', message: 'Error message');
    - $this->dispatch('toast', type: 'warning', message: 'Warning message');
    - $this->dispatch('toast', type: 'info', message: 'Info message');

    Session flash:
    - session()->flash('success', 'Your message');
    - session()->flash('error', 'Error message');
--}}

<div x-data="toastNotifications()"
     x-init="init()"
     @toast.window="addToast($event.detail)"
     class="fixed bottom-4 right-4 z-50 space-y-3 pointer-events-none max-w-sm w-full px-4 sm:px-0"
     role="region"
     aria-label="Notifications"
     aria-live="polite"
     aria-atomic="false">

    {{-- Session Flash Messages - Using @js() for XSS-safe JavaScript escaping --}}
    @if (session('success'))
        <div x-init="addToast({ type: 'success', message: @js(session('success')) })"></div>
    @endif
    @if (session('error'))
        <div x-init="addToast({ type: 'error', message: @js(session('error')) })"></div>
    @endif
    @if (session('warning'))
        <div x-init="addToast({ type: 'warning', message: @js(session('warning')) })"></div>
    @endif
    @if (session('info'))
        <div x-init="addToast({ type: 'info', message: @js(session('info')) })"></div>
    @endif
    @if (session('connection_test'))
        <div x-init="addToast({ type: 'success', message: @js(session('connection_test')) })"></div>
    @endif
    @if (session('connection_error'))
        <div x-init="addToast({ type: 'error', message: @js(session('connection_error')) })"></div>
    @endif

    {{-- Toast Container --}}
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="toast.visible"
             x-transition:enter="transform ease-out duration-300 transition"
             x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
             x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-2 sm:translate-x-4"
             class="pointer-events-auto w-full overflow-hidden rounded-xl shadow-2xl ring-1 ring-black/5 dark:ring-white/10"
             :class="{
                 'bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/30 dark:to-emerald-900/30': toast.type === 'success',
                 'bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/30 dark:to-rose-900/30': toast.type === 'error',
                 'bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900/30 dark:to-yellow-900/30': toast.type === 'warning',
                 'bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30': toast.type === 'info'
             }"
             :role="toast.type === 'error' ? 'alert' : 'status'"
             :aria-live="toast.type === 'error' ? 'assertive' : 'polite'">
            <div class="p-4">
                <div class="flex items-start gap-3">
                    {{-- Animated Icon --}}
                    <div class="flex-shrink-0">
                        {{-- Success Icon with checkmark animation --}}
                        <div x-show="toast.type === 'success'" class="relative" aria-hidden="true">
                            <div class="w-10 h-10 rounded-full bg-green-500 dark:bg-green-600 flex items-center justify-center animate-bounce-once">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                          d="M5 13l4 4L19 7"
                                          class="animate-draw-check" />
                                </svg>
                            </div>
                            {{-- Success ripple effect --}}
                            <div class="absolute inset-0 rounded-full bg-green-500/30 animate-ping-once"></div>
                        </div>

                        {{-- Error Icon --}}
                        <div x-show="toast.type === 'error'" class="relative" aria-hidden="true">
                            <div class="w-10 h-10 rounded-full bg-red-500 dark:bg-red-600 flex items-center justify-center animate-shake">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                        </div>

                        {{-- Warning Icon --}}
                        <div x-show="toast.type === 'warning'" class="relative" aria-hidden="true">
                            <div class="w-10 h-10 rounded-full bg-amber-500 dark:bg-amber-600 flex items-center justify-center animate-pulse-once">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                        </div>

                        {{-- Info Icon --}}
                        <div x-show="toast.type === 'info'" class="relative" aria-hidden="true">
                            <div class="w-10 h-10 rounded-full bg-blue-500 dark:bg-blue-600 flex items-center justify-center animate-pulse-once">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {{-- Message Content --}}
                    <div class="flex-1 pt-0.5">
                        <p class="text-sm font-semibold"
                           :class="{
                               'text-green-800 dark:text-green-200': toast.type === 'success',
                               'text-red-800 dark:text-red-200': toast.type === 'error',
                               'text-amber-800 dark:text-amber-200': toast.type === 'warning',
                               'text-blue-800 dark:text-blue-200': toast.type === 'info'
                           }"
                           x-text="toast.title || (toast.type === 'success' ? 'Success!' : toast.type === 'error' ? 'Error' : toast.type === 'warning' ? 'Warning' : 'Info')">
                        </p>
                        <p class="mt-1 text-sm"
                           :class="{
                               'text-green-700 dark:text-green-300': toast.type === 'success',
                               'text-red-700 dark:text-red-300': toast.type === 'error',
                               'text-amber-700 dark:text-amber-300': toast.type === 'warning',
                               'text-blue-700 dark:text-blue-300': toast.type === 'info'
                           }"
                           x-text="toast.message">
                        </p>
                    </div>

                    {{-- Close Button --}}
                    <div class="flex-shrink-0">
                        <button @click="removeToast(toast.id)"
                                class="inline-flex rounded-lg p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors"
                                :class="{
                                    'text-green-500 hover:bg-green-100 dark:hover:bg-green-800/50 focus:ring-green-500': toast.type === 'success',
                                    'text-red-500 hover:bg-red-100 dark:hover:bg-red-800/50 focus:ring-red-500': toast.type === 'error',
                                    'text-amber-500 hover:bg-amber-100 dark:hover:bg-amber-800/50 focus:ring-amber-500': toast.type === 'warning',
                                    'text-blue-500 hover:bg-blue-100 dark:hover:bg-blue-800/50 focus:ring-blue-500': toast.type === 'info'
                                }">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Progress Bar --}}
                <div class="mt-3 h-1 w-full rounded-full overflow-hidden"
                     :class="{
                         'bg-green-200 dark:bg-green-800': toast.type === 'success',
                         'bg-red-200 dark:bg-red-800': toast.type === 'error',
                         'bg-amber-200 dark:bg-amber-800': toast.type === 'warning',
                         'bg-blue-200 dark:bg-blue-800': toast.type === 'info'
                     }">
                    <div class="h-full rounded-full transition-all duration-100 ease-linear animate-shrink-width"
                         :class="{
                             'bg-green-500 dark:bg-green-400': toast.type === 'success',
                             'bg-red-500 dark:bg-red-400': toast.type === 'error',
                             'bg-amber-500 dark:bg-amber-400': toast.type === 'warning',
                             'bg-blue-500 dark:bg-blue-400': toast.type === 'info'
                         }"
                         :style="'animation-duration: ' + (toast.duration || 5000) + 'ms'">
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<style>
    /* Success checkmark draw animation */
    @keyframes draw-check {
        0% {
            stroke-dasharray: 100;
            stroke-dashoffset: 100;
        }
        100% {
            stroke-dasharray: 100;
            stroke-dashoffset: 0;
        }
    }

    .animate-draw-check {
        stroke-dasharray: 100;
        stroke-dashoffset: 100;
        animation: draw-check 0.4s ease-out 0.2s forwards;
    }

    /* Bounce animation (once) */
    @keyframes bounce-once {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.1);
        }
    }

    .animate-bounce-once {
        animation: bounce-once 0.4s ease-out;
    }

    /* Ping animation (once) */
    @keyframes ping-once {
        0% {
            transform: scale(1);
            opacity: 0.5;
        }
        100% {
            transform: scale(1.5);
            opacity: 0;
        }
    }

    .animate-ping-once {
        animation: ping-once 0.6s ease-out forwards;
    }

    /* Shake animation for errors */
    @keyframes shake {
        0%, 100% {
            transform: translateX(0);
        }
        10%, 30%, 50%, 70%, 90% {
            transform: translateX(-2px);
        }
        20%, 40%, 60%, 80% {
            transform: translateX(2px);
        }
    }

    .animate-shake {
        animation: shake 0.5s ease-in-out;
    }

    /* Pulse animation (once) */
    @keyframes pulse-once {
        0%, 100% {
            opacity: 1;
            transform: scale(1);
        }
        50% {
            opacity: 0.8;
            transform: scale(1.05);
        }
    }

    .animate-pulse-once {
        animation: pulse-once 0.5s ease-in-out;
    }

    /* Progress bar shrink animation */
    @keyframes shrink-width {
        0% {
            width: 100%;
        }
        100% {
            width: 0%;
        }
    }

    .animate-shrink-width {
        animation: shrink-width 5s linear forwards;
    }
</style>

<script>
    function toastNotifications() {
        return {
            toasts: [],
            toastId: 0,

            init() {
                // Listen for Livewire events from older dispatch format
                Livewire.on('notification', (data) => {
                    this.addToast({
                        type: data.type || 'info',
                        message: data.message || '',
                        title: data.title || null,
                        duration: data.duration || 5000
                    });
                });
            },

            addToast(options) {
                const id = ++this.toastId;
                const toast = {
                    id: id,
                    type: options.type || 'info',
                    title: options.title || null,
                    message: options.message || '',
                    duration: options.duration || 5000,
                    visible: true
                };

                this.toasts.push(toast);

                // Auto-remove after duration
                setTimeout(() => {
                    this.removeToast(id);
                }, toast.duration);
            },

            removeToast(id) {
                const toast = this.toasts.find(t => t.id === id);
                if (toast) {
                    toast.visible = false;
                    // Remove from array after animation
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(t => t.id !== id);
                    }, 300);
                }
            }
        }
    }
</script>
