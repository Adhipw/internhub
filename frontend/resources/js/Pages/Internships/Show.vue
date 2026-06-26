<script setup lang="ts">
import { ref, computed } from 'vue';
import { router as inertiaRouter } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { 
    MapPin, Calendar, Clock, DollarSign, Building2, 
    ArrowLeft, ArrowRight, Share2, ShieldCheck, CheckCircle2, 
    Briefcase, Globe, Info, Mail
} from 'lucide-vue-next';
import { useLangStore } from '@/Stores/lang';
import Card from '@/Components/Card.vue';
import Button from '@/Components/Button.vue';
import Badge from '@/Components/Badge.vue';
import { useAuthStore } from '@/Stores/auth';
import type { Internship } from '@/Types/internship';
import DOMPurify from 'dompurify';

interface InternshipShowProps {
    internship: Internship;
    relatedInternships?: Internship[];
    hasApplied?: boolean;
    matchScore?: number | null;
    missingSkills?: string[];
}

const props = defineProps<InternshipShowProps>();

const langStore = useLangStore();
const authStore = useAuthStore();
const t = (key: string) => langStore.t(key);

const internship = computed(() => props.internship);

const updateSeo = () => {
    if (!props.internship) return;

    const companyName = props.internship.company.name ?? 'InternHub';
    const pageTitle = `${props.internship.title} di ${companyName} - InternHub`;
    document.title = pageTitle;

    const metaDesc = document.querySelector('meta[name="description"]');
    if (metaDesc) {
        metaDesc.setAttribute('content', `Daftar lowongan magang ${props.internship.title} di ${companyName} melalui InternHub. Lokasi: ${props.internship.location}.`);
    }
};

import Modal from '@/Components/Modal.vue';

const showApplyModal = ref(false);
const coverLetter = ref('');
const applying = ref(false);
const applyError = ref('');

const handleApply = () => {
    if (!authStore.isAuthenticated) {
        inertiaRouter.visit(`/login?redirect=${encodeURIComponent(window.location.pathname)}`);
        return;
    }
    if (!internship.value) return;
    
    coverLetter.value = '';
    applyError.value = '';
    showApplyModal.value = true;
};

const submitApplication = async () => {
    if (!internship.value) return;
    applying.value = true;
    applyError.value = '';

    inertiaRouter.post(`/internships/${internship.value.slug}/apply`, {
        cover_letter: coverLetter.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showApplyModal.value = false;
            inertiaRouter.visit('/my-applications');
        },
        onError: (errors) => {
            applyError.value = String(errors.application || errors.cover_letter || 'Terjadi kesalahan saat mengirim lamaran. Silakan coba lagi.');
        },
        onFinish: () => {
            applying.value = false;
        },
    });
};

const goBack = () => {
    if (typeof window !== 'undefined' && window.history.length > 1) {
        window.history.back();
    } else {
        inertiaRouter.visit('/internships');
    }
};

const cleanHtml = (html?: string | null) => {
    if (!html) return '';
    // Hapus tag komentar Google Translate/Kalibrr
    const noComments = html.replace(/<!--TgQPHd\|?\[\]-->/g, '');
    return DOMPurify.sanitize(noComments);
};

updateSeo();
</script>

<template>
    <PublicLayout>
        <div class="bg-neutral-50 dark:bg-neutral-950 min-h-screen pt-32 pb-32">
            <div class="container mx-auto px-6 max-w-6xl">
                <!-- Back Button -->
                <button class="flex items-center gap-2 text-neutral-400 hover:text-primary-600 transition-colors mb-12 group font-bold text-sm uppercase tracking-widest" @click="goBack">
                    <ArrowLeft class="w-5 h-5 group-hover:-translate-x-2 transition-transform" />
                    {{ t('common.back') }}
                </button>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-12 items-start">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-12">
                        <!-- Header Card -->
                        <div class="bg-white dark:bg-neutral-900 rounded-[3rem] p-10 md:p-16 border border-neutral-100 dark:border-neutral-800 shadow-2xl shadow-neutral-200/50 dark:shadow-none relative overflow-hidden">
                            <!-- Subtle Decoration -->
                            <div class="absolute top-0 right-0 w-64 h-64 bg-primary-500/5 rounded-full blur-3xl -mr-32 -mt-32"></div>
                            
                            <div class="relative z-10">
                                <div class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-12">
                                    <div class="flex items-center gap-8">
                                        <div class="w-24 h-24 bg-neutral-50 dark:bg-neutral-950 rounded-[2rem] flex items-center justify-center border border-neutral-100 dark:border-neutral-800 shadow-sm overflow-hidden shrink-0">
                                            <img v-if="internship.company?.logo_url" loading="lazy" decoding="async" :src="internship.company.logo_url" class="w-full h-full object-contain p-4" />
                                            <Building2 v-else class="w-12 h-12 text-neutral-300" />
                                        </div>
                                        <div>
                                            <Badge variant="primary" size="lg" class="mb-4">{{ internship.type }}</Badge>
                                            
                                            <div v-if="matchScore !== undefined && matchScore !== null" class="mb-6 p-4 rounded-3xl flex items-center gap-4 border" :class="matchScore >= 80 ? 'bg-emerald-50 border-emerald-100 text-emerald-700 dark:bg-emerald-900/20 dark:border-emerald-800/50 dark:text-emerald-400' : 'bg-amber-50 border-amber-100 text-amber-700 dark:bg-amber-900/20 dark:border-amber-800/50 dark:text-amber-400'">
                                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center font-black text-lg shrink-0" :class="matchScore >= 80 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-800/50 dark:text-emerald-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-800/50 dark:text-amber-400'">
                                                    {{ matchScore }}%
                                                </div>
                                                <div>
                                                    <h4 class="font-black text-[10px] uppercase tracking-[0.2em] mb-1">Match Score</h4>
                                                    <p class="text-xs font-bold opacity-80" v-if="matchScore >= 80">🔥 Sangat Cocok! Profil Anda sesuai kriteria.</p>
                                                    <p class="text-xs font-bold opacity-80" v-else-if="missingSkills && missingSkills.length">💡 Pelajari keahlian berikut: <span class="font-medium opacity-90">{{ missingSkills.slice(0, 3).join(', ') }}{{ missingSkills.length > 3 ? '...' : '' }}</span></p>
                                                </div>
                                            </div>

                                            <h1 class="text-4xl font-extrabold text-neutral-900 dark:text-white tracking-tight leading-tight mb-2">{{ internship.title }}</h1>
                                            <p class="text-lg font-bold text-neutral-500 dark:text-neutral-400 flex items-center gap-2">
                                                <Building2 class="w-5 h-5" />
                                                {{ internship.company?.name }}
                                            </p>
                                        </div>
                                    </div>
                                    <button class="w-14 h-14 bg-neutral-50 dark:bg-neutral-800 rounded-2xl flex items-center justify-center text-neutral-400 hover:text-primary-600 transition-all">
                                        <Share2 class="w-6 h-6" />
                                    </button>
                                </div>

                                <div class="grid grid-cols-2 md:grid-cols-4 gap-8 pt-12 border-t border-neutral-50 dark:border-neutral-800">
                                    <div>
                                        <p class="text-[10px] font-black text-neutral-400 uppercase tracking-widest mb-2">{{ t('job.stipend') }}</p>
                                        <p class="text-lg font-black text-neutral-900 dark:text-white">{{ internship.stipend || t('job.stipend_default') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black text-neutral-400 uppercase tracking-widest mb-2">{{ t('filters.location_label') }}</p>
                                        <p class="text-lg font-black text-neutral-900 dark:text-white">{{ internship.location }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black text-neutral-400 uppercase tracking-widest mb-2">{{ t('job.deadline_label') }}</p>
                                        <p class="text-lg font-black text-rose-500">{{ internship.deadline_at_human || t('job.deadline_urgent') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black text-neutral-400 uppercase tracking-widest mb-2">{{ t('job.published_at') }}</p>
                                        <p class="text-lg font-black text-neutral-900 dark:text-white">{{ internship.created_at_human }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description & Content -->
                        <div class="space-y-12 px-8">
                            <section>
                                <h2 class="text-2xl font-black text-neutral-900 dark:text-white mb-6 flex items-center gap-3">
                                    <div class="w-1.5 h-8 bg-primary-600 rounded-full"></div>
                                    {{ t('hr.internships.about_role') }}
                                </h2>
                                <div class="prose prose-neutral dark:prose-invert max-w-none text-neutral-500 dark:text-neutral-400 font-medium leading-relaxed" v-html="cleanHtml(internship.description)">
                                </div>
                            </section>

                            <section v-if="internship.requirements && internship.requirements.length">
                                <h2 class="text-2xl font-black text-neutral-900 dark:text-white mb-8 flex items-center gap-3">
                                    <div class="w-1.5 h-8 bg-primary-600 rounded-full"></div>
                                    {{ t('hr.internships.requirements') }}
                                </h2>
                                <div v-if="typeof internship.requirements === 'string'" class="prose prose-neutral dark:prose-invert max-w-none text-neutral-500 dark:text-neutral-400 font-medium leading-relaxed" v-html="cleanHtml(internship.requirements)"></div>
                                <ul v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <li v-for="(req, index) in internship.requirements" :key="index" class="flex items-start gap-4 p-6 bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-100 dark:border-neutral-800 shadow-sm">
                                        <CheckCircle2 class="w-6 h-6 text-emerald-500 shrink-0" />
                                        <span class="text-neutral-600 dark:text-neutral-300 font-bold leading-tight" v-html="cleanHtml(req)"></span>
                                    </li>
                                </ul>
                            </section>

                            <section v-if="internship.benefits && internship.benefits.length">
                                <h2 class="text-2xl font-black text-neutral-900 dark:text-white mb-8 flex items-center gap-3">
                                    <div class="w-1.5 h-8 bg-primary-600 rounded-full"></div>
                                    {{ t('job.benefit') }}
                                </h2>
                                <div v-if="typeof internship.benefits === 'string'" class="prose prose-neutral dark:prose-invert max-w-none text-neutral-500 dark:text-neutral-400 font-medium leading-relaxed" v-html="cleanHtml(internship.benefits)"></div>
                                <ul v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <li v-for="(benefit, index) in internship.benefits" :key="index" class="flex items-start gap-4 p-6 bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-100 dark:border-neutral-800 shadow-sm">
                                        <CheckCircle2 class="w-6 h-6 text-primary-500 shrink-0" />
                                        <span class="text-neutral-600 dark:text-neutral-300 font-bold leading-tight" v-html="cleanHtml(benefit)"></span>
                                    </li>
                                </ul>
                            </section>
                        </div>
                    </div>

                    <!-- Sidebar: Action & Info -->
                    <aside class="space-y-12 lg:sticky lg:top-32">
                        <!-- Action Card -->
                        <div class="bg-neutral-900 dark:bg-white p-10 rounded-[3rem] shadow-2xl relative overflow-hidden group">
                            <div class="relative z-10">
                                <h3 class="text-2xl font-black text-white dark:text-neutral-900 mb-6">{{ t('job.apply_card_title') }}</h3>
                                <p class="text-neutral-400 dark:text-neutral-500 mb-10 font-bold leading-relaxed">{{ t('job.apply_card_desc') }}</p>
                                <Button 
                                    size="xl" 
                                    variant="secondary" 
                                    class="w-full !bg-white dark:!bg-neutral-900 !text-neutral-900 dark:!text-white hover:scale-105 transition-transform" 
                                    @click="handleApply"
                                >
                                    {{ t('job.apply_now') }}
                                    <ArrowRight class="w-6 h-6 ml-3" />
                                </Button>
                            </div>
                            <!-- Texture -->
                            <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#ffffff_1px,transparent_1px)] dark:bg-[radial-gradient(#000000_1px,transparent_1px)] [background-size:20px_20px]"></div>
                        </div>

                        <!-- Company Card -->
                        <div class="bg-white dark:bg-neutral-900 p-10 rounded-[3rem] border border-neutral-100 dark:border-neutral-800 shadow-sm space-y-8">
                            <h3 class="text-xs font-black uppercase tracking-[0.2em] text-neutral-400">{{ t('company.about') }}</h3>
                            <div class="flex items-center gap-6">
                                <div class="w-16 h-16 bg-neutral-50 dark:bg-neutral-950 rounded-2xl flex items-center justify-center border border-neutral-100 dark:border-neutral-800 shadow-sm overflow-hidden shrink-0">
                                    <img v-if="internship.company?.logo_url" loading="lazy" decoding="async" :src="internship.company.logo_url" class="w-full h-full object-contain p-3" />
                                    <Building2 v-else class="w-10 h-10 text-neutral-300" />
                                </div>
                                <div>
                                    <h4 class="text-xl font-black text-neutral-900 dark:text-white line-clamp-1">{{ internship.company?.name }}</h4>
                                    <p class="text-sm font-bold text-primary-600">{{ internship.company?.industry || 'Industri Teknologi' }}</p>
                                </div>
                            </div>
                            <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400 leading-relaxed line-clamp-4">
                                {{ internship.company?.description || 'Perusahaan terverifikasi di platform InternHub yang berfokus pada inovasi dan pengembangan talenta muda.' }}
                            </p>
                            <Button variant="outline" class="w-full" @click="inertiaRouter.visit('/companies/' + internship.company?.slug)">
                                {{ t('company.view_profile') }}
                            </Button>
                            <a 
                                :href="`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(internship.location || internship.company?.name || '')}`" 
                                target="_blank"
                                class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl font-bold text-sm bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/40 transition-colors"
                            >
                                <MapPin class="w-4 h-4" /> Buka di Google Maps
                            </a>
                        </div>

                        <!-- Trust Badge -->
                        <div class="flex items-center gap-4 px-8 py-6 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/50 rounded-3xl">
                            <ShieldCheck class="w-8 h-8 text-emerald-500" />
                            <div>
                                <p class="text-sm font-black text-emerald-700 dark:text-emerald-400 uppercase tracking-widest">{{ t('company.verified_badge_title') }}</p>
                                <p class="text-[10px] font-bold text-emerald-600/70">{{ t('company.verified_badge_desc') }}</p>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>

        <!-- Apply Modal -->
        <Modal 
            :show="showApplyModal" 
            title="Kirim Lamaran Magang" 
            max-width="xl"
            @close="showApplyModal = false"
        >
            <div v-if="internship" class="space-y-8">
                <div class="p-6 bg-neutral-50 dark:bg-neutral-800 rounded-3xl border border-neutral-100 dark:border-neutral-700 flex items-center gap-6">
                    <div class="w-16 h-16 bg-white dark:bg-neutral-900 rounded-2xl flex items-center justify-center border border-neutral-100 dark:border-neutral-800 shadow-sm overflow-hidden shrink-0">
                        <img v-if="internship.company?.logo_url" loading="lazy" decoding="async" :src="internship.company.logo_url" class="w-full h-full object-contain p-3" />
                        <Building2 v-else class="w-10 h-10 text-neutral-300" />
                    </div>
                    <div>
                        <h4 class="text-lg font-black text-neutral-900 dark:text-white leading-snug">{{ internship.title }}</h4>
                        <p class="text-sm font-bold text-neutral-500 dark:text-neutral-400">{{ internship.company?.name }}</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <label class="block text-sm font-black text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">
                        Surat Lamaran / Pesan Pengantar (Opsional)
                    </label>
                    <textarea 
                        v-model="coverLetter"
                        rows="5"
                        class="w-full px-6 py-4 bg-neutral-50 dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 rounded-3xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all text-sm font-medium leading-relaxed dark:text-white"
                        placeholder="Tuliskan mengapa Anda tertarik dengan posisi ini dan mengapa Anda kandidat yang tepat..."
                    ></textarea>
                </div>

                <!-- Info Alert -->
                <div class="flex items-start gap-4 p-5 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800/50 rounded-3xl text-blue-700 dark:text-blue-400">
                    <Info class="w-5 h-5 mt-0.5 shrink-0" />
                    <div class="text-sm font-medium leading-relaxed">
                        <p class="font-bold mb-1">Informasi Pengiriman Profil</p>
                        CV dan Portfolio yang telah Anda unggah di halaman profil Anda akan otomatis disertakan dan dikirim ke pihak HR perusahaan ini.
                    </div>
                </div>

                <!-- Error Alert -->
                <div v-if="applyError" class="flex items-center gap-4 p-5 bg-rose-50 dark:bg-rose-900/20 border border-rose-100 dark:border-rose-800/50 rounded-3xl text-rose-600 dark:text-rose-400">
                    <Info class="w-5 h-5 shrink-0" />
                    <p class="text-sm font-bold">{{ applyError }}</p>
                </div>

                <div class="flex items-center justify-end gap-4 pt-4 border-t border-neutral-100 dark:border-neutral-800">
                    <Button 
                        variant="outline" 
                        :disabled="applying"
                        @click="showApplyModal = false"
                    >
                        Batal
                    </Button>
                    <Button 
                        variant="primary" 
                        :disabled="applying"
                        class="shadow-lg shadow-primary-500/25"
                        @click="submitApplication"
                    >
                        <span v-if="applying">Mengirim...</span>
                        <span v-else>Kirim Lamaran</span>
                    </Button>
                </div>
            </div>
        </Modal>
    </PublicLayout>
</template>
