<script setup lang="ts">

import { Link, router as inertiaRouter } from '@inertiajs/vue3';
import { ref, reactive, computed } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import Pagination from '@/Components/Pagination.vue';
import { 
  Building2, Search, CheckCircle2,
  ExternalLink, Globe, MapPin, Loader2
} from 'lucide-vue-next';
import { useLangStore } from '@/Stores/lang';
import api from '@/Services/api';

const urlParams = new window.URLSearchParams(window.location.search);
const langStore = useLangStore();
const t = (key: string) => langStore.t(key);

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Company {
    id: number;
    name: string;
    slug: string;
    logo_url?: string;
    is_verified: boolean;
    website?: string;
    location?: string;
    description?: string;
}

const props = defineProps<{
    companies?: { data: Company[]; links: PaginationLink[]; meta: Record<string, unknown> };
    filters?: Record<string, string>;
}>();

const loading = ref(false);
const processing = ref(false);
const companies = computed(() => props.companies || {
  data: [],
  links: [],
  meta: {}
});

const filters = reactive({
  search: (props.filters?.search as string) || urlParams.get('search') || '',
  status: (props.filters?.status as string) || urlParams.get('status') || '',
  page: urlParams.get('page') || 1
});

const fetchCompanies = () => {
    inertiaRouter.get('/admin/companies', { ...filters }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

const handleSearch = () => {
    filters.page = 1;
    updateQuery();
};

const updateQuery = () => {
    fetchCompanies();
};

// Inline Confirmation States
const confirmingId = ref<number | null>(null);
const confirmActionType = ref<'verify' | 'unverify'>('verify');

const startConfirm = (id: number, type: 'verify' | 'unverify') => {
    confirmingId.value = id;
    confirmActionType.value = type;
};

const cancelConfirm = () => {
    confirmingId.value = null;
};

const handleConfirmedAction = async (companyId: number) => {
    processing.value = true;
    try {
        if (confirmActionType.value === 'verify') {
            await api.post(`/admin/companies/${companyId}/verify`);
        } else {
            await api.post(`/admin/companies/${companyId}/unverify`);
        }
        confirmingId.value = null;
        inertiaRouter.reload({ only: ['companies'] });
    } catch {
        alert(t('common.error_occurred'));
    } finally {
        processing.value = false;
    }
};

// Removed onMounted fetch
</script>

<template>
  <DashboardLayout>
    <div class="space-y-10">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">{{ t('admin.dashboard.company_moderation') }}</h1>
          <p class="text-slate-500 dark:text-slate-400">Verifikasi legitimasi perusahaan yang mendaftar di platform.</p>
        </div>
        
        <div class="flex items-center gap-3">
          <div class="relative group">
            <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-primary-600 transition-colors" />
            <input 
              v-model="filters.search" 
              type="text"
              placeholder="Cari perusahaan..."
              class="pl-11 pr-6 py-3 bg-white dark:bg-slate-800 dark:text-white border border-slate-100 dark:border-slate-700 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all w-64" 
              @keyup.enter="handleSearch"
            />
          </div>
        </div>
      </div>

      <!-- Companies List -->
      <div class="relative min-h-[400px]">
        <div v-if="loading" class="absolute inset-0 bg-white/50 dark:bg-slate-900/50 backdrop-blur-[1px] z-10 flex items-center justify-center rounded-2xl">
            <Loader2 class="w-12 h-12 text-primary-600 animate-spin" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <Card v-for="company in companies.data" :key="company.id" class="p-8 border-none shadow-sm flex flex-col h-full group">
            <div class="flex items-start justify-between mb-6">
              <div class="w-16 h-16 bg-slate-50 dark:bg-slate-900 rounded-2xl flex items-center justify-center text-slate-300 group-hover:bg-primary-600 group-hover:text-white transition-all overflow-hidden border border-slate-50 dark:border-slate-800">
                 <img v-if="company.logo_url" loading="lazy" decoding="async" :src="company.logo_url" class="w-full h-full object-cover" />
                 <Building2 v-else class="w-8 h-8" />
              </div>
              <div 
                class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest flex items-center gap-1"
                :class="company.is_verified ? 'bg-green-50 text-green-600' : 'bg-orange-50 text-orange-600'"
              >
                <CheckCircle2 v-if="company.is_verified" class="w-3 h-3" />
                <div v-else class="w-3 h-3 border-2 border-orange-200 border-t-orange-600 rounded-full animate-spin"></div>
                {{ company.is_verified ? 'Verified' : 'Pending' }}
              </div>
            </div>

            <div class="flex-1">
               <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">{{ company.name }}</h3>
               <div class="space-y-2 mb-6">
                  <div v-if="company.website" class="flex items-center gap-2 text-xs text-slate-500 hover:text-primary-600 transition-colors">
                     <Globe class="w-3.5 h-3.5" />
                     <a :href="company.website" target="_blank" class="truncate">{{ company.website.replace(/^https?:\/\//, '') }}</a>
                  </div>
                  <div v-if="company.location" class="flex items-center gap-2 text-xs text-slate-500">
                     <MapPin class="w-3.5 h-3.5" />
                     <span class="truncate">{{ company.location }}</span>
                  </div>
               </div>
               <p class="text-xs text-slate-400 line-clamp-3 leading-relaxed mb-6">{{ company.description || 'Tidak ada deskripsi.' }}</p>
            </div>

            <div class="pt-6 border-t border-slate-50 dark:border-slate-800/50 flex gap-3 relative z-10">
               <!-- Normal State -->
               <template v-if="confirmingId !== company.id">
                  <button 
                     v-if="!company.is_verified"
                     :disabled="processing"
                     type="button"
                     class="flex-1 py-3 bg-primary-600 text-white rounded-xl text-xs font-bold hover:bg-primary-700 shadow-lg shadow-primary-500/20 dark:shadow-none transition-all active:scale-95"
                     @click.stop="startConfirm(company.id, 'verify')"
                  >
                     Verifikasi
                  </button>
                  <button 
                     v-else
                     :disabled="processing"
                     type="button"
                     class="flex-1 py-3 bg-slate-100 dark:bg-slate-900 text-red-600 rounded-xl text-xs font-bold hover:bg-red-50 transition-all active:scale-95"
                     @click.stop="startConfirm(company.id, 'unverify')"
                  >
                     Cabut Verifikasi
                  </button>
                  <Link :href="route('companies.show', { slug: company.slug })" target="_blank" class="p-3 bg-slate-50 dark:bg-slate-900 text-slate-400 hover:text-primary-600 rounded-xl transition-all" @click.stop>
                     <ExternalLink class="w-4 h-4" />
                  </Link>
               </template>

               <!-- Confirm State (Inline) -->
               <template v-else>
                  <div class="flex-1 flex items-center justify-between bg-slate-50 dark:bg-slate-800/50 p-1.5 rounded-xl border border-primary-100 dark:border-primary-900/30">
                     <span class="text-[10px] font-bold text-primary-600 px-2 uppercase tracking-tight">Yakin?</span>
                     <div class="flex gap-1.5">
                        <button 
                           :disabled="processing"
                           class="px-3 py-1.5 text-[10px] font-bold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white transition-colors"
                           @click.stop="cancelConfirm"
                        >
                           Batal
                        </button>
                        <button 
                           :disabled="processing"
                           class="px-4 py-1.5 bg-primary-600 text-white text-[10px] font-bold rounded-lg shadow-md shadow-primary-500/20 active:scale-95 transition-all"
                           @click.stop="handleConfirmedAction(company.id)"
                        >
                           Ya
                        </button>
                     </div>
                  </div>
               </template>
            </div>
          </Card>
        </div>

        <div v-if="companies.data.length === 0 && !loading" class="col-span-full py-20 text-center bg-slate-50/50 dark:bg-slate-900/20 rounded-2xl border border-dashed border-slate-200 dark:border-slate-800">
           <Building2 class="w-16 h-16 text-slate-200 mx-auto mb-4" />
           <p class="font-bold text-slate-900 dark:text-white">Tidak ada data perusahaan</p>
           <p class="text-sm text-slate-500">Gunakan kolom pencarian untuk menemukan perusahaan.</p>
        </div>

        <!-- Pagination -->
        <div v-if="companies.links && companies.links.length > 3" class="mt-8 flex justify-center">
            <Pagination :links="companies.links" />
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

