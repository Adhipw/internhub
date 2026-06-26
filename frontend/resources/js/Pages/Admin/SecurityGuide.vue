<script setup lang="ts">
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { ShieldCheck, ShieldAlert, FileText, CheckCircle2, AlertTriangle, Building2, Link as LinkIcon, Info } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import { useLangStore } from '@/Stores/lang';

const langStore = useLangStore();
const t = (key: string) => langStore.t(key);
</script>

<template>
    <DashboardLayout>
        <div class="max-w-4xl mx-auto space-y-12 pb-20 animate-fade-in">
            <!-- Header -->
            <div class="space-y-4">
                <Link href="/admin/dashboard" class="inline-flex items-center gap-2 text-sm font-bold text-slate-500 hover:text-primary-600 transition-colors">
                    &larr; Kembali ke Dashboard
                </Link>
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-red-50 dark:bg-red-900/20 text-red-600 rounded-full text-[10px] font-semibold text-xs tracking-wide border border-red-100 dark:border-red-900/30">
                    <ShieldAlert class="w-3 h-3" />
                    Security Guideline
                </div>
                <h1 class="text-4xl md:text-5xl font-bold text-slate-900 dark:text-white tracking-tight">
                    Panduan Verifikasi Perusahaan
                </h1>
                <p class="text-slate-500 dark:text-slate-400 font-medium text-lg leading-relaxed">
                    Demi keamanan data mahasiswa, seluruh perusahaan yang mendaftar di InternHub wajib melalui proses verifikasi legalitas yang ketat. Berikut adalah panduan dan SOP untuk melakukan moderasi.
                </p>
            </div>

            <!-- Warning Callout -->
            <div class="bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-900/30 rounded-2xl p-6 md:p-8 flex gap-6 items-start shadow-sm">
                <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-2xl flex items-center justify-center shrink-0">
                    <AlertTriangle class="w-6 h-6 text-amber-600 dark:text-amber-500" />
                </div>
                <div class="space-y-2">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Peringatan Penting</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm leading-relaxed">
                        Menyetujui perusahaan palsu atau mencurigakan dapat membahayakan data pribadi (PII) mahasiswa dan membuka celah penipuan lowongan kerja fiktif. Jangan pernah memverifikasi perusahaan jika Anda ragu terhadap kredibilitas mereka.
                    </p>
                </div>
            </div>

            <!-- SOP Steps -->
            <div class="space-y-8">
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
                    <ShieldCheck class="w-6 h-6 text-primary-600" />
                    Standar Operasional Prosedur (SOP)
                </h2>

                <div class="grid gap-6">
                    <!-- Step 1 -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl p-8 border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group hover:border-primary-300 dark:hover:border-primary-700 transition-colors">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center text-5xl font-bold text-slate-100 dark:text-slate-700/50 -z-10 group-hover:scale-110 transition-transform">
                            1
                        </div>
                        <div class="flex items-center gap-4 mb-4">
                            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 text-blue-600 rounded-xl">
                                <Building2 class="w-6 h-6" />
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Verifikasi Identitas Perusahaan</h3>
                        </div>
                        <ul class="space-y-3 text-sm text-slate-600 dark:text-slate-400 ml-16">
                            <li class="flex items-start gap-2">
                                <CheckCircle2 class="w-4 h-4 text-green-500 mt-0.5 shrink-0" />
                                Pastikan nama perusahaan cocok dengan entitas bisnis yang sah (PT, CV, dll).
                            </li>
                            <li class="flex items-start gap-2">
                                <CheckCircle2 class="w-4 h-4 text-green-500 mt-0.5 shrink-0" />
                                Cek kecocokan alamat yang didaftarkan dengan alamat asli di Google Maps.
                            </li>
                            <li class="flex items-start gap-2">
                                <CheckCircle2 class="w-4 h-4 text-green-500 mt-0.5 shrink-0" />
                                Periksa apakah nomor telepon yang didaftarkan aktif dan resmi.
                            </li>
                        </ul>
                    </div>

                    <!-- Step 2 -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl p-8 border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group hover:border-primary-300 dark:hover:border-primary-700 transition-colors">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center text-5xl font-bold text-slate-100 dark:text-slate-700/50 -z-10 group-hover:scale-110 transition-transform">
                            2
                        </div>
                        <div class="flex items-center gap-4 mb-4">
                            <div class="p-3 bg-purple-50 dark:bg-purple-900/20 text-purple-600 rounded-xl">
                                <LinkIcon class="w-6 h-6" />
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Verifikasi Domain & Website</h3>
                        </div>
                        <ul class="space-y-3 text-sm text-slate-600 dark:text-slate-400 ml-16">
                            <li class="flex items-start gap-2">
                                <CheckCircle2 class="w-4 h-4 text-green-500 mt-0.5 shrink-0" />
                                Pastikan website perusahaan dapat diakses dan profesional.
                            </li>
                            <li class="flex items-start gap-2">
                                <CheckCircle2 class="w-4 h-4 text-green-500 mt-0.5 shrink-0" />
                                Email representatif HR harus menggunakan domain perusahaan (misal: <code>hr@gojek.com</code>), <strong>bukan</strong> email gratisan (<code>@gmail.com</code> atau <code>@yahoo.com</code>).
                            </li>
                        </ul>
                    </div>

                    <!-- Step 3 -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl p-8 border border-slate-200 dark:border-slate-800 shadow-sm relative overflow-hidden group hover:border-primary-300 dark:hover:border-primary-700 transition-colors">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center text-5xl font-bold text-slate-100 dark:text-slate-700/50 -z-10 group-hover:scale-110 transition-transform">
                            3
                        </div>
                        <div class="flex items-center gap-4 mb-4">
                            <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 rounded-xl">
                                <FileText class="w-6 h-6" />
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Pemeriksaan Dokumen (Jika Diperlukan)</h3>
                        </div>
                        <ul class="space-y-3 text-sm text-slate-600 dark:text-slate-400 ml-16">
                            <li class="flex items-start gap-2">
                                <Info class="w-4 h-4 text-slate-400 mt-0.5 shrink-0" />
                                Jika terdapat kecurigaan, Admin berhak meminta dokumen legal seperti NIB (Nomor Induk Berusaha), NPWP Perusahaan, atau SIUP sebelum menekan tombol verifikasi.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Call to Action -->
            <div class="bg-primary-900 rounded-2xl p-8 md:p-12 text-center shadow-2xl relative overflow-hidden">
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
                <div class="relative z-10 space-y-6">
                    <h2 class="text-2xl md:text-3xl font-bold text-white">Siap Memverifikasi?</h2>
                    <p class="text-primary-200 max-w-xl mx-auto">
                        Terdapat antrean perusahaan yang sedang menunggu persetujuan Anda. Mari wujudkan ekosistem magang yang aman bagi mahasiswa.
                    </p>
                    <Link href="/admin/companies" class="inline-block bg-white text-primary-900 px-8 py-4 rounded-2xl font-semibold text-xs tracking-wide text-sm hover:bg-primary-50 transition-all hover:scale-105 active:scale-95 shadow-xl">
                        Ke Halaman Moderasi
                    </Link>
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
