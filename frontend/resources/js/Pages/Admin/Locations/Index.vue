<script setup lang="ts">
import logger from '@/Lib/logger';
import { ref, reactive, onMounted, computed } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import Pagination from '@/Components/Pagination.vue';
import { 
  MapPin, Plus, Trash2, Power, 
  Map, CheckCircle2, XCircle, Loader2
} from 'lucide-vue-next';
import { useLangStore } from '@/Stores/lang';
import api from '@/Services/api';
import type { PaginatedResponse } from '@/Types/user';

interface Location {
  id: number;
  name: string;
  type: string;
  is_active: boolean;
  created_at: string;
}

const langStore = useLangStore();
const t = (key: string) => langStore.t(key);

import { router as inertiaRouter } from '@inertiajs/vue3';

const props = defineProps<{
    locations?: PaginatedResponse<Location>;
}>();

const loading = ref(false);
const processing = ref(false);
const locations = computed(() => props.locations || {
  data: [],
  links: [],
  meta: {} as any
});

const form = reactive({
    name: '',
    type: 'city',
    errors: {} as any
});

const fetchLocations = (page = 1) => {
    inertiaRouter.get('/admin/locations', { page }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

const submit = async () => {
    processing.value = true;
    form.errors = {};
    try {
        await api.post('/admin/locations', form);
        form.name = '';
        form.type = 'city';
        inertiaRouter.reload({ only: ['locations'] });
    } catch (error: any) {
        if (error.response?.data?.errors) {
            form.errors = error.response.data.errors;
        } else {
            alert(t('common.error_occurred'));
        }
    } finally {
        processing.value = false;
    }
};

const toggleStatus = async (id: number) => {
    try {
        await api.post(`/admin/locations/${id}/toggle`);
        inertiaRouter.reload({ only: ['locations'] });
    } catch (error) {
        alert(t('common.error_occurred'));
    }
};

const deleteLocation = async (id: number) => {
    if (confirm(t('admin.locations.delete_confirm'))) {
        try {
            await api.delete(`/admin/locations/${id}`);
            inertiaRouter.reload({ only: ['locations'] });
        } catch (error) {
            alert(t('common.error_occurred'));
        }
    }
};

onMounted(() => {
    langStore.fetchTranslations();
});
</script>

<template>
  <DashboardLayout>
    <div class="space-y-10">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div v-if="!langStore.loading">
          <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">{{ t('admin.locations.title') }}</h1>
          <p class="text-slate-500 dark:text-slate-400">{{ t('admin.locations.desc') }}</p>
        </div>
        <div v-else class="h-16 w-64 bg-slate-100 dark:bg-slate-800 animate-pulse rounded-2xl"></div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        <!-- Location List -->
        <div class="lg:col-span-2 space-y-6 relative min-h-[400px]">
          <div v-if="loading" class="absolute inset-0 bg-white/50 dark:bg-slate-900/50 backdrop-blur-[1px] z-10 flex items-center justify-center rounded-[2.5rem]">
              <Loader2 class="w-10 h-10 text-primary-600 animate-spin" />
          </div>

          <Card class="overflow-hidden border-none shadow-sm">
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800">
                    <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('admin.locations.col_name') }}</th>
                    <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('admin.locations.col_type') }}</th>
                    <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">{{ t('admin.locations.col_status') }}</th>
                    <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">{{ t('admin.locations.col_actions') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
                  <tr v-for="loc in locations.data" :key="loc.id" class="hover:bg-slate-50/30 dark:hover:bg-slate-900/20 transition-colors">
                    <td class="px-8 py-6">
                      <div class="flex items-center gap-3">
                        <MapPin class="w-4 h-4 text-slate-300" />
                        <span class="font-bold text-slate-900 dark:text-white">{{ loc.name }}</span>
                      </div>
                    </td>
                    <td class="px-8 py-6">
                      <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">{{ loc.type }}</span>
                    </td>
                    <td class="px-8 py-6 text-center">
                      <div class="flex items-center justify-center">
                        <button 
                          @click="toggleStatus(loc.id)"
                          class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest transition-all"
                          :class="loc.is_active ? 'bg-green-50 text-green-600 hover:bg-green-100' : 'bg-slate-100 text-slate-400 hover:bg-slate-200'"
                        >
                          {{ loc.is_active ? t('admin.locations.status_active') : t('admin.locations.status_inactive') }}
                        </button>
                      </div>
                    </td>
                    <td class="px-8 py-6 text-right">
                       <div class="flex items-center justify-end gap-2">
                          <button @click="deleteLocation(loc.id)" class="p-2 text-slate-400 hover:text-red-600 transition-all" :title="t('common.delete')">
                             <Trash2 class="w-4 h-4" />
                          </button>
                       </div>
                    </td>
                  </tr>
                  <tr v-if="locations.data.length === 0 && !loading">
                      <td colspan="4" class="px-8 py-20 text-center text-slate-500">
                          {{ t('admin.locations.empty') }}
                      </td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <!-- Pagination -->
            <div v-if="locations.links && locations.links.length > 3" class="px-8 py-6 border-t border-slate-50 dark:border-slate-800">
                <Pagination :links="locations.links" />
            </div>
          </Card>
        </div>

        <!-- Add Location Form -->
        <Card class="p-8 border-none shadow-sm h-fit">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest mb-6 flex items-center">
            <Plus class="w-4 h-4 mr-2 text-primary-600" />
            {{ t('admin.locations.form_title') }}
          </h3>
          <form @submit.prevent="submit" class="space-y-6">
            <div class="space-y-1">
               <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ t('admin.locations.label_name') }}</label>
               <input v-model="form.name" type="text" :placeholder="t('admin.locations.placeholder_name')" class="w-full bg-slate-50 dark:bg-slate-900 dark:text-white border-none rounded-xl text-sm p-4 focus:ring-2 focus:ring-primary-500/20" required />
               <p v-if="form.errors.name" class="text-[10px] text-red-500 font-bold mt-1">{{ form.errors.name[0] }}</p>
            </div>
            <div class="space-y-1">
               <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ t('admin.locations.label_type') }}</label>
               <select v-model="form.type" class="w-full bg-slate-50 dark:bg-slate-900 dark:text-white border-none rounded-xl text-sm p-4 focus:ring-2 focus:ring-primary-500/20">
                  <option value="city">{{ t('admin.locations.type_city') }}</option>
                  <option value="province">{{ t('admin.locations.type_province') }}</option>
                  <option value="region">{{ t('admin.locations.type_region') }}</option>
               </select>
            </div>
            <button type="submit" :disabled="processing" class="w-full py-4 bg-primary-600 text-white rounded-2xl text-sm font-bold hover:bg-primary-700 transition-all active-press flex items-center justify-center gap-2">
               <Loader2 v-if="processing" class="w-4 h-4 animate-spin" />
               {{ t('admin.locations.btn_save') }}
            </button>
          </form>
        </Card>
      </div>
    </div>
  </DashboardLayout>
</template>
