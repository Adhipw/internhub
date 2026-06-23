import logger from '@/Lib/logger';
import { createApp, defineComponent, h, type Component, type DefineComponent, type PropType } from 'vue';
import { createInertiaApp, router as inertiaRouter } from '@inertiajs/vue3';
import type { Page } from '@inertiajs/core';
import { createPinia, type Pinia } from 'pinia';
import '../css/app.css';
import './echo';

import ToastContainer from '@/Components/ToastContainer.vue';
import { useToastStore } from './Stores/toast';
import { useLangStore } from './Stores/lang';
import { useAuthStore } from './Stores/auth';
import { setupServiceWorker } from './Services/serviceWorker';
import { startDomI18nBridge } from './Services/domI18n';
import { route as routeFn, ZiggyVue } from 'ziggy-js';

logger.log('InternHub Web App Starting (Inertia + REST API)...');

declare global {
    interface Window {
        pageData: any;
        Ziggy?: Record<string, unknown>;
        route: typeof routeFn;
    }
}

const pages = import.meta.glob('./Pages/**/*.vue') as Record<string, () => Promise<{ default: DefineComponent }>>;

const readInitialPageFromLegacyDOM = (): Page | undefined => {
    const appElement = document.getElementById('app');
    const page = appElement?.dataset.page;

    if (!page) return undefined;

    try {
        return JSON.parse(page) as Page;
    } catch (error) {
        logger.error('Failed to parse Inertia initial page from #app[data-page]:', error);
        return undefined;
    }
};

const hydrateStores = (pinia: Pinia, page: Page) => {
    window.pageData = page;

    const langStore = useLangStore(pinia);
    const authStore = useAuthStore(pinia);
    const props = page.props as Record<string, any>;

    if (props.translations) {
        langStore.translations = props.translations;
        logger.log('Translations hydrated from server:', Object.keys(props.translations).length, 'keys');
    }

    if (props.locale && typeof props.locale === 'string') {
        langStore.locale = props.locale;
        localStorage.setItem('locale', props.locale);
    }

    if (props.auth && Object.prototype.hasOwnProperty.call(props.auth, 'user')) {
        authStore.syncFromInertiaUser(props.auth.user);
    }
};

const InertiaRoot = defineComponent({
    name: 'InertiaRoot',
    props: {
        inertiaApp: {
            type: Object as PropType<Component>,
            required: true,
        },
        inertiaProps: {
            type: Object as PropType<Record<string, any>>,
            required: true,
        },
    },
    setup(props) {
        return () => h('div', [
            h(props.inertiaApp, props.inertiaProps),
            h(ToastContainer),
        ]);
    },
});

// Premium Custom Alert Override for reCAPTCHA network errors.
const originalAlert = window.alert;
window.alert = function (message: any) {
    if (typeof message === 'string' && message.toLowerCase().includes('recaptcha')) {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-6 right-6 max-w-sm w-full bg-slate-900/80 backdrop-blur-xl border border-white/10 shadow-2xl rounded-2xl p-4 flex items-start space-x-4 z-[9999] transform transition-all duration-500 translate-y-10 opacity-0';
        toast.innerHTML = `
            <div class="flex-shrink-0 bg-red-500/20 rounded-full p-2">
                <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-white">Connection Error</h3>
                <p class="text-xs text-slate-300 mt-1 leading-relaxed">${message}</p>
            </div>
            <button onclick="this.parentElement.style.opacity='0'; setTimeout(() => this.parentElement.remove(), 500)" class="text-slate-400 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6L18 18"></path>
                </svg>
            </button>
        `;
        document.body.appendChild(toast);

        requestAnimationFrame(() => {
            toast.classList.remove('translate-y-10', 'opacity-0');
        });

        setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-y-2');
            setTimeout(() => toast.remove(), 500);
        }, 6000);
        return;
    }
    originalAlert(message);
};

createInertiaApp({
    page: readInitialPageFromLegacyDOM(),
    title: (title) => title ? `${title} - InternHub` : 'InternHub',
    resolve: async (name) => {
        const page = pages[`./Pages/${name}.vue`];

        if (!page) {
            throw new Error(`Inertia page not found: ${name}`);
        }

        return page().then((module) => module.default);
    },
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();
        const initialPage = props.initialPage;
        const ziggy = initialPage.props.ziggy as Record<string, unknown> | undefined;

        if (ziggy) {
            window.Ziggy = ziggy;
        }

        window.route = ((name: any, params?: any, absolute?: any, config?: any) => {
            return routeFn(name, params, absolute, config || window.Ziggy);
        }) as typeof routeFn;

        hydrateStores(pinia, initialPage);

        const root = createApp({
            render: () => h(InertiaRoot, {
                inertiaApp: App,
                inertiaProps: props,
            }),
        });

        root.use(plugin);
        root.use(pinia);
        root.use(ZiggyVue as any, ziggy as any);

        root.mount(el);

        const langStore = useLangStore(pinia);
        const authStore = useAuthStore(pinia);
        const toastStore = useToastStore(pinia);

        inertiaRouter.on('navigate', (event: any) => {
            hydrateStores(pinia, event.detail.page);
        });

        if (!langStore.translations || Object.keys(langStore.translations).length === 0) {
            langStore.fetchTranslations().catch((err) => {
                logger.error('Failed to load translations in background:', err);
            });
        }

        startDomI18nBridge(langStore).catch((err) => {
            logger.error('Failed to start DOM i18n bridge:', err);
        });

        setupServiceWorker(toastStore);

        logger.log('InternHub Inertia Web App Mounted');
    },
    progress: {
        delay: 250,
        color: '#2563eb', // primary-600
        includeCSS: true,
        showSpinner: true,
    },
});
