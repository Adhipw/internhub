import axios, { AxiosResponse } from 'axios';
import { router as inertiaRouter } from '@inertiajs/vue3';
import { useToastStore } from '@/Stores/toast';

let authRedirectInProgress = false;

const redirectToLogin = () => {
    if (authRedirectInProgress || window.location.pathname === '/login') {
        return;
    }

    authRedirectInProgress = true;
    const redirect = `${window.location.pathname}${window.location.search}`;

    inertiaRouter.visit(`/login?redirect=${encodeURIComponent(redirect)}`, {
        replace: true,
        preserveScroll: false,
        preserveState: false,
        onFinish: () => {
            authRedirectInProgress = false;
        },
    });
};

const api = axios.create({
    baseURL: '/api/v1',
    withCredentials: true,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Interceptor to handle responses and errors
api.interceptors.response.use(
    (response: AxiosResponse) => response,
    (error) => {
        const toastStore = useToastStore();

        if (error.response) {
            const status = error.response.status;
            const message = error.response.data?.message || 'Terjadi kesalahan pada server.';

            if (status === 401 || status === 419) {
                redirectToLogin();
            } else if (status === 422) {
                // Validation errors are usually handled by forms, but we can show a general toast
                toastStore.error('Mohon periksa kembali input Anda.');
            } else if (status >= 500) {
                toastStore.error('Server sedang bermasalah. Mohon coba lagi nanti.');
            } else {
                toastStore.error(message);
            }
        } else {
            toastStore.error('Koneksi internet terputus atau server tidak merespon.');
        }

        return Promise.reject(error);
    }
);

export default api;
