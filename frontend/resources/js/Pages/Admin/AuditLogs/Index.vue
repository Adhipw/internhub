<script setup lang="ts">
import logger from '@/Lib/logger';
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import { router as inertiaRouter } from '@inertiajs/vue3';
import { Head } from '@/Components';

import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import Pagination from '@/Components/Pagination.vue';
import { 
    History, 
    Search, 
    Clock, 
    Activity,
    Filter,
    Loader2
} from 'lucide-vue-next';
import api from '@/Services/api';
import type { User, PaginatedResponse } from '@/Types/user';

interface AuditLog {
    id: number;
    action: string;
    description: string;
    metadata?: any;
    ip_address?: string;
    created_at: string;
    user?: User;
}

const urlParams = new URLSearchParams(window.location.search);

const props = defineProps<{
    logs?: PaginatedResponse<AuditLog>;
    filters?: any;
}>();

const loading = ref(false);
const search = ref(props.filters?.search || urlParams.get('search') || '');
const currentPage = ref(urlParams.get('page') || 1);
const logs = computed(() => props.logs || {
    data: [],
    links: [],
    meta: {} as any
});

const debounce = (fn: Function, ms: number) => {
    let timeoutId: ReturnType<typeof setTimeout>;
    return function (this: any, ...args: any[]) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn.apply(this, args), ms);
    };
};

const fetchLogs = () => {
    inertiaRouter.get('/admin/audit-logs', {
        search: search.value,
        page: currentPage.value
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

watch(search, debounce(() => {
    fetchLogs();
}, 500));

let refreshInterval: any = null;

onMounted(() => {
    // High-frequency polling for audit logs (30s)
    refreshInterval = setInterval(() => {
        inertiaRouter.reload({ only: ['logs'] });
    }, 30000);
});

onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
});

const formatDate = (date: string) => {
    return new Date(date).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head title="Audit Logs" />

    <DashboardLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h2 class="text-3xl font-display font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-3">
                        <History class="w-8 h-8 text-primary-600" />
                        Aktivitas Sistem
                    </h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Pantau riwayat aksi dan perubahan data oleh administrator.
                    </p>
                </div>
            </div>
        </template>

        <div class="mt-8 space-y-8">
            <!-- Filters & Search -->
            <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
                <div class="relative w-full md:w-96 group">
                    <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-primary-600 transition-colors" />
                    <input 
                        v-model="search"
                        type="text" 
                        placeholder="Cari aksi, deskripsi, atau nama admin..."
                        class="w-full pl-11 pr-4 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all outline-none"
                    >
                </div>
                
                <div class="flex items-center gap-2">
                    <button class="flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold text-slate-600 dark:text-slate-400 hover:border-primary-500 transition-all">
                        <Filter class="w-4 h-4" />
                        Filter Lanjutan
                    </button>
                </div>
            </div>

            <!-- Logs Table -->
            <Card class="overflow-hidden border-none shadow-sm bg-white dark:bg-slate-800 rounded-[2rem] relative">
                <div v-if="loading" class="absolute inset-0 bg-white/50 dark:bg-slate-900/50 backdrop-blur-[1px] z-10 flex items-center justify-center">
                    <Loader2 class="w-8 h-8 text-primary-600 animate-spin" />
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800">
                                <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Administrator</th>
                                <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Aksi & Deskripsi</th>
                                <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">Waktu Kejadian</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                            <tr v-for="log in logs.data" :key="log.id" class="group hover:bg-slate-50/30 dark:hover:bg-slate-700/20 transition-all">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-primary-50 dark:bg-primary-900/20 flex items-center justify-center text-primary-600 font-bold border border-primary-100 dark:border-primary-900/30">
                                            {{ log.user?.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ log.user?.name || 'System' }}</p>
                                            <p class="text-[10px] text-slate-400 font-medium">{{ log.user?.email || 'automated@system.com' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex flex-col gap-1">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-[10px] font-bold rounded uppercase tracking-tighter">
                                                {{ log.action }}
                                            </span>
                                            <span class="text-xs font-medium text-slate-600 dark:text-slate-300">{{ log.description }}</span>
                                        </div>
                                        <p v-if="log.metadata" class="text-[10px] text-slate-400 font-mono truncate max-w-md">
                                            {{ JSON.stringify(log.metadata) }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex flex-col items-end gap-1">
                                        <div class="flex items-center gap-1.5 text-sm font-bold text-slate-700 dark:text-slate-300">
                                            <Clock class="w-3.5 h-3.5 text-slate-400" />
                                            {{ formatDate(log.created_at) }}
                                        </div>
                                        <span class="text-[10px] text-slate-400 font-medium">Melalui IP: {{ log.ip_address || 'Internal' }}</span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="logs.data.length === 0">
                                <td colspan="3" class="px-8 py-20 text-center">
                                    <div class="max-w-xs mx-auto">
                                        <Activity class="w-12 h-12 text-slate-200 dark:text-slate-700 mx-auto mb-4" />
                                        <p class="text-sm font-bold text-slate-900 dark:text-white">Tidak Ada Aktivitas</p>
                                        <p class="text-xs text-slate-500 mt-1">Belum ada riwayat aktivitas yang sesuai dengan kriteria pencarian Anda.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>

            <!-- Pagination -->
            <div class="flex justify-center pt-4">
                <Pagination :links="logs.links" />
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.font-display {
    font-family: 'Outfit', sans-serif;
}
</style>
