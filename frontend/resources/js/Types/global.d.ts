import { PageProps } from '@inertiajs/core';
import { route as routeFn } from 'ziggy-js';

declare global {
    var route: typeof routeFn;
}

declare module '@inertiajs/core' {
    interface PageProps extends Record<string, unknown> {
        auth: {
            user: {
                id: number;
                name: string;
                email: string;
                avatar_url?: string;
                roles: string[];
                permissions: string[];
                company_id?: number;
                phone_number?: string;
                created_at?: string;
            };
            session_id?: string;
        };
        current_company?: {
            id: number;
            name: string;
        };
        flash: {
            message?: string;
            error?: string;
            status?: string;
            success?: string;
        };
        errors?: Record<string, string> & {
            application?: string;
        };
        feature_flags?: Record<string, boolean>;
    }
}

declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof routeFn;
        window: typeof window;
    }
}

declare module '*.vue' {
    import type { DefineComponent } from 'vue';
    const component: DefineComponent<Record<string, unknown>, Record<string, unknown>, any>;
    export default component;
}
