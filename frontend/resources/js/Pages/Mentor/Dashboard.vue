<script setup lang="ts">
import { Link, router as inertiaRouter } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useAuthStore } from '@/Stores/auth';
import { useLangStore } from '@/Stores/lang';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import { 
  Users, MessageSquare, ArrowRight,
  Sparkles, UserCheck, Clock, Briefcase,
  TrendingUp, Star, Award
} from 'lucide-vue-next';
import { formatDate } from '@/Lib/utils';
import type { Application } from '@/Types/application';
import type { Feedback } from '@/Types/feedback';
import echo from '@/echo';

interface MentorDashboardProps {
    stats?: {
        total_mentees?: number;
        pending_tasks?: number;
        completed_evaluations?: number;
    };
    activeMentees?: Application[];
    recentFeedbacks?: Feedback[];
}

const props = defineProps<MentorDashboardProps>();

const authStore = useAuthStore();
const langStore = useLangStore();
const __ = (key: string) => langStore.__(key);
const t = (key: string) => langStore.t(key);

const stats = computed(() => ({
    total_mentees: props.stats?.total_mentees ?? 0,
    pending_tasks: props.stats?.pending_tasks ?? 0,
    completed_evaluations: props.stats?.completed_evaluations ?? 0,
}));
const activeMentees = computed<Application[]>(() => props.activeMentees || []);
const recentFeedbacks = computed<Feedback[]>(() => props.recentFeedbacks || []);
const loading = ref(false);

const fetchData = () => {
    loading.value = true;
    inertiaRouter.reload({
        only: ['stats', 'activeMentees', 'recentFeedbacks'],
        onFinish: () => { loading.value = false; }
    });
};

onMounted(() => {
    if (echo && authStore.user?.id) {
        echo.private(`mentor.${authStore.user.id}`)
            .listen('DashboardUpdated', (e: any) => {
                if (e.reload) {
                    fetchData();
                }
            });
    }
});

onUnmounted(() => {
    if (echo && authStore.user?.id) {
        echo.leave(`mentor.${authStore.user.id}`);
    }
});
</script>

<template>
  <DashboardLayout>
    <div class="space-y-12 pb-20">
      <!-- Premium Header Section -->
      <header class="flex flex-col lg:flex-row lg:items-center justify-between gap-10 pb-10 border-b border-slate-100 dark:border-white/5">
        <div class="space-y-4 animate-slide-up" style="--delay: 0.1s">
          <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20 text-xs font-semibold tracking-[0.25em] uppercase">
              <Sparkles class="w-3.5 h-3.5" />
              {{ __('Mentor Navigation Center') }}
          </div>
          <h1 class="text-4xl md:text-5xl font-bold text-slate-900 dark:text-white leading-[1.1] tracking-tight">
            {{ t('Selamat Datang Kembali,') }} <span class="text-blue-600">{{ authStore.user?.name?.split(' ')[0] }}</span>
          </h1>
          <p class="text-xl text-slate-500 dark:text-slate-400 font-medium max-w-2xl leading-relaxed">
            {{ __('Pantau perkembangan mentee Anda dan berikan bimbingan terbaik untuk masa depan mereka hari ini.') }}
          </p>
        </div>
        
        <!-- Premium Stats Widgets -->
        <div class="flex flex-wrap items-center gap-6 animate-slide-up" style="--delay: 0.2s">
            <!-- Mentee Count Card -->
           <div class="bg-white dark:bg-slate-800 p-8 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm flex items-center gap-6 group hover:shadow-md transition-[box-shadow] duration-300">
              <div class="w-16 h-16 rounded-2xl bg-primary-500/10 flex items-center justify-center text-primary-600 transition-transform duration-500 group-hover:rotate-12 group-hover:scale-110">
                <Users class="w-8 h-8" />
              </div>
              <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-normal mb-1">{{ __('Total Mentee') }}</p>
                <p class="text-4xl font-bold text-slate-900 dark:text-white tracking-tighter">
                   <template v-if="loading">--</template>
                   <template v-else>{{ stats.total_mentees }}</template>
                </p>
              </div>
           </div>
           
           <!-- Pending Tasks Card -->
           <div class="bg-white dark:bg-slate-800 p-8 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm flex items-center gap-6 group hover:shadow-md transition-[box-shadow] duration-300">
              <div class="w-16 h-16 rounded-2xl bg-orange-500/10 flex items-center justify-center text-orange-600 transition-transform duration-500 group-hover:rotate-12 group-hover:scale-110">
                <Clock class="w-8 h-8" />
              </div>
              <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-normal mb-1">{{ __('Tugas Pending') }}</p>
                <p class="text-4xl font-bold text-slate-900 dark:text-white tracking-tighter">
                   <template v-if="loading">--</template>
                   <template v-else>{{ stats.pending_tasks }}</template>
                </p>
              </div>
           </div>
        </div>
      </header>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 items-start">
        <!-- Main Activity Column -->
        <div class="lg:col-span-2 space-y-12">
          
          <!-- Mentee Management Section -->
          <section class="animate-slide-up" style="--delay: 0.3s">
            <div class="flex items-center justify-between px-2 mb-8">
              <div class="space-y-1">
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">{{ __('Bimbingan Aktif') }}</h2>
                <p class="text-sm text-slate-500 font-medium">{{ __('Mentee yang sedang dalam masa magang di bawah arahan Anda.') }}</p>
              </div>
              <Link href="/mentor/mentees" class="flex items-center gap-2 text-sm font-bold text-primary-600 group">
                {{ __('Kelola Semua') }}
                <ArrowRight class="w-4 h-4 group-hover:translate-x-1 transition-transform" />
              </Link>
            </div>

            <!-- Skeleton Loading for Mentees -->
            <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-for="i in 2" :key="i" class="bg-slate-50 dark:bg-white/5 h-64 rounded-2xl "></div>
            </div>

            <div v-else-if="activeMentees.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <Card
v-for="(mentee, index) in activeMentees" :key="mentee.id" 
                    class="p-8 group relative overflow-hidden animate-slide-up" 
                    :style="`--delay: ${0.4 + (index * 0.1)}s`"
                    hoverable>
                <Link :href="'/mentor/mentees/' + mentee.id">
                  <div class="flex items-start justify-between mb-8">
                    <div class="w-16 h-16 bg-slate-50 dark:bg-slate-900 rounded-[1.25rem] flex items-center justify-center text-slate-400 group-hover:bg-primary-600 group-hover:text-white group-hover:rotate-6 transition-[background-color,color,transform] duration-500">
                      <Users class="w-8 h-8 stroke-[1.5]" />
                    </div>
                    <div class="px-4 py-1.5 rounded-full bg-primary-50 dark:bg-primary-500/10 text-xs font-semibold tracking-wide text-primary-600 dark:text-primary-400">
                      {{ mentee.status }}
                    </div>
                  </div>
                  
                  <div class="space-y-2">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white truncate group-hover:text-primary-600 transition-colors">{{ mentee.user.name }}</h3>
                    <p class="text-sm text-slate-500 font-medium flex items-center gap-2">
                      <Briefcase class="w-4 h-4 text-slate-400" />
                      {{ mentee.internship.title }}
                    </p>
                  </div>

                  <div class="mt-8 pt-8 border-t border-slate-100 dark:border-white/5 flex items-center justify-between">
                    <div class="flex items-center gap-2 text-xs font-medium text-slate-400">
                       <Clock class="w-3.5 h-3.5" />
                       {{ __('Bergabung') }} {{ formatDate(mentee.created_at, { month: 'short', year: 'numeric' }) }}
                    </div>
                    <div class="w-10 h-10 rounded-full bg-slate-50 dark:bg-slate-900 flex items-center justify-center group-hover:bg-primary-600 group-hover:text-white transition-colors duration-500">
                        <ArrowRight class="w-5 h-5" />
                    </div>
                  </div>
                </Link>
              </Card>
            </div>
            
            <div v-else class="bg-slate-50/50 dark:bg-white/5 border-2 border-dashed border-slate-200 dark:border-white/10 rounded-2xl p-16 text-center">
              <div class="w-20 h-20 bg-white dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm text-slate-300">
                <Users class="w-10 h-10" />
              </div>
              <p class="text-lg font-bold text-slate-900 dark:text-white mb-2">{{ __('Belum Ada Mentee Aktif') }}</p>
              <p class="text-slate-500 font-medium max-w-sm mx-auto">{{ __('Semua mentee yang ditugaskan kepada Anda akan muncul di sini untuk mulai dibimbing.') }}</p>
            </div>
          </section>

          <!-- Insight/Editorial Card -->
          <div class="bg-slate-900 rounded-2xl p-12 text-white relative overflow-hidden group animate-slide-up" style="--delay: 0.5s">
             
             <div class="absolute bottom-0 left-0 w-64 h-64 bg-blue-500/10 rounded-full blur-[80px] -ml-32 -mb-32"></div>
             
             <div class="relative z-10 grid lg:grid-cols-2 gap-10 items-center">
                <div class="space-y-6">
                    <div class="w-14 h-14 bg-primary-600 rounded-2xl flex items-center justify-center shadow-lg shadow-primary-600/20">
                      <Sparkles class="w-7 h-7 text-white" />
                    </div>
                    <div class="space-y-3">
                        <h3 class="text-3xl font-bold tracking-tight leading-tight">{{ __('Jadilah Mentor Inspiratif') }}</h3>
                        <p class="text-slate-400 font-medium leading-relaxed">
                          {{ __('Berikan feedback yang konstruktif dan bimbingan berkala untuk membantu mentee Anda mencapai potensi maksimal mereka di program magang ini.') }}
                        </p>
                    </div>
                    <a href="https://www.google.com/search?q=panduan+menjadi+mentor+magang+yang+baik" target="_blank" class="inline-block px-6 py-3 bg-white text-slate-900 rounded-xl font-bold text-sm active:scale-[0.98] transition-transform shadow-sm hover:bg-slate-50 cursor-pointer">
                        {{ __('Pelajari Teknik Coaching') }}
                    </a>
                </div>
                <div class="hidden lg:flex justify-end pr-8">
                    <div class="relative">
                        <div class="w-48 h-48 bg-white/5 rounded-2xl border border-white/10 flex items-center justify-center backdrop-blur-xl rotate-12">
                             <Award class="w-20 h-20 text-primary-500 opacity-50" />
                        </div>
                        <div class="absolute -top-4 -right-4 w-24 h-24 bg-primary-600 rounded-2xl flex items-center justify-center -rotate-12 shadow-2xl">
                             <Star class="w-10 h-10 text-white" />
                        </div>
                    </div>
                </div>
             </div>
          </div>
        </div>

        <!-- Right Sidebar (Feedback & Stats) -->
        <aside class="space-y-12 animate-slide-up" style="--delay: 0.6s">
          <!-- Recent Feedback Section -->
          <section>
            <div class="flex items-center justify-between px-2 mb-8">
              <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">{{ __('Feedback Terbaru') }}</h2>
              <MessageSquare class="w-5 h-5 text-slate-300" />
            </div>
            
            <div v-if="loading" class="space-y-4">
                <div v-for="i in 3" :key="i" class="bg-white dark:bg-white/5 h-32 rounded-[2rem] "></div>
            </div>

            <div v-else class="space-y-4">
               <div
v-for="(fb, index) in recentFeedbacks" :key="fb.id" 
                    class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-[box-shadow] duration-300 animate-slide-up"
                    :style="`--delay: ${0.7 + (index * 0.1)}s`">
                  <div class="flex items-center gap-4 mb-4">
                     <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-900 flex items-center justify-center text-sm font-bold text-slate-500 border border-slate-100 dark:border-white/5">
                        {{ fb.application.user.name.charAt(0) }}
                     </div>
                     <div>
                        <p class="text-sm font-bold text-slate-900 dark:text-white line-clamp-1">{{ fb.application.user.name }}</p>
                        <p class="text-xs text-slate-400 font-semibold tracking-[0.15em]">{{ formatDate(fb.created_at) }}</p>
                     </div>
                  </div>
                  <p class="text-sm text-slate-600 dark:text-slate-400 italic font-medium leading-relaxed line-clamp-3 bg-slate-50 dark:bg-slate-900/50 p-4 rounded-2xl">
                      "{{ fb.content }}"
                  </p>
               </div>
               
               <div v-if="recentFeedbacks.length === 0" class="text-center py-16 bg-slate-50/50 dark:bg-white/5 rounded-[2rem] border border-dashed border-slate-200 dark:border-white/10">
                  <p class="text-sm text-slate-400 font-bold italic">{{ __('Belum ada feedback yang diberikan.') }}</p>
               </div>
            </div>
          </section>

          <!-- System Overview Widget -->
          <div class="bg-white dark:bg-slate-800 p-8 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm space-y-8 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-5">
                <TrendingUp class="w-20 h-20" />
            </div>

            <div class="space-y-2 relative z-10">
                <h3 class="text-xs font-bold text-primary-600 uppercase tracking-[0.25em]">{{ __('Ringkasan Sistem') }}</h3>
                <h4 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Status Aktivitas') }}</h4>
            </div>

            <div class="space-y-5 relative z-10">
               <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-white/5">
                  <span class="text-xs text-slate-500 font-bold">{{ __('Evaluasi Selesai') }}</span>
                  <span class="text-xl font-bold text-slate-900 dark:text-white">{{ stats.completed_evaluations }}</span>
               </div>
               <div class="flex items-center justify-between p-4 rounded-2xl bg-green-50 dark:bg-green-500/10 border border-green-100 dark:border-green-500/20">
                  <span class="text-xs text-green-700 dark:text-green-400 font-bold">{{ __('Status Akun') }}</span>
                  <span class="text-xs font-bold text-green-700 dark:text-green-400 flex items-center gap-1.5 bg-white dark:bg-green-900/30 px-3 py-1 rounded-full shadow-sm">
                      <UserCheck class="w-3.5 h-3.5" />
                      {{ __('AKTIF') }}
                  </span>
               </div>
            </div>

            <Link href="/mentor/mentees" class="flex items-center justify-center gap-2 w-full bg-slate-900 dark:bg-primary-600 text-white py-3 rounded-xl font-bold text-sm active:scale-[0.98] transition-colors hover:bg-slate-800 dark:hover:bg-primary-700 shadow-sm">
                {{ __('Mulai Review Mentee') }}
                <ArrowRight class="w-4 h-4" />
            </Link>
          </div>
        </aside>
      </div>
    </div>
  </DashboardLayout>
</template>

<style scoped>

@keyframes slide-up {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-slide-up {
  opacity: 0;
  animation: slide-up 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  animation-delay: var(--delay, 0s);
}
</style>

