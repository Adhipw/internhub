<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { 
    Briefcase, Calendar, MapPin, ChevronRight, 
    Search, Filter, ExternalLink, Clock, LayoutGrid, List
} from 'lucide-vue-next';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { Application } from '@/Types/application';
import type { PaginatedResponse } from '@/Types/user';

interface ApplicationsIndexProps {
    applications?: PaginatedResponse<Application>;
}

const props = defineProps<ApplicationsIndexProps>();

const applications = computed<PaginatedResponse<Application>>(() => props.applications || {
    data: [],
    links: [],
    meta: {
        current_page: 1,
        last_page: 1,
        total: 0,
    } as any,
});

const normalizedApplications = computed(() => {
    const raw = applications.value as any;

    return {
        data: raw.data || [],
        links: raw.links || [],
        meta: raw.meta || {
            current_page: raw.current_page || 1,
            last_page: raw.last_page || 1,
            total: raw.total || raw.data?.length || 0,
        },
    };
});

const loading = computed(() => !props.applications);

const viewMode = ref<'list' | 'board'>('board');

const columns = [
    { id: 'pending', label: 'Terkirim', color: 'bg-slate-100 text-slate-700', border: 'border-slate-200' },
    { id: 'reviewed', label: 'Direview HRD', color: 'bg-primary-100 text-primary-700', border: 'border-primary-200' },
    { id: 'interview', label: 'Wawancara', color: 'bg-purple-100 text-purple-700', border: 'border-purple-200' },
    { id: 'accepted', label: 'Diterima', color: 'bg-emerald-100 text-emerald-700', border: 'border-emerald-200' },
    { id: 'rejected_withdrawn', label: 'Ditolak/Ditarik', color: 'bg-rose-100 text-rose-700', border: 'border-rose-200' },
];

const getAppsByStatus = (statusGroup: string) => {
    if (statusGroup === 'rejected_withdrawn') {
        return (normalizedApplications.value.data as Application[]).filter((app: Application) => app.status === 'rejected' || app.status === 'withdrawn');
    }
    return (normalizedApplications.value.data as Application[]).filter((app: Application) => app.status === statusGroup);
};
</script>

<template>
    <DashboardLayout>
        <div class="max-w-5xl mx-auto space-y-10">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 mb-2">Lamaran Saya</h1>
                    <p class="text-slate-500">Pantau status lamaran magang yang telah kamu kirim.</p>
                </div>
            </div>

            <!-- List -->
            <div v-if="loading" class="flex justify-center py-20">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
            </div>

            <div v-else-if="normalizedApplications.data.length > 0" class="space-y-6">
                <div class="flex items-center justify-between px-4">
                    <p class="text-sm font-bold text-slate-500 uppercase tracking-widest">Total {{ normalizedApplications.meta.total }} Lamaran</p>
                    <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl">
                        <button 
                            @click="viewMode = 'list'"
                            :class="['p-2 rounded-lg flex items-center justify-center transition-all', viewMode === 'list' ? 'bg-white text-primary-600 shadow-sm' : 'text-slate-400 hover:text-slate-600']"
                            title="List View"
                        >
                            <List class="w-5 h-5" />
                        </button>
                        <button 
                            @click="viewMode = 'board'"
                            :class="['p-2 rounded-lg flex items-center justify-center transition-all', viewMode === 'board' ? 'bg-white text-primary-600 shadow-sm' : 'text-slate-400 hover:text-slate-600']"
                            title="Kanban Board View"
                        >
                            <LayoutGrid class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <!-- List View -->
                <div v-if="viewMode === 'list'" class="space-y-4">
                    <div v-for="(app, idx) in normalizedApplications.data" :key="app.id" 
                        class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm hover:border-primary-300 transition-all group relative overflow-hidden animate-reveal opacity-0"
                        :style="`animation-delay: ${100 + (Number(idx) * 50)}ms`">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-slate-50 rounded-full blur-2xl opacity-50 -mr-16 -mt-16 opacity-50 group-hover:bg-primary-50 transition-colors"></div>
                        
                        <div class="relative z-10 flex flex-col md:flex-row md:items-center gap-6">
                            <!-- Company Logo -->
                            <div class="w-16 h-16 bg-slate-50 rounded-xl flex items-center justify-center shrink-0 border border-slate-100 overflow-hidden shadow-inner">
                                <img v-if="app.internship.company.logo_url" loading="lazy" decoding="async" :src="app.internship.company.logo_url" class="w-full h-full object-cover" />
                                <Briefcase v-else class="w-8 h-8 text-slate-300" />
                            </div>

                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-3 mb-2">
                                    <h2 class="text-xl font-bold text-slate-900">{{ app.internship.title }}</h2>
                                    <StatusBadge :status="app.status" />
                                </div>
                                <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm font-semibold text-slate-500">
                                    <span class="text-primary-600">{{ app.internship.company.name }}</span>
                                    <span class="flex items-center gap-1.5">
                                        <MapPin class="w-4 h-4" />
                                        {{ app.internship.location }}
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <Clock class="w-4 h-4" />
                                        Dikirim {{ new Date(app.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-6 border-t border-slate-100 pt-6 w-full">
                                <div class="relative">
                                    <div class="overflow-hidden h-2 mb-4 text-xs flex rounded-full bg-slate-100">
                                        <div :style="{ width: app.status === 'withdrawn' ? '100%' : (app.status === 'rejected' ? '100%' : (app.status === 'accepted' ? '100%' : (app.status === 'interview' ? '66%' : (app.status === 'reviewed' ? '33%' : '10%')))) }" 
                                             class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center transition-all duration-500"
                                             :class="app.status === 'withdrawn' ? 'bg-slate-400' : (app.status === 'rejected' ? 'bg-rose-500' : (app.status === 'accepted' ? 'bg-emerald-500' : 'bg-primary-500'))">
                                        </div>
                                    </div>
                                    <div class="flex justify-between text-xs font-bold text-slate-400">
                                        <span :class="{'text-primary-600': app.status !== 'withdrawn' && app.status !== 'rejected'}">Terkirim</span>
                                        <span :class="{'text-primary-600': ['reviewed', 'interview', 'accepted'].includes(app.status)}">Direview HRD</span>
                                        <span :class="{'text-primary-600': ['interview', 'accepted'].includes(app.status)}">Wawancara</span>
                                        <span :class="{'text-emerald-600': app.status === 'accepted', 'text-rose-600': app.status === 'rejected', 'text-slate-600': app.status === 'withdrawn'}">{{ app.status === 'rejected' ? 'Ditolak' : (app.status === 'withdrawn' ? 'Ditarik' : 'Diterima') }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end mt-6 w-full">
                                <Link 
                                    :href="'/my-applications/' + app.id"
                                    class="bg-slate-900 text-white px-6 py-2.5 rounded-lg font-medium text-sm hover:bg-slate-800 transition-all flex items-center gap-2 shadow-sm"
                                >
                                    Lihat Detail
                                    <ChevronRight class="w-4 h-4" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kanban Board View -->
                <div v-if="viewMode === 'board'" class="flex gap-4 overflow-x-auto pb-4 snap-x snap-mandatory animate-reveal delay-100 opacity-0">
                    <div 
                        v-for="col in columns" 
                        :key="col.id" 
                        class="shrink-0 w-80 bg-slate-50 rounded-2xl border border-slate-200/60 p-4 flex flex-col h-[70vh] snap-start"
                    >
                        <div class="flex items-center justify-between mb-4 px-1">
                            <h3 class="font-semibold text-slate-800 text-sm tracking-tight">{{ col.label }}</h3>
                            <span :class="['text-[11px] font-semibold px-2 py-0.5 rounded-md', col.color]">
                                {{ getAppsByStatus(col.id).length }}
                            </span>
                        </div>
                        
                        <div class="flex-1 overflow-y-auto space-y-3 pr-1 custom-scrollbar">
                            <div 
                                v-for="(app, idx) in getAppsByStatus(col.id)" 
                                :key="app.id"
                                class="bg-white p-4 rounded-xl border border-slate-200/60 shadow-sm hover:border-primary-300 hover:shadow transition-all cursor-pointer group animate-reveal opacity-0 hover:-translate-y-1"
                                :style="`animation-delay: ${150 + (Number(idx) * 50)}ms`"
                                @click="$inertia.visit('/my-applications/' + app.id)"
                            >
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-8 h-8 bg-slate-50 rounded-lg flex items-center justify-center border border-slate-100 shrink-0 overflow-hidden">
                                        <img v-if="app.internship.company.logo_url" :src="app.internship.company.logo_url" class="w-full h-full object-cover" />
                                        <Briefcase v-else class="w-4 h-4 text-slate-300" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold text-primary-600 truncate">{{ app.internship.company.name }}</p>
                                        <p class="text-xs text-slate-500 truncate flex items-center gap-1 mt-0.5">
                                            <MapPin class="w-3 h-3" /> {{ app.internship.location }}
                                        </p>
                                    </div>
                                </div>
                                <h4 class="font-semibold text-slate-900 text-sm leading-snug mb-3 group-hover:text-primary-600 transition-colors">{{ app.internship.title }}</h4>
                                
                                <div class="flex items-center justify-between pt-3 border-t border-slate-50">
                                    <span class="text-[11px] font-medium text-slate-500 flex items-center gap-1">
                                        <Clock class="w-3 h-3" /> {{ new Date(app.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}
                                    </span>
                                    <span :class="['w-2 h-2 rounded-full', col.color.split(' ')[0].replace('100', '500')]"></span>
                                </div>
                            </div>

                            <!-- Empty Column State -->
                            <div v-if="getAppsByStatus(col.id).length === 0" class="h-24 border border-dashed border-slate-300 rounded-xl flex flex-col items-center justify-center text-slate-400">
                                <p class="text-xs font-medium">Kosong</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination (Mock for now or simple) -->
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white border border-slate-200/60 rounded-2xl p-16 text-center shadow-sm">
                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <Search class="w-8 h-8 text-slate-300" />
                </div>
                <h3 class="text-xl font-semibold text-slate-900 mb-3">Belum ada lamaran terkirim</h3>
                <p class="text-slate-500 max-w-md mx-auto mb-8 text-sm">Jelajahi berbagai peluang magang menarik dan mulai kirimkan lamaran pertamamu hari ini!</p>
                <Link 
                    href="/internships" 
                    class="inline-flex items-center gap-2 bg-primary-600 text-white px-6 py-2.5 rounded-lg font-medium text-sm hover:bg-primary-700 transition-all shadow-sm"
                >
                    Cari Peluang Magang
                    <ExternalLink class="w-4 h-4" />
                </Link>
            </div>
        </div>
    </DashboardLayout>
</template>

