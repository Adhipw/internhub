<script setup lang="ts">
import { ref, computed } from 'vue';
import { router as inertiaRouter } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { 
    Search, MapPin, Briefcase, Building2, Filter, 
    ChevronRight, ArrowRight, X, Clock, DollarSign,
    Navigation, Loader2
} from 'lucide-vue-next';
import { useLangStore } from '@/Stores/lang';
import Card from '@/Components/Card.vue';
import Button from '@/Components/Button.vue';
import Input from '@/Components/Input.vue';
import Badge from '@/Components/Badge.vue';
import SectionHeader from '@/Components/SectionHeader.vue';
import type { Internship } from '@/Types/internship';
import type { PaginationMeta } from '@/Types/user';

interface InternshipsIndexProps {
    internships?: {
        data: Internship[];
        links: any[];
        meta: PaginationMeta;
    };
    filters?: Record<string, string>;
}

const props = defineProps<InternshipsIndexProps>();

const langStore = useLangStore();
const t = (key: string) => langStore.t(key);
const urlParams = new URLSearchParams(window.location.search);

const internships = computed(() => props.internships?.data || []);
const meta = computed<PaginationMeta>(() => props.internships?.meta || ({
    current_page: 1,
    from: 0,
    last_page: 1,
    links: [],
    path: '',
    per_page: 10,
    to: 0,
    total: 0,
} as PaginationMeta));
const loading = ref(false);

const filters = ref({
    q: String(urlParams.get('q') || ''),
    location: String(urlParams.get('location') || ''),
    type: String(urlParams.get('type') || ''),
    is_paid: String(urlParams.get('is_paid') || ''),
    sort: String(urlParams.get('sort') || 'latest'),
    lat: urlParams.get('lat') || '',
    lng: urlParams.get('lng') || '',
    radius: urlParams.get('radius') || '5',
});

const isLocating = ref(false);
const locationError = ref('');

const getUserLocation = () => {
    isLocating.value = true;
    locationError.value = '';
    
    if (!navigator.geolocation) {
        locationError.value = 'Browser Anda tidak mendukung deteksi lokasi.';
        isLocating.value = false;
        return;
    }

    navigator.geolocation.getCurrentPosition(
        (position) => {
            filters.value.lat = position.coords.latitude.toString();
            filters.value.lng = position.coords.longitude.toString();
            if (!filters.value.radius) filters.value.radius = '5';
            isLocating.value = false;
            applyFilters();
        },
        (error) => {
            console.error('Error getting location:', error);
            locationError.value = 'Gagal mendeteksi lokasi. Pastikan izin GPS aktif.';
            isLocating.value = false;
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
};

const types = computed(() => [
    { label: t('filters.type_all'), value: '' },
    { label: t('filters.type_full'), value: 'full-time' },
    { label: t('filters.type_part'), value: 'part-time' },
    { label: t('filters.type_remote'), value: 'remote' },
]);

const applyFilters = () => {
    loading.value = true;
    inertiaRouter.get('/internships', { ...filters.value }, {
        preserveState: true,
        preserveScroll: true,
        only: ['internships', 'filters'],
        onFinish: () => {
            loading.value = false;
        },
        onError: () => {
            loading.value = false;
        },
    });
};

const clearFilters = () => {
    filters.value = { q: '', location: '', type: '', is_paid: '', sort: 'latest', lat: '', lng: '', radius: '5' };
    applyFilters();
};

if (props.filters) {
    filters.value = { ...filters.value, ...props.filters };
}
</script>

<template>
    <PublicLayout>
        <div class="bg-neutral-50 dark:bg-neutral-950 min-h-screen pt-32 pb-20">
            <div class="container mx-auto px-6">
                <!-- Header -->
                <SectionHeader 
                    :title="t('nav.internships')" 
                    :subtitle="t('jobs.subtitle')"
                />

                <div class="flex flex-col lg:flex-row gap-12">
                    <!-- Filters Sidebar -->
                    <aside class="w-full lg:w-80 shrink-0">
                        <div class="bg-white dark:bg-neutral-900 rounded-2xl p-8 border border-neutral-100 dark:border-neutral-800 sticky top-32">
                            <!-- Map Button -->
                            <Button class="w-full mb-8 !bg-emerald-500 hover:!bg-emerald-600 !text-white flex items-center justify-center gap-2 shadow-xl shadow-emerald-500/20 active:scale-95 transition-all" @click="inertiaRouter.visit('/internships/map')">
                                <MapPin class="w-5 h-5" /> Cari Lewat Peta
                            </Button>

                            <div class="flex items-center justify-between mb-8">
                                <h3 class="text-lg font-semibold text-xs tracking-wide text-neutral-900 dark:text-white">{{ t('filters.title') }}</h3>
                                <button class="text-xs font-bold text-primary-600 hover:text-primary-700 transition-colors" @click="clearFilters">{{ t('filters.reset') }}</button>
                            </div>

                            <div class="space-y-8">
                                <div>
                                    <label class="block text-[10px] font-semibold text-xs tracking-wide text-neutral-400 mb-4">{{ t('filters.search_label') }}</label>
                                    <Input v-model="filters.q" :placeholder="t('filters.search_placeholder')" @keyup.enter="applyFilters" />
                                </div>

                                <div>
                                    <label class="block text-[10px] font-semibold text-xs tracking-wide text-neutral-400 mb-4">{{ t('filters.location_label') }}</label>
                                    <Input v-model="filters.location" :placeholder="t('filters.location_placeholder')" @keyup.enter="applyFilters" />
                                </div>

                                <div class="p-5 bg-neutral-50 dark:bg-neutral-950/50 rounded-2xl border border-neutral-100 dark:border-neutral-800">
                                    <div class="flex items-center justify-between mb-4">
                                        <label class="block text-[10px] font-semibold text-xs tracking-wide text-primary-600">Radius Kampus/Kos</label>
                                        <span v-if="filters.lat" class="text-xs font-bold text-emerald-500 flex items-center gap-1">
                                            <div class="w-1.5 h-1.5 bg-emerald-500 rounded-full "></div> Aktif
                                        </span>
                                    </div>
                                    <p class="text-[11px] font-medium text-neutral-500 dark:text-neutral-400 mb-4 leading-relaxed">Cari lowongan terdekat dari lokasi fisik Anda (Radius: {{ filters.radius }} KM)</p>
                                    
                                    <div v-if="filters.lat" class="space-y-4">
                                        <input 
                                            type="range" 
                                            v-model="filters.radius" 
                                            min="1" 
                                            max="50" 
                                            class="w-full accent-primary-600"
                                            @change="applyFilters"
                                        />
                                        <Button variant="outline" class="w-full text-xs" size="sm" @click="() => { filters.lat = ''; filters.lng = ''; applyFilters(); }">
                                            Hapus Lokasi
                                        </Button>
                                    </div>
                                    <div v-else>
                                        <Button class="w-full text-xs flex items-center justify-center gap-2" variant="secondary" size="sm" @click="getUserLocation" :disabled="isLocating">
                                            <Loader2 v-if="isLocating" class="w-4 h-4 animate-spin" />
                                            <Navigation v-else class="w-4 h-4" />
                                            Gunakan Lokasi Saya
                                        </Button>
                                        <p v-if="locationError" class="text-[10px] text-rose-500 mt-2 font-medium">{{ locationError }}</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-semibold text-xs tracking-wide text-neutral-400 mb-4">{{ t('filters.type_label') }}</label>
                                    <div class="space-y-3">
                                        <label v-for="t in types" :key="t.value" class="flex items-center gap-3 cursor-pointer group">
                                            <input 
                                                v-model="filters.type" 
                                                type="radio" 
                                                :value="t.value"
                                                class="w-5 h-5 border-neutral-200 text-primary-600 focus:ring-primary-500 rounded-lg"
                                                @change="applyFilters"
                                            />
                                            <span class="text-sm font-bold text-neutral-600 dark:text-neutral-400 group-hover:text-neutral-900 dark:group-hover:text-white transition-colors">{{ t.label }}</span>
                                        </label>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-semibold text-xs tracking-wide text-neutral-400 mb-4">{{ t('filters.sort_label') }}</label>
                                    <select 
                                        v-model="filters.sort" 
                                        class="w-full px-5 py-4 bg-neutral-50 dark:bg-neutral-950 border-none rounded-2xl text-sm font-bold text-neutral-900 dark:text-white focus:ring-4 focus:ring-primary-500/10 transition-all"
                                        @change="applyFilters"
                                    >
                                        <option value="latest">{{ t('filters.sort_latest') }}</option>
                                        <option value="oldest">{{ t('filters.sort_oldest') }}</option>
                                        <option value="deadline">{{ t('filters.sort_deadline') }}</option>
                                    </select>
                                </div>

                                <Button class="w-full" @click="applyFilters">{{ t('filters.btn_apply') }}</Button>
                            </div>
                        </div>
                    </aside>

                    <!-- Internship Cards -->
                    <div class="flex-1 space-y-8">
                        <!-- Loading State -->
                        <div v-if="loading" class="space-y-6">
                            <div v-for="i in 3" :key="i" class="h-48 bg-white dark:bg-neutral-900 rounded-2xl  border border-neutral-100 dark:border-neutral-800"></div>
                        </div>

                        <!-- Results -->
                        <div v-else-if="internships.length > 0" class="space-y-6">
                            <Card 
                                v-for="internship in internships" 
                                :key="internship.id"
                                hoverable
                                padding="none"
                                class="group overflow-hidden"
                                @click="inertiaRouter.visit('/internships/' + internship.slug)"
                            >
                                <div class="p-6 md:p-8 flex flex-col md:flex-row md:items-center gap-6 lg:gap-10 transition-colors group-hover:bg-neutral-50/50 dark:group-hover:bg-neutral-800/30">
                                    <!-- Logo -->
                                    <div class="w-16 h-16 md:w-20 md:h-20 bg-neutral-50 dark:bg-neutral-950 rounded-2xl flex items-center justify-center border border-neutral-100 dark:border-neutral-800 shrink-0 group-hover:scale-105 transition-transform duration-500 shadow-sm relative overflow-hidden">
                                        <div class="absolute inset-0 bg-gradient-to-br from-primary-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                        <img v-if="internship.company?.logo_url" loading="lazy" decoding="async" :src="internship.company.logo_url" class="w-full h-full object-contain p-3 relative z-10" />
                                        <Building2 v-else class="w-8 h-8 md:w-10 md:h-10 text-neutral-300 relative z-10" />
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-1">
                                        <div class="flex flex-wrap items-center gap-2 mb-3">
                                            <Badge variant="primary" size="sm" class="text-[9px] px-2 py-0.5">{{ internship.type }}</Badge>
                                            <span v-if="internship.is_paid" class="text-[9px] font-semibold text-xs tracking-wide text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20 px-2 py-0.5 rounded-full border border-emerald-100 dark:border-emerald-800/50">{{ t('job.paid') }}</span>
                                        </div>
                                        <h3 class="text-2xl font-bold text-neutral-900 dark:text-white mb-2 group-hover:text-primary-600 transition-colors leading-tight">{{ internship.title }}</h3>
                                        <div class="flex flex-wrap items-center gap-6 text-sm font-bold text-neutral-400">
                                            <div class="flex items-center gap-2">
                                                <Building2 class="w-4 h-4" />
                                                {{ internship.company?.name }}
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <MapPin class="w-4 h-4" />
                                                {{ internship.location }}
                                            </div>
                                            <div v-if="internship.distance !== undefined" class="flex items-center gap-2 text-primary-600 bg-primary-50 dark:bg-primary-900/20 px-2 py-1 rounded-lg border border-primary-100 dark:border-primary-800/30">
                                                <Navigation class="w-3.5 h-3.5" />
                                                <span class="text-xs">{{ Number(internship.distance).toFixed(1) }} km</span>
                                            </div>
                                            <div class="flex items-center gap-2 text-rose-500 bg-rose-50 dark:bg-rose-900/10 px-3 py-1 rounded-lg border border-rose-100 dark:border-rose-900/20">
                                                <Clock class="w-3.5 h-3.5" />
                                                <span class="text-[10px] uppercase tracking-wider">{{ t('job.deadline_label') }}: {{ internship.deadline_at_human || 'Segera' }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action -->
                                    <div class="shrink-0 flex items-center justify-between md:justify-end gap-4 lg:gap-10 pt-4 md:pt-0 border-t md:border-t-0 border-neutral-50 dark:border-neutral-800">
                                        <div class="text-right flex flex-col items-end">
                                            <p class="text-[9px] font-bold text-neutral-400 uppercase tracking-widest mb-1">{{ t('job.stipend') }}</p>
                                            <p class="text-base md:text-xl font-bold text-neutral-900 dark:text-white leading-none">{{ internship.stipend || t('job.stipend_default') }}</p>
                                        </div>
                                        <div class="w-14 h-14 bg-neutral-900 dark:bg-white text-white dark:text-neutral-900 rounded-full flex items-center justify-center group-hover:bg-primary-600 group-hover:text-white group-hover:translate-x-3 transition-all shadow-xl">
                                            <ArrowRight class="w-7 h-7" />
                                        </div>
                                    </div>
                                </div>
                            </Card>

                            <!-- Pagination -->
                            <div v-if="meta.last_page > 1" class="pt-12 flex justify-center gap-4">
                                <Button 
                                    v-for="page in meta.last_page" 
                                    :key="page"
                                    :variant="meta.current_page === page ? 'primary' : 'outline'"
                                    size="sm"
                                    class="w-12 h-12 !p-0 flex items-center justify-center"
                                    @click="inertiaRouter.get('/internships', { ...filters, page }, { preserveState: true, preserveScroll: true, only: ['internships', 'filters'] })"
                                >
                                    {{ page }}
                                </Button>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="py-32 text-center bg-white dark:bg-neutral-900 rounded-2xl border-2 border-dashed border-neutral-100 dark:border-neutral-800">
                            <div class="w-24 h-24 bg-neutral-50 dark:bg-neutral-950 rounded-full flex items-center justify-center mx-auto mb-8">
                                <Search class="w-10 h-10 text-neutral-300" />
                            </div>
                            <h3 class="text-2xl font-bold text-neutral-900 dark:text-white mb-4">{{ t('jobs.empty') }}</h3>
                            <p class="text-neutral-500 font-medium max-w-md mx-auto mb-10">{{ t('common.error_occurred') }}</p>
                            <Button variant="outline" @click="clearFilters">{{ t('filters.reset') }}</Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
