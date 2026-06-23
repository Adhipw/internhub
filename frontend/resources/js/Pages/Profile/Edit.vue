<script setup lang="ts">
import logger from '@/Lib/logger';
import { ref, reactive, onMounted, computed } from 'vue';
import { useAuthStore } from '@/Stores/auth';
import { useToastStore } from '@/Stores/toast';
import api from '@/Services/api';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { 
    User, GraduationCap, Code2, FileUp, Save, 
    Plus, Trash2, CheckCircle2, AlertCircle, Loader2, X, Camera, Sparkles, Key, Eye, EyeOff
} from 'lucide-vue-next';
import FileUpload from '@/Components/FileUpload.vue';
import Breadcrumbs from '@/Components/Breadcrumbs.vue';

const props = defineProps<{
    userDetail?: any;
}>();

const authStore = useAuthStore();
const toast = useToastStore();
const activeTab = ref('biodata');
const processing = ref(false);
const userDetail = computed(() => props.userDetail || {});
const errors = reactive<any>({});
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const tabs = computed(() => {
    const isStudent = !authStore.isAdmin && !authStore.isSuperAdmin && !authStore.isHR;
    
    const baseTabs = [
        { id: 'biodata', name: 'Biodata', icon: User },
        { id: 'security', name: 'Keamanan', icon: Key },
    ];

    if (isStudent) {
        baseTabs.push(
            { id: 'education', name: 'Pendidikan', icon: GraduationCap },
            { id: 'skills', name: 'Keahlian', icon: Code2 },
            { id: 'files', name: 'Dokumen', icon: FileUp }
        );
    }

    baseTabs.push({ id: 'privacy', name: 'AI & Privasi', icon: CheckCircle2 });
    
    return baseTabs;
});

const form = reactive({
    avatar: null,
    avatar_preview: null as string | null,
    bio: '',
    phone_number: '',
    address: '',
    education: [] as any[],
    skills: [] as string[],
    cv: null,
    portfolio: null,
    ai_consent: false,
    // Password change fields
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
});

        // Sync form with fetched data
        form.bio = userDetail.value?.bio || '';
        form.phone_number = authStore.user?.phone_number || '';
        form.address = userDetail.value?.address || '';
        form.education = userDetail.value?.education || [];
        form.skills = userDetail.value?.skills || [];
        form.ai_consent = !!userDetail.value?.ai_consent;

onMounted(() => {
    // Initial sync
});

const addEducation = () => {
    form.education.push({
        school: '',
        degree: '',
        field: '',
        start_year: new Date().getFullYear(),
        end_year: null,
    });
};

const removeEducation = (index: number) => {
    form.education.splice(index, 1);
};

const newSkill = ref('');
const addSkill = () => {
    if (newSkill.value.trim() && !form.skills.includes(newSkill.value.trim())) {
        form.skills.push(newSkill.value.trim());
        newSkill.value = '';
    }
};

const removeSkill = (index: number) => {
    form.skills.splice(index, 1);
};

const submit = async () => {
    processing.value = true;
    Object.keys(errors).forEach(key => delete errors[key]);

    const formData = new FormData();
    if (form.avatar) formData.append('avatar', form.avatar);
    formData.append('bio', form.bio);
    formData.append('phone_number', form.phone_number);
    formData.append('address', form.address);
    formData.append('education', JSON.stringify(form.education));
    formData.append('skills', JSON.stringify(form.skills));
    if (form.cv) formData.append('cv', form.cv);
    if (form.portfolio) formData.append('portfolio', form.portfolio);
    formData.append('ai_consent', form.ai_consent ? '1' : '0');

    try {
        await api.post('/profile', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        toast.success('Profil berhasil diperbarui!');
        // Page reload to get new props
        window.location.reload();
    } catch (error: any) {
        if (error.response?.data?.errors) {
            Object.assign(errors, error.response.data.errors);
        }
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <DashboardLayout>
        <div class="max-w-5xl mx-auto space-y-10 pb-20">
            <!-- Breadcrumbs -->
            <Breadcrumbs
:items="[
                { label: 'Profil Saya' }
            ]" />

            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Profil Lengkap</h1>
                    <p v-if="!authStore.isHR && !authStore.isAdmin" class="text-slate-500 dark:text-slate-400">Informasi ini akan dilihat oleh perusahaan saat kamu melamar.</p>
                    <p v-else class="text-slate-500 dark:text-slate-400">Kelola informasi pribadi dan pengaturan akun Anda.</p>
                </div>
                <button 
                    :disabled="processing"
                    class="bg-primary-600 text-white px-8 py-4 rounded-2xl font-bold text-sm hover:bg-primary-700 disabled:opacity-50 transition-all shadow-xl shadow-primary-200 flex items-center gap-2"
                    @click="submit"
                >
                    <Loader2 v-if="processing" class="w-4 h-4 animate-spin" />
                    <Save v-else class="w-4 h-4" />
                    Simpan Perubahan
                </button>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-10">
                <!-- Sidebar Tabs -->
                <div class="lg:col-span-1 space-y-2">
                    <button 
                        v-for="tab in tabs" 
                        :key="tab.id"
                        class="w-full flex items-center gap-3 px-6 py-4 rounded-2xl font-bold text-sm transition-all"
                        :class="activeTab === tab.id ? 'bg-primary-600 text-white shadow-lg shadow-primary-500/20' : 'text-slate-500 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white'"
                        @click="activeTab = tab.id"
                    >
                        <component :is="tab.icon" class="w-5 h-5" />
                        {{ tab.name }}
                    </button>
                </div>

                <!-- Content Area -->
                <div class="lg:col-span-3 bg-white dark:bg-slate-900 rounded-[2.5rem] p-10 border border-slate-100 dark:border-slate-800 shadow-sm">
                    
                    <!-- Biodata Tab -->
                    <div v-if="activeTab === 'biodata'" class="space-y-8">
                        <!-- Avatar Upload -->
                        <div class="flex flex-col md:flex-row items-center gap-8 bg-slate-50 dark:bg-slate-800/50 p-8 rounded-3xl border border-slate-100 dark:border-slate-700">
                            <div class="relative group">
                                <div class="w-32 h-32 bg-white dark:bg-slate-800 rounded-full border-4 border-white dark:border-slate-700 shadow-xl overflow-hidden flex items-center justify-center text-slate-200 dark:text-slate-600">
                                    <img v-if="form.avatar_preview || authStore.user?.avatar_url" loading="lazy" decoding="async" :src="form.avatar_preview || authStore.user?.avatar_url" class="w-full h-full object-cover" />
                                    <User v-else class="w-16 h-16" />
                                </div>
                                <label class="absolute inset-0 bg-black/40 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer rounded-full">
                                    <input
type="file" class="hidden" accept="image/*" @change="(e: Event) => {
                                        const target = e.target as HTMLInputElement;
                                        const file = target.files?.[0];
                                        if (file) {
                                            (form.avatar as any) = file;
                                            form.avatar_preview = window.URL.createObjectURL(file);
                                        }
                                    }" />
                                    <Camera class="w-8 h-8" />
                                </label>
                            </div>
                            <div class="flex-1 text-center md:text-left">
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Foto Profil</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Gunakan foto profesional untuk meningkatkan peluang diterima.</p>
                                <p v-if="errors.avatar" class="text-xs text-red-500 font-bold mb-2">{{ errors.avatar }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-slate-700 dark:text-slate-300">Nomor WhatsApp</label>
                                <input 
                                    v-model="form.phone_number" 
                                    type="text" 
                                    placeholder="0812..." 
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl px-6 py-4 text-sm font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition-all"
                                />
                                <p v-if="errors.phone_number" class="text-xs text-red-500 mt-1">{{ errors.phone_number }}</p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-slate-700 dark:text-slate-300">Alamat Lengkap</label>
                                <input 
                                    v-model="form.address" 
                                    type="text" 
                                    placeholder="Jl. Raya No. 123..." 
                                    class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl px-6 py-4 text-sm font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition-all"
                                />
                                <p v-if="errors.address" class="text-xs text-red-500 mt-1">{{ errors.address }}</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-bold text-slate-700 dark:text-slate-300">Bio Singkat</label>
                            <textarea 
                                v-model="form.bio" 
                                rows="5" 
                                :placeholder="!authStore.isHR ? 'Ceritakan sedikit tentang dirimu dan aspirasimu...' : 'Tuliskan deskripsi singkat mengenai peran Anda di perusahaan ini...'" 
                                class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl px-6 py-4 text-sm font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition-all resize-none"
                            ></textarea>
                            <p v-if="errors.bio" class="text-xs text-red-500 mt-1">{{ errors.bio }}</p>
                        </div>
                    </div>

                    <!-- Security Tab -->
                    <div v-if="activeTab === 'security'" class="space-y-10">
                        <div class="bg-amber-50 dark:bg-amber-900/10 p-6 rounded-2xl border border-amber-100 dark:border-amber-900/30 flex gap-4">
                            <AlertCircle class="w-5 h-5 text-amber-500 shrink-0" />
                            <p class="text-xs text-amber-700 dark:text-amber-400 leading-relaxed">
                                <strong>Keamanan Akun:</strong> Gunakan kombinasi kata sandi yang kuat dengan minimal 8 karakter yang mengandung huruf, angka, dan simbol.
                            </p>
                        </div>

                        <div class="space-y-6">
                            <div class="space-y-2">
                                <label class="text-sm font-bold text-slate-700 dark:text-slate-300">Kata Sandi Saat Ini</label>
                                <div class="relative">
                                    <input
                                        v-model="form.current_password"
                                        :type="showCurrentPassword ? 'text' : 'password'"
                                        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl px-6 py-4 pr-14 text-sm font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition-all"
                                    />
                                    <button
                                        type="button"
                                        class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
                                        :aria-label="showCurrentPassword ? 'Sembunyikan kata sandi saat ini' : 'Tampilkan kata sandi saat ini'"
                                        @click="showCurrentPassword = !showCurrentPassword"
                                    >
                                        <Eye v-if="!showCurrentPassword" class="w-5 h-5" />
                                        <EyeOff v-else class="w-5 h-5" />
                                    </button>
                                </div>
                                <p v-if="errors.current_password" class="text-xs text-red-500 mt-1">{{ errors.current_password[0] }}</p>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-bold text-slate-700 dark:text-slate-300">Kata Sandi Baru</label>
                                <div class="relative">
                                    <input
                                        v-model="form.new_password"
                                        :type="showNewPassword ? 'text' : 'password'"
                                        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl px-6 py-4 pr-14 text-sm font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition-all"
                                    />
                                    <button
                                        type="button"
                                        class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
                                        :aria-label="showNewPassword ? 'Sembunyikan kata sandi baru' : 'Tampilkan kata sandi baru'"
                                        @click="showNewPassword = !showNewPassword"
                                    >
                                        <Eye v-if="!showNewPassword" class="w-5 h-5" />
                                        <EyeOff v-else class="w-5 h-5" />
                                    </button>
                                </div>
                                <p v-if="errors.new_password" class="text-xs text-red-500 mt-1">{{ errors.new_password[0] }}</p>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-bold text-slate-700 dark:text-slate-300">Konfirmasi Kata Sandi Baru</label>
                                <div class="relative">
                                    <input
                                        v-model="form.new_password_confirmation"
                                        :type="showConfirmPassword ? 'text' : 'password'"
                                        class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl px-6 py-4 pr-14 text-sm font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition-all"
                                    />
                                    <button
                                        type="button"
                                        class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
                                        :aria-label="showConfirmPassword ? 'Sembunyikan konfirmasi kata sandi' : 'Tampilkan konfirmasi kata sandi'"
                                        @click="showConfirmPassword = !showConfirmPassword"
                                    >
                                        <Eye v-if="!showConfirmPassword" class="w-5 h-5" />
                                        <EyeOff v-else class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Education Tab -->
                    <div v-if="activeTab === 'education'" class="space-y-8">
                        <div v-if="form.education.length === 0" class="bg-slate-50 dark:bg-slate-800/50 border border-dashed border-slate-200 dark:border-slate-700 rounded-[2rem] p-12 text-center">
                            <div class="w-16 h-16 bg-white dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm text-slate-300 dark:text-slate-600">
                                <GraduationCap class="w-8 h-8" />
                            </div>
                            <p class="font-bold text-slate-900 dark:text-white mb-1">Belum ada riwayat pendidikan</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Tambahkan latar belakang pendidikanmu di sini.</p>
                        </div>

                        <div v-for="(edu, index) in form.education" :key="index" class="p-8 bg-slate-50 dark:bg-slate-800/50 rounded-3xl relative group border border-slate-100 dark:border-slate-700">
                            <button class="absolute top-6 right-6 p-2 text-slate-300 hover:text-red-500 transition-colors" @click="removeEducation(index)">
                                <Trash2 class="w-5 h-5" />
                            </button>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Institusi</label>
                                    <input v-model="edu.school" type="text" placeholder="Universitas Indonesia" class="w-full bg-white dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 shadow-sm" />
                                </div>
                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Gelar</label>
                                    <input v-model="edu.degree" type="text" placeholder="S1 Teknik Informatika" class="w-full bg-white dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 shadow-sm" />
                                </div>
                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Tahun Mulai</label>
                                    <input v-model="edu.start_year" type="number" class="w-full bg-white dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 shadow-sm" />
                                </div>
                                <div class="space-y-2">
                                    <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Tahun Lulus (Kosongkan jika aktif)</label>
                                    <input v-model="edu.end_year" type="number" class="w-full bg-white dark:bg-slate-800 border-none rounded-xl px-4 py-3 text-sm font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 shadow-sm" />
                                </div>
                            </div>
                        </div>

                        <button type="button" class="w-full py-6 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-3xl text-slate-400 dark:text-slate-500 font-bold text-sm hover:border-primary-500 hover:text-primary-600 transition-all flex items-center justify-center gap-2" @click="addEducation">
                            <Plus class="w-5 h-5" />
                            Tambah Pendidikan
                        </button>
                    </div>

                    <!-- Skills Tab -->
                    <div v-if="activeTab === 'skills'" class="space-y-8">
                        <div class="space-y-2">
                            <label class="text-sm font-bold text-slate-700 dark:text-slate-300">Tambah Keahlian</label>
                            <div class="flex gap-4">
                                <input 
                                    v-model="newSkill" 
                                    type="text"
                                    placeholder="Contoh: UI/UX Design, Laravel, Python..." 
                                    class="flex-1 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl px-6 py-4 text-sm font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 transition-all" 
                                    @keyup.enter="addSkill"
                                />
                                <button type="button" class="bg-slate-900 dark:bg-primary-600 text-white px-8 rounded-2xl font-bold text-sm hover:bg-slate-800 dark:hover:bg-primary-700 transition-all" @click="addSkill">
                                    Tambah
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <div v-for="(skill, index) in form.skills" :key="index" class="bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 px-5 py-2.5 rounded-full font-bold text-sm border border-primary-100 dark:border-primary-900/30 flex items-center gap-2 group">
                                {{ skill }}
                                <button class="hover:text-red-500 transition-colors" @click="removeSkill(index)">
                                    <X class="w-4 h-4" />
                                </button>
                            </div>
                            <div v-if="form.skills.length === 0" class="text-slate-400 dark:text-slate-500 text-sm italic">Belum ada keahlian yang ditambahkan.</div>
                        </div>
                    </div>

                    <!-- Files Tab -->
                    <div v-if="activeTab === 'files'" class="space-y-10">
                        <FileUpload 
                            v-model="form.cv" 
                            label="Kurikulum Vitae (CV)" 
                            accept=".pdf" 
                            :max-size="2"
                            :current-file="userDetail.cv_path"
                            :error="errors.cv"
                        />

                        <FileUpload 
                            v-model="form.portfolio" 
                            label="Portofolio (Opsional)" 
                            accept=".pdf,.zip,.rar" 
                            :max-size="5"
                            :current-file="userDetail.portfolio_path"
                            :error="errors.portfolio"
                        />
                    </div>

                    <!-- Privacy Tab -->
                    <div v-if="activeTab === 'privacy'" class="space-y-8">
                        <div class="bg-indigo-50/50 dark:bg-indigo-900/10 p-8 rounded-[2rem] border border-indigo-100/50 dark:border-indigo-900/30">
                            <div class="flex items-start gap-6">
                                <div class="bg-white dark:bg-slate-800 p-3 rounded-2xl shadow-sm text-indigo-600 dark:text-indigo-400">
                                    <Sparkles class="w-6 h-6" />
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Persetujuan AI (AI Consent)</h3>
                                    <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed mb-6">
                                        Dengan mengaktifkan fitur ini, Anda memberikan izin kepada sistem InternHub untuk memproses data menggunakan teknologi kecerdasan buatan (Google Gemini) guna memberikan manfaat:
                                    </p>
                                    <ul v-if="!authStore.isHR && !authStore.isAdmin" class="space-y-3 mb-8">
                                        <li class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                                            <div class="w-1.5 h-1.5 bg-indigo-500 rounded-full"></div>
                                            Rekomendasi lowongan yang lebih akurat.
                                        </li>
                                        <li class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                                            <div class="w-1.5 h-1.5 bg-indigo-500 rounded-full"></div>
                                            Review profil otomatis oleh AI Career Coach.
                                        </li>
                                        <li class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                                            <div class="w-1.5 h-1.5 bg-indigo-500 rounded-full"></div>
                                            Bantuan ringkasan profil untuk membantu HR mereview lamaranmu lebih cepat.
                                        </li>
                                    </ul>
                                    <ul v-else class="space-y-3 mb-8">
                                        <li class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                                            <div class="w-1.5 h-1.5 bg-indigo-500 rounded-full"></div>
                                            Asisten cerdas dalam penulisan deskripsi lowongan kerja.
                                        </li>
                                        <li class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                                            <div class="w-1.5 h-1.5 bg-indigo-500 rounded-full"></div>
                                            Analisis kandidat otomatis berdasarkan kualifikasi lowongan.
                                        </li>
                                        <li class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                                            <div class="w-1.5 h-1.5 bg-indigo-500 rounded-full"></div>
                                            Pembuatan pertanyaan interview otomatis yang dipersonalisasi.
                                        </li>
                                    </ul>

                                    <label class="flex items-center gap-4 cursor-pointer group">
                                        <div class="relative">
                                            <input 
                                                v-model="form.ai_consent" 
                                                type="checkbox" 
                                                class="sr-only"
                                            />
                                            <div 
                                                class="w-14 h-8 rounded-full transition-colors duration-200"
                                                :class="form.ai_consent ? 'bg-primary-600' : 'bg-slate-200 dark:bg-slate-700'"
                                            ></div>
                                            <div 
                                                class="absolute left-1 top-1 w-6 h-6 bg-white dark:bg-slate-300 rounded-full transition-transform duration-200"
                                                :class="form.ai_consent ? 'translate-x-6' : ''"
                                            ></div>
                                        </div>
                                        <span class="text-sm font-bold" :class="form.ai_consent ? 'text-primary-700 dark:text-primary-400' : 'text-slate-500 dark:text-slate-500'">
                                            {{ form.ai_consent ? 'Saya setuju data saya diproses oleh AI' : 'Saya tidak setuju data saya diproses oleh AI' }}
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 bg-amber-50 dark:bg-amber-900/10 rounded-2xl border border-amber-100 dark:border-amber-900/30 flex gap-4">
                            <AlertCircle class="w-5 h-5 text-amber-500 shrink-0" />
                            <p class="text-xs text-amber-700 dark:text-amber-400 leading-relaxed">
                                <strong>Penting:</strong> Jika kamu menonaktifkan fitur ini, beberapa fitur asisten karir cerdas mungkin tidak akan berfungsi secara optimal, dan HR tidak akan dapat menggunakan asisten AI untuk mereview profilmu.
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
/* Optional: specific styles for the AI switch */
</style>
