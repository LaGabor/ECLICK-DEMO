import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/filament/user-panel-utilities.css',
                'resources/js/app.js',
                'resources/js/media-image-preview.js',
            ],
            refresh: true,
        }),
    ],
});
