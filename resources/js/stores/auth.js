// resources/js/stores/auth.js
import Keycloak from 'keycloak-js';
import { defineStore } from 'pinia';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        keycloak: null,
        authenticated: false,
    }),
    actions: {
        async initKeycloak() {
            this.keycloak = new Keycloak({
                url: 'http://localhost:8080',
                realm: 'ecommerce',
                clientId: 'laravel-app',
            });

            try {
                this.authenticated = await this.keycloak.init({
                    onLoad: 'check-sso',
                    silentCheckSsoRedirectUri: window.location.origin + '/silent-check-sso.html',
                });
            } catch (error) {
                console.error('Keycloak init failed', error);
            }
        },
    },
});
