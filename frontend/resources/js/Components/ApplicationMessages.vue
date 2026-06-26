<script setup lang="ts">
import logger from '@/Lib/logger';
import { ref, onMounted, computed, nextTick } from 'vue';
import api from '@/Services/api';
import { useAuthStore } from '@/Stores/auth';
import { Send, Loader2, User as UserIcon } from 'lucide-vue-next';

interface MessageUser {
    id: number;
    name: string;
    profile_photo_url?: string;
}

interface ApplicationMessage {
    id: number;
    application_id: number;
    user_id: number;
    message: string;
    created_at: string;
    created_at_human?: string;
    user?: MessageUser;
}

const props = defineProps<{
    applicationId: number;
}>();

const authStore = useAuthStore();
const currentUser = computed(() => authStore.user);

const messages = ref<ApplicationMessage[]>([]);
const loading = ref(true);
const posting = ref(false);
const newMessage = ref('');
const messagesContainer = ref<HTMLElement | null>(null);

const fetchMessages = async () => {
    try {
        const response = await api.get(`/applications/${props.applicationId}/messages`);
        messages.value = response.data.data;
        scrollToBottom();
    } catch (error) {
        logger.error('Failed to fetch messages:', error);
    } finally {
        loading.value = false;
    }
};

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

const postMessage = async () => {
    if (!newMessage.value.trim() || posting.value) return;

    posting.value = true;
    try {
        const response = await api.post(`/applications/${props.applicationId}/messages`, {
            message: newMessage.value.trim()
        });
        messages.value.push(response.data.data);
        newMessage.value = '';
        scrollToBottom();
    } catch (error) {
        logger.error('Failed to post message:', error);
        alert('Gagal mengirim pesan.');
    } finally {
        posting.value = false;
    }
};

onMounted(() => {
    fetchMessages();
});
</script>

<template>
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl overflow-hidden flex flex-col h-[500px] shadow-sm">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Diskusi Lamaran</h3>
                <p class="text-xs text-slate-500">Berkomunikasi langsung dengan kandidat/HR</p>
            </div>
        </div>

        <!-- Messages Area -->
        <div ref="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-6 bg-white dark:bg-slate-900">
            <div v-if="loading" class="flex justify-center py-10">
                <Loader2 class="w-6 h-6 text-primary-600 animate-spin" />
            </div>
            <div v-else-if="messages.length === 0" class="text-center py-10">
                <p class="text-sm text-slate-500 italic">Belum ada pesan. Mulai percakapan sekarang!</p>
            </div>
            <template v-else>
                <div 
                    v-for="msg in messages" 
                    :key="msg.id"
                    class="flex gap-4"
                    :class="msg.user_id === currentUser?.id ? 'flex-row-reverse' : 'flex-row'"
                >
                    <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center shrink-0 overflow-hidden">
                        <img v-if="msg.user?.profile_photo_url" loading="lazy" decoding="async" :src="msg.user.profile_photo_url" class="w-full h-full object-cover" />
                        <UserIcon v-else class="w-4 h-4 text-slate-400" />
                    </div>
                    <div 
                        class="max-w-[75%] space-y-1"
                        :class="msg.user_id === currentUser?.id ? 'items-end' : 'items-start'"
                    >
                        <div class="flex items-baseline gap-2 mx-1" :class="msg.user_id === currentUser?.id ? 'flex-row-reverse' : 'flex-row'">
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ msg.user?.name }}</span>
                            <span class="text-[10px] text-slate-400">{{ msg.created_at_human }}</span>
                        </div>
                        <div 
                            class="px-4 py-3 rounded-2xl text-sm leading-relaxed"
                            :class="msg.user_id === currentUser?.id 
                                ? 'bg-primary-600 text-white rounded-tr-sm' 
                                : 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 rounded-tl-sm'"
                        >
                            {{ msg.message }}
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Input Area -->
        <div class="p-4 bg-slate-50 dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800">
            <form class="flex items-end gap-2" @submit.prevent="postMessage">
                <div class="flex-1">
                    <textarea 
                        v-model="newMessage"
                        class="w-full resize-none bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl px-4 py-3 text-sm focus:ring-primary-500 focus:border-primary-500 transition-all max-h-[120px] min-h-[44px]"
                        placeholder="Ketik pesan (Enter untuk kirim)..."
                        rows="1"
                        @keydown.enter.exact.prevent="postMessage"
                    ></textarea>
                </div>
                <button 
                    type="submit"
                    :disabled="!newMessage.trim() || posting"
                    class="w-12 h-12 bg-primary-600 hover:bg-primary-700 disabled:bg-slate-200 disabled:cursor-not-allowed text-white rounded-2xl flex items-center justify-center shrink-0 transition-all shadow-sm"
                >
                    <Loader2 v-if="posting" class="w-5 h-5 animate-spin" />
                    <Send v-else class="w-5 h-5 ml-0.5" />
                </button>
            </form>
        </div>
    </div>
</template>
