<script setup lang="ts">
import logger from '@/Lib/logger';
import { Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import api from '@/Services/api';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { 
  Users, Search, ChevronRight, Filter, ArrowUpDown, Mail, Calendar
} from 'lucide-vue-next';
import { formatDate } from '@/Lib/utils';
import type { Application } from '@/Types/application';
import type { PaginationLink } from '@/Types/user';

const props = defineProps<{
    mentees: { data: Application[], links: PaginationLink[] };
}>();

const loading = ref(false);
</script>

<template>
  <DashboardLayout>
    <div v-if="loading" class="flex justify-center py-20">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
    </div>

    <div v-else class="space-y-10">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Daftar Mentee</h1>
          <p class="text-slate-500 dark:text-slate-400">Kelola dan pantau seluruh kandidat yang ditugaskan kepada Anda.</p>
        </div>
        
        <div class="flex items-center gap-3">
          <div class="relative group">
            <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-primary-600 transition-colors" />
            <input 
              type="text" 
              placeholder="Cari nama mentee..." 
              class="pl-11 pr-6 py-3 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all w-64"
            />
          </div>
          <button class="p-3 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl text-slate-500 hover:text-primary-600 transition-all">
            <Filter class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- Mentees Table/Grid -->
      <Card class="overflow-hidden border-none shadow-sm">
        <div v-if="mentees.data.length > 0" class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800">
                <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Nama Mentee</th>
                <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Posisi Magang</th>
                <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">Status</th>
                <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Bergabung Pada</th>
                <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
              <tr v-for="mentee in mentees.data" :key="mentee.id" class="hover:bg-slate-50/30 dark:hover:bg-slate-900/20 transition-colors group">
                <td class="px-8 py-6">
                  <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-sm font-bold text-slate-500 shrink-0">
                      {{ mentee.user.name.charAt(0) }}
                    </div>
                    <div>
                      <p class="font-bold text-slate-900 dark:text-white">{{ mentee.user.name }}</p>
                      <p class="text-xs text-slate-500 flex items-center gap-1 mt-0.5">
                        <Mail class="w-3 h-3" />
                        {{ mentee.user.email }}
                      </p>
                    </div>
                  </div>
                </td>
                <td class="px-8 py-6">
                  <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">{{ mentee.internship.title }}</p>
                </td>
                <td class="px-8 py-6 text-center">
                  <StatusBadge :status="mentee.status" />
                </td>
                <td class="px-8 py-6">
                  <div class="flex items-center gap-2 text-xs text-slate-500">
                    <Calendar class="w-3.5 h-3.5" />
                    {{ formatDate(mentee.created_at) }}
                  </div>
                </td>
                <td class="px-8 py-6 text-right">
                  <Link 
                    :href="'/mentor/mentees/' + mentee.id" 
                    class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-bold hover:bg-primary-600 hover:text-white transition-all active-press"
                  >
                    Detail
                    <ChevronRight class="w-3.5 h-3.5" />
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <EmptyState 
            v-else-if="!loading"
            type="empty"
            title="Tidak ada data mentee"
            description="Kandidat yang ditugaskan kepada Anda akan muncul di sini."
        />

        <!-- Pagination Placeholder -->
        <div v-if="mentees.data.length > 0" class="px-8 py-6 bg-slate-50/30 dark:bg-slate-900/30 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
           <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Menampilkan {{ mentees.data.length }} Mentee</p>
           <div class="flex gap-2">
              <button disabled class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-xl text-[10px] font-bold text-slate-300 uppercase tracking-widest">Kembali</button>
              <button disabled class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-xl text-[10px] font-bold text-slate-300 uppercase tracking-widest">Lanjut</button>
           </div>
        </div>
      </Card>
    </div>
  </DashboardLayout>
</template>

