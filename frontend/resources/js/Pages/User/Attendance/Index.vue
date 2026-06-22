<script setup lang="ts">
import logger from '@/Lib/logger';
import { Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, reactive } from 'vue';
import api from '@/Services/api';
import { useAuthStore } from '@/Stores/auth';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Button from '@/Components/Button.vue';
import Card from '@/Components/Card.vue';
import Modal from '@/Components/Modal.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { format } from 'date-fns';
import { 
    Fingerprint, MapPin, LogOut, LogIn, Calendar, 
    Info, CheckCircle2, ArrowRight
} from 'lucide-vue-next';

const props = defineProps<{
    activeApplication: any;
    activeSession: any;
    history: any;
    error: string | null;
}>();

const authStore = useAuthStore();
const activeApplication = ref<any>(props.activeApplication);
const activeSession = ref<any>(props.activeSession);
const history = ref<{ data: any[] }>(props.history as { data: any[] } || { data: [] });
const error = ref<string | null>(props.error as string | null);

const reloadData = () => {
    router.reload({
        only: ['activeApplication', 'activeSession', 'history', 'error'],
        onSuccess: (page) => {
            activeApplication.value = page.props.activeApplication;
            activeSession.value = page.props.activeSession;
            history.value = (page.props.history as { data: any[] }) || { data: [] };
            error.value = page.props.error as string | null;
            
            if (activeSession.value) {
                startRealtimeTracking();
            } else {
                stopRealtimeTracking();
            }
        }
    });
};

const location = ref<{ lat: number | null, lng: number | null }>({ lat: null, lng: null });
const locationError = ref<string | null>(null);
const showConsentModal = ref(false);
const isTracking = ref(false);
let watchId: number | null = null;

const getGeolocation = (): Promise<GeolocationPosition> => {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject('Geolocation is not supported by your browser.');
        } else {
            navigator.geolocation.getCurrentPosition(resolve, reject);
        }
    });
};

const handleCheckIn = async () => {
    try {
        const position = await getGeolocation();
        location.value = {
            lat: position.coords.latitude,
            lng: position.coords.longitude
        };
        
        await api.post('/attendance/check-in', {
            application_id: activeApplication.value.id,
            latitude: location.value.lat,
            longitude: location.value.lng,
            consent: true
        });

        showConsentModal.value = false;
        reloadData(); 
    } catch (err: any) {
        locationError.value = err.response?.data?.message || "Gagal mengambil lokasi. Pastikan izin lokasi diberikan.";
    }
};

const handleCheckOut = async () => {
    try {
        const position = await getGeolocation();
        await api.post(`/attendance/check-out/${activeSession.value.id}`, {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude
        });

        stopRealtimeTracking();
        reloadData();
    } catch (err: any) {
        alert("Gagal melakukan check-out.");
    }
};

const startRealtimeTracking = () => {
    if (!navigator.geolocation || isTracking.value) return;
    isTracking.value = true;
    
    watchId = navigator.geolocation.watchPosition((position) => {
        api.post('/attendance/update-location', {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude
        }).catch(err => logger.error("Failed to update live location"));
    }, (err) => {
        logger.error("WatchPosition error", err);
    }, {
        enableHighAccuracy: true,
        maximumAge: 30000,
        timeout: 27000
    });
};

const stopRealtimeTracking = () => {
    if (watchId) {
        navigator.geolocation.clearWatch(watchId);
        watchId = null;
    }
    isTracking.value = false;
};

onMounted(() => {
    if (activeSession.value) {
        startRealtimeTracking();
    }
});

onUnmounted(() => {
    stopRealtimeTracking();
});
</script>

<template>
    <DashboardLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Presensi & Lokasi</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">Kelola kehadiran magang Anda dengan Geofencing.</p>
        </template>

        <div class="py-12 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div v-if="error" class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                <p class="text-sm text-red-700">{{ error }}</p>
            </div>

            <div v-else-if="activeApplication" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Current Session Card -->
                <div class="lg:col-span-1">
                    <Card class="p-6">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-12 h-12 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-primary-600">
                                <Fingerprint class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 dark:text-white">Status Sesi</h3>
                                <Badge :variant="activeSession ? 'success' : 'default'">
                                    {{ activeSession ? 'Sesi Aktif' : 'Belum Check-in' }}
                                </Badge>
                            </div>
                        </div>

                        <div v-if="activeSession" class="space-y-4">
                            <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-lg">
                                <p class="text-xs text-slate-500 mb-1">Check-in pada:</p>
                                <p class="font-mono font-bold text-slate-800 dark:text-white">
                                    {{ format(new Date(activeSession.check_in_at), 'HH:mm:ss') }}
                                </p>
                            </div>

                            <div v-if="isTracking" class="flex items-center gap-2 text-xs text-emerald-600 font-medium animate-pulse">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                Realtime Tracking Aktif
                            </div>

                            <Button variant="danger" class="w-full" @click="handleCheckOut">
                                <LogOut class="w-4 h-4 mr-2" /> Check-out Sekarang
                            </Button>
                        </div>

                        <div v-else class="space-y-4">
                            <p class="text-sm text-slate-600 dark:text-slate-400">
                                Pastikan Anda berada di area kantor untuk melakukan check-in.
                            </p>
                            <Button variant="primary" class="w-full" @click="showConsentModal = true">
                                <LogIn class="w-4 h-4 mr-2" /> Mulai Check-in
                            </Button>
                        </div>
                    </Card>

                    <!-- Company Info -->
                    <Card class="mt-6 p-6">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Lokasi Kantor</h4>
                        <p class="font-bold text-slate-800 dark:text-white">{{ activeApplication.internship.company.name }}</p>
                        <p class="text-sm text-slate-500 mt-1">{{ activeApplication.internship.company.location }}</p>
                        <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded border border-blue-100 dark:border-blue-800 text-xs text-blue-700 dark:text-blue-300 flex items-center gap-2">
                            <Info class="w-4 h-4 shrink-0" /> Radius Geofence: {{ activeApplication.internship.company.geofence_radius || 100 }}m
                        </div>
                    </Card>
                </div>

                <!-- History Table -->
                <div class="lg:col-span-2">
                    <Card class="overflow-hidden border-none shadow-2xl shadow-slate-200/50 dark:shadow-none">
                        <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-white dark:bg-neutral-900">
                            <div>
                                <h3 class="font-black text-xl text-slate-900 dark:text-white tracking-tight">Riwayat Kehadiran</h3>
                                <p class="text-xs text-slate-500 mt-1">Data absensi 30 hari terakhir.</p>
                            </div>
                            <Button variant="ghost" size="sm" class="font-bold">
                                <Calendar class="mr-2 w-4 h-4" /> Unduh Laporan (PDF)
                            </Button>
                        </div>
                        
                        <div v-if="history.data.length > 0" class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-800/50 text-[10px] text-slate-400 uppercase font-black tracking-[0.2em]">
                                        <th class="px-6 py-5">Tanggal</th>
                                        <th class="px-6 py-5">Check-in</th>
                                        <th class="px-6 py-5">Check-out</th>
                                        <th class="px-6 py-5">Status</th>
                                        <th class="px-6 py-5 text-right">Verifikasi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                                    <tr v-for="item in history.data" :key="item.id" class="text-sm hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors group">
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <div class="font-bold text-slate-900 dark:text-white">{{ format(new Date(item.created_at), 'dd MMM yyyy') }}</div>
                                            <div class="text-[10px] text-slate-400 uppercase font-bold">{{ format(new Date(item.created_at), 'EEEE') }}</div>
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap font-mono font-bold text-slate-600 dark:text-slate-400">
                                            {{ item.check_in_at ? format(new Date(item.check_in_at), 'HH:mm') : '--:--' }}
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap font-mono font-bold text-slate-600 dark:text-slate-400">
                                            {{ item.check_out_at ? format(new Date(item.check_out_at), 'HH:mm') : '--:--' }}
                                        </td>
                                        <td class="px-6 py-5">
                                            <Badge :variant="item.status === 'present' ? 'success' : 'warning'" class="rounded-lg px-3 py-1">
                                                {{ item.status.toUpperCase() }}
                                            </Badge>
                                        </td>
                                        <td class="px-6 py-5 text-right">
                                            <div class="flex items-center justify-end gap-2 text-[10px] font-black text-emerald-600 uppercase tracking-widest">
                                                <CheckCircle2 class="w-4 h-4" /> Verified
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <EmptyState 
                            v-else
                            title="Belum Ada Absensi"
                            description="Anda belum memiliki riwayat absensi untuk periode ini. Mulai magang dan lakukan check-in setiap hari."
                        />
                    </Card>
                </div>
            </div>
            
            <div v-else class="bg-white border border-slate-100 rounded-[3rem] p-20 text-center shadow-sm">
                <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-8">
                    <Info class="w-10 h-10 text-slate-200" />
                </div>
                <h3 class="text-2xl font-bold text-slate-900 mb-4">Tidak Ada Magang Aktif</h3>
                <p class="text-slate-500 max-w-md mx-auto mb-10">Anda tidak memiliki magang aktif yang memerlukan absensi saat ini.</p>
                <Link 
                    href="/internships" 
                    class="inline-flex items-center gap-2 bg-primary-600 text-white px-10 py-4 rounded-full font-bold text-sm hover:bg-primary-700 transition-all shadow-xl shadow-primary-100"
                >
                    Cari Peluang Magang
                    <ArrowRight class="w-4 h-4" />
                </Link>
            </div>
        </div>

        <!-- Consent Modal -->
        <Modal :show="showConsentModal" @close="showConsentModal = false" max-width="md">
            <div class="p-6">
                <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center text-blue-600 mx-auto mb-4">
                    <i class="ph-map-pin-line text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-center text-slate-800 dark:text-white mb-2">Izin Akses Lokasi</h3>
                <p class="text-center text-slate-600 dark:text-slate-400 text-sm mb-6">
                    Aplikasi memerlukan akses lokasi Anda untuk memverifikasi kehadiran di area kantor. Lokasi Anda hanya akan dilacak selama sesi absensi aktif.
                </p>
                
                <div class="space-y-3 bg-slate-50 dark:bg-slate-800 p-4 rounded-lg mb-6">
                    <div class="flex gap-3 text-xs text-slate-600 dark:text-slate-400">
                        <i class="ph-check-circle text-emerald-500 shrink-0"></i>
                        <span>Geofencing verifikasi radius kantor.</span>
                    </div>
                    <div class="flex gap-3 text-xs text-slate-600 dark:text-slate-400">
                        <i class="ph-check-circle text-emerald-500 shrink-0"></i>
                        <span>Pelacakan otomatis berhenti saat check-out.</span>
                    </div>
                    <div class="flex gap-3 text-xs text-slate-600 dark:text-slate-400">
                        <i class="ph-check-circle text-emerald-500 shrink-0"></i>
                        <span>Audit log untuk setiap akses lokasi sensitif.</span>
                    </div>
                </div>

                <div v-if="locationError" class="text-xs text-red-600 text-center mb-4">
                    {{ locationError }}
                </div>

                <div class="flex gap-3">
                    <Button variant="ghost" class="flex-1" @click="showConsentModal = false">Batal</Button>
                    <Button variant="primary" class="flex-1" @click="handleCheckIn">Setujui & Check-in</Button>
                </div>
            </div>
        </Modal>
    </DashboardLayout>
</template>

