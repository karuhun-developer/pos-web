import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import path from 'node:path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(import.meta.dirname, 'resources/js'),
            // Ziggy diambil dari paket composer-nya, bukan dari npm: versinya
            // ikut tightenco/ziggy yang meng-generate objek Ziggy di
            // app.blade.php, jadi keduanya tidak bisa melenceng versi.
            'ziggy-js': path.resolve(import.meta.dirname, 'vendor/tightenco/ziggy'),
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
