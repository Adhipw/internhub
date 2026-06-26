<script setup lang="ts">
import logger from '@/Lib/logger';
import { ref, onMounted, watch, computed } from 'vue';
import { router as inertiaRouter } from '@inertiajs/vue3';
import api from '@/Services/api';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import Badge from '@/Components/Badge.vue';
import Button from '@/Components/Button.vue';
import Input from '@/Components/Input.vue';
import Pagination from '@/Components/Pagination.vue';
import { format } from 'date-fns';
import { 
    Users, MapPin, Search, Calendar, 
    Download, Activity, ExternalLink
} from 'lucide-vue-next';
import { useLangStore } from '@/Stores/lang';
import type { Attendance, LiveLocation } from '@/Types/attendance';
import type { PaginationLink } from '@/Types/user';

const props = defineProps<{
    attendances?: { data: Attendance[], links: PaginationLink[] };
    liveLocations?: Record<number, LiveLocation>;
    stats?: { total_present: number, currently_active: number };
    filters?: any;
}>();

const langStore = useLangStore();
const t = (key: string) => langStore.t(key);

const attendances = computed(() => props.attendances || { data: [], links: [] });
const liveLocations = computed(() => props.liveLocations || {});
const stats = computed(() => props.stats || { total_present: 0, currently_active: 0 });
const loading = ref(false);

const filters = ref({
    date: props.filters?.date || format(new Date(), 'yyyy-MM-dd'),
    search: props.filters?.search || ''
});

const fetchData = (page = 1) => {
    inertiaRouter.get('/hr/attendance', { ...filters.value, page }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

watch(() => filters.value.date, () => fetchData());
const handleSearch = () => fetchData();

const getLiveLoc = (userId: number) => liveLocations.value[userId] || null;
</script>

<template>
    <DashboardLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Monitoring Absensi</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Pantau kehadiran dan lokasi realtime peserta magang.</p>
                </div>
                <div class="flex items-center gap-3">
                    <input 
                        v-model="filters.date" 
                        type="date"
                        class="rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm focus:ring-primary-500 transition-all"
                    />
                    <Button variant="ghost" class="border border-slate-200 dark:border-slate-700">
                        <Download class="w-4 h-4 mr-2" /> Export
                    </Button>
                </div>
            </div>
        </template>

        <div class="py-6 space-y-8">
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Card class="p-6 flex items-center gap-4 bg-gradient-to-br from-emerald-50 to-white dark:from-emerald-900/10 dark:to-slate-900 border-emerald-100 dark:border-emerald-900/20">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500 text-white flex items-center justify-center shadow-lg shadow-emerald-200 dark:shadow-none">
                        <Users class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Hadir Hari Ini</p>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white leading-none mt-1">{{ stats.total_present }}</p>
                    </div>
                </Card>

                <Card class="p-6 flex items-center gap-4 bg-gradient-to-br from-blue-50 to-white dark:from-blue-900/10 dark:to-slate-900 border-blue-100 dark:border-blue-900/20">
                    <div class="w-12 h-12 rounded-2xl bg-blue-500 text-white flex items-center justify-center shadow-lg shadow-blue-200 dark:shadow-none">
                        <Activity class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-widest">Sesi Aktif (Live)</p>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white leading-none mt-1">{{ stats.currently_active }}</p>
                    </div>
                </Card>
            </div>

            <!-- Main Log -->
            <Card class="overflow-hidden border-none shadow-xl shadow-slate-200/50 dark:shadow-none">
                <div class="p-6 border-b border-slate-50 dark:border-slate-800 bg-white dark:bg-slate-900 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <h3 class="font-bold text-slate-800 dark:text-white flex items-center gap-2">
                        <Calendar class="w-5 h-5 text-primary-500" />
                        Log Kehadiran: {{ format(new Date(filters.date), 'dd MMMM yyyy') }}
                    </h3>
                    <div class="relative w-full md:w-64">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <input 
                            v-model="filters.search"
                            type="text"
                            placeholder="Cari nama peserta..." 
                            class="w-full pl-10 pr-4 py-2 rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm focus:ring-primary-500 transition-all"
                            @keyup.enter="handleSearch"
                        />
                    </div>
                </div>

                <div v-if="loading" class="flex justify-center py-20">
                    <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary-600"></div>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-800/50 text-[10px] text-slate-400 uppercase font-bold tracking-widest">
                                <th class="px-6 py-4">Peserta Magang</th>
                                <th class="px-6 py-4">Sesi Waktu</th>
                                <th class="px-6 py-4 text-center">Status Lokasi</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                            <tr v-for="item in attendances.data" :key="item.id" class="text-sm hover:bg-slate-50/30 dark:hover:bg-slate-800/30 transition-colors group">
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center font-bold text-primary-600 text-sm">
                                            {{ item.user.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 dark:text-white">{{ item.user.name }}</p>
                                            <p class="text-[10px] text-slate-500 font-medium uppercase tracking-tight">{{ item.application.internship.title }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-4 text-xs font-mono">
                                        <div class="flex flex-col">
                                            <span class="text-[9px] text-slate-400 font-sans uppercase font-bold">In</span>
                                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ format(new Date(item.check_in_at), 'HH:mm:ss') }}</span>
                                        </div>
                                        <div class="text-slate-200">|</div>
                                        <div class="flex flex-col">
                                            <span class="text-[9px] text-slate-400 font-sans uppercase font-bold">Out</span>
                                            <span :class="item.check_out_at ? 'font-bold text-slate-700 dark:text-slate-300' : 'text-slate-400 italic'">
                                                {{ item.check_out_at ? format(new Date(item.check_out_at), 'HH:mm:ss') : 'Active' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5 text-center">
                                    <div v-if="getLiveLoc(item.user_id)" class="inline-flex items-center gap-2 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 px-3 py-1.5 rounded-full border border-emerald-100 dark:border-emerald-800/50">
                                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></div>
                                        <span class="text-[10px] font-semibold text-xs tracking-wide">Live Now</span>
                                    </div>
                                    <span v-else class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Offline</span>
                                </td>
                                <td class="px-6 py-5">
                                    <Badge :variant="item.status === 'present' ? 'success' : 'warning'" class="px-3 py-1 rounded-lg">
                                        {{ item.status.toUpperCase() }}
                                    </Badge>
                                </td>
                                <td class="px-6 py-5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a 
                                            v-if="getLiveLoc(item.user_id)"
                                            :href="`https://www.google.com/maps?q=${getLiveLoc(item.user_id).lat},${getLiveLoc(item.user_id).lng}`" 
                                            target="_blank"
                                            class="p-2 text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition-all"
                                            title="Buka di Google Maps"
                                        >
                                            <MapPin class="w-4 h-4" />
                                        </a>
                                        <Button variant="ghost" size="sm" class="text-xs">Detail</Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="attendances.data.length === 0">
                                <td colspan="5" class="px-6 py-20 text-center">
                                    <p class="text-slate-500 italic">Tidak ada data absensi untuk kriteria ini.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="attendances.links && attendances.links.length > 3" class="px-6 py-4 border-t border-slate-50 dark:border-slate-800">
                    <Pagination :links="attendances.links" />
                </div>
            </Card>
        </div>
    </DashboardLayout>
</template>
