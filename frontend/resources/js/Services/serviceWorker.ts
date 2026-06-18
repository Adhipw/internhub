import logger from '@/Lib/logger';
import type { useToastStore } from '@/Stores/toast';

type ToastStore = ReturnType<typeof useToastStore>;

const SW_SCRIPT = '/sw.js';
const serviceWorkerEnabled = import.meta.env.VITE_ENABLE_SERVICE_WORKER === 'true';

const unregisterExistingServiceWorkers = async () => {
    const registrations = await navigator.serviceWorker.getRegistrations();
    await Promise.all(registrations.map((item) => item.unregister()));

    if ('caches' in window) {
        const cacheNames = await caches.keys();
        await Promise.all(cacheNames.map((cacheName) => caches.delete(cacheName)));
    }
};

export function setupServiceWorker(toastStore: ToastStore) {
    if (!import.meta.env.PROD || typeof window === 'undefined' || !('serviceWorker' in navigator)) {
        return;
    }

    if (!serviceWorkerEnabled) {
        unregisterExistingServiceWorkers().catch((error) => {
            logger.error('Failed to unregister legacy service workers:', error);
        });

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
        await unregisterExistingServiceWorkers();

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

    register().catch((error) => {
        logger.error('PWA Service Worker Registration Failed:', error);
    });
}
