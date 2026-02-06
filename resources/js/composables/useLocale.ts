import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import {
    setLocale as setI18nLocale,
    SUPPORTED_LOCALES,
    type SupportedLocale,
} from '@/i18n';

export function useLocale() {
    const { locale, t } = useI18n();

    const isRtl = computed(() => locale.value === 'ar');

    function setLocale(newLocale: SupportedLocale) {
        setI18nLocale(newLocale);
    }

    return {
        locale,
        isRtl,
        setLocale,
        t,
        supportedLocales: SUPPORTED_LOCALES,
    };
}
