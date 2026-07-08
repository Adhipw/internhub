<script setup lang="ts">
import { Link, router as inertiaRouter } from '@inertiajs/vue3';
import { computed } from 'vue';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { 
    ChevronLeft, Briefcase, MapPin, Calendar, 
    Download, ExternalLink, Clock, Building2,
    CheckCircle2, AlertCircle, Info, X, Award, ClipboardCheck
} from 'lucide-vue-next';
import StatusBadge from '@/Components/StatusBadge.vue';
import Breadcrumbs from '@/Components/Breadcrumbs.vue';
import ApplicationMessages from '@/Components/ApplicationMessages.vue';

const props = defineProps<{
    application: any;
}>();
const application = computed(() => props.application);

const steps = computed(() => [
    { label: 'Terkirim', desc: 'Lamaran sukses dikirim ke HR.' },
    { label: 'Review Berkas', desc: 'CV & Portofolio sedang dievaluasi.' },
    { label: 'Wawancara', desc: 'Wawancara dengan HR/User.' },
    { label: 'Offering Letter', desc: 'Penawaran magang resmi.' }
]);

const currentStepIndex = computed(() => {
    switch (application.value?.status) {
        case 'pending':
            return 0;
        case 'reviewing':
            return 1;
        case 'interviewing':
            return 2;
        case 'offered':
        case 'accepted':
        case 'completed':
            return 3;
        default:
            return 0;
    }
});

const isRejected = computed(() => application.value?.status === 'rejected');
const isWithdrawn = computed(() => application.value?.status === 'withdrawn');

const formatFileName = (path: string) => {
    return path.split('/').pop()?.replace(/^.*_/, '');
};

const withdraw = async () => {
    if (confirm('Apakah Anda yakin ingin menarik lamaran ini? Tindakan ini tidak dapat dibatalkan.') && application.value) {
        inertiaRouter.post(`/my-applications/${application.value.id}/withdraw`, {}, {
            onError: () => alert('Gagal menarik lamaran.'),
        });
    }
};
</script>

<template>
    <DashboardLayout>
        <div class="max-w-5xl mx-auto space-y-10 pb-20">
            <!-- Breadcrumbs -->
            <Breadcrumbs
:items="[
                { label: 'Lamaran Saya', href: '/my-applications' },
                { label: 'Detail Lamaran' }
            ]" />

            <!-- ATS Visual Stepper Card -->
            <div class="bg-white dark:bg-neutral-900 rounded-2xl p-10 border border-slate-100 dark:border-neutral-800 shadow-sm space-y-8">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                        <span class="w-1.5 h-6 bg-primary-600 rounded-full"></span>
                        Status Lamaran Anda
                    </h2>
                    <StatusBadge :status="application.status" size="lg" />
                </div>

                <!-- Stepper Row -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8 relative pt-4">
                    <!-- Connective Horizontal line (Desktop only) -->
                    <div class="hidden md:block absolute left-12 right-12 top-10 h-1 bg-slate-100 dark:bg-neutral-800 z-0">
                        <div 
                            class="h-full bg-primary-600 transition-colors duration-500" 
                            :style="{ width: isRejected || isWithdrawn ? '0%' : (currentStepIndex / 3 * 100) + '%' }"
                        ></div>
                    </div>

                    <div 
                        v-for="(step, idx) in steps" 
                        :key="idx" 
                        class="flex flex-row md:flex-col items-center md:items-center text-left md:text-center gap-4 md:gap-4 relative z-10 group"
                    >
                        <!-- Step Icon/Indicator -->
                        <div 
                            class="w-12 h-12 rounded-full flex items-center justify-center shrink-0 border-4 transition-colors duration-300 font-bold text-sm shadow-sm"
                            :class="[
                                isRejected || isWithdrawn
                                    ? 'bg-slate-100 dark:bg-neutral-800 border-white dark:border-neutral-900 text-slate-400 dark:text-neutral-600'
                                    : idx < currentStepIndex
                                        ? 'bg-primary-600 border-primary-100 dark:border-primary-900 text-white'
                                        : idx === currentStepIndex
                                            ? 'bg-primary-50 dark:bg-primary-950/20 border-primary-600 text-primary-600  ring-4 ring-primary-500/10 dark:ring-primary-500/20'
                                            : 'bg-slate-50 dark:bg-neutral-950 border-slate-100 dark:border-neutral-800 text-slate-400 dark:text-neutral-600'
                            ]"
                        >
                            <CheckCircle2 v-if="!isRejected && !isWithdrawn && idx < currentStepIndex" class="w-5 h-5" />
                            <Clock v-else-if="!isRejected && !isWithdrawn && idx === currentStepIndex" class="w-5 h-5 animate-spin" />
                            <span v-else>{{ idx + 1 }}</span>
                        </div>

                        <!-- Step Labels -->
                        <div class="space-y-1">
                            <p 
                                class="text-sm font-bold transition-colors"
                                :class="[
                                    idx === currentStepIndex && !isRejected && !isWithdrawn
                                        ? 'text-primary-600'
                                        : idx <= currentStepIndex && !isRejected && !isWithdrawn
                                            ? 'text-slate-900 dark:text-white'
                                            : 'text-slate-400 dark:text-neutral-500'
                                ]"
                            >
                                {{ step.label }}
                            </p>
                            <p class="text-xs text-slate-400 dark:text-neutral-500 font-semibold max-w-[180px] mx-auto leading-relaxed">
                                {{ step.desc }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Custom Alert for Rejected / Withdrawn Statuses -->
                <div v-if="isRejected" class="p-6 bg-red-50 dark:bg-red-950/20 rounded-2xl border border-red-100 dark:border-red-900/30 flex items-start gap-4">
                    <AlertCircle class="w-6 h-6 text-red-500 shrink-0 mt-0.5" />
                    <div class="space-y-1">
                        <p class="text-sm font-bold text-red-900 dark:text-red-400">Lamaran Tidak Lolos Seleksi</p>
                        <p class="text-xs text-red-700 dark:text-red-300 leading-relaxed">Terima kasih atas minat Anda pada lowongan magang ini. Sayangnya, perusahaan belum bisa melanjutkan lamaran Anda ke tahap berikutnya. Jangan berkecil hati, mari cari peluang magang hebat lainnya di InternHub!</p>
                    </div>
                </div>

                <div v-else-if="isWithdrawn" class="p-6 bg-slate-50 dark:bg-neutral-950 rounded-2xl border border-slate-100 dark:border-neutral-800 flex items-start gap-4">
                    <X class="w-6 h-6 text-slate-500 shrink-0 mt-0.5" />
                    <div class="space-y-1">
                        <p class="text-sm font-bold text-slate-950 dark:text-white">Lamaran Telah Ditarik</p>
                        <p class="text-xs text-slate-600 dark:text-neutral-400 leading-relaxed">Anda telah berhasil menarik kembali lamaran Anda untuk lowongan magang ini. Lowongan ini tidak akan diproses lebih lanjut.</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 items-start">
                <!-- Left: Application Details -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Main Card -->
                    <div class="bg-white rounded-2xl p-10 border border-slate-100 shadow-sm space-y-10">
                        <div class="flex items-start justify-between gap-6">
                            <div class="flex gap-6">
                                <div class="w-20 h-20 bg-slate-50 rounded-2xl flex items-center justify-center shrink-0 border border-slate-50 overflow-hidden shadow-inner">
                                    <img v-if="application.internship.company.logo_url" loading="lazy" decoding="async" :src="application.internship.company.logo_url" class="w-full h-full object-cover" />
                                    <Briefcase v-else class="w-10 h-10 text-slate-200" />
                                </div>
                                <div>
                                    <h1 class="text-2xl font-bold text-slate-900 mb-2">{{ application.internship.title }}</h1>
                                    <div class="flex items-center gap-2 mb-4">
                                        <span class="font-bold text-primary-600">{{ application.internship.company.name }}</span>
                                        <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                                        <span class="text-sm font-medium text-slate-500">{{ application.internship.location }}</span>
                                    </div>
                                    <StatusBadge :status="application.status" />
                                </div>
                            </div>

                            <!-- Action Area (Batch 7: Onboarding & Certificates) -->
                            <div v-if="['accepted', 'completed'].includes(application.status)" class="flex flex-col sm:flex-row gap-4">
                                <Link 
                                    v-if="application.status === 'accepted'"
                                    :href="'/my-applications/' + application.id + '/onboarding'"
                                    class="flex-1 bg-primary-600 text-white px-8 py-4 rounded-2xl font-bold text-sm hover:bg-primary-700 transition-colors shadow-lg shadow-primary-900/20 text-center flex items-center justify-center gap-2"
                                >
                                    <ClipboardCheck class="w-5 h-5" />
                                    Lengkapi Onboarding
                                </Link>

                                <a 
                                    v-if="application.status === 'completed'"
                                    :href="'/api/v1/applications/' + application.id + '/certificate?download=1'"
                                    target="_blank"
                                    class="flex-1 bg-emerald-600 text-white px-8 py-4 rounded-2xl font-bold text-sm hover:bg-emerald-700 transition-colors shadow-lg shadow-emerald-900/20 text-center flex items-center justify-center gap-2"
                                >
                                    <Award class="w-5 h-5" />
                                    Download Sertifikat
                                </a>
                            </div>
                        </div>

                        <div class="border-t border-slate-50 pt-10 space-y-8">
                            <div class="space-y-4">
                                <h3 class="text-lg font-bold text-slate-900">Pesan Lamaran (Cover Letter)</h3>
                                <p class="text-slate-600 leading-relaxed bg-slate-50 p-6 rounded-2xl border border-slate-100 italic">
                                    "{{ application.cover_letter || 'Tidak ada pesan tambahan.' }}"
                                </p>
                            </div>

                            <div class="space-y-4">
                                <h3 class="text-lg font-bold text-slate-900">Dokumen Terlampir</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <a 
                                        v-if="application.cv_snapshot"
                                        :href="'/storage-private/cvs/' + application.cv_snapshot.split('/').pop()" 
                                        target="_blank"
                                        class="flex items-center gap-4 p-4 rounded-2xl border border-slate-100 hover:border-primary-200 hover:bg-primary-50/30 transition-colors group"
                                    >
                                        <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center text-primary-600 group-hover:bg-primary-600 group-hover:text-white transition-colors">
                                            <Download class="w-5 h-5" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-bold text-slate-900 truncate">Curriculum Vitae (CV)</p>
                                            <p class="text-xs text-slate-500 font-medium">Snapshot PDF</p>
                                        </div>
                                    </a>

                                    <a 
                                        v-if="application.portfolio_snapshot"
                                        :href="'/storage-private/portfolios/' + application.portfolio_snapshot.split('/').pop()" 
                                        target="_blank"
                                        class="flex items-center gap-4 p-4 rounded-2xl border border-slate-100 hover:border-primary-200 hover:bg-primary-50/30 transition-colors group"
                                    >
                                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                            <ExternalLink class="w-5 h-5" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-bold text-slate-900 truncate">Portofolio Kerja</p>
                                            <p class="text-xs text-slate-500 font-medium">Dokumen Pendukung</p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Evaluation Section (Batch 7) -->
                    <div v-if="application.evaluations && application.evaluations.length > 0" class="space-y-6">
                        <div v-for="evaluation in application.evaluations" :key="evaluation.id" class="bg-white rounded-2xl p-10 border border-slate-100 shadow-sm relative overflow-hidden">
                            <div class="absolute top-0 right-0 p-8">
                                <div :class="['px-4 py-2 rounded-2xl text-xs font-medium', evaluation.final_status === 'completed' ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600']">
                                    {{ evaluation.final_status === 'completed' ? 'Magang Lolos' : 'Magang Selesai' }}
                                </div>
                            </div>
                            
                            <h2 class="text-2xl font-bold text-slate-900 mb-2">{{ evaluation.title }}</h2>
                            <p class="text-xs text-slate-400 font-medium mb-8">Evaluasi Oleh {{ evaluation.mentor.name }}</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 mb-10">
                                <div>
                                    <h4 class="text-xs font-bold text-slate-400 font-medium mb-4">Ringkasan Performa</h4>
                                    <p class="text-sm text-slate-600 leading-relaxed italic">"{{ evaluation.summary }}"</p>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-400 font-medium mb-4">Hasil Penilaian</h4>
                                    <div class="space-y-4">
                                        <div v-for="(val, key) in evaluation.metrics" :key="key">
                                            <div class="flex items-center justify-between text-xs mb-1.5">
                                                <span class="capitalize text-slate-600">{{ String(key).replace('_', ' ') }}</span>
                                                <span class="font-bold text-slate-900">{{ val }}/5</span>
                                            </div>
                                            <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                                                <div class="h-full bg-primary-600 rounded-full" :style="{ width: (val * 20) + '%' }"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-if="evaluation.recommendation" class="p-6 bg-primary-50 rounded-2xl border border-primary-100">
                                <h4 class="text-xs font-bold text-primary-400 font-medium mb-2">Rekomendasi Mentor</h4>
                                <p class="text-sm text-primary-900 font-medium">{{ evaluation.recommendation }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tugas & Sesi Bimbingan (Fitur Baru) -->
                    <div v-if="['accepted', 'completed'].includes(application.status)" class="space-y-8">
                        <!-- Sesi Bimbingan -->
                        <div v-if="application.mentoring_sessions && application.mentoring_sessions.length > 0" class="bg-white rounded-2xl p-8 border border-slate-100 shadow-sm">
                            <h3 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2">
                                <Calendar class="w-5 h-5 text-blue-600" />
                                Jadwal Sesi Bimbingan Mentor
                            </h3>
                            <!-- Catatan LSP/Skripsi -->
                            <div class="mb-4 text-xs bg-blue-50 text-blue-700 p-3 rounded-lg border border-blue-100 font-medium italic">
                                * Fitur (Skripsi/LSP): Menampilkan daftar sesi meeting/bimbingan dari mentor. Terintegrasi langsung dengan modul Mentor.
                            </div>
                            <div class="space-y-4">
                                <div v-for="session in application.mentoring_sessions" :key="session.id" class="p-5 rounded-xl border border-slate-100 hover:border-blue-200 bg-slate-50/50 transition-colors">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div class="space-y-1">
                                            <h4 class="font-bold text-slate-900">{{ session.title }}</h4>
                                            <p class="text-sm text-slate-600">{{ session.description || 'Tidak ada deskripsi' }}</p>
                                        </div>
                                        <div class="flex items-center gap-3 shrink-0">
                                            <div class="text-right">
                                                <p class="text-xs font-bold text-slate-900">{{ new Date(session.scheduled_at.replace(' ', 'T') + 'Z').toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</p>
                                                <p class="text-xs text-slate-500">{{ session.duration_minutes }} Menit</p>
                                            </div>
                                            <StatusBadge :status="session.status" />
                                        </div>
                                    </div>
                                    <div v-if="session.meeting_link" class="mt-4 pt-4 border-t border-slate-100">
                                        <a :href="session.meeting_link" target="_blank" class="text-sm font-bold text-blue-600 hover:text-blue-700 flex items-center gap-1">
                                            Join Meeting <ExternalLink class="w-4 h-4" />
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Daftar Tugas -->
                        <div v-if="application.tasks && application.tasks.length > 0" class="bg-white rounded-2xl p-8 border border-slate-100 shadow-sm">
                            <h3 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2">
                                <ClipboardCheck class="w-5 h-5 text-emerald-600" />
                                Tugas dari Mentor
                            </h3>
                            <!-- Catatan LSP/Skripsi -->
                            <div class="mb-4 text-xs bg-emerald-50 text-emerald-700 p-3 rounded-lg border border-emerald-100 font-medium italic">
                                * Fitur (Skripsi/LSP): Menampilkan tugas dari mentor. Menghilangkan "blank spot" sehingga flow magang menjadi utuh.
                            </div>
                            <div class="grid grid-cols-1 gap-4">
                                <div v-for="task in application.tasks" :key="task.id" class="p-5 rounded-xl border border-slate-100 hover:border-emerald-200 bg-slate-50/50 transition-colors">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-bold text-slate-900">{{ task.title }}</h4>
                                        <StatusBadge :status="task.status" />
                                    </div>
                                    <p class="text-sm text-slate-600 mb-4">{{ task.description || 'Tidak ada deskripsi' }}</p>
                                    <div class="flex items-center gap-4 text-xs font-medium text-slate-500">
                                        <div class="flex items-center gap-1.5" v-if="task.due_date">
                                            <Calendar class="w-4 h-4" />
                                            Tenggat: {{ new Date(task.due_date.replace(' ', 'T') + 'Z').toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <AlertCircle class="w-4 h-4" :class="task.priority === 1 ? 'text-red-500' : task.priority === 2 ? 'text-orange-500' : 'text-blue-500'" />
                                            Prioritas: {{ task.priority === 1 ? 'Tinggi' : task.priority === 2 ? 'Sedang' : 'Rendah' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Company Info Mini Card -->
                    <div class="bg-slate-900 rounded-2xl p-10 text-white relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-primary-600/20 rounded-full blur-[80px] -mr-32 -mt-32"></div>
                        
                        <div class="relative z-10 space-y-6">
                            <h3 class="text-xl font-bold">Tentang Perusahaan</h3>
                            <div class="flex items-center gap-6">
                                <div class="w-16 h-16 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/10 shrink-0">
                                    <Building2 class="w-8 h-8 text-white/50" />
                                </div>
                                <div>
                                    <h4 class="text-lg font-bold">{{ application.internship.company.name }}</h4>
                                    <p class="text-sm text-white/60">{{ application.internship.company.location }}</p>
                                </div>
                            </div>
                            <p class="text-sm text-white/70 leading-relaxed">
                                {{ application.internship.company.description || 'Perusahaan terverifikasi yang bekerja sama dengan InternHub.' }}
                            </p>
                            <Link :href="'/internships/' + application.internship.slug" class="inline-flex items-center gap-2 text-sm font-bold text-primary-400 hover:text-primary-300 transition-colors">
                                Lihat Detail Lowongan
                                <ExternalLink class="w-4 h-4" />
                            </Link>
                        </div>
                    </div>

                    <!-- In-App Messaging -->
                    <div class="pt-8 border-t border-slate-100">
                        <ApplicationMessages :application-id="application.id" />
                    </div>
                </div>

                <!-- Right: Timeline -->
                <div class="bg-white rounded-2xl p-10 border border-slate-100 shadow-sm space-y-10">
                    <h3 class="text-xl font-bold text-slate-900">Status & Timeline</h3>

                    <div class="space-y-10 relative">
                        <!-- Vertical Line -->
                        <div class="absolute left-4 top-2 bottom-2 w-0.5 bg-slate-100"></div>

                        <div v-for="(event, index) in application.timeline" :key="index" class="relative pl-12">
                            <!-- Dot -->
                            <div 
                                class="absolute left-0 w-8 h-8 rounded-full flex items-center justify-center z-10 border-4 border-white shadow-sm"
                                :class="index === 0 ? 'bg-primary-600 text-white' : 'bg-slate-100 text-slate-400'"
                            >
                                <CheckCircle2 v-if="index === 0" class="w-4 h-4" />
                                <Clock v-else class="w-4 h-4" />
                            </div>

                            <div class="space-y-1">
                                <p class="text-sm font-bold text-slate-900">{{ event.label }}</p>
                                <p class="text-xs text-slate-500 leading-relaxed">{{ event.description }}</p>
                                <p class="text-xs text-slate-400 font-medium pt-2">
                                    {{ new Date(event.date.includes('T') ? event.date : event.date.replace(' ', 'T') + 'Z').toLocaleDateString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-primary-50 p-6 rounded-2xl border border-primary-100 flex items-start gap-4">
                        <Info class="w-5 h-5 text-primary-600 shrink-0 mt-0.5" />
                        <div class="space-y-1">
                            <p class="text-xs font-bold text-primary-900">Tips Untukmu</p>
                            <p class="text-xs text-primary-700 leading-relaxed">Pastikan nomor WhatsApp aktif untuk memudahkan perusahaan menghubungimu jika terpilih untuk tahap selanjutnya.</p>
                        </div>
                    </div>

                    <!-- Withdraw Action -->
                    <div v-if="application.status === 'pending'" class="pt-6 border-t border-slate-50">
                        <button 
                            class="w-full py-4 text-xs font-bold text-red-500 hover:bg-red-50 rounded-2xl transition-colors font-medium flex items-center justify-center gap-2"
                            @click="withdraw"
                        >
                            <X class="w-4 h-4" />
                            Tarik Lamaran Ini
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

