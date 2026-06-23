<script setup lang="ts">
import { computed } from 'vue';
import { router as inertiaRouter } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { 
    Building2, Globe, MapPin, Briefcase, 
    ArrowLeft, CheckCircle2, ShieldCheck, Mail
} from 'lucide-vue-next';
import Card from '@/Components/Card.vue';
import Button from '@/Components/Button.vue';
import Badge from '@/Components/Badge.vue';
import type { Company, Internship } from '@/Types/internship';

interface CompanyShowProps {
    company: Company;
    internships: { data: Internship[] } | Internship[];
}

const props = defineProps<CompanyShowProps>();

const company = computed(() => props.company);
const internships = computed(() => Array.isArray(props.internships) ? props.internships : props.internships.data || []);
</script>

<template>
    <PublicLayout>
        <div class="bg-neutral-50 dark:bg-neutral-950 min-h-screen pt-32 pb-32">
            <div class="container mx-auto px-6 max-w-6xl">
                <!-- Header Card -->
                <div class="bg-white dark:bg-neutral-900 rounded-[3rem] p-10 md:p-16 border border-neutral-100 dark:border-neutral-800 shadow-2xl shadow-neutral-200/50 dark:shadow-none mb-16">
                    <div class="flex flex-col md:flex-row items-center md:items-start gap-12">
                        <div class="w-32 h-32 bg-neutral-50 dark:bg-neutral-950 rounded-[2.5rem] flex items-center justify-center border border-neutral-100 dark:border-neutral-800 shadow-sm shrink-0 overflow-hidden">
                            <img v-if="company.logo_url" loading="lazy" decoding="async" :src="company.logo_url" class="w-full h-full object-contain p-6" />
                            <Building2 v-else class="w-16 h-16 text-neutral-300" />
                        </div>
                        
                        <div class="flex-1 text-center md:text-left">
                            <div class="flex flex-wrap items-center justify-center md:justify-start gap-4 mb-4">
                                <h1 class="text-4xl font-extrabold text-neutral-900 dark:text-white tracking-tight">{{ company.name }}</h1>
                                <div v-if="company.is_verified" class="flex items-center gap-2 px-3 py-1 bg-emerald-50 text-emerald-600 rounded-full border border-emerald-100 text-[10px] font-black uppercase tracking-widest">
                                    <ShieldCheck class="w-4 h-4" />
                                    Terverifikasi
                                </div>
                            </div>
                            <p class="text-lg font-bold text-primary-600 mb-6">{{ company.industry || 'Teknologi & Inovasi' }}</p>
                            
                            <div class="flex flex-wrap justify-center md:justify-start gap-8 text-neutral-500 font-bold text-sm">
                                <div class="flex items-center gap-2">
                                    <MapPin class="w-5 h-5 text-neutral-400" />
                                    {{ company.location || 'Indonesia' }}
                                </div>
                                <div v-if="company.website" class="flex items-center gap-2">
                                    <Globe class="w-5 h-5 text-neutral-400" />
                                    <a :href="company.website" target="_blank" class="hover:text-primary-600 transition-colors">{{ company.website }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                    <!-- About -->
                    <div class="lg:col-span-2 space-y-12">
                        <section class="px-8">
                            <h2 class="text-2xl font-black text-neutral-900 dark:text-white mb-6 flex items-center gap-3">
                                <div class="w-1.5 h-8 bg-primary-600 rounded-full"></div>
                                Tentang Perusahaan
                            </h2>
                            <p class="text-neutral-500 dark:text-neutral-400 font-medium leading-relaxed">
                                {{ company.description || 'Kami adalah perusahaan yang berkomitmen untuk memberdayakan talenta muda Indonesia melalui program magang yang terstruktur dan berkualitas. Di sini, Anda akan belajar langsung dari para ahli di industri.' }}
                            </p>
                        </section>

                        <section class="px-8">
                            <h2 class="text-2xl font-black text-neutral-900 dark:text-white mb-8 flex items-center gap-3">
                                <div class="w-1.5 h-8 bg-primary-600 rounded-full"></div>
                                Lowongan Aktif
                            </h2>
                            
                            <div v-if="internships.length > 0" class="space-y-6">
                                <Card 
                                    v-for="internship in internships" 
                                    :key="internship.id"
                                    hoverable
                                    padding="md"
                                    class="group cursor-pointer"
                                    @click="inertiaRouter.visit('/internships/' + internship.slug)"
                                >
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <Badge variant="primary" size="sm" class="mb-3">{{ internship.type }}</Badge>
                                            <h3 class="text-xl font-bold text-neutral-900 dark:text-white group-hover:text-primary-600 transition-colors">{{ internship.title }}</h3>
                                            <p class="text-sm font-bold text-neutral-400 mt-1">{{ internship.location }}</p>
                                        </div>
                                        <div class="w-12 h-12 bg-neutral-50 dark:bg-neutral-800 rounded-full flex items-center justify-center group-hover:bg-primary-600 group-hover:text-white transition-all">
                                            <ArrowLeft class="w-6 h-6 rotate-180" />
                                        </div>
                                    </div>
                                </Card>
                            </div>
                            <div v-else class="p-16 text-center bg-white dark:bg-neutral-900 rounded-[3rem] border-2 border-dashed border-neutral-100 dark:border-neutral-800">
                                <p class="text-neutral-400 font-bold uppercase tracking-widest">Belum ada lowongan aktif saat ini.</p>
                            </div>
                        </section>
                    </div>

                    <!-- Sidebar Stats -->
                    <aside class="space-y-12">
                        <div class="bg-neutral-900 dark:bg-white p-10 rounded-[3rem] text-white dark:text-neutral-900 shadow-xl">
                            <h3 class="text-xs font-black uppercase tracking-[0.2em] text-primary-400 mb-8">Statistik Magang</h3>
                            <div class="space-y-8">
                                <div>
                                    <p class="text-4xl font-black mb-1">{{ internships.length }}</p>
                                    <p class="text-xs font-bold opacity-60">Lowongan Aktif</p>
                                </div>
                                <div class="pt-8 border-t border-white/10 dark:border-neutral-100">
                                    <p class="text-4xl font-black mb-1">45+</p>
                                    <p class="text-xs font-bold opacity-60">Alumni Magang</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-8 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/50 rounded-[2.5rem] flex items-center gap-4">
                            <ShieldCheck class="w-8 h-8 text-emerald-500" />
                            <p class="text-xs font-bold text-emerald-700 dark:text-emerald-400 leading-relaxed">Perusahaan ini telah diverifikasi oleh tim kepatuhan InternHub.</p>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
