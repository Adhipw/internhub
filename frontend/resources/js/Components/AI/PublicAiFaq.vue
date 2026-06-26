<script setup>
import { ref } from 'vue';
import { Sparkles, MessageSquare, Send, Bot } from 'lucide-vue-next';
import api from '@/Services/api';

const question = ref('');
const conversation = ref([]);
const isLoading = ref(false);

const quickQuestions = [
    "Cara mendaftar?",
    "Apakah gratis?",
    "Syarat magang?"
];

const ask = async (q) => {
    const query = q || question.value;
    if (!query) return;
    
    const userMsg = { role: 'user', text: query };
    conversation.value.push(userMsg);
    
    if (!q) question.value = '';
    isLoading.value = true;

    try {
        const response = await api.post('/ai/public/faq', {
            question: query
        });
        conversation.value.push({ role: 'assistant', text: response.data.answer });
    } catch (err) {
        conversation.value.push({ role: 'assistant', text: 'Maaf, saya sedang mengalami gangguan. Silakan coba lagi nanti.' });
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <div class="bg-neutral-900 rounded-2xl border border-white/10 overflow-hidden shadow-2xl flex flex-col h-[600px] group transition-all duration-500 hover:border-indigo-500/30">
        <!-- Header -->
        <div class="p-8 border-b border-white/10 bg-gradient-to-r from-indigo-600/20 to-transparent flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-600/20">
                    <Bot class="w-6 h-6 text-white" />
                </div>
                <div>
                    <h3 class="text-xl font-bold text-white tracking-tight">AI FAQ Support</h3>
                    <p class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest flex items-center gap-1">
                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-ping"></span>
                        Online Sekarang
                    </p>
                </div>
            </div>
            <Sparkles class="w-6 h-6 text-white/20 group-hover:text-indigo-500 transition-colors" />
        </div>

        <!-- Chat Area -->
        <div class="flex-1 overflow-y-auto p-8 space-y-6 custom-scrollbar">
            <div v-if="conversation.length === 0" class="h-full flex flex-col items-center justify-center text-center space-y-6 opacity-50">
                <MessageSquare class="w-16 h-16 text-white/20" />
                <p class="text-white/60 font-bold text-sm max-w-[200px]">Halo! Ada yang bisa saya bantu terkait InternHub?</p>
            </div>

            <div
v-for="(msg, i) in conversation" :key="i" 
                 :class="['flex', msg.role === 'user' ? 'justify-end' : 'justify-start']"
                 class="animate-slide-up">
                <div
:class="[
                    'max-w-[85%] p-5 rounded-2xl text-sm font-medium leading-relaxed',
                    msg.role === 'user' 
                        ? 'bg-indigo-600 text-white rounded-tr-none' 
                        : 'bg-white/5 text-neutral-300 border border-white/10 rounded-tl-none'
                ]">
                    {{ msg.text }}
                </div>
            </div>

            <div v-if="isLoading" class="flex justify-start ">
                <div class="bg-white/5 border border-white/10 p-5 rounded-2xl rounded-tl-none flex gap-2">
                    <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full animate-bounce"></span>
                    <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                    <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full animate-bounce [animation-delay:0.4s]"></span>
                </div>
            </div>
        </div>

        <!-- Footer / Input -->
        <div class="p-8 border-t border-white/10 bg-white/5">
            <div class="flex flex-wrap gap-2 mb-6">
                <button 
                    v-for="q in quickQuestions" 
                    :key="q"
                    class="px-4 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-full text-[10px] font-bold text-neutral-400 hover:text-white transition-all uppercase tracking-widest"
                    @click="ask(q)"
                >
                    {{ q }}
                </button>
            </div>

            <div class="relative">
                <input 
                    v-model="question"
                    type="text" 
                    placeholder="Tanya apapun di sini..."
                    class="w-full bg-white/5 border-white/10 rounded-2xl py-4 pl-6 pr-16 text-white placeholder-white/20 focus:border-indigo-500 focus:ring-0 transition-all font-medium"
                    @keyup.enter="ask()"
                />
                <button 
                    :disabled="isLoading || !question"
                    class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center text-white hover:bg-indigo-700 transition-all disabled:opacity-50"
                    @click="ask()"
                >
                    <Send class="w-5 h-5" />
                </button>
            </div>
        </div>
    </div>
</template>
