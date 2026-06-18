import logger from '@/Lib/logger';
import { defineStore } from 'pinia';
import { router as inertiaRouter } from '@inertiajs/vue3';
import api from '@/Services/api';

export const useLangStore = defineStore('lang', {
    state: () => ({
        translations: {} as Record<string, string>,
        catalogs: {} as Record<'id' | 'en', Record<string, string>>,
        locale: localStorage.getItem('locale') || 'id',
        loading: false,
    }),

    actions: {
        async fetchTranslations(localeArg?: string) {
            const locale = localeArg || this.locale;
            this.loading = true;
            try {
                const response = await api.get(`/translations/${locale}`);
                logger.log(`Fetched translations for ${locale}:`, response.data);
                // The API now returns { debug: ..., translations: ... }
                this.translations = response.data.translations || response.data;
                this.catalogs[locale as 'id' | 'en'] = this.translations;
                this.locale = locale;
                localStorage.setItem('locale', locale);
            } catch (error) {
                logger.error('Failed to fetch translations:', error);
            } finally {
                this.loading = false;
            }
        },

        async ensureCatalogs() {
            await Promise.all((['id', 'en'] as const).map(async (locale) => {
                if (this.catalogs[locale] && Object.keys(this.catalogs[locale]).length > 0) {
                    return;
                }

                try {
                    const response = await api.get(`/translations/${locale}`);
                    this.catalogs[locale] = response.data.translations || response.data;
                } catch (error) {
                    logger.error(`Failed to fetch ${locale} translation catalog:`, error);
                }
            }));
        },

        async setLocale(locale: 'id' | 'en') {
            if (this.locale === locale) {
                return;
            }

            this.locale = locale;
            localStorage.setItem('locale', locale);

            await this.fetchTranslations(locale);
            await this.ensureCatalogs();

            inertiaRouter.post(`/language/${locale}`, {}, {
                preserveScroll: true,
                preserveState: true,
                replace: true,
            });
        },

        t(key: string) {
            const val = this.translations[key];
            if (val) return val;
            
            logger.warn(`Translation missing for key: ${key}`);
            // Fallback: take the last part of the key and capitalize it
            const parts = key.split('.');
            const lastPart = parts[parts.length - 1];
            return lastPart.charAt(0).toUpperCase() + lastPart.slice(1).replace(/_/g, ' ');
        },

        __(key: string) {
            return this.t(key);
        }
    },
});
