import logger from '@/Lib/logger';
import type { useToastStore } from '@/Stores/toast';

type ToastStore = ReturnType<typeof useToastStore>;

const SW_SCRIPT = '/sw.js';
const UPDATE_INTERVAL = 30 * 60 * 1000;

export function setupServiceWorker(toastStore: ToastStore) {
    if (!import.meta.env.PROD || typeof window === 'undefined' || !('serviceWorker' in navigator)) {
        return;
    }

    let registration: ServiceWorkerRegistration | null = null;
    let updateToastId: number | null = null;

    const clearUpdateToast = () => {
        if (updateToastId !== null) {
            toastStore.remove(updateToastId);
            updateToastId = null;
        }
    };

    const applyUpdate = async () => {
        clearUpdateToast();

        if (registration?.waiting) {
            registration.waiting.postMessage({ type: 'SKIP_WAITING' });
        }

        window.location.reload();
    };

    const resetServiceWorker = async () => {
        clearUpdateToast();

        const registrations = await navigator.serviceWorker.getRegistrations();
        await Promise.all(registrations.map((item) => item.unregister()));

        if ('caches' in window) {
            const cacheNames = await caches.keys();
            await Promise.all(cacheNames.map((cacheName) => caches.delete(cacheName)));
        }

        window.location.reload();
    };

    const showUpdateToast = () => {
        if (updateToastId !== null) {
            return;
        }

        updateToastId = toastStore.add(
            'Versi baru tersedia. Muat ulang untuk memakai pembaruan terbaru.',
            'info',
            null,
            [
                {
                    label: 'Muat ulang',
                    handler: applyUpdate,
                    variant: 'primary',
                },
                {
                    label: 'Bersihkan cache',
                    handler: resetServiceWorker,
                    variant: 'secondary',
                },
            ],
        );
    };

    const watchInstallingWorker = (worker: ServiceWorker | null) => {
        if (!worker) return;

        worker.addEventListener('statechange', () => {
            if (worker.state === 'installed' && navigator.serviceWorker.controller) {
                showUpdateToast();
            }
        });
    };

    const register = async () => {
        registration = await navigator.serviceWorker.register(SW_SCRIPT, { updateViaCache: 'none' });

        if (registration.waiting && navigator.serviceWorker.controller) {
            showUpdateToast();
        }

        watchInstallingWorker(registration.installing);

        registration.addEventListener('updatefound', () => {
            watchInstallingWorker(registration?.installing ?? null);
        });

        await registration.update();
    };

    window.addEventListener('focus', () => {
        registration?.update().catch(() => {});
    });

    window.addEventListener('online', () => {
        registration?.update().catch(() => {});
    });

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            registration?.update().catch(() => {});
        }
    });

    window.setInterval(() => {
        registration?.update().catch(() => {});
    }, UPDATE_INTERVAL);

    register().catch((error) => {
        logger.error('PWA Service Worker Registration Failed:', error);
    });
}
