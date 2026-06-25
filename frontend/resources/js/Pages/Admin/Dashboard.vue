<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { computed } from 'vue';
import { 
    Users, Building2, Briefcase, FileText,
    ShieldCheck, AlertTriangle, Activity, Lock
} from 'lucide-vue-next';
import Card from '@/Components/Card.vue';
import Skeleton from '@/Components/Skeleton.vue';
import { useAuthStore } from '@/Stores/auth';
import { useLangStore } from '@/Stores/lang';
import { router as inertiaRouter } from '@inertiajs/vue3';
import echo from '@/echo';
import { onMounted, onUnmounted, ref } from 'vue';

interface DashboardLog {
  id: number;
  description?: string;
  action?: string;
  created_at_human?: string;
  user?: { name: string };
}
interface DashboardCompany {
  id: number;
  name: string;
  created_at_human?: string;
}

interface AdminDashboardProps {
    stats?: {
        total_users?: number;
        pending_companies?: number;
        active_internships?: number;
        total_applications?: number;
    };
    recentLogs?: DashboardLog[];
    recentLogsFallback?: DashboardLog[];
    pendingCompanies?: DashboardCompany[];
    pendingCompaniesFallback?: DashboardCompany[];
}

const props = defineProps<AdminDashboardProps>();

const authStore = useAuthStore();
const langStore = useLangStore();
const user = computed(() => authStore.user || {});
const t = (key: string) => langStore.t(key);

const stats = computed(() => props.stats || {
    total_users: 0,
    pending_companies: 0,
    active_internships: 0,
    total_applications: 0,
});
const recentLogs = computed(() => props.recentLogs || props.recentLogsFallback || []);
const pendingCompanies = computed(() => props.pendingCompanies || props.pendingCompaniesFallback || []);
const loading = ref(false);

const fetchData = () => {
    loading.value = true;
    inertiaRouter.reload({
        only: ['stats', 'recent_logs', 'pending_companies'],
        onFinish: () => { loading.value = false; }
    });
};

onMounted(() => {
    if (echo && user.value?.id) {
        echo.join('admins.online')
            .listen('DashboardUpdated', (e: Record<string, unknown>) => {
                if (e.reload) {
                    fetchData();
                }
            });
    }
});

onUnmounted(() => {
    if (echo) {
        echo.leave('admins.online');
    }
});
</script>

<template>
    <DashboardLayout>
        <div class="space-y-12 pb-20">
            <!-- Admin Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-8 animate-fade-in">
                <div class="space-y-4">
                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-red-50 dark:bg-red-900/20 text-red-600 rounded-full text-[10px] font-black uppercase tracking-widest border border-red-100 dark:border-red-900/30">
                        <Lock class="w-3 h-3" />
                        System Administration
                    </div>
                    <h1 class="text-5xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ t('admin.dashboard.title') || 'Control Center' }}
                    </h1>
                    <p class="text-slate-500 dark:text-slate-400 font-medium text-lg">Monitor dan kelola seluruh ekosistem InternHub.</p>
                </div>
                
                <div class="flex items-center gap-4 bg-white dark:bg-slate-900 p-2 rounded-2xl border border-slate-100 dark:border-white/5 shadow-sm">
                    <div class="flex items-center gap-3 px-4 py-2 border-r border-slate-100 dark:border-white/5">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">System Live</span>
                    </div>
                    <div class="px-4 py-2 text-xs font-bold text-slate-900 dark:text-white">
                        v2.4.0-prod
                    </div>
                </div>
            </div>

            <!-- Main Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <Card
v-for="(stat, i) in [
                    { label: 'Total Pengguna', value: stats.total_users, icon: Users, color: 'text-blue-600', bg: 'bg-blue-500/10' },
                    { label: 'Moderasi Perusahaan', value: stats.pending_companies, icon: Building2, color: 'text-orange-600', bg: 'bg-orange-500/10' },
                    { label: 'Lowongan Aktif', value: stats.active_internships, icon: Briefcase, color: 'text-emerald-600', bg: 'bg-emerald-500/10' },
                    { label: 'Total Lamaran', value: stats.total_applications, icon: FileText, color: 'text-purple-600', bg: 'bg-purple-500/10' },
                ]" :key="i" premium hoverable class="!p-8 !rounded-[3rem] group">
                    <div v-if="loading" class="space-y-4">
                        <Skeleton width="3rem" height="3rem" class="rounded-2xl" />
                        <Skeleton width="60%" height="1rem" />
                        <Skeleton width="40%" height="2.5rem" />
                    </div>
                    <template v-else>
                        <div :class="['w-14 h-14 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-all', stat.bg, stat.color]">
                            <component :is="stat.icon" class="w-6 h-6" />
                        </div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ stat.label }}</p>
                        <p class="text-4xl font-black text-slate-900 dark:text-white">{{ stat.value || 0 }}</p>
                    </template>
                </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                <!-- Activity Logs -->
                <div class="lg:col-span-2 space-y-8">
                    <div class="flex items-center justify-between px-4">
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                            <Activity class="w-6 h-6 text-primary-600" />
                            Audit System
                        </h2>
                        <Link href="/admin/audit-logs" class="text-xs font-black text-primary-600 uppercase tracking-widest hover:underline">View All Logs</Link>
                    </div>

                    <Card class="!p-0 !rounded-[3rem] overflow-hidden border-slate-100 dark:border-white/5 shadow-premium">
                        <div v-if="loading" class="p-8 space-y-4">
                            <Skeleton v-for="i in 5" :key="i" height="60px" class="rounded-2xl" />
                        </div>
                        <div v-else class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-slate-50/50 dark:bg-slate-800/30 border-b border-slate-100 dark:border-white/5">
                                        <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Admin</th>
                                        <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Aktivitas</th>
                                        <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Waktu</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 dark:divide-white/5">
                                    <tr v-for="log in recentLogs" :key="log.id" class="hover:bg-slate-50/50 dark:hover:bg-white/5 transition-colors">
                                        <td class="px-8 py-6">
                                            <div class="flex items-center gap-4">
                                                <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xs font-black text-slate-500">
                                                    {{ log.user?.name?.charAt(0) }}
                                                </div>
                                                <span class="text-sm font-black text-slate-900 dark:text-white">{{ log.user?.name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-8 py-6">
                                            <p class="text-xs font-bold text-slate-600 dark:text-slate-400 leading-relaxed">{{ log.description || log.action }}</p>
                                        </td>
                                        <td class="px-8 py-6 text-right">
                                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ log.created_at_human }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>

                <!-- Admin Sidebar -->
                <div class="space-y-12">
                    <!-- Moderation Queue -->
                    <section class="space-y-6">
                        <div class="flex items-center justify-between px-4">
                            <h2 class="text-xl font-black text-slate-900 dark:text-white">Moderasi Perusahaan</h2>
                            <span class="px-2 py-1 bg-orange-100 text-orange-700 text-[10px] font-black rounded-lg">{{ pendingCompanies.length }}</span>
                        </div>
                        
                        <div v-if="loading" class="space-y-4">
                            <Skeleton v-for="i in 2" :key="i" height="120px" class="rounded-[2.5rem]" />
                        </div>
                        <div v-else-if="pendingCompanies.length > 0" class="space-y-4">
                            <div v-for="company in pendingCompanies" :key="company.id" class="bg-white dark:bg-slate-900 p-8 rounded-[2.5rem] border border-slate-100 dark:border-white/5 shadow-sm group hover:shadow-md transition-all">
                                <div class="flex items-center gap-4 mb-6">
                                    <div class="w-12 h-12 bg-slate-50 dark:bg-slate-800 rounded-2xl flex items-center justify-center text-slate-400">
                                        <Building2 class="w-6 h-6" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-black text-slate-900 dark:text-white truncate">{{ company.name }}</p>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ company.created_at_human }}</p>
                                    </div>
                                </div>
                                <Link 
                                    :href="'/admin/companies?search=' + company.name"
                                    class="w-full block py-4 bg-slate-50 dark:bg-slate-800 text-center rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-primary-600 hover:text-white transition-all active-press"
                                >
                                    Review Perusahaan
                                </Link>
                            </div>
                        </div>
                        <div v-else class="text-center py-12 bg-slate-50/50 dark:bg-slate-800/20 rounded-[3rem] border-2 border-dashed border-slate-100 dark:border-slate-800">
                            <ShieldCheck class="w-12 h-12 text-slate-200 mx-auto mb-4" />
                            <p class="text-xs font-bold text-slate-400">Semua aman & terverifikasi.</p>
                        </div>
                    </section>

                    <!-- Security Alert -->
                    <div class="bg-red-600 p-10 rounded-[3.5rem] text-white shadow-2xl shadow-red-900/20 relative overflow-hidden group">
                        <AlertTriangle class="w-20 h-20 absolute -right-4 -top-4 opacity-10 group-hover:scale-110 transition-transform" />
                        <h3 class="text-xl font-black mb-4 relative z-10">Security Note</h3>
                        <p class="text-sm font-medium text-white/80 mb-8 relative z-10">Selalu verifikasi legalitas perusahaan sebelum menyetujui akses ke data mahasiswa.</p>
                        <Link href="/admin/companies" class="inline-flex items-center gap-2 bg-white text-red-600 px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-red-50 transition-all">
                            Verifikasi Sekarang
                        </Link>
                    </div>
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
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

