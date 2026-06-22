<script setup lang="ts">
import logger from '@/Lib/logger';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Head } from '@/Components';

import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import { 
  BarChart3, 
  TrendingUp, 
  Users, 
  Building2, 
  FileText,
  Calendar,
  ArrowUpRight,
  PieChart,
  Loader2
} from 'lucide-vue-next';
import api from '@/Services/api';
import { router as inertiaRouter } from '@inertiajs/vue3';

const props = defineProps<{
    applicationStats?: any[];
    userGrowth?: any[];
    companyStats?: any[];
}>();

const loading = ref(false);

const applicationStats = computed(() => props.applicationStats || []);
const userGrowth = computed(() => props.userGrowth || []);
const companyStats = computed(() => props.companyStats || []);

const fetchReports = () => {
    inertiaRouter.reload({ only: ['applicationStats', 'userGrowth', 'companyStats'] });
};

const downloadPDF = () => {
    window.print();
};

const goToMasterData = () => {
    inertiaRouter.visit('/admin/internships');
};

let refreshInterval: any = null;

onMounted(() => {
    // Refresh analytics every 2 minutes
    refreshInterval = setInterval(fetchReports, 120000);
});

onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
});
</script>

<template>
  <Head title="Report Viewer" />

  <DashboardLayout>
    <div v-if="loading" class="flex items-center justify-center min-h-[60vh]">
        <Loader2 class="w-12 h-12 text-primary-600 animate-spin" />
    </div>
    <div v-else class="space-y-10">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2 text-glow-sm">Analitik & Laporan 📊</h1>
          <p class="text-slate-500 dark:text-slate-400 font-medium">Pantau pertumbuhan ekosistem InterHub secara real-time.</p>
        </div>
        <div class="flex items-center gap-3 bg-white dark:bg-slate-800 p-2 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm">
           <div class="px-4 py-2 flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-widest border-r border-slate-100 dark:border-slate-700">
             <Calendar class="w-4 h-4" />
             30 Hari Terakhir
           </div>
           <button 
             @click="downloadPDF"
             class="px-4 py-2 text-xs font-bold text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-xl transition-all uppercase tracking-widest"
           >
             Unduh PDF
           </button>
        </div>
      </div>

      <!-- Growth Section -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- User Growth Chart Placeholder (Premium Visual) -->
        <Card class="lg:col-span-2 p-8 border-none shadow-sm relative overflow-hidden group">
          <div class="flex items-center justify-between mb-10">
            <div>
              <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <TrendingUp class="w-5 h-5 text-green-500" />
                Pertumbuhan Pengguna
              </h3>
              <p class="text-xs text-slate-400 font-medium uppercase tracking-widest mt-1">Harian - 30 Hari Terakhir</p>
            </div>
            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-2xl">
              <ArrowUpRight class="w-5 h-5 text-green-600" />
            </div>
          </div>

          <!-- Fake Chart Visualization with CSS (Simulating a premium bar chart) -->
          <div class="h-64 flex items-end justify-between gap-2 px-2">
            <div 
              v-for="(point, i) in userGrowth" 
              :key="i" 
              class="flex-1 bg-primary-500/20 hover:bg-primary-500 rounded-t-lg transition-all relative group/bar"
              :style="{ height: `${Math.min((point.total / 10) * 100, 100)}%` }"
            >
               <div class="absolute -top-10 left-1/2 -translate-x-1/2 bg-slate-900 text-white text-[10px] px-2 py-1 rounded opacity-0 group-hover/bar:opacity-100 transition-opacity whitespace-nowrap z-10">
                 {{ point.total }} User
               </div>
            </div>
          </div>
          <div class="mt-6 pt-6 border-t border-slate-50 dark:border-slate-800 flex justify-between text-[10px] font-bold text-slate-300 uppercase tracking-tighter">
             <span>{{ userGrowth[0]?.date }}</span>
             <span>{{ userGrowth[Math.floor(userGrowth.length/2)]?.date }}</span>
             <span>Hari Ini</span>
          </div>
        </Card>

        <!-- Stats Overview -->
        <div class="space-y-6">
          <Card class="p-6 border-none shadow-sm bg-gradient-to-br from-indigo-500 to-purple-600 text-white">
            <div class="flex items-center justify-between mb-4">
               <div class="p-3 bg-white/20 rounded-2xl backdrop-blur-sm">
                 <FileText class="w-6 h-6" />
               </div>
               <span class="text-xs font-bold bg-white/20 px-2 py-1 rounded-lg">High Retention</span>
            </div>
            <p class="text-xs font-bold text-indigo-100 uppercase tracking-widest mb-1">Total Lamaran</p>
            <p class="text-4xl font-black mb-6">{{ applicationStats.reduce((a, b) => a + b.total, 0) }}</p>
            <div class="flex gap-2">
               <div v-for="stat in applicationStats.slice(0,3)" :key="stat.status" class="flex-1 bg-white/10 p-2 rounded-xl text-center">
                 <p class="text-[8px] font-bold uppercase opacity-60 truncate">{{ stat.status }}</p>
                 <p class="text-sm font-bold">{{ stat.total }}</p>
               </div>
            </div>
          </Card>

          <Card class="p-8 border-none shadow-sm group">
            <div class="flex items-center gap-4 mb-6">
              <div class="w-12 h-12 bg-slate-50 dark:bg-slate-900 rounded-2xl flex items-center justify-center text-slate-400 group-hover:text-primary-600 transition-colors">
                <PieChart class="w-6 h-6" />
              </div>
              <div>
                <h4 class="font-bold text-slate-900 dark:text-white">Verifikasi Company</h4>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Rasio Keamanan</p>
              </div>
            </div>
            <div class="space-y-4">
              <div v-for="stat in companyStats" :key="stat.is_verified" class="space-y-2">
                <div class="flex justify-between text-xs font-bold uppercase tracking-widest">
                  <span class="text-slate-500">{{ stat.is_verified ? 'Terverifikasi' : 'Pending' }}</span>
                  <span class="text-slate-900 dark:text-white">{{ stat.total }}</span>
                </div>
                <div class="h-2 bg-slate-100 dark:bg-slate-900 rounded-full overflow-hidden">
                  <div 
                    class="h-full transition-all duration-1000" 
                    :class="stat.is_verified ? 'bg-green-500' : 'bg-orange-400'"
                    :style="{ width: `${(stat.total / companyStats.reduce((a,b) => a + b.total, 0)) * 100}%` }"
                  ></div>
                </div>
              </div>
            </div>
          </Card>
        </div>
      </div>

      <!-- Actionable Insights -->
      <section class="space-y-6">
        <h2 class="text-xl font-bold text-slate-900 dark:text-white px-2">Data Insight Strategis</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
           <Card class="p-8 border-none shadow-sm bg-slate-900 text-white relative overflow-hidden group">
              <div class="relative z-10">
                <h3 class="text-xl font-bold mb-2">Platform Health Score</h3>
                <p class="text-slate-400 text-sm mb-8">Berdasarkan rasio lamaran dan verifikasi perusahaan 30 hari terakhir.</p>
                <div class="flex items-end gap-2">
                  <span class="text-6xl font-black text-primary-500">94</span>
                  <span class="text-xl font-bold text-slate-500 mb-2">/100</span>
                </div>
              </div>
              <BarChart3 class="absolute -right-10 -bottom-10 w-64 h-64 text-slate-800/50 group-hover:rotate-12 transition-transform duration-700" />
           </Card>

           <Card class="p-8 border-none shadow-sm flex items-center justify-between group">
              <div class="max-w-[60%]">
                <div class="p-3 bg-primary-50 dark:bg-primary-900/20 w-fit rounded-2xl mb-4">
                  <Users class="w-6 h-6 text-primary-600" />
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Siap untuk Ekspansi?</h3>
                <p class="text-xs text-slate-500 leading-relaxed">Pertumbuhan user stabil di angka 12% MoM. Disarankan untuk menambah kategori lowongan baru untuk menjaga engagement.</p>
              </div>
              <button 
                @click="goToMasterData"
                class="bg-primary-600 text-white px-6 py-3 rounded-2xl font-bold text-sm hover:scale-105 active:scale-95 transition-all shadow-lg shadow-primary-500/25"
              >
                Kelola Master Data
              </button>
           </Card>
        </div>
      </section>
    </div>
  </DashboardLayout>
</template>

<style scoped>
.text-glow-sm {
  text-shadow: 0 0 20px rgba(var(--primary-rgb), 0.1);
}

@media print {
  /* Hide navigation, sidebar, and other UI clutter */
  :deep(.sidebar), 
  :deep(.main-header),
  button,
  .no-print {
    display: none !important;
  }

  /* Reset layout for print */
  :deep(.p-8), :deep(.lg\:p-12) {
    padding: 0 !important;
  }

  .space-y-10 {
    gap: 0 !important;
  }

  /* Ensure colors and backgrounds print */
  * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  body {
    background: white !important;
  }

  .grid {
    display: block !important;
  }

  .grid > * {
    margin-bottom: 2rem !important;
    page-break-inside: avoid !important;
  }
}
</style>
