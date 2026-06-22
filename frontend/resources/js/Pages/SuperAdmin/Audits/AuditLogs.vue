<script setup lang="ts">
import logger from '@/Lib/logger';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import Pagination from '@/Components/Pagination.vue';
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import { 
  Activity, Search, User, Clock, 
  ExternalLink, Filter, Loader2, Download
} from 'lucide-vue-next';
import api from '@/Services/api';
import { useLangStore } from '@/Stores/lang';

const langStore = useLangStore();
const t = (key: string) => langStore.t(key);

const props = defineProps<{
    logs?: {
        data: any[];
        links: any[];
    };
    filters?: {
        search?: string;
    };
}>();

const logs = computed(() => props.logs || {
    data: [] as any[],
    links: [] as any[]
});
const loading = ref(false);
const searchQuery = ref(props.filters?.search || '');
let refreshInterval: any = null;

import { router as inertiaRouter } from '@inertiajs/vue3';

const fetchLogs = (page = 1) => {
    inertiaRouter.get('/super-admin/audit-logs', {
        page,
        search: searchQuery.value
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

const exportLogs = () => {
    window.open('/api/v1/super-admin/audit-logs/export', '_blank');
};

let searchTimeout: any = null;
watch(searchQuery, () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchLogs(1);
    }, 500);
});

onMounted(() => {
    // Auto-refresh every 30 seconds
    refreshInterval = setInterval(() => {
        inertiaRouter.reload({ only: ['logs'] });
    }, 30000);
});

onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
});
</script>

<template>
  <DashboardLayout>
    <div class="space-y-10">
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">{{ t('admin.audit.title') }}</h1>
          <p class="text-slate-500 dark:text-slate-400">{{ t('admin.audit.desc') }}</p>
        </div>
        
        <button 
          @click="exportLogs"
          class="flex items-center gap-2 px-6 py-3 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-2xl text-sm font-bold hover:bg-slate-200 transition-all active-press"
        >
          <Download class="w-4 h-4" />
          Export Logs
        </button>
        <div class="flex items-center gap-3">
          <div class="relative">
             <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
             <input 
                v-model="searchQuery"
                type="text" 
                :placeholder="t('admin.audit.search_placeholder') || 'Search logs...'" 
                class="pl-11 pr-6 py-3 bg-white dark:bg-slate-800 border-none rounded-2xl text-sm w-64 shadow-sm focus:ring-2 focus:ring-primary-500/20 transition-all" 
             />
          </div>
        </div>
      </div>

      <div v-if="loading" class="flex justify-center py-20">
          <Loader2 class="w-12 h-12 text-primary-600 animate-spin" />
      </div>

      <div v-else class="space-y-6">
        <Card class="overflow-hidden border-none shadow-sm">
            <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                <tr class="bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800">
                    <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('admin.audit.col_time') }}</th>
                    <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('admin.audit.col_actor') }}</th>
                    <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('admin.audit.col_action') }}</th>
                    <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('admin.audit.col_desc') }}</th>
                    <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('admin.audit.col_ip') }}</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
                <tr v-for="log in logs.data" :key="log.id" class="hover:bg-slate-50/30 dark:hover:bg-slate-900/20 transition-colors">
                    <td class="px-8 py-6">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-300">
                        <Clock class="w-3.5 h-3.5 text-primary-500" />
                        {{ log.created_at_human }}
                        </div>
                    </div>
                    </td>
                    <td class="px-8 py-6">
                    <div class="flex items-center gap-3">
                        <div class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-[10px] font-bold text-slate-500">
                        {{ log.user?.name.charAt(0) || 'S' }}
                        </div>
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ log.user?.name || 'System' }}</span>
                    </div>
                    </td>
                    <td class="px-8 py-6">
                    <span class="px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded text-[10px] font-bold uppercase tracking-widest text-slate-600 dark:text-slate-400">
                        {{ log.action }}
                    </span>
                    </td>
                    <td class="px-8 py-6">
                    <p class="text-xs text-slate-500 max-w-md truncate" :title="log.description">{{ log.description }}</p>
                    </td>
                    <td class="px-8 py-6">
                    <div class="flex flex-col gap-1">
                        <span class="text-[10px] font-mono text-slate-400 bg-slate-50 dark:bg-slate-900 px-2 py-1 rounded w-fit">
                            {{ log.ip_address }}
                        </span>
                        <span v-if="log.region" class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">
                            {{ log.region }}
                        </span>
                    </div>
                    </td>
                </tr>
                </tbody>
            </table>
            </div>
        </Card>

        <Pagination :links="logs.links" @page-changed="fetchLogs" />
      </div>
    </div>
  </DashboardLayout>
</template>
