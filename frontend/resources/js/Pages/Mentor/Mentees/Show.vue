<script setup lang="ts">
import logger from '@/Lib/logger';
import { Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { 
  User, Mail, Phone, MapPin, Calendar, 
  MessageSquare, CheckSquare, BarChart3,
  ChevronLeft, Plus, Trash2, CheckCircle2,
  AlertCircle, Star, Send, Briefcase, FileText, Monitor,
  Clock, Sparkles, Target, GraduationCap
} from 'lucide-vue-next';
import { formatDate } from '@/Lib/utils';

const props = defineProps<{
    application: any;
    feedbacks: any[];
    tasks: any[];
    evaluations: any[];
    sessions: any[];
}>();

const activeTab = ref('overview');

const feedbackForm = useForm({
    content: '',
    assessment: {
        technical: 3,
        soft_skills: 3,
        attitude: 3
    } as any,
});

const taskForm = useForm({
    title: '',
    description: '',
    due_date: '',
    priority: 2,
});

const sessionForm = useForm({
    title: '',
    description: '',
    scheduled_at: '',
    duration_minutes: 60,
    meeting_link: '',
});

const evaluationForm = useForm({
    title: 'Evaluasi Akhir Periode Magang',
    summary: '',
    metrics: {
        technical_skill: 3,
        communication: 3,
        attitude: 3,
        reliability: 3
    } as any,
    recommendation: '',
    final_status: 'recommend',
});

const getMetricDisplay = (val: number) => {
    switch (Number(val)) {
        case 1:
            return {
                emoji: '😞',
                label: 'Sangat Kurang',
                colorClass: 'text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/20 border-rose-100 dark:border-rose-900/30',
                bgClass: 'bg-rose-500/10 border-rose-500/20',
                gradientClass: 'bg-gradient-to-r from-rose-400 to-rose-600 shadow-rose-500/30'
            };
        case 2:
            return {
                emoji: '😐',
                label: 'Kurang',
                colorClass: 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/20 border-amber-100 dark:border-amber-900/30',
                bgClass: 'bg-amber-500/10 border-amber-500/20',
                gradientClass: 'bg-gradient-to-r from-amber-400 to-amber-600 shadow-amber-500/30'
            };
        case 3:
            return {
                emoji: '🙂',
                label: 'Cukup',
                colorClass: 'text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/20 border-sky-100 dark:border-sky-900/30',
                bgClass: 'bg-sky-500/10 border-sky-500/20',
                gradientClass: 'bg-gradient-to-r from-sky-400 to-sky-600 shadow-sky-500/30'
            };
        case 4:
            return {
                emoji: '😀',
                label: 'Baik',
                colorClass: 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/20 border-emerald-100 dark:border-emerald-900/30',
                bgClass: 'bg-emerald-500/10 border-emerald-500/20',
                gradientClass: 'bg-gradient-to-r from-emerald-400 to-emerald-600 shadow-emerald-500/30'
            };
        case 5:
        default:
            return {
                emoji: '😎',
                label: 'Sangat Baik!',
                colorClass: 'text-teal-600 dark:text-teal-400 bg-teal-50 dark:bg-teal-950/20 border-teal-100 dark:border-teal-900/30',
                bgClass: 'bg-teal-500/10 border-teal-500/20',
                gradientClass: 'bg-gradient-to-r from-teal-400 to-teal-600 shadow-teal-500/30'
            };
    }
};

const submitFeedback = () => {
    if (!feedbackForm.content.trim()) return;
    feedbackForm.post(`/mentor/mentees/${props.application.id}/feedback`, {
        preserveScroll: true,
        onSuccess: () => {
            feedbackForm.reset();
        }
    });
};

const submitTask = () => {
    taskForm.post(`/mentor/mentees/${props.application.id}/tasks`, {
        preserveScroll: true,
        onSuccess: () => {
            taskForm.reset();
        }
    });
};

const submitSession = () => {
    sessionForm.post(`/mentor/mentees/${props.application.id}/sessions`, {
        preserveScroll: true,
        onSuccess: () => {
            sessionForm.reset();
        }
    });
};

const updateSessionStatus = (sessionId: number, status: string) => {
    router.patch(`/mentor/sessions/${sessionId}/status`, { status }, { preserveScroll: true });
};

const updateTaskStatus = (taskId: number, status: string) => {
    router.patch(`/mentor/tasks/${taskId}/status`, { status }, { preserveScroll: true });
};

const deleteTask = (taskId: number) => {
    if (confirm('Apakah Anda yakin ingin menghapus penugasan ini?')) {
        router.delete(`/mentor/tasks/${taskId}`, { preserveScroll: true });
    }
};

const submitEvaluation = () => {
    evaluationForm.transform((data) => ({
        ...data,
        application_id: props.application.id,
        final_status: data.final_status === 'recommend' ? 'completed' : 'failed'
    })).post('/mentor/evaluations', {
        preserveScroll: true,
        onSuccess: () => {
            activeTab.value = 'evaluations';
        }
    });
};

const timelineEvents = computed(() => {
    if (!props.application) return [];
    const events: any[] = [];
    
    events.push({
        date: props.application.created_at,
        title: 'Langkah Awal Dimulai',
        description: 'Kandidat resmi bergabung untuk posisi ' + props.application.internship.title,
        icon: Briefcase,
        color: 'bg-blue-500'
    });

    props.tasks?.forEach((task: any) => {
        events.push({
            date: task.created_at,
            title: 'Tantangan Baru: ' + task.title,
            status: task.status,
            icon: CheckSquare
        });

        if (task.status === 'completed' && task.updated_at) {
            events.push({
                id: `task_completed_${task.id}`,
                type: 'task_completed',
                title: `Tugas Selesai: ${task.title}`,
                date: task.updated_at,
                icon: CheckCircle2
            });
        }
    });

    props.sessions?.forEach((session: any) => {
        events.push({
            id: `session_${session.id}`,
            type: 'session',
            title: `Sesi: ${session.title}`,
            date: session.scheduled_at,
            status: session.status,
            icon: Calendar
        });
    });

    props.feedbacks?.forEach((fb: any) => {
        events.push({
            id: `feedback_${fb.id}`,
            type: 'feedback',
            title: 'Catatan Mentor',
            date: fb.created_at,
            icon: MessageSquare
        });
    });

    return events.sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime());
});

const getPriorityLabel = (priority: number) => {
    switch(priority) {
        case 3: return { label: 'Tinggi', class: 'bg-red-50 text-red-600 border border-red-100' };
        case 2: return { label: 'Sedang', class: 'bg-orange-50 text-orange-600 border border-orange-100' };
        default: return { label: 'Rendah', class: 'bg-blue-50 text-blue-600 border border-blue-100' };
    }
};

const tabs = [
    { id: 'overview', name: 'Ringkasan', icon: User },
    { id: 'tasks', name: 'Penugasan', icon: CheckSquare },
    { id: 'sessions', name: 'Bimbingan', icon: Calendar },
    { id: 'feedbacks', name: 'Catatan', icon: MessageSquare },
    { id: 'evaluations', name: 'Evaluasi Akhir', icon: GraduationCap },
];
</script>

<template>
  <DashboardLayout>
    <div class="max-w-7xl mx-auto space-y-8 pb-20 animate-in fade-in duration-700">
      <!-- Breadcrumbs & Status -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <Link href="/mentor/mentees" class="group inline-flex items-center gap-2 text-sm font-bold text-slate-500 hover:text-primary-600 transition-colors">
          <div class="w-8 h-8 rounded-full bg-white dark:bg-slate-800 shadow-sm flex items-center justify-center group-hover:bg-primary-50 transition-colors">
            <ChevronLeft class="w-4 h-4" />
          </div>
          Kembali ke Daftar Mentee
        </Link>
        <div class="flex items-center gap-3 bg-white dark:bg-slate-800 px-4 py-2 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800">
           <span class="text-xs font-bold text-slate-400 font-medium">Status Magang</span>
           <StatusBadge :status="application.status" />
        </div>
      </div>

      <!-- Main Content Layout -->
      <div class="flex flex-col lg:flex-row gap-8 items-start">
         <!-- Left Column: Profile Card -->
         <div class="w-full lg:w-80 space-y-6 shrink-0">
            <Card class="p-8 text-center border-none shadow-premium bg-white dark:bg-slate-800 overflow-hidden relative group">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-primary-500 to-secondary-500"></div>
                
                <div class="relative w-28 h-28 mx-auto mb-6">
                    <div class="absolute inset-0 bg-primary-500/10 rounded-full  scale-110"></div>
                    <div class="relative w-full h-full bg-slate-100 dark:bg-slate-900 rounded-full flex items-center justify-center text-3xl font-bold text-slate-400 border-4 border-white dark:border-slate-800 shadow-card overflow-hidden transition-transform group-hover:scale-105 duration-500">
                       <img v-if="application.user.avatar_url" loading="lazy" decoding="async" :src="application.user.avatar_url" class="w-full h-full object-cover" />
                       <span v-else>{{ application.user.name.charAt(0) }}</span>
                    </div>
                </div>

                <h2 class="text-xl font-extrabold text-slate-900 dark:text-white mb-1 leading-tight">{{ application.user.name }}</h2>
                <p class="text-xs font-bold text-primary-600 font-medium mb-6">{{ application.internship.title }}</p>
                
                <div class="space-y-4 pt-6 border-t border-slate-100 dark:border-slate-700/50 text-left">
                   <div class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                      <div class="w-8 h-8 rounded-lg bg-slate-50 dark:bg-slate-900 flex items-center justify-center">
                        <Mail class="w-4 h-4 text-slate-400" />
                      </div>
                      <span class="truncate">{{ application.user.email }}</span>
                   </div>
                   <div v-if="application.user.detail?.phone_number" class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                      <div class="w-8 h-8 rounded-lg bg-slate-50 dark:bg-slate-900 flex items-center justify-center">
                        <Phone class="w-4 h-4 text-slate-400" />
                      </div>
                      <span>{{ application.user.detail.phone_number }}</span>
                   </div>
                   <div v-if="application.user.detail?.university" class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                      <div class="w-8 h-8 rounded-lg bg-slate-50 dark:bg-slate-900 flex items-center justify-center">
                        <MapPin class="w-4 h-4 text-slate-400" />
                      </div>
                      <span>{{ application.user.detail.university }}</span>
                   </div>
                </div>
            </Card>

            <!-- Stats Grid for Left Column -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800">
                    <p class="text-xs font-bold text-slate-400 font-medium mb-1">Tugas Selesai</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ tasks.filter(t => t.status === 'completed').length }}<span class="text-xs text-slate-400 font-bold ml-1">/ {{ tasks.length }}</span></p>
                </div>
                <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800">
                    <p class="text-xs font-bold text-slate-400 font-medium mb-1">Sesi Bimbingan</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ sessions.length }}</p>
                </div>
            </div>
         </div>

         <!-- Right Column: Tabs & Content -->
         <div class="flex-1 w-full space-y-6">
            <!-- Navigation Tabs - Glassmorphism touch -->
            <div class="sticky top-4 z-20 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl p-2 rounded-[2rem] shadow-card border border-white dark:border-slate-800 flex items-center gap-1 overflow-x-auto no-scrollbar">
               <button 
                  v-for="tab in tabs" 
                  :key="tab.id"
                  class="flex items-center gap-2 px-6 py-3 rounded-2xl text-sm font-bold transition-colors whitespace-nowrap active-press"
                  :class="activeTab === tab.id ? 'bg-primary-600 text-white shadow-lg shadow-primary-500/20' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800'"
                  @click="activeTab = tab.id"
               >
                  <component :is="tab.icon" class="w-4 h-4" />
                  {{ tab.name }}
               </button>
            </div>

            <!-- Tab Contents -->
            <div class="min-h-[500px]">
                <!-- Tab: Overview -->
                <div v-if="activeTab === 'overview'" class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                   <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                      <div class="space-y-8">
                        <Card class="p-8 border-none shadow-sm hover-lift">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-10 h-10 rounded-xl bg-primary-50 dark:bg-primary-900/20 flex items-center justify-center text-primary-600">
                                    <Sparkles class="w-5 h-5" />
                                </div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white font-medium">Profil & Aspirasi</h3>
                            </div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed italic">
                                {{ application.user.detail?.bio || 'Mentee belum menuliskan profil singkatnya.' }}
                            </p>
                        </Card>
                        
                        <Card class="p-8 border-none shadow-sm hover-lift">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-10 h-10 rounded-xl bg-secondary-50 dark:bg-secondary-900/20 flex items-center justify-center text-secondary-600">
                                    <Briefcase class="w-5 h-5" />
                                </div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white font-medium">Detail Penempatan</h3>
                            </div>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-900/50 rounded-xl">
                                    <span class="text-xs text-slate-500 font-bold">POSISI</span>
                                    <span class="text-xs font-bold text-slate-900 dark:text-white">{{ application.internship.title }}</span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-900/50 rounded-xl">
                                    <span class="text-xs text-slate-500 font-bold">TIPE KERJA</span>
                                    <span class="text-xs font-bold text-slate-900 dark:text-white">{{ application.internship.type }}</span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-900/50 rounded-xl">
                                    <span class="text-xs text-slate-500 font-bold">GABUNG SEJAK</span>
                                    <span class="text-xs font-bold text-slate-900 dark:text-white">{{ formatDate(application.created_at) }}</span>
                                </div>
                            </div>
                        </Card>
                      </div>
    
                      <!-- Timeline Activity -->
                      <Card class="p-8 border-none shadow-sm h-full">
                        <div class="flex items-center gap-3 mb-8">
                            <div class="w-10 h-10 rounded-xl bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center text-orange-600">
                                <Clock class="w-5 h-5" />
                            </div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white font-medium">Jejak Aktivitas</h3>
                        </div>
                        
                        <div class="relative space-y-10 before:absolute before:inset-0 before:ml-5 before:-translate-x-px before:h-full before:w-0.5 before:bg-slate-100 dark:before:bg-slate-800">
                            <div v-for="(event, index) in timelineEvents" :key="index" class="relative flex items-start gap-6 group animate-in fade-in slide-in-from-left-4 duration-500" :style="{ 'animation-delay': (index * 100) + 'ms' }">
                                <div class="absolute left-0 w-10 h-10 rounded-2xl flex items-center justify-center shadow-lg transition-colors group-hover:scale-110 group-hover:rotate-6 z-10" :class="[event.color]">
                                    <component :is="event.icon" class="w-5 h-5 text-white" />
                                </div>
                                <div class="ml-14 pt-0.5">
                                    <time class="text-xs font-bold text-primary-500 font-medium">{{ formatDate(event.date, { day: 'numeric', month: 'short' }) }}</time>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white mt-0.5">{{ event.title }}</h4>
                                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ event.description }}</p>
                                </div>
                            </div>
                        </div>
                      </Card>
                   </div>
    
                   <Card class="p-8 border-none shadow-sm hover-lift overflow-hidden relative">
                      <div class="absolute top-0 right-0 w-32 h-32 bg-primary-500/5 rounded-full -mr-16 -mt-16"></div>
                      <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600">
                            <FileText class="w-5 h-5" />
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white font-medium">Surat Lamaran / Cover Letter</h3>
                      </div>
                      <div class="bg-slate-50 dark:bg-slate-900/50 p-8 rounded-2xl text-sm text-slate-600 dark:text-slate-400 leading-relaxed whitespace-pre-line border border-slate-100 dark:border-slate-800 shadow-inner">
                         {{ application.cover_letter || 'Kandidat tidak menyertakan surat lamaran khusus.' }}
                      </div>
                   </Card>
                </div>
 
                <!-- Tab: Tasks -->
                <div v-if="activeTab === 'tasks'" class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                   <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                      <!-- Task List -->
                      <div class="lg:col-span-2 space-y-4">
                         <div v-for="(task, index) in tasks" :key="task.id" class="bg-white dark:bg-slate-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-800 shadow-sm flex items-start gap-5 group hover-lift animate-in fade-in slide-in-from-bottom-2" :style="{ 'animation-delay': (index * 100) + 'ms' }">
                            <button 
                               class="mt-1 w-8 h-8 rounded-2xl border-2 flex items-center justify-center transition-colors shrink-0 active-press"
                               :class="task.status === 'completed' ? 'bg-green-500 border-green-500 text-white shadow-lg shadow-green-500/20' : 'border-slate-200 dark:border-slate-700 hover:border-primary-500'"
                               @click="updateTaskStatus(task.id, task.status === 'completed' ? 'todo' : 'completed')"
                            >
                               <CheckCircle2 v-if="task.status === 'completed'" class="w-5 h-5" />
                               <div v-else class="w-2 h-2 rounded-full bg-slate-200 dark:bg-slate-700 group-hover:bg-primary-500 transition-colors"></div>
                            </button>
                            
                            <div class="flex-1 min-w-0">
                               <div class="flex flex-wrap items-center gap-2 mb-2">
                                  <h4 class="font-extrabold text-slate-900 dark:text-white truncate text-lg" :class="{'line-through text-slate-400 opacity-60': task.status === 'completed'}">{{ task.title }}</h4>
                                  <span :class="['px-3 py-1 rounded-full text-xs font-semibold text-xs tracking-wide', getPriorityLabel(task.priority).class]">
                                     {{ getPriorityLabel(task.priority).label }}
                                  </span>
                               </div>
                               <p class="text-sm text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed" :class="{'opacity-50': task.status === 'completed'}">{{ task.description }}</p>
                               <div class="mt-6 flex flex-wrap items-center gap-6">
                                  <div class="flex items-center gap-2 text-xs font-semibold text-xs tracking-wide" :class="task.status === 'completed' ? 'text-green-500' : 'text-slate-400'">
                                     <Clock class="w-3.5 h-3.5" />
                                     <span>{{ task.status === 'completed' ? 'Selesai' : 'Batas: ' + formatDate(task.due_date, { day: 'numeric', month: 'short' }) }}</span>
                                  </div>
                               </div>
                            </div>
    
                            <button class="opacity-0 group-hover:opacity-100 p-2.5 text-slate-300 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-colors active-press" @click="deleteTask(task.id)">
                               <Trash2 class="w-5 h-5" />
                            </button>
                         </div>
    
                         <div v-if="tasks.length === 0" class="py-24 text-center bg-slate-50/30 dark:bg-slate-900/20 rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-800">
                            <div class="w-20 h-20 bg-white dark:bg-slate-800 rounded-2xl shadow-sm flex items-center justify-center mx-auto mb-6">
                                <CheckSquare class="w-10 h-10 text-slate-300" />
                            </div>
                            <h4 class="text-lg font-bold text-slate-900 dark:text-white">Siap memberikan tugas?</h4>
                            <p class="text-sm text-slate-500 max-w-xs mx-auto mt-2">Belum ada penugasan untuk mentee ini. Mulai dengan membuat tugas pertama Anda di samping.</p>
                         </div>
                      </div>
    
                      <!-- Create Task Form -->
                      <div class="space-y-6">
                        <Card class="p-8 border-none shadow-premium h-fit sticky top-32 bg-gradient-to-br from-white to-slate-50 dark:from-slate-800 dark:to-slate-800/50">
                           <div class="flex items-center gap-3 mb-8">
                                <div class="w-10 h-10 rounded-xl bg-primary-600 text-white flex items-center justify-center shadow-lg shadow-primary-500/20">
                                    <Plus class="w-5 h-5" />
                                </div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white font-medium">Tugas Baru</h3>
                           </div>
                           
                           <form class="space-y-5" @submit.prevent="submitTask">
                              <div class="space-y-2">
                                 <label class="text-xs font-bold text-slate-400 font-medium ml-1">Judul Penugasan</label>
                                 <input v-model="taskForm.title" type="text" placeholder="Misal: Riset Kompetitor" class="w-full bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl text-sm p-4 focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 transition-colors outline-none" required />
                              </div>
                              <div class="space-y-2">
                                 <label class="text-xs font-bold text-slate-400 font-medium ml-1">Instruksi Singkat</label>
                                 <textarea v-model="taskForm.description" rows="4" placeholder="Apa yang harus dikerjakan mentee?" class="w-full bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl text-sm p-4 focus:ring-4 focus:ring-primary-500/10 focus:border-primary-500 transition-colors outline-none"></textarea>
                              </div>
                              <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                   <label class="text-xs font-bold text-slate-400 font-medium ml-1">Deadline</label>
                                   <input v-model="taskForm.due_date" type="date" class="w-full bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl text-xs p-4 focus:ring-4 focus:ring-primary-500/10 outline-none" />
                                </div>
                                <div class="space-y-2">
                                   <label class="text-xs font-bold text-slate-400 font-medium ml-1">Prioritas</label>
                                   <select v-model="taskForm.priority" class="w-full bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl text-xs p-4 focus:ring-4 focus:ring-primary-500/10 outline-none">
                                      <option :value="1">Rendah</option>
                                      <option :value="2">Sedang</option>
                                      <option :value="3">Tinggi</option>
                                   </select>
                                </div>
                              </div>
                              <button type="submit" :disabled="taskForm.processing" class="w-full py-4 bg-primary-600 text-white rounded-2xl text-sm font-bold hover:bg-primary-700 shadow-lg shadow-primary-500/20 transition-colors active-press mt-4 flex items-center justify-center gap-2">
                                 <span v-if="taskForm.processing" class="w-4 h-4 border-2 border-white/30 border-t-white animate-spin rounded-full"></span>
                                 {{ taskForm.processing ? 'Menyimpan...' : 'Berikan Tugas' }}
                              </button>
                           </form>
                        </Card>
                      </div>
                   </div>
                </div>
 
                <!-- Tab: Mentoring Sessions -->
                <div v-if="activeTab === 'sessions'" class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                   <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                      <!-- Sessions List -->
                      <div class="lg:col-span-2 space-y-6">
                         <Card v-for="(session, index) in sessions" :key="session.id" class="p-8 border-none shadow-sm relative overflow-hidden group hover-lift animate-in fade-in slide-in-from-bottom-2" :style="{ 'animation-delay': (index * 100) + 'ms' }">
                            <div class="absolute top-0 right-0 w-24 h-24 bg-purple-500/5 rounded-full -mr-12 -mt-12 group-hover:scale-125 transition-transform duration-700"></div>
                            
                            <div class="flex items-start justify-between mb-8">
                                <div class="flex items-center gap-5">
                                    <div class="w-14 h-14 rounded-2xl bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center text-purple-600 shadow-inner">
                                        <Calendar class="w-7 h-7" />
                                    </div>
                                    <div>
                                        <h4 class="text-xl font-bold text-slate-900 dark:text-white leading-tight">{{ session.title }}</h4>
                                        <div class="flex items-center gap-3 mt-1.5 text-xs font-bold text-slate-500">
                                            <span class="flex items-center gap-1"><Clock class="w-3.5 h-3.5 text-purple-500" /> {{ formatDate(session.scheduled_at, { hour: '2-digit', minute: '2-digit' }) }}</span>
                                            <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                            <span>{{ formatDate(session.scheduled_at, { day: 'numeric', month: 'long' }) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <span
class="px-4 py-1.5 rounded-full text-xs font-semibold text-xs tracking-wide border" :class="{
                                    'bg-blue-50 text-blue-600 border-blue-100': session.status === 'planned',
                                    'bg-green-50 text-green-600 border-green-100': session.status === 'completed',
                                    'bg-red-50 text-red-600 border-red-100': session.status === 'cancelled',
                                }">
                                    {{ session.status === 'planned' ? 'Terjadwal' : session.status === 'completed' ? 'Selesai' : 'Dibatalkan' }}
                                </span>
                            </div>
    
                            <p class="text-sm text-slate-600 dark:text-slate-400 mb-8 leading-relaxed">{{ session.description || 'Tidak ada deskripsi tambahan untuk sesi ini.' }}</p>
    
                            <div class="flex items-center justify-between pt-8 border-t border-slate-50 dark:border-slate-700/50">
                                <a v-if="session.meeting_link" :href="session.meeting_link" target="_blank" class="px-5 py-2.5 bg-slate-900 dark:bg-white dark:text-slate-900 text-white rounded-xl text-xs font-bold hover:scale-105 transition-colors flex items-center gap-2 active-press">
                                    <Monitor class="w-4 h-4" />
                                    Gabung Meeting
                                </a>
                                <div v-else class="text-xs font-bold text-slate-400 italic">Link belum tersedia</div>
    
                                <div v-if="session.status === 'planned'" class="flex items-center gap-2">
                                    <button class="px-5 py-2.5 bg-green-500 text-white rounded-xl text-xs font-bold hover:bg-green-600 transition-colors shadow-lg shadow-green-500/10 active-press" @click="updateSessionStatus(session.id, 'completed')">Selesai</button>
                                    <button class="px-5 py-2.5 bg-slate-100 dark:bg-slate-700 text-slate-500 rounded-xl text-xs font-bold hover:bg-red-50 hover:text-red-500 transition-colors active-press" @click="updateSessionStatus(session.id, 'cancelled')">Batal</button>
                                </div>
                            </div>
                         </Card>
    
                         <div v-if="sessions.length === 0" class="py-24 text-center bg-slate-50/30 dark:bg-slate-900/20 rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-800">
                            <div class="w-20 h-20 bg-white dark:bg-slate-800 rounded-2xl shadow-sm flex items-center justify-center mx-auto mb-6">
                                <Calendar class="w-10 h-10 text-slate-300" />
                            </div>
                            <h4 class="text-lg font-bold text-slate-900 dark:text-white">Jadwalkan Tatap Muka?</h4>
                            <p class="text-sm text-slate-500 max-w-xs mx-auto mt-2">Buat janji temu bimbingan rutin agar progres mentee tetap terpantau dengan baik.</p>
                         </div>
                      </div>
    
                      <!-- Schedule Session Form -->
                      <Card class="p-8 border-none shadow-premium h-fit sticky top-32 bg-white dark:bg-slate-800">
                         <div class="flex items-center gap-3 mb-8">
                            <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center shadow-lg shadow-purple-500/20">
                                <Plus class="w-5 h-5" />
                            </div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white font-medium">Sesi Baru</h3>
                         </div>
                         
                         <form class="space-y-5" @submit.prevent="submitSession">
                            <div class="space-y-2">
                               <label class="text-xs font-bold text-slate-400 font-medium ml-1">Topik Bimbingan</label>
                               <input v-model="sessionForm.title" type="text" class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-sm p-4 focus:ring-4 focus:ring-purple-500/10 transition-colors outline-none" placeholder="Misal: Review Sprint 1" required />
                            </div>
                            <div class="space-y-2">
                               <label class="text-xs font-bold text-slate-400 font-medium ml-1">Waktu Sesi</label>
                               <input v-model="sessionForm.scheduled_at" type="datetime-local" class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-sm p-4 focus:ring-4 focus:ring-purple-500/10 outline-none" required />
                            </div>
                            <div class="space-y-2">
                               <label class="text-xs font-bold text-slate-400 font-medium ml-1">Durasi Estimasi (Menit)</label>
                               <input v-model="sessionForm.duration_minutes" type="number" class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-sm p-4 focus:ring-4 focus:ring-purple-500/10 outline-none" required />
                            </div>
                            <div class="space-y-2">
                               <label class="text-xs font-bold text-slate-400 font-medium ml-1">Link Meeting</label>
                               <input v-model="sessionForm.meeting_link" type="url" class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-sm p-4 focus:ring-4 focus:ring-purple-500/10 outline-none" placeholder="Google Meet / Zoom" />
                            </div>
                            <button type="submit" :disabled="sessionForm.processing" class="w-full py-4 bg-purple-600 text-white rounded-2xl text-sm font-bold hover:bg-purple-700 shadow-lg shadow-purple-500/20 transition-colors active-press mt-4">
                               {{ sessionForm.processing ? 'Menyimpan...' : 'Jadwalkan Sesi' }}
                            </button>
                         </form>
                      </Card>
                   </div>
                </div>
 
                <!-- Tab: Feedbacks -->
                <div v-if="activeTab === 'feedbacks'" class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                   <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                      <div class="lg:col-span-2 space-y-6">
                         <Card v-for="(fb, index) in feedbacks" :key="fb.id" class="p-8 border-none shadow-sm relative overflow-hidden group hover-lift animate-in fade-in slide-in-from-bottom-2" :style="{ 'animation-delay': (index * 100) + 'ms' }">
                            <div class="flex items-center gap-4 mb-8">
                               <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-lg font-bold text-white shadow-lg shadow-primary-500/20">
                                  {{ fb.mentor.name.charAt(0) }}
                               </div>
                               <div>
                                  <p class="text-base font-extrabold text-slate-900 dark:text-white leading-tight">{{ fb.mentor.name }}</p>
                                  <p class="text-xs text-primary-500 font-semibold text-xs tracking-wide mt-0.5">{{ formatDate(fb.created_at) }}</p>
                               </div>
                            </div>
                            <div class="bg-slate-50 dark:bg-slate-900/50 p-6 rounded-2xl mb-8 border border-slate-100 dark:border-slate-800 shadow-inner">
                                <p class="text-sm text-slate-600 dark:text-slate-400 italic leading-relaxed">"{{ fb.content }}"</p>
                            </div>
                            
                            <div v-if="fb.assessment" class="flex flex-wrap gap-4">
                               <div v-for="(val, key) in fb.assessment" :key="key" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-800 rounded-2xl shadow-sm flex items-center gap-3">
                                  <span class="text-xs font-bold text-slate-400 font-medium">{{ String(key).replace('_', ' ') }}</span>
                                  <div class="flex gap-0.5">
                                     <Star v-for="i in 5" :key="i" class="w-3 h-3" :class="i <= val ? 'fill-yellow-400 text-yellow-400' : 'text-slate-200 dark:text-slate-700'" />
                                  </div>
                               </div>
                            </div>
                         </Card>
    
                         <div v-if="feedbacks.length === 0" class="py-24 text-center bg-slate-50/30 dark:bg-slate-900/20 rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-800">
                            <div class="w-20 h-20 bg-white dark:bg-slate-800 rounded-2xl shadow-sm flex items-center justify-center mx-auto mb-6">
                                <MessageSquare class="w-10 h-10 text-slate-300" />
                            </div>
                            <h4 class="text-lg font-bold text-slate-900 dark:text-white">Apresiasi atau Koreksi?</h4>
                            <p class="text-sm text-slate-500 max-w-xs mx-auto mt-2">Jangan ragu memberikan feedback untuk membantu perkembangan mentee Anda.</p>
                         </div>
                      </div>
    
                      <div class="space-y-6">
                        <Card class="p-8 border-none shadow-premium h-fit sticky top-32 bg-white dark:bg-slate-800">
                           <div class="flex items-center gap-3 mb-8">
                                <div class="w-10 h-10 rounded-xl bg-pink-600 text-white flex items-center justify-center shadow-lg shadow-pink-500/20">
                                    <Send class="w-5 h-5" />
                                </div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white font-medium">Beri Catatan</h3>
                           </div>
                           <form class="space-y-8" @submit.prevent="submitFeedback">
                              <div class="space-y-2">
                                 <label class="text-xs font-bold text-slate-400 font-medium ml-1">Pesan Feedback</label>
                                 <textarea v-model="feedbackForm.content" rows="6" class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-sm p-5 focus:ring-4 focus:ring-pink-500/10 transition-colors outline-none" placeholder="Tuliskan apresiasi atau poin pengembangan..." required></textarea>
                              </div>
                              
                              <div class="space-y-5">
                                 <p class="text-xs font-bold text-slate-400 font-medium ml-1">Penilaian Objektif</p>
                                 <div v-for="(val, key) in feedbackForm.assessment" :key="key" class="flex items-center justify-between bg-slate-50 dark:bg-slate-900 p-3 rounded-2xl">
                                    <span class="text-xs font-bold text-slate-600 dark:text-slate-400 capitalize ml-1">{{ String(key).replace('_', ' ') }}</span>
                                    <div class="flex gap-1.5">
                                       <button v-for="i in 5" :key="i" type="button" class="transition-colors hover:scale-125 active-press" @click="feedbackForm.assessment[key] = i">
                                          <Star class="w-5 h-5" :class="i <= val ? 'fill-yellow-400 text-yellow-400' : 'text-slate-200 dark:text-slate-800'" />
                                       </button>
                                    </div>
                                 </div>
                              </div>
    
                              <button type="submit" :disabled="feedbackForm.processing || !feedbackForm.content.trim()" class="w-full py-4 bg-primary-600 text-white rounded-2xl text-sm font-bold hover:bg-primary-700 shadow-lg shadow-primary-500/20 transition-colors active-press">
                                 {{ feedbackForm.processing ? 'Mengirim...' : 'Kirim Catatan' }}
                              </button>
                           </form>
                        </Card>
                      </div>
                   </div>
                </div>
 
                <!-- Tab: Evaluations -->
                <div v-if="activeTab === 'evaluations'" class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
                   <!-- Show existing evaluations -->
                    <div v-if="evaluations.length > 0" class="space-y-8">
                      <Card v-for="evaluation in evaluations" :key="evaluation.id" class="p-12 border-none shadow-premium relative overflow-hidden bg-white dark:bg-slate-800">
                         <div class="absolute top-0 right-0 p-10">
                            <div :class="['px-6 py-2.5 rounded-2xl text-xs font-semibold text-xs tracking-wide border-2', evaluation.final_status === 'completed' ? 'bg-green-50 text-green-600 border-green-100' : 'bg-red-50 text-red-600 border-red-100']">
                               {{ evaluation.final_status === 'completed' ? 'Direkomendasikan' : 'Perlu Evaluasi Lanjut' }}
                            </div>
                         </div>
                         
                         <div class="flex items-center gap-4 mb-2">
                            <div class="w-12 h-12 rounded-2xl bg-slate-900 dark:bg-white flex items-center justify-center text-white dark:text-slate-900">
                                <GraduationCap class="w-7 h-7" />
                            </div>
                            <h3 class="text-3xl font-bold text-slate-900 dark:text-white">{{ evaluation.title }}</h3>
                         </div>
                         <p class="text-xs text-slate-400 font-semibold text-xs tracking-wide mb-12 ml-16 italic">Disusun Oleh {{ evaluation.mentor.name }} • {{ formatDate(evaluation.created_at, { day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
                         
                         <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 mb-12">
                            <div class="space-y-6">
                               <div class="flex items-center gap-3">
                                  <div class="w-1 h-6 bg-primary-500 rounded-full"></div>
                                  <h4 class="text-xs font-bold text-slate-900 dark:text-white font-medium">Ringkasan Performa</h4>
                               </div>
                               <p class="text-base text-slate-600 dark:text-slate-400 leading-relaxed font-medium">{{ evaluation.summary }}</p>
                            </div>
                            <div class="space-y-8">
                               <div class="flex items-center gap-3">
                                  <div class="w-1 h-6 bg-primary-500 rounded-full"></div>
                                  <h4 class="text-xs font-bold text-slate-900 dark:text-white font-medium">Metrik Pencapaian</h4>
                               </div>
                               <div class="space-y-6">
                                  <div v-for="(val, key) in evaluation.metrics" :key="key" class="space-y-3">
                                     <div class="flex items-center justify-between text-sm">
                                        <span class="capitalize font-bold text-slate-600 dark:text-slate-400">{{ String(key).replace('_', ' ') }}</span>
                                        <span class="font-bold text-primary-600">{{ val }}<span class="text-slate-400 text-xs">/5</span></span>
                                     </div>
                                     <div class="h-3 w-full bg-slate-100 dark:bg-slate-900 rounded-full overflow-hidden p-0.5 border border-slate-50 dark:border-slate-800">
                                        <div class="h-full bg-gradient-to-r from-primary-500 to-primary-600 rounded-full shadow-lg shadow-primary-500/30 transition-colors duration-1000" :style="{ width: (val * 20) + '%' }"></div>
                                     </div>
                                  </div>
                               </div>
                            </div>
                         </div>
                         
                         <div class="p-8 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-inner relative overflow-hidden group">
                            <div class="absolute top-0 left-0 w-2 h-full bg-primary-600 opacity-20 group-hover:opacity-100 transition-opacity"></div>
                            <h4 class="text-xs font-bold text-primary-500 font-medium mb-3">Rekomendasi Karir & Langkah Strategis</h4>
                            <p class="text-lg text-slate-800 dark:text-slate-200 font-extrabold leading-snug">{{ evaluation.recommendation }}</p>
                         </div>
                      </Card>
                   </div>
    
                   <!-- Evaluation Form -->
                   <Card v-else class="p-12 border-none shadow-premium max-w-5xl mx-auto bg-white dark:bg-slate-800 relative overflow-hidden">
                      <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-primary-600 via-purple-600 to-pink-600"></div>
                      
                      <div class="text-center mb-12">
                         <div class="w-20 h-20 bg-slate-900 text-white rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-2xl rotate-3">
                            <BarChart3 class="w-10 h-10" />
                         </div>
                         <h3 class="text-3xl font-bold text-slate-900 dark:text-white">Evaluasi Akhir Periode</h3>
                         <p class="text-base text-slate-500 mt-2 max-w-lg mx-auto">Berikan ulasan menyeluruh untuk menutup periode magang dengan standar profesional tinggi.</p>
                      </div>
    
                      <form class="space-y-12" @submit.prevent="submitEvaluation">
                         <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
                            <div class="space-y-8">
                               <div class="space-y-2.5">
                                  <label class="text-xs font-bold text-slate-400 font-medium ml-1">Nama Laporan Evaluasi</label>
                                  <input v-model="evaluationForm.title" type="text" class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-sm p-5 focus:ring-4 focus:ring-primary-500/10 transition-colors outline-none" required />
                               </div>
                               <div class="space-y-2.5">
                                  <label class="text-xs font-bold text-slate-400 font-medium ml-1">Analisis Performa (Ringkasan)</label>
                                  <textarea v-model="evaluationForm.summary" rows="7" class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-sm p-6 focus:ring-4 focus:ring-primary-500/10 transition-colors outline-none" placeholder="Ceritakan bagaimana perkembangan mentee selama 3 bulan terakhir..." required></textarea>
                               </div>
                            </div>
    
                            <div class="space-y-10">
                               <p class="text-xs font-bold text-slate-400 font-medium ml-1">Rating Kompetensi (1-5)</p>
                               <div v-for="(val, key) in evaluationForm.metrics" :key="key" class="space-y-5 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 p-6 rounded-2xl transition-colors duration-300 hover:shadow-xl relative overflow-hidden group">
                                  <!-- Decorative blurred backdrop orb that changes color dynamically based on rating -->
                                  

                                  <div class="flex items-center justify-between relative z-10">
                                     <span class="text-xs font-bold text-slate-400 font-medium capitalize">{{ String(key).replace('_', ' ') }}</span>
                                     <div class="flex items-center gap-2 px-3 py-1 rounded-full border transition-colors duration-500 font-bold" :class="[getMetricDisplay(val).colorClass]">
                                        <span class="text-base animate-bounce">{{ getMetricDisplay(val).emoji }}</span>
                                        <span class="text-xs font-semibold text-sm tracking-wider">{{ getMetricDisplay(val).label }}</span>
                                        <span class="text-xs font-bold">({{ val }} / 5)</span>
                                     </div>
                                  </div>

                                  <div class="relative pt-2 pb-2">
                                     <!-- Premium styled progress track background -->
                                     <div class="absolute left-0 right-0 top-1/2 -translate-y-1/2 h-2.5 bg-slate-100 dark:bg-slate-800 rounded-full"></div>
                                     <!-- Premium styled dynamic gradient filling -->
                                     <div class="absolute left-0 top-1/2 -translate-y-1/2 h-2.5 rounded-full transition-colors duration-300 shadow-md" :class="[getMetricDisplay(val).gradientClass]" :style="{ width: ((val - 1) * 25) + '%' }"></div>

                                     <!-- The native slider invisible overlay but active -->
                                     <input 
                                        v-model="evaluationForm.metrics[key]" 
                                        type="range" 
                                        min="1" 
                                        max="5" 
                                        step="1" 
                                        class="relative w-full h-8 opacity-0 cursor-pointer z-10" 
                                     />

                                     <!-- Visual Floating Bubble Thumb indicator -->
                                     <div 
                                        class="absolute top-1/2 -translate-y-1/2 w-7 h-7 rounded-full bg-white border-2 dark:bg-slate-900 border-white dark:border-slate-800 shadow-[0_4px_12px_rgba(0,0,0,0.15)] flex items-center justify-center pointer-events-none transition-colors duration-300 scale-110 group-hover:scale-125"
                                        :style="{ left: 'calc(' + ((val - 1) * 25) + '% - 14px)' }"
                                     >
                                        <div class="w-2.5 h-2.5 rounded-full" :class="[getMetricDisplay(val).gradientClass]"></div>
                                     </div>
                                  </div>
                                  
                                  <!-- Level scale markers -->
                                  <div class="flex justify-between px-1 text-xs font-bold text-slate-400 dark:text-neutral-500 font-medium select-none">
                                     <span>Sangat Kurang</span>
                                     <span>Kurang</span>
                                     <span>Cukup</span>
                                     <span>Baik</span>
                                     <span>Sangat Baik</span>
                                  </div>
                               </div>
                            </div>
                         </div>
    
                         <div class="space-y-2.5">
                            <label class="text-xs font-bold text-slate-400 font-medium ml-1">Rekomendasi Karir & Saran Strategis</label>
                            <textarea v-model="evaluationForm.recommendation" rows="4" class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-lg font-bold p-6 focus:ring-4 focus:ring-primary-500/10 transition-colors outline-none shadow-inner" placeholder="Tuliskan saran konkret untuk masa depan mentee..."></textarea>
                         </div>
    
                         <div class="pt-10 border-t border-slate-100 dark:border-slate-800 flex flex-col md:flex-row items-center justify-between gap-8">
                            <div class="flex items-center gap-3 bg-slate-100 dark:bg-slate-900 p-2.5 rounded-2xl shadow-inner">
                               <button 
                                  type="button" 
                                  class="px-8 py-4 rounded-2xl text-xs font-bold transition-colors active-press"
                                  :class="evaluationForm.final_status === 'recommend' ? 'bg-white dark:bg-slate-800 text-green-600 shadow-xl' : 'text-slate-500 opacity-60 hover:opacity-100'"
                                  @click="evaluationForm.final_status = 'recommend'"
                               >
                                  Rekomendasikan
                               </button>
                               <button 
                                  type="button" 
                                  class="px-8 py-4 rounded-2xl text-xs font-bold transition-colors active-press"
                                  :class="evaluationForm.final_status === 'not_recommend' ? 'bg-white dark:bg-slate-800 text-red-600 shadow-xl' : 'text-slate-500 opacity-60 hover:opacity-100'"
                                  @click="evaluationForm.final_status = 'not_recommend'"
                               >
                                  Tinjau Ulang
                               </button>
                            </div>
                            <button type="submit" :disabled="evaluationForm.processing" class="w-full md:w-auto bg-slate-900 dark:bg-white dark:text-slate-900 text-white px-16 py-5 rounded-[2rem] text-sm font-bold hover:scale-105 shadow-2xl transition-colors active-press flex items-center justify-center gap-3">
                               <span v-if="evaluationForm.processing" class="w-5 h-5 border-2 border-white/30 border-t-white animate-spin rounded-full"></span>
                               {{ evaluationForm.processing ? 'Menyimpan...' : 'Kirim Evaluasi Akhir' }}
                            </button>
                         </div>
                      </form>
                   </Card>
                </div>
            </div>
         </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 20px;
  height: 20px;
  background: white;
  border: 3px solid #3b82f6;
  border-radius: 50%;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
  transition: all 0.2s ease;
}

input[type="range"]::-webkit-slider-thumb:hover {
  transform: scale(1.2);
  box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
}

input[type="range"]::-moz-range-thumb {
  width: 18px;
  height: 18px;
  background: white;
  border: 3px solid #3b82f6;
  border-radius: 50%;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}
</style>

