<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { ref, computed, onMounted, watch } from 'vue';
import { useLangStore } from '@/Stores/lang';
import { 
    ChevronRight, Shield, ScrollText, Mail, HelpCircle, 
    BookOpen, Briefcase, Building2, Rocket, Users, 
    GraduationCap, Lightbulb, MapPin, Phone, Globe
} from 'lucide-vue-next';

const page = usePage();
const supportEmail = computed(() => (page.props.public_settings as any)?.support_email || 'support@InternHub.my.id');
const supportPhone = computed(() => (page.props.public_settings as any)?.support_phone || '+62 812 3456 7890');
const officeAddress = computed(() => (page.props.public_settings as any)?.office_address || t('info.office_address'));

const props = defineProps<{
    initialSection: string;
}>();

const langStore = useLangStore();
const t = (key: string) => langStore.t(key);

const activeTab = ref(props.initialSection || 'center');

// Real-time synchronization when route changes
watch(() => props.initialSection, (newVal) => {
    if (newVal) {
        activeTab.value = newVal;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
});

const sections = computed(() => [
    { id: 'center', label: t('info.help_center'), icon: HelpCircle, category: 'bantuan' },
    { id: 'contact', label: t('info.contact'), icon: Mail, category: 'bantuan' },
    { id: 'terms', label: t('info.terms'), icon: ScrollText, category: 'bantuan' },
    { id: 'privacy', label: t('info.privacy'), icon: Shield, category: 'bantuan' },
    
    { id: 'selection-system', label: t('info.selection_system'), icon: Rocket, category: 'perusahaan' },
    { id: 'employer-branding', label: t('info.employer_branding'), icon: Building2, category: 'perusahaan' },
    { id: 'enterprise', label: t('info.enterprise'), icon: Briefcase, category: 'perusahaan' },
    
    { id: 'cv-guide', label: t('info.cv_guide'), icon: BookOpen, category: 'mahasiswa' },
    { id: 'career-tips', label: t('info.career_tips'), icon: Lightbulb, category: 'mahasiswa' },
    { id: 'campus-program', label: t('info.campus_program'), icon: GraduationCap, category: 'mahasiswa' },
]);

const currentSection = computed(() => sections.value.find(s => s.id === activeTab.value));

// Dynamic "Last Updated" date for real-time feel
const lastUpdatedDate = computed(() => {
    const d = new Date();
    const months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    const monthsEn = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    
    const isEn = langStore.locale === 'en';
    const day = d.getDate();
    const month = isEn ? monthsEn[d.getMonth()] : months[d.getMonth()];
    const year = d.getFullYear();
    
    return isEn ? `${month} ${day}, ${year}` : `${day} ${month} ${year}`;
});

const faqs = computed(() => [
    { q: t('faq.q1'), a: t('faq.a1') },
    { q: t('faq.q2'), a: t('faq.a2') },
    { q: t('faq.q3'), a: t('faq.a3') },
    { q: t('faq.q4'), a: t('faq.a4') },
    { q: t('faq.q5'), a: t('faq.a5') }
]);

onMounted(() => {
    // Scroll to top when tab changes
    window.scrollTo({ top: 0, behavior: 'smooth' });
});
</script>

<template>

    <PublicLayout>
        <div class="pt-32 pb-20 bg-slate-50 dark:bg-neutral-950 min-h-screen transition-colors duration-300">
            <div class="container mx-auto px-6">
                <div class="flex flex-col lg:flex-row gap-12">
                    <!-- Sidebar Navigation -->
                    <aside class="lg:w-80 shrink-0">
                        <div class="sticky top-32 space-y-8">
                            <!-- Category: Bantuan -->
                            <div>
                                <h4 class="text-xs font-bold text-slate-400 dark:text-neutral-500 uppercase tracking-normal mb-4 px-4">{{ t('info.help_center') }}</h4>
                                <div class="space-y-1">
                                    <button 
                                        v-for="s in sections.filter(s => s.category === 'bantuan')" 
                                        :key="s.id"
                                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all group"
                                        :class="activeTab === s.id ? 'bg-primary-600 text-white shadow-lg shadow-primary-600/20' : 'text-slate-600 dark:text-neutral-400 hover:bg-white dark:hover:bg-neutral-900'"
                                        @click="activeTab = s.id"
                                    >
                                        <component :is="s.icon" class="w-5 h-5" />
                                        <span class="font-bold text-sm">{{ s.label }}</span>
                                        <ChevronRight v-if="activeTab !== s.id" class="w-4 h-4 ml-auto opacity-0 group-hover:opacity-100 transition-opacity" />
                                    </button>
                                </div>
                            </div>

                            <!-- Category: Perusahaan -->
                            <div>
                                <h4 class="text-xs font-bold text-slate-400 dark:text-neutral-500 uppercase tracking-normal mb-4 px-4">{{ t('info.for_companies') }}</h4>
                                <div class="space-y-1">
                                    <button 
                                        v-for="s in sections.filter(s => s.category === 'perusahaan')" 
                                        :key="s.id"
                                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all group"
                                        :class="activeTab === s.id ? 'bg-primary-600 text-white shadow-lg shadow-primary-600/20' : 'text-slate-600 dark:text-neutral-400 hover:bg-white dark:hover:bg-neutral-900'"
                                        @click="activeTab = s.id"
                                    >
                                        <component :is="s.icon" class="w-5 h-5" />
                                        <span class="font-bold text-sm">{{ s.label }}</span>
                                        <ChevronRight v-if="activeTab !== s.id" class="w-4 h-4 ml-auto opacity-0 group-hover:opacity-100 transition-opacity" />
                                    </button>
                                </div>
                            </div>

                            <!-- Category: Mahasiswa -->
                            <div>
                                <h4 class="text-xs font-bold text-slate-400 dark:text-neutral-500 uppercase tracking-normal mb-4 px-4">{{ t('info.for_students') }}</h4>
                                <div class="space-y-1">
                                    <button 
                                        v-for="s in sections.filter(s => s.category === 'mahasiswa')" 
                                        :key="s.id"
                                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all group"
                                        :class="activeTab === s.id ? 'bg-primary-600 text-white shadow-lg shadow-primary-600/20' : 'text-slate-600 dark:text-neutral-400 hover:bg-white dark:hover:bg-neutral-900'"
                                        @click="activeTab = s.id"
                                    >
                                        <component :is="s.icon" class="w-5 h-5" />
                                        <span class="font-bold text-sm">{{ s.label }}</span>
                                        <ChevronRight v-if="activeTab !== s.id" class="w-4 h-4 ml-auto opacity-0 group-hover:opacity-100 transition-opacity" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <!-- Main Content Area -->
                    <main class="flex-1 max-w-4xl">
                        <div class="bg-white dark:bg-neutral-900 rounded-2xl p-8 md:p-16 shadow-sm border border-slate-100 dark:border-neutral-800 transition-all duration-500 animate-in fade-in slide-in-from-bottom-4">
                            
                            <!-- Header -->
                            <div class="flex items-center gap-4 mb-10">
                                <div class="w-14 h-14 bg-primary-50 dark:bg-primary-900/20 rounded-2xl flex items-center justify-center text-primary-600">
                                    <component :is="currentSection?.icon" class="w-7 h-7" />
                                </div>
                                <div>
                                    <h1 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white tracking-tight">{{ currentSection?.label }}</h1>
                                    <p class="text-slate-500 dark:text-neutral-500 font-medium mt-1">{{ t('info.last_updated') }}: {{ lastUpdatedDate }}</p>
                                </div>
                            </div>

                            <!-- Content Sections -->
                            <div class="prose prose-slate dark:prose-invert max-w-none">
                                
                                <!-- Pusat Bantuan -->
                                <template v-if="activeTab === 'center'">
                                    <h3>{{ t('info.faq_title') }}</h3>
                                    <div class="space-y-6 not-prose mt-8">
                                        <div v-for="(faqItem, index) in faqs" :key="index" class="p-6 rounded-2xl border border-slate-100 dark:border-neutral-800 hover:border-primary-200 transition-colors">
                                            <h4 class="font-bold text-slate-900 dark:text-white mb-2 flex items-center gap-2">
                                                <div class="w-1.5 h-1.5 rounded-full bg-primary-600"></div>
                                                {{ faqItem.q }}
                                            </h4>
                                            <p class="text-sm text-slate-500 dark:text-neutral-400 leading-relaxed">
                                                {{ faqItem.a }}
                                            </p>
                                        </div>
                                    </div>
                                </template>

                                <!-- Hubungi Kami -->
                                <template v-if="activeTab === 'contact'">
                                    <p class="text-lg text-slate-600 dark:text-neutral-300 mb-10">{{ t('info.contact_desc') }}</p>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 not-prose">
                                        <div class="p-8 rounded-[2rem] bg-slate-50 dark:bg-neutral-800/50 border border-slate-100 dark:border-neutral-700 flex flex-col items-center text-center">
                                            <Mail class="w-10 h-10 text-primary-600 mb-4" />
                                            <h4 class="font-bold text-slate-900 dark:text-white">{{ t('info.email_support') }}</h4>
                                            <p class="text-sm text-slate-500 dark:text-neutral-400 mt-2">{{ supportEmail }}</p>
                                        </div>
                                        <div class="p-8 rounded-[2rem] bg-slate-50 dark:bg-neutral-800/50 border border-slate-100 dark:border-neutral-700 flex flex-col items-center text-center">
                                            <Phone class="w-10 h-10 text-emerald-600 mb-4" />
                                            <h4 class="font-bold text-slate-900 dark:text-white">{{ t('info.whatsapp') }}</h4>
                                            <p class="text-sm text-slate-500 dark:text-neutral-400 mt-2">{{ supportPhone }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-12 p-10 rounded-2xl bg-primary-600 text-white shadow-xl shadow-primary-600/20 not-prose">
                                        <h3 class="text-2xl font-bold mb-4 !text-white !mt-0">{{ t('info.office_title') }}</h3>
                                        <div class="flex items-start gap-4">
                                            <MapPin class="w-6 h-6 mt-1 !text-white shrink-0" />
                                            <p class="font-medium leading-relaxed !text-white !mb-0" v-html="officeAddress.replace(/\\n/g, '<br />')"></p>
                                        </div>
                                    </div>
                                </template>

                                <!-- Syarat & Ketentuan -->
                                <template v-if="activeTab === 'terms'">
                                    <p>{{ t('info.terms_intro') }}</p>
                                    <h3>{{ t('info.terms_h1') }}</h3>
                                    <p>{{ t('info.terms_p1') }}</p>
                                    <h3>{{ t('info.terms_h2') }}</h3>
                                    <p>{{ t('info.terms_p2') }}</p>
                                    <h3>{{ t('info.terms_h3') }}</h3>
                                    <p>{{ t('info.terms_p3') }}</p>
                                </template>

                                <!-- Kebijakan Privasi -->
                                <template v-if="activeTab === 'privacy'">
                                    <p>{{ t('info.privacy_intro') }}</p>
                                    <h3>{{ t('info.privacy_h1') }}</h3>
                                    <ul>
                                        <li>{{ t('info.privacy_l1') }}</li>
                                        <li>{{ t('info.privacy_l2') }}</li>
                                        <li>{{ t('info.privacy_l3') }}</li>
                                    </ul>
                                    <h3>{{ t('info.privacy_h2') }}</h3>
                                    <p>{{ t('info.privacy_p2') }}</p>
                                </template>

                                <!-- Sistem Seleksi -->
                                <template v-if="activeTab === 'selection-system'">
                                    <p>{{ t('info.selection_intro') }}</p>
                                    <div class="grid grid-cols-1 gap-8 mt-10 not-prose">
                                        <div
v-for="item in [
                                            { title: t('info.ai_scoring'), desc: t('info.ai_scoring_desc'), icon: Rocket },
                                            { title: t('info.kanban'), desc: t('info.kanban_desc'), icon: Users },
                                            { title: t('info.interview'), desc: t('info.interview_desc'), icon: Phone }
                                        ]" :key="item.title" class="flex gap-6 items-start">
                                            <div class="w-12 h-12 bg-white dark:bg-neutral-800 rounded-xl flex items-center justify-center border border-slate-100 dark:border-neutral-700 shrink-0 shadow-sm">
                                                <component :is="item.icon" class="w-6 h-6 text-primary-600" />
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-slate-900 dark:text-white">{{ item.title }}</h4>
                                                <p class="text-sm text-slate-500 dark:text-neutral-400 mt-1">{{ item.desc }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Employer Branding -->
                                <template v-if="activeTab === 'employer-branding'">
                                    <p>{{ t('info.branding_intro') }}</p>
                                    <h3>{{ t('info.branding_h1') }}</h3>
                                    <p>{{ t('info.branding_p1') }}</p>
                                    <h3>{{ t('info.branding_h2') }}</h3>
                                    <p>{{ t('info.branding_p2') }}</p>
                                </template>

                                <!-- Panduan CV -->
                                <template v-if="activeTab === 'cv-guide'">
                                    <div class="bg-amber-50 dark:bg-amber-900/20 p-8 rounded-[2rem] border border-amber-100 dark:border-amber-800 mb-10 not-prose">
                                        <h3 class="text-xl font-bold text-amber-900 dark:text-amber-400 flex items-center gap-2">
                                            <Lightbulb class="w-6 h-6" />
                                            {{ t('info.cv_tips_title') }}
                                        </h3>
                                        <p class="text-amber-800 dark:text-amber-500/80 mt-2 font-medium">{{ t('info.cv_tips_desc') }}</p>
                                    </div>
                                    <h3>{{ t('info.cv_guide_h1') }}</h3>
                                    <ol>
                                        <li>{{ t('info.cv_guide_l1') }}</li>
                                        <li>{{ t('info.cv_guide_l2') }}</li>
                                        <li>{{ t('info.cv_guide_l3') }}</li>
                                        <li>{{ t('info.cv_guide_l4') }}</li>
                                    </ol>
                                </template>

                                <!-- Default / Coming Soon -->
                                <template v-if="['enterprise', 'career-tips', 'campus-program'].includes(activeTab)">
                                    <div class="text-center py-20">
                                        <div class="w-20 h-20 bg-slate-50 dark:bg-neutral-800 rounded-full flex items-center justify-center mx-auto mb-6">
                                            <Rocket class="w-10 h-10 text-slate-300 animate-bounce" />
                                        </div>
                                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white">{{ t('info.coming_soon') }}</h3>
                                        <p class="text-slate-500 dark:text-neutral-400 max-w-md mx-auto mt-2">{{ t('info.coming_soon_desc') }}</p>
                                    </div>
                                </template>

                            </div>

                            <!-- Footer CTA within Page -->
                            <div class="mt-20 pt-10 border-t border-slate-100 dark:border-neutral-800 flex flex-col md:flex-row items-center justify-between gap-6">
                                <p class="text-slate-500 dark:text-neutral-500 text-sm font-medium">{{ t('info.need_help') }}</p>
                                <div class="flex gap-4">
                                    <button class="px-6 py-3 rounded-full bg-slate-900 dark:bg-white text-white dark:text-neutral-900 font-bold text-sm transition-transform hover:scale-105" @click="activeTab = 'contact'">{{ t('info.contact_cs') }}</button>
                                    <Link href="/" class="px-6 py-3 rounded-full border border-slate-200 dark:border-neutral-700 text-slate-600 dark:text-neutral-400 font-bold text-sm transition-transform hover:scale-105">{{ t('info.back_home') }}</Link>
                                </div>
                            </div>

                        </div>
                    </main>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<style scoped>
@reference "../../css/app.css";
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap');

:deep(h1), :deep(h2), :deep(h3), :deep(h4) {
    font-family: 'Outfit', sans-serif;
}

.prose h3 {
    @apply text-2xl font-bold text-slate-900 dark:text-white mt-12 mb-6 tracking-tight;
}

.prose p {
    @apply text-slate-600 dark:text-neutral-300 leading-relaxed mb-6;
}

.prose ul, .prose ol {
    @apply text-slate-600 dark:text-neutral-300 space-y-4 mb-8;
}

.prose li {
    @apply pl-2;
}
</style>

