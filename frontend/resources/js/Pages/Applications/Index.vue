<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { 
    Briefcase, Calendar, MapPin, ChevronRight, 
    Search, Filter, ExternalLink, Clock
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
                </div>

                <div class="space-y-4">
                    <div v-for="app in normalizedApplications.data" :key="app.id" class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm hover:border-primary-200 transition-all group relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-slate-50 rounded-full blur-3xl -mr-16 -mt-16 opacity-50 group-hover:bg-primary-50 transition-colors"></div>
                        
                        <div class="relative z-10 flex flex-col md:flex-row md:items-center gap-8">
                            <!-- Company Logo -->
                            <div class="w-20 h-20 bg-slate-50 rounded-3xl flex items-center justify-center shrink-0 border border-slate-50 overflow-hidden shadow-inner">
                                <img v-if="app.internship.company.logo_url" loading="lazy" decoding="async" :src="app.internship.company.logo_url" class="w-full h-full object-cover" />
                                <Briefcase v-else class="w-10 h-10 text-slate-200" />
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
                                    class="bg-slate-900 text-white px-8 py-3.5 rounded-2xl font-bold text-sm hover:bg-slate-800 transition-all flex items-center gap-2 shadow-lg shadow-slate-200"
                                >
                                    Lihat Detail & Timeline
                                    <ChevronRight class="w-4 h-4" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination (Mock for now or simple) -->
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white border border-slate-100 rounded-[3rem] p-20 text-center shadow-sm">
                <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-8">
                    <Search class="w-10 h-10 text-slate-200" />
                </div>
                <h3 class="text-2xl font-bold text-slate-900 mb-4">Belum ada lamaran terkirim</h3>
                <p class="text-slate-500 max-w-md mx-auto mb-10">Jelajahi berbagai peluang magang menarik dan mulai kirimkan lamaran pertamamu hari ini!</p>
                <Link 
                    href="/internships" 
                    class="inline-flex items-center gap-2 bg-primary-600 text-white px-10 py-4 rounded-full font-bold text-sm hover:bg-primary-700 transition-all shadow-xl shadow-primary-100"
                >
                    Cari Peluang Magang
                    <ExternalLink class="w-4 h-4" />
                </Link>
            </div>
        </div>
    </DashboardLayout>
</template>

