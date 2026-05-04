import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import inertia from '@inertiajs/vite'

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.tsx',
            refresh: true,
        }),
        tailwindcss(),
        react(),
        inertia(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
})
