<script setup lang="ts">
import logger from '@/Lib/logger';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import Pagination from '@/Components/Pagination.vue';
import { ref, onMounted, computed } from 'vue';
import { 
  Server, ShieldAlert, AlertTriangle, 
  Clock, MapPin, Monitor, Loader2
} from 'lucide-vue-next';
import api from '@/Services/api';
import echo from '@/echo';
import { useLangStore } from '@/Stores/lang';

const langStore = useLangStore();
const t = (key: string) => langStore.t(key);

const props = defineProps<{
    events?: {
        data: any[];
        links: any[];
    };
}>();

const events = computed(() => props.events || {
    data: [] as any[],
    links: [] as any[]
});
const loading = ref(false);

import { router as inertiaRouter } from '@inertiajs/vue3';

const fetchEvents = (page = 1) => {
    inertiaRouter.get('/super-admin/security-events', { page }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

const setupRealtime = () => {
    if (echo) {
        echo.private('admin-security-events')
            .listen('SecurityEventCreated', (e: any) => {
                inertiaRouter.reload({ only: ['events'] });
                // Optional: Show a toast or notification
            });
    }
};

onMounted(() => {

    setupRealtime();
});
</script>

<template>
  <DashboardLayout>
    <div class="space-y-10">
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-3">
             <ShieldAlert class="w-8 h-8 text-red-600" />
             {{ t('admin.security.title') }}
          </h1>
          <p class="text-slate-500 dark:text-slate-400">{{ t('admin.security.desc') }}</p>
        </div>
      </div>

      <div v-if="loading" class="flex justify-center py-20">
          <Loader2 class="w-12 h-12 text-primary-600 animate-spin" />
      </div>

      <div v-else class="space-y-6">
        <Card v-for="event in events.data" :key="event.id" class="p-8 border-none shadow-sm flex items-start gap-6 relative overflow-hidden group">
          <div 
            class="absolute top-0 left-0 w-1.5 h-full transition-all group-hover:w-2"
            :class="{
              'bg-red-500': event.severity === 'high',
              'bg-orange-500': event.severity === 'medium',
              'bg-blue-500': event.severity === 'low'
            }"
          ></div>
          
          <div 
            class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0"
            :class="{
              'bg-red-50 text-red-600': event.severity === 'high',
              'bg-orange-50 text-orange-600': event.severity === 'medium',
              'bg-blue-50 text-blue-600': event.severity === 'low'
            }"
          >
             <AlertTriangle class="w-7 h-7" />
          </div>

          <div class="flex-1 space-y-4">
             <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                   <h3 class="font-bold text-lg text-slate-900 dark:text-white mb-1">{{ event.description }}</h3>
                   <div class="flex items-center gap-4 text-xs text-slate-500">
                      <span class="flex items-center gap-1.5 font-bold uppercase tracking-widest" :class="event.severity === 'high' ? 'text-red-500' : ''">
                         {{ t('admin.security.severity_' + event.severity) }}
                      </span>
                      <span class="flex items-center gap-1.5 font-bold">
                         <Clock class="w-3.5 h-3.5 text-primary-500" />
                         {{ event.created_at_human }}
                      </span>
                   </div>
                </div>
                <div class="flex items-center gap-3">
                   <div class="px-3 py-1.5 bg-slate-50 dark:bg-slate-900 rounded-xl flex flex-col gap-1 text-[10px] font-bold text-slate-500 uppercase tracking-widest border border-slate-100 dark:border-slate-800">
                      <div class="flex items-center gap-2">
                        <Monitor class="w-3.5 h-3.5" />
                        {{ event.ip_address }}
                      </div>
                      <div v-if="event.region" class="flex items-center gap-2 text-slate-400">
                        <MapPin class="w-3.5 h-3.5" />
                        {{ event.region }}
                      </div>
                   </div>
                </div>
             </div>

             <div v-if="event.payload" class="p-4 bg-slate-900 rounded-xl overflow-hidden group/payload relative">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">{{ t('admin.security.payload') }}</p>
                <pre class="text-xs text-green-400 font-mono overflow-x-auto whitespace-pre-wrap">{{ typeof event.payload === 'string' ? JSON.stringify(JSON.parse(event.payload), null, 2) : JSON.stringify(event.payload, null, 2) }}</pre>
             </div>
          </div>
        </Card>

        <div v-if="events.data.length === 0" class="py-20 text-center bg-slate-50/50 dark:bg-slate-900/20 rounded-[2.5rem] border border-dashed border-slate-200 dark:border-slate-800">
           <ShieldAlert class="w-16 h-16 text-slate-200 mx-auto mb-4" />
           <p class="font-bold text-slate-900 dark:text-white">{{ t('admin.security.no_events') }}</p>
        </div>

        <Pagination :links="events.links" @page-changed="fetchEvents" />
      </div>
    </div>
  </DashboardLayout>
</template>
