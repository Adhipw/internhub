<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import api from '@/Services/api';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import { 
    ChevronLeft, FileText, Upload, CheckCircle2, 
    AlertCircle, Clock, Info, Download, Trash2
} from 'lucide-vue-next';
import { 
    IdentificationIcon, AcademicCapIcon, ClipboardDocumentCheckIcon 
} from '@heroicons/vue/24/outline';
import type { Application, ApplicationDocument } from '@/Types/application';
import StatusBadge from '@/Components/StatusBadge.vue';

interface OnboardingProps {
    application: Application;
    documents?: ApplicationDocument[];
}

const props = defineProps<OnboardingProps>();
const application = computed(() => props.application);
const documents = ref<ApplicationDocument[]>(props.documents || []);
const uploading = ref<string | null>(null);

const documentTypes = [
    { key: 'agreement', name: 'Signed Internship Agreement', description: 'Dokumen perjanjian magang yang sudah ditandatangani.', icon: ClipboardDocumentCheckIcon, hasTemplate: true },
    { key: 'ktp', name: 'Identity Card (KTP/KTM)', description: 'Kartu identitas resmi (KTP atau Kartu Tanda Mahasiswa).', icon: IdentificationIcon },
    { key: 'campus_letter', name: 'Campus Recommendation Letter', description: 'Surat pengantar atau rekomendasi dari kampus.', icon: AcademicCapIcon },
];

const getDocument = (type: string) => {
    return documents.value.find(doc => doc.type === type);
};

const handleFileUpload = async (event: any, type: string) => {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);
    formData.append('type', type);

    if (!application.value) return;
    uploading.value = type;
    try {
        await api.post(`/applications/${application.value.id}/onboarding/upload`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        const refreshed = await api.get(`/applications/${application.value.id}/onboarding`);
        documents.value = refreshed.data.data || [];
    } catch (error) {
        alert('Gagal mengunggah dokumen. Pastikan format file adalah PDF/JPG/PNG dan ukuran maksimal 5MB.');
    } finally {
        uploading.value = null;
    }
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'verified': return 'text-emerald-600 bg-emerald-50 border-emerald-100';
        case 'rejected': return 'text-rose-600 bg-rose-50 border-rose-100';
        default: return 'text-amber-600 bg-amber-50 border-amber-100';
    }
};
</script>

<template>
    <DashboardLayout>
        <div class="max-w-4xl mx-auto space-y-10 pb-20">
            <!-- Header -->
            <div class="flex items-center gap-6 pb-8 border-b border-slate-100 dark:border-slate-800">
                <Link :href="'/my-applications/' + application.id" class="p-3 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl hover:bg-slate-50 transition-all">
                    <ChevronLeft class="w-5 h-5 text-slate-400" />
                </Link>
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Administrasi Onboarding</h1>
                    <p class="text-sm text-slate-500 font-medium mt-1">Lengkapi dokumen berikut untuk memulai program magang Anda.</p>
                </div>
            </div>

            <!-- Premium Animated Warning/Info -->
            <div class="bg-gradient-to-r from-primary-600 to-primary-800 rounded-2xl p-10 text-white shadow-premium-xl relative overflow-hidden group animate-reveal">
                <div class="absolute inset-0 bg-gradient-to-tr from-transparent via-white/10 to-transparent animate-gradient-x opacity-50 pointer-events-none"></div>
                <Info class="w-32 h-32 absolute -right-6 -top-6 opacity-10 group-hover:scale-125 group-hover:rotate-12 transition-all duration-700 ease-out" />
                <div class="relative z-10 space-y-4">
                    <h3 class="text-2xl font-bold tracking-tight">Informasi Penting</h3>
                    <p class="text-sm font-medium text-white/90 leading-relaxed max-w-2xl">
                        Seluruh dokumen akan diverifikasi oleh tim HR dalam 1-3 hari kerja. Anda akan menerima notifikasi jika ada dokumen yang perlu diperbaiki. Magang baru dapat dimulai setelah seluruh dokumen berstatus <span class="font-bold text-white px-2 py-1 bg-white/20 rounded-lg backdrop-blur-md">Verified</span>.
                    </p>
                </div>
            </div>

            <!-- Documents List -->
            <div class="grid grid-cols-1 gap-6 relative z-10">
                <div v-for="(type, index) in documentTypes" :key="type.key" class="glass-premium rounded-2xl p-8 md:p-10 transition-all duration-500 hover:-translate-y-2 hover:shadow-premium-2xl group animate-reveal" :style="'animation-delay: ' + (index * 150) + 'ms'">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-8">
                        <div class="flex gap-6">
                            <div class="w-16 h-16 bg-white dark:bg-neutral-800 rounded-2xl flex items-center justify-center shrink-0 shadow-premium-sm group-hover:scale-110 transition-transform duration-500 group-hover:shadow-premium-md border border-neutral-100 dark:border-neutral-700">
                                <component :is="type.icon" class="w-8 h-8 text-primary-500 group-hover:-glow rounded-full" />
                            </div>
                            <div class="space-y-1">
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-primary-600 transition-colors">{{ type.name }}</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">{{ type.description }}</p>
                                
                                <a 
                                    v-if="type.hasTemplate" 
                                    :href="'/my-applications/' + application.id + '/agreement-template'" 
                                    target="_blank" 
                                    class="inline-flex mt-2 text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 text-sm font-bold items-center gap-1.5 transition-colors"
                                >
                                    <Download class="w-4 h-4" /> Unduh Template Perjanjian (Otomatis)
                                </a>
                                
                                <!-- Document Status Badge -->
                                <div v-if="getDocument(type.key)" class="inline-flex mt-3">
                                    <div :class="['px-3 py-1 rounded-full text-[10px] font-semibold text-xs tracking-wide border', getStatusColor(getDocument(type.key)?.status || 'pending')]">
                                        {{ getDocument(type.key)?.status }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Area -->
                        <div class="shrink-0 flex items-center gap-4">
                            <template v-if="getDocument(type.key)">
                                <div class="flex flex-col items-end gap-3">
                                    <div class="flex gap-2">
                                        <a 
                                            :href="'/api/v1/onboarding-documents/' + getDocument(type.key)?.id + '/download'" 
                                            class="p-3 bg-slate-50 dark:bg-slate-800 text-slate-500 rounded-xl hover:bg-primary-50 hover:text-primary-600 transition-all"
                                            title="Download"
                                        >
                                            <Download class="w-5 h-5" />
                                        </a>
                                        <label 
                                            v-if="getDocument(type.key)?.status !== 'verified'"
                                            class="p-3 bg-slate-50 dark:bg-slate-800 text-slate-500 rounded-xl hover:bg-orange-50 hover:text-orange-600 transition-all cursor-pointer"
                                            title="Re-upload"
                                        >
                                            <Upload class="w-5 h-5" />
                                            <input type="file" class="hidden" :disabled="!!uploading" @change="handleFileUpload($event, type.key)" />
                                        </label>
                                    </div>
                                    <p v-if="getDocument(type.key)?.status === 'rejected'" class="text-[10px] font-bold text-rose-500 max-w-[200px] text-right italic">
                                        Alasan: {{ getDocument(type.key)?.notes }}
                                    </p>
                                </div>
                            </template>
                            
                            <template v-else>
                                <label class="relative bg-slate-900 dark:bg-neutral-100 text-white dark:text-slate-900 px-8 py-4 rounded-2xl font-bold text-sm hover:bg-primary-600 dark:hover:bg-primary-500 hover:text-white transition-all duration-300 shadow-premium-md hover:shadow-premium-lg cursor-pointer flex items-center gap-2 overflow-hidden overflow-group">
                                    <div class="absolute inset-0 bg-white/20 group-hover:translate-x-full transition-transform duration-700 -skew-x-12 -translate-x-full"></div>
                                    <template v-if="uploading === type.key">
                                        <div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                                        Uploading...
                                    </template>
                                    <template v-else>
                                        <Upload class="w-5 h-5 group-hover:-translate-y-1 transition-transform" />
                                        Unggah Dokumen
                                    </template>
                                    <input type="file" class="hidden" :disabled="!!uploading" @change="handleFileUpload($event, type.key)" />
                                </label>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

