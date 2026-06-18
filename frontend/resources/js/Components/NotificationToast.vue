<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from '@/Stores/auth';
import { Bell, X } from 'lucide-vue-next';

interface Notification {
    id: string;
    title: string;
    message: string;
    type: 'info' | 'success' | 'warning';
}

const authStore = useAuthStore();
const notifications = ref<Notification[]>([]);
const timeoutIds = new Set<ReturnType<typeof window.setTimeout>>();
let channelName: string | null = null;

const addNotification = (notif: Omit<Notification, 'id'>) => {
    const id = Math.random().toString(36).substring(7);
    const newNotif = { ...notif, id };
    notifications.value.push(newNotif);
    
    // Auto remove after 10 seconds
    const timeoutId = setTimeout(() => {
        removeNotification(id);
        timeoutIds.delete(timeoutId);
    }, 10000);
    timeoutIds.add(timeoutId);
};

const removeNotification = (id: string) => {
    notifications.value = notifications.value.filter(n => n.id !== id);
};

onMounted(() => {
    if (authStore.user) {
        // Listen to Private Channel
        const channel = `user.${authStore.user.id}`;
        channelName = channel;
        
        // Use global window.Echo if available
        if ((window as any).Echo) {
            (window as any).Echo.private(channel)
                .listen('.ApplicationStatusChanged', (e: any) => {
                    addNotification({
                        title: 'Status Lamaran Berubah',
                        message: `Lamaran Anda untuk "${e.internship_title}" telah diperbarui menjadi: ${e.status}.`,
                        type: 'info'
                    });
                });
        }
    }
});

onUnmounted(() => {
    timeoutIds.forEach((timeoutId) => window.clearTimeout(timeoutId));
    timeoutIds.clear();

    if (channelName && (window as any).Echo) {
        (window as any).Echo.leave(channelName);
    }
});
</script>

<template>
    <div class="fixed top-6 right-6 z-[100] flex flex-col gap-4 max-w-sm w-full">
        <TransitionGroup 
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div 
                v-for="notif in notifications" 
                :key="notif.id"
                class="bg-white dark:bg-slate-900 rounded-[1.5rem] p-6 shadow-2xl border border-slate-100 dark:border-slate-800 flex items-start gap-4"
            >
                <div class="w-10 h-10 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center text-primary-600 shrink-0">
                    <Bell class="w-5 h-5" />
                </div>
                <div class="flex-1">
                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ notif.title }}</p>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ notif.message }}</p>
                </div>
                <button @click="removeNotification(notif.id)" class="text-slate-400 hover:text-slate-600">
                    <X class="w-4 h-4" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
