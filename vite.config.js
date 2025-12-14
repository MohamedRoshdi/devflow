import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        // Optimize build output
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true, // Remove console.log in production
                drop_debugger: true,
            },
        },
        // Code splitting for better caching
        rollupOptions: {
            output: {
                manualChunks: {
                    // Vendor chunk for third-party libraries
                    vendor: ['alpinejs'],
                    // Chart libraries in separate chunk if using charts
                    // charts: ['chart.js'],
                },
            },
        },
        // Increase chunk size warning limit
        chunkSizeWarningLimit: 1000,
        // Enable source maps in development only
        sourcemap: process.env.NODE_ENV !== 'production',
    },
    // Optimize dependencies
    optimizeDeps: {
        include: ['alpinejs', '@xterm/xterm', '@xterm/addon-fit', '@xterm/addon-web-links'],
        exclude: [],
    },
    // Enable CSS code splitting
    css: {
        devSourcemap: true,
    },
});


