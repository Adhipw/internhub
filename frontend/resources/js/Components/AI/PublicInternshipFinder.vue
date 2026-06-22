<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Sparkles, Search, Lightbulb, ArrowRight, Bot, CheckCircle2 } from 'lucide-vue-next';
import api from '@/Services/api';

const prompt = ref('');
const matches = ref([]);
const isLoading = ref(false);
const error = ref('');

const examples = [
    "Saya mahasiswa Informatika semester 6, bisa React dan ingin magang remote.",
    "Saya tertarik UI/UX dan punya portfolio Figma, cari magang di Jakarta.",
    "Mahasiswa Akuntansi semester 4 mencari magang di perusahaan perbankan."
];

const search = async () => {
    if (!prompt.value) return;
    
    isLoading.value = true;
    error.value = '';
    matches.value = [];

    try {
        const response = await api.post('/ai/public/finder', {
            prompt: prompt.value
        });
        matches.value = response.data.matches || [];
    } catch (err) {
        error.value = err.response?.data?.error || 'Maaf, terjadi kesalahan. Silakan coba lagi.';
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <div class="bg-white dark:bg-neutral-900 rounded-[3rem] shadow-3xl border border-neutral-100 dark:border-white/5 overflow-hidden transition-all duration-500">
        <div class="grid grid-cols-1 lg:grid-cols-2">
            <!-- Input Side -->
            <div class="p-10 lg:p-16 border-b lg:border-b-0 lg:border-r border-neutral-100 dark:border-white/5">
                <div class="flex items-center gap-3 mb-10">
                    <div class="w-12 h-12 bg-primary-600 rounded-2xl flex items-center justify-center shadow-lg shadow-primary-600/20">
                        <Sparkles class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <h3 class="text-2xl font-black text-neutral-900 dark:text-white tracking-tight">AI Assistant</h3>
                        <p class="text-xs font-bold text-primary-600 uppercase tracking-widest">Powered by Gemini AI</p>
                    </div>
                </div>

                <div class="space-y-8">
                    <div class="relative group">
                        <div class="absolute -inset-1 bg-gradient-to-r from-primary-600 to-violet-600 rounded-3xl blur opacity-10 group-focus-within:opacity-30 transition duration-500"></div>
                        <textarea 
                            v-model="prompt"
                            placeholder="Ceritakan minat, skill, atau background pendidikanmu..."
                            class="relative w-full rounded-3xl border-neutral-100 dark:border-neutral-800 focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 min-h-[160px] text-lg p-8 bg-white dark:bg-neutral-800 text-neutral-900 dark:text-white placeholder-neutral-400 font-medium transition-all"
                            :disabled="isLoading"
                        ></textarea>
                    </div>

                    <div class="flex flex-col gap-4">
                        <p class="text-[10px] font-black text-neutral-400 uppercase tracking-widest flex items-center gap-2">
                            <Lightbulb class="w-3.5 h-3.5 text-amber-500" />
                            Contoh Prompt:
                        </p>
                        <div class="flex flex-col gap-2">
                            <button 
                                v-for="ex in examples" 
                                :key="ex"
                                @click="prompt = ex"
                                class="text-left text-xs font-bold text-neutral-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 p-3 rounded-xl border border-dashed border-neutral-200 dark:border-neutral-800 transition-all"
                            >
                                "{{ ex }}"
                            </button>
                        </div>
                    </div>

                    <button 
                        @click="search"
                        :disabled="isLoading || !prompt"
                        class="w-full bg-primary-600 text-white py-6 rounded-2xl font-black text-lg hover:bg-primary-700 disabled:opacity-50 transition-all shadow-xl shadow-primary-600/20 flex items-center justify-center gap-4 active-press"
                    >
                        <span v-if="!isLoading">Cari Rekomendasi</span>
                        <span v-else>Menganalisis...</span>
                        <ArrowRight v-if="!isLoading" class="w-6 h-6" />
                        <div v-else class="w-6 h-6 border-3 border-white border-t-transparent rounded-full animate-spin"></div>
                    </button>
                </div>
            </div>

            <!-- Output Side -->
            <div class="p-10 lg:p-16 bg-neutral-50/50 dark:bg-neutral-950/50 flex flex-col">
                <div v-if="!matches.length && !isLoading" class="flex-1 flex flex-col items-center justify-center text-center space-y-6 py-20">
                    <div class="w-24 h-24 bg-white dark:bg-neutral-900 rounded-[2.5rem] flex items-center justify-center shadow-xl border border-neutral-100 dark:border-white/5 animate-float">
                        <Bot class="w-12 h-12 text-neutral-300 dark:text-neutral-700" />
                    </div>
                    <div class="max-w-xs">
                        <h4 class="text-xl font-black text-neutral-900 dark:text-white mb-2">Belum ada hasil</h4>
                        <p class="text-sm font-bold text-neutral-400 leading-relaxed">Tuliskan sesuatu di sebelah kiri, dan asisten AI kami akan memberikan rekomendasi magang terbaik untukmu.</p>
                    </div>
                </div>

                <div v-else-if="isLoading" class="flex-1 flex flex-col items-center justify-center py-20">
                    <div class="relative">
                        <div class="w-20 h-20 bg-primary-600 rounded-[2rem] animate-pulse blur-xl absolute inset-0 opacity-40"></div>
                        <div class="w-20 h-20 bg-primary-600 rounded-[2rem] flex items-center justify-center relative z-10 animate-bounce">
                            <Sparkles class="w-10 h-10 text-white" />
                        </div>
                    </div>
                    <p class="mt-8 text-sm font-black text-primary-600 uppercase tracking-[0.3em] animate-pulse">Menganalisis Kualifikasi...</p>
                </div>

                <div v-else class="flex-1 animate-slide-up flex flex-col h-full">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-neutral-200 dark:border-neutral-800">
                        <div class="w-10 h-10 bg-emerald-500/10 rounded-xl flex items-center justify-center">
                            <CheckCircle2 class="w-6 h-6 text-emerald-500" />
                        </div>
                        <h4 class="text-lg font-black text-neutral-900 dark:text-white">Rekomendasi AI Ditemukan</h4>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar space-y-4">
                        <div v-for="match in matches" :key="match.id" class="bg-white dark:bg-neutral-900 rounded-3xl p-6 shadow-sm border border-neutral-100 dark:border-neutral-800 hover:border-primary-500/50 transition-all group relative">
                            <div class="absolute top-6 right-6 flex items-center justify-center w-12 h-12 rounded-full bg-primary-50 dark:bg-primary-900/20">
                                <span class="text-sm font-black text-primary-600">{{ match.match_score }}%</span>
                            </div>
                            
                            <h5 class="text-lg font-black text-neutral-900 dark:text-white pr-14">{{ match.title }}</h5>
                            <p class="text-sm font-bold text-primary-600 mb-4">{{ match.company }}</p>
                            
                            <div class="flex flex-wrap gap-2 mb-4">
                                <span class="px-3 py-1 bg-neutral-100 dark:bg-neutral-800 rounded-full text-xs font-bold text-neutral-600 dark:text-neutral-400">
                                    {{ match.location }}
                                </span>
                                <span class="px-3 py-1 bg-neutral-100 dark:bg-neutral-800 rounded-full text-xs font-bold text-neutral-600 dark:text-neutral-400">
                                    {{ match.type }}
                                </span>
                            </div>
                            
                            <p class="text-sm text-neutral-600 dark:text-neutral-400 leading-relaxed italic mb-6">
                                "{{ match.explanation }}"
                            </p>
                            
                            <Link :href="'/internships/' + match.slug" class="inline-flex items-center gap-2 text-sm font-black text-white bg-neutral-900 dark:bg-white dark:text-neutral-900 px-6 py-2.5 rounded-xl hover:bg-primary-600 dark:hover:bg-primary-600 dark:hover:text-white transition-all">
                                Lihat Detail
                                <ArrowRight class="w-4 h-4" />
                            </Link>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-neutral-200 dark:border-neutral-800 flex justify-between items-center">
                        <span class="text-[10px] font-black text-neutral-400 uppercase tracking-[0.2em] italic">Dipersembahkan oleh Gemini AI</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.shadow-3xl {
    box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.15);
}

.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
}

.animate-float {
    animation: float 4s infinite ease-in-out;
}

.animate-slide-up {
    animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-15px); }
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.active-press:active {
    transform: scale(0.98);
}
</style>

