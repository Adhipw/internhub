<script setup lang="ts">
import { Link, router as inertiaRouter } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { 
  ChevronRightIcon
} from '@heroicons/vue/24/outline';
import { useDebounceFn } from '@vueuse/core';
import { useLangStore } from '@/Stores/lang';
import type { Application } from '@/Types/application';
import type { PaginatedResponse } from '@/Types/user';

interface HrApplicationsIndexProps {
    applications?: PaginatedResponse<Application>;
    filters?: {
        status?: string;
        internship_id?: string;
    };
}

const props = defineProps<HrApplicationsIndexProps>();

const langStore = useLangStore();
const t = (key: string) => langStore.t(key);

const applications = computed<PaginatedResponse<Application>>(() => props.applications || {
    data: [],
    links: [],
    meta: {} as PaginatedResponse<Application>['meta']
});
const loading = ref(false);
const statusFilter = ref(props.filters?.status || new window.URLSearchParams(window.location.search).get('status') || '');
const internshipId = ref(props.filters?.internship_id || new window.URLSearchParams(window.location.search).get('internship_id') || '');

const debouncedFilter = useDebounceFn(() => {
    loading.value = true;
    inertiaRouter.get('/hr/applications', {
        status: statusFilter.value,
        internship_id: internshipId.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['applications'],
        onFinish: () => {
            loading.value = false;
        },
        onError: () => {
            loading.value = false;
        },
    });
}, 300);

watch(statusFilter, () => {
    debouncedFilter();
});
</script>

<template>
  <DashboardLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('hr.applications.title') }}</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('hr.applications.desc') }}</p>
        </div>
      </div>
    </template>

    <div v-if="loading" class="flex justify-center py-20">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Filters -->
      <Card class="p-4 bg-white dark:bg-gray-800 border-none shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex flex-1 items-center space-x-4">
            <div class="w-full md:w-64">
              <select 
                v-model="statusFilter"
                class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 dark:text-white text-sm focus:ring-primary-500 focus:border-primary-500 transition-all"
              >
                <option value="">{{ t('hr.applications.filter_all') }}</option>
                <option value="pending">{{ t('hr.applications.status_pending') }}</option>
                <option value="reviewing">{{ t('hr.applications.status_reviewing') }}</option>
                <option value="accepted">{{ t('hr.applications.status_accepted') }}</option>
                <option value="rejected">{{ t('hr.applications.status_rejected') }}</option>
              </select>
            </div>
          </div>
          <div class="text-sm text-gray-500">
            {{ t('hr.applications.showing') }} {{ applications.data.length }} {{ t('hr.applications.applicants') }}
          </div>
        </div>
      </Card>

      <!-- Applications Table -->
      <Card class="bg-white dark:bg-gray-800 border-none shadow-sm overflow-hidden">
        <div v-if="applications.data.length > 0" class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
              <tr>
                <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ t('hr.applications.col_candidate') }}</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ t('hr.applications.col_job') }}</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ t('hr.applications.col_date') }}</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ t('hr.applications.col_status') }}</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">{{ t('hr.applications.col_actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr v-for="app in applications.data" :key="app.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all duration-300 group cursor-pointer" @click="inertiaRouter.visit('/hr/applications/' + app.id)">
                <td class="px-8 py-6">
                  <div class="flex items-center">
                    <div class="h-12 w-12 flex-shrink-0 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 font-bold group-hover:bg-primary-600 group-hover:text-white transition-all shadow-sm">
                      {{ app.user.name.charAt(0) }}
                    </div>
                    <div class="ml-5">
                      <div class="text-sm font-bold text-slate-900 dark:text-white">{{ app.user.name }}</div>
                      <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ app.user.email }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-8 py-6">
                  <div class="text-sm text-slate-900 dark:text-white font-bold">{{ app.internship.title }}</div>
                </td>
                <td class="px-8 py-6">
                  <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ app.created_at_human }}</div>
                </td>
                <td class="px-8 py-6">
                  <StatusBadge :status="app.status" />
                </td>
                <td class="px-8 py-6 text-right">
                  <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-slate-50 dark:bg-slate-800 text-slate-400 group-hover:bg-primary-600 group-hover:text-white transition-all">
                    <ChevronRightIcon class="w-5 h-5" />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <EmptyState 
            v-else-if="!loading"
            type="empty"
            :title="t('hr.applications.empty_title')"
            :description="t('hr.applications.empty_desc')"
        >
            <template #actions>
                <div class="mt-8 flex items-center justify-center space-x-4">
                    <Link href="/hr/internships" class="text-sm font-bold text-primary-600 hover:underline">
                    {{ t('hr.applications.manage_jobs') }}
                    </Link>
                    <span class="text-slate-200">|</span>
                    <Link href="/hr/internships/create" class="text-sm font-bold text-slate-600 dark:text-slate-400 hover:underline">
                    {{ t('hr.applications.post_new') }}
                    </Link>
                </div>
            </template>
        </EmptyState>
        
        <!-- Pagination -->
        <div v-if="applications.links && applications.links.length > 3" class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
            <Pagination :links="applications.links" />
        </div>
      </Card>
    </div>
  </DashboardLayout>
</template>

