<script setup lang="ts">
import logger from '@/Lib/logger';
import { ref, onMounted } from 'vue';
import { Head } from '@/Components';
import api from '@/Services/api';

import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import { 
  Calendar, 
  Clock, 
  MapPin, 
  User,
  Search,
  Filter,
  CheckCircle2,
  XCircle,
  AlertCircle,
  Loader2
} from 'lucide-vue-next';
import { formatDate } from '@/Lib/utils';

const props = defineProps<{
    attendances: { data: any[], links: any[] };
}>();

const getStatusClass = (status: string) => {
  switch (status) {
    case 'present': return 'bg-green-50 text-green-600';
    case 'late': return 'bg-orange-50 text-orange-600';
    case 'absent': return 'bg-red-50 text-red-600';
    default: return 'bg-slate-50 text-slate-600';
  }
};
</script>

<template>
  <DashboardLayout>
    <Head title="Monitoring Absensi Mentee" />
    
    <div class="space-y-10">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Monitoring Absensi</h1>
          <p class="text-slate-500 dark:text-slate-400">Pantau kehadiran dan lokasi kerja mentee Anda secara real-time.</p>
        </div>
        
        <div class="flex items-center gap-3">
          <div class="relative group">
            <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-primary-600 transition-colors" />
            <input 
              type="text" 
              placeholder="Cari mentee..." 
              class="pl-11 pr-6 py-3 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-colors w-64"
            />
          </div>
          <button class="p-3 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl text-slate-500 hover:text-primary-600 transition-colors">
            <Filter class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- Attendance Table -->
      <Card class="overflow-hidden border-none shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800">
                <th class="px-8 py-5 text-xs font-bold text-slate-400 font-medium">Mentee</th>
                <th class="px-8 py-5 text-xs font-bold text-slate-400 font-medium">Tanggal</th>
                <th class="px-8 py-5 text-xs font-bold text-slate-400 font-medium">Check In</th>
                <th class="px-8 py-5 text-xs font-bold text-slate-400 font-medium">Check Out</th>
                <th class="px-8 py-5 text-xs font-bold text-slate-400 font-medium text-center">Status</th>
                <th class="px-8 py-5 text-xs font-bold text-slate-400 font-medium">Lokasi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
              <tr v-for="attendance in attendances.data" :key="attendance.id" class="hover:bg-slate-50/30 dark:hover:bg-slate-900/20 transition-colors group">
                <td class="px-8 py-6">
                  <div class="flex items-center gap-4">
                    <div class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-500">
                      {{ attendance.user.name.charAt(0) }}
                    </div>
                    <div>
                      <p class="text-sm font-bold text-slate-900 dark:text-white">{{ attendance.user.name }}</p>
                      <p class="text-xs text-slate-500 line-clamp-1">{{ attendance.application.internship.title }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-8 py-6">
                  <div class="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400 font-medium">
                    <Calendar class="w-3.5 h-3.5 text-slate-300" />
                    {{ formatDate(attendance.check_in_at, { day: 'numeric', month: 'short', year: 'numeric' }) }}
                  </div>
                </td>
                <td class="px-8 py-6">
                  <div v-if="attendance.check_in_at" class="flex items-center gap-2 text-xs text-slate-900 dark:text-white font-bold">
                    <Clock class="w-3.5 h-3.5 text-green-500" />
                    {{ formatDate(attendance.check_in_at, { hour: '2-digit', minute: '2-digit' }) }}
                  </div>
                  <span v-else class="text-xs text-slate-400">--:--</span>
                </td>
                <td class="px-8 py-6">
                  <div v-if="attendance.check_out_at" class="flex items-center gap-2 text-xs text-slate-900 dark:text-white font-bold">
                    <Clock class="w-3.5 h-3.5 text-orange-500" />
                    {{ formatDate(attendance.check_out_at, { hour: '2-digit', minute: '2-digit' }) }}
                  </div>
                  <span v-else class="text-xs text-slate-400">--:--</span>
                </td>
                <td class="px-8 py-6 text-center">
                  <span :class="['px-3 py-1 rounded-full text-xs font-medium', getStatusClass(attendance.status)]">
                    {{ attendance.status }}
                  </span>
                </td>
                <td class="px-8 py-6">
                  <div v-if="attendance.check_in_location?.lat" class="flex items-center gap-2 text-xs text-slate-500">
                    <MapPin class="w-3.5 h-3.5 text-primary-500" />
                    <a :href="`https://www.google.com/maps?q=${attendance.check_in_location.lat},${attendance.check_in_location.lng}`" target="_blank" class="hover:text-primary-600 hover:underline transition-colors">
                      Lihat di Maps
                    </a>
                  </div>
                  <span v-else class="text-xs text-slate-400 font-medium italic">Tidak ada koordinat</span>
                </td>
              </tr>
              <tr v-if="attendances.data.length === 0">
                <td colspan="6" class="px-8 py-20 text-center">
                   <div class="w-16 h-16 bg-slate-50 dark:bg-slate-900 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                     <Clock class="w-8 h-8" />
                   </div>
                   <p class="font-bold text-slate-900 dark:text-white">Belum ada data absensi</p>
                   <p class="text-sm text-slate-500">Data kehadiran mentee Anda hari ini akan muncul di sini.</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>
    </div>
  </DashboardLayout>
</template>
