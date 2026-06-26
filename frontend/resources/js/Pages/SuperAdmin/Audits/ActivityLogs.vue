<script setup lang="ts">
import logger from '@/Lib/logger';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import ActivityTimeline from '@/Components/Audits/ActivityTimeline.vue';
import ActivityFilters from '@/Components/Audits/ActivityFilters.vue';
import Pagination from '@/Components/Pagination.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { ShieldCheck, Activity, Terminal, Loader2 } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import api from '@/Services/api';
import { useLangStore } from '@/Stores/lang';

const langStore = useLangStore();
const t = (key: string) => langStore.t(key);

import { router as inertiaRouter } from '@inertiajs/vue3';

const props = defineProps<{
    logs?: {
        data: any[];
        links: any[];
    };
    filters?: any;
}>();

const logs = computed(() => props.logs || {
    data: [] as any[],
    links: [] as any[]
});
const loading = ref(false);
const filters = ref<any>(props.filters || {});

const fetchLogs = (page = 1, extraFilters = {}) => {
    inertiaRouter.get('/super-admin/activity-logs', {
        page,
        ...filters.value,
        ...extraFilters
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

const handleFilter = (newFilters: any) => {
    filters.value = newFilters;
    fetchLogs(1);
};

const handleReset = () => {
    filters.value = {};
    fetchLogs(1);
};
</script>

<template>
    <DashboardLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">{{ t('admin.audit.title') }}</h2>
                    <div class="flex items-center gap-3 mt-2">
                        <span class="flex items-center gap-1.5 text-[10px] font-semibold text-xs tracking-wide text-slate-400">
                            <Activity class="w-3 h-3 text-primary-500" /> {{ t('admin.audit.operational_history') }}
                        </span>
                        <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                        <span class="flex items-center gap-1.5 text-[10px] font-semibold text-xs tracking-wide text-slate-400">
                            <ShieldCheck class="w-3 h-3 text-emerald-500" /> {{ t('admin.audit.compliance_ready') }}
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-xl">
                        <Terminal class="w-4 h-4 text-primary-400" />
                        <span>{{ t('admin.audit.live_stream') }}</span>
                        <span class="w-2 h-2 rounded-full bg-emerald-500  ml-2"></span>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6 max-w-5xl mx-auto">
            <ActivityFilters :filters="filters" @filter="handleFilter" @reset="handleReset" />

            <div v-if="loading" class="flex justify-center py-20">
                <Loader2 class="w-12 h-12 text-primary-600 animate-spin" />
            </div>

            <Card v-else class="mt-6 p-8 overflow-hidden">
                <div v-if="logs.data.length > 0">
                    <ActivityTimeline :activities="logs.data" />
                    
                    <div class="mt-12 flex justify-center border-t border-slate-100 dark:border-slate-800 pt-8">
                        <Pagination :links="logs.links" @page-changed="fetchLogs" />
                    </div>
                </div>

                <EmptyState 
                    v-else
                    type="search"
                    :title="t('admin.audit.no_logs_title')"
                    :description="t('admin.audit.no_logs_desc')"
                />
            </Card>
        </div>
    </DashboardLayout>
</template>
