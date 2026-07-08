<script setup lang="ts">
import logger from '@/Lib/logger';
import { Link, router as inertiaRouter } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { ref, onMounted, computed, onUnmounted } from 'vue';
import { useLangStore } from '@/Stores/lang';
import { useTheme } from '@/Composables/useTheme';
import { Head } from '@/Components';
import Icon from '@/Components/Icon.vue';
import api from '@/Services/api';
import echo from '@/echo';
import { useTransition, TransitionPresets } from '@vueuse/core';

interface WelcomeProps {
    featuredInternships?: any[];
    companies?: any[];
    stats?: Record<string, any> | null;
}

const props = defineProps<WelcomeProps>();
const langStore = useLangStore();
const { isDarkMode } = useTheme();

// Helper for translations
const t = (key: string) => langStore.t(key);

// Reactive state for data
const featuredInternships = ref<any[]>(props.featuredInternships || []);
const companiesRes = ref<any[]>(props.companies || []);
const stats = ref<any>(props.stats || null);
const loading = ref({
    stats: !props.stats,
    jobs: !props.featuredInternships,
    companies: !props.companies,
});
const error = ref({
    stats: false,
    jobs: false,
    companies: false
});

// Search form state
const searchQuery = ref('');
const locationQuery = ref('');

// AI Matcher reactive state
const aiPrompt = ref('');
const aiLoading = ref(false);
const aiMatches = ref<any[]>([]);
const aiHasSearched = ref(false);
const aiError = ref('');

const submitAiMatcher = async () => {
    if (!aiPrompt.value.trim() || aiLoading.value) return;
    aiLoading.value = true;
    aiError.value = '';
    aiMatches.value = [];
    aiHasSearched.value = true;
    try {
        const response = await api.post('/ai/public/finder', {
            prompt: aiPrompt.value
        });
        if (response.data && Array.isArray(response.data.matches)) {
            aiMatches.value = response.data.matches;
        } else {
            aiMatches.value = [];
        }
    } catch (err: unknown) {
        logger.error('AI Matcher failed:', err);
        const errorData = (err as any)?.response?.data;
        const errCode = errorData?.error;
        const status = (err as any)?.response?.status;
        if (errCode === 'RATE_LIMIT_EXCEEDED' || status === 429) {
            aiError.value = 'Mesin AI sedang sibuk. Batas penggunaan tercapai. Silakan coba lagi dalam 1 jam.';
        } else if (!(err as any)?.response || status >= 500) {
            aiError.value = 'Server AI sedang tidak tersedia. Silakan coba beberapa saat lagi.';
        } else {
            aiError.value = errorData?.message || errorData?.error || 'Gagal terhubung dengan mesin AI. Silakan coba beberapa saat lagi.';
        }
    } finally {
        aiLoading.value = false;
    }
};

// eslint-disable-next-line no-undef
const handleAiKeydown = (e: KeyboardEvent) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        submitAiMatcher();
    }
};

// Contoh prompt saran untuk user
const aiExamples = [
    'Saya mahasiswa Ilmu Komunikasi, suka marketing digital dan media sosial',
    'Frontend developer React & Vue, cari magang remote',
    'Mahasiswa Hukum semester 6, tertarik bidang legal',
    'Suka desain grafis dan video editing',
    'Data analyst Python dan Excel',
];

const submitSearch = () => {
    inertiaRouter.get('/internships', {
        q: searchQuery.value,
        location: locationQuery.value,
    });
};

// Fetch data functions
const fetchStats = async () => {
    loading.value.stats = true;
    error.value.stats = false;
    try {
        const response = await api.get('/stats/public');
        stats.value = response.data;
    } catch (err) {
        logger.error('Failed to fetch stats:', err);
        error.value.stats = true;
    } finally {
        loading.value.stats = false;
    }
};

const fetchJobs = async () => {
    loading.value.jobs = true;
    error.value.jobs = false;
    try {
        const response = await api.get('/internships?featured=1&per_page=6');
        featuredInternships.value = response.data.data || [];
    } catch (err) {
        logger.error('Failed to fetch jobs:', err);
        error.value.jobs = true;
    } finally {
        loading.value.jobs = false;
    }
};

const fetchCompanies = async () => {
    loading.value.companies = true;
    error.value.companies = false;
    try {
        const response = await api.get('/companies?per_page=12');
        companiesRes.value = response.data.data || [];
    } catch (err) {
        logger.error('Failed to fetch companies:', err);
        error.value.companies = true;
    } finally {
        loading.value.companies = false;
    }
};

// Testimonials data (Realtime if endpoint exists, else empty)
const testimonials = ref<any[]>([]);
const fetchTestimonials = async () => {
    // Fallback to empty as per requirement "no fake data"
    testimonials.value = [];
};

let pollInterval: ReturnType<typeof setInterval>;

onMounted(() => {
    if (!props.stats) {
        fetchStats();
    }

    if (!props.featuredInternships) {
        fetchJobs();
    }

    if (!props.companies) {
        fetchCompanies();
    }

    fetchTestimonials();

    // Listen for realtime stats updates via Echo (WebSockets)
    if (echo) {
        echo.channel('public-stats')
            .listen('.App\\Events\\PublicStatsUpdated', (e: any) => {
                if (e.stats) {
                    stats.value = e.stats;
                } else {
                    fetchStats(); // Fallback if payload empty
                }
            });
    }

    // Robust Fallback: Polling every 15s to guarantee realtime experience for users without websockets
    pollInterval = setInterval(() => {
        fetchStats();
    }, 15000);
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
    if (echo) echo.leave('public-stats');
});

// Animations for Counters (VueUse)
const sourceInternships = computed(() => Number(stats.value?.total_internships) || 0);
const animatedInternships = useTransition(sourceInternships, { duration: 1500, transition: TransitionPresets.easeOutExpo });

const sourceCompanies = computed(() => Number(stats.value?.total_companies) || 0);
const animatedCompanies = useTransition(sourceCompanies, { duration: 1500, transition: TransitionPresets.easeOutExpo });

const sourceStudents = computed(() => Number(stats.value?.total_students) || 0);
const animatedStudents = useTransition(sourceStudents, { duration: 1500, transition: TransitionPresets.easeOutExpo });

const sourcePlacements = computed(() => Number(stats.value?.total_placements) || 0);
const animatedPlacements = useTransition(sourcePlacements, { duration: 1500, transition: TransitionPresets.easeOutExpo });

const statsCards = computed(() => [
    { label: t('stats.internships'), val: animatedInternships.value, icon: 'briefcase', color: 'text-blue-500' },
    { label: t('stats.companies'), val: animatedCompanies.value, icon: 'building', color: 'text-emerald-500' },
    { label: t('stats.students'), val: animatedStudents.value, icon: 'users', color: 'text-amber-500' },
    { label: t('stats.applications'), val: animatedPlacements.value, icon: 'send', color: 'text-purple-500' }
]);

// Format numbers based on locale
const formatNumber = (num: number) => {
    if (num === undefined || num === null) return '0';
    return new Intl.NumberFormat(langStore.locale === 'id' ? 'id-ID' : 'en-US').format(num);
};


// Categories static data but labels translated
const categories = computed(() => [
    { id: 'tech', label: t('cat.technology'), desc: t('cat.technology_desc'), icon: 'laptop' },
    { id: 'design', label: t('cat.design'), desc: t('cat.design_desc'), icon: 'palette' },
    { id: 'marketing', label: t('cat.marketing'), desc: t('cat.marketing_desc'), icon: 'megaphone' },
    { id: 'data', label: t('cat.data'), desc: t('cat.data_desc'), icon: 'chart' },
    { id: 'finance', label: t('cat.finance'), desc: t('cat.finance_desc'), icon: 'money' },
    { id: 'hr', label: t('cat.hr'), desc: t('cat.hr_desc'), icon: 'hr' },
    { id: 'content', label: t('cat.content'), desc: t('cat.content_desc'), icon: 'pen' },
    { id: 'admin', label: t('cat.admin'), desc: t('cat.admin_desc'), icon: 'clipboard' }
]);

const faqs = computed(() => [
    { q: t('faq.q1'), a: t('faq.a1') },
    { q: t('faq.q2'), a: t('faq.a2') },
    { q: t('faq.q3'), a: t('faq.a3') },
    { q: t('faq.q4'), a: t('faq.a4') },
    { q: t('faq.q5'), a: t('faq.a5') }
]);
</script>

<template>
    <PublicLayout>
        <Head :title="t('hero.title') + ' - InternHub'" />

        <!-- 1. Hero Section -->
        <section class="relative pt-16 pb-24 lg:pt-24 lg:pb-32 overflow-hidden transition-colors duration-500 border-b" :class="isDarkMode ? 'bg-slate-950 border-slate-800' : 'bg-white border-slate-100'">
            
            <div class="max-w-5xl mx-auto px-6 text-center">
                <!-- Premium Badge -->
                <div v-motion-slide-visible-once-bottom class="group relative inline-flex items-center justify-center mb-8 cursor-default">
                    <!-- Subtle glow effect behind -->
                    <div class="absolute inset-0 rounded-full opacity-0 group-hover:opacity-100 blur-md transition-opacity duration-700" :class="isDarkMode ? 'bg-blue-500/20' : 'bg-blue-400/20'"></div>
                    
                    <!-- Badge surface -->
                    <div class="relative flex items-center gap-2 px-3 py-1.5 rounded-full transition-all duration-300 shadow-sm" 
                         :class="isDarkMode ? 'bg-blue-900/40 hover:bg-blue-900/60' : 'bg-blue-50 hover:bg-blue-100'">
                        
                        <!-- Simple Dot -->
                        <div class="relative flex h-2 w-2 items-center justify-center">
                            <span class="absolute inline-flex h-full w-full rounded-full opacity-40 animate-ping" :class="isDarkMode ? 'bg-blue-400' : 'bg-blue-600'"></span>
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5" :class="isDarkMode ? 'bg-blue-400' : 'bg-blue-600'"></span>
                        </div>

                        <!-- Text -->
                        <span class="text-xs font-bold tracking-wide pr-1" :class="isDarkMode ? 'text-blue-400' : 'text-blue-600'">
                            {{ t('hero.badge') }}
                        </span>
                    </div>
                </div>

                <!-- Title & Subtitle -->
                <h1 v-motion-slide-visible-once-bottom class="text-4xl lg:text-7xl font-bold mb-8 leading-tight mx-auto max-w-4xl delay-100" :class="isDarkMode ? 'text-white' : 'text-slate-950'">
                    {{ t('hero.title') }}
                </h1>
                
                <p v-motion-slide-visible-once-bottom class="text-lg lg:text-xl mb-12 max-w-2xl mx-auto leading-relaxed delay-200" :class="isDarkMode ? 'text-slate-400' : 'text-slate-600'">
                    {{ t('hero.subtitle') }}
                </p>

                <!-- Professional Search Box (Centered) -->
                <div v-motion-slide-visible-once-bottom class="p-2 rounded-2xl mb-12 max-w-4xl mx-auto delay-300 border shadow-sm transition-colors" :class="isDarkMode ? 'bg-slate-900/80 border-slate-800' : 'bg-white border-slate-200'">
                    <form class="flex flex-col md:flex-row items-center" @submit.prevent="submitSearch">
                        <div class="flex-1 w-full flex items-center gap-3 px-6 py-4 border-b md:border-b-0 md:border-r transition-colors" :class="isDarkMode ? 'border-slate-700/50 focus-within:bg-slate-800/50 md:rounded-l-full' : 'border-slate-100 focus-within:bg-slate-50 md:rounded-l-full'">
                            <Icon name="search" class-name="w-5 h-5 text-blue-600" />
                            <input 
                                v-model="searchQuery"
                                type="text" 
                                :placeholder="t('hero.search_pos')"
                                autocomplete="off"
                                class="w-full border-none focus:ring-0 bg-transparent text-sm font-semibold placeholder:text-slate-400 outline-none"
                                :class="isDarkMode ? 'text-white' : 'text-slate-900'"
                            />
                        </div>
                        <div class="flex-1 w-full flex items-center gap-3 px-6 py-4 transition-colors" :class="isDarkMode ? 'focus-within:bg-slate-800/50' : 'focus-within:bg-slate-50'">
                            <Icon name="map" class-name="w-5 h-5 text-blue-600" />
                            <input 
                                v-model="locationQuery"
                                type="text" 
                                :placeholder="t('hero.search_loc')"
                                autocomplete="off"
                                class="w-full border-none focus:ring-0 bg-transparent text-sm font-semibold placeholder:text-slate-400 outline-none"
                                :class="isDarkMode ? 'text-white' : 'text-slate-900'"
                            />
                        </div>
                        <button type="submit" class="w-full md:w-auto bg-blue-600 text-white px-8 py-3.5 md:rounded-xl rounded-xl font-bold text-sm hover:bg-blue-700 active:scale-[0.98] transition-colors cursor-pointer flex items-center justify-center gap-2 m-1 md:m-0">
                            {{ t('hero.btn_search') }}
                            <Icon name="chevron" class-name="w-4 h-4" />
                        </button>
                    </form>
                </div>

                <!-- Quick Chips (Centered) -->
                <div class="flex flex-wrap items-center justify-center gap-3 mb-16">
                    <span class="text-xs font-medium mr-2" :class="isDarkMode ? 'text-slate-500' : 'text-slate-400'">{{ t('hero.popular') }}:</span>
                    <button v-for="tag in ['Remote', 'Jakarta', 'UI/UX', 'Frontend', 'Data', 'Marketing']" :key="tag" class="px-5 py-2 rounded-full text-xs font-semibold transition-colors cursor-pointer border" :class="isDarkMode ? 'bg-slate-900 border-slate-800 text-slate-400 hover:border-blue-600 hover:text-blue-500' : 'bg-slate-50 border-slate-200 text-slate-600 hover:border-blue-600 hover:text-blue-600'" @click="searchQuery = tag; submitSearch()">
                        {{ tag }}
                    </button>
                </div>

                <!-- Trust Row (Centered) -->
                <div class="flex flex-wrap justify-center gap-12">
                    <div v-for="point in ['free_students', 'verified_companies', 'transparent_status']" :key="point" class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center bg-emerald-500/10 border border-emerald-500/20">
                            <Icon name="check" class-name="w-4 h-4 text-emerald-500" />
                        </div>
                        <span class="text-xs font-medium" :class="isDarkMode ? 'text-slate-400' : 'text-slate-500'">{{ t('hero.' + point) }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. Trust/Statistic Strip -->
        <section class="py-12 border-y transition-colors duration-300" :class="isDarkMode ? 'bg-slate-950 border-slate-800' : 'bg-slate-50 border-slate-100'">
            <div class="max-w-7xl mx-auto px-6">
                <div v-if="loading.stats" class="grid grid-cols-2 lg:grid-cols-4 gap-8">
                    <div v-for="i in 4" :key="i" class="h-16 rounded-2xl " :class="isDarkMode ? 'bg-slate-900' : 'bg-white'"></div>
                </div>
                <div v-else-if="stats" class="grid grid-cols-2 lg:grid-cols-4 gap-8">
                    <div v-motion-slide-visible-once-bottom :style="`transition-delay: ${index * 100}ms`"
v-for="(item, index) in statsCards" :key="item.label" class="flex flex-col md:flex-row items-center gap-4 text-center md:text-left p-6 rounded-2xl transition-all hover:border-blue-200 hover:shadow-sm" :class="isDarkMode ? 'bg-slate-900/50 border border-slate-800' : 'bg-white border border-slate-200'">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl shadow-inner" :class="[isDarkMode ? 'bg-slate-800' : 'bg-slate-50', item.color]">
                            <Icon :name="item.icon" class-name="w-6 h-6" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold leading-none mb-1" style="font-variant-numeric: tabular-nums">{{ formatNumber(Math.round(item.val)) }}</div>
                            <div class="text-xs font-medium" :class="isDarkMode ? 'text-slate-500' : 'text-slate-400'">{{ item.label }}</div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center text-xs font-bold py-4 text-slate-500">
                    {{ t('stats.not_available') }}
                </div>
            </div>
        </section>

        <!-- 3. Lowongan Magang Terbaru -->
        <section class="py-24 lg:py-32 transition-colors duration-300" :class="isDarkMode ? 'bg-slate-950' : 'bg-white'">
            <div class="max-w-7xl mx-auto px-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-16">
                    <div v-motion-slide-visible-once-bottom class="max-w-2xl text-center md:text-left">
                        <h2 class="text-3xl lg:text-5xl font-bold mb-4 tracking-tight" :class="isDarkMode ? 'text-white' : 'text-slate-950'">{{ t('jobs.title') }}</h2>
                        <p class="text-lg font-medium" :class="isDarkMode ? 'text-slate-400' : 'text-slate-600'">{{ t('jobs.subtitle') }}</p>
                    </div>
                    <Link :href="route('internships.index')" class="group flex items-center justify-center md:justify-start gap-3 px-6 py-3 rounded-xl font-bold text-sm transition-colors shadow-sm hover:bg-slate-800 active:scale-[0.98]" :class="isDarkMode ? 'bg-slate-800 text-white border border-slate-700' : 'bg-slate-900 text-white'">
                        {{ t('jobs.btn_all') }}
                        <Icon name="chevron" class-name="w-4 h-4 transition-transform group-hover:translate-x-1" />
                    </Link>
                </div>

                <!-- Grid Lowongan -->
                <div v-if="loading.jobs" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div v-for="i in 6" :key="i" class="h-[400px] rounded-2xl " :class="isDarkMode ? 'bg-slate-900' : 'bg-slate-100'"></div>
                </div>
                
                <div v-else-if="featuredInternships.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div v-motion-slide-visible-once-bottom :style="`transition-delay: ${index * 100}ms`" v-for="(job, index) in featuredInternships" :key="job.id" class="group relative p-6 md:p-8 rounded-2xl border transition-[border-color,box-shadow] duration-200 hover:shadow-md flex flex-col" :class="isDarkMode ? 'bg-slate-900 border-slate-800 hover:border-slate-700' : 'bg-white border-slate-200 hover:border-slate-300'">
                        <div class="flex items-start justify-between mb-8">
                             <div class="w-14 h-14 rounded-2xl flex items-center justify-center border font-bold text-xl overflow-hidden shadow-sm" :class="isDarkMode ? 'bg-slate-800 border-slate-700 text-blue-500' : 'bg-blue-50 border-blue-50 text-blue-600'">
                                <img v-if="job.company?.logo_url" loading="lazy" decoding="async" :src="job.company.logo_url" class="w-full h-full object-cover p-2" />
                                <span v-else>{{ job.company?.name?.charAt(0) }}</span>
                            </div>
                            <span class="px-3 py-1.5 rounded-full text-xs font-semibold" :class="isDarkMode ? 'bg-blue-900/40 text-blue-400' : 'bg-blue-50 text-blue-600'">{{ job.working_type || job.type }}</span>
                        </div>

                        <Link :href="route('internships.show', { slug: job.slug })" class="block mb-2 group-hover:text-blue-600 transition-colors">
                            <h3 class="text-xl font-bold tracking-tight line-clamp-1" :class="isDarkMode ? 'text-white' : 'text-slate-950'">{{ job.title }}</h3>
                        </Link>
                        
                        <div class="flex items-center gap-2 mb-6">
                            <p class="text-sm font-bold" :class="isDarkMode ? 'text-slate-400' : 'text-slate-600'">{{ job.company?.name }}</p>
                            <Icon v-if="job.company?.is_verified" name="shield" class-name="w-3.5 h-3.5 text-emerald-500" />
                        </div>

                        <div class="space-y-4 mb-8 text-xs font-medium" :class="isDarkMode ? 'text-slate-500' : 'text-slate-500'">
                            <div class="flex items-center gap-3">
                                <Icon name="map" class-name="w-4 h-4 text-blue-500" />
                                <span>{{ job.location }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <Icon name="clock" class-name="w-4 h-4 text-rose-500" />
                                <span>{{ job.deadline_at_human || t('job.deadline_urgent') }}</span>
                            </div>
                        </div>

                        <div class="pt-6 border-t flex items-center justify-between mt-auto" :class="isDarkMode ? 'border-slate-800' : 'border-slate-100'">
                            <div class="flex flex-col">
                                <span class="text-xs font-medium text-slate-500 mb-1 uppercase tracking-wide">{{ t('job.stipend') }}</span>
                                <span class="text-base font-bold" :class="isDarkMode ? 'text-blue-400' : 'text-blue-600'">{{ job.stipend || t('job.stipend_default') }}</span>
                            </div>
                            <Link :href="route('internships.show', { slug: job.slug })" aria-label="View internship details" class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white flex items-center justify-center transition-colors cursor-pointer shadow-sm dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-600 dark:hover:text-white">
                                <Icon name="chevron" class-name="w-4 h-4" />
                            </Link>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-24 rounded-2xl border border-dashed" :class="isDarkMode ? 'bg-slate-900/30 border-slate-800 text-slate-500' : 'bg-slate-50 border-slate-200 text-slate-400'">
                    <Icon name="briefcase" class-name="w-12 h-12 mx-auto mb-6 opacity-20" />
                    <p class="font-bold text-lg">{{ t('jobs.empty') }}</p>
                </div>
            </div>
        </section>

        <!-- 4. Filter & Kategori -->
        <section class="py-24 transition-colors duration-300" :class="isDarkMode ? 'bg-slate-900 border-y border-slate-800' : 'bg-slate-50 border-y border-slate-100'">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl lg:text-4xl font-bold mb-4 tracking-tight" :class="isDarkMode ? 'text-white' : 'text-slate-950'">{{ t('categories.title') }}</h2>
                    <p class="text-lg font-medium max-w-2xl mx-auto" :class="isDarkMode ? 'text-slate-400' : 'text-slate-600'">{{ t('categories.subtitle') }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <button v-motion-slide-visible-once-bottom :style="`transition-delay: ${index * 75}ms`" v-for="(cat, index) in categories" :key="cat.id" class="p-8 rounded-2xl border transition-[border-color,box-shadow] duration-200 group text-left cursor-pointer" :class="isDarkMode ? 'bg-slate-950 border-slate-800 hover:border-blue-900' : 'bg-white border-slate-200 hover:border-blue-200 hover:shadow-md'" @click="searchQuery = cat.label; submitSearch()">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-6" :class="isDarkMode ? 'bg-slate-900 text-blue-500' : 'bg-blue-50 text-blue-600'">
                            <Icon :name="cat.icon" class-name="w-6 h-6" />
                        </div>
                        <h3 class="text-lg font-bold mb-1" :class="isDarkMode ? 'text-white' : 'text-slate-950'">{{ cat.label }}</h3>
                        <p class="text-xs font-medium mb-6" :class="isDarkMode ? 'text-slate-500' : 'text-slate-400'">{{ cat.desc }}</p>
                        <!-- Realtime count would go here if supported -->
                    </button>
                </div>
            </div>
        </section>

        <!-- 5. Perusahaan Terverifikasi -->
        <section class="py-24 transition-colors duration-300" :class="isDarkMode ? 'bg-slate-950' : 'bg-white'">
            <div class="max-w-7xl mx-auto px-6">
                <div v-motion-slide-visible-once-bottom class="text-center mb-16">
                    <h2 class="text-3xl font-bold mb-4 tracking-tight" :class="isDarkMode ? 'text-white' : 'text-slate-950'">{{ t('companies.title') }}</h2>
                    <p class="text-lg font-medium max-w-2xl mx-auto" :class="isDarkMode ? 'text-slate-400' : 'text-slate-600'">{{ t('companies.subtitle') }}</p>
                </div>

                <div v-if="loading.companies" class="flex flex-wrap justify-center gap-8 opacity-30">
                    <div v-for="i in 8" :key="i" class="w-32 h-12 rounded-lg bg-slate-500 "></div>
                </div>
                <div v-else-if="companiesRes.length > 0" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
                    <div v-motion-slide-visible-once-bottom :style="`transition-delay: ${index * 50}ms`" v-for="(comp, index) in companiesRes" :key="comp.id" class="p-6 rounded-2xl border transition-all duration-300 flex flex-col items-center gap-4 group" :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-100 shadow-sm'">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center border font-bold text-xl overflow-hidden grayscale group-hover:grayscale-0 transition-[filter] duration-200" :class="isDarkMode ? 'bg-slate-800 border-slate-700 text-blue-500' : 'bg-slate-50 border-slate-200 text-slate-400'">
                            <img v-if="comp.logo_url" loading="lazy" decoding="async" :src="comp.logo_url" class="w-full h-full object-cover" />
                            <span v-else>{{ comp.name?.charAt(0) }}</span>
                        </div>
                        <div class="text-center">
                            <p class="text-xs font-bold line-clamp-1 mb-1" :class="isDarkMode ? 'text-white' : 'text-slate-950'">{{ comp.name }}</p>
                            <div v-if="comp.is_verified" class="flex items-center justify-center gap-1 text-xs font-semibold text-emerald-600">
                                <Icon name="shield" class-name="w-2.5 h-2.5" />
                                Verified
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center text-xs font-bold text-slate-500">
                    {{ t('companies.empty') }}
                </div>
            </div>
        </section>

        <!-- 6. Cara Melamar Magang -->
        <section class="py-24 border-y transition-colors duration-300" :class="isDarkMode ? 'bg-slate-950 border-slate-800' : 'bg-slate-50 border-slate-100'">
            <div v-motion-slide-visible-once-bottom class="max-w-7xl mx-auto px-6 text-center">
                <h2 class="text-3xl lg:text-4xl font-bold mb-16 tracking-tight" :class="isDarkMode ? 'text-white' : 'text-slate-950'">{{ t('steps.title') }}</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 relative">
                    <!-- Decor Line -->
                    <div class="hidden md:block absolute top-12 left-0 w-full h-[2px] -z-0" :class="isDarkMode ? 'bg-slate-800' : 'bg-slate-200'"></div>
                    
                    <div v-motion-slide-visible-once-bottom :style="`transition-delay: ${i * 100}ms`" v-for="(step, i) in ['register', 'profile', 'find', 'apply', 'monitor']" :key="step" class="relative z-10 flex flex-col items-center">
                        <div class="w-24 h-24 rounded-full flex items-center justify-center border-4 mb-6 transition-colors" :class="isDarkMode ? 'bg-slate-900 border-slate-800 text-blue-500' : 'bg-white border-slate-100 text-blue-600'">
                            <span class="text-3xl font-bold">{{ i + 1 }}</span>
                        </div>
                        <h4 class="text-sm font-semibold mb-2" :class="isDarkMode ? 'text-white' : 'text-slate-950'">{{ t('steps.step' + (i+1) + '_title') }}</h4>
                        <p class="text-xs font-medium leading-relaxed max-w-[160px]" :class="isDarkMode ? 'text-slate-400' : 'text-slate-500'">{{ t('steps.step' + (i+1) + '_desc') }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 7. Keunggulan InternHub -->
        <section class="py-24 transition-colors duration-300" :class="isDarkMode ? 'bg-slate-950' : 'bg-white'">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl lg:text-4xl font-bold mb-4 tracking-tight" :class="isDarkMode ? 'text-white' : 'text-slate-950'">{{ t('advantages.title') }}</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div v-motion-slide-visible-once-bottom :style="`transition-delay: ${index * 100}ms`"
v-for="(item, index) in [
                        { icon: 'shield', title: t('advantages.v1_title'), desc: t('advantages.v1_desc') },
                        { icon: 'briefcase', title: t('advantages.v2_title'), desc: t('advantages.v2_desc') },
                        { icon: 'money', title: t('advantages.v3_title'), desc: t('advantages.v3_desc') },
                        { icon: 'send', title: t('advantages.v4_title'), desc: t('advantages.v4_desc') },
                        { icon: 'sparkles', title: t('advantages.v5_title'), desc: t('advantages.v5_desc') },
                        { icon: 'users', title: t('advantages.v6_title'), desc: t('advantages.v6_desc') }
                    ]" :key="item.title" class="p-8 rounded-2xl border transition-[border-color,box-shadow] duration-200 hover:shadow-md" :class="isDarkMode ? 'bg-slate-900 border-slate-800 hover:border-slate-700' : 'bg-white border-slate-200 hover:border-slate-300'">
                        <Icon :name="item.icon" class-name="w-10 h-10 text-blue-600 mb-6" />
                        <h3 class="text-lg font-bold mb-3" :class="isDarkMode ? 'text-white' : 'text-slate-950'">{{ item.title }}</h3>
                        <p class="text-sm font-medium leading-relaxed" :class="isDarkMode ? 'text-slate-400' : 'text-slate-500'">{{ item.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 8. Rekomendasi AI -->
        <section class="py-24 transition-colors duration-300" :class="isDarkMode ? 'bg-slate-950' : 'bg-slate-50'">
            <div class="container mx-auto px-6">
                <div v-motion-slide-visible-once-bottom class="max-w-4xl mx-auto rounded-2xl p-12 lg:p-20 border transition-all duration-500 relative overflow-hidden text-center" :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200 shadow-sm'">
                    
                    <!-- Decorative Element -->
                    
                    

                    <div class="relative z-10 text-left">
                        <div class="mb-4">
                            <span class="text-xs font-bold tracking-widest uppercase text-blue-600 dark:text-blue-400">SMART MATCH</span>
                        </div>
                        
                        <h2 class="text-3xl lg:text-4xl font-bold mb-4 tracking-tight" :class="isDarkMode ? 'text-white' : 'text-slate-900'">Temukan Magang Ideal Anda</h2>
                        
                        <p class="text-base font-medium leading-relaxed mb-8 max-w-2xl" :class="isDarkMode ? 'text-slate-400' : 'text-slate-600'">
                            Ceritakan minat, keahlian, atau posisi impian Anda dengan bahasa sehari-hari. Sistem kami akan mencocokkan Anda dengan lowongan yang paling relevan.
                        </p>
                        
                        <div class="max-w-3xl border transition-all duration-300 focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 rounded-lg overflow-hidden flex flex-col" :class="isDarkMode ? 'bg-slate-900 border-slate-700' : 'bg-white border-slate-300'">
                            <textarea 
                                v-model="aiPrompt"
                                :placeholder="t('ai.placeholder')" 
                                class="w-full border-none focus:ring-0 bg-transparent text-base font-semibold resize-none h-28 px-6 py-4 outline-none" 
                                :class="isDarkMode ? 'text-white placeholder:text-slate-600' : 'text-slate-900 placeholder:text-slate-400'"
                                :disabled="aiLoading"
                                @keydown="handleAiKeydown"
                            ></textarea>
                            
                            <div class="flex items-center justify-between p-3 border-t bg-slate-50 dark:bg-slate-800/50" :class="isDarkMode ? 'border-slate-700' : 'border-slate-200'">
                                <div class="hidden sm:block text-xs font-medium text-slate-500">
                                    Tekan Shift + Enter untuk garis baru
                                </div>
                                <button 
                                    :disabled="aiLoading || !aiPrompt.trim()"
                                    class="w-full sm:w-auto bg-slate-900 dark:bg-white text-white dark:text-slate-900 px-6 py-2.5 rounded font-semibold text-sm hover:opacity-90 active:scale-[0.98] transition-all cursor-pointer flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                    @click="submitAiMatcher"
                                >
                                    <Icon :name="aiLoading ? 'loader' : 'search'" class-name="w-4 h-4" :class="aiLoading ? 'animate-spin' : ''" />
                                    {{ aiLoading ? 'Mencari...' : 'Cari Lowongan' }}
                                </button>
                            </div>
                        </div>

                        <!-- AI Example Prompt Chips -->
                        <div v-if="!aiHasSearched" class="max-w-3xl mt-4 flex flex-wrap items-center gap-2">
                            <span class="text-xs font-medium text-slate-400 w-full text-center mb-1">Contoh:</span>
                            <button
                                v-for="ex in aiExamples"
                                :key="ex"
                                class="px-4 py-2 rounded-full text-xs font-semibold border transition-colors cursor-pointer"
                                :class="isDarkMode ? 'bg-slate-900 border-slate-700 text-slate-400 hover:border-blue-600 hover:text-blue-400' : 'bg-slate-50 border-slate-200 text-slate-600 hover:border-blue-500 hover:text-blue-600'"
                                @click="aiPrompt = ex; submitAiMatcher()"
                            >
                                {{ ex.length > 40 ? ex.slice(0, 38) + '...' : ex }}
                            </button>
                        </div>

                        <!-- Loader State -->
                        <div v-if="aiLoading" class="max-w-3xl mt-8 p-6 rounded-lg border flex items-center gap-4" :class="isDarkMode ? 'bg-slate-900 border-slate-700' : 'bg-white border-slate-200'">
                            <Icon name="loader" class-name="w-5 h-5 animate-spin text-slate-500" />
                            <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Sedang memproses kriteria pencarian Anda...</span>
                        </div>

                        <!-- AI Error Message -->
                        <div v-if="aiError" class="max-w-2xl mx-auto mt-8 p-6 rounded-2xl border bg-red-500/10 border-red-500/20 text-red-500 text-sm font-semibold text-center">
                            {{ aiError }}
                        </div>

                        <!-- AI Matching Results -->
                        <div v-if="!aiLoading && aiMatches.length > 0" class="max-w-2xl mx-auto mt-12 text-left space-y-6">
                            <h3 class="text-xl font-bold mb-6 flex items-center gap-3 px-4" :class="isDarkMode ? 'text-white' : 'text-slate-900'">
                                <Icon name="sparkles" class-name="w-6 h-6 text-blue-600" />
                                Rekomendasi Magang Terbaik Untukmu
                            </h3>
                            
                            <div v-for="match in aiMatches" :key="match.id" class="p-8 rounded-2xl border transition-[border-color] duration-200 hover:border-blue-500 flex flex-col md:flex-row items-start md:items-center justify-between gap-6" :class="isDarkMode ? 'bg-slate-950 border-slate-800' : 'bg-white border-slate-100 shadow-sm'">
                                <div class="flex-1 space-y-4">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <span class="px-4 py-1.5 rounded-full text-xs font-semibold text-emerald-600 bg-emerald-500/10">
                                            Match: {{ match.match_score }}%
                                        </span>
                                        <span class="px-4 py-1.5 rounded-full text-xs font-semibold text-blue-600 bg-blue-500/10">
                                            {{ match.type }}
                                        </span>
                                    </div>
                                    
                                    <div>
                                        <h4 class="text-lg font-bold" :class="isDarkMode ? 'text-white' : 'text-slate-900'">
                                            {{ match.title }}
                                        </h4>
                                        <p class="text-sm font-bold text-blue-600 mt-1">
                                            {{ match.company }}
                                        </p>
                                        <p class="text-xs font-semibold text-slate-400 dark:text-neutral-500 flex items-center gap-1.5 mt-1">
                                            <Icon name="map-pin" class-name="w-3.5 h-3.5" />
                                            {{ match.location }}
                                        </p>
                                    </div>
                                    
                                    <!-- AI Explanation Bubble -->
                                    <div class="p-5 rounded-2xl border text-sm font-semibold leading-relaxed" :class="isDarkMode ? 'bg-slate-900 border-slate-800 text-slate-300' : 'bg-slate-50 border-slate-100 text-slate-600'">
                                        <span class="font-bold text-blue-600 block mb-1">Mengapa ini cocok?</span>
                                        {{ match.explanation }}
                                    </div>
                                </div>
                                
                                <button 
                                    class="w-full md:w-auto shrink-0 bg-blue-600 text-white px-6 py-3 rounded-xl font-bold text-sm hover:bg-blue-700 active:scale-[0.98] transition-colors cursor-pointer flex items-center justify-center gap-2"
                                    @click="inertiaRouter.visit(`/internships/${match.slug}`)"
                                >
                                    Lihat Detail
                                    <Icon name="chevron" class-name="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <!-- No Matches Found -->
                        <div v-else-if="!aiLoading && aiHasSearched && aiMatches.length === 0" class="max-w-3xl mt-8 p-10 rounded-lg border text-center space-y-4" :class="isDarkMode ? 'bg-slate-900 border-slate-700' : 'bg-white border-slate-200'">
                            <Icon name="search" class-name="w-8 h-8 text-slate-400 mx-auto" />
                            <h4 class="text-lg font-bold" :class="isDarkMode ? 'text-white' : 'text-slate-900'">Pencarian Tidak Ditemukan</h4>
                            <p class="text-sm font-medium text-slate-500 max-w-md mx-auto">Kami tidak dapat menemukan lowongan magang yang cocok dengan kriteria tersebut. Coba gunakan kata kunci yang lebih umum.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 9. Testimoni -->
        <section v-if="testimonials.length > 0" class="py-24 lg:py-32 transition-colors duration-300" :class="isDarkMode ? 'bg-slate-950' : 'bg-white'">
            <div v-motion-slide-visible-once-bottom class="max-w-7xl mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl lg:text-4xl font-bold mb-4 tracking-tight" :class="isDarkMode ? 'text-white' : 'text-slate-950'">{{ t('testimonials.title') }}</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div v-motion-slide-visible-once-bottom :style="`transition-delay: ${index * 150}ms`" v-for="(t, index) in testimonials" :key="t.name" class="p-8 rounded-2xl border transition-all hover:shadow-sm" :class="isDarkMode ? 'bg-slate-900 border-slate-800 hover:border-slate-700' : 'bg-white border-slate-200 hover:border-slate-300'">
                        <div class="flex items-center gap-6 mb-8">
                            <div class="w-16 h-16 rounded-2xl flex items-center justify-center font-bold text-xl text-white shadow-xl bg-blue-600">
                                {{ t.initials }}
                            </div>
                            <div>
                                <h4 class="text-lg font-bold mb-1" :class="isDarkMode ? 'text-white' : 'text-slate-950'">{{ t.name }}</h4>
                                <p class="text-xs font-semibold text-blue-600">{{ t.role }}</p>
                            </div>
                        </div>
                        <p class="text-lg font-medium italic leading-relaxed" :class="isDarkMode ? 'text-slate-400' : 'text-slate-600'">"{{ t.text }}"</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 10. FAQ -->
        <section class="py-24 lg:py-32 transition-colors duration-300" :class="isDarkMode ? 'bg-slate-950 border-t border-slate-800' : 'bg-white border-t border-slate-100'">
            <div v-motion-slide-visible-once-bottom class="max-w-4xl mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl lg:text-4xl font-bold mb-4 tracking-tight" :class="isDarkMode ? 'text-white' : 'text-slate-950'">{{ t('faq.title') }}</h2>
                </div>
                
                <div class="space-y-4">
                    <details v-for="(f, i) in faqs" :key="i" class="group rounded-2xl border overflow-hidden" :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'">
                        <summary class="flex items-center justify-between p-8 cursor-pointer list-none font-bold transition-colors" :class="isDarkMode ? 'text-white' : 'text-slate-950 group-open:text-blue-600'">
                            <h4 class="text-lg">{{ f.q }}</h4>
                            <Icon name="chevron" class-name="w-5 h-5 transition-transform group-open:rotate-90 text-slate-500" />
                        </summary>
                        <div class="px-8 pb-8 text-sm font-medium leading-relaxed border-t transition-colors" :class="isDarkMode ? 'text-slate-400 border-slate-800' : 'text-slate-600 border-slate-100 pt-6'">
                            {{ f.a }}
                        </div>
                    </details>
                </div>
            </div>
        </section>

        <!-- 11. CTA Akhir -->
        <section class="py-24 lg:py-32 max-w-7xl mx-auto px-6">
            <div v-motion-slide-visible-once-bottom class="rounded-2xl p-16 lg:p-24 text-center border transition-all duration-500 relative overflow-hidden" :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200 shadow-sm'">
                <!-- Abstract Decor -->
                
                

                <div class="relative z-10">
                    <h2 class="text-4xl lg:text-6xl font-bold mb-8 tracking-tight leading-tight" :class="isDarkMode ? 'text-white' : 'text-slate-950'">{{ t('cta.title') }}</h2>
                    <p class="text-xl font-medium max-w-3xl mx-auto mb-16 leading-relaxed" :class="isDarkMode ? 'text-slate-400' : 'text-slate-600'">
                        {{ t('cta.subtitle') }}
                    </p>
                    
                    <div class="flex flex-col md:flex-row justify-center items-center gap-6 mb-20">
                        <Link v-if="$page.props.feature_flags?.public_registration !== false" href="/register" class="w-full md:w-auto bg-slate-900 text-white px-16 py-6 rounded-full font-bold text-lg uppercase tracking-wide hover:bg-slate-800 transition-colors shadow-sm">
                            {{ t('cta.btn_register') }}
                        </Link>
                        <Link href="/internships" class="w-full md:w-auto px-16 py-6 rounded-full font-bold text-lg uppercase tracking-wide border-2 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800" :class="isDarkMode ? 'border-slate-800 text-white' : 'border-slate-200 text-slate-950'">
                            {{ t('cta.btn_jobs') }}
                        </Link>
                    </div>

                    <div class="flex flex-wrap justify-center gap-12 opacity-60">
                        <div v-for="point in ['free_students', 'verified_companies', 'transparent_status']" :key="point" class="flex items-center gap-3">
                            <Icon name="check" class-name="w-5 h-5 text-emerald-500" />
                            <span class="text-xs font-medium">{{ t('hero.' + point) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>



