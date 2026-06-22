<script setup lang="ts">
import logger from '@/Lib/logger';
import { Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import api from '@/Services/api';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import Button from '@/Components/Button.vue';
import Pagination from '@/Components/Pagination.vue';
import { 
  PlusIcon, PencilIcon, TrashIcon, EyeIcon,
  ChevronRightIcon, BriefcaseIcon
} from '@heroicons/vue/24/outline';
import { useLangStore } from '@/Stores/lang';
import { useAuthStore } from '@/Stores/auth';
import echo from '@/echo';
import ImportModal from '@/Components/ImportModal.vue';
import { Download } from 'lucide-vue-next';
import type { Internship } from '@/Types/internship';
import type { PaginatedResponse } from '@/Types/user';

import { router as inertiaRouter } from '@inertiajs/vue3';

const props = defineProps<{
    internships?: PaginatedResponse<Internship>;
}>();

const authStore = useAuthStore();
const langStore = useLangStore();
const t = (key: string) => langStore.t(key);
const user = computed(() => authStore.user || {});

const internships = computed(() => props.internships || {
    data: [],
    links: [],
    meta: {} as any
});
const loading = ref(false);

const fetchData = (page = 1) => {
    inertiaRouter.get('/hr/internships', { page }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

const showImportModal = ref(false);
const handleImportSuccess = () => {
    showImportModal.value = false;
    inertiaRouter.reload({ only: ['internships'] });
};

const deleteInternship = async (id: number) => {
    if (confirm(t('hr.internships.delete_confirm'))) {
        try {
            await api.delete(`/hr/internships/${id}`);
            inertiaRouter.reload({ only: ['internships'] });
        } catch (error) {
            alert(t('common.error_occurred'));
        }
    }
};
</script>

<template>
  <DashboardLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('hr.internships.title') }}</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('hr.internships.desc') }}</p>
        </div>
        <div class="flex items-center gap-3">
          <Button variant="ghost" @click="showImportModal = true" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <Download class="w-4 h-4 mr-2" />
            Import
          </Button>
          <Link href="/hr/internships/create">
            <Button class="rounded-xl">
              <PlusIcon class="w-4 h-4 mr-2" />
              {{ t('hr.internships.create_button') }}
            </Button>
          </Link>
        </div>
      </div>
    </template>

    <div v-if="loading" class="flex justify-center py-20">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
    </div>

    <div v-else class="space-y-6">
      <Card class="bg-white dark:bg-gray-800 border-none shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
              <tr>
                <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ t('hr.internships.col_job') }}</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ t('hr.internships.col_type_loc') }}</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">{{ t('hr.internships.col_applicants') }}</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ t('hr.internships.col_status') }}</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">{{ t('hr.internships.col_actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr v-for="job in internships.data" :key="job.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <td class="px-6 py-4">
                  <div class="text-sm font-bold text-gray-900 dark:text-white">{{ job.title }}</div>
                  <div class="text-xs text-gray-500 mt-0.5">{{ t('hr.internships.created_at') }} {{ job.created_at_human }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="flex flex-col">
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ job.type }}</span>
                    <span class="text-xs text-gray-500">{{ job.location }}</span>
                  </div>
                </td>
                <td class="px-6 py-4 text-center">
                  <Link :href="'/hr/applications?internship_id=' + job.id" class="inline-flex flex-col items-center group">
                    <span class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-primary-600 transition-colors">{{ job.applications_count }}</span>
                    <span class="text-xs text-gray-500">Total</span>
                  </Link>
                </td>
                <td class="px-6 py-4">
                  <Badge :variant="job.status === 'published' ? 'success' : (job.status === 'draft' ? 'warning' : 'error')">
                    {{ job.status === 'published' ? t('common.active') : (job.status === 'draft' ? 'Draft' : t('common.closed')) }}
                  </Badge>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end space-x-2">
                    <Link :href="'/hr/internships/' + job.id + '/edit'" class="p-2 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition-all" :title="t('common.edit')">
                      <PencilIcon class="w-5 h-5" />
                    </Link>
                    <button @click="deleteInternship(job.id)" class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all" :title="t('common.delete')">
                      <TrashIcon class="w-5 h-5" />
                    </button>
                    <Link :href="'/internships/' + job.slug" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all" :title="t('common.view_public')">
                      <EyeIcon class="w-5 h-5" />
                    </Link>
                  </div>
                </td>
              </tr>
              <tr v-if="internships.data.length === 0">
                <td colspan="5" class="px-6 py-32 text-center">
                  <div class="max-w-md mx-auto">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 mb-6 text-slate-300 dark:text-slate-700">
                      <BriefcaseIcon class="w-8 h-8 stroke-1" />
                    </div>
                    <h4 class="text-xl font-display font-bold text-slate-900 dark:text-white tracking-tight">{{ t('hr.internships.empty_title') }}</h4>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400 leading-relaxed px-10">
                      {{ t('hr.internships.empty_desc') }}
                    </p>
                    <Link href="/hr/internships/create" class="mt-8 inline-flex px-8 py-3 bg-slate-900 text-white dark:bg-white dark:text-slate-900 text-sm font-bold rounded-xl hover:opacity-90 transition-opacity">
                      {{ t('hr.internships.create_now') }}
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Pagination -->
        <div v-if="internships.links && internships.links.length > 3" class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
            <Pagination :links="internships.links" />
        </div>
      </Card>
    </div>

    <ImportModal 
      :show="showImportModal" 
      title="Import Internships" 
      endpoint="/hr/import/internships" 
      template-url="/api/import/template/internships"
      @close="showImportModal = false"
      @success="handleImportSuccess"
    />
  </DashboardLayout>
</template>

