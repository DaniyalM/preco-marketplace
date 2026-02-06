import { VueQueryPlugin, QueryClient } from '@tanstack/vue-query';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import '../css/app.css';
import i18n from './i18n';
import { useAuthStore } from './stores/auth';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Enable View Transitions API for smooth page navigation
const setupViewTransitions = () => {
    // Check if View Transitions API is supported
    if (!document.startViewTransition) {
        return;
    }

    let skipNextTransition = false;

    // Hook into Inertia's navigation events
    router.on('before', (event) => {
        // Skip transition for form submissions or when explicitly disabled
        const isFormSubmission = event.detail.visit.method !== 'get';
        skipNextTransition = isFormSubmission;
    });

    router.on('navigate', () => {
        if (skipNextTransition) {
            skipNextTransition = false;
            return;
        }

        // The view transition is handled by Inertia's built-in mechanism
        // We just need to ensure smooth animations via CSS
    });
};

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();
        const queryClient = new QueryClient({
            defaultOptions: {
                queries: {
                    staleTime: 1000 * 60, // 1 minute
                },
            },
        });
        const app = createApp({ render: () => h(App, props) });

        app.use(plugin).use(pinia).use(i18n).use(VueQueryPlugin, { queryClient });

        // Initialize auth store (stateless - reads from Inertia shared props)
        const authStore = useAuthStore();
        authStore.init();

        app.mount(el);

        // Setup view transitions after app is mounted
        setupViewTransitions();
    },
    progress: {
        color: '#4B5563',
        showSpinner: true,
    },
});
