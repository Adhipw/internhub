<script setup lang="ts">
import { Link, router as inertiaRouter } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { 
    Briefcase, Bookmark, FileCheck, CheckCircle2, 
    ArrowUpRight, Clock, MapPin, ChevronRight, User,
    Search, Zap, Sparkles, Building2, Bell, ShieldCheck, Trophy,
    TrendingUp, Calendar, ClipboardCheck
} from 'lucide-vue-next';
import Card from '@/Components/Card.vue';
import StatsCard from '@/Components/StatsCard.vue';
import KanbanCard from '@/Components/KanbanCard.vue';
import Skeleton from '@/Components/Skeleton.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatDate } from '@/Lib/utils';
import { useAuthStore } from '@/Stores/auth';
import { useLangStore } from '@/Stores/lang';
import type { Application } from '@/Types/application';
import type { Internship } from '@/Types/internship';
import echo from '@/echo';

interface DashboardData {
    recent_applications: Application[];
    recommended_internships: Internship[];
    notifications: any[];
    stats: {
        profile_completion: number;
        total_applications: number;
        active_applications: number;
        saved_internships: number;
    };
}

interface DashboardProps extends DashboardData {
    saved_internships?: any[];
}

const props = defineProps<DashboardProps>();

// State & Data
const authStore = useAuthStore();
const langStore = useLangStore();
const user = computed(() => authStore.user || {});
const t = (key: string) => langStore.t(key);
const __ = (key: string) => langStore.__(key);

const stats = computed(() => props.stats || {
    profile_completion: 0,
    total_applications: 0,
    active_applications: 0,
    saved_internships: 0,
});
const recent_applications = computed(() => props.recent_applications || []);
const recommended_internships = computed(() => props.recommended_internships || []);
const notifications = computed(() => props.notifications || []);
const loading = ref(false);

const fetchData = () => {
    loading.value = true;
    inertiaRouter.reload({
        only: ['stats', 'recent_applications', 'recommended_internships', 'notifications', 'saved_internships'],
        onFinish: () => { loading.value = false; }
    });
};

onMounted(() => {
    if (echo && authStore.user?.id) {
        echo.private(`App.Models.User.${authStore.user.id}`)
            .listen('DashboardUpdated', (e: any) => {
                if (e.reload) {
                    fetchData();
                }
            });
    }
});

onUnmounted(() => {
    if (echo && authStore.user?.id) {
        echo.leave(`App.Models.User.${authStore.user.id}`);
    }
});
</script>

<template>
    <DashboardLayout>
        <div class="space-y-12 pb-20">
            <!-- Premium Hero Header -->
            <section class="relative p-8 rounded-2xl overflow-hidden group shadow-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                <div class="relative flex flex-col lg:flex-row lg:items-center justify-between gap-10">
                    <div class="space-y-6">

                        <h1 class="text-4xl md:text-5xl font-bold text-slate-900 dark:text-white tracking-tight">
                            {{ t('dashboard.halo') || 'Halo,' }} <br/>
                            <span class="text-blue-600">{{ user.name?.split(' ')[0] || 'User' }}</span>
                        </h1>

                    </div>
                    
                    <!-- Premium Profile Completion Widget -->
                    <Card class="!p-8 !rounded-2xl border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-8 group/card transition-colors hover:shadow-md">
                        <div class="relative w-24 h-24 shrink-0">
                            <svg class="w-full h-full transform -rotate-90">
                                <circle cx="48" cy="48" r="42" fill="transparent" stroke="currentColor" stroke-width="8" class="text-slate-100 dark:text-slate-800" />
                                <circle
cx="48" cy="48" r="42" fill="transparent" stroke="currentColor" stroke-width="8" 
                                    :stroke-dasharray="2 * Math.PI * 42" 
                                    :stroke-dashoffset="2 * Math.PI * 42 * (1 - (stats.profile_completion || 0) / 100)" 
                                    stroke-linecap="round"
                                    class="text-blue-600 transition-colors duration-1000"
                                />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center text-xl font-bold text-slate-900 dark:text-white" style="font-variant-numeric: tabular-nums">
                                {{ stats.profile_completion || 0 }}%
                            </div>
                        </div>
                        <div class="space-y-1">
                            <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">{{ t('dashboard.profile_completion') || 'Kelengkapan Profil' }}</p>
                            <Link href="/profile" class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 hover:text-blue-600 transition-colors group/link">
                                {{ t('dashboard.complete_data') || 'Lengkapi Data' }}
                                <ArrowUpRight class="w-5 h-5 group-hover/link:translate-x-1 group-hover/link:-translate-y-1 transition-transform" />
                            </Link>
                        </div>
                    </Card>
                </div>
            </section>

            <!-- Onboarding Action Card (Batch 7) -->
            <div v-if="recent_applications.some(app => app.status === 'accepted')" class="animate-fade-in">
                <Card class="!p-8 !rounded-2xl !bg-emerald-600 text-white border-none shadow-sm relative overflow-hidden group">
                    <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-8">
                        <div class="space-y-4">
                            <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/20 rounded-full text-xs font-semibold">
                                <CheckCircle2 class="w-3.5 h-3.5" />
                                {{ langStore.locale === 'id' ? 'Tindakan Diperlukan' : 'Action Required' }}
                            </div>
                            <h2 class="text-3xl font-bold tracking-tight">
                                {{ langStore.locale === 'id' ? 'Selamat! Lamaran Anda Diterima.' : 'Congratulations! Your Application is Accepted.' }}
                            </h2>
                            <p class="text-emerald-50 font-medium text-lg max-w-2xl">
                                {{ langStore.locale === 'id' ? 'Langkah terakhir: Silakan lengkapi dokumen onboarding (KTP, Perjanjian Magang) untuk meresmikan status magang Anda.' : 'Final step: Please complete the onboarding documents (ID Card, Internship Agreement) to formalize your internship status.' }}
                            </p>
                        </div>
                        <Link 
                            v-if="recent_applications.find(app => app.status === 'accepted')"
                            :href="'/my-applications/' + recent_applications.find(app => app.status === 'accepted')?.id + '/onboarding'"
                            class="bg-white text-emerald-600 px-6 py-3 rounded-xl font-bold text-sm active:scale-[0.98] transition-colors cursor-pointer text-center whitespace-nowrap shadow-sm hover:bg-slate-50"
                        >
                            {{ langStore.locale === 'id' ? 'Lengkapi Dokumen' : 'Complete Documents' }}
                        </Link>
                    </div>
                </Card>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <StatsCard 
                    v-for="(stat, i) in [
                        { label: t('dashboard.total_apps') || 'Total Lamaran', value: stats.total_applications, icon: Briefcase, color: 'primary' },
                        { label: t('dashboard.active_process') || 'Proses Aktif', value: stats.active_applications, icon: Zap, color: 'warning' },
                        { label: t('dashboard.favorites') || 'Favorit', value: stats.saved_internships, icon: Bookmark, color: 'danger' },
                        { label: t('dashboard.new_opportunities') || 'Peluang Baru', value: recommended_internships.length, icon: Sparkles, color: 'success' },
                    ]" 
                    :key="i"
                    :label="stat.label"
                    :value="stat.value"
                    :icon="stat.icon"
                    :color="stat.color"
                    :loading="loading"
                />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-12">
                    <section class="space-y-8">
                        <div class="flex items-end justify-between px-4">
                            <div>
                                <h2 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">{{ t('dashboard.app_status') || 'Tracking Lamaran' }}</h2>
                                <p class="text-slate-500 font-bold text-sm">{{ t('dashboard.track_desc') || 'Pantau status magang Anda secara real-time.' }}</p>
                            </div>
                            <Link href="/my-applications" class="text-xs font-medium text-blue-600 hover:underline">Lihat Semua</Link>
                        </div>

                        <div v-if="loading" class="space-y-4">
                            <Skeleton v-for="i in 3" :key="i" height="100px" class="rounded-2xl" />
                        </div>
                        <div v-else-if="recent_applications.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <KanbanCard 
                                v-for="app in recent_applications" 
                                :key="app.id"
                                :title="app.internship?.title"
                                :subtitle="app.internship?.company?.name"
                                :status="app.status"
                                :date="app.created_at_human || ''"
                                :image="app.internship?.company?.logo_url || ''"
                                :href="'/my-applications/' + app.id"
                                show-decision-indicator
                            />
                        </div>
                        <div v-else class="bg-slate-50/50 dark:bg-slate-900/50 border-2 border-dashed border-slate-100 dark:border-slate-800 rounded-2xl p-20 text-center">
                            <div class="w-20 h-20 bg-white dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm">
                                <Briefcase class="w-10 h-10 text-slate-200" />
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Belum Ada Lamaran</h3>
                            <p class="text-slate-500 font-medium mb-8">Mulai karirmu dengan melamar ke posisi yang tersedia.</p>
                            <Link href="/internships" class="inline-flex items-center gap-2 bg-blue-600 text-white px-6 py-3 rounded-xl font-bold text-sm hover:bg-blue-700 active:scale-[0.98] transition-colors cursor-pointer shadow-sm">
                                <Search class="w-4 h-4" /> Cari Magang
                            </Link>
                        </div>
                    </section>
                </div>

                <!-- Sidebar Content -->
                <div class="space-y-12">
                    <section class="bg-slate-900 dark:bg-white p-10 rounded-2xl shadow-sm border border-slate-800 dark:border-slate-200 relative overflow-hidden group">
                        
                        
                        <div class="flex items-center justify-between mb-10 relative z-10">
                            <h2 class="text-xl font-bold text-white dark:text-slate-900 tracking-tight">{{ t('dashboard.notifications') || 'Notifikasi' }}</h2>
                            <span v-if="notifications.length > 0" class="w-6 h-6 bg-primary-500 text-white text-xs font-bold rounded-full flex items-center justify-center">{{ notifications.length }}</span>
                        </div>

                        <div v-if="loading" class="space-y-6">
                            <Skeleton v-for="i in 3" :key="i" height="60px" class="!bg-white/10 dark:!bg-slate-100 rounded-2xl" />
                        </div>
                        <div v-else-if="notifications.length > 0" class="space-y-8 relative z-10">
                            <div v-for="note in notifications" :key="note.id" class="flex gap-4 group/item cursor-pointer">
                                <div v-if="!note.read_at" class="w-1.5 h-1.5 rounded-full bg-primary-400 mt-2 shrink-0 "></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-slate-300 dark:text-slate-600 leading-relaxed group-hover/item:text-white dark:group-hover/item:text-black transition-colors">
                                        {{ note.data?.message || note.data?.title || t('dashboard.new_notif') }}
                                    </p>
                                    <p class="text-xs font-medium text-slate-500 mt-2">{{ note.created_at_human }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-10 relative z-10">
                            <Bell class="w-12 h-12 text-slate-700 dark:text-slate-200 mx-auto mb-4 opacity-20" aria-hidden="true" />
                            <p class="text-xs font-bold text-slate-500">Tidak ada notifikasi baru.</p>
                        </div>
                    </section>

                    <!-- Career Tips / Promo -->
                    <Card class="!bg-blue-600 !p-8 !rounded-2xl text-white border-none shadow-sm relative overflow-hidden group">
                        <div class="relative">
                        <h3 class="text-2xl font-bold mb-4 relative z-10 leading-tight">Siapkan CV Terbaikmu!</h3>
                        <p class="text-sm font-medium text-white/80 mb-8">Tingkatkan peluang diterima dengan CV yang profesional dan sesuai standar industri.</p>
                        <Link href="/cv-guide" class="inline-flex justify-center items-center gap-2 bg-white text-blue-600 px-6 py-3 rounded-xl font-bold text-sm hover:bg-slate-50 active:scale-[0.98] transition-colors cursor-pointer shadow-sm">
                            Baca Tips
                        </Link>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

