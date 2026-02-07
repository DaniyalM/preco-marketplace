import { useColorMode, tryOnMounted } from '@vueuse/core';

const COOKIE_NAME = 'theme';
const COOKIE_MAX_AGE = 365 * 24 * 60 * 60; // 1 year

function setThemeCookie(value: string) {
    if (typeof document === 'undefined') return;
    document.cookie = `${COOKIE_NAME}=${encodeURIComponent(value)};path=/;max-age=${COOKIE_MAX_AGE};SameSite=Lax`;
}

/**
 * Theme mode: 'light' | 'dark' | 'auto' (system).
 * Persists to localStorage (via VueUse) and to cookie for server-side initial paint.
 */
export function useTheme() {
    const colorMode = useColorMode({
        selector: 'html',
        attribute: 'class',
        storageKey: 'theme',
        initialValue: 'auto',
        modes: {
            light: 'light',
            dark: 'dark',
            auto: '',
        },
        onChanged(modeValue, defaultHandler) {
            defaultHandler(modeValue);
            setThemeCookie(modeValue);
        },
    });

    tryOnMounted(() => {
        setThemeCookie(colorMode.store?.value ?? 'auto');
    });

    return {
        /** Current effective mode (light/dark/auto). When 'auto', follows system. */
        mode: colorMode,
        /** User-stored preference */
        store: colorMode.store,
        /** System preference (light/dark) */
        system: colorMode.system,
        setLight: () => { colorMode.value = 'light'; },
        setDark: () => { colorMode.value = 'dark'; },
        setSystem: () => { colorMode.value = 'auto'; },
    };
}
