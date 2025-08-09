import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import path from 'path';


export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.tsx',
                'resources/css/app.css',
            ],
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
            '@components': path.resolve(__dirname, 'resources/js/components'),
            '@lib': path.resolve(__dirname, 'resources/js/lib'),
            '@ui': path.resolve(__dirname, 'resources/js/components/ui'),
            '@hooks': path.resolve(__dirname, 'resources/js/hooks'),
            '@assets': path.resolve(__dirname, 'resources/js/assets'),
            ziggy: path.resolve('vendor/tightenco/ziggy/dist'),
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor': ['react', 'react-dom', '@inertiajs/react'],
                    'ui': ['@radix-ui/react-dialog', '@radix-ui/react-dropdown-menu', '@radix-ui/react-select', '@radix-ui/react-popover'],
                    'charts': ['recharts'],
                }
            }
        },
        chunkSizeWarningLimit: 600, // Increase warning limit to 600 kB
    },
});