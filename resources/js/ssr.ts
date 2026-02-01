import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import type { DefineComponent } from 'vue';
import { createSSRApp, h } from 'vue';
import { renderToString } from 'vue/server-renderer';

const appName = import.meta.env.VITE_APP_NAME || 'P-Commerce';

createServer(
    (page) =>
        createInertiaApp({
            page,
            render: renderToString,
            title: (title) => (title ? `${title} - ${appName}` : appName),
            resolve: resolvePage,
            setup: ({ App, props, plugin }) => {
                const pinia = createPinia();
                const app = createSSRApp({ render: () => h(App, props) });
                
                app.use(plugin).use(pinia);
                
                return app;
            },
        }),
    { 
        cluster: true,
        port: 13714,
    },
);

function resolvePage(name: string) {
    const pagesGlob = import.meta.glob<DefineComponent>('./Pages/**/*.vue');

    return resolvePageComponent<DefineComponent>(`./Pages/${name}.vue`, pagesGlob);
}
