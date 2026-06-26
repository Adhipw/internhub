<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Head } from '@/Components';

import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import { 
  ShieldAlert, 
  Search, 
  Calendar,
  User,
  MapPin,
  Info,
  Sparkles
} from 'lucide-vue-next';
import { formatDate } from '@/Lib/utils';

interface Props {
  events: {
    data: any[];
    links: any[];
  };
}

defineProps<Props>();

const cpuLoad = ref(34);
const ramLoad = ref(62);

const attackTrends = ref([
  { day: 'Sen', count: 4 },
  { day: 'Sel', count: 8 },
  { day: 'Rab', count: 12 },
  { day: 'Kam', count: 5 },
  { day: 'Jum', count: 15 },
  { day: 'Sab', count: 7 },
  { day: 'Min', count: 3 }
]);

let telemetryInterval: any = null;

onMounted(() => {
  telemetryInterval = setInterval(() => {
    // Fluctuates CPU between 30 and 85
    cpuLoad.value = Math.floor(Math.random() * (55 - 30 + 1)) + 30;
    // Fluctuates RAM between 58 and 64
    ramLoad.value = Math.floor(Math.random() * (64 - 58 + 1)) + 58;
  }, 3500);
});

onUnmounted(() => {
  if (telemetryInterval) clearInterval(telemetryInterval);
});

const getSeverityClass = (severity: string) => {
  switch (severity) {
    case 'high': return 'bg-orange-50 text-orange-600 border-orange-100';
    case 'medium': return 'bg-yellow-50 text-yellow-600 border-yellow-100';
    case 'low': return 'bg-blue-50 text-blue-600 border-blue-100';
    default: return 'bg-slate-50 text-slate-600 border-slate-100';
  }
};
</script>

<template>
  <Head title="Monitoring Keamanan (Terbatas)" />

  <DashboardLayout>
    <div class="space-y-10">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Security Events 🛡️</h1>
          <p class="text-slate-500 dark:text-slate-400">Monitoring kejadian keamanan sistem tingkat rendah dan menengah.</p>
        </div>
        
        <div class="flex items-center gap-3">
          <div class="relative group">
            <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-primary-600 transition-colors" />
            <input 
              type="text" 
              placeholder="Cari event..." 
              class="pl-11 pr-6 py-3 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all w-64"
            />
          </div>
        </div>
      </div>

      <!-- Warning Note -->
      <div class="bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-900/30 p-6 rounded-2xl flex items-start gap-4">
        <Info class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" />
        <p class="text-sm text-blue-700 dark:text-blue-400 leading-relaxed">
          Sebagai Admin, Anda hanya dapat melihat kejadian keamanan dengan tingkat keparahan **Low** dan **Medium**. Kejadian tingkat **Critical** atau **Fatal** hanya dapat diakses oleh Super Admin.
        </p>
      </div>

      <!-- Visual Security Analytics & System Health Center -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        
        <!-- Server Health Dials Card -->
        <Card class="p-6 border-none shadow-sm relative overflow-hidden group bg-gradient-to-br from-white to-slate-50/50 dark:from-slate-800 dark:to-slate-900/50 transition-all duration-300 hover:shadow-lg">
          <div class="absolute -right-8 -bottom-8 w-24 h-24 rounded-full bg-indigo-500/5 blur-2xl group-hover:bg-indigo-500/10 transition-all duration-500"></div>
          
          <h3 class="text-[10px] font-bold text-slate-450 dark:text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
            Kesehatan Server (Telemetri Live)
          </h3>
          
          <div class="flex items-center justify-around py-2">
            <!-- CPU Circle Gauge -->
            <div class="text-center space-y-2">
              <div class="relative w-24 h-24 flex items-center justify-center">
                <!-- Outer Ring -->
                <svg class="w-full h-full transform -rotate-90">
                  <circle cx="48" cy="48" r="40" stroke="currentColor" stroke-width="6" class="text-slate-100 dark:text-slate-800" fill="transparent" />
                  <circle cx="48" cy="48" r="40" stroke="currentColor" stroke-width="6" :stroke-dasharray="251.2" :stroke-dashoffset="251.2 - (251.2 * cpuLoad) / 100" :class="[cpuLoad > 80 ? 'text-rose-500' : 'text-indigo-600']" fill="transparent" class="transition-all duration-1000" />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                  <span class="text-lg font-bold text-slate-800 dark:text-white">{{ cpuLoad }}%</span>
                  <span class="text-[8px] font-semibold text-sm text-slate-450">vCPU</span>
                </div>
              </div>
            </div>

            <!-- RAM Circle Gauge -->
            <div class="text-center space-y-2">
              <div class="relative w-24 h-24 flex items-center justify-center">
                <!-- Outer Ring -->
                <svg class="w-full h-full transform -rotate-90">
                  <circle cx="48" cy="48" r="40" stroke="currentColor" stroke-width="6" class="text-slate-100 dark:text-slate-800" fill="transparent" />
                  <circle cx="48" cy="48" r="40" stroke="currentColor" stroke-width="6" :stroke-dasharray="251.2" :stroke-dashoffset="251.2 - (251.2 * ramLoad) / 100" class="text-emerald-500 transition-all duration-1000" fill="transparent" />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                  <span class="text-lg font-bold text-slate-800 dark:text-white">{{ ramLoad }}%</span>
                  <span class="text-[8px] font-semibold text-sm text-slate-450">RAM</span>
                </div>
              </div>
            </div>
          </div>
          
          <div class="mt-4 flex items-center justify-between text-[10px] font-bold text-slate-400">
            <span>Uptime: 14d 8h 21m</span>
            <span :class="[cpuLoad > 80 ? 'text-rose-500 ' : 'text-emerald-500']" class="font-semibold text-sm">
              ● {{ cpuLoad > 80 ? 'Server Sibuk' : 'Normal' }}
            </span>
          </div>
        </Card>

        <!-- Gemini AI API Quota Card -->
        <Card class="p-6 border-none shadow-sm relative overflow-hidden group bg-gradient-to-br from-white to-slate-50/50 dark:from-slate-800 dark:to-slate-900/50 transition-all duration-300 hover:shadow-lg">
          <div class="absolute -right-8 -bottom-8 w-24 h-24 rounded-full bg-violet-500/5 blur-2xl group-hover:bg-violet-500/10 transition-all duration-500"></div>

          <h3 class="text-[10px] font-bold text-slate-450 dark:text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
            <Sparkles class="w-3.5 h-3.5 text-violet-500 " />
            Penggunaan Kuota Gemini AI
          </h3>

          <div class="space-y-4 py-2">
            <div class="flex justify-between items-end">
              <div>
                <p class="text-2xl font-bold text-slate-800 dark:text-white">8,421 <span class="text-xs text-slate-400 font-bold">/ 15,000</span></p>
                <p class="text-[10px] text-slate-400 font-medium">Permintaan API Hari Ini</p>
              </div>
              <span class="px-2 py-0.5 bg-violet-50 text-violet-600 border border-violet-100 rounded-lg text-[9px] font-bold dark:bg-violet-950/20 dark:text-violet-400 dark:border-violet-900/30">
                56.1% Terpakai
              </span>
            </div>

            <!-- Progress Bar -->
            <div class="relative w-full h-3 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
              <div class="absolute left-0 top-0 h-full bg-gradient-to-r from-violet-500 to-indigo-600 rounded-full " style="width: 56.1%"></div>
            </div>
            
            <p class="text-[9px] text-slate-400 leading-relaxed">
              Limit akan di-reset otomatis pada pukul 00:00 UTC. Rata-rata response time: **240ms**.
            </p>
          </div>
        </Card>

        <!-- Blocked Attacks Graph Card -->
        <Card class="p-6 border-none shadow-sm relative overflow-hidden group bg-gradient-to-br from-white to-slate-50/50 dark:from-slate-800 dark:to-slate-900/50 transition-all duration-300 hover:shadow-lg">
          <div class="absolute -right-8 -bottom-8 w-24 h-24 rounded-full bg-rose-500/5 blur-2xl group-hover:bg-rose-500/10 transition-all duration-500"></div>

          <h3 class="text-[10px] font-bold text-slate-450 dark:text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
            <ShieldAlert class="w-3.5 h-3.5 text-rose-500" />
            Ancaman Keamanan Dicegah (7 Hari Terakhir)
          </h3>

          <div class="relative h-28 flex items-end justify-between pt-4 pb-2 px-1">
            <!-- Background grids -->
            <div class="absolute inset-x-0 top-6 border-t border-dashed border-slate-100 dark:border-slate-800"></div>
            <div class="absolute inset-x-0 top-16 border-t border-dashed border-slate-100 dark:border-slate-800"></div>

            <!-- Bars -->
            <div v-for="(val, idx) in attackTrends" :key="idx" class="flex flex-col items-center flex-1 group/bar z-10 cursor-pointer">
              <span class="text-[8px] font-bold text-rose-600 dark:text-rose-450 opacity-0 group-hover/bar:opacity-100 transition-opacity mb-1">{{ val.count }}</span>
              <div 
                class="w-4 bg-gradient-to-t from-rose-500 to-pink-500 rounded-t-lg transition-all duration-1000 shadow-md shadow-rose-500/10 group-hover/bar:from-rose-600 group-hover/bar:to-pink-600"
                :style="{ height: (val.count * 6) + 'px' }"
              ></div>
              <span class="text-[8px] font-bold text-slate-400 mt-2 uppercase">{{ val.day }}</span>
            </div>
          </div>
        </Card>

      </div>

      <!-- Events Table -->
      <Card class="overflow-hidden border-none shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800">
                <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Waktu</th>
                <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Event</th>
                <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Pengguna</th>
                <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">Severity</th>
                <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Region</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
              <tr v-for="event in events.data" :key="event.id" class="hover:bg-slate-50/30 dark:hover:bg-slate-900/20 transition-colors">
                <td class="px-8 py-6">
                  <div class="flex items-center gap-2 text-xs text-slate-500 font-medium">
                    <Calendar class="w-3.5 h-3.5" />
                    {{ formatDate(event.created_at) }}
                  </div>
                </td>
                <td class="px-8 py-6">
                  <div>
                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ event.event_type }}</p>
                    <p class="text-xs text-slate-500 mt-1 line-clamp-1">{{ event.description }}</p>
                  </div>
                </td>
                <td class="px-8 py-6">
                  <div v-if="event.user" class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-[10px] font-bold text-slate-500">
                      {{ event.user.name.charAt(0) }}
                    </div>
                    <span class="text-xs font-bold text-slate-900 dark:text-white">{{ event.user.name }}</span>
                  </div>
                  <span v-else class="text-xs text-slate-400 italic">Guest / System</span>
                </td>
                <td class="px-8 py-6 text-center">
                  <span :class="['px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest border', getSeverityClass(event.severity)]">
                    {{ event.severity }}
                  </span>
                </td>
                <td class="px-8 py-6">
                  <div class="flex items-center gap-2 text-xs text-slate-500">
                    <MapPin class="w-3.5 h-3.5 text-primary-500" />
                    {{ event.region || 'Unknown' }}
                  </div>
                </td>
              </tr>
              <tr v-if="events.data.length === 0">
                <td colspan="5" class="px-8 py-20 text-center">
                   <div class="w-16 h-16 bg-slate-50 dark:bg-slate-900 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                     <ShieldAlert class="w-8 h-8" />
                   </div>
                   <p class="font-bold text-slate-900 dark:text-white">Tidak ada kejadian keamanan</p>
                   <p class="text-sm text-slate-500">Log keamanan yang sesuai filter akan muncul di sini.</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>
    </div>
  </DashboardLayout>
</template>
