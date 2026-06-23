<script setup lang="ts">
import logger from '@/Lib/logger';
import { Link } from '@inertiajs/vue3';
import { ref, onMounted, watch, computed } from 'vue';
import { Head } from '@/Components';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Pagination from '@/Components/Pagination.vue';
import { 
  Briefcase, Search, Flag, Eye, 
  Archive, CheckCircle2, MoreVertical,
  Building2, Loader2, AlertTriangle, Check, X,
  ShieldAlert, Sparkles, HelpCircle
} from 'lucide-vue-next';
import { useLangStore } from '@/Stores/lang';
import api from '@/Services/api';
import type { Internship } from '@/Types/internship';
import type { PaginatedResponse } from '@/Types/user';

import { router as inertiaRouter } from '@inertiajs/vue3';

const props = defineProps<{
    internships?: PaginatedResponse<Internship>;
    filters?: { search?: string };
}>();

const urlParams = new URLSearchParams(window.location.search);
const langStore = useLangStore();
const t = (key: string) => langStore.t(key);

const loading = ref(false);
const processing = ref(false);
const internships = computed(() => props.internships || {
  data: [],
  links: [],
  meta: {} as any
});

const search = ref(props.filters?.search || '');
const currentPage = ref(urlParams.get('page') || 1);

// Auto-Spam & Content Flagging System
const spamKeywords = [
  'slot', 'gacor', 'casino', 'judi', 'crypto', 'investasi', 'cepat kaya', 'tanpa modal', 'bergaransi',
  'btc', 'lunas', 'menang', 'bonus deposit', 'promosi judi', 'betting', 'togel'
];

const analyzeSpamRisk = (job: Internship) => {
  let riskScore = 0;
  const reasons: string[] = [];

  let reqText = '';
  if (Array.isArray(job.requirements)) {
    reqText = job.requirements.join(' ');
  } else if (typeof job.requirements === 'string') {
    reqText = job.requirements;
  }

  const textToScan = `${job.title} ${job.description || ''} ${reqText}`.toLowerCase();

  // Scan keywords
  spamKeywords.forEach(kw => {
    if (textToScan.includes(kw)) {
      riskScore += 40;
      reasons.push(`Kata sensitif: "${kw}"`);
    }
  });

  // Check description length
  if (!job.description || job.description.length < 30) {
    riskScore += 30;
    reasons.push('Deskripsi terlalu pendek (< 30 karakter)');
  }

  // Check title casing
  if (job.title && job.title === job.title.toUpperCase() && job.title.length > 5) {
    riskScore += 20;
    reasons.push('Judul menggunakan huruf KAPITAL semua');
  }

  // Determine risk category
  let riskLevel = 'safe';
  let riskLabel = 'Lolos Scan AI';
  let colorClass = 'bg-emerald-50 text-emerald-600 border-emerald-100 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/30';

  if (riskScore >= 60) {
    riskLevel = 'high';
    riskLabel = 'Indikasi Spam / Judi';
    colorClass = 'bg-rose-50 text-rose-600 border-rose-100 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/30';
  } else if (riskScore > 0) {
    riskLevel = 'medium';
    riskLabel = 'Perlu Tinjauan';
    colorClass = 'bg-amber-50 text-amber-600 border-amber-100 dark:bg-amber-950/20 dark:text-amber-400 dark:border-amber-900/30';
  }

  return {
    score: Math.min(riskScore, 100),
    level: riskLevel,
    label: riskLabel,
    colorClass,
    reasons
  };
};

// Review Drawer States & Actions
const selectedJobForReview = ref<Internship | null>(null);
const showQuickDrawer = ref(false);

const openReviewDrawer = (job: Internship) => {
  selectedJobForReview.value = job;
  showQuickDrawer.value = true;
};

const closeReviewDrawer = () => {
  showQuickDrawer.value = false;
  selectedJobForReview.value = null;
};

const updateStatusDirectly = async (internshipId: number, status: string) => {
    processing.value = true;
    try {
        await api.patch(`/admin/internships/${internshipId}/status`, { status });
        inertiaRouter.reload({ only: ['internships'] });
        // If the open drawer matches the updated item, refresh its status in real time
        if (selectedJobForReview.value && selectedJobForReview.value.id === internshipId) {
            selectedJobForReview.value.status = status as any;
        }
    } catch (error) {
        alert(t('common.error_occurred'));
    } finally {
        processing.value = false;
    }
};

const fetchInternships = () => {
    inertiaRouter.get('/admin/internships', { 
        search: search.value,
        page: currentPage.value
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

const updateStatus = async (internshipId: number, status: string) => {
    if (confirm(t('admin.internships.update_confirm').replace(':status', status))) {
        processing.value = true;
        try {
            await api.patch(`/admin/internships/${internshipId}/status`, { status });
            inertiaRouter.reload({ only: ['internships'] });
        } catch (error) {
            alert(t('common.error_occurred'));
        } finally {
            processing.value = false;
        }
    }
};

onMounted(() => {
    langStore.fetchTranslations();
});
</script>

<template>
  <Head :title="t('admin.internships.title')" />

  <DashboardLayout>
    <div class="space-y-10">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div v-if="!langStore.loading">
          <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">{{ t('admin.internships.title') }}</h1>
          <p class="text-slate-500 dark:text-slate-400">{{ t('admin.internships.desc') }}</p>
        </div>
        <div v-else class="h-16 w-64 bg-slate-100 dark:bg-slate-800 animate-pulse rounded-2xl"></div>
        
        <div class="flex items-center gap-3">
          <div class="relative group">
            <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-primary-600 transition-colors" />
            <input 
              v-model="search" 
              type="text"
              :placeholder="t('admin.internships.search_placeholder')"
              class="pl-11 pr-6 py-3 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all w-64" 
              @keyup.enter="fetchInternships"
            />
          </div>
        </div>
      </div>

      <!-- Internship List -->
      <Card class="overflow-hidden border-none shadow-sm relative">
        <div v-if="loading" class="absolute inset-0 bg-white/50 dark:bg-slate-900/50 backdrop-blur-[1px] z-10 flex items-center justify-center">
            <Loader2 class="w-8 h-8 text-primary-600 animate-spin" />
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800">
                <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('admin.internships.col_internship') }}</th>
                <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('admin.internships.col_company') }}</th>
                <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">{{ t('admin.internships.col_status') }}</th>
                <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('admin.internships.col_posted') }}</th>
                <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">{{ t('admin.internships.col_actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
              <tr v-for="job in internships.data" :key="job.id" class="hover:bg-slate-50/30 dark:hover:bg-slate-900/20 transition-colors">
                <td class="px-8 py-6">
                  <div class="flex items-center gap-2 flex-wrap">
                    <p class="font-bold text-slate-900 dark:text-white">{{ job.title }}</p>
                    <span 
                      class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest border shrink-0" 
                      :class="[analyzeSpamRisk(job).colorClass]"
                      :title="analyzeSpamRisk(job).reasons.join(', ')"
                    >
                      {{ analyzeSpamRisk(job).label }}
                    </span>
                  </div>
                  <p class="text-xs text-slate-500 line-clamp-1">{{ job.location }} • {{ job.type }}</p>
                </td>
                <td class="px-8 py-6">
                  <div class="flex items-center gap-3">
                     <div class="w-8 h-8 bg-slate-50 dark:bg-slate-800 rounded-lg flex items-center justify-center text-slate-400 shrink-0">
                        <Building2 class="w-4 h-4" />
                     </div>
                     <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">{{ job.company?.name }}</p>
                  </div>
                </td>
                <td class="px-8 py-6 text-center">
                  <StatusBadge :status="job.status || 'draft'" />
                </td>
                <td class="px-8 py-6">
                   <p class="text-xs text-slate-500">{{ job.created_at_human }}</p>
                </td>
                <td class="px-8 py-6 text-right">
                   <div class="flex items-center justify-end gap-2">
                      <!-- Quick Action Drawer Trigger -->
                      <button 
                         class="p-2 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all shrink-0 active-press"
                         title="Tinjau Cepat (Quick Drawer)"
                         @click="openReviewDrawer(job)"
                      >
                         <Sparkles class="w-4 h-4 text-indigo-500 animate-pulse" />
                      </button>

                      <Link :href="'/internships/' + job.slug" target="_blank" class="p-2 text-slate-400 hover:text-primary-600 transition-all" :title="t('common.view')">
                         <Eye class="w-4 h-4" />
                      </Link>
                      <button 
                        v-if="job.status !== 'published'"
                        class="p-2 text-slate-400 hover:text-green-600 transition-all"
                        :title="t('admin.internships.publish_tooltip')"
                        @click="updateStatus(job.id, 'published')"
                      >
                         <CheckCircle2 class="w-4 h-4" />
                      </button>
                      <button 
                        v-if="job.status !== 'flagged'"
                        class="p-2 text-slate-400 hover:text-orange-600 transition-all"
                        :title="t('admin.internships.flag_tooltip')"
                        @click="updateStatus(job.id, 'flagged')"
                      >
                         <Flag class="w-4 h-4" />
                      </button>
                      <button 
                        v-if="job.status !== 'archived'"
                        class="p-2 text-slate-400 hover:text-red-600 transition-all"
                        :title="t('admin.internships.archive_tooltip')"
                        @click="updateStatus(job.id, 'archived')"
                      >
                         <Archive class="w-4 h-4" />
                      </button>
                   </div>
                </td>
              </tr>
              <tr v-if="internships.data.length === 0 && !loading">
                <td colspan="5" class="px-8 py-20 text-center">
                   <Briefcase class="w-16 h-16 text-slate-200 mx-auto mb-4" />
                   <p class="font-bold text-slate-900 dark:text-white">{{ t('admin.internships.empty') }}</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Pagination -->
        <div v-if="internships.links && internships.links.length > 3" class="px-8 py-6 border-t border-slate-50 dark:border-slate-800">
            <Pagination :links="internships.links" />
        </div>
      </Card>
    </div>

    <!-- Quick Action Drawer (Sliding Sidebar Drawer) -->
    <div 
      v-if="showQuickDrawer && selectedJobForReview" 
      class="fixed inset-0 z-50 overflow-hidden pointer-events-none"
    >
      <!-- Backdrop with blur -->
      <div 
        class="absolute inset-0 bg-slate-900/40 dark:bg-slate-950/60 backdrop-blur-sm pointer-events-auto transition-opacity duration-300"
        @click="closeReviewDrawer"
      ></div>

      <!-- Sliding panel -->
      <div class="absolute inset-y-0 right-0 max-w-full flex pl-10 pointer-events-auto">
        <div class="w-screen max-w-lg bg-white dark:bg-slate-900 shadow-2xl flex flex-col border-l border-slate-100 dark:border-slate-800 animate-in slide-in-from-right duration-300">
          
          <!-- Drawer Header -->
          <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-500/20 text-white">
                <Sparkles class="w-5 h-5 animate-pulse" />
              </div>
              <div>
                <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest">Moderasi Cepat AI</h3>
                <p class="text-[10px] text-slate-500 font-medium">Asisten Tinjau Otomatis InternHub</p>
              </div>
            </div>
            <button 
              class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all active-press" 
              @click="closeReviewDrawer"
            >
              <X class="w-5 h-5" />
            </button>
          </div>

          <!-- Drawer Content -->
          <div class="flex-1 overflow-y-auto p-6 space-y-6">
            
            <!-- Company & Basic Job Header -->
            <div class="p-5 bg-slate-50 dark:bg-slate-950/50 rounded-2xl border border-slate-100 dark:border-slate-800">
              <span class="px-2 py-0.5 bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-[9px] font-black uppercase tracking-widest rounded-lg">Lowongan Magang</span>
              <h2 class="text-lg font-extrabold text-slate-900 dark:text-white mt-2 leading-snug">{{ selectedJobForReview.title }}</h2>
              <div class="flex items-center gap-2 text-xs text-slate-500 font-medium mt-1">
                <span>{{ selectedJobForReview.company?.name }}</span>
                <span>•</span>
                <span>{{ selectedJobForReview.location }}</span>
              </div>
            </div>

            <!-- Spam Audit Analysis -->
            <div class="p-6 rounded-2xl border transition-all duration-300" :class="[analyzeSpamRisk(selectedJobForReview).colorClass]">
              <div class="flex items-start gap-3">
                <div class="shrink-0 mt-0.5">
                  <ShieldAlert v-if="analyzeSpamRisk(selectedJobForReview).level === 'high'" class="w-5 h-5 animate-bounce" />
                  <AlertTriangle v-else-if="analyzeSpamRisk(selectedJobForReview).level === 'medium'" class="w-5 h-5" />
                  <CheckCircle2 v-else class="w-5 h-5" />
                </div>
                <div class="space-y-1">
                  <h4 class="text-xs font-black uppercase tracking-widest">Hasil Scan Otomatis</h4>
                  <p class="text-sm font-bold">{{ analyzeSpamRisk(selectedJobForReview).label }} (Spam Score: {{ analyzeSpamRisk(selectedJobForReview).score }}%)</p>
                  
                  <!-- Detection Reasons -->
                  <ul v-if="analyzeSpamRisk(selectedJobForReview).reasons.length > 0" class="mt-3 space-y-1 text-xs list-disc pl-4 font-semibold">
                    <li v-for="(reason, rIdx) in analyzeSpamRisk(selectedJobForReview).reasons" :key="rIdx">
                      {{ reason }}
                    </li>
                  </ul>
                  <p v-else class="mt-2 text-xs opacity-80">Tidak terdeteksi adanya indikasi spam atau bahasa iklan yang mencurigakan.</p>
                </div>
              </div>
            </div>

            <!-- Job Description Detail -->
            <div class="space-y-3">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Deskripsi Posisi</label>
              <div class="p-5 bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 rounded-2xl text-xs leading-relaxed text-slate-700 dark:text-slate-350 shadow-inner whitespace-pre-wrap max-h-48 overflow-y-auto">
                {{ selectedJobForReview.description || 'Tidak ada deskripsi.' }}
              </div>
            </div>

            <!-- Requirements Detail -->
            <div class="space-y-3">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Persyaratan & Keahlian</label>
              <div class="p-5 bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 rounded-2xl text-xs leading-relaxed text-slate-700 dark:text-slate-350 shadow-inner max-h-48 overflow-y-auto">
                <ul v-if="selectedJobForReview.requirements && selectedJobForReview.requirements.length > 0" class="list-disc pl-4 space-y-1">
                  <li v-for="(req, reqIdx) in selectedJobForReview.requirements" :key="reqIdx">
                    {{ req }}
                  </li>
                </ul>
                <p v-else class="text-slate-400 italic">Tidak ada persyaratan khusus yang didaftarkan.</p>
              </div>
            </div>

            <!-- Audit Log Tracking -->
            <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-950/30 rounded-xl text-xs">
              <span class="text-slate-500 font-bold">STATUS SEKARANG</span>
              <StatusBadge :status="selectedJobForReview.status || 'draft'" />
            </div>

          </div>

          <!-- Drawer Footer - Quick Actions -->
          <div class="p-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex gap-3">
            <button 
              v-if="selectedJobForReview.status !== 'archived'"
              :disabled="processing"
              class="flex-1 py-3 border border-rose-200 dark:border-rose-900/30 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/20 text-xs font-black rounded-xl transition-all active-press flex items-center justify-center gap-2"
              @click="updateStatusDirectly(selectedJobForReview.id, 'archived')"
            >
              <X class="w-4 h-4" />
              Tolak
            </button>

            <button 
              v-if="selectedJobForReview.status !== 'flagged'"
              :disabled="processing"
              class="flex-1 py-3 border border-amber-200 dark:border-amber-900/30 text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/20 text-xs font-black rounded-xl transition-all active-press flex items-center justify-center gap-2"
              @click="updateStatusDirectly(selectedJobForReview.id, 'flagged')"
            >
              <Flag class="w-4 h-4" />
              Tandai Flag
            </button>

            <button 
              v-if="selectedJobForReview.status !== 'published'"
              :disabled="processing"
              class="flex-1 py-3 bg-green-600 hover:bg-green-700 text-white text-xs font-black rounded-xl shadow-lg shadow-green-500/10 transition-all active-press flex items-center justify-center gap-2"
              @click="updateStatusDirectly(selectedJobForReview.id, 'published')"
            >
              <Check class="w-4 h-4" />
              Setujui
            </button>
          </div>

        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

