// resources/js/bootstrap.js
import axios from 'axios';
import { useAuthStore } from './stores/auth';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Keycloak Interceptor
window.axios.interceptors.request.use(async (config) => {
    const authStore = useAuthStore();

    if (authStore.authenticated && authStore.keycloak.token) {
        try {
            // Refresh if token is expiring in < 30s
            await authStore.keycloak.updateToken(30);
            config.headers.Authorization = `Bearer ${authStore.keycloak.token}`;
        } catch (error) {
            console.error('Failed to refresh token', error);
            authStore.keycloak.login();
        }
    }
    return config;
});
