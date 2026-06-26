<script setup lang="ts">
import { Link, router as inertiaRouter } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Building2, ArrowRight, CheckCircle2, MapPin, Briefcase, Loader2 } from 'lucide-vue-next';
import type { Company } from '@/Types/internship';
import type { PaginatedResponse } from '@/Types/user';

interface CompaniesIndexProps {
    companies?: PaginatedResponse<Company>;
}

const props = defineProps<CompaniesIndexProps>();

const companies = computed(() => props.companies || { data: [], links: [], meta: {} as any });
const loading = computed(() => !props.companies);

const t = (key: string) => key;
</script>

<template>
    <PublicLayout>
        <!-- Hero Section -->
        <section class="relative pt-32 pb-20 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-b from-primary-50/50 to-transparent dark:from-primary-950/20 pointer-events-none"></div>
            <div class="container mx-auto px-6 relative z-10">
                <div class="max-w-3xl">
                    <h1 class="text-4xl md:text-5xl font-bold text-neutral-900 dark:text-white leading-tight mb-6">
                        Bekerja di Perusahaan <br/>
                        <span class="text-primary-600">Terbaik di Indonesia.</span>
                    </h1>
                    <p class="text-lg text-neutral-600 dark:text-neutral-400 leading-relaxed">
                        Temukan berbagai perusahaan mitra kami yang sedang mencari talenta magang terbaik. Mulai karir profesionalmu bersama pemimpin industri.
                    </p>
                </div>
            </div>
        </section>

        <!-- Company List -->
        <section class="pb-32">
            <div class="container mx-auto px-6">
                <div v-if="loading" class="flex justify-center py-20">
                    <Loader2 class="animate-spin h-12 w-12 text-primary-600" />
                </div>
                
                <div v-else-if="companies.data && companies.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div 
                        v-for="company in companies.data" 
                        :key="company.id"
                        class="group bg-white dark:bg-neutral-900 rounded-2xl p-8 border border-neutral-100 dark:border-white/5 hover:border-primary-200 dark:hover:border-primary-900/30 transition-all duration-500 hover:shadow-2xl hover:shadow-primary-600/5 relative overflow-hidden"
                    >
                        <!-- Hover Decoration -->
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-primary-600/5 rounded-full blur-2xl group-hover:bg-primary-600/10 transition-all"></div>

                        <div class="flex items-start justify-between mb-8">
                            <div class="w-16 h-16 rounded-2xl bg-neutral-50 dark:bg-neutral-800 p-2 flex items-center justify-center border border-neutral-100 dark:border-white/5 shadow-sm group-hover:scale-110 transition-transform duration-500">
                                <img v-if="company.logo_url" loading="lazy" decoding="async" :src="company.logo_url" :alt="company.name" class="w-full h-full object-contain" />
                                <Building2 v-else class="w-8 h-8 text-neutral-300" />
                            </div>
                            <div v-if="company.is_verified" class="flex items-center gap-1.5 px-3 py-1.2 rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 text-[10px] font-semibold text-xs tracking-wide border border-blue-100 dark:border-blue-900/30">
                                <CheckCircle2 class="w-3 h-3" />
                                Verified
                            </div>
                        </div>

                        <h3 class="text-xl font-bold text-neutral-900 dark:text-white mb-2 group-hover:text-primary-600 transition-colors">
                            {{ company.name }}
                        </h3>
                        
                        <div class="flex items-center gap-4 text-sm text-neutral-500 dark:text-neutral-400 mb-6">
                            <div class="flex items-center gap-1.5">
                                <MapPin class="w-4 h-4 text-primary-500" />
                                {{ company.location || 'Indonesia' }}
                            </div>
                            <div class="flex items-center gap-1.5">
                                <Briefcase class="w-4 h-4 text-primary-500" />
                                {{ company.internships_count }} Lowongan
                            </div>
                        </div>

                        <p class="text-sm text-neutral-500 dark:text-neutral-400 leading-relaxed mb-8 line-clamp-2">
                            {{ company.description || 'Tidak ada deskripsi tersedia.' }}
                        </p>

                        <Link 
                            :href="'/companies/' + company.slug"
                            class="inline-flex items-center gap-2 text-sm font-bold text-primary-600 hover:gap-3 transition-all"
                        >
                            Lihat Profil & Lowongan
                            <ArrowRight class="w-4 h-4" />
                        </Link>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-20 bg-white dark:bg-neutral-900 rounded-2xl border border-dashed border-neutral-200 dark:border-white/10">
                    <div class="w-20 h-20 bg-neutral-50 dark:bg-neutral-800 rounded-full flex items-center justify-center mx-auto mb-6">
                        <Building2 class="w-10 h-10 text-neutral-300" />
                    </div>
                    <h3 class="text-xl font-bold text-neutral-900 dark:text-white mb-2">Belum ada perusahaan</h3>
                    <p class="text-neutral-500 dark:text-neutral-400">Silakan cek kembali nanti untuk daftar perusahaan terbaru.</p>
                </div>

                <!-- Pagination -->
                <div v-if="companies.links && companies.links.length > 3" class="mt-16 flex justify-center gap-2">
                    <button 
                        v-for="(link, k) in companies.links" 
                        :key="k"
                        class="px-4 py-2 rounded-xl text-sm font-bold transition-all"
                        :class="[
                            link.active ? 'bg-primary-600 text-white shadow-lg shadow-primary-600/20' : 'bg-white dark:bg-neutral-900 text-neutral-500 hover:bg-primary-50 dark:hover:bg-primary-900/30',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                        @click="link.url ? inertiaRouter.get(link.url, {}, { preserveScroll: true, preserveState: true, replace: true }) : null"
                        v-html="link.label"
                    />
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

