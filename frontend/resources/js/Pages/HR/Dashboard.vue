<script setup lang="ts">
import { Link, router as inertiaRouter } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    ArrowRight,
    Briefcase,
    Building2,
    Calendar,
    CheckCircle2,
    Clock,
    Users,
} from 'lucide-vue-next';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useLangStore } from '@/Stores/lang';
import echo from '@/echo';
import { onMounted, onUnmounted, ref } from 'vue';

interface HrApplication {
    id: number;
    status: string;
    created_at_human?: string;
    user?: {
        name?: string;
    };
    internship?: {
        title?: string;
    };
}

interface Company {
    id?: number;
    name?: string;
    slug?: string;
}

interface HrStats {
    active_internships: number;
    total_applicants: number;
    pending_review: number;
    scheduled_interviews: number;
}

interface HrDashboardProps {
    stats?: Partial<HrStats>;
    recentApplications?: HrApplication[];
    company?: Company;
}

const props = defineProps<HrDashboardProps>();
const langStore = useLangStore();
const t = (key: string) => langStore.t(key);

const defaultStats: HrStats = {
    active_internships: 0,
    total_applicants: 0,
    pending_review: 0,
    scheduled_interviews: 0,
};

const loading = ref(false);
const error = ref('');

const fetchData = () => {
    loading.value = true;
    inertiaRouter.reload({
        only: ['stats', 'recentApplications', 'company'],
        onFinish: () => { loading.value = false; }
    });
};

onMounted(() => {
    if (echo && company.value?.id) {
        echo.private(`company.${company.value.id}`)
            .listen('DashboardUpdated', (e: any) => {
                if (e.reload) {
                    fetchData();
                }
            });
    }
});

onUnmounted(() => {
    if (echo && company.value?.id) {
        echo.leave(`company.${company.value.id}`);
    }
});

const stats = computed<HrStats>(() => ({
    ...defaultStats,
    ...props.stats,
}));
const recentApplications = computed<HrApplication[]>(() => props.recentApplications || []);
const company = computed<Company>(() => props.company || {});

const statsCards = computed(() => [
    { label: t('hr.dashboard.stats_active'), value: stats.value.active_internships, icon: Briefcase },
    { label: t('hr.dashboard.stats_applicants'), value: stats.value.total_applicants, icon: Users },
    { label: t('hr.dashboard.stats_pending'), value: stats.value.pending_review, icon: Clock },
    { label: t('hr.dashboard.stats_interviews'), value: stats.value.scheduled_interviews, icon: Calendar },
]);

const quickLinks = computed(() => [
    { name: t('hr.dashboard.quick_manage_jobs'), href: '/hr/internships', icon: Briefcase },
    { name: t('hr.dashboard.quick_applicants'), href: '/hr/applications', icon: Users },
    { name: t('hr.dashboard.quick_interviews'), href: '/hr/applications?status=reviewing', icon: Calendar },
]);

const pendingApplications = computed(() =>
    recentApplications.value.filter((application) => application.status === 'pending').slice(0, 3)
);
</script>

<template>
    <DashboardLayout>
        <div class="space-y-10 pb-20">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800">
                <div>
                    <h2 class="text-3xl font-display font-bold text-gray-900 dark:text-white tracking-tight">
                        {{ company.name || 'Dashboard HR' }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ t('hr.dashboard.subtitle') }}
                    </p>
                </div>

                <Link
                    href="/hr/internships/create"
                    class="inline-flex items-center px-5 py-2.5 bg-slate-900 hover:bg-slate-800 dark:bg-white dark:hover:bg-gray-100 dark:text-slate-900 text-white text-sm font-bold rounded-xl transition-all shadow-sm"
                >
                    <Briefcase class="w-4 h-4 mr-2" />
                    {{ t('hr.dashboard.post_job') }}
                </Link>
            </div>

            <div v-if="loading" class="flex justify-center py-20">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
            </div>

            <div v-else-if="error" class="p-6 bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl text-sm font-bold">
                {{ error }}
            </div>

            <div v-else class="space-y-10">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div
                        v-for="stat in statsCards"
                        :key="stat.label"
                        class="group p-6 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-3xl shadow-sm"
                    >
                        <component :is="stat.icon" class="w-7 h-7 text-primary-600 mb-5" />
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ stat.label }}</p>
                        <p class="mt-2 text-4xl font-display font-bold text-slate-900 dark:text-white group-hover:text-primary-600 transition-colors">
                            {{ stat.value }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                    <div class="lg:col-span-8 space-y-10">
                        <section>
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ t('hr.dashboard.action_needed') }}</h3>
                                <span v-if="stats.pending_review > 0" class="px-2 py-1 bg-amber-100 text-amber-700 text-[10px] font-bold rounded-md uppercase">
                                    {{ t('hr.dashboard.priority') }}
                                </span>
                            </div>

                            <div v-if="pendingApplications.length > 0" class="space-y-4">
                                <div
                                    v-for="application in pendingApplications"
                                    :key="application.id"
                                    class="flex items-center p-5 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl hover:shadow-md transition-all"
                                >
                                    <div class="h-12 w-12 rounded-full bg-slate-50 dark:bg-slate-900 flex items-center justify-center text-slate-400 font-bold border border-slate-100 dark:border-slate-700">
                                        {{ application.user?.name?.charAt(0) || '?' }}
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ application.user?.name || t('hr.dashboard.candidate') }}</h4>
                                        <p class="text-xs text-slate-500">
                                            {{ t('hr.dashboard.applied_for') }}
                                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ application.internship?.title || '-' }}</span>
                                        </p>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <span class="text-[10px] font-medium text-slate-400 flex items-center">
                                            <Clock class="w-3 h-3 mr-1" />
                                            {{ application.created_at_human || '-' }}
                                        </span>
                                        <Link
                                            :href="`/hr/applications/${application.id}`"
                                            class="px-4 py-2 bg-slate-50 dark:bg-slate-900 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-lg hover:bg-primary-600 hover:text-white transition-all"
                                        >
                                            {{ t('hr.dashboard.review') }}
                                        </Link>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="p-10 border-2 border-dashed border-slate-100 dark:border-slate-800 rounded-3xl text-center">
                                <CheckCircle2 class="w-10 h-10 text-slate-200 dark:text-slate-700 mx-auto mb-4" />
                                <p class="text-slate-500 text-sm font-medium">{{ t('hr.dashboard.all_reviewed') }}</p>
                            </div>
                        </section>

                        <section>
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ t('hr.dashboard.recent_activity') }}</h3>
                                <Link href="/hr/applications" class="text-xs font-bold text-primary-600 hover:underline">
                                    {{ t('hr.dashboard.see_all_applicants') }}
                                </Link>
                            </div>
                            <div class="bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl overflow-hidden shadow-sm">
                                <table class="w-full text-left">
                                    <thead class="bg-slate-50 dark:bg-slate-900/50">
                                        <tr>
                                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('hr.dashboard.col_candidate') }}</th>
                                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('hr.dashboard.col_position') }}</th>
                                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('hr.dashboard.col_status') }}</th>
                                            <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">{{ t('hr.dashboard.col_time') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
                                        <tr
                                            v-for="application in recentApplications"
                                            :key="application.id"
                                            class="group hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors cursor-pointer"
                                            @click="inertiaRouter.visit(`/hr/applications/${application.id}`)"
                                        >
                                            <td class="px-6 py-4 text-sm font-bold text-slate-900 dark:text-white">
                                                {{ application.user?.name || t('hr.dashboard.candidate') }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-slate-500">{{ application.internship?.title || '-' }}</td>
                                            <td class="px-6 py-4"><StatusBadge :status="application.status" /></td>
                                            <td class="px-6 py-4 text-right text-xs text-slate-400 font-medium">{{ application.created_at_human || '-' }}</td>
                                        </tr>
                                        <tr v-if="recentApplications.length === 0">
                                            <td colspan="4" class="px-6 py-12 text-center text-sm text-slate-500">
                                                {{ t('hr.dashboard.no_activity') }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>

                    <div class="lg:col-span-4 space-y-10">
                        <div class="p-8 bg-white dark:bg-slate-800 rounded-3xl text-slate-900 dark:text-white shadow-xl relative overflow-hidden group border border-slate-100 dark:border-slate-700">
                            <div class="relative z-10">
                                <h4 class="text-lg font-bold mb-2">{{ t('hr.dashboard.public_profile') }}</h4>
                                <p class="text-slate-500 dark:text-slate-400 text-xs leading-relaxed mb-8">{{ t('hr.dashboard.public_profile_desc') }}</p>
                                <Link
                                    :href="company.slug ? `/companies/${company.slug}` : '/companies'"
                                    class="inline-flex items-center text-sm font-bold text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition-colors group"
                                >
                                    {{ t('hr.dashboard.open_profile') }}
                                    <ArrowRight class="w-4 h-4 ml-2 group-hover:translate-x-1 transition-transform" />
                                </Link>
                            </div>
                            <div class="absolute top-[-20%] right-[-10%] w-40 h-40 bg-primary-500/5 dark:bg-white/5 rounded-full blur-3xl group-hover:scale-125 transition-transform duration-700"></div>
                        </div>

                        <section>
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6">{{ t('hr.dashboard.quick_navigation') }}</h4>
                            <div class="grid grid-cols-1 gap-3">
                                <Link
                                    v-for="link in quickLinks"
                                    :key="link.name"
                                    :href="link.href"
                                    class="flex items-center p-4 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl hover:border-primary-500 hover:ring-1 hover:ring-primary-500 transition-all group"
                                >
                                    <component :is="link.icon" class="w-5 h-5 text-slate-400 group-hover:text-primary-600 transition-colors" />
                                    <span class="ml-4 text-sm font-bold text-slate-700 dark:text-slate-300">{{ link.name }}</span>
                                </Link>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

