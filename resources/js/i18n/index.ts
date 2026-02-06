import { createI18n } from 'vue-i18n';
import en from './locales/en';
import ar from './locales/ar';

export const SUPPORTED_LOCALES = ['en', 'ar'] as const;
export type SupportedLocale = (typeof SUPPORTED_LOCALES)[number];

const RTL_LOCALES: SupportedLocale[] = ['ar'];

export function isRtl(locale: string): boolean {
    return RTL_LOCALES.includes(locale as SupportedLocale);
}

const STORAGE_KEY = 'pcommerce_locale';

export function getStoredLocale(): SupportedLocale | null {
    if (typeof window === 'undefined') return null;
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored && SUPPORTED_LOCALES.includes(stored as SupportedLocale)) {
        return stored as SupportedLocale;
    }
    return null;
}

export function setStoredLocale(locale: SupportedLocale): void {
    if (typeof window === 'undefined') return;
    localStorage.setItem(STORAGE_KEY, locale);
}

function applyLocaleToDocument(locale: string): void {
    if (typeof document === 'undefined') return;
    const html = document.documentElement;
    html.setAttribute('lang', locale === 'ar' ? 'ar' : 'en');
    html.setAttribute('dir', isRtl(locale) ? 'rtl' : 'ltr');
}

export const i18n = createI18n({
    legacy: false,
    locale: getStoredLocale() ?? 'en',
    fallbackLocale: 'en',
    messages: {
        en,
        ar,
    },
    globalInjection: true,
});

export function setLocale(locale: SupportedLocale): void {
    i18n.global.locale.value = locale;
    setStoredLocale(locale);
    applyLocaleToDocument(locale);
}

// Apply initial dir/lang on load
if (typeof document !== 'undefined') {
    applyLocaleToDocument(i18n.global.locale.value);
}

export default i18n;
