<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Head } from '@/Components';

import { route } from 'ziggy-js';
import { Search, Home, ArrowLeft } from 'lucide-vue-next';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineProps<{ status: number }>();

const goBack = () => {
    if (window.history.length > 1) {
        window.history.back();
        return;
    }

    router.visit(route('welcome'));
};
</script>

<template>
    <Head :title="status === 503 ? 'Sedang Update Sistem' : `${status}: Halaman Tidak Ditemukan`" />
    
    <PublicLayout>
        <div class="min-h-[80vh] flex items-center justify-center px-6 pt-20">
            <div class="max-w-2xl w-full text-center space-y-10">
                <!-- 404 Visual -->
                <div class="relative inline-block">
                    <h1 class="text-[12rem] md:text-[18rem] font-bold text-slate-100 leading-none select-none">
                        {{ status }}
                    </h1>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="w-32 h-32 md:w-48 md:h-48 bg-primary-600 rounded-2xl rotate-12 flex items-center justify-center shadow-2xl shadow-primary-200 animate-bounce-slow">
                            <Search class="w-16 h-16 md:w-24 md:h-24 text-white" />
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <h2 class="text-3xl md:text-5xl font-bold text-slate-900">
                        {{ status === 503 ? 'Sedang Update Sistem' : 'Ups! Sepertinya Anda Tersesat.' }}
                    </h2>
                    <p class="text-slate-500 text-lg md:text-xl max-w-lg mx-auto">
                        {{ status === 503 ? 'Mohon maaf, saat ini sedang dilakukan update/redeploy sistem untuk fitur baru. Silakan coba kembali dalam beberapa saat.' : 'Halaman yang Anda cari tidak ditemukan atau telah dipindahkan. Jangan khawatir, mari kembali ke jalur yang benar.' }}
                    </p>
                </div>

                <div class="flex flex-col md:flex-row items-center justify-center gap-4">
                    <Link 
                        :href="route('welcome')" 
                        class="w-full md:w-auto bg-primary-600 text-white px-10 py-4 rounded-full font-bold text-lg hover:bg-primary-700 shadow-xl shadow-primary-100 transition-all flex items-center justify-center gap-2"
                    >
                        <Home class="w-5 h-5" />
                        Kembali ke Beranda
                    </Link>
                    <button 
                        class="w-full md:w-auto bg-white border border-slate-200 text-slate-900 px-10 py-4 rounded-full font-bold text-lg hover:bg-slate-50 transition-all flex items-center justify-center gap-2"
                        @click="goBack"
                    >
                        <ArrowLeft class="w-5 h-5" />
                        Halaman Sebelumnya
                    </button>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@900&display=swap');

h1 {
    font-family: 'Outfit', sans-serif;
}

@keyframes bounceSlow {
    0%, 100% { transform: translateY(0) rotate(12deg); }
    50% { transform: translateY(-20px) rotate(15deg); }
}

.animate-bounce-slow {
    animation: bounceSlow 4s infinite ease-in-out;
}
</style>
