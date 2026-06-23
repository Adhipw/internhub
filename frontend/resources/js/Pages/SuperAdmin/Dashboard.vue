<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { 
    Users, ShieldCheck, Database, Globe, 
    Settings, Activity, AlertCircle, ArrowUpRight,
    Lock, Server, Zap, BarChart3, History,
    Cpu, HardDrive, Network, Sparkles, Bell,
    Briefcase, RefreshCw
} from 'lucide-vue-next';
import Card from '@/Components/Card.vue';
import Skeleton from '@/Components/Skeleton.vue';
import { useAuthStore } from '@/Stores/auth';
import { useLangStore } from '@/Stores/lang';
import api from '@/Services/api';
import logger from '@/Lib/logger';
import echo from '@/echo';
import VueApexCharts from 'vue3-apexcharts';
import type { ApexOptions } from 'apexcharts';

interface SuperAdminDashboardProps {
    stats?: Record<string, any>;
    chart_data?: {
        dates: string[];
        users: number[];
        applications: number[];
    };
    security_events?: any[];
    audit_logs?: any[];
    system_info?: Record<string, any>;
    system_health?: Record<string, any>;
}

const props = defineProps<SuperAdminDashboardProps>();

const authStore = useAuthStore();
const langStore = useLangStore();
const user = computed(() => authStore.user || {});
const t = (key: string) => langStore.t(key);
const __ = (key: string) => langStore.__(key);

const loading = ref(false);
const error = ref<any>(null);

const stats = computed(() => props.stats || {});
const systemHealth = computed(() => props.system_health || { status: 'healthy', uptime: '99.9%', latency: '45ms' });
const globalStats = computed(() => [
    { label: t('super_admin.dashboard.total_users') || 'Total Users', value: stats.value.total_users || 0, icon: Users, color: 'text-indigo-600', bg: 'bg-indigo-500/10' },
    { label: t('super_admin.dashboard.total_admins') || 'Administrators', value: stats.value.total_admins || 0, icon: ShieldCheck, color: 'text-emerald-600', bg: 'bg-emerald-500/10' },
    { label: t('super_admin.dashboard.total_super_admins') || 'Root Access', value: stats.value.total_super_admins || 0, icon: Lock, color: 'text-rose-600', bg: 'bg-rose-500/10' },
    { label: t('super_admin.dashboard.active_sessions') || 'Live Sessions', value: stats.value.active_sessions || 0, icon: Globe, color: 'text-blue-600', bg: 'bg-blue-500/10' },
]);
const recentSecurityEvents = computed(() => props.security_events || []);

import { router as inertiaRouter } from '@inertiajs/vue3';

const fetchData = () => {
    loading.value = true;
    inertiaRouter.reload({
        only: ['stats', 'security_events', 'audit_logs', 'system_health', 'chart_data'],
        onFinish: () => { loading.value = false; },
        onError: (err) => {
            error.value = err;
            logger.error('Failed to refresh super admin dashboard:', err);
        }
    });
};

const chartOptions = computed<ApexOptions>(() => {
    const isDark = document.documentElement.classList.contains('dark');
    return {
        chart: {
            type: 'area',
            height: 350,
            fontFamily: 'inherit',
            toolbar: { show: false },
            zoom: { enabled: false },
            background: 'transparent'
        },
        colors: ['#3b82f6', '#10b981'], 
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3 },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.4,
                opacityTo: 0.05,
                stops: [0, 90, 100]
            }
        },
        xaxis: {
            categories: props.chart_data?.dates || [],
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: {
                style: { colors: isDark ? '#94a3b8' : '#64748b' }
            }
        },
        yaxis: {
            labels: {
                style: { colors: isDark ? '#94a3b8' : '#64748b' }
            }
        },
        grid: {
            borderColor: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)',
            strokeDashArray: 4,
            xaxis: { lines: { show: true } },
            yaxis: { lines: { show: true } }
        },
        theme: { mode: isDark ? 'dark' : 'light' },
        legend: {
            position: 'top',
            horizontalAlign: 'right',
            labels: { colors: isDark ? '#f1f5f9' : '#0f172a' }
        },
        tooltip: { theme: isDark ? 'dark' : 'light' }
    };
});

const chartSeries = computed(() => [
    {
        name: 'New Users',
        data: props.chart_data?.users || []
    },
    {
        name: 'New Applications',
        data: props.chart_data?.applications || []
    }
]);

onMounted(() => {
    if (echo && user.value?.id) {
        echo.join('admins.online')
            .listen('DashboardUpdated', (e: any) => {
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
            <!-- Super Admin Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-8 animate-fade-in">
                <div class="space-y-4">
                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 rounded-full text-[10px] font-black uppercase tracking-widest border border-indigo-100 dark:border-indigo-900/30">
                        <ShieldCheck class="w-3 h-3" />
                        {{ t('super_admin.dashboard.root_authority') }}
                    </div>
                    <h1 class="text-5xl font-black text-slate-900 dark:text-white tracking-tight">
                        <span class="text-indigo-600">{{ t('super_admin.dashboard.platform_commander') }}</span>
                    </h1>
                    <p class="text-slate-500 dark:text-slate-400 font-medium text-lg">{{ t('super_admin.dashboard.sub_desc') }}</p>
                </div>

                <div class="flex gap-4">
                    <button 
                        :disabled="loading" 
                        class="p-4 bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all active:scale-95 group"
                        @click="fetchData"
                    >
                        <RefreshCw :class="['w-5 h-5 text-slate-400 group-hover:text-indigo-500', loading ? 'animate-spin' : '']" />
                    </button>
                    <Card glass class="!p-4 !rounded-2xl border-white/50 flex items-center gap-4">
                        <div class="w-3 h-3 rounded-full bg-indigo-500 shadow-[0_0_15px_rgba(99,102,241,0.5)]"></div>
                        <span class="text-xs font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest">{{ t('super_admin.dashboard.master_key_active') }}</span>
                    </Card>
                </div>
            </div>

            <!-- Global Infrastructure Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <Card v-for="(stat, i) in globalStats" :key="i" premium hoverable class="!p-8 !rounded-[3rem] group overflow-hidden">
                    <div v-if="loading && !stats.total_users" class="space-y-4">
                        <Skeleton width="3rem" height="3rem" class="rounded-2xl" />
                        <Skeleton width="60%" height="1rem" />
                        <Skeleton width="40%" height="2.5rem" />
                    </div>
                    <template v-else>
                        <div :class="['w-14 h-14 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-all', stat.bg, stat.color]">
                            <component :is="stat.icon" class="w-6 h-6" />
                        </div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ stat.label }}</p>
                        <p class="text-4xl font-black text-slate-900 dark:text-white">{{ stat.value }}</p>
                    </template>
                </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                <!-- System Health Monitor -->
                <div class="lg:col-span-2 space-y-12">
                    
                    <!-- System Activity Chart -->
                    <Card class="!p-8 !rounded-[3rem] border-slate-100 dark:border-white/5 shadow-premium">
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <h2 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                                    <Activity class="w-5 h-5 text-indigo-600" />
                                    System Activity Overview
                                </h2>
                                <p class="text-xs font-bold text-slate-500 mt-1">Growth & engagement over the last 7 days</p>
                            </div>
                        </div>
                        <div class="h-[350px] w-full">
                            <VueApexCharts 
                                v-if="props.chart_data"
                                type="area" 
                                height="350" 
                                :options="chartOptions" 
                                :series="chartSeries" 
                            />
                        </div>
                    </Card>

                    <div class="flex items-center justify-between px-4">
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                            <Activity class="w-6 h-6 text-indigo-600" />
                            {{ t('super_admin.dashboard.infra_health') || 'Infrastructure Health' }}
                        </h2>
                        <div class="flex items-center gap-2 text-emerald-500 text-xs font-black uppercase tracking-widest">
                            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                            {{ systemHealth.status === 'healthy' ? (t('super_admin.dashboard.all_systems_operational') || 'All Systems Operational') : 'System Warning' }}
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <Card glass class="!p-10 !rounded-[3.5rem] border-white/50 group">
                            <div class="flex items-center justify-between mb-10">
                                <div class="w-14 h-14 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 rounded-[1.5rem] flex items-center justify-center group-hover:rotate-12 transition-transform">
                                    <Database class="w-8 h-8" />
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Disk Storage</p>
                                    <p class="text-xl font-black text-slate-900 dark:text-white">{{ stats.storage_used }}% Used</p>
                                </div>
                            </div>
                            <div class="h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden mb-4">
                                <div class="h-full bg-indigo-500 transition-all duration-1000" :style="{ width: stats.storage_used + '%' }"></div>
                            </div>
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Status: {{ systemHealth.storage }}</p>
                        </Card>

                        <Card glass class="!p-10 !rounded-[3.5rem] border-white/50 group">
                            <div class="flex items-center justify-between mb-10">
                                <div class="w-14 h-14 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 rounded-[1.5rem] flex items-center justify-center group-hover:rotate-12 transition-transform">
                                    <Zap class="w-8 h-8" />
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">API Latency</p>
                                    <p class="text-xl font-black text-slate-900 dark:text-white">{{ systemHealth.latency }}</p>
                                </div>
                            </div>
                            <div class="h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden mb-4">
                                <div class="h-full bg-emerald-500 w-[100%]"></div>
                            </div>
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Uptime: {{ systemHealth.uptime }}</p>
                        </Card>
                    </div>

                    <!-- Global Security Events -->
                    <Card class="!p-0 !rounded-[3.5rem] overflow-hidden border-slate-100 dark:border-white/5 shadow-premium">
                        <div class="p-8 border-b border-slate-50 dark:border-white/5 flex items-center justify-between">
                            <h3 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                                <Lock class="w-6 h-6 text-rose-500" />
                                {{ t('admin.security.title') || 'Security Events' }}
                            </h3>
                            <Link href="/super-admin/security-events" class="text-[10px] font-black text-indigo-600 uppercase tracking-widest hover:underline">{{ t('super_admin.dashboard.view_all') || 'View All' }}</Link>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <tbody class="divide-y divide-slate-50 dark:divide-white/5">
                                    <tr v-for="event in recentSecurityEvents" :key="event.id" class="hover:bg-slate-50/50 dark:hover:bg-white/5 transition-colors group">
                                        <td class="px-8 py-6">
                                            <div class="flex items-center gap-4">
                                                <div class="w-2 h-2 rounded-full bg-rose-500"></div>
                                                <div>
                                                    <p class="text-sm font-black text-slate-900 dark:text-white">{{ event.event_type }}</p>
                                                    <p class="text-[10px] font-bold text-slate-400">{{ event.ip_address }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-8 py-6">
                                            <p class="text-xs font-bold text-slate-500 leading-relaxed">{{ event.description }}</p>
                                        </td>
                                        <td class="px-8 py-6 text-right">
                                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ event.created_at_human }}</span>
                                        </td>
                                    </tr>
                                    <tr v-if="recentSecurityEvents.length === 0">
                                        <td colspan="3" class="px-8 py-20 text-center text-slate-400 font-bold italic">
                                            <ShieldCheck class="w-12 h-12 text-slate-100 mx-auto mb-4" />
                                            {{ t('admin.security.no_events') || 'No security threats detected.' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>

                <!-- Super Admin Sidebar -->
                <div class="space-y-12">
                    <!-- Master Control Center -->
                    <Card class="!p-10 !rounded-[4rem] bg-white dark:!bg-slate-950 text-slate-900 dark:text-white border-none shadow-2xl relative overflow-hidden group">
                        <Database class="w-24 h-24 absolute -right-6 -bottom-6 opacity-10 group-hover:scale-110 transition-transform" />
                        <h3 class="text-2xl font-black mb-8 relative z-10 leading-tight">{{ t('sidebar.system_integration') || 'Control Center' }}</h3>
                        
                        <div class="grid grid-cols-2 gap-4 relative z-10">
                            <Link
v-for="link in [
                                { label: t('sidebar.system_settings'), icon: Server, to: '/super-admin/settings' },
                                { label: t('sidebar.system_integration'), icon: Zap, to: '/super-admin/integrations' },
                                { label: t('sidebar.global_users'), icon: Users, to: '/super-admin/users' },
                                { label: t('sidebar.audit_logs'), icon: History, to: '/super-admin/audit-logs' }
                            ]" :key="link.label" :href="link.to" class="bg-slate-50 hover:bg-slate-100 dark:bg-white/10 dark:hover:bg-white/20 p-6 rounded-[2.5rem] flex flex-col items-center gap-3 transition-all active-press">
                                <component :is="link.icon" class="w-6 h-6 text-indigo-500 dark:text-indigo-400" />
                                <span class="text-[9px] font-black uppercase tracking-widest text-center">{{ link.label }}</span>
                            </Link>
                        </div>
                    </Card>

                    <!-- Realtime Log Stream -->
                    <section class="space-y-6">
                        <div class="flex items-center justify-between px-4">
                            <h3 class="text-xl font-black text-slate-900 dark:text-white">{{ t('admin.audit.live_stream') || 'Live Activity' }}</h3>
                            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                        </div>
                        
                        <div class="bg-slate-50 dark:bg-slate-900 rounded-[3rem] p-8 space-y-6 max-h-[300px] overflow-y-auto custom-scrollbar border border-slate-200 dark:border-white/5 shadow-inner">
                            <template v-if="props?.audit_logs">
                                <div v-for="log in props.audit_logs" :key="log.id" class="flex gap-4">
                                    <div class="text-[10px] font-mono text-indigo-500 shrink-0">{{ new Date(log.created_at).toLocaleTimeString() }}</div>
                                    <div class="text-[10px] font-mono text-slate-600 dark:text-slate-400 break-all leading-relaxed">
                                        <span class="text-emerald-500 dark:text-emerald-400">[{{ log.user?.name || 'System' }}]</span> {{ log.action }} : {{ log.description }}
                                    </div>
                                </div>
                            </template>
                            <div v-if="!props?.audit_logs || props.audit_logs.length === 0" class="text-center py-10 text-slate-500 text-xs italic font-mono">
                                No activity recorded.
                            </div>
                        </div>
                    </section>

                    <!-- System Info -->
                    <Card class="!p-10 !rounded-[3.5rem] bg-white dark:bg-slate-900 border-none shadow-xl space-y-6">
                        <h3 class="text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <Server class="w-5 h-5 text-indigo-500" />
                            System Environment
                        </h3>
                        
                        <div class="space-y-4">
                            <template v-if="props?.system_info">
                                <div v-for="(value, key) in props.system_info" :key="key" class="flex items-center justify-between">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ String(key).replace(/_/g, ' ') }}</span>
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ value }}</span>
                                </div>
                            </template>
                            <div v-else class="space-y-4">
                                <Skeleton v-for="i in 5" :key="i" height="1rem" />
                            </div>
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
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}
</style>

