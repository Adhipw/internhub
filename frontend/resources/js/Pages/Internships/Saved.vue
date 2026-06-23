<script setup lang="ts">
import logger from '@/Lib/logger';
import { Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head } from '@/Components';
import { computed, ref } from 'vue';
import api from '@/Services/api';
import type { Internship } from '@/Types/internship';

import {
    Briefcase, MapPin, Trash2,
    Clock, ArrowUpRight, Bookmark
} from 'lucide-vue-next';

interface SavedInternship {
    id: number;
    internship: Internship;
    created_at_human?: string;
}

const props = defineProps<{
    savedInternships: SavedInternship[];
}>();

const savedInternships = ref<SavedInternship[]>(props.savedInternships || []);
const removingSlug = ref<string | null>(null);
const errorMessage = ref('');

const visibleSavedInternships = computed(() => savedInternships.value.filter(item => item.internship));

const removeSaved = async (slug: string) => {
    if (!slug || removingSlug.value) return;

    removingSlug.value = slug;
    errorMessage.value = '';

    try {
        await api.post(`/internships/${slug}/toggle-save`);
        savedInternships.value = savedInternships.value.filter(item => item.internship?.slug !== slug);
    } catch (error) {
        logger.error('Failed to remove saved internship:', error);
        errorMessage.value = 'Gagal menghapus lowongan tersimpan.';
    } finally {
        removingSlug.value = null;
    }
};
</script>

<template>
    <Head title="Internship Tersimpan - InternHub" />

    <DashboardLayout>
        <div class="max-w-5xl mx-auto space-y-10">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 mb-2">Peluang Tersimpan</h1>
                    <p class="text-slate-500">Daftar magang yang menarik perhatianmu dan siap untuk dilamar.</p>
                </div>
            </div>

            <div v-if="errorMessage" class="rounded-3xl border border-red-100 bg-red-50 p-5 text-sm font-bold text-red-600 dark:border-red-900/30 dark:bg-red-950/20 dark:text-red-300">
                {{ errorMessage }}
            </div>

            <div v-if="visibleSavedInternships.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-for="item in visibleSavedInternships" :key="item.id" class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm hover:shadow-md transition-all group flex flex-col justify-between dark:bg-slate-900 dark:border-slate-800">
                    <div>
                        <div class="flex items-start justify-between mb-6">
                            <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center border border-slate-50 overflow-hidden shadow-inner dark:bg-slate-950 dark:border-slate-800">
                                <img v-if="item.internship.company?.logo_url" loading="lazy" decoding="async" :src="item.internship.company.logo_url" class="w-full h-full object-cover" />
                                <Briefcase v-else class="w-8 h-8 text-slate-200" />
                            </div>
                            <button :disabled="removingSlug === item.internship.slug" class="p-3 text-slate-300 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all disabled:opacity-50 dark:hover:bg-red-950/30" @click="removeSaved(item.internship.slug)">
                                <Trash2 class="w-5 h-5" />
                            </button>
                        </div>

                        <h3 class="text-xl font-bold text-slate-900 mb-2 group-hover:text-primary-600 transition-colors dark:text-white">
                            <Link :href="`/internships/${item.internship.slug}`">{{ item.internship.title }}</Link>
                        </h3>
                        <p class="text-sm font-bold text-primary-600 mb-6">{{ item.internship.company?.name }}</p>

                        <div class="space-y-3 mb-8">
                            <div class="flex items-center gap-3 text-xs font-semibold text-slate-500">
                                <MapPin class="w-4 h-4 text-slate-400" />
                                {{ item.internship.location }}
                            </div>
                            <div class="flex items-center gap-3 text-xs font-semibold text-slate-500">
                                <Clock class="w-4 h-4 text-slate-400" />
                                {{ item.internship.type }}
                            </div>
                        </div>
                    </div>

                    <Link
                        :href="`/internships/${item.internship.slug}`"
                        class="w-full bg-slate-900 text-white py-4 rounded-2xl font-bold text-sm hover:bg-slate-800 transition-all flex items-center justify-center gap-2"
                    >
                        Lihat & Lamar
                        <ArrowUpRight class="w-4 h-4" />
                    </Link>
                </div>
            </div>

            <div v-else class="bg-white border border-slate-100 rounded-[3rem] p-20 text-center shadow-sm dark:bg-slate-900 dark:border-slate-800">
                <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-8">
                    <Bookmark class="w-10 h-10 text-slate-200" />
                </div>
                <h3 class="text-2xl font-bold text-slate-900 mb-4 dark:text-white">Belum ada peluang tersimpan</h3>
                <p class="text-slate-500 max-w-md mx-auto mb-10">Gunakan fitur simpan (ikon bookmark) pada daftar magang untuk menyimpan peluang yang kamu sukai.</p>
                <Link
                    href="/internships"
                    class="inline-flex items-center gap-2 bg-primary-600 text-white px-10 py-4 rounded-full font-bold text-sm hover:bg-primary-700 transition-all shadow-xl shadow-primary-100"
                >
                    Jelajahi Magang
                    <ArrowUpRight class="w-4 h-4" />
                </Link>
            </div>
        </div>
    </DashboardLayout>
</template>
