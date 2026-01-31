import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig, type PluginOption } from 'vite';

export default defineConfig(async ({ mode }) => {
    const plugins: PluginOption[] = [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ];

    // Only load wayfinder in development (it's a dev dependency)
    if (mode === 'development') {
        try {
            const { wayfinder } = await import('@laravel/vite-plugin-wayfinder');
            plugins.push(
                wayfinder({
                    formVariants: true,
                })
            );
        } catch {
            // wayfinder not installed, skip
        }
    }

    return {
        plugins,
    };
});
