import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import i18n from './i18n';
import { createPinia } from 'pinia';
import { createSSRApp, h } from 'vue';
import { renderToString } from 'vue/server-renderer';

const appName = import.meta.env.VITE_APP_NAME || 'P-Commerce';

// Eager glob so SSR bundle includes all page modules; path must match actual dir (pages/)
const pages = import.meta.glob<{ default: import('vue').DefineComponent }>('./pages/**/*.vue', {
    eager: true,
});

createServer(
    (page) =>
        createInertiaApp({
            page,
            render: renderToString,
            title: (title) => (title ? `${title} - ${appName}` : appName),
            resolve: (name: string) => {
                const key = `./pages/${name}.vue`;
                const mod = pages[key];
                if (!mod?.default) {
                    throw new Error(`Page not found: ${key}`);
                }
                return Promise.resolve(mod.default);
            },
            setup: ({ App, props, plugin }) => {
                const pinia = createPinia();
                const app = createSSRApp({ render: () => h(App, props) });

                app.use(plugin).use(pinia).use(i18n);

                return app;
            },
        }),
    {
        cluster: true,
        port: 13714,
    },
);
