import logger from '@/Lib/logger';
import { ref } from 'vue';
import axios from 'axios';
import echo from '@/echo';
import { useAuthStore } from '@/Stores/auth';

export interface NotificationData {
    id: string;
    type: string;
    data: {
        message: string;
        [key: string]: any;
    };
    read_at: string | null;
    created_at: string;
}

const notifications = ref<NotificationData[]>([]);
const unreadCount = ref(0);
const latestToast = ref<{ message: string; type: 'success' | 'error' } | null>(null);
const webJson = axios.create({
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

export function useNotifications() {
    const authStore = useAuthStore();
    const user = authStore.user;

    const fetchNotifications = async () => {
        try {
            const response = await webJson.get('/notifications');
            notifications.value = response.data.notifications || [];
            unreadCount.value = response.data.unreadCount || 0;
        } catch (error) {
            logger.error('Failed to fetch notifications', error);
        }
    };

    const markAsRead = async (id: string) => {
        try {
            await webJson.post(`/notifications/${id}/read`);
            const index = notifications.value.findIndex(n => n.id === id);
            if (index !== -1 && !notifications.value[index].read_at) {
                notifications.value[index].read_at = new Date().toISOString();
                unreadCount.value = Math.max(0, unreadCount.value - 1);
            }
        } catch (error) {
            logger.error('Failed to mark notification as read', error);
        }
    };

    const setupListeners = () => {
        // Skip WebSocket setup if Reverb is not configured
        if (!echo || !user) return;

        // User Channel
        echo.private(`App.Models.User.${user.id}`)
            .notification((notification: any) => {
                notifications.value.unshift({
                    id: notification.id,
                    type: notification.type,
                    data: notification,
                    read_at: null,
                    created_at: new Date().toISOString(),
                });
                unreadCount.value++;
                latestToast.value = {
                    message: notification.message || 'New notification received',
                    type: 'success',
                };
            });

        // Company Channel (if HR)
        const companies = (authStore.user as any)?.companies || [];
        companies.forEach((company: any) => {
            echo!.private(`company.${company.id}`)
                .listen('.NotificationSent', (_event: any) => {
                    // Handle company-specific realtime events if needed
                });
        });

        // Presence Channel for Admins
        const isAdmin = authStore.isAdmin || authStore.isSuperAdmin;
        if (isAdmin) {
            echo.join('admins.online')
                .here((users: any[]) => {
                    logger.log('Online Admins:', users);
                })
                .joining((user: any) => {
                    logger.log('Admin Joined:', user.name);
                })
                .leaving((user: any) => {
                    logger.log('Admin Left:', user.name);
                });
        }
    };

    return {
        notifications,
        unreadCount,
        latestToast,
        fetchNotifications,
        markAsRead,
        setupListeners,
    };
}
