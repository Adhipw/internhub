<script setup lang="ts">
import logger from '@/Lib/logger';
import { Link, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import { 
  CheckSquare, Search, ChevronRight, Clock, Target, CheckCircle2
} from 'lucide-vue-next';
import { formatDate } from '@/Lib/utils';

const props = defineProps<{
    tasks: any;
}>();

const getPriorityLabel = (priority: number) => {
    switch(priority) {
        case 3: return { label: 'Tinggi', class: 'bg-red-50 text-red-600 border border-red-100' };
        case 2: return { label: 'Sedang', class: 'bg-orange-50 text-orange-600 border border-orange-100' };
        default: return { label: 'Rendah', class: 'bg-blue-50 text-blue-600 border border-blue-100' };
    }
};

const updateTaskStatus = (taskId: number, currentStatus: string) => {
    const newStatus = currentStatus === 'completed' ? 'in_progress' : 'completed';
    router.patch(`/mentor/tasks/${taskId}/status`, { status: newStatus }, { preserveScroll: true });
};
</script>

<template>
  <DashboardLayout>
    <div class="space-y-10">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Penugasan Mentee</h1>
          <p class="text-slate-500 dark:text-slate-400">Pantau seluruh tugas yang Anda berikan kepada mentee.</p>
        </div>
        
        <div class="flex items-center gap-3">
          <div class="relative group">
            <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-primary-600 transition-colors" />
            <input 
              type="text" 
              placeholder="Cari tugas..." 
              class="pl-11 pr-6 py-3 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all w-64"
            />
          </div>
        </div>
      </div>

      <!-- Tasks Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
          <Card v-for="(task, index) in tasks.data" :key="task.id" class="p-6 border-none shadow-sm flex flex-col group hover:-translate-y-1 transition-transform">
            <div class="flex items-start justify-between mb-4">
                <button 
                    class="mt-1 w-8 h-8 rounded-2xl border-2 flex items-center justify-center transition-all shrink-0"
                    :class="task.status === 'completed' ? 'bg-green-500 border-green-500 text-white shadow-lg shadow-green-500/20' : 'border-slate-200 dark:border-slate-700 hover:border-primary-500'"
                    @click="updateTaskStatus(task.id, task.status)"
                >
                    <CheckCircle2 v-if="task.status === 'completed'" class="w-5 h-5" />
                    <div v-else class="w-2 h-2 rounded-full bg-slate-200 dark:bg-slate-700 group-hover:bg-primary-500 transition-colors"></div>
                </button>
                <span :class="['px-3 py-1 rounded-full text-[9px] font-semibold text-xs tracking-wide', getPriorityLabel(task.priority).class]">
                    {{ getPriorityLabel(task.priority).label }}
                </span>
            </div>
            
            <div class="flex-1">
                <h4 class="font-extrabold text-slate-900 dark:text-white text-lg mb-2" :class="{'line-through text-slate-400 opacity-60': task.status === 'completed'}">{{ task.title }}</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed mb-4" :class="{'opacity-50': task.status === 'completed'}">{{ task.description }}</p>
                
                <div class="p-3 bg-slate-50 dark:bg-slate-900/50 rounded-xl mb-4 border border-slate-100 dark:border-slate-800">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Ditugaskan Kepada</p>
                    <p class="text-xs font-bold text-slate-900 dark:text-white">{{ task.application?.user?.name }}</p>
                    <p class="text-[10px] text-slate-500 mt-0.5">{{ task.application?.internship?.title }}</p>
                </div>
            </div>

            <div class="mt-auto pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2 text-[10px] font-semibold text-xs tracking-wide" :class="task.status === 'completed' ? 'text-green-500' : 'text-slate-400'">
                    <Clock class="w-3.5 h-3.5" />
                    <span>{{ task.status === 'completed' ? 'Selesai' : 'Batas: ' + formatDate(task.due_at || task.created_at, { day: 'numeric', month: 'short' }) }}</span>
                </div>
                <Link :href="'/mentor/mentees/' + task.application_id" class="text-primary-600 hover:text-primary-700 bg-primary-50 hover:bg-primary-100 dark:bg-primary-900/20 p-2 rounded-xl transition-colors">
                    <ChevronRight class="w-4 h-4" />
                </Link>
            </div>
          </Card>

          <div v-if="tasks.data.length === 0" class="col-span-full py-24 text-center bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm">
            <div class="w-20 h-20 bg-slate-50 dark:bg-slate-900/50 rounded-2xl shadow-inner flex items-center justify-center mx-auto mb-6">
                <Target class="w-10 h-10 text-slate-300" />
            </div>
            <h4 class="text-lg font-bold text-slate-900 dark:text-white">Belum Ada Tugas</h4>
            <p class="text-sm text-slate-500 max-w-sm mx-auto mt-2">Anda belum memberikan penugasan apapun kepada mentee. Berikan tugas melalui halaman detail mentee.</p>
          </div>
      </div>
    </div>
  </DashboardLayout>
</template>

