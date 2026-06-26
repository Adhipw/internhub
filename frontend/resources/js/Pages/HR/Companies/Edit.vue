<script setup lang="ts">
import logger from '@/Lib/logger';
import { ref, reactive, onMounted } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import Skeleton from '@/Components/Skeleton.vue';
import { 
    Building2, Globe, MapPin, 
    Save, Loader2, AlertCircle, CheckCircle2,
    ChevronRight, Camera, FileText
} from 'lucide-vue-next';
import api from '@/Services/api';
import { useAuthStore } from '@/Stores/auth';

import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
    company?: any;
}>();

const authStore = useAuthStore();
const loading = ref(false);
const processing = ref(false);
const successMessage = ref('');
const errors = reactive<any>({});

const form = useForm({
    name: props.company?.name || '',
    website: props.company?.website || '',
    location: props.company?.location || '',
    description: props.company?.description || '',
    logo_url: props.company?.logo_url || '',
    industry: props.company?.industry || '',
});

const submit = () => {
    form.put('/hr/company', {
        preserveScroll: true,
        onSuccess: () => {
            successMessage.value = 'Profil perusahaan berhasil diperbarui!';
            setTimeout(() => { successMessage.value = ''; }, 5000);
        }
    });
};
</script>

<template>
    <DashboardLayout>
        <div class="max-w-5xl mx-auto space-y-10 pb-20">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 animate-fade-in">
                <div class="space-y-4">
                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-primary-50 dark:bg-primary-900/30 text-primary-600 rounded-full text-[10px] font-semibold text-xs tracking-wide">
                        <Building2 class="w-3.5 h-3.5" />
                        Manajemen Perusahaan
                    </div>
                    <h1 class="text-4xl font-bold text-slate-900 dark:text-white tracking-tight">Profil Perusahaan</h1>
                    <p class="text-slate-500 dark:text-slate-400 font-medium text-lg">Perbarui informasi publik perusahaan Anda untuk menarik talenta terbaik.</p>
                </div>
                
                <button 
                    :disabled="processing || loading"
                    class="bg-primary-600 text-white px-10 py-4 rounded-2xl font-bold text-sm hover:bg-primary-700 disabled:opacity-50 transition-all shadow-xl shadow-primary-900/20 flex items-center gap-3 active-press"
                    @click="submit"
                >
                    <Loader2 v-if="processing" class="w-5 h-5 animate-spin" />
                    <Save v-else class="w-5 h-5" />
                    Simpan Perubahan
                </button>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="space-y-8">
                <Skeleton height="200px" class="rounded-2xl" />
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <Skeleton height="100px" class="rounded-2xl" />
                    <Skeleton height="100px" class="rounded-2xl" />
                </div>
            </div>

            <!-- Form Content -->
            <div v-else class="space-y-12 animate-slide-up">
                <!-- Success Alert -->
                <div v-if="successMessage" class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 p-6 rounded-2xl flex items-center gap-4 animate-reveal">
                    <CheckCircle2 class="w-6 h-6 text-emerald-500" />
                    <p class="text-sm font-bold text-emerald-700 dark:text-emerald-400">{{ successMessage }}</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                    <!-- Identity Section -->
                    <div class="lg:col-span-2 space-y-10">
                        <Card class="!p-10 !rounded-2xl border-slate-100 dark:border-white/5 shadow-premium">
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-8 flex items-center gap-2">
                                <Building2 class="w-4 h-4" />
                                Identitas & Brand
                            </h3>

                            <div class="space-y-8">
                                <div class="flex flex-col md:flex-row items-center gap-8 bg-slate-50 dark:bg-slate-800/50 p-8 rounded-2xl border border-slate-100 dark:border-white/5">
                                    <div class="relative group">
                                        <div class="w-32 h-32 bg-white dark:bg-slate-900 rounded-[2rem] border-4 border-white dark:border-slate-800 shadow-xl overflow-hidden flex items-center justify-center text-slate-200">
                                            <img v-if="form.logo_url" loading="lazy" decoding="async" :src="form.logo_url" class="w-full h-full object-cover" />
                                            <Building2 v-else class="w-16 h-16" />
                                        </div>
                                        <div class="absolute inset-0 bg-black/40 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer rounded-[2rem]">
                                            <Camera class="w-8 h-8" />
                                        </div>
                                    </div>
                                    <div class="flex-1 text-center md:text-left">
                                        <h4 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Logo Perusahaan</h4>
                                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4 font-medium">Format URL gambar (PNG/JPG). Pastikan logo memiliki kontras yang baik.</p>
                                        <input 
                                            v-model="form.logo_url" 
                                            type="text" 
                                            placeholder="https://example.com/logo.png" 
                                            class="w-full bg-white dark:bg-slate-900 border-none rounded-xl px-4 py-3 text-xs font-bold focus:ring-2 focus:ring-primary-500 shadow-sm"
                                        />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="space-y-3">
                                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest ml-1">Nama Perusahaan</label>
                                        <input v-model="form.name" type="text" class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-2xl px-6 py-4 text-sm font-bold focus:ring-2 focus:ring-primary-500 shadow-inner" />
                                        <p v-if="form.errors.name" class="text-xs text-red-500 font-bold">{{ form.errors.name }}</p>
                                    </div>
                                    <div class="space-y-3">
                                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest ml-1">Industri</label>
                                        <input v-model="form.industry" type="text" placeholder="Contoh: Teknologi, Perbankan..." class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-2xl px-6 py-4 text-sm font-bold focus:ring-2 focus:ring-primary-500 shadow-inner" />
                                    </div>
                                </div>
                            </div>
                        </Card>

                        <Card class="!p-10 !rounded-2xl border-slate-100 dark:border-white/5 shadow-premium">
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-8 flex items-center gap-2">
                                <Globe class="w-4 h-4" />
                                Lokasi & Kontak
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-3">
                                    <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest ml-1">Website</label>
                                    <input v-model="form.website" type="text" placeholder="https://..." class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-2xl px-6 py-4 text-sm font-bold focus:ring-2 focus:ring-primary-500 shadow-inner" />
                                </div>
                                <div class="space-y-3">
                                    <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest ml-1">Alamat Utama</label>
                                    <input v-model="form.location" type="text" placeholder="Jakarta, Indonesia" class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-2xl px-6 py-4 text-sm font-bold focus:ring-2 focus:ring-primary-500 shadow-inner" />
                                </div>
                            </div>
                        </Card>
                    </div>

                    <!-- Description Section -->
                    <div class="lg:col-span-1 space-y-10">
                        <Card class="!p-10 !rounded-2xl border-slate-100 dark:border-white/5 shadow-premium h-full">
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-8 flex items-center gap-2">
                                <FileText class="w-4 h-4" />
                                Tentang Perusahaan
                            </h3>
                            
                            <div class="space-y-3">
                                <label class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest ml-1">Deskripsi Publik</label>
                                <textarea 
                                    v-model="form.description" 
                                    rows="15" 
                                    placeholder="Ceritakan sejarah, visi, dan misi perusahaan Anda..." 
                                    class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-[2rem] px-6 py-8 text-sm font-medium focus:ring-2 focus:ring-primary-500 shadow-inner resize-none leading-relaxed"
                                ></textarea>
                                <p v-if="form.errors.description" class="text-xs text-red-500 font-bold">{{ form.errors.description }}</p>
                            </div>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.shadow-premium {
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.03);
}

.animate-reveal {
    animation: reveal 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes reveal {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

.active-press:active {
    transform: scale(0.95);
}
</style>
