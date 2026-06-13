import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import inertia from '@inertiajs/vite'

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5173,
        // Страница отдаётся с nginx.mockwave.orb.local, а Vite-ассеты/HMR — с
        // localhost:5173 (порт проброшен на хост). Это кросс-домен, поэтому
        // разрешаем CORS для dev-сервера.
        cors: true,
        hmr: {
            host: 'localhost',
        },
    },
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
